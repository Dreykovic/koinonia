<?php

namespace Tests\Feature;

use App\Http\Middleware\AddRequestContext;
use App\Support\SafeLog;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Monolog\Handler\TestHandler;
use Tests\TestCase;

/**
 * Couverture globale du logging (issue #32) : chaque route reçoit le contexte de
 * requête, et les blocs "ne jamais lever" laissent toujours une trace.
 */
class LoggingCoverageTest extends TestCase
{
    /**
     * Routes volontairement hors des groupes web/api, avec la raison.
     * Toute nouvelle route hors groupe fait échouer le test tant qu'elle n'est pas justifiée ici.
     */
    private const WITHOUT_CONTEXT = [
        // Routes de développement d'Ignition (paquet require-dev, absent en production avec --no-dev).
        '_ignition/',
    ];

    /**
     * @return array<int, RoutingRoute>
     */
    private function applicationRoutes(): array
    {
        return array_values(array_filter(
            Route::getRoutes()->getRoutes(),
            fn (RoutingRoute $route) => ! $this->isAllowListed($route),
        ));
    }

    private function isAllowListed(RoutingRoute $route): bool
    {
        foreach (self::WITHOUT_CONTEXT as $prefix) {
            if (str_starts_with($route->uri(), $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function describe(RoutingRoute $route): string
    {
        return implode('|', $route->methods()).' /'.ltrim($route->uri(), '/').($route->getName() ? " ({$route->getName()})" : '');
    }

    public function test_every_route_belongs_to_the_web_or_api_group(): void
    {
        $orphans = [];

        foreach ($this->applicationRoutes() as $route) {
            if (array_intersect(['web', 'api'], $route->gatherMiddleware()) === []) {
                $orphans[] = $this->describe($route);
            }
        }

        $this->assertSame([], $orphans, 'Routes sans groupe web/api : ajouter le groupe ou justifier l\'exception dans WITHOUT_CONTEXT.');
    }

    public function test_every_web_and_api_route_gets_the_request_context_middleware(): void
    {
        $router = app('router');
        $routes = $this->applicationRoutes();
        $missing = [];

        foreach ($routes as $route) {
            if (! in_array(AddRequestContext::class, $router->gatherRouteMiddleware($route), true)) {
                $missing[] = $this->describe($route);
            }
        }

        // Garde-fou : le parcours porte bien sur les routes de l'application.
        $this->assertGreaterThan(50, count($routes));
        $this->assertSame([], $missing, 'Routes sans AddRequestContext (request_id absent des logs).');
    }

    public function test_health_check_and_broadcast_auth_keep_the_request_context(): void
    {
        $router = app('router');

        foreach (['health', 'broadcasting.auth'] as $name) {
            $route = Route::getRoutes()->getByName($name);

            if ($route === null && $name === 'broadcasting.auth') {
                // Broadcast::routes() n'est enregistré qu'avec le BroadcastServiceProvider.
                continue;
            }

            $this->assertNotNull($route, "Route {$name} introuvable.");
            $this->assertContains(AddRequestContext::class, $router->gatherRouteMiddleware($route), $name);
        }
    }

    public function test_safe_log_writes_a_warning_with_exception_details(): void
    {
        $handler = new TestHandler();
        $this->app['log']->channel('app')->getLogger()->pushHandler($handler);

        SafeLog::failure('alert.dispatch.failed', new \RuntimeException('smtp down'), ['rule' => 'error_spike']);

        $record = $handler->getRecords()[0] ?? null;
        $this->assertNotNull($record);
        $this->assertSame('alert.dispatch.failed', $record->message);
        $this->assertSame('Warning', $record->level->name);
        $this->assertSame('error_spike', $record->context['rule']);
        $this->assertSame(\RuntimeException::class, $record->context['exception_class']);
        $this->assertSame('smtp down', $record->context['error']);
        $this->assertStringContainsString('LoggingCoverageTest.php:', $record->context['location']);
    }

    public function test_safe_log_never_throws_when_the_channel_is_broken(): void
    {
        Log::shouldReceive('channel')->andThrow(new \RuntimeException('storage/logs non inscriptible'));

        $errorLog = ini_get('error_log');
        $file = tempnam(sys_get_temp_dir(), 'safelog');
        ini_set('error_log', $file);

        try {
            SafeLog::failure('monitoring.request_metrics.failed', new \LogicException('boom'));
        } finally {
            ini_set('error_log', $errorLog ?: '');
        }

        $written = (string) file_get_contents($file);
        @unlink($file);

        $this->assertStringContainsString('monitoring.request_metrics.failed', $written);
        $this->assertStringContainsString('storage/logs non inscriptible', $written);
    }
}
