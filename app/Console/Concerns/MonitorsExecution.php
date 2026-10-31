<?php

namespace App\Console\Concerns;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Journalisation d'une commande Artisan dans le canal `queue` :
 * début, fin, durée, compteurs métier, et échec (exception) avec code de sortie non nul.
 *
 * Usage dans handle() :
 *   return $this->monitored(function () {
 *       $this->metrics['deleted'] = ...;
 *       return self::SUCCESS;
 *   });
 */
trait MonitorsExecution
{
    /**
     * Compteurs métier ajoutés au log de fin (ex. ['generated' => 4]).
     *
     * @var array<string, int|string|bool|null>
     */
    protected array $metrics = [];

    protected function monitored(callable $callback): int
    {
        $context = ['command' => $this->getName()];
        $startedAt = hrtime(true);

        Log::channel('queue')->info('command.started', $context + ['arguments' => $this->loggableArguments()]);

        try {
            $exitCode = (int) $callback();
        } catch (Throwable $e) {
            Log::channel('queue')->error('command.failed', $context + [
                'duration_ms' => $this->elapsedMs($startedAt),
                'metrics' => $this->metrics,
                'exception_class' => get_class($e),
                'error' => $e->getMessage(),
                'location' => $e->getFile().':'.$e->getLine(),
                'exception' => $e,
            ]);

            $this->error('Échec de la commande : '.$e->getMessage());

            // Code non nul : le hook onFailure du scheduler prend le relais (alerte).
            return Command::FAILURE;
        }

        $level = $exitCode === Command::SUCCESS ? 'info' : 'warning';

        Log::channel('queue')->{$level}('command.finished', $context + [
            'exit_code' => $exitCode,
            'duration_ms' => $this->elapsedMs($startedAt),
            'metrics' => $this->metrics,
        ]);

        return $exitCode;
    }

    /**
     * Arguments et options explicitement fournis (sans le nom de la commande).
     */
    private function loggableArguments(): array
    {
        $arguments = array_diff_key($this->arguments(), ['command' => true]);
        $options = array_filter($this->options(), fn ($value) => $value !== null && $value !== false && $value !== []);

        return $arguments + $options;
    }

    private function elapsedMs(int $startedAt): int
    {
        return (int) round((hrtime(true) - $startedAt) / 1_000_000);
    }
}
