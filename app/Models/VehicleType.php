<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleType extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    /* =========================
     | Relations
     ========================= */

    public function userProfiles()
    {
        return $this->belongsToMany(
            UserProfile::class,
            'user_profile_vehicle_types'
        )->withPivot('is_active')
         ->withTimestamps();
    }

    /**
     * Get the users in this community.
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    /* =========================
     | Scopes
     ========================= */

    public function scopeActive(Builder $query)
    {
        return $query->where('is_active', true);
    }
}

