<?php

namespace Tests\Feature;

use App\Enums\PlatformRole;
use App\Models\MenuSetting;
use App\Models\MenuWeek;
use App\Models\User;
use App\Models\WeekMenu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuWeekManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Chaque test construit son propre cycle avec des semaines completes.
        MenuWeek::query()->delete();
    }

    private function user(string $role): User
    {
        return User::create([
            'name' => 'Test ' . $role,
            'email' => $role . '-' . uniqid() . '@example.test',
            'password' => 'password',
            'roles' => [$role],
        ]);
    }

    private function admin(): User
    {
        return $this->user(PlatformRole::ADMIN);
    }

    private function member(): User
    {
        return $this->user(PlatformRole::MEMBRE);
    }

    private function week(string $name, bool $inCycle = true): MenuWeek
    {
        return MenuWeek::createWithSlots($name, null, $inCycle);
    }

    public function test_migration_moves_menus_to_week_entities(): void
    {
        $this->assertTrue(\Schema::hasTable('menu_weeks'));
        $this->assertTrue(\Schema::hasColumn('week_menus', 'week_id'));
        $this->assertTrue(\Schema::hasColumn('menu_settings', 'active_week_id'));
        $this->assertFalse(\Schema::hasColumn('week_menus', 'week_number'));
        $this->assertFalse(\Schema::hasColumn('menu_settings', 'active_week'));
    }

    public function test_creating_an_empty_week_adds_fourteen_hidden_slots(): void
    {
        $week = $this->week('Semaine test');

        $this->assertSame(14, $week->menus()->count());
        $this->assertSame(0, $week->menus()->where('available', true)->count());
    }

    public function test_cycle_page_lists_weeks_in_order(): void
    {
        $a = $this->week('Alpha');
        $b = $this->week('Beta');
        $this->week('Reserve', false);

        $this->actingAs($this->admin())
            ->get(route('week-menus.admin.weeks.index'))
            ->assertOk()
            ->assertSeeInOrder(['Alpha', 'Beta', 'En reserve', 'Reserve']);
    }

    public function test_members_cannot_manage_weeks(): void
    {
        $this->actingAs($this->member())
            ->get(route('week-menus.admin.weeks.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_a_week_from_a_copy(): void
    {
        $source = $this->week('Source');
        $dish = $source->menus()->first();
        $dish->update(['dish_name' => 'Riz sauce tomate', 'available' => true]);

        $response = $this->actingAs($this->admin())->post(route('week-menus.admin.weeks.store'), [
            'name' => 'Semaine copiee',
            'source_id' => $source->id,
        ]);

        $copy = MenuWeek::where('name', 'Semaine copiee')->firstOrFail();

        $response->assertRedirect(route('week-menus.admin.edit', ['week' => $copy->id]));
        $this->assertSame(14, $copy->menus()->count());
        $this->assertDatabaseHas('week_menus', [
            'week_id' => $copy->id,
            'day_id' => $dish->day_id,
            'period_id' => $dish->period_id,
            'dish_name' => 'Riz sauce tomate',
            'available' => 1,
        ]);
        $this->assertSame(MenuWeek::max('position'), $copy->position);
    }

    public function test_creating_a_week_requires_a_name(): void
    {
        $this->actingAs($this->admin())
            ->from(route('week-menus.admin.weeks.index'))
            ->post(route('week-menus.admin.weeks.store'), ['name' => ''])
            ->assertRedirect(route('week-menus.admin.weeks.index'))
            ->assertSessionHasErrors('name');
    }

    public function test_admin_can_duplicate_and_rename_a_week(): void
    {
        $week = $this->week('Originale');
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('week-menus.admin.weeks.duplicate', $week));
        $copy = MenuWeek::where('name', 'Copie de Originale')->firstOrFail();
        $this->assertSame(14, $copy->menus()->count());

        $this->actingAs($admin)
            ->patch(route('week-menus.admin.weeks.update', $copy), ['name' => 'Semaine fete'])
            ->assertRedirect(route('week-menus.admin.weeks.index'));

        $this->assertDatabaseHas('menu_weeks', ['id' => $copy->id, 'name' => 'Semaine fete']);
    }

    public function test_moving_a_week_changes_order_but_not_the_online_week(): void
    {
        $first = $this->week('Premiere');
        $second = $this->week('Deuxieme');
        $third = $this->week('Troisieme');
        MenuSetting::setActiveWeek($first);

        $this->actingAs($this->admin())
            ->patch(route('week-menus.admin.weeks.move', $third), ['direction' => 'up'])
            ->assertRedirect(route('week-menus.admin.weeks.index'));

        $this->assertSame(
            [$first->id, $third->id, $second->id],
            MenuWeek::ordered()->pluck('id')->all()
        );
        $this->assertSame([1, 2, 3], MenuWeek::ordered()->pluck('position')->all());
        $this->assertSame($first->id, MenuSetting::activeWeekId());

        // En tete : monter ne fait rien et le signale
        $this->actingAs($this->admin())
            ->patch(route('week-menus.admin.weeks.move', $first), ['direction' => 'up'])
            ->assertSessionHas('error');
    }

    public function test_a_week_can_leave_and_rejoin_the_cycle(): void
    {
        $first = $this->week('Premiere');
        $second = $this->week('Deuxieme');
        $third = $this->week('Troisieme');
        $admin = $this->admin();

        $this->actingAs($admin)->patch(route('week-menus.admin.weeks.update', $first), ['in_cycle' => 0]);

        $this->assertFalse($first->fresh()->in_cycle);
        $this->assertSame([$second->id, $third->id], MenuWeek::inCycle()->ordered()->pluck('id')->all());

        $this->actingAs($admin)->patch(route('week-menus.admin.weeks.update', $first), ['in_cycle' => 1]);

        // De retour en fin de rotation
        $this->assertSame([$second->id, $third->id, $first->id], MenuWeek::inCycle()->ordered()->pluck('id')->all());
    }

    public function test_the_last_week_cannot_be_deleted(): void
    {
        $only = $this->week('Seule');

        $this->actingAs($this->admin())
            ->delete(route('week-menus.admin.weeks.destroy', $only))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('menu_weeks', ['id' => $only->id]);
    }

    public function test_deleting_the_online_week_falls_back_to_the_first_of_the_cycle(): void
    {
        $first = $this->week('Premiere');
        $second = $this->week('Deuxieme');
        MenuSetting::setActiveWeek($second);

        $this->actingAs($this->admin())
            ->delete(route('week-menus.admin.weeks.destroy', $second))
            ->assertRedirect(route('week-menus.admin.weeks.index'));

        $this->assertDatabaseMissing('menu_weeks', ['id' => $second->id]);
        $this->assertSame(0, WeekMenu::where('week_id', $second->id)->count());
        $this->assertSame($first->id, MenuSetting::activeWeekId());
        $this->assertSame([1], MenuWeek::ordered()->pluck('position')->all());
    }

    public function test_editor_saves_only_the_targeted_week(): void
    {
        $first = $this->week('Premiere');
        $second = $this->week('Deuxieme');
        $mine = $second->menus()->first();
        $other = $first->menus()->first();

        $this->actingAs($this->admin())
            ->put(route('week-menus.admin.update-week', $second), [
                'menus' => [
                    $mine->id => ['dish_name' => 'Attieke poisson', 'available' => 1],
                    $other->id => ['dish_name' => 'Intrusion', 'available' => 1],
                ],
            ])
            ->assertRedirect(route('week-menus.admin.edit', ['week' => $second->id]));

        $this->assertSame('Attieke poisson', $mine->fresh()->dish_name);
        $this->assertNotSame('Intrusion', $other->fresh()->dish_name);
    }

    public function test_public_menu_shows_the_online_week(): void
    {
        $first = $this->week('Premiere');
        $second = $this->week('Semaine en ligne');
        $second->menus()->first()->update(['dish_name' => 'Fufu sauce claire', 'available' => true]);
        $first->menus()->first()->update(['dish_name' => 'Plat cache', 'available' => true]);

        $this->actingAs($this->admin())
            ->patch(route('week-menus.admin.active-week'), ['week' => $second->id, 'return' => 'weeks'])
            ->assertRedirect(route('week-menus.admin.weeks.index'));

        $this->actingAs($this->member())
            ->get(route('week-menus.index'))
            ->assertOk()
            ->assertSee('Semaine en ligne')
            ->assertSee('Fufu sauce claire')
            ->assertDontSee('Plat cache');
    }

    public function test_public_grid_keeps_every_day_column_when_a_dish_is_hidden(): void
    {
        $week = $this->week('Grille');
        $week->menus()->update(['available' => true]);
        $mardiMidi = $week->menus()->where('day_id', 2)->where('period_id', 1)->first();
        $mardiMidi->update(['dish_name' => 'Plat masque du mardi', 'available' => false]);
        $week->menus()->where('day_id', 2)->where('period_id', 2)->update(['dish_name' => 'Igname pilee']);
        MenuSetting::setActiveWeek($week);

        $response = $this->actingAs($this->member())->get(route('week-menus.index'));

        $response->assertOk()
            ->assertDontSee('Plat masque du mardi')
            ->assertSee('Pas de plat')
            ->assertSee('Igname pilee');

        // Les 7 jours restent en colonne dans l'ordre, meme avec un plat masque.
        $response->assertSeeInOrder(['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche']);
        $this->assertSame(2, substr_count($response->getContent(), 'Pas de plat'),
            'La case masquee apparait une fois dans le tableau et une fois dans les cartes');
    }

    public function test_public_page_shows_an_empty_state_when_nothing_is_visible(): void
    {
        $week = $this->week('Vide');
        MenuSetting::setActiveWeek($week);

        $this->actingAs($this->member())
            ->get(route('week-menus.index'))
            ->assertOk()
            ->assertSee('pas encore publie')
            ->assertDontSee('Modifier le menu');
    }

    public function test_dashboard_shows_today_dishes_or_an_empty_state(): void
    {
        $week = $this->week('Jour');
        MenuSetting::setActiveWeek($week);
        $member = $this->member();

        $this->actingAs($member)->get(route('dashboard'))->assertOk()->assertSee('Aucun plat n', false);

        $todayId = \App\Models\WeekMenuDay::where('code', strtolower(now()->locale('fr_FR')->dayName))->value('id');
        $week->menus()->where('day_id', $todayId)->update(['dish_name' => 'Riz gras du jour', 'available' => true]);

        $this->actingAs($member)->get(route('dashboard'))->assertOk()->assertSee('Riz gras du jour');
    }

    public function test_editor_redirects_to_cycle_page_when_no_week_exists(): void
    {
        $this->actingAs($this->admin())
            ->get(route('week-menus.admin.edit'))
            ->assertRedirect(route('week-menus.admin.weeks.index'));
    }
}
