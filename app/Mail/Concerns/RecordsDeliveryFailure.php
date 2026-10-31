<?php

namespace App\Mail\Concerns;

use App\Services\FailedEmailRecorder;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * Pour les mailables mis en file (ShouldQueue) : l'échec SMTP survient dans le worker,
 * hors de SafeMailSender. Laravel appelle alors failed() après la dernière tentative ;
 * on y crée l'entrée FailedEmail et le log `mail.send.failed`, comme pour un envoi direct.
 *
 * Le modèle lié et le request_id d'origine sont mémorisés avant la mise en file
 * (withFailureContext, appelé par SafeMailSender) pour garder la corrélation.
 */
trait RecordsDeliveryFailure
{
    public ?string $failureRelatedType = null;

    public int|string|null $failureRelatedId = null;

    public ?string $failureRequestId = null;

    public function withFailureContext(?Model $relatedModel, ?string $requestId): static
    {
        $this->failureRelatedType = $relatedModel ? get_class($relatedModel) : null;
        $this->failureRelatedId = $relatedModel?->getKey();
        $this->failureRequestId = $requestId;

        return $this;
    }

    /**
     * Appelé par Illuminate\Mail\SendQueuedMailable quand le job échoue définitivement.
     */
    public function failed(Throwable $e): void
    {
        $recorder = app(FailedEmailRecorder::class);

        foreach ($this->to ?: [['address' => 'unknown', 'name' => null]] as $recipient) {
            $recorder->record(
                $this,
                (string) ($recipient['address'] ?? 'unknown'),
                $recipient['name'] ?? null,
                $e,
                FailedEmailRecorder::SOURCE_QUEUE,
                ['type' => $this->failureRelatedType, 'id' => $this->failureRelatedId],
                $this->failureRequestId,
            );
        }
    }
}
