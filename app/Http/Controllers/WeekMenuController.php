<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateWeekMenuRequest;
use App\Models\WeekMenu;
use App\Models\WeekMenuDay;
use App\Models\WeekMenuPeriod;

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
    public function edit()
    {
        $menusPerPeriods = WeekMenu::with(['day', 'period'])
            ->orderedPeriod()
            ->get()
            ->groupBy(fn ($menu) => $menu->period->code);

        $menusPerDays = WeekMenu::with(['day', 'period'])
            ->ordered()
            ->get()
            ->groupBy(fn ($menu) => $menu->day->code);

        return view('week-menus.edit', [
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
            ->orderedPeriod()
            ->get()
            ->groupBy(fn ($menu) => $menu->period->code);

        $menusPerDays = WeekMenu::with(['day', 'period'])
            ->available()
            ->ordered()
            ->get()
            ->groupBy(fn ($menu) => $menu->day->code);

        return compact('menusPerDays', 'menusPerPeriods');
    }
}
