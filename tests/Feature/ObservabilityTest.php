<?php

namespace Tests\Feature;

use App\Console\Commands\MonitoringTestAlertCommand;
use App\Http\Middleware\LogRequestDuration;
use App\Mail\MonitoringAlert;
use App\Models\User;
use App\Providers\ObservabilityServiceProvider;
use App\Support\SchedulerHeartbeat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Monolog\Handler\TestHandler;
use RuntimeException;
use Tests\TestCase;

class ObservabilityTest extends TestCase
{
    use RefreshDatabase;

    private TestHandler $appLog;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'monitoring.alerts.enabled' => true,
            'monitoring.alerts.mail_to' => 'ops@example.test',
            'monitoring.alerts.throttle_minutes' => 30,
        ]);

        $this->appLog = new TestHandler();
        $this->app['log']->channel('app')->getLogger()->pushHandler($this->appLog);

        Route::middleware('web')->group(function () {
            Route::get('_test/ok', fn () => 'ok');
            Route::get('_test/server-error', fn () => response('ko', 500));
            Route::get('_test/exception', fn () => throw new RuntimeException('boom secret'));
        });
    }

    private function appLogRecords(string $message): array
    {
        return array_values(array_filter(
            $this->appLog->getRecords(),
            fn ($record) => $record['message'] === $message,
        ));
    }

    // ---- /up -------------------------------------------------------------

    public function test_health_check_is_ok_when_everything_works(): void
    {
        SchedulerHeartbeat::beat();
        DB::table('failed_jobs')->insert([
            'uuid' => 'test-uuid-1', 'connection' => 'database', 'queue' => 'default',
            'payload' => '{}', 'exception' => 'x', 'failed_at' => now(),
        ]);

        $this->getJson('/up')
            ->assertOk()
            ->assertHeader('Cache-Control')
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('checks.database.status', 'ok')
            ->assertJsonPath('checks.cache.status', 'ok')
            ->assertJsonPath('checks.queue.status', 'ok')
            ->assertJsonPath('checks.queue.pending', 0)
            ->assertJsonPath('checks.queue.failed', 1)
            ->assertJsonPath('checks.storage.status', 'ok')
            ->assertJsonPath('checks.scheduler.status', 'ok');
    }

    public function test_health_check_is_degraded_when_heartbeat_is_stale(): void
    {
        Cache::forever(SchedulerHeartbeat::CACHE_KEY, now()->subHour()->timestamp);

        $this->getJson('/up')
            ->assertStatus(503)
            ->assertJsonPath('status', 'degraded')
            ->assertJsonPath('checks.scheduler.status', 'fail')
            ->assertJsonPath('checks.database.status', 'ok');
    }

    public function test_health_check_hides_exception_details_and_logs_them(): void
    {
        SchedulerHeartbeat::beat();
        config(['queue.failed.table' => 'missing_failed_jobs_table']);

        $response = $this->getJson('/up')
            ->assertStatus(503)
            ->assertJsonPath('checks.queue', ['status' => 'fail']);

        $this->assertStringNotContainsString('missing_failed_jobs_table', $response->getContent());
        $logged = $this->appLogRecords('health.check.failed');
        $this->assertCount(1, $logged);
        $this->assertSame('queue', $logged[0]['context']['check']);
    }

    public function test_health_check_needs_no_authentication_nor_session(): void
    {
        SchedulerHeartbeat::beat();

        $response = $this->get('/up')->assertOk();

        $this->assertEmpty($response->headers->getCookies());
        $this->assertNotNull($response->headers->get('X-Request-Id'));
    }

    // ---- Requêtes lentes ------------------------------------------------

    public function test_slow_request_is_logged_with_duration_and_status(): void
    {
        config(['monitoring.slow_request_ms' => 0]);

        $this->get('/_test/ok')->assertOk();

        $logged = $this->appLogRecords('http.request.slow');
        $this->assertCount(1, $logged);
        $this->assertSame(200, $logged[0]['context']['status']);
        $this->assertArrayHasKey('duration_ms', $logged[0]['context']);
        // Contexte partagé par AddRequestContext (le middleware tourne après lui).
        $this->assertArrayHasKey('request_id', $logged[0]['context']);
    }

    public function test_fast_request_is_not_logged(): void
    {
        config(['monitoring.slow_request_ms' => 60000]);

        $this->get('/_test/ok')->assertOk();

        $this->assertSame([], $this->appLogRecords('http.request.slow'));
    }

    public function test_slow_query_is_logged_without_bindings(): void
    {
        config(['monitoring.slow_query_ms' => 0]);

        DB::table('users')->where('email', 'secret@example.test')->exists();

        $logged = $this->appLogRecords('db.query.slow');
        $this->assertNotEmpty($logged);
        $context = end($logged)['context'];
        $this->assertStringContainsString('?', $context['sql']);
        $this->assertStringNotContainsString('secret@example.test', json_encode($context));
        $this->assertArrayHasKey('duration_ms', $context);
        $this->assertSame(DB::getDefaultConnection(), $context['connection']);
    }

    public function test_slow_query_logging_is_disabled_with_negative_threshold(): void
    {
        config(['monitoring.slow_query_ms' => -1]);

        DB::table('users')->count();

        $this->assertSame([], $this->appLogRecords('db.query.slow'));
    }

    public function test_lazy_loading_is_logged_instead_of_thrown_outside_production(): void
    {
        ObservabilityServiceProvider::resetLazyLoadingReports();
        foreach (['a', 'b'] as $suffix) {
            User::create(['name' => "Test {$suffix}", 'email' => "lazy-{$suffix}@example.test", 'password' => 'password', 'roles' => ['membre']]);
        }

        foreach (User::all() as $user) {
            $user->vehicleReservations->count();
        }

        $logged = $this->appLogRecords('db.lazy_loading');
        $this->assertCount(1, $logged, 'Une seule ligne par modèle + relation.');
        $this->assertSame(User::class, $logged[0]['context']['model']);
        $this->assertSame('vehicleReservations', $logged[0]['context']['relation']);
        $this->assertStringContainsString('ObservabilityTest.php', (string) $logged[0]['context']['location']);
    }

    // ---- Alertes -----------------------------------------------------------

    public function test_spike_of_5xx_responses_triggers_error_spike_alert(): void
    {
        Mail::fake();
        config(['monitoring.error_spike.threshold' => 3, 'monitoring.error_spike.window_minutes' => 5]);

        $this->get('/_test/server-error')->assertStatus(500);
        $this->get('/_test/server-error')->assertStatus(500);
        Mail::assertNothingSent();

        $this->get('/_test/server-error')->assertStatus(500);

        Mail::assertSent(MonitoringAlert::class, fn (MonitoringAlert $mail) => $mail->rule === 'error_spike');
        Mail::assertSent(MonitoringAlert::class, 1);
    }

    public function test_reported_exception_is_counted_only_once(): void
    {
        Mail::fake();
        config(['monitoring.error_spike.threshold' => 2]);

        // Compté par le handler d'exceptions uniquement : 1 occurrence, sous le seuil de 2.
        $this->get('/_test/exception')->assertStatus(500);

        Mail::assertNothingSent();
    }

    public function test_stale_scheduler_sends_alert_at_most_once_per_interval(): void
    {
        Mail::fake();
        Cache::forever(SchedulerHeartbeat::CACHE_KEY, now()->subHour()->timestamp);

        $this->get('/_test/ok');
        $this->get('/_test/ok');

        Mail::assertSent(MonitoringAlert::class, fn (MonitoringAlert $mail) => $mail->rule === 'scheduler_stale');
        Mail::assertSent(MonitoringAlert::class, 1);
        $this->assertTrue($this->appLog->hasCriticalThatContains('alert.scheduler_stale'));
        $this->assertTrue(Cache::has(LogRequestDuration::SCHEDULER_CHECK_KEY));
    }

    public function test_missing_heartbeat_outside_production_does_not_alert(): void
    {
        Mail::fake();
        Cache::forget(SchedulerHeartbeat::CACHE_KEY);

        $this->get('/_test/ok');

        Mail::assertNothingSent();
    }

    public function test_test_alert_command_sends_one_mail_per_rule(): void
    {
        Mail::fake();

        $this->artisan('monitoring:test-alert')->assertSuccessful();

        Mail::assertSent(MonitoringAlert::class, count(MonitoringTestAlertCommand::RULES));
        foreach (array_keys(MonitoringTestAlertCommand::RULES) as $rule) {
            Mail::assertSent(MonitoringAlert::class, fn (MonitoringAlert $mail) => $mail->rule === $rule && $mail->hasTo('ops@example.test'));
        }

        // Le throttle est ignoré puis libéré : relancer renvoie bien les alertes.
        $this->artisan('monitoring:test-alert error_spike')->assertSuccessful();
        Mail::assertSent(MonitoringAlert::class, count(MonitoringTestAlertCommand::RULES) + 1);
    }

    public function test_test_alert_command_rejects_unknown_rule(): void
    {
        Mail::fake();

        $this->artisan('monitoring:test-alert nope')->assertFailed();

        Mail::assertNothingSent();
    }

    public function test_test_alert_command_fails_without_recipients(): void
    {
        Mail::fake();
        config(['monitoring.alerts.mail_to' => '']);

        $this->artisan('monitoring:test-alert')->assertFailed();

        Mail::assertNothingSent();
    }
}
