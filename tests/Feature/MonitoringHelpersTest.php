<?php

namespace Tests\Feature;

use App\Mail\MonitoringAlert;
use App\Support\LogMask;
use App\Support\SchedulerHeartbeat;
use App\Traits\BroadcastsSafely;
use App\Traits\LogsErrors;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Mail;
use Monolog\Handler\TestHandler;
use Tests\TestCase;

class MonitoringHelpersTest extends TestCase
{
    private TestHandler $appLog;

    protected function setUp(): void
    {
        parent::setUp();

        $this->appLog = new TestHandler();
        $this->app['log']->channel('app')->getLogger()->pushHandler($this->appLog);
    }

    private function subject(): object
    {
        return new class
        {
            use BroadcastsSafely, LogsErrors {
                logError as public;
                logWarning as public;
                logInfo as public;
                broadcastSafely as public;
            }
        };
    }

    private function event(): object
    {
        return new class implements ShouldBroadcastNow
        {
            public function broadcastOn(): array
            {
                return ['planning'];
            }
        };
    }

    public function test_log_error_keeps_exception_details(): void
    {
        $e = new \RuntimeException('boom');

        $this->subject()->logError('reservation.store.failed', $e, ['vehicle_id' => 4]);

        $record = $this->appLog->getRecords()[0];
        $this->assertSame('reservation.store.failed', $record->message);
        $this->assertSame('Error', $record->level->name);
        $this->assertSame(4, $record->context['vehicle_id']);
        $this->assertSame(\RuntimeException::class, $record->context['exception_class']);
        $this->assertSame('boom', $record->context['error']);
        $this->assertStringContainsString('MonitoringHelpersTest.php:', $record->context['location']);
        $this->assertSame($e, $record->context['exception']);
    }

    public function test_log_warning_and_info_use_the_given_channel(): void
    {
        $security = new TestHandler();
        $this->app['log']->channel('security')->getLogger()->pushHandler($security);

        $this->subject()->logWarning('reservation.conflict', ['vehicle_id' => 1]);
        $this->subject()->logInfo('auth.login.succeeded', [], 'security');

        $this->assertTrue($this->appLog->hasWarning(['message' => 'reservation.conflict', 'context' => ['vehicle_id' => 1]]));
        $this->assertTrue($security->hasInfoThatContains('auth.login.succeeded'));
    }

    public function test_broadcast_safely_succeeds_with_a_working_driver(): void
    {
        config(['broadcasting.default' => 'null']);

        $this->assertTrue($this->subject()->broadcastSafely($this->event(), [], true));
        $this->assertFalse($this->appLog->hasWarningRecords());
    }

    public function test_broadcast_failure_is_logged_counted_and_never_thrown(): void
    {
        Mail::fake();
        Broadcast::extend('failing', fn () => new class extends Broadcaster
        {
            public function auth($request)
            {
            }

            public function validAuthenticationResponse($request, $result)
            {
            }

            public function broadcast(array $channels, $event, array $payload = [])
            {
                throw new BroadcastException('Pusher injoignable');
            }
        });
        config([
            'broadcasting.default' => 'failing',
            'broadcasting.connections.failing' => ['driver' => 'failing'],
            'monitoring.alerts.mail_to' => 'ops@example.test',
            'monitoring.broadcast_failure.threshold' => 2,
        ]);

        $event = $this->event();
        $this->assertFalse($this->subject()->broadcastSafely($event, ['reservation_id' => 9]));

        $record = collect($this->appLog->getRecords())->firstWhere('message', 'broadcast.failed');
        $this->assertSame('Warning', $record->level->name);
        $this->assertSame(get_class($event), $record->context['event']);
        $this->assertSame(9, $record->context['reservation_id']);
        Mail::assertNothingSent();

        $this->subject()->broadcastSafely($event);
        Mail::assertSent(MonitoringAlert::class, fn ($mail) => $mail->rule === 'broadcast_failure');
    }

    public function test_scheduler_heartbeat(): void
    {
        config(['monitoring.scheduler.heartbeat_max_age_minutes' => 5]);

        $this->freezeSecond();

        // Sans battement (cache vidé) : délai de grâce de max_age à partir du premier constat.
        $this->assertNull(SchedulerHeartbeat::lastBeat());
        $this->assertFalse(SchedulerHeartbeat::isStale());
        $this->travel(6)->minutes();
        $this->assertTrue(SchedulerHeartbeat::isStale());

        SchedulerHeartbeat::beat();
        $this->assertTrue(SchedulerHeartbeat::lastBeat()->equalTo(now()));
        $this->assertFalse(SchedulerHeartbeat::isStale());

        $this->travel(4)->minutes();
        $this->assertFalse(SchedulerHeartbeat::isStale());

        $this->travel(2)->minutes();
        $this->assertTrue(SchedulerHeartbeat::isStale());
    }

    public function test_log_mask_email(): void
    {
        $this->assertSame('j***@example.com', LogMask::email('jean.dupont@example.com'));
        $this->assertSame('***', LogMask::email('pas-une-adresse'));
        $this->assertNull(LogMask::email(null));
        $this->assertSame('', LogMask::email(''));
    }
}
