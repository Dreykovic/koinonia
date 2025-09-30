<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'license_plate',
        'capacity',
        'is_available',
        'description',
        'vehicle_type_id'
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'capacity' => 'integer',
    ];

    /**
     * Get the reservations for this vehicle.
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class,'vehicle_type_id');
    }

    /**
     * Get the reservations for this vehicle.
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(VehicleReservation::class);
    }

    /**
     * Check if vehicle is available at a specific date and time range.
     */
    public function isAvailableAt(string $date, string $startTime, string $endTime, ?int $excludeReservationId = null): bool
    {
        if (!$this->is_available) {
            return false;
        }

        $query = $this->reservations()
            ->where('reservation_date', $date)
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where(function ($q2) use ($startTime, $endTime) {
                    $q2->where('start_time', '<', $endTime)
                       ->where('end_time', '>', $startTime);
                });
            });

        if ($excludeReservationId) {
            $query->where('id', '!=', $excludeReservationId);
        }

        return !$query->exists();
    }

    /**
     * Get the type label in French.
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type->name) {
            'sante' => 'Sante',
            'formation' => 'Formation',
            'communautaire' => 'Communautaire',
            default => $this->type->name,
        };
    }

    /**
     * Scope to get only available vehicles.
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    /**
     * Scope to filter by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}



