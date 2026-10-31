<?php

namespace App\Http\Controllers;

use App\Support\SchedulerHeartbeat;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Health check public (GET /up) : base, cache, file d'attente, stockage, scheduler.
 *
 * 200 si tout va bien, 503 sinon. La réponse ne contient jamais de message d'exception
 * ni de configuration : le détail d'un échec est journalisé (health.check.failed).
 */
class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->run('database', fn () => $this->database()),
            'cache' => $this->run('cache', fn () => $this->cache()),
            'queue' => $this->run('queue', fn () => $this->queue()),
            'storage' => $this->run('storage', fn () => $this->storage()),
            'scheduler' => $this->run('scheduler', fn () => $this->scheduler()),
        ];

        $healthy = collect($checks)->every(fn (array $check) => $check['status'] === 'ok');

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'checks' => $checks,
            'checked_at' => now()->toIso8601String(),
        ], $healthy ? 200 : 503)->header('Cache-Control', 'no-store');
    }

    /**
     * Exécute une vérification ; une exception devient un statut "fail" générique.
     *
     * @param  callable(): array<string, mixed>  $check
     * @return array<string, mixed>
     */
    private function run(string $name, callable $check): array
    {
        try {
            return $check();
        } catch (Throwable $e) {
            Log::channel('app')->error('health.check.failed', [
                'check' => $name,
                'exception_class' => get_class($e),
                'error' => $e->getMessage(),
            ]);

            return ['status' => 'fail'];
        }
    }

    private function database(): array
    {
        DB::select('select 1');

        return ['status' => 'ok'];
    }

    /**
     * Écriture puis relecture d'une clé jetable dans le store par défaut.
     */
    private function cache(): array
    {
        $key = 'monitoring:health:'.Str::random(12);
        Cache::put($key, 'ok', 10);
        $value = Cache::get($key);
        Cache::forget($key);

        if ($value !== 'ok') {
            Log::channel('app')->error('health.check.failed', ['check' => 'cache', 'error' => 'read_after_write_mismatch']);

            return ['status' => 'fail'];
        }

        return ['status' => 'ok'];
    }

    /**
     * Les tables jobs / failed_jobs doivent être lisibles ; on remonte les volumes.
     */
    private function queue(): array
    {
        $pending = DB::table(config('queue.connections.database.table', 'jobs'))->count();
        $failed = DB::table(config('queue.failed.table', 'failed_jobs'))->count();

        return ['status' => 'ok', 'pending' => $pending, 'failed' => $failed];
    }

    private function storage(): array
    {
        if (! is_writable(storage_path('logs'))) {
            Log::channel('app')->error('health.check.failed', ['check' => 'storage', 'error' => 'logs_not_writable']);

            return ['status' => 'fail'];
        }

        return ['status' => 'ok'];
    }

    private function scheduler(): array
    {
        $last = SchedulerHeartbeat::lastBeat();

        return [
            'status' => SchedulerHeartbeat::isStale() ? 'fail' : 'ok',
            'last_beat' => $last?->toIso8601String(),
        ];
    }
}
