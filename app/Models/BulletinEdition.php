<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BulletinEdition extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'subtitle',
        'edition_number',
        'week_start',
        'week_end',
        'year',
        'is_published',
        'created_by',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($bulletin) {
            // Auto-increment edition_number per year
            if (empty($bulletin->edition_number)) {
                $lastEdition = static::where('year', $bulletin->year)
                    ->orderBy('edition_number', 'desc')
                    ->first();

                $bulletin->edition_number = $lastEdition ? $lastEdition->edition_number + 1 : 1;
            }
        });
    }

    protected $casts = [
        'week_start' => 'date',
        'week_end' => 'date',
        'is_published' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function weeklyServices(): HasMany
    {
        return $this->hasMany(WeeklyService::class, 'bulletin_id');
    }

    public function weeklyActivities(): HasMany
    {
        return $this->hasMany(WeeklyActivity::class, 'bulletin_id');
    }

    public function spiritualReflections(): HasMany
    {
        return $this->hasMany(SpiritualReflection::class, 'bulletin_id');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeCurrent($query)
    {
        $today = now();
        return $query->where('week_start', '<=', $today)
                    ->where('week_end', '>=', $today);
    }

    public function getWeekLabelAttribute(): string
    {
        return "Semaine du {$this->week_start->format('d')} au {$this->week_end->format('d M Y')}";
    }

    public function getFullTitleAttribute(): string
    {
        return "Numero: {$this->edition_number} | {$this->week_label} | Annee {$this->year}";
    }
}



