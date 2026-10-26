<?php

namespace Tests\Feature;

use App\Enums\PlatformRole;
use App\Models\MenuSetting;
use App\Models\MenuWeek;
use App\Models\User;
use App\Services\MenuRotation;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuRotationTest extends TestCase
{
    use RefreshDatabase;

    private MenuWeek $a;
    private MenuWeek $b;
    private MenuWeek $c;

    protected function setUp(): void
    {
        parent::setUp();

        MenuWeek::query()->delete();
        Carbon::setTestNow('2026-10-28 10:00:00'); // mercredi

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

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => 'admin-' . uniqid() . '@example.test',
            'password' => 'password',
            'roles' => [PlatformRole::ADMIN],
        ]);
    }

    public function test_manual_mode_never_moves_with_time(): void
    {
        Carbon::setTestNow('2026-12-30 10:00:00');

        $this->assertSame($this->b->id, MenuSetting::activeWeekId());
        $this->assertFalse(MenuRotation::isEnabled());
    }

    public function test_enabling_keeps_the_current_week_and_rotates_each_monday(): void
    {
        $this->actingAs($this->admin())
            ->patch(route('week-menus.admin.rotation'), ['enabled' => 1])
            ->assertRedirect(route('week-menus.admin.weeks.index'));

        $this->assertTrue(MenuRotation::isEnabled());
        $this->assertSame($this->b->id, MenuSetting::activeWeekId(), 'La semaine en ligne ne change pas en activant');

        Carbon::setTestNow('2026-11-01 23:00:00'); // dimanche soir : toujours B
        $this->assertSame($this->b->id, MenuSetting::activeWeekId());

        Carbon::setTestNow('2026-11-02 00:30:00'); // lundi : C
        $this->assertSame($this->c->id, MenuSetting::activeWeekId());

        Carbon::setTestNow('2026-11-09 12:00:00'); // lundi suivant : retour a A
        $this->assertSame($this->a->id, MenuSetting::activeWeekId());

        Carbon::setTestNow('2026-11-16 12:00:00'); // B de nouveau
        $this->assertSame($this->b->id, MenuSetting::activeWeekId());
    }

    public function test_schedule_announces_next_switch_and_dates(): void
    {
        MenuRotation::enable();

        $next = MenuRotation::next();
        $this->assertSame($this->c->id, $next['week']->id);
        $this->assertSame('2026-11-02', $next['at']->toDateString());

        $schedule = MenuRotation::schedule()->keyBy(fn ($s) => $s['week']->id);
        $this->assertTrue($schedule[$this->b->id]['is_current']);
        $this->assertSame('2026-10-26', $schedule[$this->b->id]['start']->toDateString());
        $this->assertSame('2026-11-02', $schedule[$this->c->id]['start']->toDateString());
        $this->assertSame('2026-11-09', $schedule[$this->a->id]['start']->toDateString());
        $this->assertSame('26 oct.', MenuRotation::formatDate($schedule[$this->b->id]['start']));
    }

    public function test_reordering_in_auto_mode_changes_what_comes_next_only(): void
    {
        MenuRotation::enable(); // ancre B

        $this->actingAs($this->admin())
            ->patch(route('week-menus.admin.weeks.move', $this->a), ['direction' => 'down']); // ordre : B, A, C

        $this->assertSame($this->b->id, MenuSetting::activeWeekId());
        $this->assertSame($this->a->id, MenuRotation::next()['week']->id);

        Carbon::setTestNow('2026-11-02 12:00:00');
        $this->assertSame($this->a->id, MenuSetting::activeWeekId());
    }

    public function test_showing_a_cycle_week_in_auto_mode_reanchors_the_rotation(): void
    {
        MenuRotation::enable();

        $this->actingAs($this->admin())
            ->patch(route('week-menus.admin.active-week'), ['week' => $this->a->id]);

        $this->assertTrue(MenuRotation::isEnabled());
        $this->assertSame($this->a->id, MenuSetting::activeWeekId());

        Carbon::setTestNow('2026-11-02 12:00:00');
        $this->assertSame($this->b->id, MenuSetting::activeWeekId());
    }

    public function test_showing_a_reserve_week_in_auto_mode_switches_to_manual(): void
    {
        $reserve = MenuWeek::createWithSlots('Fete', null, false);
        MenuRotation::enable();

        $this->actingAs($this->admin())
            ->patch(route('week-menus.admin.active-week'), ['week' => $reserve->id]);

        $this->assertFalse(MenuRotation::isEnabled());
        $this->assertSame($reserve->id, MenuSetting::activeWeekId());

        Carbon::setTestNow('2026-12-01 12:00:00');
        $this->assertSame($reserve->id, MenuSetting::activeWeekId());
    }

    public function test_disabling_freezes_the_week_displayed_that_day(): void
    {
        MenuRotation::enable();
        Carbon::setTestNow('2026-11-04 12:00:00'); // C en ligne

        $this->actingAs($this->admin())
            ->patch(route('week-menus.admin.rotation'), ['enabled' => 0]);

        $this->assertFalse(MenuRotation::isEnabled());
        $this->assertSame($this->c->id, MenuSetting::activeWeekId());

        Carbon::setTestNow('2026-11-30 12:00:00');
        $this->assertSame($this->c->id, MenuSetting::activeWeekId());
    }

    public function test_losing_the_anchor_restarts_from_the_first_week_of_the_cycle(): void
    {
        MenuRotation::enable(); // ancre B

        $this->actingAs($this->admin())->delete(route('week-menus.admin.weeks.destroy', $this->b));

        $this->assertSame($this->a->id, MenuSetting::activeWeekId());

        Carbon::setTestNow('2026-11-02 12:00:00');
        $this->assertSame($this->c->id, MenuSetting::activeWeekId());
    }

    public function test_cycle_page_shows_rotation_state(): void
    {
        MenuRotation::enable();

        $this->actingAs($this->admin())
            ->get(route('week-menus.admin.weeks.index'))
            ->assertOk()
            ->assertSee('Rotation automatique')
            ->assertSee('Prochaine bascule')
            ->assertSee('2 nov.')
            ->assertSee('du 26 oct. au 1er nov.');
    }
}
