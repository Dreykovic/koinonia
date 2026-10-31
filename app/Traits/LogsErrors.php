<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Raccourcis de journalisation structurée (clé d'événement + contexte).
 *
 * Le contexte de requête (request_id, user_id, route…) est déjà partagé par
 * AddRequestContext : ne passer ici que le contexte métier. Voir docs/logging.md.
 */
trait LogsErrors
{
    /**
     * Erreur avec exception : conserve classe, message, fichier:ligne et l'exception
     * elle-même (Monolog en extrait la stack trace).
     */
    protected function logError(string $event, Throwable $e, array $context = [], string $channel = 'app'): void
    {
        Log::channel($channel)->error($event, $context + [
            'exception_class' => get_class($e),
            'error' => $e->getMessage(),
            'location' => $e->getFile().':'.$e->getLine(),
            'exception' => $e,
        ]);
    }

    /**
     * Situation anormale mais gérée (conflit, donnée incohérente, service dégradé).
     */
    protected function logWarning(string $event, array $context = [], string $channel = 'app'): void
    {
        Log::channel($channel)->warning($event, $context);
    }

    /**
     * Événement métier notable (création, validation, envoi…).
     */
    protected function logInfo(string $event, array $context = [], string $channel = 'app'): void
    {
        Log::channel($channel)->info($event, $context);
    }
}
