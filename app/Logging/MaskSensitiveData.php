<?php

namespace App\Logging;

use App\Support\LogMask;
use Illuminate\Database\QueryException;
use Illuminate\Log\Logger;
use Monolog\LogRecord;

/**
 * Tap Monolog des canaux fichiers (config/logging.php) : une QueryException reportée
 * par le handler d'exceptions ou journalisée telle quelle porte le SQL avec ses valeurs
 * liées (e-mails, téléphones, hash de mot de passe). Le processeur remplace l'exception,
 * son message et le message du log par leur version masquée (LogMask), trace conservée.
 */
class MaskSensitiveData
{
    public function __invoke(Logger $logger): void
    {
        $logger->getLogger()->pushProcessor(static function (LogRecord $record): LogRecord {
            $exception = $record->context['exception'] ?? null;

            if (! $exception instanceof QueryException) {
                return $record;
            }

            $raw = $exception->getMessage();
            $context = $record->context;
            $context['exception'] = LogMask::loggableException($exception);

            if (($context['error'] ?? null) === $raw) {
                $context['error'] = LogMask::exceptionMessage($exception);
            }

            return $record->with(
                message: $record->message === $raw ? LogMask::exceptionMessage($exception) : $record->message,
                context: $context,
            );
        });
    }
}
