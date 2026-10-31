<?php

namespace App\Http\Controllers;

use App\Events\MealInscriptionChanged;
use App\Http\Controllers\Concerns\GuardsMealMenuActions;
use App\Models\MealInscription;
use App\Models\SundayMeal;
use App\Traits\BroadcastsSafely;
use Illuminate\Http\Request;

class MealInscriptionController extends Controller
{
    use BroadcastsSafely, GuardsMealMenuActions;

    /**
     * Register for a meal.
     */
    public function register(Request $request, SundayMeal $meal)
    {
        $user = auth()->user();

        // Check if meal is open
        if (! $meal->canRegister()) {
            $this->logRefusal('closed', $meal);

            return back()->with('error', 'Les inscriptions sont fermees pour ce repas.');
        }

        // Check if already registered
        $existingInscription = MealInscription::where('sunday_meal_id', $meal->id)
            ->where('user_id', $user->id)
            ->whereNull('cancelled_at')
            ->first();

        if ($existingInscription) {
            $this->logRefusal('already_registered', $meal);

            return back()->with('error', 'Vous etes deja inscrit a ce repas.');
        }

        // Get meal limits
        $maxPlaces = $meal->getMaxPlacesAllowed();
        $maxGuests = $meal->getMaxGuestsAllowed();

        $validated = $request->validate([
            'places_count' => ['nullable', 'integer', 'min:1', 'max:'.$maxPlaces],
            'guests_count' => ['nullable', 'integer', 'min:0', 'max:'.$maxGuests],
            'guest_names' => ['nullable', 'array'],
            'guest_names.*' => ['nullable', 'string', 'max:100'],
        ]);

        // Check capacity - places_count defaults to 1, guests_count to 0
        $placesCount = $validated['places_count'] ?? 1;
        $guestsCount = $validated['guests_count'] ?? 0;
        $context = ['meal_id' => $meal->id, 'places_count' => $placesCount, 'guests_count' => $guestsCount];

        $result = $this->guardAction('meal.inscription.store', $context, function () use ($meal, $user, $validated, $placesCount, $guestsCount, $context) {
            $totalNeeded = $placesCount + $guestsCount;
            if ($meal->remainingSlots() < $totalNeeded) {
                $this->logRefusal('not_enough_places', $meal, ['requested' => $totalNeeded, 'remaining' => $meal->remainingSlots()]);

                return back()->with('error', 'Il n\'y a pas assez de places disponibles.');
            }

            // Check if there was a cancelled inscription
            $cancelledInscription = MealInscription::where('sunday_meal_id', $meal->id)
                ->where('user_id', $user->id)
                ->whereNotNull('cancelled_at')
                ->first();

            // Filter guest names - remove empty values
            $guestNames = isset($validated['guest_names'])
                ? array_values(array_filter($validated['guest_names']))
                : null;

            if ($cancelledInscription) {
                $cancelledInscription->update([
                    'places_count' => $placesCount,
                    'guests_count' => $guestsCount,
                    'guest_names' => $guestNames ?: null,
                    'registered_at' => now(),
                    'cancelled_at' => null,
                ]);
                $inscription = $cancelledInscription;
            } else {
                $inscription = MealInscription::create([
                    'sunday_meal_id' => $meal->id,
                    'user_id' => $user->id,
                    'places_count' => $placesCount,
                    'guests_count' => $guestsCount,
                    'guest_names' => $guestNames ?: null,
                    'registered_at' => now(),
                ]);
            }

            // Jamais les noms des invites dans les logs : seulement leur nombre.
            $this->logInfo('meal.inscription.created', $context + [
                'inscription_id' => $inscription->id,
                'reactivated' => $cancelledInscription !== null,
            ]);

            return null;
        }, 'Votre inscription n\'a pas pu etre enregistree. Reessayez dans quelques instants.');

        if ($result !== null) {
            return $result;
        }

        $this->broadcastChange($meal, 'inscribed');

        return back()->with('success', 'Inscription enregistree avec succes.');
    }

    /**
     * Update an inscription.
     */
    public function update(Request $request, SundayMeal $meal)
    {
        $user = auth()->user();

        // Check if meal is open
        if (! $meal->canRegister()) {
            $this->logRefusal('closed', $meal, ['operation' => 'update']);

            return back()->with('error', 'Les modifications ne sont plus possibles.');
        }

        $inscription = MealInscription::where('sunday_meal_id', $meal->id)
            ->where('user_id', $user->id)
            ->whereNull('cancelled_at')
            ->firstOrFail();

        // Get meal limits
        $maxPlaces = $meal->getMaxPlacesAllowed();
        $maxGuests = $meal->getMaxGuestsAllowed();

        $validated = $request->validate([
            'places_count' => ['nullable', 'integer', 'min:1', 'max:'.$maxPlaces],
            'guests_count' => ['nullable', 'integer', 'min:0', 'max:'.$maxGuests],
            'guest_names' => ['nullable', 'array'],
            'guest_names.*' => ['nullable', 'string', 'max:100'],
        ]);

        // Check capacity (excluding current inscription) - places_count defaults to 1, guests_count to 0
        $placesCount = $validated['places_count'] ?? 1;
        $guestsCount = $validated['guests_count'] ?? 0;
        $context = [
            'meal_id' => $meal->id,
            'inscription_id' => $inscription->id,
            'places_count' => $placesCount,
            'guests_count' => $guestsCount,
        ];

        $result = $this->guardAction('meal.inscription.update', $context, function () use ($meal, $inscription, $validated, $placesCount, $guestsCount, $context) {
            $currentPersons = $inscription->totalPersons();
            $newPersons = $placesCount + $guestsCount;
            $difference = $newPersons - $currentPersons;

            if ($difference > 0 && $meal->remainingSlots() < $difference) {
                $this->logRefusal('not_enough_places', $meal, [
                    'operation' => 'update',
                    'requested' => $difference,
                    'remaining' => $meal->remainingSlots(),
                ]);

                return back()->with('error', 'Il n\'y a pas assez de places disponibles.');
            }

            // Filter guest names - remove empty values
            $guestNames = isset($validated['guest_names'])
                ? array_values(array_filter($validated['guest_names']))
                : null;

            $inscription->update([
                'places_count' => $placesCount,
                'guests_count' => $guestsCount,
                'guest_names' => $guestNames ?: null,
            ]);

            $this->logInfo('meal.inscription.updated', $context + ['previous_persons' => $currentPersons]);

            return null;
        }, 'Votre inscription n\'a pas pu etre modifiee. Reessayez dans quelques instants.');

        if ($result !== null) {
            return $result;
        }

        $this->broadcastChange($meal, 'updated');

        return back()->with('success', 'Inscription mise a jour avec succes.');
    }

    /**
     * Cancel an inscription.
     */
    public function unregister(SundayMeal $meal)
    {
        $user = auth()->user();

        // Check if meal is open
        if (! $meal->canRegister()) {
            $this->logRefusal('closed', $meal, ['operation' => 'cancel']);

            return back()->with('error', 'Les annulations ne sont plus possibles.');
        }

        $inscription = MealInscription::where('sunday_meal_id', $meal->id)
            ->where('user_id', $user->id)
            ->whereNull('cancelled_at')
            ->first();

        if (! $inscription) {
            $this->logRefusal('not_registered', $meal, ['operation' => 'cancel']);

            return back()->with('error', 'Vous n\'etes pas inscrit a ce repas.');
        }

        $context = ['meal_id' => $meal->id, 'inscription_id' => $inscription->id];

        $result = $this->guardAction('meal.inscription.cancel', $context, function () use ($inscription, $context) {
            $inscription->update(['cancelled_at' => now()]);

            $this->logInfo('meal.inscription.cancelled', $context);

            return null;
        }, 'Votre inscription n\'a pas pu etre annulee. Reessayez dans quelques instants.');

        if ($result !== null) {
            return $result;
        }

        $this->broadcastChange($meal, 'cancelled');

        return back()->with('success', 'Inscription annulee avec succes.');
    }

    /**
     * Diffuse le changement aux autres membres. Un echec (Pusher, file) est journalise
     * par broadcastSafely sans annuler l'inscription : l'interface retombe sur le polling.
     */
    private function broadcastChange(SundayMeal $meal, string $action): void
    {
        // L'inscription est deja enregistree : un rechargement en echec ne doit pas la faire paraitre ratee.
        $fresh = rescue(fn () => $meal->fresh(), null, false) ?? $meal;

        $this->broadcastSafely(
            new MealInscriptionChanged($fresh, $action),
            ['meal_id' => $meal->id, 'action' => $action],
            true,
        );
    }

    /**
     * Refus metier (repas ferme, complet, deja inscrit) : normal, trace en info pour
     * pouvoir repondre a un membre qui dit ne pas avoir pu s'inscrire.
     */
    private function logRefusal(string $reason, SundayMeal $meal, array $context = []): void
    {
        $this->logInfo('meal.inscription.refused', ['reason' => $reason, 'meal_id' => $meal->id] + $context);
    }
}
