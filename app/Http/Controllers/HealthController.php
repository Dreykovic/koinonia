<?php

namespace App\Http\Controllers;

use App\Support\LogMask;
use App\Support\SchedulerHeartbeat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Health check public (GET /up, throttlé) : base, cache, file d'attente, stockage, scheduler.
 *
 * 200 si tout va bien, 503 sinon. Publiquement, seul le statut de chaque vérification est
 * renvoyé ; les volumes de la file et le dernier battement du scheduler ne sont ajoutés
 * qu'avec ?token= égal à MONITORING_HEALTH_TOKEN. La réponse ne contient jamais de message
 * d'exception : le détail d'un échec est journalisé (health.check.failed), au plus une
 * fois toutes les LOG_EVERY_SECONDS par vérification pour ne pas remplir le disque
 * quand un moniteur interroge /up en boucle pendant une panne.
 */
class HealthController extends Controller
{
    public const LOG_EVERY_SECONDS = 300;

    public function __invoke(Request $request): JsonResponse
    {
        $checks = ['database' => $this->run('database', fn () => $this->database())];

        // Sans base, la vérification de la file (tables jobs) échouerait aussi : une seule ligne de log.
        $checks['queue'] = $checks['database']['status'] === 'ok'
            ? $this->run('queue', fn () => $this->queue())
            : ['status' => 'fail'];

        $checks += [
            'cache' => $this->run('cache', fn () => $this->cache()),
            'storage' => $this->run('storage', fn () => $this->storage()),
            'scheduler' => $this->run('scheduler', fn () => $this->scheduler()),
        ];

        $healthy = collect($checks)->every(fn (array $check) => $check['status'] === 'ok');

        if (! $this->showsDetails($request)) {
            $checks = array_map(fn (array $check) => ['status' => $check['status']], $checks);
        }

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'checks' => $checks,
            'checked_at' => now()->toIso8601String(),
        ], $healthy ? 200 : 503)->header('Cache-Control', 'no-store');
    }

    /**
     * Détails (volumes de file, dernier battement) réservés au détenteur du jeton de supervision.
     */
    private function showsDetails(Request $request): bool
    {
        $expected = (string) config('monitoring.health_token');
        $given = $request->query('token');

        return $expected !== '' && is_string($given) && hash_equals($expected, $given);
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
            $this->logFailure($name, [
                'exception_class' => get_class($e),
                'error' => LogMask::exceptionMessage($e),
            ]);

            return ['status' => 'fail'];
        }
    }

    /**
     * health.check.failed, dédoublonné par vérification. Si le cache est lui-même en panne,
     * on journalise quand même (mieux vaut une ligne de trop qu'une panne invisible).
     */
    private function logFailure(string $check, array $context): void
    {
        try {
            $first = Cache::add('monitoring:health:logged:'.$check, now()->timestamp, self::LOG_EVERY_SECONDS);
        } catch (Throwable) {
            $first = true;
        }

        if ($first) {
            Log::channel('app')->error('health.check.failed', ['check' => $check] + $context);
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
            $this->logFailure('cache', ['error' => 'read_after_write_mismatch']);

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
            $this->logFailure('storage', ['error' => 'logs_not_writable']);

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
