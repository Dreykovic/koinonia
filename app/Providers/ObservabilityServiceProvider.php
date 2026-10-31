<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

/**
 * Observabilité locale : requêtes SQL lentes et chargements paresseux (N+1).
 * Tout est journalisé dans le canal `app`, rien n'est envoyé à l'extérieur.
 */
class ObservabilityServiceProvider extends ServiceProvider
{
    /**
     * Violations N+1 déjà signalées dans ce processus (une seule ligne par modèle + relation).
     *
     * @var array<string, true>
     */
    private static array $reportedLazyLoads = [];

    public function boot(): void
    {
        $this->logSlowQueries();
        $this->detectLazyLoading();
    }

    /**
     * Requêtes au-delà de monitoring.slow_query_ms (seuil négatif : désactivé).
     * Le SQL est journalisé avec ses placeholders, jamais avec les valeurs liées.
     */
    private function logSlowQueries(): void
    {
        DB::listen(function (QueryExecuted $query) {
            $threshold = (int) config('monitoring.slow_query_ms', 500);

            if ($threshold < 0 || $query->time < $threshold) {
                return;
            }

            Log::channel('app')->warning('db.query.slow', [
                'sql' => $query->sql,
                'duration_ms' => round($query->time, 2),
                'threshold_ms' => $threshold,
                'connection' => $query->connectionName,
            ]);
        });
    }

    /**
     * Hors production : les chargements paresseux (signe de N+1) sont journalisés
     * au lieu de lever une exception. En production : rien n'est activé.
     */
    private function detectLazyLoading(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());

        Model::handleLazyLoadingViolationUsing(function (Model $model, string $relation) {
            $key = get_class($model).'::'.$relation;

            if (isset(self::$reportedLazyLoads[$key])) {
                return;
            }
            self::$reportedLazyLoads[$key] = true;

            Log::channel('app')->warning('db.lazy_loading', [
                'model' => get_class($model),
                'relation' => $relation,
                'location' => self::callerLocation(),
            ]);
        });
    }

    /**
     * Premier appelant dans le code de l'application (hors vendor), pour retrouver la boucle fautive.
     */
    private static function callerLocation(): ?string
    {
        $base = base_path().DIRECTORY_SEPARATOR;
        $vendor = DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR;

        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 50) as $frame) {
            $file = $frame['file'] ?? null;

            if ($file && ! str_contains($file, $vendor) && $file !== __FILE__) {
                return str_replace($base, '', $file).':'.($frame['line'] ?? 0);
            }
        }

        return null;
    }

    /**
     * Réinitialise la déduplication (tests, workers longs).
     */
    public static function resetLazyLoadingReports(): void
    {
        self::$reportedLazyLoads = [];
    }
}
