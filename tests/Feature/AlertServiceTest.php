<?php

namespace Tests\Feature;

use App\Enums\PlatformRole;
use App\Mail\MonitoringAlert;
use App\Models\FailedEmail;
use App\Models\User;
use App\Services\AlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Monolog\Handler\TestHandler;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\RawMessage;
use Tests\TestCase;

class AlertServiceTest extends TestCase
{
    use RefreshDatabase;

    private TestHandler $appLog;

    private TestHandler $mailLog;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'monitoring.alerts.enabled' => true,
            'monitoring.alerts.mail_to' => 'ops@example.test, bad-address',
            'monitoring.alerts.throttle_minutes' => 30,
        ]);

        $this->appLog = new TestHandler();
        $this->app['log']->channel('app')->getLogger()->pushHandler($this->appLog);
        $this->mailLog = new TestHandler();
        $this->app['log']->channel('mail')->getLogger()->pushHandler($this->mailLog);
    }

    private function alerts(): AlertService
    {
        return app(AlertService::class);
    }

    public function test_send_logs_critical_and_mails_configured_recipients(): void
    {
        Mail::fake();

        $this->assertTrue($this->alerts()->send('queue_failure', 'Un job a échoué', ['job' => 'X']));

        $this->assertTrue($this->appLog->hasCriticalThatContains('alert.queue_failure'));
        Mail::assertSent(MonitoringAlert::class, function (MonitoringAlert $mail) {
            return $mail->hasTo('ops@example.test') && ! $mail->hasTo('bad-address')
                && $mail->rule === 'queue_failure' && $mail->context['job'] === 'X';
        });
    }

    public function test_alert_mail_renders(): void
    {
        $html = (new MonitoringAlert('error_spike', 'Pic', ['count' => 10, 'ok' => false, 'e' => new \RuntimeException('boom')]))->render();

        $this->assertStringContainsString('error_spike', $html);
        $this->assertStringContainsString('RuntimeException: boom', $html);
    }

    public function test_send_is_throttled_per_rule(): void
    {
        Mail::fake();

        $this->assertTrue($this->alerts()->send('rule_a', 'premier'));
        $this->assertFalse($this->alerts()->send('rule_a', 'second'));
        $this->assertTrue($this->alerts()->send('rule_b', 'autre règle'));

        Mail::assertSent(MonitoringAlert::class, 2);
        // Chaque occurrence reste tracée même si l'e-mail est throttlé.
        $this->assertCount(3, array_filter($this->appLog->getRecords(), fn ($r) => str_starts_with($r->message, 'alert.rule_')));

        $this->travel(31)->minutes();
        $this->assertTrue($this->alerts()->send('rule_a', 'après la période'));
    }

    public function test_disabled_alerts_only_log(): void
    {
        Mail::fake();
        config(['monitoring.alerts.enabled' => false]);

        $this->assertFalse($this->alerts()->send('rule', 'message'));

        Mail::assertNothingSent();
        $this->assertTrue($this->appLog->hasCriticalThatContains('alert.rule'));
    }

    public function test_falls_back_to_active_admins_when_no_recipient_is_configured(): void
    {
        Mail::fake();
        config(['monitoring.alerts.mail_to' => '']);

        $make = fn (string $email, array $roles, bool $active = true) => User::create([
            'name' => 'U', 'email' => $email, 'password' => 'password', 'roles' => $roles, 'is_active' => $active,
        ]);
        $make('admin@example.test', [PlatformRole::ADMIN]);
        $make('inactive-admin@example.test', [PlatformRole::ADMIN], false);
        $make('member@example.test', [PlatformRole::MEMBRE]);

        $this->assertSame(['admin@example.test'], $this->alerts()->recipients());
        $this->assertTrue($this->alerts()->send('rule', 'message'));
        Mail::assertSent(MonitoringAlert::class, fn ($mail) => $mail->hasTo('admin@example.test') && ! $mail->hasTo('member@example.test'));
    }

    public function test_mail_failure_is_logged_without_recursion_nor_failed_email(): void
    {
        Mail::extend('failing', fn () => new class implements TransportInterface
        {
            public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
            {
                throw new TransportException('SMTP indisponible');
            }

            public function __toString(): string
            {
                return 'failing://';
            }
        });
        config(['mail.default' => 'failing', 'mail.mailers.failing' => ['transport' => 'failing']]);

        $this->assertFalse($this->alerts()->send('error_spike', 'Pic'));

        $this->assertTrue($this->mailLog->hasErrorThatContains('alert.mail.failed'));
        $this->assertSame('o***@example.test', $this->mailLog->getRecords()[0]->context['recipients'][0]);
        $this->assertSame(0, FailedEmail::count());
        // Une seule alerte critique : l'échec SMTP n'en déclenche pas une autre.
        $this->assertCount(1, array_filter($this->appLog->getRecords(), fn ($r) => $r->level->name === 'Critical'));
    }

    public function test_hit_sends_once_when_threshold_is_reached_within_window(): void
    {
        Mail::fake();

        $this->alerts()->hit('mail_failure', 'Échecs SMTP', [], 3, 15);
        $this->alerts()->hit('mail_failure', 'Échecs SMTP', [], 3, 15);
        Mail::assertNothingSent();

        $this->alerts()->hit('mail_failure', 'Échecs SMTP', [], 3, 15);
        Mail::assertSent(MonitoringAlert::class, fn ($mail) => $mail->context['count'] === 3);

        $this->alerts()->hit('mail_failure', 'Échecs SMTP', [], 3, 15);
        Mail::assertSent(MonitoringAlert::class, 1);
    }

    public function test_hit_counter_resets_after_the_window(): void
    {
        Mail::fake();

        $this->alerts()->hit('broadcast_failure', 'x', [], 2, 5);
        $this->travel(6)->minutes();
        $this->alerts()->hit('broadcast_failure', 'x', [], 2, 5);

        Mail::assertNothingSent();
    }
}
