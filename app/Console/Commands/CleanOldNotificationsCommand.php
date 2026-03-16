<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

class CleanOldNotificationsCommand extends Command
{
    protected $signature = 'notifications:clean {days=30 : Delete notifications older than this many days}';

    protected $description = 'Clean old notifications (run daily at 03:00)';

    public function handle(NotificationService $service): int
    {
        $days = (int) $this->argument('days');
        $count = $service->cleanOldNotifications($days);

        $this->info("Deleted {$count} old notification(s).");

        return self::SUCCESS;
    }
}
