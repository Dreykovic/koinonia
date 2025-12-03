<?php

namespace App\Http\Controllers;

use App\Models\UserProfile;
use App\Models\VehicleType;
use Illuminate\Http\Request;

class UserProfileVehicleTypeController extends Controller
{
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

        $userProfile = UserProfile::findOrFail($validated['user_profile_id']);

        // Vérifier si la relation n'existe pas déjà
        if ($userProfile->vehicleTypes()->where('vehicle_type_id', $validated['vehicle_type_id'])->exists()) {
            return redirect()->back()->with('error', 'Ce type de véhicule est déjà associé à ce profil.');
        }

        // Attacher le type de véhicule
        $userProfile->vehicleTypes()->attach($validated['vehicle_type_id'], [
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Type de véhicule associé avec succès.');
    }

    public function update(Request $request, UserProfile $userProfile, VehicleType $vehicleType)
    {
        $validated = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        // Vérifier que la relation existe
        if (!$userProfile->vehicleTypes()->where('vehicle_type_id', $vehicleType->id)->exists()) {
            return redirect()->back()->with('error', 'Ce type de véhicule n\'est pas associé à ce profil.');
        }

        // Mettre à jour le pivot
        $userProfile->vehicleTypes()->updateExistingPivot($vehicleType->id, [
            'is_active' => $validated['is_active'],
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Statut mis à jour avec succès.');
    }

    public function destroy(UserProfile $userProfile, VehicleType $vehicleType)
    {
        // Vérifier que la relation existe
        if (!$userProfile->vehicleTypes()->where('vehicle_type_id', $vehicleType->id)->exists()) {
            return redirect()->back()->with('error', 'Ce type de véhicule n\'est pas associé à ce profil.');
        }

        // Détacher le type de véhicule
        $userProfile->vehicleTypes()->detach($vehicleType->id);

        return redirect()->back()->with('success', 'Type de véhicule dissocié avec succès.');
    }
}