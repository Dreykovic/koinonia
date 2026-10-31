<?php

namespace App\Providers;

use App\Services\AlertService;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
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
        Queue::failing(fn (JobFailed $event) => $this->jobFailed($event));

        Queue::exceptionOccurred(function (JobExceptionOccurred $event) {
            // L'échec définitif est déjà tracé par jobFailed().
            if ($event->job->hasFailed()) {
                return;
            }

            Log::channel('queue')->warning('queue.job.exception', $this->jobContext($event->job, $event->connectionName) + [
                'exception_class' => get_class($event->exception),
                'error' => $event->exception->getMessage(),
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

        Log::channel('queue')->error('queue.job.failed', $context + [
            'exception_class' => get_class($e),
            'error' => $e->getMessage(),
            'location' => $e->getFile().':'.$e->getLine(),
            'exception' => $e,
        ]);

        if (! config('monitoring.queue_failure.enabled', true)) {
            return;
        }

        try {
            app(AlertService::class)->send('job_failed', 'Un job de la file d\'attente a échoué définitivement.', $context + [
                'exception_class' => get_class($e),
                'error' => $e->getMessage(),
            ]);
        } catch (Throwable $alertError) {
            // Ne jamais perturber le worker à cause de l'alerte elle-même.
            Log::channel('queue')->error('queue.job.failed.alert_failed', ['error' => $alertError->getMessage()]);
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
