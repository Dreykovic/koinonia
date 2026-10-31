<?php

namespace App\Mail;

use App\Mail\Concerns\RecordsDeliveryFailure;
use App\Models\AccountRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountRequestRejectedMail extends Mailable implements ShouldQueue
{
    use Queueable, RecordsDeliveryFailure, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public AccountRequest $accountRequest
    ) {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[ITCJ] Votre demande de compte',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.account-request-rejected',
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
