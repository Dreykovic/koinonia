<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\VehicleReservation;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class VehicleReservationController extends Controller
{
    public function planning()
    {
        $user = auth()->user();
        $vehicles = Vehicle::all();
        return view('vehicles.planning', compact('vehicles'));
    }

    public function getVehicles()
    {
        $user = auth()->user();
        $vehicles = Vehicle::available()->get()->filter(function ($vehicle) use ($user) {
            return $user->canAccessVehicle($vehicle);
        })->values();
        return response()->json($vehicles);
    }

    public function getReservations(Request $request)
    {
        $date = $request->get('date', now()->toDateString());
        $user = auth()->user();
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

    public function getStats(Request $request)
    {
        $date = $request->get('date', now()->toDateString());
        $user = auth()->user();
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

        try {
            return DB::transaction(function () use ($validated, $user) {
                $vehicle = Vehicle::lockForUpdate()->findOrFail($validated['vehicle_id']);
                if (!$user->canAccessVehicle($vehicle)) {
                    return response()->json(['message' => 'Vous n\'avez pas acces a ce vehicule.'], 403);
                }
                if (!$vehicle->isAvailableAt($validated['reservation_date'], $validated['start_time'], $validated['end_time'])) {
                    return response()->json(['message' => 'Ce creneau n\'est pas disponible.'], 422);
                }
                $reservation = VehicleReservation::create([
                    'vehicle_id' => $validated['vehicle_id'],
                    'user_id' => $user->id,
                    'reservation_date' => $validated['reservation_date'],
                    'start_time' => $validated['start_time'],
                    'end_time' => $validated['end_time'],
                    'destination' => $validated['destination'],
                    'notes' => $validated['notes'] ?? null,
                    'status' => 'approved',
                ]);
                return response()->json([
                    'message' => 'Reservation creee avec succes.',
                    'reservation' => $reservation->load('vehicle', 'user:id,name'),
                ], 201);
            });
        } catch (QueryException $e) {
            if (in_array($e->getCode(), ['23505', '23000'])) {
                return response()->json(['message' => 'Ce creneau vient d\'etre reserve par un autre utilisateur.'], 409);
            }
            throw $e;
        }
    }

    public function update(Request $request, $id)
    {
        $reservation = VehicleReservation::findOrFail($id);
        $user = auth()->user();
        if ($reservation->user_id !== $user->id && !$user->isAdmin()) {
            return response()->json(['message' => 'Vous n\'etes pas autorise a modifier cette reservation.'], 403);
        }
        $validated = $request->validate([
            'start_time' => ['sometimes', 'date_format:H:i'],
            'end_time' => ['sometimes', 'date_format:H:i', 'after:start_time'],
            'destination' => ['sometimes', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);
        if (isset($validated['start_time']) || isset($validated['end_time'])) {
            try {
                return DB::transaction(function () use ($validated, $reservation) {
                    $vehicle = Vehicle::lockForUpdate()->findOrFail($reservation->vehicle_id);
                    $startTime = $validated['start_time'] ?? $reservation->start_time;
                    $endTime = $validated['end_time'] ?? $reservation->end_time;
                    if (!$vehicle->isAvailableAt($reservation->reservation_date->format('Y-m-d'), $startTime, $endTime, $reservation->id)) {
                        return response()->json(['message' => 'Ce creneau n\'est pas disponible.'], 422);
                    }
                    $reservation->update($validated);
                    return response()->json([
                        'message' => 'Reservation mise a jour avec succes.',
                        'reservation' => $reservation->fresh()->load('vehicle', 'user'),
                    ]);
                });
            } catch (QueryException $e) {
                if (in_array($e->getCode(), ['23505', '23000'])) {
                    return response()->json(['message' => 'Ce creneau vient d\'etre reserve par un autre utilisateur.'], 409);
                }
                throw $e;
            }
        }
        $reservation->update($validated);
        return response()->json([
            'message' => 'Reservation mise a jour avec succes.',
            'reservation' => $reservation->fresh()->load('vehicle', 'user'),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $reservation = VehicleReservation::findOrFail($id);
        $user = auth()->user();
        if ($reservation->user_id !== $user->id && !$user->isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Vous n\'etes pas autorise a annuler cette reservation.'], 403);
            }
            return back()->with('error', 'Vous n\'etes pas autorise a annuler cette reservation.');
        }
        if (!$reservation->canBeCancelled()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Cette reservation ne peut plus etre annulee.'], 422);
            }
            return back()->with('error', 'Cette reservation ne peut plus etre annulee.');
        }
        $reservation->update(['status' => 'cancelled']);
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Reservation annulee avec succes.']);
        }
        return redirect()->route('reservations.index')->with('success', 'Reservation annulee avec succes.');
    }

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
        if ($user->isAdmin()) {
            if ($filter === 'mine') { $query->where('user_id', $user->id); }
            elseif ($filter === 'others') { $query->where('user_id', '!=', $user->id); }
        } else {
            $query->where('user_id', $user->id);
        }
        $today = now()->toDateString();
        $tomorrow = now()->addDay()->toDateString();
        $endOfWeek = now()->endOfWeek()->toDateString();
        switch ($dateFilter) {
            case 'today': $query->whereDate('reservation_date', $today)->whereIn('status', ['pending', 'approved']); break;
            case 'tomorrow': $query->whereDate('reservation_date', $tomorrow)->whereIn('status', ['pending', 'approved']); break;
            case 'week': $query->whereDate('reservation_date', '>=', $today)->whereDate('reservation_date', '<=', $endOfWeek)->whereIn('status', ['pending', 'approved']); break;
            case 'upcoming': $query->whereDate('reservation_date', '>=', $today)->whereIn('status', ['pending', 'approved']); break;
            case 'past': $query->where(function ($q) use ($today) { $q->whereDate('reservation_date', '<', $today)->orWhere('status', 'cancelled'); }); break;
        }
        if ($dateFilter === 'past') {
            $query->orderBy('reservation_date', 'desc')->orderBy('start_time', 'desc');
        } else {
            $query->orderBy('reservation_date', 'asc')->orderBy('start_time', 'asc');
        }
        $reservations = $query->paginate(20)->withQueryString();
        return view('reservations.index', compact('reservations', 'filter', 'dateFilter'));
    }

    public function create()
    {
        $user = auth()->user();
        $vehicles = Vehicle::available()->get()->filter(function ($vehicle) use ($user) {
            return $user->canAccessVehicle($vehicle);
        })->values();
        return view('reservations.create', compact('vehicles'));
    }

    public function show(VehicleReservation $reservation)
    {
        $user = auth()->user();
        if ($reservation->user_id !== $user->id && !$user->isAdmin()) { abort(403); }
        $reservation->load(['vehicle', 'user', 'approver']);
        return view('reservations.show', compact('reservation'));
    }

    public function edit(VehicleReservation $reservation)
    {
        $user = auth()->user();
        if ($reservation->user_id !== $user->id && !$user->isAdmin()) { abort(403); }
        $vehicles = Vehicle::available()->get()->filter(function ($vehicle) use ($user) {
            return $user->canAccessVehicle($vehicle);
        })->values();
        return view('reservations.edit', compact('reservation', 'vehicles'));
    }

    public function approve(VehicleReservation $reservation)
    {
        if (!auth()->user()->isAdmin()) { abort(403); }
        $reservation->update(['status' => 'approved', 'approved_by' => auth()->id()]);
        return back()->with('success', 'Reservation approuvee avec succes.');
    }

    public function reject(VehicleReservation $reservation)
    {
        if (!auth()->user()->isAdmin()) { abort(403); }
        $reservation->update(['status' => 'rejected', 'approved_by' => auth()->id()]);
        return back()->with('success', 'Reservation rejetee.');
    }

    public function apiStore(Request $request) { return $this->store($request); }
    public function apiUpdate(Request $request, $id) { return $this->update($request, $id); }
    public function apiDestroy(Request $request, $id) { return $this->destroy($request, $id); }
}
