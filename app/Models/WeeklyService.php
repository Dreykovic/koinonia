<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeeklyService extends Model
{
    use HasFactory;

    protected $fillable = [
        'bulletin_id',
        'week_start',
        'week_end',
        'service_type',
        'assigned_user_id',
        'assigned_name',
        'community',
        'description',
        'is_active',
    ];

    protected $casts = [
        'week_start' => 'date',
        'week_end' => 'date',
        'is_active' => 'boolean',
    ];

    const SERVICE_TYPES = [
        'sante' => 'Sante',
        'tarife' => 'Tarife',
        'chauffeur' => 'Chauffeur',
        'intendance' => 'Intendance',
        'sport' => 'Sport',
        'liturgie' => 'Liturgie',
    ];

    const SERVICE_ICONS = [
        'sante' => 'ki-heart',
        'tarife' => 'ki-wallet',
        'chauffeur' => 'ki-car-2',
        'intendance' => 'ki-home-2',
        'sport' => 'ki-basketball',
        'liturgie' => 'ki-cross',
    ];

    const SERVICE_COLORS = [
        'sante' => ['bg' => 'var(--itcj-red-light)', 'text' => 'var(--itcj-red)'],
        'tarife' => ['bg' => 'var(--itcj-gold-light)', 'text' => '#9a7a30'],
        'chauffeur' => ['bg' => 'var(--itcj-blue-light)', 'text' => 'var(--itcj-blue)'],
        'intendance' => ['bg' => '#e8f5e9', 'text' => '#2e7d32'],
        'sport' => ['bg' => '#fff3e0', 'text' => '#e65100'],
        'liturgie' => ['bg' => '#f3e5f5', 'text' => '#7b1fa2'],
    ];

    public function bulletin(): BelongsTo
    {
        return $this->belongsTo(BulletinEdition::class, 'bulletin_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function getServiceLabelAttribute(): string
    {
        return self::SERVICE_TYPES[$this->service_type] ?? ucfirst($this->service_type);
    }

    public function getServiceIconAttribute(): string
    {
        return self::SERVICE_ICONS[$this->service_type] ?? 'ki-abstract-26';
    }

    public function getServiceColorAttribute(): array
    {
        return self::SERVICE_COLORS[$this->service_type] ?? ['bg' => '#f5f5f5', 'text' => '#616161'];
    }

    public function getAssignedDisplayNameAttribute(): string
    {
        if ($this->assignedUser) {
            $prefix = $this->assignedUser->title_prefix ? $this->assignedUser->title_prefix . ' ' : '';
            return $prefix . $this->assignedUser->name;
        }
        return $this->assigned_name ?? '-';
    }

    public function scopeCurrentWeek($query)
    {
        $today = now();
        return $query->where('week_start', '<=', $today)
                    ->where('week_end', '>=', $today)
                    ->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('service_type', $type);
    }
}
