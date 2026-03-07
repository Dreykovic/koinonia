<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Enums\PlatformRole;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'roles',
        'user_profile_id',
        'community_id',
        'phone',
        'room_number',
        'birthday',
        'is_active',
        'title_prefix',
        'matricule',
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
        'roles' => 'array',
    ];

    /* =========================
     | Rôles plateforme
     ========================= */

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles ?? []);
    }

    /**
     * Check if user has any of the specified roles.
     */
    public function hasAnyRole(array $roles): bool
    {
        return count(array_intersect($roles, $this->roles ?? [])) > 0;
    }

    /**
     * Check if user has all of the specified roles.
     */
    public function hasAllRoles(array $roles): bool
    {
        return count(array_intersect($roles, $this->roles ?? [])) === count($roles);
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(PlatformRole::ADMIN);
    }

    /**
     * Check if user is communication.
     */
    public function isCommunicateur(): bool
    {
        return $this->hasRole(PlatformRole::COMMUNICATION);
    }

    /**
     * Mutator pour normaliser les rôles avant sauvegarde.
     */
    public function setRolesAttribute($roles): void
    {
        $roles = collect($roles)
            ->filter()
            ->map(fn ($r) => strtolower($r))
            ->intersect(PlatformRole::all())
            ->unique()
            ->values()
            ->all();

        $this->attributes['roles'] = json_encode($roles);
    }

    /**
     * Add a role to the user.
     */
    public function addRole(string $role): void
    {
        if (!PlatformRole::exists($role)) {
            return;
        }

        $roles = $this->roles ?? [];

        if (!in_array($role, $roles)) {
            $roles[] = $role;
            $this->roles = $roles;
            $this->save();
        }
    }

    /**
     * Remove a role from the user.
     */
    public function removeRole(string $role): void
    {
        $this->roles = array_values(
            array_diff($this->roles ?? [], [$role])
        );
        $this->save();
    }

    /**
     * Sync roles (replace all existing roles).
     */
    public function syncRoles(array $roles): void
    {
        $this->roles = $roles;
        $this->save();
    }

    /**
     * Get human-readable labels for user's roles.
     */
    public function getRoleLabelsAttribute(): array
    {
        return collect($this->roles ?? [])
            ->map(fn($role) => PlatformRole::label($role))
            ->all();
    }

    /**
     * Get formatted roles as comma-separated string.
     */
    public function getFormattedRolesAttribute(): string
    {
        return implode(', ', $this->role_labels);
    }

    /**
     * Get primary role (first role or 'autre' if none).
     */
    public function getPrimaryRoleAttribute(): string
    {
        return $this->roles[0] ?? PlatformRole::AUTRE;
    }

    /**
     * Get primary role label.
     */
    public function getPrimaryRoleLabelAttribute(): string
    {
        return PlatformRole::label($this->primary_role);
    }

    /* =========================
     | Relations
     ========================= */

    public function profile(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'user_profile_id');
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function vehicleReservations(): HasMany
    {
        return $this->hasMany(VehicleReservation::class);
    }

    public function mealInscriptions(): HasMany
    {
        return $this->hasMany(MealInscription::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /* =========================
     | Scopes
     ========================= */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeWithProfile(Builder $query): Builder
    {
        return $query->with('profile');
    }

    public function scopeByRole(Builder $query, string $role): Builder
    {
        return $query->whereJsonContains('roles', $role);
    }

    public function scopeByAnyRole(Builder $query, array $roles): Builder
    {
        return $query->where(function ($q) use ($roles) {
            foreach ($roles as $role) {
                $q->orWhereJsonContains('roles', $role);
            }
        });
    }

    /* =========================
     | Métier : Réservations
     ========================= */

    /**
     * Check if user can access a specific vehicle based on its type.
     *
     * @param Vehicle|string $vehicle Vehicle instance or vehicle type name
     */
    public function canAccessVehicle($vehicle): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if (!$this->is_active || !$this->profile || !$this->profile->is_active) {
            return false;
        }

        $vehicleTypeName = $vehicle instanceof Vehicle
            ? $vehicle->type?->name
            : $vehicle;

        if (!$vehicleTypeName) {
            return false;
        }

        return $this->profile->canReserveVehicleType($vehicleTypeName);
    }

    /**
     * Get the human-readable community label.
     */
    public function getCommunityLabelAttribute(): string
    {
        return $this->community?->name ?? 'Non assigné';
    }
}
/**
 * 
 * 
 * 
 * 
 * 
 * // Obtenir les labels
$user->role_labels; // ['Administrateur', 'Communication']
$user->formatted_roles; // "Administrateur, Communication"
$user->primary_role_label; // "Administrateur"

// Dans une vue Blade
{{ $user->formatted_roles }}

// Pour un select
<select>
    @foreach(PlatformRole::options() as $value => $label)
        <option value="{{ $value }}">{{ $label }}</option>
    @endforeach
</select>

// Query
User::byAnyRole(['admin', 'communication'])->get();
 */











