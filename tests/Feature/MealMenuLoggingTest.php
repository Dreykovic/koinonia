<?php

namespace Tests\Feature;

use App\Enums\PlatformRole;
use App\Mail\MonitoringAlert;
use App\Models\MealInscription;
use App\Models\MenuSetting;
use App\Models\MenuWeek;
use App\Models\SundayMeal;
use App\Models\User;
use App\Services\MenuRotation;
use App\Services\SundayMealService;
use Carbon\Carbon;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Mail;
use Monolog\Handler\TestHandler;
use RuntimeException;
use Tests\TestCase;

/**
 * Issue #25 : gestion d'erreur et logs des modules Repas et Menus.
 */
class MealMenuLoggingTest extends TestCase
{
    use RefreshDatabase;

    private TestHandler $appLog;

    private TestHandler $auditLog;

    private MenuWeek $a;

    private MenuWeek $b;

    private MenuWeek $c;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-28 10:00:00'); // mercredi
        Mail::fake();
        config(['monitoring.alerts.mail_to' => 'ops@example.test']);

        $this->appLog = new TestHandler();
        $this->auditLog = new TestHandler();
        $this->app['log']->channel('app')->getLogger()->pushHandler($this->appLog);
        $this->app['log']->channel('audit')->getLogger()->pushHandler($this->auditLog);

        MenuWeek::query()->delete();
        $this->a = MenuWeek::createWithSlots('A');
        $this->b = MenuWeek::createWithSlots('B');
        $this->c = MenuWeek::createWithSlots('C');
        MenuSetting::setActiveWeek($this->b);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function user(array $roles = [PlatformRole::MEMBRE]): User
    {
        return User::create([
            'name' => 'Test',
            'email' => 'user-'.uniqid().'@example.test',
            'password' => 'password',
            'roles' => $roles,
        ]);
    }

    private function record(TestHandler $handler, string $message): ?object
    {
        return collect($handler->getRecords())->firstWhere('message', $message);
    }

    private function openMeal(): SundayMeal
    {
        return SundayMeal::create([
            'meal_date' => '2026-11-01',
            'max_capacity' => 10,
            'is_open' => true,
            'allow_multiple_places' => true,
            'max_places_per_user' => 3,
            'allow_guests' => true,
            'max_guests_per_user' => 3,
        ]);
    }

    // ---------------------------------------------------------------- Rotation

    public function test_rotation_failure_is_logged_alerted_and_falls_back_to_the_saved_week(): void
    {
        MenuRotation::enable(); // ancre B

        // Ancre perdue (B mise en reserve hors interface) puis impossible de re-ancrer
        // (base en ecriture indisponible).
        MenuWeek::query()->whereKey($this->b->id)->update(['in_cycle' => false]);
        MenuSetting::saving(fn () => throw new RuntimeException('base en lecture seule'));

        $this->assertSame($this->b->id, MenuSetting::activeWeekId(), 'La semaine enregistree reste affichee');

        $lost = $this->record($this->appLog, 'menu.rotation.anchor_lost');
        $this->assertSame('Warning', $lost->level->name);
        $this->assertSame($this->b->id, $lost->context['anchor_week_id']);

        $failed = $this->record($this->appLog, 'menu.rotation.failed');
        $this->assertSame('Error', $failed->level->name);
        $this->assertSame('current_index', $failed->context['step']);
        $this->assertSame(RuntimeException::class, $failed->context['exception_class']);

        $this->assertNotNull($this->record($this->appLog, 'alert.menu_rotation_failure'));
        Mail::assertSent(MonitoringAlert::class, fn ($mail) => $mail->rule === 'menu_rotation_failure');

        // Les pages restent servies, sans nouvel e-mail dans la fenetre.
        $this->actingAs($this->user())->get(route('week-menus.index'))->assertOk()->assertSee('B');
        $this->assertSame([], MenuRotation::schedule()->all());
        $this->assertNull(MenuRotation::next());
        Mail::assertSent(MonitoringAlert::class, 1);
    }

    public function test_rotation_toggle_failure_redirects_with_an_error_and_is_logged(): void
    {
        MenuSetting::saving(fn () => throw new RuntimeException('base indisponible'));

        $this->actingAs($this->user([PlatformRole::ADMIN]))
            ->from(route('week-menus.admin.weeks.index'))
            ->patch(route('week-menus.admin.rotation'), ['enabled' => 1])
            ->assertRedirect(route('week-menus.admin.weeks.index'))
            ->assertSessionHas('error', fn ($message) => str_starts_with($message, 'Le mode de rotation n\'a pas pu etre modifie.'));

        $record = $this->record($this->appLog, 'menu.rotation.toggle.failed');
        $this->assertSame('Error', $record->level->name);
        $this->assertTrue($record->context['enabled']);
        $this->assertSame('base indisponible', $record->context['error']);
        $this->assertNull($this->record($this->auditLog, 'menu.rotation.enabled'));
    }

    public function test_rotation_toggle_success_is_audited(): void
    {
        $this->actingAs($this->user([PlatformRole::ADMIN]))
            ->patch(route('week-menus.admin.rotation'), ['enabled' => 1])
            ->assertSessionHas('success');

        $record = $this->record($this->auditLog, 'menu.rotation.enabled');
        $this->assertSame($this->b->id, $record->context['anchor_week_id']);
    }

    public function test_anchor_in_the_future_is_logged_once_as_warning(): void
    {
        MenuRotation::enable();
        MenuSetting::query()->update(['anchor_week_start' => '2026-12-07']);

        MenuSetting::activeWeekId();
        MenuSetting::activeWeekId();

        $warnings = collect($this->appLog->getRecords())->where('message', 'menu.rotation.anchor_in_future');
        $this->assertCount(1, $warnings);
        $this->assertSame('2026-12-07', $warnings->first()->context['anchor_week_start']);
    }

    public function test_empty_cycle_in_auto_mode_is_logged_and_keeps_the_saved_week(): void
    {
        MenuRotation::enable();
        MenuWeek::query()->update(['in_cycle' => false]);

        $this->assertSame($this->b->id, MenuSetting::activeWeekId());
        $this->assertSame('Warning', $this->record($this->appLog, 'menu.rotation.empty_cycle')->level->name);
    }

    public function test_menu_page_failure_returns_500_and_logs_the_route_event(): void
    {
        MenuWeek::retrieved(fn () => throw new RuntimeException('table illisible'));

        $this->actingAs($this->user())->get(route('week-menus.index'))->assertStatus(500);

        $record = $this->record($this->appLog, 'menu.page.index.failed');
        $this->assertSame('Error', $record->level->name);
        $this->assertSame('table illisible', $record->context['error']);
    }

    public function test_missing_requested_week_is_logged_and_falls_back(): void
    {
        $this->actingAs($this->user([PlatformRole::ADMIN]))
            ->get(route('week-menus.admin.edit', ['week' => 999999]))
            ->assertOk();

        $this->assertSame(999999, $this->record($this->appLog, 'menu.week.requested_missing')->context['requested_week_id']);
    }

    public function test_reorder_mismatch_is_logged_as_inconsistent_cycle(): void
    {
        $this->actingAs($this->user([PlatformRole::ADMIN]))
            ->patch(route('week-menus.admin.weeks.reorder'), ['order' => [$this->c->id, $this->a->id]])
            ->assertSessionHas('error');

        $record = $this->record($this->appLog, 'menu.cycle.reorder_mismatch');
        $this->assertSame('Warning', $record->level->name);
        $this->assertSame([$this->a->id, $this->b->id, $this->c->id], $record->context['cycle']);
    }

    // ---------------------------------------------------------------- Repas

    public function test_meal_generation_failure_is_logged_per_date_and_alerted(): void
    {
        SundayMeal::creating(fn () => throw new RuntimeException('insert refuse'));

        $this->assertSame(0, app(SundayMealService::class)->generateUpcomingSundays(2));

        $failures = collect($this->appLog->getRecords())->where('message', 'meal.generate.failed');
        $this->assertCount(2, $failures);
        $this->assertSame('2026-11-01', $failures->first()->context['date']);
        $this->assertSame(2, $this->record($this->appLog, 'meal.generate.incomplete')->context['failed']);
        Mail::assertSent(MonitoringAlert::class, fn ($mail) => $mail->rule === 'meal_generation_failure');
    }

    public function test_closing_without_a_meal_tomorrow_is_a_warning(): void
    {
        Carbon::setTestNow('2026-10-31 18:00:00'); // samedi

        $this->assertSame(0, app(SundayMealService::class)->closeMealsForSaturday());

        $this->assertSame('2026-11-01', $this->record($this->appLog, 'meal.close.missing')->context['date']);
    }

    public function test_meal_store_failure_redirects_back_with_an_error(): void
    {
        SundayMeal::creating(fn () => throw new RuntimeException('insert refuse'));

        $this->actingAs($this->user([PlatformRole::ADMIN]))
            ->from(route('meals.admin.create'))
            ->post(route('meals.admin.store'), [
                'meal_date' => '2026-11-08',
                'max_capacity' => 40,
                'registration_deadline' => '2026-11-07 18:00',
                'cancellation_deadline' => '2026-11-07 18:00',
                'max_places_per_user' => 1,
                'max_guests_per_user' => 0,
            ])
            ->assertRedirect(route('meals.admin.create'))
            ->assertSessionHas('error');

        $record = $this->record($this->appLog, 'meal.store.failed');
        $this->assertSame('2026-11-08', $record->context['meal_date']);
        $this->assertSame(0, SundayMeal::count());
    }

    public function test_inscription_is_logged_without_guest_names(): void
    {
        $meal = $this->openMeal();

        $this->actingAs($this->user())
            ->post(route('meals.register', $meal), [
                'places_count' => 1,
                'guests_count' => 1,
                'guest_names' => ['Marie Invitee'],
            ])
            ->assertSessionHas('success');

        $record = $this->record($this->appLog, 'meal.inscription.created');
        $this->assertSame($meal->id, $record->context['meal_id']);
        $this->assertSame(1, $record->context['guests_count']);
        $this->assertStringNotContainsString('Marie', json_encode($record->context));
    }

    public function test_broadcast_failure_does_not_cancel_the_inscription(): void
    {
        Broadcast::extend('failing', fn () => new class extends Broadcaster
        {
            public function auth($request)
            {
            }

            public function validAuthenticationResponse($request, $result)
            {
            }

            public function broadcast(array $channels, $event, array $payload = [])
            {
                throw new BroadcastException('Pusher injoignable');
            }
        });
        config([
            'broadcasting.default' => 'failing',
            'broadcasting.connections.failing' => ['driver' => 'failing'],
        ]);

        $meal = $this->openMeal();
        $user = $this->user();

        $this->actingAs($user)
            ->post(route('meals.register', $meal))
            ->assertSessionHas('success');

        $this->assertTrue(MealInscription::where('sunday_meal_id', $meal->id)->where('user_id', $user->id)->exists());

        $record = $this->record($this->appLog, 'broadcast.failed');
        $this->assertSame($meal->id, $record->context['meal_id']);
        $this->assertSame('inscribed', $record->context['action']);
    }

    public function test_full_meal_refusal_is_logged_with_reason(): void
    {
        $meal = $this->openMeal();
        $meal->update(['max_capacity' => 1]);
        MealInscription::create([
            'sunday_meal_id' => $meal->id,
            'user_id' => $this->user()->id,
            'places_count' => 1,
            'guests_count' => 0,
            'registered_at' => now(),
        ]);

        $this->actingAs($this->user())->post(route('meals.register', $meal))->assertSessionHas('error');

        $record = $this->record($this->appLog, 'meal.inscription.refused');
        $this->assertNotNull($record);
        $this->assertContains($record->context['reason'], ['closed', 'not_enough_places']);
    }
}
