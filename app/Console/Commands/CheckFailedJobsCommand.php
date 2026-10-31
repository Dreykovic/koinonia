<?php

namespace App\Console\Commands;

use App\Console\Concerns\MonitorsExecution;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckFailedJobsCommand extends Command
{
    use MonitorsExecution;

    protected $signature = 'monitoring:failed-jobs';

    protected $description = 'Report jobs waiting in the failed_jobs table (run daily)';

    public function handle(): int
    {
        return $this->monitored(function () {
            $table = config('queue.failed.table', 'failed_jobs');
            $connection = config('queue.failed.database');

            $total = DB::connection($connection)->table($table)->count();
            $recent = DB::connection($connection)->table($table)->where('failed_at', '>=', now()->subDay())->count();

            $this->metrics = ['total' => $total, 'last_24h' => $recent];

            // Chaque échec a déjà été alerté au moment où il s'est produit (job_failed) :
            // ici on rappelle seulement que des jobs attendent un `queue:retry` ou un `queue:forget`.
            if ($total > 0) {
                Log::channel('queue')->warning('queue.failed_jobs.pending', $this->metrics);
                $this->warn("{$total} job(s) en échec ({$recent} sur les dernières 24 h). Voir php artisan queue:failed.");
            } else {
                $this->info('Aucun job en échec.');
            }

            return self::SUCCESS;
        });
    }
}
