<?php

namespace App\Console\Commands;

use App\Console\Concerns\MonitorsExecution;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class CleanOldNotificationsCommand extends Command
{
    use MonitorsExecution;

    protected $signature = 'notifications:clean {days=30 : Delete notifications older than this many days}';

    protected $description = 'Clean old notifications (run daily at 03:00)';

    public function handle(NotificationService $service): int
    {
        return $this->monitored(function () use ($service) {
            $days = (int) $this->argument('days');
            $count = $service->cleanOldNotifications($days);

            $this->metrics = ['older_than_days' => $days, 'deleted' => $count];
            $this->info("Deleted {$count} old notification(s).");

            return self::SUCCESS;
        });
    }
}
