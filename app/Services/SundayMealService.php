<?php

namespace App\Services;

use App\Models\SundayMeal;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Fermeture et generation automatiques des repas du dimanche (commandes meals:close
 * et meals:generate). Journalisation dans le canal `app` : `meal.close.*`,
 * `meal.generate.*` ; un echec de generation declenche l'alerte `meal_generation_failure`.
 */
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

        try {
            $closedCount = SundayMeal::where('meal_date', $tomorrow)
                ->where('is_open', true)
                ->update(['is_open' => false]);

            if ($closedCount === 0 && ! SundayMeal::where('meal_date', $tomorrow)->exists()) {
                // Aucun repas pour demain : la generation n'a pas tourne ou le repas a ete supprime.
                Log::channel('app')->warning('meal.close.missing', ['date' => $tomorrow]);
            }
        } catch (Throwable $e) {
            Log::channel('app')->error('meal.close.failed', [
                'date' => $tomorrow,
                'exception_class' => get_class($e),
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);

            // Les inscriptions resteraient ouvertes : la commande doit echouer visiblement.
            throw $e;
        }

        Log::channel('app')->info('meal.close.completed', [
            'date' => $tomorrow,
            'count' => $closedCount,
        ]);

        return $closedCount;
    }

    /**
     * Générer les repas pour les N prochains dimanches.
     * Ne crée pas de repas si un repas existe déjà pour cette date.
     * Un dimanche en échec est journalisé et n'empêche pas de générer les suivants.
     *
     * @param  int  $count  Nombre de dimanches à générer (défaut: 4)
     * @return int Nombre de repas créés
     */
    public function generateUpcomingSundays(int $count = 4): int
    {
        if ($count < 1) {
            Log::channel('app')->warning('meal.generate.invalid_count', ['requested' => $count]);

            return 0;
        }

        $createdCount = 0;
        $failedDates = [];
        $nextSunday = Carbon::now()->isSunday()
            ? Carbon::now()->startOfDay()
            : Carbon::now()->next(Carbon::SUNDAY);

        for ($i = 0; $i < $count; $i++) {
            $date = $nextSunday->copy()->addWeeks($i)->toDateString();

            try {
                if (SundayMeal::where('meal_date', $date)->exists()) {
                    continue;
                }

                $meal = SundayMeal::create([
                    'meal_date' => $date,
                    'max_capacity' => self::DEFAULT_CAPACITY,
                    'is_open' => true,
                    'allow_multiple_places' => self::DEFAULT_ALLOW_MULTIPLE_PLACES,
                    'max_places_per_user' => self::DEFAULT_MAX_PLACES_PER_USER,
                    'allow_guests' => self::DEFAULT_ALLOW_GUESTS,
                    'max_guests_per_user' => self::DEFAULT_MAX_GUESTS_PER_USER,
                ]);
                $createdCount++;

                Log::channel('app')->info('meal.generate.created', [
                    'meal_id' => $meal->id,
                    'date' => $date,
                ]);
            } catch (Throwable $e) {
                $failedDates[] = $date;

                Log::channel('app')->error('meal.generate.failed', [
                    'date' => $date,
                    'exception_class' => get_class($e),
                    'error' => $e->getMessage(),
                    'exception' => $e,
                ]);
            }
        }

        $summary = [
            'requested' => $count,
            'created' => $createdCount,
            'failed' => count($failedDates),
        ];

        if ($failedDates !== []) {
            Log::channel('app')->warning('meal.generate.incomplete', $summary + ['failed_dates' => $failedDates]);

            try {
                app(AlertService::class)->send(
                    'meal_generation_failure',
                    'La génération automatique des repas du dimanche a échoué pour au moins une date.',
                    $summary + ['failed_dates' => implode(', ', $failedDates)],
                );
            } catch (Throwable) {
                // L'echec est deja journalise ci-dessus ; l'alerte ne doit pas faire planter la commande.
            }
        } else {
            Log::channel('app')->info('meal.generate.completed', $summary);
        }

        return $createdCount;
    }
}
