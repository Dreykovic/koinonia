<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class VehicleSetting extends Model
{
    protected $fillable = [
        'opening_hour',
        'closing_hour',
        'max_future_days',
    ];

    protected $casts = [
        'max_future_days' => 'integer',
    ];

    /**
     * Cache key for settings.
     */
    const CACHE_KEY = 'vehicle_settings';
    const CACHE_TTL = 3600; // 1 hour

    /**
     * Get the settings instance (cached).
     */
    public static function getSettings(): self
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return self::first() ?? self::createDefault();
        });
    }

    /**
     * Create default settings if none exist.
     */
    protected static function createDefault(): self
    {
        return self::create([
            'opening_hour' => '07:00:00',
            'closing_hour' => '20:00:00',
            'max_future_days' => 14,
        ]);
    }

    /**
     * Clear the settings cache.
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Get opening hour as H:i format.
     */
    public function getOpeningHourFormattedAttribute(): string
    {
        return substr($this->opening_hour, 0, 5);
    }

    /**
     * Get closing hour as H:i format.
     */
    public function getClosingHourFormattedAttribute(): string
    {
        return substr($this->closing_hour, 0, 5);
    }

    /**
     * Get opening hour as integer.
     */
    public function getOpeningHourIntAttribute(): int
    {
        return (int) substr($this->opening_hour, 0, 2);
    }

    /**
     * Get closing hour as integer.
     */
    public function getClosingHourIntAttribute(): int
    {
        return (int) substr($this->closing_hour, 0, 2);
    }

    /**
     * Get the maximum reservation date.
     */
    public function getMaxReservationDateAttribute(): string
    {
        return now()->addDays($this->max_future_days)->toDateString();
    }

    /**
     * Check if a given hour is within operating hours.
     */
    public function isWithinOperatingHours(string $time): bool
    {
        $openingMinutes = $this->timeToMinutes($this->opening_hour);
        $closingMinutes = $this->timeToMinutes($this->closing_hour);
        $timeMinutes = $this->timeToMinutes($time);

        return $timeMinutes >= $openingMinutes && $timeMinutes <= $closingMinutes;
    }

    /**
     * Check if a date is within the allowed future range.
     */
    public function isWithinFutureRange(string $date): bool
    {
        $targetDate = Carbon::parse($date);
        $maxDate = now()->addDays($this->max_future_days);

        return $targetDate->lessThanOrEqualTo($maxDate);
    }

    /**
     * Convert time string to minutes from midnight.
     */
    protected function timeToMinutes(string $time): int
    {
        $parts = explode(':', $time);

        return (int) $parts[0] * 60 + (int) $parts[1];
    }
}


