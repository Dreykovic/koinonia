<?php

namespace App\Traits;

use App\Support\ErrorSpike;
use App\Support\LogMask;
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
     * elle-même (Monolog en extrait la stack trace). Le message est masqué (e-mails,
     * hash) et une QueryException est journalisée sans ses valeurs liées.
     *
     * N'alimente pas l'alerte error_spike : à utiliser quand l'exception est relancée
     * (le handler la compte) ; sinon voir logHandledError().
     */
    protected function logError(string $event, Throwable $e, array $context = [], string $channel = 'app'): void
    {
        Log::channel($channel)->error($event, $context + LogMask::exceptionContext($e));
    }

    /**
     * Erreur interceptée puis transformée en redirection ou en page dégradée : elle n'est
     * ni reportée ni rendue en 5xx, on la compte donc ici pour l'alerte error_spike.
     */
    protected function logHandledError(string $event, Throwable $e, array $context = [], string $channel = 'app'): void
    {
        $this->logError($event, $e, $context, $channel);

        ErrorSpike::count($e);
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
