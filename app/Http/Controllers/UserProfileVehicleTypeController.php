<?php

namespace App\Http\Controllers;

use App\Models\UserProfile;
use App\Models\VehicleType;
use App\Traits\LogsErrors;
use Illuminate\Http\Request;
use Throwable;

class UserProfileVehicleTypeController extends Controller
{
    use LogsErrors;

    public function index()
    {
        // Récupérer tous les profils avec leurs types de véhicules
        $userProfiles = UserProfile::with(['vehicleTypes' => function ($query) {
            $query->withPivot('is_active')->orderBy('name');
        }])
            ->withCount('users')
            ->orderBy('name')
            ->get();

        // Récupérer tous les types de véhicules actifs
        $vehicleTypes = VehicleType::active()->orderBy('name')->get();

        return view('user-profile-vehicle-types.index', compact('userProfiles', 'vehicleTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_profile_id' => 'required|exists:user_profiles,id',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
        ]);

        $context = [
            'user_profile_id' => (int) $validated['user_profile_id'],
            'vehicle_type_id' => (int) $validated['vehicle_type_id'],
        ];

        $userProfile = UserProfile::findOrFail($validated['user_profile_id']);

        // Vérifier si la relation n'existe pas déjà
        if ($userProfile->vehicleTypes()->where('vehicle_type_id', $validated['vehicle_type_id'])->exists()) {
            $this->logWarning('profile_vehicle_type.store.refused', $context + ['reason' => 'already_attached']);

            return redirect()->back()->with('error', 'Ce type de véhicule est déjà associé à ce profil.');
        }

        // Attacher le type de véhicule
        try {
            $userProfile->vehicleTypes()->attach($validated['vehicle_type_id'], [
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (Throwable $e) {
            $this->logError('profile_vehicle_type.store.failed', $e, $context);

            return redirect()->back()->with('error', 'Le type de véhicule n\'a pas pu être associé. Veuillez réessayer.');
        }

        // Droits de réservation par profil : pas d'ActivityLog, trace d'administration dans le canal audit.
        $this->logInfo('profile_vehicle_type.attached', $context, 'audit');

        return redirect()->back()->with('success', 'Type de véhicule associé avec succès.');
    }

    public function update(Request $request, UserProfile $userProfile, VehicleType $vehicleType)
    {
        $validated = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $context = ['user_profile_id' => $userProfile->id, 'vehicle_type_id' => $vehicleType->id];

        // Vérifier que la relation existe
        if (! $userProfile->vehicleTypes()->where('vehicle_type_id', $vehicleType->id)->exists()) {
            $this->logWarning('profile_vehicle_type.update.refused', $context + ['reason' => 'not_attached']);

            return redirect()->back()->with('error', 'Ce type de véhicule n\'est pas associé à ce profil.');
        }

        // Mettre à jour le pivot
        try {
            $userProfile->vehicleTypes()->updateExistingPivot($vehicleType->id, [
                'is_active' => $validated['is_active'],
                'updated_at' => now(),
            ]);
        } catch (Throwable $e) {
            $this->logError('profile_vehicle_type.update.failed', $e, $context);

            return redirect()->back()->with('error', 'Le statut n\'a pas pu être mis à jour. Veuillez réessayer.');
        }

        $this->logInfo('profile_vehicle_type.updated', $context + ['is_active' => (bool) $validated['is_active']], 'audit');

        return redirect()->back()->with('success', 'Statut mis à jour avec succès.');
    }

    public function destroy(UserProfile $userProfile, VehicleType $vehicleType)
    {
        $context = ['user_profile_id' => $userProfile->id, 'vehicle_type_id' => $vehicleType->id];

        // Vérifier que la relation existe
        if (! $userProfile->vehicleTypes()->where('vehicle_type_id', $vehicleType->id)->exists()) {
            $this->logWarning('profile_vehicle_type.destroy.refused', $context + ['reason' => 'not_attached']);

            return redirect()->back()->with('error', 'Ce type de véhicule n\'est pas associé à ce profil.');
        }

        // Détacher le type de véhicule
        try {
            $userProfile->vehicleTypes()->detach($vehicleType->id);
        } catch (Throwable $e) {
            $this->logError('profile_vehicle_type.destroy.failed', $e, $context);

            return redirect()->back()->with('error', 'Le type de véhicule n\'a pas pu être dissocié. Veuillez réessayer.');
        }

        $this->logInfo('profile_vehicle_type.detached', $context, 'audit');

        return redirect()->back()->with('success', 'Type de véhicule dissocié avec succès.');
    }
}
