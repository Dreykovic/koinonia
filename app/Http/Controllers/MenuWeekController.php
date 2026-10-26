<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMenuWeekRequest;
use App\Http\Requests\UpdateMenuWeekRequest;
use App\Models\MenuSetting;
use App\Models\MenuWeek;
use App\Services\MenuRotation;
use Illuminate\Http\Request;

/**
 * Page "Cycle des menus" : creer, dupliquer, renommer, ordonner, mettre en
 * reserve et supprimer les semaines. L'edition des plats reste dans
 * WeekMenuController::edit.
 */
class MenuWeekController extends Controller
{
    public function index()
    {
        $weeks = MenuWeek::ordered()
            ->withCount([
                'menus as slots_count',
                'menus as visible_count' => fn ($q) => $q->where('available', true),
            ])
            ->get();

        $auto = MenuRotation::isEnabled();

        return view('week-menus.weeks', [
            'cycleWeeks' => $weeks->where('in_cycle', true)->values(),
            'reserveWeeks' => $weeks->where('in_cycle', false)->values(),
            'allWeeks' => $weeks,
            'activeWeek' => MenuSetting::activeWeek(),
            'autoRotation' => $auto,
            'schedule' => $auto ? MenuRotation::schedule()->keyBy(fn ($s) => $s['week']->id) : collect(),
            'nextSwitch' => $auto ? MenuRotation::next() : null,
        ]);
    }

    /**
     * Interrupteur Auto / Manuel de la rotation du cycle.
     */
    public function rotation(Request $request)
    {
        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        if ($validated['enabled']) {
            $anchor = MenuRotation::enable();
            $next = MenuRotation::next();

            $message = $anchor
                ? "Rotation automatique activee : « {$anchor->name} » reste en ligne cette semaine"
                    . ($next ? ', puis le ' . MenuRotation::formatDate($next['at']) . " « {$next['week']->name} » prend le relais." : '.')
                : 'Rotation automatique activee. Ajoutez une semaine au cycle pour qu\'elle tourne.';
        } else {
            MenuRotation::disable();
            $current = MenuSetting::activeWeek();

            $message = 'Rotation automatique desactivee.'
                . ($current ? " « {$current->name} » reste affichee jusqu'a ce que vous en choisissiez une autre." : '');
        }

        return redirect()->route('week-menus.admin.weeks.index')->with('success', $message);
    }

    public function store(StoreMenuWeekRequest $request)
    {
        $data = $request->validated();
        $source = !empty($data['source_id']) ? MenuWeek::find($data['source_id']) : null;

        $week = MenuWeek::createWithSlots(trim($data['name']), $source);

        $message = $source
            ? "La semaine « {$week->name} » a ete creee a partir de « {$source->name} »."
            : "La semaine « {$week->name} » a ete creee. Saisissez ses plats puis rendez-les visibles.";

        return redirect()
            ->route('week-menus.admin.edit', ['week' => $week->id])
            ->with('success', $message);
    }

    public function duplicate(MenuWeek $week)
    {
        $copy = $week->duplicate();

        return redirect()
            ->route('week-menus.admin.edit', ['week' => $copy->id])
            ->with('success', "« {$copy->name} » a ete creee avec les plats de « {$week->name} ».");
    }

    public function update(UpdateMenuWeekRequest $request, MenuWeek $week)
    {
        $data = $request->validated();
        $previousName = $week->name;

        if (array_key_exists('name', $data)) {
            $week->name = trim($data['name']);
        }

        if (array_key_exists('in_cycle', $data)) {
            $week->in_cycle = (bool) $data['in_cycle'];
        }

        $renamed = $week->isDirty('name');
        $cycleChanged = $week->isDirty('in_cycle');
        $week->save();

        if ($cycleChanged) {
            // Une semaine qui (re)entre dans le cycle se place en fin de rotation.
            if ($week->in_cycle) {
                $week->update(['position' => MenuWeek::nextPosition()]);
            }
            MenuWeek::normalizePositions();
        }

        $message = match (true) {
            $cycleChanged && $week->in_cycle => "« {$week->name} » est de retour dans le cycle, en fin de rotation.",
            $cycleChanged => "« {$week->name} » est mise en reserve : elle ne tourne plus dans le cycle.",
            $renamed => "« {$previousName} » s'appelle maintenant « {$week->name} ».",
            default => 'Aucune modification a enregistrer.',
        };

        return redirect()->route('week-menus.admin.weeks.index')->with('success', $message);
    }

    public function move(Request $request, MenuWeek $week)
    {
        $validated = $request->validate([
            'direction' => ['required', 'in:up,down'],
        ]);

        $moved = $week->move($validated['direction']);

        return redirect()
            ->route('week-menus.admin.weeks.index')
            ->with($moved ? 'success' : 'error', $moved
                ? "« {$week->name} » a ete deplacee. La semaine en ligne ne change pas."
                : "« {$week->name} » est deja en " . ($validated['direction'] === 'up' ? 'tete' : 'fin') . ' du cycle.');
    }

    public function destroy(MenuWeek $week)
    {
        if (MenuWeek::count() <= 1) {
            return redirect()
                ->route('week-menus.admin.weeks.index')
                ->with('error', 'Impossible de supprimer la derniere semaine : le cycle doit en contenir au moins une.');
        }

        $wasActive = $week->isActive();
        $name = $week->name;

        $week->delete();

        $message = "La semaine « {$name} » a ete supprimee.";

        if ($wasActive) {
            $replacement = MenuSetting::activeWeek();
            $message .= $replacement
                ? " « {$replacement->name} » est maintenant en ligne."
                : ' Plus aucune semaine n\'est en ligne.';
        }

        return redirect()->route('week-menus.admin.weeks.index')->with('success', $message);
    }
}
