<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class WeekMenu extends Model
{
    protected $table = 'week_menus';

    protected $fillable = [
        'day_id',
        'period_id',
        'dish_name',
        'description',
        'price',
        'available',
    ];

    protected $casts = [
        'available' => 'boolean',
        'price' => 'integer',
    ];

    /* =======================
       RELATIONS
    ======================= */

    public function day(): BelongsTo
    {
        return $this->belongsTo(WeekMenuDay::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(WeekMenuPeriod::class);
    }

    /* =======================
       SCOPES (IMPORTANT)
    ======================= */

    /**
     * Menu items that are available
     */
    public function scopeAvailable(Builder $query): Builder
    {
        // return $query->where('available', true);
        return $query;
    }

    /**
     * Complete ordering by day and period
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->join('week_menu_days', 'week_menus.day_id', '=', 'week_menu_days.id')
            ->join('week_menu_periods', 'week_menus.period_id', '=', 'week_menu_periods.id')
            ->orderBy('week_menu_days.position')
            ->orderBy('week_menu_periods.position')

            ->select('week_menus.*');
    }
        /**
     * Complete ordering by period and day
     */
    public function scopeOrderedPeriod(Builder $query): Builder
    {
        return $query
            ->join('week_menu_days', 'week_menus.day_id', '=', 'week_menu_days.id')
            ->join('week_menu_periods', 'week_menus.period_id', '=', 'week_menu_periods.id')
            ->orderBy('week_menu_periods.position')
            ->orderBy('week_menu_days.position')

            ->select('week_menus.*');
    }

    /**
     * Filter by day (lundi, mardi, etc.)
     */
    public function scopeForDay(Builder $query, string $dayCode): Builder
    {
        return $query->whereHas('day', function ($q) use ($dayCode) {
            $q->where('code', $dayCode);
        });
    }

    /**
     * 
     * Filter by periods (midi / soir)
     */
    public function scopeForPeriod(Builder $query, string $periodCode): Builder
    {
        return $query->whereHas('period', function ($q) use ($periodCode) {
            $q->where('code', $periodCode);
        });
    }

    /**
     * today's menus
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeToday(Builder $query): Builder
    {
        // Récupération du nom du jour en français (ex: "lundi")
        $dayCode = now()->locale('fr_FR')->dayName;

        return $query->forDay($dayCode);
    }

    /**
     * menu of the current period (midi / soir)
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForCurrentPeriod(Builder $query): Builder
    {
        $hour = now()->format('H');

        $periodCode = $hour < 15 ? 'midi' : 'soir';

        return $query->forPeriod($periodCode);
    }


}
