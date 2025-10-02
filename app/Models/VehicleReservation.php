<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleReservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'user_id',
        'reservation_date',
        'start_time',
        'end_time',
        'destination',
        'status',
        'approved_by',
        'notes',
    ];

    protected $casts = [
        'reservation_date' => 'date',
    ];

    /**
     * Get the vehicle for this reservation.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get the user who made this reservation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who approved/rejected this reservation.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the status label in French.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'En attente',
            'approved' => 'Approuvee',
            'rejected' => 'Rejetee',
            'cancelled' => 'Annulee',
            default => $this->status,
        };
    }

    /**
     * Check if the reservation can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        if (!in_array($this->status, ['pending', 'approved'])) {
            return false;
        }

        // Peut annuler tant que la réservation n'est pas terminée
        $reservationEndDateTime = $this->reservation_date->format('Y-m-d') . ' ' . $this->end_time;
        $reservationEndTime = \Carbon\Carbon::parse($reservationEndDateTime);

        return now()->lessThan($reservationEndTime);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get reservations for a specific date.
     */
    public function scopeForDate($query, string $date)
    {
        return $query->where('reservation_date', $date);
    }

    /**
     * Scope to get upcoming reservations.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('reservation_date', '>=', now()->toDateString())
                     ->orderBy('reservation_date')
                     ->orderBy('start_time');
    }
}



