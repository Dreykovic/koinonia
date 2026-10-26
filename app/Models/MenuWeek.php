<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Une semaine de menus : 14 creneaux (7 jours x midi/soir), un nom, une
 * position dans le cycle, et la possibilite d'etre mise en reserve.
 */
class MenuWeek extends Model
{
    public const NAME_MAX = 80;

    protected $fillable = ['name', 'position', 'in_cycle'];

    protected $casts = [
        'position' => 'integer',
        'in_cycle' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Les plats partent avec leur semaine, quel que soit le moteur SQL.
        static::deleting(function (MenuWeek $week) {
            $week->menus()->delete();
        });

        static::deleted(function (MenuWeek $week) {
            MenuSetting::forgetWeek($week);
            static::normalizePositions();
        });
    }

    /* =======================
       RELATIONS
    ======================= */

    public function menus(): HasMany
    {
        return $this->hasMany(WeekMenu::class, 'week_id');
    }

    /* =======================
       SCOPES
    ======================= */

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('id');
    }

    public function scopeInCycle(Builder $query): Builder
    {
        return $query->where('in_cycle', true);
    }

    /* =======================
       ETAT
    ======================= */

    public function isActive(): bool
    {
        return $this->id === MenuSetting::activeWeekId();
    }

    public static function nextPosition(): int
    {
        return ((int) static::query()->max('position')) + 1;
    }

    /* =======================
       CREATION / COPIE
    ======================= */

    /**
     * Cree une semaine avec ses 14 creneaux, vides ou copies d'une autre.
     * Une semaine vide a tous ses plats masques : rien ne s'affiche tant que
     * l'admin n'a pas saisi les plats.
     */
    public static function createWithSlots(string $name, ?MenuWeek $source = null, bool $inCycle = true): MenuWeek
    {
        return DB::transaction(function () use ($name, $source, $inCycle) {
            $week = static::create([
                'name' => $name,
                'position' => static::nextPosition(),
                'in_cycle' => $inCycle,
            ]);

            $sourceMenus = $source
                ? $source->menus()->get()->keyBy(fn ($m) => $m->day_id . '-' . $m->period_id)
                : collect();

            $rows = [];
            foreach (WeekMenuDay::orderBy('position')->get() as $day) {
                foreach (WeekMenuPeriod::orderBy('position')->get() as $period) {
                    $from = $sourceMenus->get($day->id . '-' . $period->id);

                    $rows[] = [
                        'week_id' => $week->id,
                        'day_id' => $day->id,
                        'period_id' => $period->id,
                        'dish_name' => $from->dish_name ?? 'A definir',
                        'description' => $from->description ?? null,
                        'price' => $from->price ?? null,
                        'available' => $from ? (bool) $from->available : false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            WeekMenu::insert($rows);

            return $week;
        });
    }

    public function duplicate(?string $name = null): MenuWeek
    {
        $name ??= mb_substr('Copie de ' . $this->name, 0, self::NAME_MAX);

        return static::createWithSlots($name, $this, $this->in_cycle);
    }

    /* =======================
       ORDRE DU CYCLE
    ======================= */

    /**
     * Echange la place de cette semaine avec sa voisine dans le cycle.
     * Ne touche pas a la semaine en ligne : seule la suite de la rotation change.
     */
    public function move(string $direction): bool
    {
        $cycle = static::inCycle()->ordered()->get()->values();
        $index = $cycle->search(fn ($w) => $w->id === $this->id);

        if ($index === false) {
            return false;
        }

        $target = $direction === 'up' ? $index - 1 : $index + 1;

        if ($target < 0 || $target >= $cycle->count()) {
            return false;
        }

        $neighbour = $cycle[$target];

        DB::transaction(function () use ($neighbour) {
            [$mine, $theirs] = [$this->position, $neighbour->position];
            $this->update(['position' => $theirs]);
            $neighbour->update(['position' => $mine]);
        });

        static::normalizePositions();

        return true;
    }

    /**
     * Renumerote 1..n dans l'ordre courant pour eviter trous et doublons.
     */
    public static function normalizePositions(): void
    {
        DB::transaction(function () {
            foreach (static::ordered()->get() as $index => $week) {
                if ($week->position !== $index + 1) {
                    $week->update(['position' => $index + 1]);
                }
            }
        });
    }
}
