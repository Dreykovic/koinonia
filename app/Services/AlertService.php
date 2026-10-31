<?php

namespace App\Services;

use App\Enums\PlatformRole;
use App\Mail\MonitoringAlert;
use App\Models\User;
use App\Support\LogMask;
use App\Support\SafeLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Alertes 100 % locales : une ligne "critical" dans le canal `app` (clé "alert.<règle>")
 * puis un e-mail immédiat aux administrateurs, limité à un envoi par règle et par
 * période (config monitoring.alerts.throttle_minutes).
 *
 * Usage : app(AlertService::class)->send('job_failed', 'Un job a échoué', [...]);
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
        // Les valeurs textuelles partent par e-mail : e-mails et hash masqués par précaution.
        $context = array_map(fn ($value) => is_string($value) ? LogMask::text($value) : $value, $context);

        try {
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
        } catch (Throwable $e) {
            // Cache ou disque indisponible (quota plein…) : l'appelant ne doit jamais échouer.
            SafeLog::failure('alert.dispatch.failed', $e, ['rule' => $rule]);

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
                'error' => LogMask::text($e->getMessage()),
            ]);

            return false;
        }
    }

    /**
     * Compteur par fenêtre fixe de $windowMinutes : déclenche send() une fois par fenêtre,
     * dès que le nombre d'occurrences atteint $threshold. Utilisé pour les pics (5xx,
     * échecs mail, échecs broadcast). Ne lève jamais d'exception.
     *
     * La clé porte le numéro de fenêtre : avec le cache fichier, increment() peut perdre
     * le TTL d'une clé expirée entre add() et increment() ; une telle clé "éternelle"
     * n'est alors plus jamais relue et ne bloque pas la règle. Le seuil est testé avec >=
     * (une occurrence perdue par un increment non atomique ne supprime pas l'alerte) et
     * le déclenchement est dédoublonné par fenêtre.
     */
    public function hit(string $rule, string $message, array $context, int $threshold, int $windowMinutes): void
    {
        try {
            $window = max(1, $windowMinutes);
            $bucket = intdiv(now()->timestamp, $window * 60);
            $key = self::CACHE_PREFIX."hits:{$rule}:{$bucket}";

            Cache::add($key, 0, now()->addMinutes($window * 2));
            $count = (int) Cache::increment($key);

            if ($count < max(1, $threshold)) {
                return;
            }

            if (! Cache::add(self::CACHE_PREFIX."fired:{$rule}:{$bucket}", $count, now()->addMinutes($window * 2))) {
                return;
            }
        } catch (Throwable $e) {
            SafeLog::failure('alert.dispatch.failed', $e, ['rule' => $rule]);

            return;
        }

        $this->send($rule, $message, $context + ['count' => $count, 'window_minutes' => $windowMinutes]);
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
