<?php

namespace Tests\Feature;

use App\Enums\PlatformRole;
use App\Http\Middleware\LogRequestDuration;
use App\Mail\AccountApprovedMail;
use App\Mail\MonitoringAlert;
use App\Models\Community;
use App\Models\FailedEmail;
use App\Models\User;
use App\Services\AlertService;
use App\Services\FailedEmailRecorder;
use App\Support\LogMask;
use App\Support\SchedulerHeartbeat;
use App\Traits\LogsErrors;
use App\Traits\SafeMailSender;
use ErrorException;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Mockery;
use Monolog\Handler\TestHandler;
use Monolog\LogRecord;
use PDOException;
use RuntimeException;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\RawMessage;
use Tests\TestCase;

/**
 * Corrections de la revue du monitoring (#18) : report pendant une panne de base,
 * masquage des données dans les logs et les alertes, fausses alertes, /up, compteurs.
 */
class MonitoringHardeningTest extends TestCase
{
    use RefreshDatabase;

    private TestHandler $appLog;

    private TestHandler $mailLog;

    private TestHandler $securityLog;

    private TestHandler $defaultLog;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'monitoring.alerts.enabled' => true,
            'monitoring.alerts.mail_to' => 'ops@example.test',
            'monitoring.alerts.throttle_minutes' => 30,
        ]);

        $this->defaultLog = $this->captureLog('single');
        $this->appLog = $this->captureLog('app');
        $this->mailLog = $this->captureLog('mail');
        $this->securityLog = $this->captureLog('security');

        Route::middleware('web')->group(function () {
            Route::get('_hardening/ok', fn () => 'ok');
            Route::get('_hardening/boom', fn () => throw new RuntimeException('boom original'));
        });
    }

    private function captureLog(string $channel): TestHandler
    {
        $handler = new TestHandler();
        $this->app['log']->channel($channel)->getLogger()->pushHandler($handler);

        return $handler;
    }

    /**
     * @return array<int, LogRecord>
     */
    private function records(TestHandler $handler, string $message): array
    {
        return array_values(array_filter($handler->getRecords(), fn (LogRecord $r) => $r->message === $message));
    }

    private function queryException(): QueryException
    {
        return new QueryException(
            'mysql',
            'insert into users (email, password) values (?, ?)',
            ['jean.dupont@example.test', '$2y$10$abcdefghijklmnopqrstuuABCDEFGHIJKLMNOPQRSTUVWXYZ01234'],
            new PDOException('SQLSTATE[22001]: String data, right truncated: 1406 Data too long for column'),
        );
    }

    private function errorSpikeCount(): int
    {
        $bucket = intdiv(now()->timestamp, 5 * 60);

        return (int) Cache::get(AlertService::CACHE_PREFIX."hits:error_spike:{$bucket}", 0);
    }

    private function failingMailer(string $message): void
    {
        Mail::extend('failing', fn () => new class($message) implements TransportInterface
        {
            public function __construct(private string $message)
            {
            }

            public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
            {
                throw new TransportException($this->message);
            }

            public function __toString(): string
            {
                return 'failing://';
            }
        });

        config(['mail.default' => 'failing', 'mail.mailers.failing' => ['transport' => 'failing']]);
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin', 'email' => 'admin-'.uniqid().'@example.test', 'password' => 'password',
            'roles' => [PlatformRole::ADMIN], 'is_active' => true,
        ]);
    }

    // ---- Report d'exception pendant une panne de base ----------------------

    public function test_exception_is_still_reported_when_the_user_lookup_hits_a_dead_database(): void
    {
        $this->freezeSecond();
        config(['monitoring.error_spike.threshold' => 100]);

        $provider = Mockery::mock(UserProvider::class);
        $provider->shouldReceive('retrieveById')->andThrow($this->queryException());
        Auth::provider('dead-db', fn () => $provider);
        config(['auth.providers.users.driver' => 'dead-db']);
        Auth::forgetGuards();

        $this->withSession([Auth::guard()->getName() => 5])
            ->get('/_hardening/boom')
            ->assertStatus(500);

        $reported = $this->records($this->defaultLog, 'boom original');
        $this->assertCount(1, $reported);
        $this->assertInstanceOf(RuntimeException::class, $reported[0]->context['exception']);
        $this->assertNotEmpty($reported[0]->context['request_id']);
        $this->assertArrayNotHasKey('user_id', $reported[0]->context);
        $this->assertSame(1, $this->errorSpikeCount());
    }

    // ---- Battement du scheduler ------------------------------------------

    public function test_cache_clear_in_production_sends_no_false_scheduler_alert(): void
    {
        Mail::fake();
        $this->freezeSecond();
        $this->app->detectEnvironment(fn () => 'production');

        SchedulerHeartbeat::beat();
        Cache::flush();

        $this->get('/_hardening/ok')->assertOk();
        Mail::assertNothingSent();

        // Le cron ne revient jamais : l'alerte part une fois le délai écoulé.
        Cache::forget(LogRequestDuration::SCHEDULER_CHECK_KEY);
        $this->travel(6)->minutes();
        $this->get('/_hardening/ok')->assertOk();

        Mail::assertSent(MonitoringAlert::class, fn (MonitoringAlert $mail) => $mail->rule === 'scheduler_stale');
    }

    public function test_health_endpoint_503_does_not_feed_error_spike(): void
    {
        Mail::fake();
        config(['monitoring.error_spike.threshold' => 1]);
        Cache::forever(SchedulerHeartbeat::CACHE_KEY, now()->subHour()->timestamp);

        foreach (range(1, 5) as $i) {
            $this->getJson('/up')->assertStatus(503);
        }

        Mail::assertNotSent(MonitoringAlert::class, fn (MonitoringAlert $mail) => $mail->rule === 'error_spike');
        $this->assertSame(0, $this->errorSpikeCount());
    }

    public function test_health_check_failure_is_logged_once_per_interval(): void
    {
        SchedulerHeartbeat::beat();
        config(['queue.failed.table' => 'missing_failed_jobs_table']);

        $this->getJson('/up')->assertStatus(503);
        $this->getJson('/up')->assertStatus(503);

        $this->assertCount(1, $this->records($this->appLog, 'health.check.failed'));
    }

    public function test_health_endpoint_is_throttled(): void
    {
        SchedulerHeartbeat::beat();

        foreach (range(1, 30) as $i) {
            $this->getJson('/up')->assertOk();
        }

        $this->getJson('/up')->assertStatus(429);
    }

    // ---- error_spike pour les erreurs interceptées ---------------------------

    public function test_swallowed_controller_failure_counts_for_error_spike_without_leaking_the_message(): void
    {
        Mail::fake();
        config(['monitoring.error_spike.threshold' => 1]);
        $qe = $this->queryException();
        Community::creating(fn () => throw $qe);

        $this->actingAs($this->admin())
            ->from('/communities')
            ->post(route('communities.store'), ['name' => 'Saint-Paul'])
            ->assertRedirect('/communities')
            ->assertSessionHas('error');

        Mail::assertSent(MonitoringAlert::class, function (MonitoringAlert $mail) {
            $rendered = json_encode($mail->context).$mail->render();

            return $mail->rule === 'error_spike'
                && $mail->context['last_exception'] === QueryException::class
                && ! str_contains($rendered, 'jean.dupont')
                && ! str_contains($rendered, '$2y$10$');
        });
    }

    public function test_alert_service_never_throws_when_the_cache_is_unwritable(): void
    {
        $this->failingMailer('Connexion SMTP refusée');
        $user = User::create(['name' => 'Jean', 'email' => 'jean@example.test', 'password' => 'x', 'roles' => [PlatformRole::MEMBRE]]);

        Cache::shouldReceive('add')->andThrow(new ErrorException('file_put_contents(): disk quota exceeded'));

        $sender = new class
        {
            use SafeMailSender {
                sendMailSafely as public;
            }
        };

        $this->assertFalse($sender->sendMailSafely($user, new AccountApprovedMail($user, 'tmp'), $user, true));

        $this->assertNotEmpty($this->records($this->appLog, 'alert.dispatch.failed'));
    }

    public function test_threshold_overshoot_still_alerts_once_per_window(): void
    {
        Mail::fake();
        $this->freezeSecond();
        $bucket = intdiv(now()->timestamp, 5 * 60);
        // Compteur déjà au-delà du seuil (incrément perdu, clé sans TTL…).
        Cache::put(AlertService::CACHE_PREFIX."hits:error_spike:{$bucket}", 50, 600);

        app(AlertService::class)->hit('error_spike', 'Pic', [], 10, 5);
        app(AlertService::class)->hit('error_spike', 'Pic', [], 10, 5);

        Mail::assertSent(MonitoringAlert::class, 1);
        $this->assertCount(1, $this->records($this->appLog, 'alert.error_spike'));

        // Fenêtre suivante : nouveau compteur.
        $this->travel(5)->minutes();
        app(AlertService::class)->hit('error_spike', 'Pic', [], 10, 5);
        $this->assertSame(1, $this->errorSpikeCount());
    }

    // ---- Données personnelles ------------------------------------------------

    public function test_reset_password_email_cannot_inject_log_lines(): void
    {
        $forged = "a@b\n[2026-10-31 03:00:00] production.CRITICAL: alert.scheduler_stale {\"user_id\":1}";

        $this->get('/reset-password/tok?email='.rawurlencode($forged))->assertOk();

        $record = $this->records($this->securityLog, 'auth.password.reset_form')[0];
        $this->assertNull($record->context['email']);

        $this->assertSame('***', LogMask::email($forged));
        $this->assertSame('j***@example.com', LogMask::email('jean.dupont@example.com'));
    }

    public function test_query_exception_is_logged_without_bound_values(): void
    {
        $qe = $this->queryException();

        $subject = new class
        {
            use LogsErrors {
                logError as public;
            }
        };
        $subject->logError('user.store.failed', $qe);

        // Report direct (handler d'exceptions) : nettoyé par le tap MaskSensitiveData.
        Log::channel('app')->error($qe->getMessage(), ['exception' => $qe]);

        $records = $this->appLog->getRecords();
        $this->assertCount(2, $records);

        $dump = json_encode(array_map(fn (LogRecord $r) => [$r->message, $r->context, (string) $r->context['exception']->getMessage()], $records));
        $this->assertStringNotContainsString('jean.dupont@example.test', $dump);
        $this->assertStringNotContainsString('$2y$10$', $dump);
        $this->assertStringContainsString('insert into users (email, password) values (?, ?)', $dump);
        $this->assertSame('insert into users (email, password) values (?, ?)', $records[0]->context['sql']);
        $this->assertSame(QueryException::class, $records[0]->context['exception_class']);
        // Trace d'origine conservée.
        $this->assertSame($qe->getTrace(), $records[1]->context['exception']->getTrace());
        $this->assertSame($qe->getLine(), $records[1]->context['exception']->getLine());
    }

    public function test_smtp_error_and_mail_alert_mask_the_recipient(): void
    {
        $this->failingMailer('550 5.1.1 <jean.dupont@example.com>: Recipient address rejected');
        config(['monitoring.mail_failure.threshold' => 1]);

        $user = User::create(['name' => 'Jean', 'email' => 'jean.dupont@example.com', 'password' => 'x', 'roles' => [PlatformRole::MEMBRE]]);
        $sender = new class
        {
            use SafeMailSender {
                sendMailSafely as public;
            }
        };

        $sender->sendMailSafely($user, new AccountApprovedMail($user, 'tmp'), $user, true);

        $failed = $this->records($this->mailLog, 'mail.send.failed')[0];
        $this->assertStringContainsString('j***@example.com', $failed->context['error']);
        $alert = $this->records($this->appLog, 'alert.mail_failure')[0];
        $this->assertStringNotContainsString('jean.dupont', json_encode([$failed->context, $alert->context]));
    }

    // ---- File d'attente --------------------------------------------------------

    private function job(string $name, int $attempts): Job
    {
        $job = Mockery::mock(Job::class);
        $job->shouldReceive('resolveName')->andReturn($name);
        $job->shouldReceive('payload')->andReturn(['displayName' => 'App\\Events\\ReservationCreated']);
        $job->shouldReceive('uuid')->andReturn('uuid-1');
        $job->shouldReceive('attempts')->andReturn($attempts);
        $job->shouldReceive('hasFailed')->andReturn(false);
        $job->shouldReceive('getJobId')->andReturn('1');
        $job->shouldReceive('getQueue')->andReturn('default');
        $job->shouldReceive('maxTries')->andReturn(3);

        return $job;
    }

    public function test_queued_broadcast_failure_counts_for_broadcast_failure_alert(): void
    {
        Mail::fake();
        config(['monitoring.broadcast_failure.threshold' => 1]);

        event(new JobExceptionOccurred('database', $this->job(BroadcastEvent::class, 1), new RuntimeException('Pusher 401')));

        $logged = $this->records($this->appLog, 'broadcast.failed')[0];
        $this->assertSame('App\\Events\\ReservationCreated', $logged->context['event']);
        Mail::assertSent(MonitoringAlert::class, fn (MonitoringAlert $mail) => $mail->rule === 'broadcast_failure');
    }

    public function test_queued_mail_failure_records_the_real_attempt_count(): void
    {
        $this->failingMailer('Timeout SMTP');
        $user = User::create(['name' => 'Jean', 'email' => 'jean@example.test', 'password' => 'x', 'roles' => [PlatformRole::MEMBRE]]);
        $mail = (new AccountApprovedMail($user, 'tmp'))->to('jean@example.test');

        // Le worker en est à sa 3e et dernière tentative (--tries=3).
        event(new JobProcessing('database', $this->job(\Illuminate\Mail\SendQueuedMailable::class, 3)));
        $mail->failed(new TransportException('Timeout SMTP'));

        $failed = FailedEmail::sole();
        $this->assertSame(3, (int) $failed->attempts);
        $this->assertSame(3, $this->records($this->mailLog, 'mail.send.failed')[0]->context['attempt']);

        $this->assertSame('failed', app(FailedEmailRecorder::class)->retry($failed));
        $this->assertSame(4, (int) $failed->fresh()->attempts);
    }

    // ---- Canal security --------------------------------------------------------

    public function test_role_access_and_guest_pages_are_logged_at_info(): void
    {
        $this->get(route('login'))->assertOk();
        $this->actingAs($this->admin())->get(route('users.index'))->assertOk();

        $this->assertTrue($this->securityLog->hasInfoThatContains('auth.login.form'));
        $this->assertTrue($this->securityLog->hasInfoThatContains('auth.access.granted'));
    }

    // ---- Temps réel ---------------------------------------------------------------

    public function test_layout_exposes_the_pusher_key_at_runtime(): void
    {
        config([
            'broadcasting.default' => 'pusher',
            'broadcasting.connections.pusher.key' => 'cle-publique-123',
            'broadcasting.connections.pusher.options.cluster' => 'eu',
        ]);

        $this->actingAs($this->admin())->get(route('dashboard'))
            ->assertOk()
            ->assertSee('<meta name="pusher-key" content="cle-publique-123">', false)
            ->assertSee('<meta name="pusher-cluster" content="eu">', false);

        config(['broadcasting.default' => 'log']);
        $this->get(route('dashboard'))->assertDontSee('name="pusher-key"', false);
    }
}
