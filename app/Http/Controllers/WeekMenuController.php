<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateWeekMenuRequest;
use App\Http\Requests\UpdateWeekMenusRequest;
use App\Models\MenuSetting;
use App\Models\WeekMenu;
use App\Models\WeekMenuDay;
use App\Models\WeekMenuPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WeekMenuController extends Controller
{
    /**
     * Display the full weekly menu (public view).
     * Shows all available menus ordered by day and period.
     */
    public function index()
    {
        $grouped = $this->getMenusGrouped();

        return view('week-menus.index', [
            'menusPerDays' => $grouped['menusPerDays'],
            'menusPerPeriods' => $grouped['menusPerPeriods'],
        ]);
    }

    /**
     * Display the admin form to edit the weekly menu.
     * Shows ALL menus (including unavailable) so admins can toggle them.
     */
    public function edit(Request $request)
    {
        $activeWeek = MenuSetting::activeWeek();
        $week = (int) $request->query('week', $activeWeek);
        if (!in_array($week, MenuSetting::WEEKS, true)) {
            $week = $activeWeek;
        }

        $menusPerPeriods = WeekMenu::with(['day', 'period'])
            ->forWeek($week)
            ->orderedPeriod()
            ->get()
            ->groupBy(fn ($menu) => $menu->period->code);

        $menusPerDays = WeekMenu::with(['day', 'period'])
            ->forWeek($week)
            ->ordered()
            ->get()
            ->groupBy(fn ($menu) => $menu->day->code);

        return view('week-menus.edit', [
            'week' => $week,
            'activeWeek' => $activeWeek,
            'weeks' => MenuSetting::WEEKS,
            'menusPerDays' => $menusPerDays,
            'menusPerPeriods' => $menusPerPeriods,
        ]);
    }

    /**
     * Update a menu item.
     */
    public function update(UpdateWeekMenuRequest $request, WeekMenu $weekMenu)
    {
        $weekMenu->update($request->validated());

        return back()->with('success', "Le menu {$weekMenu->dish_name} a ete mis a jour.");
    }

    /**
     * Save every dish of a week at once.
     *
     * Une seule soumission pour les 14 plats : l'admin modifie ce qu'il veut
     * puis enregistre, au lieu d'un formulaire par plat.
     */
    public function updateWeek(UpdateWeekMenusRequest $request, int $week)
    {
        if (!in_array($week, MenuSetting::WEEKS, true)) {
            abort(404);
        }

        $payload = $request->validated()['menus'];

        // Seuls les plats de la semaine visee sont modifiables.
        $menus = WeekMenu::forWeek($week)
            ->whereIn('id', array_keys($payload))
            ->get();

        $changed = 0;

        DB::transaction(function () use ($menus, $payload, &$changed) {
            foreach ($menus as $menu) {
                $menu->fill([
                    'dish_name' => $payload[$menu->id]['dish_name'],
                    'available' => (bool) ($payload[$menu->id]['available'] ?? false),
                ]);

                if ($menu->isDirty()) {
                    $menu->save();
                    $changed++;
                }
            }
        });

        $message = match (true) {
            $changed === 0 => 'Aucune modification a enregistrer.',
            $changed === 1 => 'Le menu de la semaine ' . $week . ' a ete mis a jour (1 plat).',
            default => 'Le menu de la semaine ' . $week . " a ete mis a jour ({$changed} plats).",
        };

        return redirect()
            ->route('week-menus.admin.edit', ['week' => $week])
            ->with('success', $message);
    }

    /**
     * Choose which week of the 4-week cycle is displayed.
     */
    public function setActiveWeek(Request $request)
    {
        $validated = $request->validate([
            'week' => ['required', 'integer', 'in:' . implode(',', MenuSetting::WEEKS)],
        ]);

        MenuSetting::setActiveWeek((int) $validated['week']);

        return redirect()
            ->route('week-menus.admin.edit', ['week' => $validated['week']])
            ->with('success', "La semaine {$validated['week']} est maintenant affichee.");
    }

    /**
     * Enable or disable a menu item.
     */
    public function toggle(WeekMenu $weekMenu)
    {
        $weekMenu->update([
            'available' => !$weekMenu->available,
        ]);

        $state = $weekMenu->available ? 'visible' : 'masque';

        return back()->with('success', "Le menu {$weekMenu->dish_name} est maintenant {$state}.");
    }

    /**
     * Get menus grouped by day and by period.
     */
    private function getMenusGrouped(): array
    {
        $menusPerPeriods = WeekMenu::with(['day', 'period'])
            ->available()
            ->forActiveWeek()
            ->orderedPeriod()
            ->get()
            ->groupBy(fn ($menu) => $menu->period->code);

        $menusPerDays = WeekMenu::with(['day', 'period'])
            ->available()
            ->forActiveWeek()
            ->ordered()
            ->get()
            ->groupBy(fn ($menu) => $menu->day->code);

        return compact('menusPerDays', 'menusPerPeriods');
    }
}







