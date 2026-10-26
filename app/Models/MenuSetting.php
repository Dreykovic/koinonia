<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuSetting extends Model
{
    protected $fillable = ['active_week_id'];

    protected $casts = ['active_week_id' => 'integer'];

    /**
     * Semaine actuellement affichee. Si aucune n'est designee (ou si elle a
     * ete supprimee), la premiere semaine du cycle prend le relais.
     */
    public static function activeWeek(): ?MenuWeek
    {
        $setting = self::query()->first();
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
