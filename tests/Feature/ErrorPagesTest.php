<?php

namespace Tests\Feature;

use App\Http\Middleware\AddRequestContext;
use App\Mail\MonitoringAlert;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Monolog\Handler\TestHandler;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['app.debug' => false]);

        Route::middleware('web')->prefix('_errors')->group(function () {
            Route::get('403', fn () => abort(403));
            Route::get('403-custom', fn () => abort(403, 'Réservé aux administrateurs.'));
            Route::get('404', fn () => throw (new ModelNotFoundException())->setModel('App\Models\User', [1]));
            Route::get('419', fn () => throw new TokenMismatchException('CSRF token mismatch.'));
            Route::get('429', fn () => throw new ThrottleRequestsException('Too Many Attempts.', null, ['Retry-After' => 60]));
            Route::get('500', fn () => throw new \RuntimeException('secret-detail-from-sql'));
            Route::get('503', fn () => abort(503));
            Route::get('422', fn () => request()->validate(['name' => 'required']));
        });
    }

    public static function codes(): array
    {
        return [
            '403' => [403, 'Accès refusé'],
            '404' => [404, 'Page introuvable'],
            '419' => [419, 'Session expirée'],
            '429' => [429, 'Trop de requêtes'],
            '500' => [500, 'Erreur serveur'],
            '503' => [503, 'Maintenance en cours'],
        ];
    }

    /** @dataProvider codes */
    public function test_web_error_page_uses_the_design_system_and_shows_the_request_id(int $status, string $heading): void
    {
        $response = $this->get("/_errors/{$status}");

        $response->assertStatus($status)
            ->assertSee($heading)
            ->assertSee('ITCJ Community')
            ->assertSee('Référence de l');

        $requestId = $response->headers->get(AddRequestContext::HEADER);
        $this->assertNotEmpty($requestId);
        $response->assertSee($requestId);
    }

    /** @dataProvider codes */
    public function test_json_error_has_french_message_status_and_request_id(int $status): void
    {
        $response = $this->getJson("/_errors/{$status}");

        $response->assertStatus($status)
            ->assertExactJson([
                'message' => \App\Exceptions\Handler::MESSAGES[$status],
                'status' => $status,
                'request_id' => $response->headers->get(AddRequestContext::HEADER),
            ]);
    }

    public function test_explicit_abort_message_is_kept(): void
    {
        $this->get('/_errors/403-custom')->assertStatus(403)->assertSee('Réservé aux administrateurs.');
        $this->getJson('/_errors/403-custom')->assertJsonPath('message', 'Réservé aux administrateurs.');
    }

    public function test_unknown_route_gets_a_request_id_even_before_the_middleware(): void
    {
        $response = $this->get('/_route-qui-n-existe-pas');

        $response->assertStatus(404)->assertSee('Page introuvable');
        $response->assertSee($response->headers->get(AddRequestContext::HEADER));
    }

    public function test_no_stack_trace_or_internal_message_when_debug_is_off(): void
    {
        $this->get('/_errors/500')
            ->assertStatus(500)
            ->assertDontSee('secret-detail-from-sql')
            ->assertDontSee('RuntimeException')
            ->assertDontSee('ErrorPagesTest.php');

        $this->getJson('/_errors/500')
            ->assertStatus(500)
            ->assertJsonMissingPath('trace')
            ->assertJsonMissingPath('exception')
            ->assertDontSee('secret-detail-from-sql');
    }

    public function test_debug_mode_keeps_laravel_details(): void
    {
        config(['app.debug' => true]);

        $this->getJson('/_errors/500')
            ->assertStatus(500)
            ->assertJsonPath('exception', \RuntimeException::class)
            ->assertJsonStructure(['message', 'status', 'request_id', 'trace']);
    }

    public function test_validation_errors_keep_the_standard_422_json(): void
    {
        $this->getJson('/_errors/422')
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'errors' => ['name']]);
    }

    public function test_unauthenticated_json_uses_the_same_format(): void
    {
        Route::middleware(['web', 'auth'])->get('/_errors/401', fn () => 'ok');

        $this->getJson('/_errors/401')
            ->assertStatus(401)
            ->assertJsonPath('status', 401)
            ->assertJsonPath('message', \App\Exceptions\Handler::MESSAGES[401]);
    }

    public function test_csrf_and_throttling_are_logged_on_security_channel_not_as_errors(): void
    {
        $security = new TestHandler();
        $this->app['log']->channel('security')->getLogger()->pushHandler($security);
        $default = new TestHandler();
        $this->app['log']->channel('single')->getLogger()->pushHandler($default);

        $this->get('/_errors/419');
        $this->get('/_errors/429');

        $this->assertTrue($security->hasNoticeThatContains('security.csrf.mismatch'));
        $this->assertTrue($security->hasNoticeThatContains('security.throttled'));
        $this->assertFalse($default->hasErrorRecords());
    }

    public function test_repeated_server_errors_trigger_the_error_spike_alert(): void
    {
        Mail::fake();
        config([
            'monitoring.alerts.mail_to' => 'ops@example.test',
            'monitoring.error_spike.threshold' => 2,
        ]);

        $this->get('/_errors/500');
        Mail::assertNothingSent();

        $this->get('/_errors/500');
        Mail::assertSent(MonitoringAlert::class, fn (MonitoringAlert $mail) => $mail->rule === 'error_spike');
    }
}
