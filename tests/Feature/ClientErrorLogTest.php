<?php

namespace Tests\Feature;

use App\Enums\PlatformRole;
use App\Models\User;
use App\Support\AppVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Monolog\Handler\TestHandler;
use Monolog\LogRecord;
use Tests\TestCase;

class ClientErrorLogTest extends TestCase
{
    use RefreshDatabase;

    private TestHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new TestHandler();
        $this->app['log']->channel('app')->getLogger()->pushHandler($this->handler);
    }

    private function user(): User
    {
        return User::create([
            'name' => 'Test',
            'email' => 'client-err-'.uniqid().'@example.test',
            'password' => 'password',
            'roles' => [PlatformRole::MEMBRE],
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'type' => 'error',
            'message' => 'TypeError: x is undefined',
            'source' => 'https://koinonia.test/build/assets/app-abc123.js',
            'line' => 12,
            'col' => 34,
            'stack' => "TypeError: x is undefined\n    at foo (app-abc123.js:12:34)",
            'url' => 'https://koinonia.test/meals?week=3',
            'version' => '2026.10.26',
        ], $overrides);
    }

    private function record(string $message): ?LogRecord
    {
        return collect($this->handler->getRecords())->first(fn ($r) => $r->message === $message);
    }

    public function test_guest_js_error_is_logged_with_url_version_and_user_agent(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->withHeader('User-Agent', 'Mozilla/5.0 Test')
            ->postJson(route('log.client'), $this->payload())
            ->assertNoContent();

        $record = $this->record('client.js.error');

        $this->assertNotNull($record);
        $this->assertSame('ERROR', $record->level->getName());
        $this->assertSame('TypeError: x is undefined', $record->context['error']);
        $this->assertSame('https://koinonia.test/meals?week=3', $record->context['page_url']);
        $this->assertSame(12, $record->context['line']);
        $this->assertSame(34, $record->context['col']);
        $this->assertSame(['TypeError: x is undefined', 'at foo (app-abc123.js:12:34)'], $record->context['stack']);
        $this->assertSame(AppVersion::current(), $record->context['app_version']);
        $this->assertSame('2026.10.26', $record->context['client_version']);
        $this->assertSame('Mozilla/5.0 Test', $record->context['user_agent']);
        $this->assertNotEmpty($record->context['request_id']);
        $this->assertArrayNotHasKey('user_id', $record->context);
    }

    public function test_authenticated_error_carries_user_from_request_context(): void
    {
        $user = $this->user();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->actingAs($user)
            ->postJson(route('log.client'), $this->payload(['type' => 'rejection']))
            ->assertNoContent();

        $record = $this->record('client.js.unhandled_rejection');

        $this->assertSame($user->id, $record->context['user_id']);
        $this->assertSame([PlatformRole::MEMBRE], $record->context['roles']);
        $this->assertSame('log.client', $record->context['route']);
    }

    public function test_network_and_broadcast_failures_are_warnings(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $this->postJson(route('log.client'), $this->payload([
            'type' => 'network', 'message' => 'HTTP 500', 'method' => 'POST',
            'status' => 500, 'request_url' => '/api/v1/reservations', 'failed_request_id' => 'abc-12345678',
        ]))->assertNoContent();

        $this->postJson(route('log.client'), $this->payload([
            'type' => 'broadcast', 'message' => 'Broadcast connection unavailable', 'state' => 'unavailable',
        ]))->assertNoContent();

        $network = $this->record('client.network.failed');
        $this->assertSame('WARNING', $network->level->getName());
        $this->assertSame('POST', $network->context['http_method']);
        $this->assertSame(500, $network->context['http_status']);
        $this->assertSame('/api/v1/reservations', $network->context['request_url']);
        $this->assertSame('abc-12345678', $network->context['failed_request_id']);

        $broadcast = $this->record('client.broadcast.connection_lost');
        $this->assertSame('WARNING', $broadcast->level->getName());
        $this->assertSame('unavailable', $broadcast->context['state']);
    }

    public function test_invalid_payloads_are_rejected_without_logging(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $invalid = [
            $this->payload(['type' => 'whatever']),
            $this->payload(['message' => '']),
            $this->payload(['message' => str_repeat('a', 5001)]),
            $this->payload(['line' => 'abc']),
            $this->payload(['version' => "1.0\n[fake] log"]),
            $this->payload(['failed_request_id' => "bad\nid"]),
            $this->payload(['state' => 'pwned']),
            $this->payload(['method' => 'TRACE']),
        ];

        foreach ($invalid as $payload) {
            $this->postJson(route('log.client'), $payload)->assertStatus(422);
        }

        $this->assertNull($this->record('client.js.error'));
    }

    public function test_fields_are_truncated_sanitized_and_redacted(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->postJson(route('log.client'), $this->payload([
                'message' => "Boom\r\n[2026-01-01] fake.ERROR: injected for jean.dupont@example.com".str_repeat('x', 2000),
                'url' => 'https://koinonia.test/reset-password/secret-token-123?email=jean@example.com&page=2',
                'stack' => str_repeat("at line\n", 2000)."\n[2026-01-01] local.CRITICAL: forged",
                'source' => "app.js\n[2026-01-01] local.CRITICAL: forged",
            ]))->assertNoContent();

        $context = $this->record('client.js.error')->context;

        $this->assertStringNotContainsString("\n", $context['error']);
        $this->assertStringNotContainsString('jean.dupont@', $context['error']);
        $this->assertStringContainsString('j***@example.com', $context['error']);
        $this->assertLessThanOrEqual(501, mb_strlen($context['error']));
        $this->assertCount(30, $context['stack']);
        $this->assertStringNotContainsString("\n", $context['source']);
        foreach ($context['stack'] as $frame) {
            $this->assertStringNotContainsString("\n", $frame);
        }
        $this->assertStringNotContainsString('secret-token-123', $context['page_url']);
        $this->assertStringNotContainsString('jean@example.com', $context['page_url']);
        $this->assertStringContainsString('page=2', $context['page_url']);
    }

    public function test_layouts_expose_version_and_report_url(): void
    {
        $this->withoutVite();

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('<meta name="app-version" content="'.AppVersion::current().'">', false)
            ->assertSee('<meta name="client-log-url" content="/log/client">', false);

        $this->actingAs($this->user())->get(route('dashboard'))
            ->assertOk()
            ->assertSee('<meta name="app-version" content="'.AppVersion::current().'">', false);
    }

    public function test_csrf_token_is_required(): void
    {
        // Le middleware CSRF est neutralisé en tests unitaires de l'application : on force la vérification.
        $this->app->instance('env', 'local');

        $this->postJson(route('log.client'), $this->payload())->assertStatus(419);

        $this->assertNull($this->record('client.js.error'));
    }

    public function test_endpoint_is_throttled(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        for ($i = 0; $i < 20; $i++) {
            $this->postJson(route('log.client'), $this->payload(['message' => "err $i"]))->assertNoContent();
        }

        $this->postJson(route('log.client'), $this->payload(['message' => 'one too many']))->assertStatus(429);
    }
}
