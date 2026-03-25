<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('meals:close')->saturdays()->at('18:00');

        $schedule->command('meals:generate 4')->weekly()->sundays()->at('00:00');

        $schedule->command('notifications:clean 30')->daily()->at('03:00');

        $schedule->command('queue:work --stop-when-empty --tries=3 --timeout=60')
            ->everyMinute()
            ->withoutOverlapping();
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
