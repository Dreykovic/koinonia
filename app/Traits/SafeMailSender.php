<?php

namespace App\Traits;

use App\Services\FailedEmailRecorder;
use App\Support\LogMask;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Envoi d'emails qui ne casse jamais la requête. Chaque échec laisse une entrée
 * `failed_emails` (admin.failed-emails.*) et un log `mail.send.failed` (canal `mail`)
 * portant le même failed_email_id. Voir docs/logging.md, section Emails.
 */
trait SafeMailSender
{
    /**
     * Envoyer un email de manière sécurisée sans bloquer la requête.
     *
     * @param  mixed  $to  Destinataire (email string ou objet avec email)
     * @param  \Illuminate\Mail\Mailable  $mailable  Instance du Mailable
     * @param  Model|null  $relatedModel  Modèle lié pour traçabilité
     * @param  bool  $immediate  Envoyer tout de suite (sendNow) au lieu de passer par la file
     * @return bool True si l'envoi (ou la mise en file) a réussi, False sinon
     */
    protected function sendMailSafely($to, $mailable, ?Model $relatedModel = null, bool $immediate = false): bool
    {
        $recipientEmail = is_string($to) ? $to : ($to->email ?? 'unknown');
        $recipientName = is_object($to) ? ($to->name ?? null) : null;
        $requestId = FailedEmailRecorder::currentRequestId();

        // Mémorise le contexte pour un échec ultérieur dans le worker (Mail\Concerns\RecordsDeliveryFailure).
        if (method_exists($mailable, 'withFailureContext')) {
            $mailable->withFailureContext($relatedModel, $requestId);
        }

        $queued = ! $immediate && $mailable instanceof ShouldQueue;

        try {
            // Mailable::send() court-circuite le test ShouldQueue fait par le
            // Mailer : l'email part tout de suite, sans worker de file d'attente.
            if ($immediate) {
                $mailable->to($to)->send(Mail::getFacadeRoot());
            } else {
                Mail::to($to)->send($mailable);
            }
        } catch (\Throwable $e) {
            app(FailedEmailRecorder::class)->record(
                $mailable,
                $recipientEmail,
                $recipientName,
                $e,
                FailedEmailRecorder::SOURCE_SYNC,
                ['type' => $relatedModel ? get_class($relatedModel) : null, 'id' => $relatedModel?->getKey()],
                $requestId,
            );

            return false;
        }

        Log::channel('mail')->info($queued ? 'mail.queued' : 'mail.sent', array_filter([
            'mailable' => get_class($mailable),
            'recipient' => LogMask::email($recipientEmail),
            'related_model' => $relatedModel ? get_class($relatedModel).'#'.$relatedModel->getKey() : null,
        ]));

        return true;
    }

    /**
     * Envoyer un email APRES que la réponse HTTP soit renvoyée au visiteur.
     *
     * Le formulaire n'attend jamais le serveur SMTP : la page de confirmation
     * s'affiche immédiatement, puis l'email part. Les échecs restent tracés
     * dans la table failed_emails, comme pour un envoi classique.
     *
     * @param  mixed  $to  Destinataire (email string ou objet avec email)
     * @param  \Illuminate\Mail\Mailable  $mailable  Instance du Mailable
     * @param  Model|null  $relatedModel  Modèle lié pour traçabilité
     */
    protected function sendMailAfterResponse($to, $mailable, ?Model $relatedModel = null): void
    {
        app()->terminating(function () use ($to, $mailable, $relatedModel) {
            $this->sendMailSafely($to, $mailable, $relatedModel, true);
        });
    }

    /**
     * Envoyer un email à plusieurs destinataires de manière sécurisée.
     *
     * @param  iterable  $recipients  Collection de destinataires
     * @param  callable  $mailableFactory  Fonction qui retourne un Mailable pour chaque destinataire
     * @param  Model|null  $relatedModel  Modèle lié pour traçabilité
     * @return array ['sent' => int, 'failed' => int]
     */
    protected function sendMailSafelyToMany(iterable $recipients, callable $mailableFactory, ?Model $relatedModel = null): array
    {
        $sent = 0;
        $failed = 0;

        foreach ($recipients as $recipient) {
            $mailable = $mailableFactory($recipient);
            if ($this->sendMailSafely($recipient, $mailable, $relatedModel)) {
                $sent++;
            } else {
                $failed++;
            }
        }

        if ($failed > 0) {
            Log::channel('mail')->warning('mail.batch.partial_failure', ['sent' => $sent, 'failed' => $failed]);
        }

        return ['sent' => $sent, 'failed' => $failed];
    }
}
