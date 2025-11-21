<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SundayMeal extends Model
{
    use HasFactory;

    protected $fillable = [
        'meal_date',
        'max_capacity',
        'is_open',
        'allow_multiple_places',
        'max_places_per_user',
        'allow_guests',
        'max_guests_per_user',
        'registration_deadline',
        'cancellation_deadline',
        'notes',
    ];

    protected $casts = [
        'meal_date' => 'date',
        'is_open' => 'boolean',
        'max_capacity' => 'integer',
        'allow_multiple_places' => 'boolean',
        'max_places_per_user' => 'integer',
        'allow_guests' => 'boolean',
        'max_guests_per_user' => 'integer',
        'registration_deadline' => 'datetime',
        'cancellation_deadline' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($meal) {
            // Set default deadlines if not provided (Saturday 18h00 before the meal)
            if (empty($meal->registration_deadline)) {
                $meal->registration_deadline = Carbon::parse($meal->meal_date)
                    ->subDay()
                    ->setTime(18, 0);
            }
            if (empty($meal->cancellation_deadline)) {
                $meal->cancellation_deadline = Carbon::parse($meal->meal_date)
                    ->subDay()
                    ->setTime(18, 0);
            }
        });
    }

    /**
     * Get the inscriptions for this meal.
     */
    public function inscriptions(): HasMany
    {
        return $this->hasMany(MealInscription::class);
    }

    /**
     * Get the active (non-cancelled) inscriptions.
     */
    public function activeInscriptions(): HasMany
    {
        return $this->hasMany(MealInscription::class)->whereNull('cancelled_at');
    }

    /**
     * Get confirmed inscriptions count (total persons including places and guests).
     */
    public function getConfirmedCountAttribute(): int
    {
        return $this->activeInscriptions()
            ->selectRaw('COALESCE(SUM(places_count + guests_count), 0) as total')
            ->value('total') ?? 0;
    }

    /**
     * Get remaining slots.
     */
    public function getRemainingPlacesAttribute(): int
    {
        return max(0, $this->max_capacity - $this->confirmed_count);
    }

    /**
     * Check if the meal is full.
     */
    public function isFull(): bool
    {
        return $this->remaining_places <= 0;
    }

    /**
     * Check if registration is still possible.
     */
    public function canRegister(): bool
    {
        if (!$this->is_open) {
            return false;
        }

        // If no deadline set, registration is open until meal date
        if (!$this->registration_deadline) {
            return !$this->isFull() && $this->meal_date->isFuture();
        }

        return now()->lessThan($this->registration_deadline) && !$this->isFull();
    }

    /**
     * Check if cancellation is still possible.
     */
    public function canCancel(): bool
    {
        // If no deadline set, can cancel until meal date
        if (!$this->cancellation_deadline) {
            return $this->meal_date->isFuture();
        }

        return now()->lessThan($this->cancellation_deadline);
    }

    /**
     * Check if registration deadline has passed.
     */
    public function isRegistrationClosed(): bool
    {
        if (!$this->registration_deadline) {
            return $this->meal_date->isPast();
        }

        return now()->greaterThanOrEqualTo($this->registration_deadline);
    }

    /**
     * Get the status for display.
     */
    public function getStatusAttribute(): string
    {
        if (!$this->is_open) {
            return 'closed';
        }

        if ($this->isRegistrationClosed()) {
            return 'deadline_passed';
        }

        if ($this->isFull()) {
            return 'full';
        }

        return 'open';
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'closed' => 'Ferme',
            'deadline_passed' => 'Inscription terminee',
            'full' => 'Complet',
            'open' => 'Ouvert',
            default => 'Inconnu',
        };
    }

    /**
     * Scope to get upcoming meals.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('meal_date', '>=', now()->toDateString())
                     ->orderBy('meal_date');
    }

    /**
     * Scope to get past meals.
     */
    public function scopePast($query)
    {
        return $query->where('meal_date', '<', now()->toDateString())
                     ->orderByDesc('meal_date');
    }

    /**
     * Scope to get open meals.
     */
    public function scopeOpen($query)
    {
        return $query->where('is_open', true);
    }

    /**
     * Get formatted date.
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->meal_date->locale('fr')->isoFormat('dddd D MMMM YYYY');
    }

    /**
     * Get short formatted date.
     */
    public function getShortDateAttribute(): string
    {
        return $this->meal_date->locale('fr')->isoFormat('D MMM');
    }

    /**
     * Alias for remaining_places (backward compatibility).
     */
    public function remainingSlots(): int
    {
        return $this->remaining_places;
    }

    /**
     * Get total persons count.
     */
    public function totalPersons(): int
    {
        return $this->activeInscriptions()
            ->selectRaw('COALESCE(SUM(places_count + guests_count), 0) as total')
            ->value('total') ?? 0;
    }

    /**
     * Get the maximum places allowed per user.
     * Returns 1 if multiple places are not allowed.
     */
    public function getMaxPlacesAllowed(): int
    {
        if (!$this->allow_multiple_places) {
            return 1;
        }

        return $this->max_places_per_user ?? 5;
    }

    /**
     * Get the maximum guests allowed per user.
     * Returns 0 if guests are not allowed.
     */
    public function getMaxGuestsAllowed(): int
    {
        if (!$this->allow_guests) {
            return 0;
        }

        return $this->max_guests_per_user ?? 5;
    }
}







