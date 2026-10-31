<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Battement de cœur du scheduler : schedule:run appelle beat() chaque minute,
 * le health check / les alertes interrogent isStale() pour détecter un cron arrêté.
 */
class SchedulerHeartbeat
{
    public const CACHE_KEY = 'monitoring:scheduler:heartbeat';

    /**
     * Enregistre l'instant présent comme dernier battement.
     */
    public static function beat(): void
    {
        Cache::forever(self::CACHE_KEY, now()->timestamp);
    }

    /**
     * Date du dernier battement, ou null si le scheduler n'a jamais tourné (ou cache vidé).
     */
    public static function lastBeat(): ?Carbon
    {
        $timestamp = Cache::get(self::CACHE_KEY);

        return is_numeric($timestamp) ? Carbon::createFromTimestamp((int) $timestamp) : null;
    }

    /**
     * Vrai si aucun battement ou si le dernier date de plus de
     * monitoring.scheduler.heartbeat_max_age_minutes.
     */
    public static function isStale(): bool
    {
        $last = self::lastBeat();
        $maxAge = (int) config('monitoring.scheduler.heartbeat_max_age_minutes', 5);

        return $last === null || $last->lt(now()->subMinutes($maxAge));
    }
}
