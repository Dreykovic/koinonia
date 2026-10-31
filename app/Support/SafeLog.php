<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Journalisation de dernier recours, pour les blocs qui ne doivent jamais lever
 * d'exception (handler d'exceptions, middleware de mesure, envoi d'alerte) :
 * écrit dans le canal demandé et, si le canal lui-même échoue, dans le log
 * d'erreurs PHP (error_log). Aucune erreur n'est donc avalée silencieusement.
 */
class SafeLog
{
    /**
     * Échec d'une opération secondaire (alerte, métrique, cache) qui ne doit pas
     * faire échouer l'opération principale.
     */
    public static function failure(string $event, Throwable $e, array $context = [], string $channel = 'app'): void
    {
        $context += [
            'exception_class' => get_class($e),
            'error' => LogMask::exceptionMessage($e),
            'location' => $e->getFile().':'.$e->getLine(),
        ];

        try {
            Log::channel($channel)->warning($event, $context);
        } catch (Throwable $logFailure) {
            error_log(sprintf(
                '%s %s (log channel %s unavailable: %s)',
                $event,
                json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR),
                $channel,
                $logFailure->getMessage(),
            ));
        }
    }
}
