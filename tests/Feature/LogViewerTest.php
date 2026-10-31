<?php

namespace Tests\Feature;

use App\Enums\PlatformRole;
use App\Models\User;
use App\Services\LogReader;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Monolog\Handler\TestHandler;
use Tests\TestCase;

class LogViewerTest extends TestCase
{
    use RefreshDatabase;

    private string $dir;

    private string $outside;

    private TestHandler $auditLog;

    private const RANGE = ['from' => '2026-10-29T00:00', 'to' => '2026-10-30T23:59'];

    protected function setUp(): void
    {
        parent::setUp();

        // Répertoire de logs temporaire, injecté dans le service.
        $this->dir = sys_get_temp_dir().'/koinonia-logs-'.uniqid();
        $this->outside = sys_get_temp_dir().'/koinonia-outside-'.uniqid();
        mkdir($this->dir);
        mkdir($this->outside);
        $this->app->instance(LogReader::class, new LogReader($this->dir));

        $this->auditLog = new TestHandler();
        $this->app['log']->channel('audit')->getLogger()->pushHandler($this->auditLog);

        $this->writeFixtures();
    }

    protected function tearDown(): void
    {
        foreach ([$this->dir, $this->outside] as $dir) {
            foreach (scandir($dir) as $name) {
                if (! in_array($name, ['.', '..'], true)) {
                    unlink($dir.'/'.$name);
                }
            }
            rmdir($dir);
        }

        parent::tearDown();
    }

    private function writeFixtures(): void
    {
        // Format ligne (LineFormatter de Laravel), avec une stack trace multi-lignes.
        file_put_contents($this->dir.'/app-2026-10-30.log', <<<'LOG'
[2026-10-30 10:00:00] testing.INFO: reservation.created {"request_id":"aaaaaaaa-1111-2222-3333-444444444444","route":"reservations.store","user_id":900007,"reservation_id":12}
[2026-10-30 10:00:05] testing.ERROR: reservation.store.failed {"request_id":"bbbbbbbb-1111-2222-3333-444444444444","route":"reservations.store","user_id":900008,"vehicle_id":3,"exception":"[object] (RuntimeException(code: 0): SQLSTATE boom at /app/Http/Controllers/VehicleReservationController.php:120)
[stacktrace]
#0 /app/vendor/laravel/framework/src/Illuminate/Routing/Controller.php(54): store()
#1 /app/public/index.php(52): handle()
#2 {main}
"}
[2026-10-30 10:00:06] testing.WARNING: Message libre avec {accolades} [crochets]
[2026-10-30 11:30:00] testing.DEBUG: menu.rotation.checked {"route":"week-menus.index","user_id":900009}

LOG);

        // Format JSON (LOG_FORMATTER=Monolog\Formatter\JsonFormatter).
        $json = fn (string $time, string $level, string $message, array $context) => json_encode([
            'message' => $message,
            'context' => $context,
            'level' => 200,
            'level_name' => strtoupper($level),
            'channel' => 'testing',
            'datetime' => Carbon::parse('2026-10-30 '.$time)->format('Y-m-d\TH:i:s.uP'),
            'extra' => [],
        ], JSON_UNESCAPED_SLASHES)."\n";

        file_put_contents(
            $this->dir.'/audit-2026-10-30.log',
            $json('10:00:03', 'info', 'reservation.approved', ['request_id' => 'bbbbbbbb-1111-2222-3333-444444444444', 'user_id' => 900008, 'route' => 'reservations.approve'])
            .$json('10:00:07', 'critical', 'alert.error_spike', ['rule' => 'error_spike', 'exception' => [
                'class' => 'LogicException', 'message' => 'json boom', 'code' => 0, 'file' => '/app/X.php:3',
                'trace' => ['/app/Y.php:10', '/app/Z.php:20'],
            ]])
        );

        file_put_contents($this->dir.'/security-2026-10-29.log',
            "[2026-10-29 09:00:00] testing.WARNING: auth.login.failed {\"reason\":\"invalid_credentials\",\"email\":\"j***@example.com\"} \n");

        // Fichiers hors liste blanche : jamais lus.
        file_put_contents($this->dir.'/schedule-abc.log', "[2026-10-30 10:00:00] testing.ERROR: hidden.schedule \n");
        file_put_contents($this->outside.'/secret.log', "[2026-10-30 10:00:00] testing.ERROR: outside.secret \n");
        symlink($this->outside.'/secret.log', $this->dir.'/app-2026-10-28.log');
    }

    private function user(string $role, array $attributes = []): User
    {
        return User::create($attributes + [
            'name' => 'Test '.$role.' '.uniqid(),
            'email' => $role.'-'.uniqid().'@example.test',
            'password' => 'password',
            'roles' => [$role],
            'is_active' => true,
        ]);
    }

    private function admin(): User
    {
        return $this->user(PlatformRole::ADMIN);
    }

    private function messages($response): array
    {
        return array_map(fn ($e) => $e['message'], $response->viewData('entries')->items());
    }

    public function test_guest_is_redirected_and_non_admins_are_refused(): void
    {
        $this->get(route('admin.logs.index'))->assertRedirect(route('login'));

        foreach ([PlatformRole::MEMBRE, PlatformRole::COMMUNICATION] as $role) {
            $user = $this->user($role);
            $this->actingAs($user)->get(route('admin.logs.index'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.logs.show', ['channel' => 'app', 'date' => '2026-10-30', 'offset' => 0]))->assertForbidden();
            $this->actingAs($user)->get(route('admin.logs.request', ['requestId' => 'bbbbbbbb']))->assertForbidden();
        }
    }

    public function test_admin_sees_entries_newest_first_across_channels_and_formats(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.logs.index', self::RANGE));

        $response->assertOk()->assertSee("Journaux de l'application", false);
        $this->assertSame([
            'menu.rotation.checked',
            'alert.error_spike',
            'Message libre avec {accolades} [crochets]',
            'reservation.store.failed',
            'reservation.approved',
            'reservation.created',
            'auth.login.failed',
        ], $this->messages($response));
        $response->assertDontSee('hidden.schedule')->assertDontSee('outside.secret');
    }

    public function test_line_format_is_parsed_with_multiline_stack_trace(): void
    {
        $reader = new LogReader($this->dir);
        $entries = $reader->search(['channel' => 'app', 'level' => 'error'])['entries'];

        $this->assertCount(1, $entries);
        $entry = $entries[0];
        $this->assertSame('line', $entry['format']);
        $this->assertSame('error', $entry['level']);
        $this->assertSame('reservation.store.failed', $entry['message']);
        $this->assertSame('2026-10-30 10:00:05', $entry['datetime']->format('Y-m-d H:i:s'));
        $this->assertSame(3, $entry['context']['vehicle_id']);
        $this->assertSame('900008', $entry['user_id']);
        $this->assertSame('bbbbbbbb-1111-2222-3333-444444444444', $entry['request_id']);
        $this->assertStringContainsString('RuntimeException(code: 0): SQLSTATE boom', $entry['exception']);
        $this->assertSame(3, count(explode("\n", $entry['trace'])));
        $this->assertStringStartsWith('#0 /app/vendor', $entry['trace']);

        // Message libre contenant accolades et crochets, sans contexte.
        $free = $reader->search(['channel' => 'app', 'q' => 'accolades'])['entries'][0];
        $this->assertSame('Message libre avec {accolades} [crochets]', $free['message']);
        $this->assertSame([], $free['context']);
    }

    public function test_json_format_is_parsed(): void
    {
        $entries = (new LogReader($this->dir))->search(['channel' => 'audit'])['entries'];

        $this->assertSame(['alert.error_spike', 'reservation.approved'], array_column($entries, 'message'));
        $this->assertSame('json', $entries[0]['format']);
        $this->assertSame('critical', $entries[0]['level']);
        $this->assertSame('error_spike', $entries[0]['context']['rule']);
        $this->assertStringContainsString('LogicException: json boom', $entries[0]['exception']);
        $this->assertSame("#0 /app/Y.php:10\n#1 /app/Z.php:20", $entries[0]['trace']);
        $this->assertSame('reservations.approve', $entries[1]['route']);
    }

    public function test_filters_by_level_channel_and_date(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->get(route('admin.logs.index', self::RANGE + ['level' => 'error']));
        $this->assertSame(['alert.error_spike', 'reservation.store.failed'], $this->messages($response));

        $response = $this->actingAs($admin)->get(route('admin.logs.index', self::RANGE + ['channel' => 'security']));
        $this->assertSame(['auth.login.failed'], $this->messages($response));

        $response = $this->actingAs($admin)->get(route('admin.logs.index', ['from' => '2026-10-30T11:00', 'to' => '2026-10-30T11:59']));
        $this->assertSame(['menu.rotation.checked'], $this->messages($response));

        $response = $this->actingAs($admin)->get(route('admin.logs.index', ['from' => '2026-10-29T00:00', 'to' => '2026-10-29T23:59']));
        $this->assertSame(['auth.login.failed'], $this->messages($response));
    }

    public function test_filters_by_user_route_text_and_request_id(): void
    {
        $admin = $this->admin();
        $target = $this->user(PlatformRole::MEMBRE, ['name' => 'Jean Dupont', 'email' => 'jean.dupont@example.test']);
        $id = $target->id;

        // Les fixtures référencent des ids fictifs : on remplace 900008 par l'id réel.
        foreach (['app-2026-10-30.log', 'audit-2026-10-30.log'] as $file) {
            $path = $this->dir.'/'.$file;
            file_put_contents($path, str_replace('"user_id":900008', '"user_id":'.$id, file_get_contents($path)));
        }

        foreach ([(string) $id, 'jean.dupont@example.test', 'Dupont'] as $user) {
            $response = $this->actingAs($admin)->get(route('admin.logs.index', self::RANGE + ['user' => $user]));
            $this->assertSame(['reservation.store.failed', 'reservation.approved'], $this->messages($response), $user);
        }
        $response->assertSee('Jean Dupont');

        $response = $this->actingAs($admin)->get(route('admin.logs.index', self::RANGE + ['user' => 'personne-inconnue']));
        $this->assertSame([], $this->messages($response));
        $response->assertSee('Aucun utilisateur ne correspond');

        $response = $this->actingAs($admin)->get(route('admin.logs.index', self::RANGE + ['route' => 'week-menus']));
        $this->assertSame(['menu.rotation.checked'], $this->messages($response));

        $response = $this->actingAs($admin)->get(route('admin.logs.index', self::RANGE + ['q' => 'SQLSTATE']));
        $this->assertSame(['reservation.store.failed'], $this->messages($response));

        // request_id complet ou préfixe de 8 caractères, sans plage de dates.
        foreach (['bbbbbbbb-1111-2222-3333-444444444444', 'bbbbbbbb'] as $requestId) {
            $response = $this->actingAs($admin)->get(route('admin.logs.index', ['request_id' => $requestId]));
            $this->assertSame(['reservation.store.failed', 'reservation.approved'], $this->messages($response));
        }
    }

    public function test_request_timeline_shows_all_lines_in_chronological_order(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('admin.logs.request', ['requestId' => 'bbbbbbbb-1111-2222-3333-444444444444', 'date' => '2026-10-30']));

        $response->assertOk();
        $entries = $response->viewData('entries');
        $this->assertSame(['reservation.approved', 'reservation.store.failed'], array_column($entries, 'message'));
        $this->assertSame(['audit', 'app'], array_column($entries, 'channel'));

        $this->assertTrue($this->auditLog->hasInfoThatContains('admin.logs.request_viewed'));
    }

    public function test_entry_detail_shows_context_and_collapsible_stack_trace(): void
    {
        $admin = $this->admin();
        $entry = (new LogReader($this->dir))->search(['channel' => 'app', 'level' => 'error'])['entries'][0];

        $this->actingAs($admin)
            ->get(route('admin.logs.show', ['channel' => 'app', 'date' => '2026-10-30', 'offset' => $entry['offset']]))
            ->assertOk()
            ->assertSee('reservation.store.failed')
            ->assertSee('&quot;vehicle_id&quot;: 3', false)
            ->assertSee('<details class="log-trace', false)
            ->assertSee('#1 /app/public/index.php(52): handle()')
            ->assertSee(route('admin.logs.request', ['requestId' => 'bbbbbbbb-1111-2222-3333-444444444444', 'date' => '2026-10-30']), false);

        // Offset au milieu d'une ligne : rien n'est affiché.
        $this->actingAs($admin)
            ->get(route('admin.logs.show', ['channel' => 'app', 'date' => '2026-10-30', 'offset' => $entry['offset'] + 3]))
            ->assertNotFound();
    }

    public function test_path_traversal_is_rejected(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.logs.index', ['channel' => '../../.env']))
            ->assertRedirect(route('admin.logs.index'))
            ->assertSessionHasErrors('channel');

        $this->actingAs($admin)
            ->get(route('admin.logs.show', ['channel' => '../../.env', 'offset' => 0]))
            ->assertSessionHasErrors('channel');

        $this->actingAs($admin)
            ->get(route('admin.logs.show', ['channel' => 'app', 'date' => '../../../etc/passwd', 'offset' => 0]))
            ->assertSessionHasErrors('date');

        // Lien symbolique vers l'extérieur du répertoire : ignoré.
        $this->actingAs($admin)
            ->get(route('admin.logs.show', ['channel' => 'app', 'date' => '2026-10-28', 'offset' => 0]))
            ->assertNotFound();

        $this->get('/admin/logs/request/..%2F..%2Fsecret')->assertNotFound();

        $reader = new LogReader($this->dir);
        $this->assertNull($reader->file('app', '2026-10-28'));

        $this->expectException(InvalidArgumentException::class);
        $reader->file('../outside', null);
    }

    public function test_access_is_audited_with_filters(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.logs.index', self::RANGE + ['level' => 'warning', 'user' => 'jean.dupont@example.test']))
            ->assertOk();

        $record = collect($this->auditLog->getRecords())->first(fn ($r) => $r->message === 'admin.logs.viewed');

        $this->assertNotNull($record);
        $this->assertSame('warning', $record->context['filters']['level']);
        $this->assertSame('j***@example.test', $record->context['filters']['user']);
        $this->assertSame('2026-10-29T00:00', $record->context['filters']['from']);
        $this->assertSame(1, $record->context['page']);
    }

    public function test_pagination_reads_newest_first_with_bounded_pages(): void
    {
        $lines = '';
        for ($i = 0; $i < 120; $i++) {
            $lines .= sprintf("[2026-10-30 %02d:%02d:00] testing.INFO: bulk.event.%d {\"n\":%d} \n", 12 + intdiv($i, 60), $i % 60, $i, $i);
        }
        file_put_contents($this->dir.'/queue-2026-10-30.log', $lines);

        $reader = new LogReader($this->dir);

        $page1 = $reader->search(['channel' => 'queue'], 1, 50);
        $this->assertSame(120, $page1['total']);
        $this->assertCount(50, $page1['entries']);
        $this->assertSame('bulk.event.119', $page1['entries'][0]['message']);

        $page3 = $reader->search(['channel' => 'queue'], 3, 50);
        $this->assertCount(20, $page3['entries']);
        $this->assertSame('bulk.event.19', $page3['entries'][0]['message']);
        $this->assertSame('bulk.event.0', $page3['entries'][19]['message']);

        $this->actingAs($this->admin())
            ->get(route('admin.logs.index', self::RANGE + ['channel' => 'queue', 'page' => 2]))
            ->assertOk()
            ->assertSee('bulk.event.69')
            ->assertDontSee('bulk.event.70<', false);
    }
}
