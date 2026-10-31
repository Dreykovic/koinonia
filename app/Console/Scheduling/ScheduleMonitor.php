<?php

namespace App\Console\Scheduling;

use App\Services\AlertService;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Throwable;

/**
 * Hook d'échec des tâches planifiées : une tâche qui se termine avec un code non nul
 * (ou dont la closure lève une exception) est journalisée en error dans `queue`
 * avec la fin de sa sortie, puis déclenche l'alerte `scheduled_task_failed`.
 */
class ScheduleMonitor
{
    /** Nombre maximal de caractères de sortie conservés dans le log. */
    public const OUTPUT_TAIL = 2000;

    public static function watch(Event $event): Event
    {
        // Le type Stringable du paramètre $output fait capturer la sortie par Laravel
        // (fichier storage/logs/schedule-<hash>.log, écrasé à chaque exécution).
        return $event->onFailure(function (Stringable $output) use ($event) {
            static::failed($event, (string) $output);
        });
    }

    public static function failed(Event $event, string $output = ''): void
    {
        $context = [
            'task' => static::taskName($event),
            'exit_code' => $event->exitCode,
            'expression' => $event->expression,
        ];

        Log::channel('queue')->error('schedule.task.failed', $context + [
            'output' => Str::substr(trim($output), -self::OUTPUT_TAIL),
        ]);

        try {
            app(AlertService::class)->send('scheduled_task_failed', 'Une tâche planifiée a échoué.', $context);
        } catch (Throwable $e) {
            Log::channel('queue')->error('schedule.task.failed.alert_failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Nom lisible : la description, sinon la commande Artisan sans le binaire PHP.
     */
    public static function taskName(Event $event): string
    {
        if (is_string($event->description) && $event->description !== '') {
            return $event->description;
        }

        $command = (string) $event->command;

        return Str::contains($command, "'artisan' ") ? Str::after($command, "'artisan' ") : $command;
    }
}
