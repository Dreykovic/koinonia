<?php

namespace App\Console\Commands;

use App\Services\SundayMealService;
use Illuminate\Console\Command;

class CloseMealInscriptionsCommand extends Command
{
    protected $signature = 'meals:close';

    protected $description = 'Close Sunday meal inscriptions (run every Saturday at 18:00)';

    public function handle(SundayMealService $service): int
    {
        $count = $service->closeMealsForSaturday();

        $this->info("Closed {$count} meal inscription(s).");

        return self::SUCCESS;
    }
}
