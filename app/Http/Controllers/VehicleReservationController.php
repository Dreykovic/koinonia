<?php

namespace App\Http\Controllers;

use App\Events\ReservationCancelled;
use App\Events\ReservationCreated;
use App\Events\ReservationUpdated;
use App\Models\Vehicle;
use App\Models\VehicleReservation;
use App\Models\VehicleSetting;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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

        // Get vehicle settings for time restrictions
        $settings = VehicleSetting::getSettings();

        return view('vehicles.planning', compact('vehicles', 'settings'));
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
     * Uses transaction + pessimistic lock to prevent race conditions.
     */
    public function store(Request $request)
    {
        $settings = VehicleSetting::getSettings();

        $validated = $request->validate([
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'reservation_date' => [
                'required',
                'date',
                'after_or_equal:today',
                'before_or_equal:'.$settings->max_reservation_date,
            ],
            'start_time' => [
                'required',
                'date_format:H:i',
                'after_or_equal:'.$settings->opening_hour_formatted,
            ],
            'end_time' => [
                'required',
                'date_format:H:i',
                'after:start_time',
                'before_or_equal:'.$settings->closing_hour_formatted,
            ],
            'destination' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ], [
            'reservation_date.before_or_equal' => 'Les reservations ne peuvent pas depasser '.$settings->max_future_days.' jours a l\'avance.',
            'start_time.after_or_equal' => 'L\'heure de debut doit etre apres '.$settings->opening_hour_formatted.'.',
            'end_time.before_or_equal' => 'L\'heure de fin doit etre avant '.$settings->closing_hour_formatted.'.',
        ]);

        $user = auth()->user();

        try {
            return DB::transaction(function () use ($validated, $user) {
                // Lock the vehicle row to prevent concurrent reservations
                $vehicle = Vehicle::lockForUpdate()->findOrFail($validated['vehicle_id']);

                // Check access
                if (!$user->canAccessVehicle($vehicle)) {
                    return response()->json([
                        'message' => 'Vous n\'avez pas acces a ce vehicule.',
                    ], 403);
                }

                // Check availability (now protected by lock)
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

                // Broadcast to other users
                broadcast(new ReservationCreated($reservation->load('vehicle', 'user')))->toOthers();

                return response()->json([
                    'message' => 'Reservation creee avec succes.',
                    'reservation' => $reservation->load('vehicle', 'user:id,name'),
                ], 201);
            });
        } catch (QueryException $e) {
            // Handle unique constraint violation (PostgreSQL: 23505, MySQL: 23000)
            if (in_array($e->getCode(), ['23505', '23000'])) {
                return response()->json([
                    'message' => 'Ce creneau vient d\'etre reserve par un autre utilisateur.',
                ], 409);
            }
            throw $e;
        }
    }

    /**
     * Update a reservation.
     * Uses transaction + pessimistic lock when times are being changed.
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

        // If times are being changed, use transaction with lock
        if (isset($validated['start_time']) || isset($validated['end_time'])) {
            try {
                return DB::transaction(function () use ($validated, $reservation) {
                    // Lock the vehicle to prevent concurrent modifications
                    $vehicle = Vehicle::lockForUpdate()->findOrFail($reservation->vehicle_id);

                    $startTime = $validated['start_time'] ?? $reservation->start_time;
                    $endTime = $validated['end_time'] ?? $reservation->end_time;

                    if (!$vehicle->isAvailableAt(
                        $reservation->reservation_date->format('Y-m-d'),
                        $startTime,
                        $endTime,
                        $reservation->id
                    )) {
                        return response()->json([
                            'message' => 'Ce creneau n\'est pas disponible.',
                        ], 422);
                    }

                    $reservation->update($validated);

                    $freshReservation = $reservation->fresh()->load('vehicle', 'user');
                    broadcast(new ReservationUpdated($freshReservation))->toOthers();

                    return response()->json([
                        'message' => 'Reservation mise a jour avec succes.',
                        'reservation' => $freshReservation,
                    ]);
                });
            } catch (QueryException $e) {
                if (in_array($e->getCode(), ['23505', '23000'])) {
                    return response()->json([
                        'message' => 'Ce creneau vient d\'etre reserve par un autre utilisateur.',
                    ], 409);
                }
                throw $e;
            }
        }

        // No time change, simple update without lock
        $reservation->update($validated);

        $freshReservation = $reservation->fresh()->load('vehicle', 'user');
        broadcast(new ReservationUpdated($freshReservation))->toOthers();

        return response()->json([
            'message' => 'Reservation mise a jour avec succes.',
            'reservation' => $freshReservation,
        ]);
    }

    /**
     * Cancel a reservation.
     */
    public function destroy(Request $request, $id)
    {
        $reservation = VehicleReservation::findOrFail($id);
        $user = auth()->user();

        // Check ownership or admin
        if ($reservation->user_id !== $user->id && !$user->isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Vous n\'etes pas autorise a annuler cette reservation.',
                ], 403);
            }
            return back()->with('error', 'Vous n\'etes pas autorise a annuler cette reservation.');
        }

        // Check if can be cancelled
        if (!$reservation->canBeCancelled()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Cette reservation ne peut plus etre annulee.',
                ], 422);
            }
            return back()->with('error', 'Cette reservation ne peut plus etre annulee.');
        }

        // Broadcast before updating status (we need the original data)
        broadcast(new ReservationCancelled($reservation))->toOthers();

        $reservation->update(['status' => 'cancelled']);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Reservation annulee avec succes.',
            ]);
        }

        return redirect()->route('reservations.index')->with('success', 'Reservation annulee avec succes.');
    }

    /**
     * Display a listing of reservations.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $filter = $request->get('filter', 'all');
        $dateFilter = $request->get('date', 'upcoming');

        $query = VehicleReservation::with(['vehicle', 'user:id,name']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('destination', 'like', "%{$search}%")
                  ->orWhereHas('vehicle', function ($vq) use ($search) {
                      $vq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by user (admin only)
        if ($user->isAdmin()) {
            if ($filter === 'mine') {
                $query->where('user_id', $user->id);
            } elseif ($filter === 'others') {
                $query->where('user_id', '!=', $user->id);
            }
        } else {
            $query->where('user_id', $user->id);
        }

        // Date filters
        $today = now()->toDateString();
        $tomorrow = now()->addDay()->toDateString();
        $endOfWeek = now()->endOfWeek()->toDateString();

        switch ($dateFilter) {
            case 'today':
                $query->whereDate('reservation_date', $today)
                      ->whereIn('status', ['pending', 'approved']);
                break;
            case 'tomorrow':
                $query->whereDate('reservation_date', $tomorrow)
                      ->whereIn('status', ['pending', 'approved']);
                break;
            case 'week':
                $query->whereDate('reservation_date', '>=', $today)
                      ->whereDate('reservation_date', '<=', $endOfWeek)
                      ->whereIn('status', ['pending', 'approved']);
                break;
            case 'upcoming':
                $query->whereDate('reservation_date', '>=', $today)
                      ->whereIn('status', ['pending', 'approved']);
                break;
            case 'past':
                // Archives: show all past + cancelled
                $query->where(function ($q) use ($today) {
                    $q->whereDate('reservation_date', '<', $today)
                      ->orWhere('status', 'cancelled');
                });
                break;
            // 'all' = no date filter, show everything
        }

        // Order: upcoming dates first for non-past, past dates most recent first
        if ($dateFilter === 'past') {
            $query->orderBy('reservation_date', 'desc')->orderBy('start_time', 'desc');
        } else {
            $query->orderBy('reservation_date', 'asc')->orderBy('start_time', 'asc');
        }

        $reservations = $query->paginate(20)->withQueryString();

        return view('reservations.index', compact('reservations', 'filter', 'dateFilter'));
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
    public function apiDestroy(Request $request, $id)
    {
        return $this->destroy($request, $id);
    }
}
















