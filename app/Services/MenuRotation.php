<?php

namespace App\Services;

use App\Models\MenuSetting;
use App\Models\MenuWeek;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Rotation automatique du cycle de menus.
 *
 * Un point d'ancrage (une semaine du cycle + le lundi ou elle etait en ligne)
 * suffit a deduire la semaine affichee a n'importe quelle date :
 * on avance dans le cycle d'un cran par lundi ecoule. Aucune tache planifiee,
 * aucune derive possible.
 */
class MenuRotation
{
    public static function isEnabled(): bool
    {
        return (bool) MenuSetting::query()->value('auto_rotation');
    }

    /** Lundi de la semaine en cours. */
    public static function thisMonday(): Carbon
    {
        return now()->startOfWeek(CarbonInterface::MONDAY)->startOfDay();
    }

    /** Semaines qui tournent, dans l'ordre du cycle. */
    public static function cycle(): Collection
    {
        return MenuWeek::inCycle()->ordered()->get()->values();
    }

    /**
     * Active la rotation en l'ancrant sur la semaine actuellement affichee :
     * elle reste en ligne cette semaine, la suivante du cycle prend le relais
     * lundi prochain.
     */
    public static function enable(): ?MenuWeek
    {
        // Lire la semaine en ligne AVANT de passer en auto : c'est elle qui ancre.
        $current = MenuSetting::activeWeek();
        $anchor = $current && $current->in_cycle ? $current : self::cycle()->first();

        $setting = MenuSetting::query()->first() ?? new MenuSetting();
        $setting->auto_rotation = true;
        $setting->save();

        if ($anchor) {
            self::anchorTo($anchor);
        }

        return $anchor;
    }

    /** Repasse en manuel : la semaine affichee aujourd'hui reste en ligne. */
    public static function disable(): void
    {
        $current = MenuSetting::activeWeek();

        $setting = MenuSetting::query()->first() ?? new MenuSetting();
        $setting->auto_rotation = false;
        if ($current) {
            $setting->active_week_id = $current->id;
        }
        $setting->save();
    }

    /**
     * Fait de $week la semaine en ligne des maintenant ; la rotation continue
     * a partir d'elle lundi prochain.
     */
    public static function anchorTo(MenuWeek $week): void
    {
        $setting = MenuSetting::query()->first() ?? new MenuSetting();
        $setting->anchor_week_id = $week->id;
        $setting->anchor_week_start = self::thisMonday()->toDateString();
        $setting->active_week_id = $week->id;
        $setting->save();
    }

    /**
     * Semaine du cycle a afficher aujourd'hui selon l'ancrage.
     * Si l'ancre a quitte le cycle, la rotation repart de la premiere semaine.
     */
    public static function currentWeek(): ?MenuWeek
    {
        $cycle = self::cycle();

        if ($cycle->isEmpty()) {
            return null;
        }

        return $cycle[self::currentIndex($cycle)];
    }

    /**
     * Planning du cycle : pour chaque semaine, sa prochaine periode d'affichage.
     *
     * @return Collection<int, array{week: MenuWeek, start: Carbon, end: Carbon, is_current: bool, offset: int}>
     */
    public static function schedule(): Collection
    {
        $cycle = self::cycle();

        if ($cycle->isEmpty()) {
            return collect();
        }

        $count = $cycle->count();
        $currentIndex = self::currentIndex($cycle);
        $monday = self::thisMonday();

        return $cycle->map(function (MenuWeek $week, int $index) use ($count, $currentIndex, $monday) {
            $offset = ($index - $currentIndex + $count) % $count;
            $start = $monday->copy()->addWeeks($offset);

            return [
                'week' => $week,
                'start' => $start,
                'end' => $start->copy()->addDays(6),
                'is_current' => $offset === 0,
                'offset' => $offset,
            ];
        });
    }

    /** Prochaine semaine du cycle et date de bascule (null si une seule semaine). */
    public static function next(): ?array
    {
        $cycle = self::cycle();

        if ($cycle->count() < 2) {
            return null;
        }

        $index = (self::currentIndex($cycle) + 1) % $cycle->count();

        return [
            'week' => $cycle[$index],
            'at' => self::thisMonday()->addWeek(),
        ];
    }

    public static function formatDate(CarbonInterface $date): string
    {
        return $date->locale('fr')->isoFormat('Do MMM');
    }

    public static function formatRange(CarbonInterface $start, CarbonInterface $end): string
    {
        return 'du ' . self::formatDate($start) . ' au ' . self::formatDate($end);
    }

    private static function currentIndex(Collection $cycle): int
    {
        $setting = MenuSetting::query()->first();
        $count = $cycle->count();

        $anchorIndex = $setting?->anchor_week_id
            ? $cycle->search(fn (MenuWeek $w) => $w->id === (int) $setting->anchor_week_id)
            : false;

        if ($anchorIndex === false || !$setting?->anchor_week_start) {
            // Ancre perdue (semaine supprimee ou mise en reserve) : la rotation
            // repart de la premiere semaine du cycle a partir de cette semaine.
            self::anchorTo($cycle->first());

            return 0;
        }

        $anchorMonday = Carbon::parse($setting->anchor_week_start)->startOfWeek(CarbonInterface::MONDAY)->startOfDay();
        $elapsed = intdiv($anchorMonday->diffInDays(self::thisMonday(), false), 7);

        return ((($anchorIndex + $elapsed) % $count) + $count) % $count;
    }
}
