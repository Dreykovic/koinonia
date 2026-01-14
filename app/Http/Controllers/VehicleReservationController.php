<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\VehicleReservation;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class VehicleReservationController extends Controller
{
    /**
     * Show the vehicle planning page.
     */
    public function planning()
    {
        $user = auth()->user();

        // Get all vehicles (not just available ones, so we can show them as disabled for non-accessible)
        $vehicles = Vehicle::all();

        return view('vehicles.planning', compact('vehicles'));
    }

    /**
     * Get vehicles accessible to the authenticated user.
     */
    public function getVehicles()
    {
        $user = auth()->user();

        $vehicles = Vehicle::available()->get()->filter(function ($vehicle) use ($user) {
            return $user->canAccessVehicle($vehicle);
        })->values();

        return response()->json($vehicles);
    }

    /**
     * Get reservations for a specific date.
     */
    public function getReservations(Request $request)
    {
        $date = $request->get('date', now()->toDateString());
        $user = auth()->user();

        // Get all reservations for the date (we filter access in the frontend)
        $reservations = VehicleReservation::with(['vehicle', 'user:id,name'])
            ->where('reservation_date', $date)
            ->whereIn('status', ['pending', 'approved'])
            ->get()
            ->map(function ($reservation) use ($user) {
                return [
                    'id' => $reservation->id,
                    'vehicle_id' => $reservation->vehicle_id,
                    'vehicle_name' => $reservation->vehicle->name,
                    'user_id' => $reservation->user_id,
                    'user_name' => $reservation->user->name,
                    'start_time' => $reservation->start_time,
                    'end_time' => $reservation->end_time,
                    'destination' => $reservation->destination,
                    'status' => $reservation->status,
                    'notes' => $reservation->notes,
                    'can_edit' => $user->id === $reservation->user_id || $user->isAdmin(),
                    'can_cancel' => $reservation->canBeCancelled() && ($user->id === $reservation->user_id || $user->isAdmin()),
                ];
            });

        return response()->json($reservations);
    }

    /**
     * Get statistics for a specific date.
     */
    public function getStats(Request $request)
    {
        $date = $request->get('date', now()->toDateString());
        $user = auth()->user();

        // Get vehicles accessible to user
        $accessibleVehicles = Vehicle::all()->filter(function ($vehicle) use ($user) {
            return $user->canAccessVehicle($vehicle);
        });

        $accessibleVehicleIds = $accessibleVehicles->pluck('id');

        $reservationsToday = VehicleReservation::whereIn('vehicle_id', $accessibleVehicleIds)
            ->where('reservation_date', $date)
            ->whereIn('status', ['pending', 'approved'])
            ->count();

        return response()->json([
            'total_vehicles' => $accessibleVehicles->count(),
            'reservations_today' => $reservationsToday,
        ]);
    }

    /**
     * Store a new reservation.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'reservation_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'destination' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $user = auth()->user();
        $vehicle = Vehicle::findOrFail($validated['vehicle_id']);

        // Check access
        if (!$user->canAccessVehicle($vehicle)) {
            return response()->json([
                'message' => 'Vous n\'avez pas acces a ce vehicule.',
            ], 403);
        }

        // Check availability
        if (!$vehicle->isAvailableAt($validated['reservation_date'], $validated['start_time'], $validated['end_time'])) {
            return response()->json([
                'message' => 'Ce creneau n\'est pas disponible.',
            ], 422);
        }

        $reservation = VehicleReservation::create([
            'vehicle_id' => $validated['vehicle_id'],
            'user_id' => $user->id,
            'reservation_date' => $validated['reservation_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'destination' => $validated['destination'],
            'notes' => $validated['notes'] ?? null,
            'status' => 'approved', // Auto-approved for simplicity
        ]);

        return response()->json([
            'message' => 'Reservation creee avec succes.',
            'reservation' => $reservation->load('vehicle', 'user:id,name'),
        ]);
    }

    /**
     * Update a reservation.
     */
    public function update(Request $request, $id)
    {
        $reservation = VehicleReservation::findOrFail($id);
        $user = auth()->user();

        // Check ownership or admin
        if ($reservation->user_id !== $user->id && !$user->isAdmin()) {
            return response()->json([
                'message' => 'Vous n\'etes pas autorise a modifier cette reservation.',
            ], 403);
        }

        $validated = $request->validate([
            'start_time' => ['sometimes', 'date_format:H:i'],
            'end_time' => ['sometimes', 'date_format:H:i', 'after:start_time'],
            'destination' => ['sometimes', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        // Check availability (excluding current reservation) only if times are being changed
        if (isset($validated['start_time']) || isset($validated['end_time'])) {
            $startTime = $validated['start_time'] ?? $reservation->start_time;
            $endTime = $validated['end_time'] ?? $reservation->end_time;

            if (!$reservation->vehicle->isAvailableAt(
                $reservation->reservation_date->format('Y-m-d'),
                $startTime,
                $endTime,
                $reservation->id
            )) {
                return response()->json([
                    'message' => 'Ce creneau n\'est pas disponible.',
                ], 422);
            }
        }

        $reservation->update($validated);

        return response()->json([
            'message' => 'Reservation mise a jour avec succes.',
            'reservation' => $reservation->fresh()->load('vehicle', 'user:id,name'),
        ]);
    }

    /**
     * Cancel a reservation.
     */
    public function destroy($id)
    {
        $reservation = VehicleReservation::findOrFail($id);
        $user = auth()->user();

        // Check ownership or admin
        if ($reservation->user_id !== $user->id && !$user->isAdmin()) {
            return response()->json([
                'message' => 'Vous n\'etes pas autorise a annuler cette reservation.',
            ], 403);
        }

        // Check if can be cancelled
        if (!$reservation->canBeCancelled()) {
            return response()->json([
                'message' => 'Cette reservation ne peut plus etre annulee (moins de 2h avant le debut).',
            ], 422);
        }

        $reservation->update(['status' => 'cancelled']);

        return response()->json([
            'message' => 'Reservation annulee avec succes.',
        ]);
    }

    /**
     * Display a listing of reservations.
     */
    public function index()
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            $reservations = VehicleReservation::with(['vehicle', 'user:id,name'])
                ->orderBy('reservation_date', 'desc')
                ->orderBy('start_time', 'desc')
                ->paginate(20);
        } else {
            $reservations = VehicleReservation::with('vehicle')
                ->where('user_id', $user->id)
                ->orderBy('reservation_date', 'desc')
                ->orderBy('start_time', 'desc')
                ->paginate(20);
        }

        return view('reservations.index', compact('reservations'));
    }

    /**
     * Show the form for creating a new reservation.
     */
    public function create()
    {
        $user = auth()->user();

        $vehicles = Vehicle::available()->get()->filter(function ($vehicle) use ($user) {
            return $user->canAccessVehicle($vehicle);
        })->values();

        return view('reservations.create', compact('vehicles'));
    }

    /**
     * Display the specified reservation.
     */
    public function show(VehicleReservation $reservation)
    {
        $user = auth()->user();

        if ($reservation->user_id !== $user->id && !$user->isAdmin()) {
            abort(403);
        }

        $reservation->load(['vehicle', 'user', 'approver']);

        return view('reservations.show', compact('reservation'));
    }

    /**
     * Show the form for editing the specified reservation.
     */
    public function edit(VehicleReservation $reservation)
    {
        $user = auth()->user();

        if ($reservation->user_id !== $user->id && !$user->isAdmin()) {
            abort(403);
        }

        $vehicles = Vehicle::available()->get()->filter(function ($vehicle) use ($user) {
            return $user->canAccessVehicle($vehicle);
        })->values();

        return view('reservations.edit', compact('reservation', 'vehicles'));
    }

    /**
     * Approve a reservation (admin only).
     */
    public function approve(VehicleReservation $reservation)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        $reservation->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
        ]);

        return back()->with('success', 'Reservation approuvee avec succes.');
    }

    /**
     * Reject a reservation (admin only).
     */
    public function reject(VehicleReservation $reservation)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        $reservation->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
        ]);

        return back()->with('success', 'Reservation rejetee.');
    }

    /**
     * API: Store a new reservation.
     */
    public function apiStore(Request $request)
    {
        return $this->store($request);
    }

    /**
     * API: Update a reservation.
     */
    public function apiUpdate(Request $request, $id)
    {
        return $this->update($request, $id);
    }

    /**
     * API: Cancel a reservation.
     */
    public function apiDestroy($id)
    {
        return $this->destroy($id);
    }
}
