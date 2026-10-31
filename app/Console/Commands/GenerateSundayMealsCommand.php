<?php

namespace App\Console\Commands;

use App\Console\Concerns\MonitorsExecution;
use App\Services\SundayMealService;
use Illuminate\Console\Command;

class GenerateSundayMealsCommand extends Command
{
    use MonitorsExecution;

    protected $signature = 'meals:generate {weeks=4 : Number of upcoming Sundays to generate}';

    protected $description = 'Generate meals for upcoming Sundays (run every Sunday at midnight)';

    public function handle(SundayMealService $service): int
    {
        return $this->monitored(function () use ($service) {
            $weeks = (int) $this->argument('weeks');
            $count = $service->generateUpcomingSundays($weeks);

            $this->metrics = ['weeks' => $weeks, 'generated' => $count];
            $this->info("Generated {$count} Sunday meal(s).");

            return self::SUCCESS;
        });
    }
}
