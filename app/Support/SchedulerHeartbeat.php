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
     * Premier instant où l'absence de battement a été constatée (cache vidé, installation neuve).
     */
    public const MISSING_SINCE_KEY = 'monitoring:scheduler:missing_since';

    /**
     * Enregistre l'instant présent comme dernier battement.
     */
    public static function beat(): void
    {
        Cache::forever(self::CACHE_KEY, now()->timestamp);
        Cache::forget(self::MISSING_SINCE_KEY);
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
     * Vrai si le dernier battement date de plus de monitoring.scheduler.heartbeat_max_age_minutes.
     *
     * Sans battement (cache:clear / optimize:clear après un déploiement, installation neuve),
     * on laisse au cron le même délai à partir du premier constat : pas de fausse alerte
     * ni de 503 sur /up juste après un vidage du cache, mais un cron qui ne tourne jamais
     * finit bien par être signalé.
     */
    public static function isStale(): bool
    {
        $maxAge = (int) config('monitoring.scheduler.heartbeat_max_age_minutes', 5);
        $last = self::lastBeat();

        if ($last !== null) {
            return $last->lt(now()->subMinutes($maxAge));
        }

        Cache::add(self::MISSING_SINCE_KEY, now()->timestamp);
        $since = Cache::get(self::MISSING_SINCE_KEY);

        return ! is_numeric($since) || Carbon::createFromTimestamp((int) $since)->lt(now()->subMinutes($maxAge));
    }
}
