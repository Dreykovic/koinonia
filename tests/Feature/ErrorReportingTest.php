<?php

namespace Tests\Feature;

use App\Enums\PlatformRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Mockery;
use RuntimeException;
use Sentry\ClientInterface;
use Sentry\Event;
use Sentry\State\HubInterface;
use Sentry\State\Scope;
use Tests\TestCase;

class ErrorReportingTest extends TestCase
{
    use RefreshDatabase;

    public function test_unhandled_exception_is_reported_to_sentry_with_user_and_route(): void
    {
        $user = User::create([
            'name' => 'Test',
            'email' => 'sentry-' . uniqid() . '@example.test',
            'password' => 'password',
            'roles' => [PlatformRole::MEMBRE],
        ]);

        Route::middleware('web')->get('/_boom', fn () => throw new RuntimeException('boom'))->name('boom');

        $captured = null;
        $client = Mockery::mock(ClientInterface::class)->shouldIgnoreMissing();
        $client->shouldReceive('captureException')
            ->once()
            ->andReturnUsing(function ($e, ?Scope $scope) use (&$captured) {
                // Les processeurs globaux (intégrations du SDK) peuvent écarter l'événement : on les neutralise.
                $processors = new \ReflectionProperty(Scope::class, 'globalEventProcessors');
                $processors->setAccessible(true);
                $processors->setValue(null, []);

                $captured = $scope->applyToEvent(Event::createEvent());

                return null;
            });

        /** @var HubInterface $hub */
        $hub = $this->app->make('sentry');
        $hub->bindClient($client);

        $this->actingAs($user)->get('/_boom')->assertStatus(500);

        $this->assertSame((string) $user->id, (string) $captured->getUser()->getId());
        $this->assertSame('boom', $captured->getTags()['route']);
        $this->assertNotEmpty($captured->getTags()['request_id']);
    }
}
