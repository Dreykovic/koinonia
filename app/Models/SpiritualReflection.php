<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpiritualReflection extends Model
{
    use HasFactory;

    protected $fillable = [
        'bulletin_id',
        'title',
        'content',
        'source',
        'type',
        'week_start',
        'week_end',
        'is_current',
        'created_by',
    ];

    protected $casts = [
        'week_start' => 'date',
        'week_end' => 'date',
        'is_current' => 'boolean',
    ];

    const TYPES = [
        'spiritualite' => 'Spiritualite',
        'theologie' => 'Theologie',
    ];

    const TYPE_ICONS = [
        'spiritualite' => 'ki-heart',
        'theologie' => 'ki-book-open',
    ];

    const TYPE_COLORS = [
        'spiritualite' => ['bg' => 'var(--itcj-blue-light)', 'text' => 'var(--itcj-blue)'],
        'theologie' => ['bg' => 'var(--itcj-gold-light)', 'text' => '#9a7a30'],
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
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }

    public function getTypeIconAttribute(): string
    {
        return self::TYPE_ICONS[$this->type] ?? 'ki-abstract-26';
    }

    public function getTypeColorAttribute(): array
    {
        return self::TYPE_COLORS[$this->type] ?? ['bg' => '#f5f5f5', 'text' => '#616161'];
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeSpiritualite($query)
    {
        return $query->where('type', 'spiritualite');
    }

    public function scopeTheologie($query)
    {
        return $query->where('type', 'theologie');
    }

    public static function setAsCurrent(int $id): void
    {
        $reflection = self::findOrFail($id);

        // Remove current flag from other reflections of same type
        self::where('type', $reflection->type)
            ->where('id', '!=', $id)
            ->update(['is_current' => false]);

        // Set this one as current
        $reflection->update(['is_current' => true]);
    }
}


