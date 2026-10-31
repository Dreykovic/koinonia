<?php

namespace App\Services;

use App\Traits\LogsErrors;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Génération des QR codes. Le contenu encodé (URL) n'est jamais journalisé :
 * seuls la taille et la longueur du contenu apparaissent dans les logs.
 */
class QRCodeService
{
    use LogsErrors;

    /**
     * Generate an inline SVG QR code.
     *
     * Renvoie une chaîne vide en cas d'échec (erreur journalisée) : la page
     * appelante s'affiche sans le QR code plutôt que de planter.
     */
    public function generate(string $url, int $size = 300): string
    {
        try {
            return $this->svg($url, $size);
        } catch (Throwable $e) {
            $this->logError('qrcode.generate.failed', $e, $this->context($url, $size, 'inline'));

            return '';
        }
    }

    /**
     * Return a downloadable QR code response (SVG).
     *
     * En cas d'échec : erreur journalisée et retour à la page précédente
     * avec un message, au lieu d'une page 500.
     */
    public function downloadPng(string $url, int $size = 500): Response
    {
        try {
            $svg = $this->svg($url, $size);
        } catch (Throwable $e) {
            $this->logError('qrcode.generate.failed', $e, $this->context($url, $size, 'download'));

            return redirect()->back()->with('error', 'Impossible de générer le QR code pour le moment. Veuillez réessayer plus tard.');
        }

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'attachment; filename="qrcode.svg"',
        ]);
    }

    private function svg(string $url, int $size): string
    {
        return (string) QrCode::size($size)
            ->format('svg')
            ->generate($url);
    }

    /**
     * Contexte de log sans le contenu encodé (cf. docs/logging.md).
     */
    private function context(string $url, int $size, string $mode): array
    {
        return ['size' => $size, 'mode' => $mode, 'payload_length' => strlen($url)];
    }
}
