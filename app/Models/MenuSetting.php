<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuSetting extends Model
{
    public const WEEKS = [1, 2, 3, 4];

    protected $fillable = ['active_week'];

    protected $casts = ['active_week' => 'integer'];

    /**
     * Semaine du cycle actuellement affichee (1 par defaut).
     */
    public static function activeWeek(): int
    {
        return (int) (self::query()->value('active_week') ?? 1);
    }

    public static function setActiveWeek(int $week): void
    {
        $setting = self::query()->first() ?? new self();
        $setting->active_week = $week;
        $setting->save();
    }
}
