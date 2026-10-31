<?php

namespace Tests\Feature;

use App\Enums\PlatformRole;
use App\Models\BulletinEdition;
use App\Models\User;
use App\Services\BirthdayService;
use App\Services\QRCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Monolog\Handler\TestHandler;
use RuntimeException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Tests\TestCase;

/**
 * Issue #27 : gestion d'erreur et logs des modules Bulletin, Dashboard, Guide,
 * What's new et QR codes.
 */
class BulletinDashboardLoggingTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, TestHandler> */
    private array $logs = [];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['app', 'audit', 'security'] as $channel) {
            $this->logs[$channel] = new TestHandler();
            $this->app['log']->channel($channel)->getLogger()->pushHandler($this->logs[$channel]);
        }
    }

    private function user(string $role): User
    {
        return User::create([
            'name' => 'Test '.$role,
            'email' => $role.'-'.uniqid().'@example.test',
            'password' => 'password',
            'roles' => [$role],
        ]);
    }

    private function bulletin(array $attributes = []): BulletinEdition
    {
        return BulletinEdition::create($attributes + [
            'title' => 'Bulletin test',
            'week_start' => now()->startOfWeek()->toDateString(),
            'week_end' => now()->endOfWeek()->toDateString(),
            'year' => now()->year,
            'is_published' => true,
        ]);
    }

    private function record(string $channel, string $message): ?array
    {
        foreach ($this->logs[$channel]->getRecords() as $record) {
            if ($record->message === $message) {
                return $record->toArray();
            }
        }

        return null;
    }

    // ---------------------------------------------------------------- Dashboard

    public function test_dashboard_still_renders_when_a_service_throws(): void
    {
        $this->app->instance(BirthdayService::class, new class extends BirthdayService
        {
            public function getWeekBirthdays($bulletin): Collection
            {
                throw new RuntimeException('birthdays down');
            }
        });

        $this->actingAs($this->user(PlatformRole::MEMBRE))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-degraded-notice', false)
            ->assertSee('anniversaires');

        $record = $this->record('app', 'dashboard.widget.failed');
        $this->assertNotNull($record);
        $this->assertSame('birthdays', $record['context']['widget']);
        $this->assertSame('birthdays down', $record['context']['error']);
        $this->assertNotNull($this->record('app', 'dashboard.degraded'));
    }

    public function test_dashboard_has_no_notice_when_everything_loads(): void
    {
        $this->actingAs($this->user(PlatformRole::ADMIN))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('data-degraded-notice', false);

        $this->assertNull($this->record('app', 'dashboard.widget.failed'));
    }

    // ---------------------------------------------------------------- Bulletin

    public function test_bulletin_store_failure_is_logged_and_redirects_back_with_input(): void
    {
        BulletinEdition::creating(fn () => throw new RuntimeException('db down'));

        $this->actingAs($this->user(PlatformRole::COMMUNICATION))
            ->from(route('bulletin.create'))
            ->post(route('bulletin.store'), [
                'title' => 'Edition qui echoue',
                'week_start' => '2026-11-02',
                'week_end' => '2026-11-08',
                'is_published' => 1,
            ])
            ->assertRedirect(route('bulletin.create'))
            ->assertSessionHas('error')
            ->assertSessionHasInput('title', 'Edition qui echoue');

        $record = $this->record('app', 'bulletin.store.failed');
        $this->assertNotNull($record);
        $this->assertSame('2026-11-02', $record['context']['week_start']);
        // Le contenu saisi n'est pas journalisé.
        $this->assertArrayNotHasKey('title', $record['context']);
    }

    public function test_toggle_publish_is_audited(): void
    {
        $bulletin = $this->bulletin(['is_published' => false]);

        $this->actingAs($this->user(PlatformRole::ADMIN))
            ->post(route('bulletin.toggle-publish', $bulletin))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue($bulletin->fresh()->is_published);
        $record = $this->record('audit', 'bulletin.published');
        $this->assertNotNull($record);
        $this->assertSame($bulletin->id, $record['context']['bulletin_id']);
    }

    public function test_toggle_publish_failure_is_logged(): void
    {
        $bulletin = $this->bulletin();
        BulletinEdition::updating(fn () => throw new RuntimeException('db down'));

        $this->actingAs($this->user(PlatformRole::ADMIN))
            ->post(route('bulletin.toggle-publish', $bulletin))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertTrue($bulletin->fresh()->is_published);
        $record = $this->record('app', 'bulletin.publish.failed');
        $this->assertNotNull($record);
        $this->assertSame($bulletin->id, $record['context']['bulletin_id']);
        $this->assertFalse($record['context']['target_published']);
    }

    public function test_member_cannot_manage_bulletins(): void
    {
        $this->actingAs($this->user(PlatformRole::MEMBRE))
            ->get(route('bulletin.index'))
            ->assertForbidden();
    }

    public function test_index_failure_redirects_to_dashboard_and_logs_filters(): void
    {
        BulletinEdition::retrieved(fn () => throw new RuntimeException('db down'));
        $this->bulletin();

        $this->actingAs($this->user(PlatformRole::ADMIN))
            ->get(route('bulletin.index', ['year' => now()->year, 'search' => 'Bulletin']))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error');

        $record = $this->record('app', 'bulletin.index.failed');
        $this->assertNotNull($record);
        $this->assertSame(['year' => (string) now()->year], $record['context']['filters']);
        $this->assertTrue($record['context']['has_search']);
    }

    public function test_current_bulletin_page_degrades_when_loading_fails(): void
    {
        $this->bulletin();
        BulletinEdition::retrieved(fn () => throw new RuntimeException('db down'));

        $this->actingAs($this->user(PlatformRole::MEMBRE))
            ->get(route('bulletin.current'))
            ->assertOk()
            ->assertSee('data-degraded-notice', false)
            ->assertDontSee('Bulletin test');

        $this->assertNotNull($this->record('app', 'bulletin.current.failed'));
    }

    // ---------------------------------------------------------------- Services

    public function test_birthday_service_degrades_to_empty_collection(): void
    {
        // Dates invalides : week_end non-Carbon fait échouer le calcul.
        $bulletin = new BulletinEdition();
        $bulletin->setRawAttributes(['id' => 42, 'week_start' => '2026-11-02', 'week_end' => 'pas-une-date']);

        $result = app(BirthdayService::class)->getWeekBirthdays($bulletin);

        $this->assertTrue($result->isEmpty());
        $record = $this->record('app', 'birthday.week.failed');
        $this->assertNotNull($record);
        $this->assertSame(42, $record['context']['bulletin_id']);
    }

    public function test_qr_code_failure_is_logged_without_payload(): void
    {
        QrCode::shouldReceive('size')->andThrow(new RuntimeException('gd missing'));
        $service = app(QRCodeService::class);

        $this->assertSame('', $service->generate('https://example.test/secret-link'));

        $response = $service->downloadPng('https://example.test/secret-link');
        $this->assertTrue($response->isRedirection());

        $records = array_filter($this->logs['app']->getRecords(), fn ($r) => $r->message === 'qrcode.generate.failed');
        $this->assertCount(2, $records);
        foreach ($records as $record) {
            $this->assertStringNotContainsString('secret-link', json_encode(array_diff_key($record->context, ['exception' => 1])));
            $this->assertSame(strlen('https://example.test/secret-link'), $record->context['payload_length']);
        }
    }

    // ---------------------------------------------------------------- Guide / What's new

    public function test_guide_degrades_when_config_is_invalid(): void
    {
        config(['guide.sections' => ['broken' => ['title' => 'Cassé', 'entries' => 'pas-un-tableau']]]);

        $this->actingAs($this->user(PlatformRole::MEMBRE))
            ->get(route('guide'))
            ->assertOk()
            ->assertSee('data-degraded-notice', false);

        $this->assertNotNull($this->record('app', 'guide.load.failed'));
    }

    public function test_whats_new_seen_failure_returns_json_error_and_logs(): void
    {
        $user = $this->user(PlatformRole::MEMBRE);
        User::saving(fn () => throw new RuntimeException('db down'));

        $this->actingAs($user)
            ->postJson(route('whats-new.seen'))
            ->assertStatus(500)
            ->assertJsonStructure(['message', 'status', 'request_id']);

        $this->assertNotNull($this->record('app', 'whats_new.seen.failed'));
    }
}
