<?php

namespace App\Services;

use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Supprimer les notifications plus anciennes que $days jours.
     */
    public function cleanOldNotifications(int $days = 30): int
    {
        $deletedCount = Notification::where('created_at', '<', now()->subDays($days))->delete();

        Log::info('Nettoyage des anciennes notifications', [
            'older_than_days' => $days,
            'deleted' => $deletedCount,
        ]);

        return $deletedCount;
    }
}
