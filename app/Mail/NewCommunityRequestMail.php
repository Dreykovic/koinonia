<?php

namespace App\Mail;

use App\Mail\Concerns\RecordsDeliveryFailure;
use App\Models\CommunityRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewCommunityRequestMail extends Mailable implements ShouldQueue
{
    use Queueable, RecordsDeliveryFailure, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public CommunityRequest $communityRequest
    ) {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $type = $this->communityRequest->isRepair() ? 'Reparation' : 'Achat';

        return new Envelope(
            subject: "[ITCJ] Nouvelle demande - {$type}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.community-request-new',
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
