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
            'email' => 'ctx-'.uniqid().'@example.test',
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
        $this->actingAs($user)->get('/_probe?token=secret-value&email=x@example.test')->assertOk();

        $record = collect($handler->getRecords())->first(fn ($r) => $r->message === 'probe');

        $this->assertSame($user->id, $record->context['user_id']);
        $this->assertSame([PlatformRole::MEMBRE], $record->context['roles']);
        $this->assertSame('probe', $record->context['route']);
        $this->assertSame('GET', $record->context['method']);
        $this->assertNotEmpty($record->context['request_id']);
        $this->assertSame('127.0.0.1', $record->context['ip']);
        // URL sans query string (tokens, e-mails).
        $this->assertStringEndsWith('/_probe', $record->context['url']);
        $this->assertStringNotContainsString('secret-value', json_encode($record->context));
    }

    public function test_reset_password_token_is_masked_in_the_logged_url(): void
    {
        $handler = new TestHandler();
        $this->app['log']->channel('security')->getLogger()->pushHandler($handler);

        $this->get('/reset-password/tok-SECRET-123?email=jean@example.test')->assertOk();

        $record = collect($handler->getRecords())->first(fn ($r) => $r->message === 'auth.password.reset_form');

        $this->assertStringEndsWith('/reset-password/***', $record->context['url']);
        $this->assertStringNotContainsString('tok-SECRET-123', json_encode($record->context));
        $this->assertSame('j***@example.test', $record->context['email']);
    }

    public function test_unhandled_exception_is_logged_with_request_context(): void
    {
        $handler = new TestHandler();
        $this->app['log']->channel('single')->getLogger()->pushHandler($handler);

        Route::middleware('web')->get('/_boom', fn () => throw new \RuntimeException('boom'))->name('boom');

        $user = $this->user();
        $this->actingAs($user)->get('/_boom')->assertStatus(500);

        $record = collect($handler->getRecords())->first(fn ($r) => str_contains($r->message, 'boom'));

        $this->assertSame($user->id, $record->context['user_id']);
        $this->assertSame('boom', $record->context['route']);
        $this->assertNotEmpty($record->context['request_id']);
        $this->assertInstanceOf(\RuntimeException::class, $record->context['exception']);
    }
}
