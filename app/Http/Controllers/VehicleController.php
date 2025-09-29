<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    /**
     * Display a listing of vehicles.
     */
    public function index()
    {
        $vehicles = Vehicle::withCount(['reservations' => function ($query) {
            $query->where('reservation_date', '>=', now()->toDateString())
                  ->whereIn('status', ['pending', 'approved']);
        }])->get();

        return view('vehicles.index', compact('vehicles'));
    }

    /**
     * Show the form for creating a new vehicle.
     */
    public function create()
    {

        $vehicleTypes = VehicleType::active()->orderBy('name')->get();
    
        return view('vehicles.create', compact('vehicleTypes'));
    }

    /**
     * Store a newly created vehicle.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'vehicle_type_id' => 'required|exists:vehicle_types,id',

            'license_plate' => ['nullable', 'string', 'max:20', 'unique:vehicles'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:50'],
            'description' => ['nullable', 'string'],
            'is_available' => ['boolean'],
        ]);

        $validated['is_available'] = $request->boolean('is_available', true);
        $validated['capacity'] = $validated['capacity'] ?? 5; // Default 5 places

        Vehicle::create($validated);

        return redirect()->route('vehicles.index')
            ->with('success', 'Vehicule cree avec succes.');
    }

    /**
     * Display the specified vehicle.
     */
    public function show(Vehicle $vehicle)
    {
        $vehicle->load(['type','reservations' => function ($query) {
            $query->with('user')
                  ->where('reservation_date', '>=', now()->toDateString())
                  ->orderBy('reservation_date')
                  ->orderBy('start_time');
        }]);

        return view('vehicles.show', compact('vehicle'));
    }

    /**
     * Show the form for editing the specified vehicle.
     */
    public function edit(Vehicle $vehicle)
    {
        $vehicleTypes = VehicleType::active()->orderBy('name')->get();
        
        return view('vehicles.edit', compact('vehicle', 'vehicleTypes'));
    }

    /**
     * Update the specified vehicle.
     */
    public function update(Request $request, Vehicle $vehicle)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'license_plate' => ['nullable', 'string', 'max:20', 'unique:vehicles,license_plate,' . $vehicle->id],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:50'],
            'description' => ['nullable', 'string'],
            'is_available' => ['boolean'],
            'vehicle_type_id' => 'required|exists:vehicle_types,id',

        ]);

        $validated['is_available'] = $request->boolean('is_available', true);
        $validated['capacity'] = $validated['capacity'] ?? $vehicle->capacity ?? 5;

        $vehicle->update($validated);

        return redirect()->route('vehicles.index')
            ->with('success', 'Vehicule mis a jour avec succes.');
    }

    /**
     * Remove the specified vehicle.
     */
    public function destroy(Vehicle $vehicle)
    {
        // Check if vehicle has future reservations
        $hasReservations = $vehicle->reservations()
            ->where('reservation_date', '>=', now()->toDateString())
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($hasReservations) {
            return back()->with('error', 'Impossible de supprimer un vehicule avec des reservations a venir.');
        }

        $vehicle->delete();

        return redirect()->route('vehicles.index')
            ->with('success', 'Vehicule supprime avec succes.');
    }

    /**
     * Toggle vehicle availability.
     */
    public function toggleAvailability(Vehicle $vehicle)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        $vehicle->update(['is_available' => !$vehicle->is_available]);

        $status = $vehicle->is_available ? 'disponible' : 'indisponible';

        return back()->with('success', "Vehicule {$vehicle->name} est maintenant {$status}.");
    }
}

