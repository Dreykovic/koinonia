<?php

namespace App\Http\Controllers;

use App\Events\MealInscriptionChanged;
use App\Models\MealInscription;
use App\Models\SundayMeal;
use Illuminate\Http\Request;

class MealInscriptionController extends Controller
{
    /**
     * Register for a meal.
     */
    public function register(Request $request, SundayMeal $meal)
    {
        $user = auth()->user();

        // Check if meal is open
        if (!$meal->canRegister()) {
            return back()->with('error', 'Les inscriptions sont fermees pour ce repas.');
        }

        // Check if already registered
        $existingInscription = MealInscription::where('sunday_meal_id', $meal->id)
            ->where('user_id', $user->id)
            ->whereNull('cancelled_at')
            ->first();

        if ($existingInscription) {
            return back()->with('error', 'Vous etes deja inscrit a ce repas.');
        }

        // Get meal limits
        $maxPlaces = $meal->getMaxPlacesAllowed();
        $maxGuests = $meal->getMaxGuestsAllowed();

        $validated = $request->validate([
            'places_count' => ['nullable', 'integer', 'min:1', 'max:' . $maxPlaces],
            'guests_count' => ['nullable', 'integer', 'min:0', 'max:' . $maxGuests],
            'guest_names' => ['nullable', 'array'],
            'guest_names.*' => ['nullable', 'string', 'max:100'],
        ]);

        // Check capacity - places_count defaults to 1, guests_count to 0
        $placesCount = $validated['places_count'] ?? 1;
        $guestsCount = $validated['guests_count'] ?? 0;
        $totalNeeded = $placesCount + $guestsCount;
        if ($meal->remainingSlots() < $totalNeeded) {
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
        } else {
            MealInscription::create([
                'sunday_meal_id' => $meal->id,
                'user_id' => $user->id,
                'places_count' => $placesCount,
                'guests_count' => $guestsCount,
                'guest_names' => $guestNames ?: null,
                'registered_at' => now(),
            ]);
        }

        // Broadcast to other users
        broadcast(new MealInscriptionChanged($meal->fresh(), 'inscribed'))->toOthers();

        return back()->with('success', 'Inscription enregistree avec succes.');
    }

    /**
     * Update an inscription.
     */
    public function update(Request $request, SundayMeal $meal)
    {
        $user = auth()->user();

        // Check if meal is open
        if (!$meal->canRegister()) {
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
            'places_count' => ['nullable', 'integer', 'min:1', 'max:' . $maxPlaces],
            'guests_count' => ['nullable', 'integer', 'min:0', 'max:' . $maxGuests],
            'guest_names' => ['nullable', 'array'],
            'guest_names.*' => ['nullable', 'string', 'max:100'],
        ]);

        // Check capacity (excluding current inscription) - places_count defaults to 1, guests_count to 0
        $placesCount = $validated['places_count'] ?? 1;
        $guestsCount = $validated['guests_count'] ?? 0;
        $currentPersons = $inscription->totalPersons();
        $newPersons = $placesCount + $guestsCount;
        $difference = $newPersons - $currentPersons;

        if ($difference > 0 && $meal->remainingSlots() < $difference) {
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

        // Broadcast to other users
        broadcast(new MealInscriptionChanged($meal->fresh(), 'updated'))->toOthers();

        return back()->with('success', 'Inscription mise a jour avec succes.');
    }

    /**
     * Cancel an inscription.
     */
    public function unregister(SundayMeal $meal)
    {
        $user = auth()->user();

        // Check if meal is open
        if (!$meal->canRegister()) {
            return back()->with('error', 'Les annulations ne sont plus possibles.');
        }

        $inscription = MealInscription::where('sunday_meal_id', $meal->id)
            ->where('user_id', $user->id)
            ->whereNull('cancelled_at')
            ->first();

        if (!$inscription) {
            return back()->with('error', 'Vous n\'etes pas inscrit a ce repas.');
        }

        $inscription->update(['cancelled_at' => now()]);

        // Broadcast to other users
        broadcast(new MealInscriptionChanged($meal->fresh(), 'cancelled'))->toOthers();

        return back()->with('success', 'Inscription annulee avec succes.');
    }
}
