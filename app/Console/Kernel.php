<?php

namespace App\Console;

use App\Services\SundayMealService;
use App\Services\NotificationService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Close meal inscriptions every Saturday at 18:00
        $schedule->call(function () {
            app(SundayMealService::class)->closeMealsForSaturday();
        })->saturdays()->at('18:00');

        // Generate upcoming Sunday meals every Sunday at midnight
        $schedule->call(function () {
            app(SundayMealService::class)->generateUpcomingSundays(4);
        })->weekly()->sundays()->at('00:00');

        // Clean old notifications every day at 03:00
        $schedule->call(function () {
            app(NotificationService::class)->cleanOldNotifications(30);
        })->daily()->at('03:00');
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


