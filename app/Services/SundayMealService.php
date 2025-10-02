<?php

namespace App\Services;

use App\Models\SundayMeal;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SundayMealService
{
    // Valeurs par défaut pour les repas auto-générés
    protected const DEFAULT_CAPACITY = 60;
    protected const DEFAULT_ALLOW_MULTIPLE_PLACES = false;  // 1 place par personne
    protected const DEFAULT_MAX_PLACES_PER_USER = 1;
    protected const DEFAULT_ALLOW_GUESTS = true;            // invités autorisés
    protected const DEFAULT_MAX_GUESTS_PER_USER = 5;

    /**
     * Fermer les repas pour demain (appelé samedi 18h).
     * Met is_open = false pour le repas du lendemain.
     *
     * @return int Nombre de repas fermés
     */
    public function closeMealsForSaturday(): int
    {
        $tomorrow = Carbon::tomorrow()->toDateString();

        $closedCount = SundayMeal::where('meal_date', $tomorrow)
            ->where('is_open', true)
            ->update(['is_open' => false]);

        Log::info('Fermeture automatique des repas', [
            'date' => $tomorrow,
            'count' => $closedCount,
        ]);

        return $closedCount;
    }

    /**
     * Générer les repas pour les N prochains dimanches.
     * Ne crée pas de repas si un repas existe déjà pour cette date.
     *
     * @param int $count Nombre de dimanches à générer (défaut: 4)
     * @return int Nombre de repas créés
     */
    public function generateUpcomingSundays(int $count = 4): int
    {
        $createdCount = 0;
        $nextSunday = Carbon::now()->isSunday()
            ? Carbon::now()->startOfDay()
            : Carbon::now()->next(Carbon::SUNDAY);

        for ($i = 0; $i < $count; $i++) {
            $date = $nextSunday->copy()->addWeeks($i);

            if (!SundayMeal::where('meal_date', $date->toDateString())->exists()) {
                SundayMeal::create([
                    'meal_date' => $date->toDateString(),
                    'max_capacity' => self::DEFAULT_CAPACITY,
                    'is_open' => true,
                    'allow_multiple_places' => self::DEFAULT_ALLOW_MULTIPLE_PLACES,
                    'max_places_per_user' => self::DEFAULT_MAX_PLACES_PER_USER,
                    'allow_guests' => self::DEFAULT_ALLOW_GUESTS,
                    'max_guests_per_user' => self::DEFAULT_MAX_GUESTS_PER_USER,
                ]);
                $createdCount++;

                Log::info('Repas du dimanche créé automatiquement', [
                    'date' => $date->toDateString(),
                ]);
            }
        }

        Log::info('Génération automatique des repas terminée', [
            'requested' => $count,
            'created' => $createdCount,
        ]);

        return $createdCount;
    }
}

