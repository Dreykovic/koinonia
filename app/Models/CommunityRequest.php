<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'community_id',
        'request_type',
        'repair_location_id',
        'other_location',
        'room_number',
        'problem_description',
        'purchase_description',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    /**
     * Liste des statuts disponibles.
     */
    public const STATUSES = [
        'pending' => 'En attente',
        'in_progress' => 'En cours',
        'completed' => 'Termine',
        'rejected' => 'Rejete',
    ];

    /**
     * Get the community.
     */
    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    /**
     * Get the repair location.
     */
    public function repairLocation(): BelongsTo
    {
        return $this->belongsTo(RepairLocation::class);
    }

    /**
     * Get the assigned user.
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Check if this is a repair request.
     */
    public function isRepair(): bool
    {
        return $this->request_type === 'reparation';
    }

    /**
     * Check if this is a purchase request.
     */
    public function isPurchase(): bool
    {
        return $this->request_type === 'achat';
    }

    /**
     * Get the type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return $this->isRepair() ? 'Reparation' : 'Achat';
    }

    /**
     * Get the status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? 'Inconnu';
    }

    /**
     * Get the location display name.
     */
    public function getLocationDisplayAttribute(): string
    {
        if ($this->other_location) {
            return $this->other_location;
        }

        return $this->repairLocation?->name ?? '-';
    }

    /**
     * Scope to filter by status.
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by type.
     */
    public function scopeType($query, $type)
    {
        return $query->where('request_type', $type);
    }

    /**
     * Scope to filter by community.
     */
    public function scopeByCommunity($query, $communityId)
    {
        return $query->where('community_id', $communityId);
    }

    /**
     * Scope for pending requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for repairs.
     */
    public function scopeRepairs($query)
    {
        return $query->where('request_type', 'reparation');
    }

    /**
     * Scope for purchases.
     */
    public function scopePurchases($query)
    {
        return $query->where('request_type', 'achat');
    }
}





