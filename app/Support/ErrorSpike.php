<?php

namespace App\Support;

use App\Http\Middleware\AddRequestContext;
use App\Services\AlertService;
use Throwable;

/**
 * Compteur de l'alerte `error_spike` (erreurs serveur), partagé par le handler d'exceptions,
 * LogRequestDuration (5xx explicites) et les blocs catch qui transforment une erreur en
 * redirection ou en page dégradée (LogsErrors::logHandledError, GuardsMealMenuActions).
 *
 * L'alerte ne contient que la classe d'exception / le statut et le request_id : jamais le
 * message (il peut contenir du SQL et des données personnelles), qui reste dans les logs locaux.
 * Ne lève jamais d'exception.
 */
class ErrorSpike
{
    public static function count(Throwable|int $failure): void
    {
        try {
            $context = $failure instanceof Throwable
                ? ['last_exception' => get_class($failure)]
                : ['last_status' => $failure];

            if (app()->bound('request') && ($request = request()) && $request->headers->has(AddRequestContext::HEADER)) {
                $context['request_id'] = $request->headers->get(AddRequestContext::HEADER);
            }

            app(AlertService::class)->hit(
                'error_spike',
                'Pic d\'erreurs serveur (5xx).',
                $context,
                (int) config('monitoring.error_spike.threshold', 10),
                (int) config('monitoring.error_spike.window_minutes', 5),
            );
        } catch (Throwable $alertFailure) {
            // L'alerte ne doit jamais empêcher le report de l'erreur d'origine.
            SafeLog::failure('alert.dispatch.failed', $alertFailure, ['rule' => 'error_spike']);
        }
    }
}
