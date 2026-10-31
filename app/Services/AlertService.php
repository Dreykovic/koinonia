<?php

namespace App\Services;

use App\Enums\PlatformRole;
use App\Mail\MonitoringAlert;
use App\Models\User;
use App\Support\LogMask;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Alertes 100 % locales : une ligne "critical" dans le canal `app` (clé "alert.<règle>")
 * puis un e-mail immédiat aux administrateurs, limité à un envoi par règle et par
 * période (config monitoring.alerts.throttle_minutes).
 *
 * Usage : app(AlertService::class)->send('queue_failure', 'Un job a échoué', [...]);
 *         app(AlertService::class)->hit('mail_failure', 'Échecs SMTP répétés', [...], 3, 15);
 */
class AlertService
{
    public const CACHE_PREFIX = 'monitoring:alert:';

    /**
     * Journalise l'alerte puis l'envoie par e-mail, sauf si les alertes sont désactivées,
     * sans destinataire ou si la règle a déjà été envoyée pendant la période de throttle.
     *
     * L'envoi est synchrone (jamais en file : la queue peut être la panne) et ne lève
     * jamais d'exception ; un échec SMTP est seulement journalisé dans le canal `mail`.
     *
     * @return bool true si un e-mail est parti
     */
    public function send(string $rule, string $message, array $context = []): bool
    {
        Log::channel('app')->critical("alert.{$rule}", ['rule' => $rule, 'alert' => $message] + $context);

        if (! config('monitoring.alerts.enabled')) {
            return false;
        }

        $recipients = $this->recipients();

        if ($recipients === []) {
            Log::channel('app')->warning('alert.no_recipients', ['rule' => $rule]);

            return false;
        }

        // Cache::add est atomique : un seul e-mail par règle et par période, même en concurrence.
        $minutes = max(1, (int) config('monitoring.alerts.throttle_minutes', 30));
        if (! Cache::add(self::CACHE_PREFIX."throttle:{$rule}", now()->timestamp, now()->addMinutes($minutes))) {
            return false;
        }

        try {
            // MonitoringAlert n'implémente pas ShouldQueue : send() part immédiatement.
            Mail::to($recipients)->send(new MonitoringAlert($rule, $message, $context));

            return true;
        } catch (Throwable $e) {
            // Pas de FailedEmail ni de nouvelle alerte : on éviterait une boucle si le SMTP est en panne.
            Log::channel('mail')->error('alert.mail.failed', [
                'rule' => $rule,
                'recipients' => array_map([LogMask::class, 'email'], $recipients),
                'exception_class' => get_class($e),
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Compteur sur fenêtre glissante fixe : déclenche send() quand le nombre d'occurrences
     * atteint $threshold dans les $windowMinutes suivant la première occurrence.
     * Utilisé pour les pics (5xx, échecs mail, échecs broadcast).
     */
    public function hit(string $rule, string $message, array $context, int $threshold, int $windowMinutes): void
    {
        $key = self::CACHE_PREFIX."hits:{$rule}";

        Cache::add($key, 0, now()->addMinutes(max(1, $windowMinutes)));
        $count = (int) Cache::increment($key);

        // Égalité stricte : une seule alerte par fenêtre, pas une par occurrence au-delà du seuil.
        if ($count === max(1, $threshold)) {
            $this->send($rule, $message, $context + ['count' => $count, 'window_minutes' => $windowMinutes]);
        }
    }

    /**
     * Destinataires : ALERT_MAIL_TO, sinon les administrateurs actifs.
     *
     * @return array<int, string>
     */
    public function recipients(): array
    {
        $configured = array_values(array_filter(
            array_map('trim', explode(',', (string) config('monitoring.alerts.mail_to'))),
            fn (string $email) => filter_var($email, FILTER_VALIDATE_EMAIL) !== false,
        ));

        if ($configured !== []) {
            return $configured;
        }

        try {
            return User::active()->byRole(PlatformRole::ADMIN)->pluck('email')->filter()->values()->all();
        } catch (Throwable $e) {
            // La base peut être la cause de l'alerte : on ne bloque pas, on trace.
            Log::channel('app')->error('alert.recipients.failed', ['error' => $e->getMessage()]);

            return [];
        }
    }
}
