<?php

namespace App\Services;

use App\Models\MenuSetting;
use App\Models\MenuWeek;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Rotation automatique du cycle de menus.
 *
 * Un point d'ancrage (une semaine du cycle + le lundi ou elle etait en ligne)
 * suffit a deduire la semaine affichee a n'importe quelle date :
 * on avance dans le cycle d'un cran par lundi ecoule. Aucune tache planifiee,
 * aucune derive possible.
 *
 * Journalisation (canal `app`) : un calcul en echec est logue en error
 * (`menu.rotation.failed`) et declenche l'alerte `menu_rotation_failure` ; les
 * appelants retombent alors sur la semaine choisie en base (MenuSetting). Les
 * incoherences du cycle (ancre perdue, ancre dans le futur, cycle vide) sont
 * loguees en warning.
 */
class MenuRotation
{
    /** Une alerte (critical + e-mail) au plus par fenetre pour un calcul de rotation en echec. */
    private const FAILURE_ALERT_WINDOW_MINUTES = 30;

    /** Un meme warning repetitif (calcule a chaque affichage) n'est ecrit qu'une fois par periode. */
    private const REPEATED_WARNING_MINUTES = 60;

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
        } else {
            Log::channel('app')->warning('menu.rotation.empty_cycle', ['trigger' => 'enable']);
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
     * Null si le cycle est vide ou si le calcul echoue (l'appelant garde la semaine en base).
     */
    public static function currentWeek(): ?MenuWeek
    {
        $cycle = self::safeCycle();

        if ($cycle === null) {
            return null;
        }

        if ($cycle->isEmpty()) {
            self::warnOnce('menu.rotation.empty_cycle', ['trigger' => 'current_week']);

            return null;
        }

        $index = self::currentIndex($cycle);

        return $index === null ? null : $cycle[$index];
    }

    /**
     * Planning du cycle : pour chaque semaine, sa prochaine periode d'affichage.
     * Vide si le cycle est vide ou si le calcul echoue.
     *
     * @return Collection<int, array{week: MenuWeek, start: Carbon, end: Carbon, is_current: bool, offset: int}>
     */
    public static function schedule(): Collection
    {
        $cycle = self::safeCycle();

        if ($cycle === null || $cycle->isEmpty()) {
            return collect();
        }

        $currentIndex = self::currentIndex($cycle);

        if ($currentIndex === null) {
            return collect();
        }

        $count = $cycle->count();
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

    /** Prochaine semaine du cycle et date de bascule (null si une seule semaine ou en cas d'echec). */
    public static function next(): ?array
    {
        $cycle = self::safeCycle();

        if ($cycle === null || $cycle->count() < 2) {
            return null;
        }

        $currentIndex = self::currentIndex($cycle);

        if ($currentIndex === null) {
            return null;
        }

        $index = ($currentIndex + 1) % $cycle->count();

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
        return 'du '.self::formatDate($start).' au '.self::formatDate($end);
    }

    /** Cycle, ou null (erreur journalisee) si la lecture echoue. */
    private static function safeCycle(): ?Collection
    {
        try {
            return self::cycle();
        } catch (Throwable $e) {
            self::reportFailure('cycle', $e);

            return null;
        }
    }

    /** Index de la semaine courante, ou null (erreur journalisee) si le calcul echoue. */
    private static function currentIndex(Collection $cycle): ?int
    {
        try {
            return self::computeIndex($cycle);
        } catch (Throwable $e) {
            self::reportFailure('current_index', $e, ['cycle' => $cycle->pluck('id')->all()]);

            return null;
        }
    }

    private static function computeIndex(Collection $cycle): int
    {
        $setting = MenuSetting::query()->first();
        $count = $cycle->count();

        $anchorIndex = $setting?->anchor_week_id
            ? $cycle->search(fn (MenuWeek $w) => $w->id === (int) $setting->anchor_week_id)
            : false;

        if ($anchorIndex === false || ! $setting?->anchor_week_start) {
            // Ancre perdue (semaine supprimee ou mise en reserve) : la rotation
            // repart de la premiere semaine du cycle a partir de cette semaine.
            Log::channel('app')->warning('menu.rotation.anchor_lost', [
                'anchor_week_id' => $setting?->anchor_week_id,
                'anchor_week_start' => $setting?->anchor_week_start?->toDateString(),
                'restart_week_id' => $cycle->first()->id,
                'cycle' => $cycle->pluck('id')->all(),
            ]);

            self::anchorTo($cycle->first());

            return 0;
        }

        $anchorMonday = Carbon::parse($setting->anchor_week_start)->startOfWeek(CarbonInterface::MONDAY)->startOfDay();
        $elapsed = intdiv($anchorMonday->diffInDays(self::thisMonday(), false), 7);

        if ($elapsed < 0) {
            // L'ancre est posee apres aujourd'hui (horloge serveur reculee, base modifiee a la main) :
            // le calcul reste defini (modulo) mais la semaine affichee est probablement fausse.
            self::warnOnce('menu.rotation.anchor_in_future', [
                'anchor_week_id' => (int) $setting->anchor_week_id,
                'anchor_week_start' => $anchorMonday->toDateString(),
                'this_monday' => self::thisMonday()->toDateString(),
            ]);
        }

        return ((($anchorIndex + $elapsed) % $count) + $count) % $count;
    }

    private static function reportFailure(string $step, Throwable $e, array $context = []): void
    {
        Log::channel('app')->error('menu.rotation.failed', ['step' => $step] + $context + [
            'exception_class' => get_class($e),
            'error' => $e->getMessage(),
            'location' => $e->getFile().':'.$e->getLine(),
            'exception' => $e,
        ]);

        try {
            // Seuil 1 : la premiere erreur de la fenetre alerte, les suivantes restent dans le log.
            app(AlertService::class)->hit(
                'menu_rotation_failure',
                'La rotation automatique des menus a echoue : la semaine enregistree reste affichee.',
                ['step' => $step, 'error' => $e->getMessage()],
                1,
                self::FAILURE_ALERT_WINDOW_MINUTES,
            );
        } catch (Throwable) {
            // Jamais d'exception depuis le calcul de la semaine affichee.
        }
    }

    /**
     * Warning calcule a chaque affichage : ecrit une fois par periode pour ne pas noyer le log.
     */
    private static function warnOnce(string $event, array $context): void
    {
        try {
            $first = Cache::add('menu-rotation:warned:'.$event, true, now()->addMinutes(self::REPEATED_WARNING_MINUTES));
        } catch (Throwable) {
            $first = true;
        }

        if ($first) {
            Log::channel('app')->warning($event, $context);
        }
    }
}
