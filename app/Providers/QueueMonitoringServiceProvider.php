<?php

namespace App\Providers;

use App\Services\AlertService;
use App\Support\CurrentJob;
use App\Support\LogMask;
use App\Support\SafeLog;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobTimedOut;
use Illuminate\Queue\Events\WorkerStopping;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use Throwable;

/**
 * Surveillance de la file d'attente (canal `queue`) :
 * - job définitivement en échec (= ligne ajoutée dans failed_jobs) : log error + alerte `job_failed` ;
 * - tentative en échec qui sera retentée, timeout, arrêt anormal du worker : log warning.
 *
 * Le payload des jobs n'est jamais journalisé (il peut contenir des données personnelles).
 */
class QueueMonitoringServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Tentative courante, lue par RecordsDeliveryFailure::failed() (échec définitif d'un mail).
        Queue::before(fn (JobProcessing $event) => CurrentJob::start($event->job->attempts()));
        Queue::after(fn () => CurrentJob::clear());

        // JobFailed est levé après le failed() du job : la tentative n'est plus utile.
        Queue::failing(function (JobFailed $event) {
            CurrentJob::clear();
            $this->jobFailed($event);
        });

        Queue::exceptionOccurred(function (JobExceptionOccurred $event) {
            // Une diffusion Pusher échoue dans le worker (événements ShouldBroadcast) :
            // chaque tentative compte pour l'alerte broadcast_failure. En file `sync`,
            // l'exception remonte à BroadcastsSafely qui la compte déjà.
            if ($event->connectionName !== 'sync' && $this->isBroadcast($event->job)) {
                $this->countBroadcastFailure($event->job, $event->exception);
            }

            // L'échec définitif est déjà tracé par jobFailed().
            if ($event->job->hasFailed()) {
                return;
            }

            Log::channel('queue')->warning('queue.job.exception', $this->jobContext($event->job, $event->connectionName) + [
                'exception_class' => get_class($event->exception),
                'error' => LogMask::exceptionMessage($event->exception),
            ]);
        });

        Event::listen(JobTimedOut::class, function (JobTimedOut $event) {
            Log::channel('queue')->warning('queue.job.timed_out', $this->jobContext($event->job, $event->connectionName));
        });

        Event::listen(WorkerStopping::class, function (WorkerStopping $event) {
            // 0 : file vide (--stop-when-empty) ; 12 : mémoire dépassée ; 1 : timeout / erreur.
            if ((int) $event->status !== 0) {
                Log::channel('queue')->warning('queue.worker.stopped', ['status' => (int) $event->status]);
            }
        });
    }

    private function jobFailed(JobFailed $event): void
    {
        $context = $this->jobContext($event->job, $event->connectionName);
        $e = $event->exception;

        Log::channel('queue')->error('queue.job.failed', $context + LogMask::exceptionContext($e));

        if (! config('monitoring.queue_failure.enabled', true)) {
            return;
        }

        try {
            // Pas de message d'exception dans l'e-mail (SQL, données personnelles) :
            // il reste dans queue-*.log (queue.job.failed, même uuid).
            app(AlertService::class)->send('job_failed', 'Un job de la file d\'attente a échoué définitivement.', $context + [
                'exception_class' => get_class($e),
            ]);
        } catch (Throwable $alertError) {
            // Ne jamais perturber le worker à cause de l'alerte elle-même.
            Log::channel('queue')->error('queue.job.failed.alert_failed', ['error' => $alertError->getMessage()]);
        }
    }

    private function isBroadcast(Job $job): bool
    {
        try {
            return $job->resolveName() === BroadcastEvent::class;
        } catch (Throwable) {
            return false;
        }
    }

    private function countBroadcastFailure(Job $job, Throwable $e): void
    {
        // displayName d'un BroadcastEvent = classe de l'événement diffusé.
        $event = $job->payload()['displayName'] ?? null;

        Log::channel('app')->warning('broadcast.failed', [
            'event' => $event,
            'job_uuid' => $job->uuid(),
            'attempts' => $job->attempts(),
            'exception_class' => get_class($e),
            'error' => LogMask::exceptionMessage($e),
        ]);

        try {
            app(AlertService::class)->hit(
                'broadcast_failure',
                'Échecs répétés de diffusion temps réel (broadcast).',
                ['event' => $event, 'exception_class' => get_class($e)],
                (int) config('monitoring.broadcast_failure.threshold', 5),
                (int) config('monitoring.broadcast_failure.window_minutes', 10),
            );
        } catch (Throwable $alertError) {
            SafeLog::failure('alert.dispatch.failed', $alertError, ['rule' => 'broadcast_failure'], 'queue');
        }
    }

    /**
     * Identification du job sans son payload.
     */
    private function jobContext(Job $job, ?string $connection): array
    {
        return [
            'job' => $job->resolveName(),
            'job_id' => $job->getJobId(),
            'uuid' => $job->uuid(),
            'connection' => $connection,
            'queue' => $job->getQueue(),
            'attempts' => $job->attempts(),
            'max_tries' => $job->maxTries(),
        ];
    }
}
