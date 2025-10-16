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
     * @return bool True si l'envoi a réussi, False sinon
     */
    protected function sendMailSafely($to, $mailable, ?Model $relatedModel = null): bool
    {
        try {
            $recipientEmail = is_string($to) ? $to : $to->email;
            $recipientName = is_object($to) ? ($to->name ?? null) : null;

            Mail::to($to)->send($mailable);

            return true;
        } catch (\Exception $e) {
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



