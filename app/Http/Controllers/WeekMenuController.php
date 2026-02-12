<?php

namespace App\Http\Controllers;

use App\Models\WeekMenu;
use App\Models\WeekMenuDay;
use App\Models\WeekMenuPeriod;
use Illuminate\Http\Request;

class WeekMenuController extends Controller
{
    /**
     * Display the full weekly menu (public view).
     * Shows all available menus ordered by day and period.
     */
    public function index()
    {
        $menus = WeekMenu::with(['day', 'period'])
            ->available();

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
        // dd($menus);
        return view('week-menus.index', compact('menus','menusPerDays','menusPerPeriods'));
    }

    /**
     * Display the admin form to edit the weekly menu.
     */
    public function edit()
    {
        // Days ordered for display (Monday → Sunday)
        $days = WeekMenuDay::orderBy('position')->get();

        // Periods ordered for display (Lunch → Dinner)
        $periods = WeekMenuPeriod::orderBy('position')->get();

        // Existing menus indexed by day_id + period_id
        $menus = WeekMenu::with(['day', 'period'])
            ->get()
            ->keyBy(fn ($menu) => $menu->day_id . '_' . $menu->period_id);


        $menus = WeekMenu::with(['day', 'period'])
            ->available();

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
        // dd($menus);
        return view('week-menus.edit', compact('menus','menusPerDays','menusPerPeriods'));

    }

    /**
     * Create or update the weekly menu (UPSERT logic).
     * One menu per day and period.
     */
    public function update(Request $request,WeekMenu $weekMenu)
    {
        $validated =$request->validate([
        // 'day_id'     => ['sometimes', 'integer', 'exists:days,id'],
        // 'period_id'  => ['sometimes', 'integer', 'exists:periods,id'],
        'dish_name'  => ['sometimes', 'string', 'max:255'],
        // 'description'=> ['sometimes', 'nullable', 'string', 'max:1000'],
        // 'price'      => ['sometimes', 'numeric', 'min:0'],
        // 'available'  => ['sometimes', 'boolean'],
        ]);
    
       

        $weekMenu->update($validated);
        return back()->with('success', "Le menu {$weekMenu->dish_name} est maintenant ajouter.");
    }

    /**
     * Enable or disable a menu item.
     */
    public function toggle(WeekMenu $weekMenu)
    {
        $weekMenu->update([
            'available' => ! $weekMenu->available
        ]);
     return back()->with('success', "Le menu {$weekMenu->dish_name} est maintenant visible.");
    }
}
