<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MealInscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'sunday_meal_id',
        'user_id',
        'places_count',
        'guests_count',
        'guest_names',
        'registered_at',
        'cancelled_at',
    ];

    protected $casts = [
        'guest_names' => 'array',
        'places_count' => 'integer',
        'guests_count' => 'integer',
        'registered_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Get total persons for this inscription (places + guests).
     */
    public function totalPersons(): int
    {
        return $this->places_count + $this->guests_count;
    }

    /**
     * Get the meal for this inscription.
     */
    public function sundayMeal(): BelongsTo
    {
        return $this->belongsTo(SundayMeal::class);
    }

    /**
     * Get the user for this inscription.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if this inscription is active (not cancelled).
     */
    public function isActive(): bool
    {
        return is_null($this->cancelled_at);
    }

    /**
     * Check if this inscription is cancelled.
     */
    public function isCancelled(): bool
    {
        return !is_null($this->cancelled_at);
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return $this->isActive() ? 'Confirme' : 'Annule';
    }

    /**
     * Scope to get only active inscriptions.
     */
    public function scopeActive($query)
    {
        return $query->whereNull('cancelled_at');
    }

    /**
     * Scope to get cancelled inscriptions.
     */
    public function scopeCancelled($query)
    {
        return $query->whereNotNull('cancelled_at');
    }
}






