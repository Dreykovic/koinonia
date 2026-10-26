<?php

namespace App\Traits;

use App\Models\FailedEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

trait SafeMailSender
{
    /**
     * Envoyer un email de manière sécurisée sans bloquer la requête.
     *
     * @param mixed $to Destinataire (email string ou objet avec email)
     * @param \Illuminate\Mail\Mailable $mailable Instance du Mailable
     * @param Model|null $relatedModel Modèle lié pour traçabilité
     * @param bool $immediate Envoyer tout de suite (sendNow) au lieu de passer par la file
     * @return bool True si l'envoi a réussi, False sinon
     */
    protected function sendMailSafely($to, $mailable, ?Model $relatedModel = null, bool $immediate = false): bool
    {
        try {
            $recipientEmail = is_string($to) ? $to : $to->email;
            $recipientName = is_object($to) ? ($to->name ?? null) : null;

            // Mailable::send() court-circuite le test ShouldQueue fait par le
            // Mailer : l'email part tout de suite, sans worker de file d'attente.
            if ($immediate) {
                $mailable->to($to)->send(Mail::getFacadeRoot());
            } else {
                Mail::to($to)->send($mailable);
            }

            return true;
        } catch (\Throwable $e) {
            $recipientEmail = is_string($to) ? $to : ($to->email ?? 'unknown');
            $recipientName = is_object($to) ? ($to->name ?? null) : null;

            // Essayer de récupérer le sujet du mailable
            $subject = null;
            if (method_exists($mailable, 'envelope')) {
                try {
                    $envelope = $mailable->envelope();
                    $subject = $envelope->subject ?? null;
                } catch (\Exception $envelopeException) {
                    // Ignorer l'erreur si envelope() échoue
                }
            }

            // Créer l'entrée dans la table des emails échoués
            FailedEmail::create([
                'mailable_class' => get_class($mailable),
                'recipient_email' => $recipientEmail,
                'recipient_name' => $recipientName,
                'subject' => $subject,
                'error_message' => $e->getMessage(),
                'related_model_type' => $relatedModel ? get_class($relatedModel) : null,
                'related_model_id' => $relatedModel?->id,
            ]);

            // Logger l'erreur
            Log::warning('Email failed to send', [
                'to' => $recipientEmail,
                'mailable' => get_class($mailable),
                'error' => $e->getMessage(),
                'related_model' => $relatedModel ? get_class($relatedModel) . '#' . $relatedModel->id : null,
            ]);

            return false;
        }
    }

    /**
     * Envoyer un email à plusieurs destinataires de manière sécurisée.
     *
     * @param iterable $recipients Collection de destinataires
     * @param callable $mailableFactory Fonction qui retourne un Mailable pour chaque destinataire
     * @param Model|null $relatedModel Modèle lié pour traçabilité
     * @return array ['sent' => int, 'failed' => int]
     */
    /**
     * Envoyer un email APRES que la réponse HTTP soit renvoyée au visiteur.
     *
     * Le formulaire n'attend jamais le serveur SMTP : la page de confirmation
     * s'affiche immédiatement, puis l'email part. Les échecs restent tracés
     * dans la table failed_emails, comme pour un envoi classique.
     *
     * @param mixed $to Destinataire (email string ou objet avec email)
     * @param \Illuminate\Mail\Mailable $mailable Instance du Mailable
     * @param Model|null $relatedModel Modèle lié pour traçabilité
     */
    protected function sendMailAfterResponse($to, $mailable, ?Model $relatedModel = null): void
    {
        app()->terminating(function () use ($to, $mailable, $relatedModel) {
            $this->sendMailSafely($to, $mailable, $relatedModel, true);
        });
    }

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

        return ['sent' => $sent, 'failed' => $failed];
    }
}







