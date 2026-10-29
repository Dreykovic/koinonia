<?php

namespace Tests\Feature;

use App\Enums\PlatformRole;
use App\Http\Middleware\AddRequestContext;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Monolog\Handler\TestHandler;
use Tests\TestCase;

class RequestContextTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::create([
            'name' => 'Test',
            'email' => 'ctx-' . uniqid() . '@example.test',
            'password' => 'password',
            'roles' => [PlatformRole::MEMBRE],
        ]);
    }

    public function test_response_carries_a_generated_request_id(): void
    {
        $id = $this->get(route('login'))->headers->get(AddRequestContext::HEADER);

        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $id);
    }

    public function test_safe_incoming_request_id_is_kept_and_unsafe_one_is_replaced(): void
    {
        $this->withHeader(AddRequestContext::HEADER, 'abc-12345678')->get(route('login'))
            ->assertHeader(AddRequestContext::HEADER, 'abc-12345678');

        $id = $this->withHeader(AddRequestContext::HEADER, "bad\nforged line")->get(route('login'))
            ->headers->get(AddRequestContext::HEADER);

        $this->assertNotSame("bad\nforged line", $id);
    }

    public function test_logs_written_during_a_request_include_user_and_route(): void
    {
        $handler = new TestHandler();
        $this->app['log']->channel('single')->getLogger()->pushHandler($handler);

        Route::middleware('web')->get('/_probe', function () {
            logger()->channel('single')->warning('probe');

            return 'ok';
        })->name('probe');

        $user = $this->user();
        $this->actingAs($user)->get('/_probe')->assertOk();

        $record = collect($handler->getRecords())->first(fn ($r) => $r->message === 'probe');

        $this->assertSame($user->id, $record->context['user_id']);
        $this->assertSame('probe', $record->context['route']);
        $this->assertSame('GET', $record->context['method']);
        $this->assertNotEmpty($record->context['request_id']);
    }
}
