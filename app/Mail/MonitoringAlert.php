<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Throwable;

/**
 * E-mail d'alerte envoyé par AlertService.
 *
 * Volontairement sans ShouldQueue : l'alerte doit partir même si la file d'attente est en panne.
 */
class MonitoringAlert extends Mailable
{
    public function __construct(
        public string $rule,
        public string $alertMessage,
        public array $context = [],
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[ITCJ]['.app()->environment().'] Alerte : '.$this->rule,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.monitoring-alert',
            with: [
                'details' => $this->details(),
                'environment' => app()->environment(),
                'appUrl' => config('app.url'),
                'occurredAt' => now()->format('d/m/Y H:i:s T'),
            ],
        );
    }

    /**
     * Contexte aplati en chaînes lisibles (exceptions résumées, tableaux en JSON).
     *
     * @return array<string, string>
     */
    public function details(): array
    {
        return collect($this->context)->map(function ($value) {
            return match (true) {
                $value instanceof Throwable => get_class($value).': '.$value->getMessage(),
                $value === null, is_bool($value) => var_export($value, true),
                is_scalar($value) => (string) $value,
                default => (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            };
        })->all();
    }
}
