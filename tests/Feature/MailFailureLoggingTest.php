<?php

namespace Tests\Feature;

use App\Enums\PlatformRole;
use App\Mail\AccountApprovedMail;
use App\Mail\AccountRequestRejectedMail;
use App\Mail\CommunityRequestStatusChangedMail;
use App\Mail\NewAccountRequestMail;
use App\Mail\NewCommunityRequestMail;
use App\Models\AccountRequest;
use App\Models\CommunityRequest;
use App\Models\FailedEmail;
use App\Models\Notification;
use App\Models\User;
use App\Services\FailedEmailRecorder;
use App\Services\NotificationService;
use App\Traits\SafeMailSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Monolog\Handler\TestHandler;
use Monolog\LogRecord;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\RawMessage;
use Tests\TestCase;

class MailFailureLoggingTest extends TestCase
{
    use RefreshDatabase;

    private TestHandler $mailLog;

    private TestHandler $appLog;

    private TestHandler $auditLog;

    protected function setUp(): void
    {
        parent::setUp();

        // Transport SMTP en échec, enregistré comme mailer par défaut.
        Mail::extend('failing', fn () => new class implements TransportInterface
        {
            public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
            {
                throw new TransportException('Connexion SMTP refusée');
            }

            public function __toString(): string
            {
                return 'failing://';
            }
        });

        config([
            'mail.default' => 'failing',
            'mail.mailers.failing' => ['transport' => 'failing'],
            'monitoring.alerts.enabled' => true,
            'monitoring.alerts.mail_to' => 'ops@example.test',
            'monitoring.mail_failure.threshold' => 3,
            'monitoring.mail_failure.window_minutes' => 15,
        ]);

        $this->mailLog = $this->spyChannel('mail');
        $this->appLog = $this->spyChannel('app');
        $this->auditLog = $this->spyChannel('audit');
    }

    private function spyChannel(string $channel): TestHandler
    {
        $handler = new TestHandler();
        $this->app['log']->channel($channel)->getLogger()->pushHandler($handler);

        return $handler;
    }

    private function sender(): object
    {
        return new class
        {
            use SafeMailSender {
                sendMailSafely as public;
            }
        };
    }

    private function record(TestHandler $handler, string $message): ?LogRecord
    {
        foreach ($handler->getRecords() as $record) {
            if ($record->message === $message) {
                return $record;
            }
        }

        return null;
    }

    private function user(string $email = 'jean.dupont@example.com', array $roles = [PlatformRole::MEMBRE]): User
    {
        return User::create([
            'name' => 'Jean Dupont', 'email' => $email, 'password' => 'password', 'roles' => $roles, 'is_active' => true,
        ]);
    }

    private function accountRequest(): AccountRequest
    {
        return AccountRequest::forceCreate([
            'name' => 'Marie Martin', 'email' => 'marie.martin@example.com', 'status' => 'rejected', 'admin_notes' => 'Refus',
        ]);
    }

    private function communityRequest(): CommunityRequest
    {
        return CommunityRequest::forceCreate([
            'email' => 'paul@example.com', 'request_type' => 'achat', 'purchase_description' => 'Ampoules', 'status' => 'pending',
        ]);
    }

    public function test_immediate_failure_leaves_failed_email_and_correlated_error_log(): void
    {
        $request = $this->accountRequest();

        $sent = $this->sender()->sendMailSafely($request->email, new AccountRequestRejectedMail($request), $request, true);

        $this->assertFalse($sent);
        $failed = FailedEmail::sole();
        $this->assertSame(AccountRequestRejectedMail::class, $failed->mailable_class);
        $this->assertSame('marie.martin@example.com', $failed->recipient_email);
        $this->assertSame(AccountRequest::class, $failed->related_model_type);
        $this->assertSame($request->id, $failed->related_model_id);
        $this->assertSame(FailedEmailRecorder::SOURCE_SYNC, $failed->source);
        $this->assertSame(1, $failed->attempts);
        $this->assertStringContainsString('Connexion SMTP refusée', $failed->error_message);
        $this->assertTrue($failed->canBeRetried());

        $log = $this->record($this->mailLog, 'mail.send.failed');
        $this->assertNotNull($log);
        $this->assertSame('Error', $log->level->name);
        $this->assertSame($failed->id, $log->context['failed_email_id']);
        $this->assertSame('m***@example.com', $log->context['recipient']);
        $this->assertSame(AccountRequestRejectedMail::class, $log->context['mailable']);
        $this->assertSame(1, $log->context['attempt']);
        $this->assertSame(AccountRequest::class.'#'.$request->id, $log->context['related_model']);
        $this->assertSame(TransportException::class, $log->context['exception_class']);
        // Adresse jamais en clair dans le log.
        $this->assertStringNotContainsString('marie.martin', json_encode($log->context));
    }

    public function test_queued_mail_failure_is_recorded_once_with_origin_context(): void
    {
        // QUEUE_CONNECTION=sync en test : failed() du mailable puis l'exception remonte au trait.
        $user = $this->user();

        $sent = $this->sender()->sendMailSafely($user, new AccountApprovedMail($user, 'Temp-Secret-123'), $user);

        $this->assertFalse($sent);
        $failed = FailedEmail::sole();
        $this->assertSame(FailedEmailRecorder::SOURCE_QUEUE, $failed->source);
        $this->assertSame(User::class, $failed->related_model_type);
        $this->assertSame($user->id, $failed->related_model_id);
        $this->assertCount(1, array_filter($this->mailLog->getRecords(), fn ($r) => $r->message === 'mail.send.failed'));

        // Le mot de passe temporaire n'apparaît ni dans les logs ni en clair dans la base.
        $this->assertStringNotContainsString('Temp-Secret-123', json_encode(array_map(fn ($r) => $r->context, $this->mailLog->getRecords())));
        $this->assertStringNotContainsString('Temp-Secret-123', $failed->getRawOriginal('payload'));
    }

    public function test_worker_failure_callback_records_failed_email(): void
    {
        $request = $this->communityRequest();
        $mail = (new CommunityRequestStatusChangedMail($request, 'pending'))->withFailureContext($request, 'req-abc-12345');
        $mail->to('paul@example.com', 'Paul');

        $mail->failed(new TransportException('Timeout SMTP'));

        $failed = FailedEmail::sole();
        $this->assertSame('paul@example.com', $failed->recipient_email);
        $this->assertSame('Paul', $failed->recipient_name);
        $this->assertSame('req-abc-12345', $failed->request_id);
        $this->assertSame(CommunityRequest::class, $failed->related_model_type);

        $log = $this->record($this->mailLog, 'mail.send.failed');
        $this->assertSame('req-abc-12345', $log->context['origin_request_id']);
        $this->assertSame(FailedEmailRecorder::SOURCE_QUEUE, $log->context['source']);
    }

    /**
     * @dataProvider mailables
     */
    public function test_every_mailable_failure_is_traced(string $class): void
    {
        $admin = $this->user('admin@example.com', [PlatformRole::ADMIN]);
        $mailable = match ($class) {
            AccountApprovedMail::class => new AccountApprovedMail($admin, 'tmp'),
            AccountRequestRejectedMail::class => new AccountRequestRejectedMail($this->accountRequest()),
            NewAccountRequestMail::class => new NewAccountRequestMail($this->accountRequest()),
            CommunityRequestStatusChangedMail::class => new CommunityRequestStatusChangedMail($this->communityRequest(), 'pending'),
            NewCommunityRequestMail::class => new NewCommunityRequestMail($this->communityRequest()),
        };

        $this->assertFalse($this->sender()->sendMailSafely($admin, $mailable, null, true));

        $this->assertSame($class, FailedEmail::sole()->mailable_class);
        $this->assertNotNull($this->record($this->mailLog, 'mail.send.failed'));
    }

    public static function mailables(): array
    {
        return [
            'account approved' => [AccountApprovedMail::class],
            'account rejected' => [AccountRequestRejectedMail::class],
            'new account request' => [NewAccountRequestMail::class],
            'community request status' => [CommunityRequestStatusChangedMail::class],
            'new community request' => [NewCommunityRequestMail::class],
        ];
    }

    public function test_repeated_failures_raise_mail_failure_alert(): void
    {
        $request = $this->accountRequest();

        for ($i = 0; $i < 3; $i++) {
            $this->sender()->sendMailSafely($request->email, new AccountRequestRejectedMail($request), $request, true);
        }

        $this->assertSame(3, FailedEmail::count());
        $this->assertTrue($this->appLog->hasCriticalThatContains('alert.mail_failure'));
        // Le SMTP étant en panne, l'alerte elle-même échoue sans créer de FailedEmail supplémentaire.
        $this->assertTrue($this->mailLog->hasErrorThatContains('alert.mail.failed'));
    }

    public function test_successful_send_is_logged_with_masked_recipient(): void
    {
        config(['mail.default' => 'array']);
        $request = $this->accountRequest();

        $this->assertTrue($this->sender()->sendMailSafely($request->email, new AccountRequestRejectedMail($request), $request, true));

        $this->assertSame(0, FailedEmail::count());
        $this->assertSame('m***@example.com', $this->record($this->mailLog, 'mail.sent')->context['recipient']);
    }

    public function test_admin_retry_success_resolves_and_logs(): void
    {
        $request = $this->accountRequest();
        $this->sender()->sendMailSafely($request->email, new AccountRequestRejectedMail($request), $request, true);
        $failed = FailedEmail::sole();

        config(['mail.default' => 'array']);
        $this->actingAs($this->user('admin@example.com', [PlatformRole::ADMIN]))
            ->post(route('admin.failed-emails.retry', $failed))
            ->assertRedirect()
            ->assertSessionHas('success');

        $failed->refresh();
        $this->assertTrue($failed->is_resolved);
        $this->assertSame(2, $failed->attempts);
        $this->assertCount(1, Mail::mailer('array')->getSymfonyTransport()->messages());

        $log = $this->record($this->mailLog, 'mail.retry.succeeded');
        $this->assertSame($failed->id, $log->context['failed_email_id']);
        $this->assertSame(2, $log->context['attempt']);
        $this->assertNotNull($this->record($this->auditLog, 'admin.failed_email.retried'));
    }

    public function test_admin_retry_failure_is_logged_and_kept_unresolved(): void
    {
        $request = $this->accountRequest();
        $this->sender()->sendMailSafely($request->email, new AccountRequestRejectedMail($request), $request, true);
        $failed = FailedEmail::sole();

        $this->actingAs($this->user('admin@example.com', [PlatformRole::ADMIN]))
            ->post(route('admin.failed-emails.retry', $failed))
            ->assertSessionHas('error');

        $failed->refresh();
        $this->assertFalse($failed->is_resolved);
        $this->assertSame(2, $failed->attempts);
        $log = $this->record($this->mailLog, 'mail.retry.failed');
        $this->assertSame('Error', $log->level->name);
        $this->assertSame($failed->id, $log->context['failed_email_id']);
    }

    public function test_retry_without_payload_is_reported_as_unavailable(): void
    {
        $failed = FailedEmail::create([
            'mailable_class' => AccountRequestRejectedMail::class,
            'recipient_email' => 'old@example.com',
            'error_message' => 'ancien échec',
        ]);

        $this->actingAs($this->user('admin@example.com', [PlatformRole::ADMIN]))
            ->post(route('admin.failed-emails.retry', $failed))
            ->assertSessionHas('error');

        $this->assertNotNull($this->record($this->mailLog, 'mail.retry.unavailable'));
    }

    public function test_retry_route_is_admin_only(): void
    {
        $failed = FailedEmail::create([
            'mailable_class' => Mailable::class, 'recipient_email' => 'x@example.com', 'error_message' => 'x',
        ]);

        $this->actingAs($this->user())
            ->post(route('admin.failed-emails.retry', $failed))
            ->assertForbidden();
    }

    public function test_failed_emails_page_lists_entries(): void
    {
        $request = $this->accountRequest();
        $this->sender()->sendMailSafely($request->email, new AccountRequestRejectedMail($request), $request, true);

        $this->actingAs($this->user('admin@example.com', [PlatformRole::ADMIN]))
            ->get(route('admin.failed-emails.index'))
            ->assertOk()
            ->assertSee('marie.martin@example.com')
            ->assertSee(route('admin.failed-emails.retry', FailedEmail::sole()));
    }

    public function test_notification_cleanup_is_logged(): void
    {
        $queueLog = $this->spyChannel('queue');
        $user = $this->user();
        $old = Notification::create(['user_id' => $user->id, 'type' => 'info', 'title' => 'T', 'message' => 'M']);
        $old->forceFill(['created_at' => now()->subDays(40)])->save();
        Notification::create(['user_id' => $user->id, 'type' => 'info', 'title' => 'T', 'message' => 'M']);

        $this->assertSame(1, app(NotificationService::class)->cleanOldNotifications(30));

        $log = $this->record($queueLog, 'notifications.cleaned');
        $this->assertSame(1, $log->context['deleted']);
        $this->assertSame(30, $log->context['older_than_days']);
    }
}
