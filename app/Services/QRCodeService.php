<?php

namespace App\Services;

use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\Response;

class QRCodeService
{
    /**
     * Generate an inline SVG QR code.
     */
    public function generate(string $url, int $size = 300): string
    {
        return QrCode::size($size)
            ->format('svg')
            ->generate($url);
    }

    /**
     * Return a downloadable PNG QR code response.
     */
    public function downloadPng(string $url, int $size = 500): Response
    {
        $svg = QrCode::size($size)
            ->format('svg')
            ->generate($url);

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'attachment; filename="qrcode.svg"',
        ]);
    }
}

