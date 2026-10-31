<?php

namespace App\Http\Middleware;

use App\Services\AlertService;
use App\Support\ErrorSpike;
use App\Support\SafeLog;
use App\Support\SchedulerHeartbeat;
use Closure;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Temps de réponse et santé vus depuis les requêtes HTTP :
 * - requête plus lente que monitoring.slow_request_ms : warning http.request.slow ;
 * - réponse 5xx non comptée par le handler d'exceptions : compteur error_spike ;
 * - après la réponse, au plus une fois toutes les SCHEDULER_CHECK_MINUTES : alerte
 *   scheduler_stale si le cron ne bat plus (pas d'outil de supervision externe).
 *
 * À placer juste après AddRequestContext pour que le contexte (request_id, route…) soit présent.
 */
class LogRequestDuration
{
    public const SCHEDULER_CHECK_MINUTES = 5;

    public const SCHEDULER_CHECK_KEY = 'monitoring:scheduler:last_check';

    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);

        $response = $next($request);

        try {
            $this->logIfSlow($response, (microtime(true) - $start) * 1000);

            // Un 503 de /up décrit l'état d'une dépendance (déjà alertée par sa propre règle),
            // pas une erreur applicative : un moniteur de disponibilité ne doit pas gonfler error_spike.
            if (! $request->routeIs('health')) {
                $this->countServerError($response);
            }
        } catch (Throwable $e) {
            // La mesure ne doit jamais casser la réponse.
            SafeLog::failure('monitoring.request_metrics.failed', $e);
        }

        return $response;
    }

    /**
     * Après l'envoi de la réponse : vérification du battement du scheduler.
     */
    public function terminate(Request $request, Response $response): void
    {
        try {
            $this->checkScheduler();
        } catch (Throwable $e) {
            Log::channel('app')->warning('monitoring.scheduler_check.failed', [
                'exception_class' => get_class($e),
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function logIfSlow(Response $response, float $durationMs): void
    {
        $threshold = (int) config('monitoring.slow_request_ms', 2000);

        if ($threshold < 0 || $durationMs < $threshold) {
            return;
        }

        Log::channel('app')->warning('http.request.slow', [
            'duration_ms' => round($durationMs, 1),
            'threshold_ms' => $threshold,
            'status' => $response->getStatusCode(),
        ]);
    }

    /**
     * Les exceptions reportées sont déjà comptées par Handler::register() ; on ne compte ici
     * que les 5xx renvoyées explicitement (response()->json(..., 500), abort(503)…).
     */
    private function countServerError(Response $response): void
    {
        $status = $response->getStatusCode();

        if ($status < 500) {
            return;
        }

        $exception = $response->exception ?? null;
        if ($exception instanceof Throwable && app(ExceptionHandler::class)->shouldReport($exception)) {
            return;
        }

        ErrorSpike::count($status);
    }

    /**
     * Sans battement du tout (installation neuve, cache vidé), isStale() laisse au cron
     * heartbeat_max_age_minutes pour battre avant de le déclarer arrêté. Hors production,
     * pas d'alerte dans ce cas : le cron n'est simplement pas configuré en local.
     */
    private function checkScheduler(): void
    {
        if (! Cache::add(self::SCHEDULER_CHECK_KEY, now()->timestamp, now()->addMinutes(self::SCHEDULER_CHECK_MINUTES))) {
            return;
        }

        if (! SchedulerHeartbeat::isStale()) {
            return;
        }

        $last = SchedulerHeartbeat::lastBeat();

        if ($last === null && ! app()->isProduction()) {
            return;
        }

        app(AlertService::class)->send('scheduler_stale', 'Le planificateur (schedule:run) ne tourne plus.', [
            'last_beat' => $last?->toIso8601String() ?? 'jamais',
            'max_age_minutes' => (int) config('monitoring.scheduler.heartbeat_max_age_minutes', 5),
        ]);
    }
}
