<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateWeekMenusRequest;
use App\Models\MenuSetting;
use App\Models\MenuWeek;
use App\Models\WeekMenu;
use App\Services\MenuRotation;
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
            'activeWeek' => MenuSetting::activeWeek(),
        ]);
    }

    /**
     * Display the admin form to edit the weekly menu.
     * Shows ALL menus (including unavailable) so admins can toggle them.
     */
    public function edit(Request $request)
    {
        $weeks = MenuWeek::ordered()->get();

        if ($weeks->isEmpty()) {
            return redirect()
                ->route('week-menus.admin.weeks.index')
                ->with('error', 'Creez d\'abord une semaine pour pouvoir saisir des plats.');
        }

        $activeWeek = MenuSetting::activeWeek();
        $requested = (int) $request->query('week', 0);

        $week = $weeks->firstWhere('id', $requested) ?? $activeWeek ?? $weeks->first();

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

        $auto = MenuRotation::isEnabled();

        return view('week-menus.edit', [
            'week' => $week,
            'activeWeek' => $activeWeek,
            'weeks' => $weeks,
            'menusPerDays' => $menusPerDays,
            'menusPerPeriods' => $menusPerPeriods,
            'autoRotation' => $auto,
            'schedule' => $auto ? MenuRotation::schedule()->keyBy(fn ($s) => $s['week']->id) : collect(),
            'nextSwitch' => $auto ? MenuRotation::next() : null,
        ]);
    }

    /**
     * Save every dish of a week at once.
     *
     * Une seule soumission pour les 14 plats : l'admin modifie ce qu'il veut
     * puis enregistre, au lieu d'un formulaire par plat.
     */
    public function updateWeek(UpdateWeekMenusRequest $request, MenuWeek $week)
    {
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
            $changed === 1 => "« {$week->name} » a ete mise a jour (1 plat).",
            default => "« {$week->name} » a ete mise a jour ({$changed} plats).",
        };

        return redirect()
            ->route('week-menus.admin.edit', ['week' => $week->id])
            ->with('success', $message);
    }

    /**
     * Choose which week of the cycle is displayed.
     */
    public function setActiveWeek(Request $request)
    {
        $validated = $request->validate([
            'week' => ['required', 'integer', 'exists:menu_weeks,id'],
        ]);

        $week = MenuWeek::findOrFail($validated['week']);

        if (MenuRotation::isEnabled() && $week->in_cycle) {
            // En auto : la semaine passe en ligne tout de suite et la rotation
            // continue a partir d'elle lundi prochain.
            MenuRotation::anchorTo($week);
            $next = MenuRotation::next();
            $message = "« {$week->name} » est maintenant affichee. La rotation continue a partir d'elle"
                . ($next ? ', puis le ' . MenuRotation::formatDate($next['at']) . " « {$next['week']->name} » prend le relais." : '.');
        } elseif (MenuRotation::isEnabled()) {
            // Une semaine en reserve ne peut pas tourner : on repasse en manuel.
            MenuRotation::disable();
            MenuSetting::setActiveWeek($week);
            $message = "« {$week->name} » est maintenant affichee. La rotation automatique est desactivee : elle restera en ligne jusqu'a ce que vous la reactiviez.";
        } else {
            MenuSetting::setActiveWeek($week);
            $message = "« {$week->name} » est maintenant affichee.";
        }

        $target = $request->input('return') === 'weeks'
            ? route('week-menus.admin.weeks.index')
            : route('week-menus.admin.edit', ['week' => $week->id]);

        return redirect()->to($target)->with('success', $message);
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
