<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateWeekMenusRequest;
use App\Models\MenuSetting;
use App\Models\MenuWeek;
use App\Models\WeekMenu;
use App\Models\WeekMenuDay;
use App\Models\WeekMenuPeriod;
use App\Services\MenuRotation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WeekMenuController extends Controller
{
    /**
     * Display the full weekly menu (public view).
     *
     * La grille est construite a partir des 7 jours et des 2 periodes de
     * reference, jamais a partir des plats visibles : un plat masque laisse
     * une case "pas de plat" au lieu de decaler les colonnes.
     */
    public function index()
    {
        $activeWeek = MenuSetting::activeWeek();
        $days = WeekMenuDay::orderBy('position')->get();
        $periods = WeekMenuPeriod::orderBy('position')->get();

        $menus = $activeWeek
            ? WeekMenu::forWeek($activeWeek)->get()->keyBy(fn ($m) => $m->day_id . '-' . $m->period_id)
            : collect();

        $visibleCount = $menus->where('available', true)->count();

        $dateRange = null;
        if ($activeWeek && MenuRotation::isEnabled()) {
            $current = MenuRotation::schedule()->firstWhere('is_current', true);
            if ($current) {
                $dateRange = MenuRotation::formatRange($current['start'], $current['end']);
            }
        }

        $todayCode = strtolower(now()->locale('fr_FR')->dayName);
        $todayDay = $days->firstWhere('code', $todayCode);

        // Plats du jour par periode (null si masque ou absent)
        $todayMenus = $periods->mapWithKeys(function ($period) use ($menus, $todayDay) {
            $menu = $todayDay ? $menus->get($todayDay->id . '-' . $period->id) : null;

            return [$period->code => $menu && $menu->available ? $menu : null];
        });

        return view('week-menus.index', [
            'activeWeek' => $activeWeek,
            'dateRange' => $dateRange,
            'days' => $days,
            'periods' => $periods,
            'menus' => $menus,
            'visibleCount' => $visibleCount,
            'todayCode' => $todayCode,
            'todayLabel' => now()->locale('fr')->isoFormat('dddd D MMMM'),
            'todayMenus' => $todayMenus,
            'currentPeriodCode' => (int) now()->format('H') < 15 ? 'midi' : 'soir',
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

}
