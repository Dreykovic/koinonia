<?php

namespace App\Console;

use App\Console\Scheduling\ScheduleMonitor;
use App\Support\SchedulerHeartbeat;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Battement de cœur : prouve que le cron appelle bien schedule:run (voir SchedulerHeartbeat).
        $schedule->call(fn () => SchedulerHeartbeat::beat())
            ->name('monitoring:heartbeat')
            ->everyMinute();

        $schedule->command('meals:close')->saturdays()->at('18:00');

        $schedule->command('meals:generate 4')->weekly()->sundays()->at('00:00');

        $schedule->command('notifications:clean 30')->daily()->at('03:00');

        $schedule->command('monitoring:failed-jobs')->daily()->at('07:00');

        $schedule->command('queue:work --stop-when-empty --tries=3 --timeout=60')
            ->everyMinute()
            ->withoutOverlapping();

        // Toute tâche en échec (code de sortie non nul ou exception) : log error + alerte.
        foreach ($schedule->events() as $event) {
            ScheduleMonitor::watch($event);
        }
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
