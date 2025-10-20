<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserProfile extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    /* =========================
     | Relations
     ========================= */

    public function vehicleTypes()
    {
        return $this->belongsToMany(
            VehicleType::class,
            'user_profile_vehicle_types'
        )->withPivot('is_active')
         ->withTimestamps();
    }

    /**
     * Get the users in this community.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /* =========================
     | Scopes
     ========================= */

    public function scopeActive(Builder $query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByName(Builder $query, string $name)
    {
        return $query->where('name', $name);
    }

    /* =========================
     | Métier
     ========================= */

    public function canReserveVehicleType(string $vehicleTypeName): bool
    {
        return $this->vehicleTypes()
            ->where('vehicle_types.name', $vehicleTypeName)
            ->wherePivot('is_active', true)
            ->exists();
    }
}


