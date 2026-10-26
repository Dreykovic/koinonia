<?php

namespace App\Models;

use App\Services\MenuRotation;
use Illuminate\Database\Eloquent\Model;

class MenuSetting extends Model
{
    protected $fillable = ['active_week_id', 'auto_rotation', 'anchor_week_id', 'anchor_week_start'];

    protected $casts = [
        'active_week_id' => 'integer',
        'auto_rotation' => 'boolean',
        'anchor_week_id' => 'integer',
        'anchor_week_start' => 'date',
    ];

    /**
     * Semaine actuellement affichee.
     *
     * En rotation automatique, elle est deduite du point d'ancrage et de la
     * date du jour. En manuel, c'est celle choisie par l'admin. Si aucune
     * n'est designee (ou si elle a ete supprimee), la premiere semaine du
     * cycle prend le relais.
     */
    public static function activeWeek(): ?MenuWeek
    {
        $setting = self::query()->first();

        if ($setting?->auto_rotation) {
            $week = MenuRotation::currentWeek();

            if ($week) {
                if ($setting->active_week_id !== $week->id) {
                    // Garde la valeur en base alignee : utile si on repasse en manuel.
                    $setting->forceFill(['active_week_id' => $week->id])->save();
                }

                return $week;
            }
        }

        $week = $setting?->active_week_id ? MenuWeek::find($setting->active_week_id) : null;

        if ($week) {
            return $week;
        }

        $fallback = MenuWeek::inCycle()->ordered()->first() ?? MenuWeek::ordered()->first();

        if ($fallback) {
            self::setActiveWeek($fallback);
        }

        return $fallback;
    }

    public static function activeWeekId(): ?int
    {
        return self::activeWeek()?->id;
    }

    public static function setActiveWeek(MenuWeek $week): void
    {
        $setting = self::query()->first() ?? new self();
        $setting->active_week_id = $week->id;
        $setting->save();
    }

    /**
     * Appele quand une semaine disparait : la prochaine lecture choisira
     * automatiquement une remplacante.
     */
    public static function forgetWeek(MenuWeek $week): void
    {
        self::query()->where('active_week_id', $week->id)->update(['active_week_id' => null]);
    }
}
