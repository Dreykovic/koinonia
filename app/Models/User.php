<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'community_id',
        'room_number',
        'phone',
        'is_active',
        'birthday',
        'title_prefix',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'birthday' => 'date',
    ];

    /**
     * Get the community this user belongs to.
     */
    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    /**
     * Get the vehicle reservations for this user.
     */
    public function vehicleReservations(): HasMany
    {
        return $this->hasMany(VehicleReservation::class);
    }

    /**
     * Get the meal inscriptions for this user.
     */
    public function mealInscriptions(): HasMany
    {
        return $this->hasMany(MealInscription::class);
    }

    /**
     * Get the notifications for this user.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Check if user can access a specific vehicle based on its type.
     */
    public function canAccessVehicle(Vehicle $vehicle): bool
    {
        if ($this->role === 'admin') {
            return true;
        }

        return match ($vehicle->type) {
            'sante' => $this->role === 'sante',
            'formation' => $this->role === 'formateur',
            'communautaire' => $this->role !== 'formateur',
            default => false,
        };
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Get the human-readable role label.
     */
    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            'admin' => 'Administrateur',
            'communication' => 'Communication',
            'sante' => 'Sante',
            'formateur' => 'Formateur',
            'membre' => 'Membre',
            default => $this->role,
        };
    }

    /**
     * Get the human-readable community label.
     */
    public function getCommunityLabelAttribute(): string
    {
        return $this->community?->name ?? 'Non assigne';
    }

    /**
     * Scope to get only active users.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
