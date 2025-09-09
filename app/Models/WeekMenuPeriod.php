<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WeekMenuPeriod extends Model
{
    protected $table = 'week_menu_periods';
    public $timestamps = false;
    protected $primaryKey = 'id';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'code',
        'position',
    ];

    /* =======================
       RELATIONS
    ======================= */

    public function menus(): HasMany
    {
        return $this->hasMany(WeekMenu::class);
    }
}
