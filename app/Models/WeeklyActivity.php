<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeeklyActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'guests',
        'description',
        'activity_date',
        'activity_time',
        'location',
        'type',
        'is_highlighted',
    ];

    protected $casts = [
        'activity_date' => 'date',
        'activity_time' => 'datetime:H:i',
        'is_recurring' => 'boolean',
        'is_highlighted' => 'boolean',
    ];

    const ACTIVITY_TYPES = [
        'messe' => 'Messe',
        'reunion' => 'Reunion',
        'visite' => 'Visite',
        'evenement' => 'Evenement',
        'autre' => 'Autre',
    ];

    const TYPE_ICONS = [
        'messe' => 'ki-cross',
        'reunion' => 'ki-people',
        'visite' => 'ki-user-tick',
        'evenement' => 'ki-calendar',
        'autre' => 'ki-abstract-26',
    ];

    const TYPE_COLORS = [
        'messe' => ['bg' => '#f3e5f5', 'text' => '#7b1fa2'],
        'reunion' => ['bg' => 'var(--itcj-blue-light)', 'text' => 'var(--itcj-blue)'],
        'visite' => ['bg' => 'var(--itcj-gold-light)', 'text' => '#9a7a30'],
        'evenement' => ['bg' => '#e8f5e9', 'text' => '#2e7d32'],
        'autre' => ['bg' => '#f5f5f5', 'text' => '#616161'],
    ];

    public function bulletin(): BelongsTo
    {
        return $this->belongsTo(BulletinEdition::class, 'bulletin_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::ACTIVITY_TYPES[$this->type] ?? ucfirst($this->type);
    }

    public function getTypeIconAttribute(): string
    {
        return self::TYPE_ICONS[$this->type] ?? 'ki-abstract-26';
    }

    public function getTypeColorAttribute(): array
    {
        return self::TYPE_COLORS[$this->type] ?? ['bg' => '#f5f5f5', 'text' => '#616161'];
    }

    public function getFormattedTimeAttribute(): ?string
    {
        if ($this->activity_time) {
            return \Carbon\Carbon::parse($this->activity_time)->format('H\hi');
        }
        return null;
    }

    public function getFormattedDateAttribute(): ?string
    {
        if ($this->activity_date) {
            return $this->activity_date->locale('fr')->isoFormat('dddd D MMMM');
        }
        return null;
    }

    public function scopeThisWeek($query)
    {
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();
        return $query->whereBetween('activity_date', [$startOfWeek, $endOfWeek]);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('activity_date', '>=', now()->toDateString())
                    ->orderBy('activity_date')
                    ->orderBy('activity_time');
    }

    public function scopeHighlighted($query)
    {
        return $query->where('is_highlighted', true);
    }
}

