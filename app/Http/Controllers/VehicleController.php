<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\VehicleType;
use App\Traits\LogsErrors;
use Illuminate\Http\Request;
use Throwable;

class VehicleController extends Controller
{
    use LogsErrors;

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

        try {
            $vehicle = Vehicle::create($validated);
        } catch (Throwable $e) {
            $this->logHandledError('vehicle.store.failed', $e, ['vehicle_type_id' => $validated['vehicle_type_id']]);

            return back()->withInput()->with('error', 'Le vehicule n\'a pas pu etre cree. Reessayez.');
        }

        $this->logInfo('vehicle.created', $this->vehicleContext($vehicle), 'audit');

        return redirect()->route('vehicles.index')
            ->with('success', 'Vehicule cree avec succes.');
    }

    /**
     * Display the specified vehicle.
     */
    public function show(Vehicle $vehicle)
    {
        // Pas de fiche de consultation dediee : la fiche d'edition en tient lieu.
        return redirect()->route('vehicles.edit', $vehicle);
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
            'license_plate' => ['nullable', 'string', 'max:20', 'unique:vehicles,license_plate,'.$vehicle->id],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:50'],
            'description' => ['nullable', 'string'],
            'is_available' => ['boolean'],
            'vehicle_type_id' => 'required|exists:vehicle_types,id',

        ]);

        $validated['is_available'] = $request->boolean('is_available', true);
        $validated['capacity'] = $validated['capacity'] ?? $vehicle->capacity ?? 5;

        try {
            $vehicle->update($validated);
        } catch (Throwable $e) {
            $this->logHandledError('vehicle.update.failed', $e, $this->vehicleContext($vehicle));

            return back()->withInput()->with('error', 'Le vehicule n\'a pas pu etre mis a jour. Reessayez.');
        }

        $this->logInfo('vehicle.updated', $this->vehicleContext($vehicle) + [
            'fields' => array_keys($vehicle->getChanges()),
        ], 'audit');

        return redirect()->route('vehicles.index')
            ->with('success', 'Vehicule mis a jour avec succes.');
    }

    /**
     * Remove the specified vehicle.
     */
    public function destroy(Vehicle $vehicle)
    {
        // Check if vehicle has future reservations
        $futureReservations = $vehicle->reservations()
            ->where('reservation_date', '>=', now()->toDateString())
            ->whereIn('status', ['pending', 'approved'])
            ->count();

        if ($futureReservations > 0) {
            $this->logWarning('vehicle.delete.refused', $this->vehicleContext($vehicle) + [
                'reason' => 'future_reservations',
                'future_reservations' => $futureReservations,
            ]);

            return back()->with('error', 'Impossible de supprimer un vehicule avec des reservations a venir.');
        }

        $context = $this->vehicleContext($vehicle);

        try {
            $vehicle->delete();
        } catch (Throwable $e) {
            $this->logHandledError('vehicle.delete.failed', $e, $context);

            return back()->with('error', 'Le vehicule n\'a pas pu etre supprime. Reessayez.');
        }

        $this->logInfo('vehicle.deleted', $context, 'audit');

        return redirect()->route('vehicles.index')
            ->with('success', 'Vehicule supprime avec succes.');
    }

    /**
     * Toggle vehicle availability.
     */
    public function toggleAvailability(Vehicle $vehicle)
    {
        if (! auth()->user()->isAdmin()) {
            $this->logWarning('vehicle.access.denied', $this->vehicleContext($vehicle) + ['action' => 'toggle_availability'], 'security');
            abort(403);
        }

        try {
            $vehicle->update(['is_available' => ! $vehicle->is_available]);
        } catch (Throwable $e) {
            $this->logHandledError('vehicle.availability.failed', $e, $this->vehicleContext($vehicle));

            return back()->with('error', 'La disponibilite du vehicule n\'a pas pu etre modifiee. Reessayez.');
        }

        $this->logInfo('vehicle.availability.toggled', $this->vehicleContext($vehicle), 'audit');

        $status = $vehicle->is_available ? 'disponible' : 'indisponible';

        return back()->with('success', "Vehicule {$vehicle->name} est maintenant {$status}.");
    }

    /**
     * Contexte metier d'un vehicule pour les logs.
     */
    private function vehicleContext(Vehicle $vehicle): array
    {
        return [
            'vehicle_id' => $vehicle->id,
            'vehicle_name' => $vehicle->name,
            'vehicle_type_id' => $vehicle->vehicle_type_id,
            'is_available' => $vehicle->is_available,
        ];
    }
}
