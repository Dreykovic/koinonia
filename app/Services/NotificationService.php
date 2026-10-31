<?php

namespace App\Services;

use App\Models\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotificationService
{
    /**
     * Supprimer les notifications plus anciennes que $days jours.
     *
     * Exécuté par le scheduler (notifications:clean) : canal `queue`, comme les autres
     * commandes planifiées. Un échec est journalisé puis relancé pour que la commande
     * sorte en erreur.
     */
    public function cleanOldNotifications(int $days = 30): int
    {
        try {
            $deletedCount = Notification::where('created_at', '<', now()->subDays($days))->delete();
        } catch (Throwable $e) {
            Log::channel('queue')->error('notifications.clean.failed', [
                'older_than_days' => $days,
                'exception_class' => get_class($e),
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);

            throw $e;
        }

        Log::channel('queue')->info('notifications.cleaned', [
            'older_than_days' => $days,
            'deleted' => $deletedCount,
        ]);

        return $deletedCount;
    }
}
