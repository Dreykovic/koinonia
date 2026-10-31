<?php

namespace App\Console\Commands;

use App\Console\Concerns\MonitorsExecution;
use App\Services\SundayMealService;
use Illuminate\Console\Command;

class CloseMealInscriptionsCommand extends Command
{
    use MonitorsExecution;

    protected $signature = 'meals:close';

    protected $description = 'Close Sunday meal inscriptions (run every Saturday at 18:00)';

    public function handle(SundayMealService $service): int
    {
        return $this->monitored(function () use ($service) {
            $count = $service->closeMealsForSaturday();

            $this->metrics = ['closed' => $count];
            $this->info("Closed {$count} meal inscription(s).");

            return self::SUCCESS;
        });
    }
}
