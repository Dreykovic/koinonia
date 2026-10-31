<?php

namespace App\Console\Commands;

use App\Services\AlertService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Envoie une alerte de test par règle (ou pour une seule règle) en ignorant le throttle,
 * pour vérifier de bout en bout la chaîne log critical + e-mail aux administrateurs.
 */
class MonitoringTestAlertCommand extends Command
{
    /**
     * Règles d'alerte connues (voir docs/logging.md).
     */
    public const RULES = [
        'error_spike' => 'Pic d\'erreurs serveur (5xx)',
        'job_failed' => 'Job de la file d\'attente en échec',
        'scheduled_task_failed' => 'Tâche planifiée en échec',
        'scheduler_stale' => 'Planificateur (schedule:run) inactif',
        'mail_failure' => 'Échecs répétés d\'envoi d\'e-mails',
        'broadcast_failure' => 'Échecs répétés de diffusion temps réel',
        'auth_lockout' => 'Blocages de connexion répétés',
        'menu_rotation_failure' => 'Échec de la rotation des menus',
        'meal_generation_failure' => 'Échec de la génération des repas du dimanche',
    ];

    protected $signature = 'monitoring:test-alert {rule? : Règle à tester (toutes si absent)}';

    protected $description = 'Envoie une alerte de test pour chaque règle de monitoring (ignore le throttle)';

    public function handle(AlertService $alerts): int
    {
        $rule = $this->argument('rule');

        if ($rule !== null && ! array_key_exists($rule, self::RULES)) {
            $this->error("Règle inconnue : {$rule}. Règles disponibles : ".implode(', ', array_keys(self::RULES)));

            return self::INVALID;
        }

        if (! config('monitoring.alerts.enabled')) {
            $this->error('Les alertes e-mail sont désactivées (ALERT_ENABLED=false).');

            return self::FAILURE;
        }

        $recipients = $alerts->recipients();
        if ($recipients === []) {
            $this->error('Aucun destinataire : définir ALERT_MAIL_TO ou un administrateur actif.');

            return self::FAILURE;
        }

        $rules = $rule !== null ? [$rule => self::RULES[$rule]] : self::RULES;
        $rows = [];
        $failures = 0;

        foreach ($rules as $name => $label) {
            $throttleKey = AlertService::CACHE_PREFIX."throttle:{$name}";

            // Ignore le throttle, puis le libère : un test ne doit pas masquer une vraie alerte.
            Cache::forget($throttleKey);
            $sent = $alerts->send($name, "[TEST] {$label}", [
                'test' => true,
                'triggered_by' => 'monitoring:test-alert',
            ]);
            Cache::forget($throttleKey);

            $failures += $sent ? 0 : 1;
            $rows[] = [$name, $sent ? 'envoyée' : 'échec (voir storage/logs/mail-*.log)'];
        }

        $this->table(['Règle', 'Résultat'], $rows);
        $this->info('Destinataires : '.count($recipients));

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }
}
