<?php

/*
|--------------------------------------------------------------------------
| Monitoring et alertes (100 % local)
|--------------------------------------------------------------------------
|
| Aucune dépendance externe : une alerte = une ligne "critical" dans le canal
| `app` + un e-mail aux administrateurs via le SMTP de l'application
| (App\Services\AlertService). Voir docs/logging.md.
|
*/

return [

    'alerts' => [
        // Interrupteur général des e-mails d'alerte (la ligne de log critical est toujours écrite).
        'enabled' => (bool) env('ALERT_ENABLED', true),

        // Destinataires séparés par des virgules. Vide : e-mails des utilisateurs actifs ayant le rôle admin.
        'mail_to' => env('ALERT_MAIL_TO', ''),

        // Délai minimal entre deux e-mails pour une même règle (anti-spam).
        'throttle_minutes' => (int) env('ALERT_THROTTLE_MINUTES', 30),
    ],

    // Seuil (ms) au-delà duquel une requête SQL est journalisée comme lente.
    'slow_query_ms' => (int) env('MONITORING_SLOW_QUERY_MS', 500),

    // Seuil (ms) au-delà duquel une requête HTTP est journalisée comme lente.
    'slow_request_ms' => (int) env('MONITORING_SLOW_REQUEST_MS', 2000),

    // Pic d'erreurs 5xx : alerte si `threshold` exceptions reportées en `window_minutes`.
    'error_spike' => [
        'threshold' => (int) env('ALERT_ERROR_SPIKE_THRESHOLD', 10),
        'window_minutes' => (int) env('ALERT_ERROR_SPIKE_WINDOW', 5),
    ],

    // Échecs d'envoi d'e-mails : alerte si `threshold` échecs en `window_minutes`.
    'mail_failure' => [
        'threshold' => (int) env('ALERT_MAIL_FAILURE_THRESHOLD', 3),
        'window_minutes' => (int) env('ALERT_MAIL_FAILURE_WINDOW', 15),
    ],

    // Échecs de diffusion temps réel (Pusher) : alerte si `threshold` échecs en `window_minutes`.
    'broadcast_failure' => [
        'threshold' => (int) env('ALERT_BROADCAST_FAILURE_THRESHOLD', 5),
        'window_minutes' => (int) env('ALERT_BROADCAST_FAILURE_WINDOW', 10),
    ],

    // Blocages de connexion (5 échecs e-mail+IP) : alerte si `threshold` blocages en `window_minutes`.
    'auth_lockout' => [
        'threshold' => (int) env('ALERT_AUTH_LOCKOUT_THRESHOLD', 10),
        'window_minutes' => (int) env('ALERT_AUTH_LOCKOUT_WINDOW', 15),
    ],

    // Règle `job_failed` : alerte à chaque job en échec (throttlée par règle comme les autres).
    'queue_failure' => [
        'enabled' => (bool) env('ALERT_QUEUE_FAILURE', true),
    ],

    'scheduler' => [
        // Âge maximal (minutes) du dernier battement de schedule:run avant de le considérer inactif.
        // Doit dépasser l'intervalle du cron avec une marge (2 × intervalle + 1) : 5 pour un cron
        // chaque minute, 121 pour un cron horaire. Sinon /up répond 503 et scheduler_stale part entre deux passages.
        'heartbeat_max_age_minutes' => (int) env('MONITORING_HEARTBEAT_MAX_AGE', 5),
    ],

    // Jeton optionnel : GET /up?token=... ajoute le détail (volumes de file, dernier battement).
    // Vide = réponse publique limitée aux statuts.
    'health_token' => env('MONITORING_HEALTH_TOKEN', ''),

];
