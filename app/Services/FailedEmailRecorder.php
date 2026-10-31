<?php

namespace App\Services;

use App\Http\Middleware\AddRequestContext;
use App\Models\FailedEmail;
use App\Support\LogMask;
use Illuminate\Contracts\Mail\Mailable as MailableContract;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;
use WeakMap;

/**
 * Trace les envois d'e-mails échoués (issue #28) : une entrée `failed_emails`
 * consultable dans admin.failed-emails.*, une ligne `error` dans le canal `mail`
 * portant le même failed_email_id, et le compteur d'alerte `mail_failure`.
 *
 * Utilisé par SafeMailSender (envoi direct), par Mail\Concerns\RecordsDeliveryFailure
 * (échec définitif d'un mailable mis en file) et par le renvoi depuis l'administration.
 */
class FailedEmailRecorder
{
    public const SOURCE_SYNC = 'sync';

    public const SOURCE_QUEUE = 'queue';

    /**
     * Exceptions déjà enregistrées => [destinataire => id FailedEmail]. Avec la file `sync`, la même
     * exception remonte d'abord dans failed() du mailable puis dans SafeMailSender :
     * on évite ainsi un doublon.
     */
    private static ?WeakMap $recorded = null;

    /**
     * Enregistre un échec d'envoi et le journalise. Ne lève jamais d'exception.
     *
     * @param  array{type?: ?string, id?: int|string|null}  $related  Modèle lié (classe + id)
     */
    public function record(
        MailableContract $mailable,
        string $recipientEmail,
        ?string $recipientName,
        Throwable $e,
        string $source = self::SOURCE_SYNC,
        array $related = [],
        ?string $requestId = null,
    ): ?FailedEmail {
        $recorded = self::$recorded ??= new WeakMap();

        $already = $recorded[$e] ?? [];

        if (isset($already[$recipientEmail])) {
            return FailedEmail::find($already[$recipientEmail]);
        }

        $requestId ??= self::currentRequestId();
        $failedEmail = null;

        try {
            $failedEmail = FailedEmail::create([
                'mailable_class' => get_class($mailable),
                'recipient_email' => $recipientEmail,
                'recipient_name' => $recipientName,
                'subject' => $this->subject($mailable),
                'error_message' => $e->getMessage(),
                'related_model_type' => $related['type'] ?? null,
                'related_model_id' => $related['id'] ?? null,
                'source' => $source,
                'request_id' => $requestId,
                'attempts' => 1,
                'last_attempt_at' => now(),
                'payload' => $this->payload($mailable),
            ]);

            $recorded[$e] = $already + [$recipientEmail => $failedEmail->id];
        } catch (Throwable $dbException) {
            // La base peut être indisponible : le log ci-dessous reste la seule trace.
            Log::channel('mail')->error('mail.failed_email.store_failed', [
                'mailable' => get_class($mailable),
                'exception_class' => get_class($dbException),
                'error' => $dbException->getMessage(),
            ]);
        }

        Log::channel('mail')->error('mail.send.failed', array_filter([
            'failed_email_id' => $failedEmail?->id,
            'mailable' => get_class($mailable),
            'recipient' => LogMask::email($recipientEmail),
            'attempt' => 1,
            'source' => $source,
            'related_model' => isset($related['type']) ? $related['type'].'#'.($related['id'] ?? '?') : null,
            // En file, le contexte de requête n'est plus partagé : on garde celui d'origine.
            'origin_request_id' => $source === self::SOURCE_QUEUE ? $requestId : null,
            'exception_class' => get_class($e),
            'error' => $e->getMessage(),
        ], fn ($value) => $value !== null));

        $this->countFailure(get_class($mailable), $e);

        return $failedEmail;
    }

    /**
     * Renvoie immédiatement (sans file) un email échoué depuis son payload.
     *
     * @return string 'sent' | 'failed' | 'unavailable'
     */
    public function retry(FailedEmail $failedEmail): string
    {
        $context = [
            'failed_email_id' => $failedEmail->id,
            'mailable' => $failedEmail->mailable_class,
            'recipient' => LogMask::email($failedEmail->recipient_email),
        ];

        if (! $failedEmail->canBeRetried()) {
            Log::channel('mail')->warning('mail.retry.unavailable', $context);

            return 'unavailable';
        }

        $attempt = (int) $failedEmail->attempts + 1;
        $context['attempt'] = $attempt;

        try {
            $mailable = unserialize(Crypt::decryptString($failedEmail->payload));

            // Mailable::send() envoie tout de suite, même pour un mailable ShouldQueue :
            // l'administrateur voit le résultat réel du renvoi.
            $mailable->to($failedEmail->recipient_email, $failedEmail->recipient_name)
                ->send(Mail::getFacadeRoot());
        } catch (Throwable $e) {
            $failedEmail->update([
                'attempts' => $attempt,
                'last_attempt_at' => now(),
                'error_message' => $e->getMessage(),
            ]);

            Log::channel('mail')->error('mail.retry.failed', $context + [
                'exception_class' => get_class($e),
                'error' => $e->getMessage(),
            ]);

            $this->countFailure($failedEmail->mailable_class, $e);

            return 'failed';
        }

        $failedEmail->update([
            'attempts' => $attempt,
            'last_attempt_at' => now(),
            'is_resolved' => true,
            'resolved_at' => now(),
        ]);

        Log::channel('mail')->info('mail.retry.succeeded', $context);

        return 'sent';
    }

    /**
     * request_id courant : contexte de log partagé, sinon en-tête de la requête.
     */
    public static function currentRequestId(): ?string
    {
        $shared = Log::sharedContext()['request_id'] ?? null;

        if ($shared) {
            return (string) $shared;
        }

        if (app()->bound('request')) {
            return request()->headers->get(AddRequestContext::HEADER) ?: null;
        }

        return null;
    }

    /**
     * Compteur d'alerte `mail_failure` (seuils dans config/monitoring.php).
     */
    private function countFailure(string $mailableClass, Throwable $e): void
    {
        app(AlertService::class)->hit(
            'mail_failure',
            'Échecs répétés d\'envoi d\'e-mails.',
            ['mailable' => $mailableClass, 'exception_class' => get_class($e), 'error' => $e->getMessage()],
            (int) config('monitoring.mail_failure.threshold', 3),
            (int) config('monitoring.mail_failure.window_minutes', 15),
        );
    }

    private function subject(MailableContract $mailable): ?string
    {
        try {
            if (method_exists($mailable, 'envelope')) {
                return $mailable->envelope()->subject ?? null;
            }

            return $mailable->subject ?? null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Mailable sérialisé puis chiffré (il peut contenir un mot de passe temporaire),
     * sans ses destinataires : le renvoi utilise ceux de l'entrée FailedEmail.
     */
    private function payload(MailableContract $mailable): ?string
    {
        try {
            $copy = clone $mailable;

            foreach (['to', 'cc', 'bcc'] as $property) {
                if (property_exists($copy, $property)) {
                    $copy->{$property} = [];
                }
            }

            return Crypt::encryptString(serialize($copy));
        } catch (Throwable) {
            // Mailable non sérialisable (closure…) : l'entrée reste consultable, sans renvoi.
            return null;
        }
    }
}
