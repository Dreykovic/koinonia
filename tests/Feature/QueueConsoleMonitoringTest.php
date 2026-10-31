<?php

namespace Tests\Feature;

use App\Console\Scheduling\ScheduleMonitor;
use App\Mail\MonitoringAlert;
use App\Services\SundayMealService;
use App\Support\SchedulerHeartbeat;
use Illuminate\Bus\Queueable;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Monolog\Handler\TestHandler;
use RuntimeException;
use Tests\TestCase;

class QueueConsoleMonitoringTest extends TestCase
{
    use RefreshDatabase;

    private TestHandler $queueLog;

    private TestHandler $appLog;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'queue.default' => 'sync',
            'monitoring.alerts.enabled' => true,
            'monitoring.alerts.mail_to' => 'ops@example.test',
            'monitoring.queue_failure.enabled' => true,
        ]);

        $this->queueLog = new TestHandler();
        $this->app['log']->channel('queue')->getLogger()->pushHandler($this->queueLog);
        $this->appLog = new TestHandler();
        $this->app['log']->channel('app')->getLogger()->pushHandler($this->appLog);
    }

    private function queueRecord(string $message): ?array
    {
        foreach ($this->queueLog->getRecords() as $record) {
            if ($record['message'] === $message) {
                return $record['context'];
            }
        }

        return null;
    }

    public function test_failed_job_logs_error_and_sends_alert(): void
    {
        Mail::fake();

        try {
            FailingMonitoringTestJob::dispatch();
            $this->fail('Le job aurait dû lever une exception.');
        } catch (RuntimeException $e) {
            $this->assertSame('boom job', $e->getMessage());
        }

        $this->assertTrue($this->queueLog->hasErrorThatContains('queue.job.failed'));
        $context = $this->queueRecord('queue.job.failed');
        $this->assertSame(FailingMonitoringTestJob::class, $context['job']);
        $this->assertSame('sync', $context['connection']);
        $this->assertSame(RuntimeException::class, $context['exception_class']);
        $this->assertArrayHasKey('attempts', $context);
        $this->assertArrayNotHasKey('payload', $context);

        $this->assertTrue($this->appLog->hasCriticalThatContains('alert.job_failed'));
        Mail::assertSent(MonitoringAlert::class, fn (MonitoringAlert $mail) => $mail->rule === 'job_failed'
            && $mail->context['job'] === FailingMonitoringTestJob::class);
    }

    public function test_failed_job_alert_can_be_disabled(): void
    {
        Mail::fake();
        config(['monitoring.queue_failure.enabled' => false]);

        try {
            FailingMonitoringTestJob::dispatch();
        } catch (RuntimeException) {
            // attendu
        }

        $this->assertTrue($this->queueLog->hasErrorThatContains('queue.job.failed'));
        $this->assertFalse($this->appLog->hasCriticalThatContains('alert.job_failed'));
        Mail::assertNothingSent();
    }

    public function test_command_exception_is_logged_and_returns_failure(): void
    {
        $this->mock(SundayMealService::class)
            ->shouldReceive('closeMealsForSaturday')
            ->andThrow(new RuntimeException('base indisponible'));

        $this->artisan('meals:close')->assertExitCode(1);

        $this->assertTrue($this->queueLog->hasInfoThatContains('command.started'));
        $this->assertTrue($this->queueLog->hasErrorThatContains('command.failed'));
        $context = $this->queueRecord('command.failed');
        $this->assertSame('meals:close', $context['command']);
        $this->assertSame('base indisponible', $context['error']);
        $this->assertArrayHasKey('duration_ms', $context);
    }

    public function test_command_success_logs_duration_and_counters(): void
    {
        $this->mock(SundayMealService::class)
            ->shouldReceive('generateUpcomingSundays')->with(2)->andReturn(2);

        $this->artisan('meals:generate 2')->assertExitCode(0);

        $this->assertSame(['weeks' => '2'], $this->queueRecord('command.started')['arguments']);
        $context = $this->queueRecord('command.finished');
        $this->assertSame('meals:generate', $context['command']);
        $this->assertSame(0, $context['exit_code']);
        $this->assertSame(['weeks' => 2, 'generated' => 2], $context['metrics']);
        $this->assertIsInt($context['duration_ms']);
    }

    public function test_failed_jobs_check_reports_pending_jobs(): void
    {
        DB::table('failed_jobs')->insert([
            'uuid' => (string) Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => '{}',
            'exception' => 'RuntimeException',
            'failed_at' => now()->subHour(),
        ]);

        $this->artisan('monitoring:failed-jobs')->assertExitCode(0);

        $this->assertTrue($this->queueLog->hasWarningThatContains('queue.failed_jobs.pending'));
        $this->assertSame(['total' => 1, 'last_24h' => 1], $this->queueRecord('queue.failed_jobs.pending'));
    }

    public function test_failing_scheduled_task_logs_error_with_output_and_alerts(): void
    {
        Mail::fake();

        $event = (new Schedule())->exec("sh -c 'echo sortie-de-test; exit 3'");
        ScheduleMonitor::watch($event);

        try {
            $event->run($this->app);
        } finally {
            if ($event->output && is_file($event->output)) {
                @unlink($event->output);
            }
        }

        $this->assertSame(3, $event->exitCode);
        $this->assertTrue($this->queueLog->hasErrorThatContains('schedule.task.failed'));
        $context = $this->queueRecord('schedule.task.failed');
        $this->assertSame(3, $context['exit_code']);
        $this->assertStringContainsString('sortie-de-test', $context['output']);

        $this->assertTrue($this->appLog->hasCriticalThatContains('alert.scheduled_task_failed'));
        Mail::assertSent(MonitoringAlert::class, fn (MonitoringAlert $mail) => $mail->rule === 'scheduled_task_failed');
    }

    public function test_successful_scheduled_task_does_not_alert(): void
    {
        Mail::fake();

        $event = (new Schedule())->exec('true');
        ScheduleMonitor::watch($event);
        $event->run($this->app);
        if ($event->output && is_file($event->output)) {
            @unlink($event->output);
        }

        $this->assertSame(0, $event->exitCode);
        $this->assertFalse($this->queueLog->hasErrorThatContains('schedule.task.failed'));
        Mail::assertNothingSent();
    }

    public function test_schedule_has_heartbeat_and_failure_hooks_on_every_task(): void
    {
        $this->app->make(ConsoleKernel::class);
        $events = $this->app->make(Schedule::class)->events();

        $heartbeat = collect($events)->first(fn (Event $e) => $e->description === 'monitoring:heartbeat');
        $this->assertNotNull($heartbeat);
        $this->assertSame('* * * * *', $heartbeat->expression);

        foreach ($events as $event) {
            $callbacks = (fn () => $this->afterCallbacks)->call($event);
            $this->assertNotEmpty($callbacks, 'Tâche sans hook onFailure : '.ScheduleMonitor::taskName($event));
        }

        $heartbeat->run($this->app);
        $this->assertNotNull(SchedulerHeartbeat::lastBeat());
        $this->assertFalse(SchedulerHeartbeat::isStale());
    }
}

class FailingMonitoringTestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function handle(): void
    {
        throw new RuntimeException('boom job');
    }
}
