<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GuardsMealMenuActions;
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
    use GuardsMealMenuActions;

    public function index()
    {
        return $this->guardPage('menu.page.weeks', [], function () {
            $weeks = MenuWeek::ordered()
                ->withCount([
                    'menus as slots_count',
                    'menus as visible_count' => fn ($q) => $q->where('available', true),
                ])
                ->get();

            // Une semaine sans ses 14 cases (jours x periodes) ne s'affiche pas correctement.
            $incomplete = $weeks->filter(fn ($w) => $w->slots_count === 0)->pluck('id')->values()->all();
            if ($incomplete !== []) {
                $this->logWarning('menu.week.without_slots', ['week_ids' => $incomplete]);
            }

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
        });
    }

    /**
     * Interrupteur Auto / Manuel de la rotation du cycle.
     */
    public function rotation(Request $request)
    {
        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $enabled = (bool) $validated['enabled'];

        return $this->guardAction('menu.rotation.toggle', ['enabled' => $enabled], function () use ($enabled) {
            if ($enabled) {
                $anchor = MenuRotation::enable();
                $next = MenuRotation::next();

                $this->logInfo('menu.rotation.enabled', ['anchor_week_id' => $anchor?->id], 'audit');

                $message = $anchor
                    ? "Rotation automatique activee : « {$anchor->name} » reste en ligne cette semaine"
                        .($next ? ', puis le '.MenuRotation::formatDate($next['at'])." « {$next['week']->name} » prend le relais." : '.')
                    : 'Rotation automatique activee. Ajoutez une semaine au cycle pour qu\'elle tourne.';
            } else {
                MenuRotation::disable();
                $current = MenuSetting::activeWeek();

                $this->logInfo('menu.rotation.disabled', ['active_week_id' => $current?->id], 'audit');

                $message = 'Rotation automatique desactivee.'
                    .($current ? " « {$current->name} » reste affichee jusqu'a ce que vous en choisissiez une autre." : '');
            }

            return redirect()->route('week-menus.admin.weeks.index')->with('success', $message);
        }, 'Le mode de rotation n\'a pas pu etre modifie. Reessayez dans quelques instants.');
    }

    public function store(StoreMenuWeekRequest $request)
    {
        $data = $request->validated();
        $sourceId = ! empty($data['source_id']) ? (int) $data['source_id'] : null;

        return $this->guardAction('menu.week.store', ['source_week_id' => $sourceId], function () use ($data, $sourceId) {
            $source = $sourceId ? MenuWeek::find($sourceId) : null;

            if ($sourceId && ! $source) {
                // Semaine source supprimee entre l'affichage du formulaire et l'envoi : creation a vide.
                $this->logWarning('menu.week.source_missing', ['source_week_id' => $sourceId]);
            }

            $week = MenuWeek::createWithSlots(trim($data['name']), $source);

            $this->logInfo('menu.week.created', ['week_id' => $week->id, 'source_week_id' => $source?->id], 'audit');

            $message = $source
                ? "La semaine « {$week->name} » a ete creee a partir de « {$source->name} »."
                : "La semaine « {$week->name} » a ete creee. Saisissez ses plats puis rendez-les visibles.";

            return redirect()
                ->route('week-menus.admin.edit', ['week' => $week->id])
                ->with('success', $message);
        }, 'La semaine n\'a pas pu etre creee. Reessayez dans quelques instants.');
    }

    public function duplicate(MenuWeek $week)
    {
        return $this->guardAction('menu.week.duplicate', ['week_id' => $week->id], function () use ($week) {
            $copy = $week->duplicate();

            $this->logInfo('menu.week.duplicated', ['week_id' => $copy->id, 'source_week_id' => $week->id], 'audit');

            return redirect()
                ->route('week-menus.admin.edit', ['week' => $copy->id])
                ->with('success', "« {$copy->name} » a ete creee avec les plats de « {$week->name} ».");
        }, 'La semaine n\'a pas pu etre dupliquee. Reessayez dans quelques instants.');
    }

    public function update(UpdateMenuWeekRequest $request, MenuWeek $week)
    {
        $data = $request->validated();

        return $this->guardAction('menu.week.update', ['week_id' => $week->id], function () use ($data, $week) {
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

            if ($renamed || $cycleChanged) {
                $this->logInfo('menu.week.updated', [
                    'week_id' => $week->id,
                    'renamed' => $renamed,
                    'in_cycle' => $cycleChanged ? $week->in_cycle : null,
                ], 'audit');
            }

            if ($cycleChanged && ! $week->in_cycle && MenuRotation::isEnabled() && MenuRotation::cycle()->isEmpty()) {
                // Cycle vide en mode auto : la rotation n'a plus rien a faire tourner.
                $this->logWarning('menu.rotation.empty_cycle', ['week_id' => $week->id, 'trigger' => 'reserve']);
            }

            $message = match (true) {
                $cycleChanged && $week->in_cycle => "« {$week->name} » est de retour dans le cycle, en fin de rotation.",
                $cycleChanged => "« {$week->name} » est mise en reserve : elle ne tourne plus dans le cycle.",
                $renamed => "« {$previousName} » s'appelle maintenant « {$week->name} ».",
                default => 'Aucune modification a enregistrer.',
            };

            return redirect()->route('week-menus.admin.weeks.index')->with('success', $message);
        }, 'La semaine n\'a pas pu etre modifiee. Reessayez dans quelques instants.');
    }

    public function move(Request $request, MenuWeek $week)
    {
        $validated = $request->validate([
            'direction' => ['required', 'in:up,down'],
        ]);

        $context = ['week_id' => $week->id, 'direction' => $validated['direction']];

        return $this->guardAction('menu.week.move', $context, function () use ($week, $validated, $context) {
            $moved = $week->move($validated['direction']);

            if ($moved) {
                $this->logInfo('menu.week.moved', $context, 'audit');
            }

            return redirect()
                ->route('week-menus.admin.weeks.index')
                ->with($moved ? 'success' : 'error', $moved
                    ? "« {$week->name} » a ete deplacee. La semaine en ligne ne change pas."
                    : "« {$week->name} » est deja en ".($validated['direction'] === 'up' ? 'tete' : 'fin').' du cycle.');
        }, 'La semaine n\'a pas pu etre deplacee. Reessayez dans quelques instants.');
    }

    /**
     * Nouvel ordre complet du cycle, envoye par le glisser-deposer.
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['integer', 'distinct', 'exists:menu_weeks,id'],
        ]);

        $order = array_map('intval', $validated['order']);

        return $this->guardAction('menu.cycle.reorder', ['order' => $order], function () use ($order) {
            $applied = MenuWeek::applyOrder($order);

            if ($applied) {
                $this->logInfo('menu.cycle.reordered', ['order' => $order], 'audit');
            } else {
                // Cycle incoherent avec l'ordre recu (page perimee, autre admin en parallele).
                $this->logWarning('menu.cycle.reorder_mismatch', [
                    'order' => $order,
                    'cycle' => MenuWeek::inCycle()->ordered()->pluck('id')->all(),
                ]);
            }

            return redirect()
                ->route('week-menus.admin.weeks.index')
                ->with($applied ? 'success' : 'error', $applied
                    ? 'Nouvel ordre du cycle enregistre. La semaine en ligne ne change pas.'
                    : 'L\'ordre recu ne correspond plus au cycle actuel : rechargez la page et recommencez.');
        }, 'Le nouvel ordre n\'a pas pu etre enregistre. Reessayez dans quelques instants.');
    }

    public function destroy(MenuWeek $week)
    {
        return $this->guardAction('menu.week.destroy', ['week_id' => $week->id], function () use ($week) {
            if (MenuWeek::count() <= 1) {
                $this->logInfo('menu.week.delete_refused', ['week_id' => $week->id, 'reason' => 'last_week']);

                return redirect()
                    ->route('week-menus.admin.weeks.index')
                    ->with('error', 'Impossible de supprimer la derniere semaine : le cycle doit en contenir au moins une.');
            }

            $wasActive = $week->isActive();
            $name = $week->name;

            $week->delete();

            $message = "La semaine « {$name} » a ete supprimee.";
            $replacement = null;

            if ($wasActive) {
                $replacement = MenuSetting::activeWeek();
                $message .= $replacement
                    ? " « {$replacement->name} » est maintenant en ligne."
                    : ' Plus aucune semaine n\'est en ligne.';

                if (! $replacement) {
                    $this->logWarning('menu.week.missing', ['page' => 'destroy', 'deleted_week_id' => $week->id]);
                }
            }

            $this->logInfo('menu.week.deleted', [
                'week_id' => $week->id,
                'was_active' => $wasActive,
                'replacement_week_id' => $replacement?->id,
            ], 'audit');

            return redirect()->route('week-menus.admin.weeks.index')->with('success', $message);
        }, 'La semaine n\'a pas pu etre supprimee. Reessayez dans quelques instants.');
    }
}
