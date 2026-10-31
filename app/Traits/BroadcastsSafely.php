<?php

namespace App\Traits;

use App\Services\AlertService;
use App\Support\LogMask;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Diffusion temps réel (Pusher/Echo) qui ne casse jamais l'action métier :
 * un échec est journalisé (warning, canal `app`) et compté pour l'alerte
 * `broadcast_failure` ; l'interface retombe sur le polling de /api/v1.
 *
 * Les événements ShouldBroadcast sont mis en file : ici on ne voit que l'échec de la
 * mise en file ; l'appel Pusher qui échoue dans le worker est compté par
 * QueueMonitoringServiceProvider (même clé broadcast.failed, même règle).
 */
trait BroadcastsSafely
{
    /**
     * @param  object  $event  Événement ShouldBroadcast / ShouldBroadcastNow
     * @param  array  $context  Contexte métier ajouté au log en cas d'échec (ids, date…)
     * @param  bool  $toOthers  Équivalent de broadcast($event)->toOthers()
     * @return bool true si la diffusion a réussi
     */
    protected function broadcastSafely(object $event, array $context = [], bool $toOthers = false): bool
    {
        try {
            $pending = broadcast($event);

            if ($toOthers) {
                $pending->toOthers();
            }

            // PendingBroadcast diffuse dans son destructeur : on le force ici, dans le try.
            unset($pending);

            return true;
        } catch (Throwable $e) {
            Log::channel('app')->warning('broadcast.failed', $context + [
                'event' => get_class($event),
                'exception_class' => get_class($e),
                'error' => LogMask::exceptionMessage($e),
            ]);

            app(AlertService::class)->hit(
                'broadcast_failure',
                'Échecs répétés de diffusion temps réel (broadcast).',
                ['event' => get_class($event), 'exception_class' => get_class($e)],
                (int) config('monitoring.broadcast_failure.threshold', 5),
                (int) config('monitoring.broadcast_failure.window_minutes', 10),
            );

            return false;
        }
    }
}
