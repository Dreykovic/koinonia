<?php

namespace App\Http\Controllers;

use App\Models\MealInscription;
use App\Models\SundayMeal;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MealController extends Controller
{
    /**
     * Display the meals page for members with upcoming meal widget.
     */
    public function index()
    {
        $user = auth()->user();

        // Get upcoming meal (next one with open registrations or the next Sunday)
        $upcomingMeal = SundayMeal::upcoming()->first();

        // Get user's inscription for the upcoming meal
        $userInscription = null;
        if ($upcomingMeal) {
            $userInscription = MealInscription::where('sunday_meal_id', $upcomingMeal->id)
                ->where('user_id', $user->id)
                ->whereNull('cancelled_at')
                ->first();
        }

        // Get user's past reservations
        $pastInscriptions = MealInscription::with('sundayMeal')
            ->where('user_id', $user->id)
            ->whereHas('sundayMeal', function ($q) {
                $q->where('meal_date', '<', now()->toDateString());
            })
            ->orderByDesc('registered_at')
            ->limit(10)
            ->get();

        return view('meals.index', compact('upcomingMeal', 'userInscription', 'pastInscriptions'));
    }

    /**
     * Reserve a spot for the meal (simple 1-click reservation).
     */
    public function reserve(SundayMeal $meal)
    {
        $user = auth()->user();

        // Check if can register
        if (!$meal->canRegister()) {
            if ($meal->isFull()) {
                return back()->with('error', 'Ce repas est complet.');
            }
            return back()->with('error', 'Les inscriptions pour ce repas sont fermees.');
        }

        // Check if already registered
        $existingInscription = MealInscription::where('sunday_meal_id', $meal->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingInscription) {
            if ($existingInscription->isActive()) {
                return back()->with('error', 'Vous etes deja inscrit a ce repas.');
            }

            // Reactivate cancelled inscription
            $existingInscription->update([
                'cancelled_at' => null,
                'registered_at' => now(),
            ]);
        } else {
            // Create new inscription
            MealInscription::create([
                'sunday_meal_id' => $meal->id,
                'user_id' => $user->id,
                'registered_at' => now(),
            ]);
        }

        return back()->with('success', 'Votre reservation a ete confirmee !');
    }

    /**
     * Cancel a reservation.
     */
    public function cancel(SundayMeal $meal)
    {
        $user = auth()->user();

        // Check if can cancel
        if (!$meal->canCancel()) {
            return back()->with('error', 'Le delai d\'annulation est depasse.');
        }

        $inscription = MealInscription::where('sunday_meal_id', $meal->id)
            ->where('user_id', $user->id)
            ->whereNull('cancelled_at')
            ->first();

        if (!$inscription) {
            return back()->with('error', 'Vous n\'avez pas de reservation pour ce repas.');
        }

        $inscription->update(['cancelled_at' => now()]);

        return back()->with('success', 'Votre reservation a ete annulee.');
    }

    /**
     * Show meal details (member view).
     */
    public function show(SundayMeal $meal)
    {
        $user = auth()->user();

        $userInscription = MealInscription::where('sunday_meal_id', $meal->id)
            ->where('user_id', $user->id)
            ->whereNull('cancelled_at')
            ->first();

        // For admin, load all inscriptions
        $inscriptions = null;
        if ($user->isAdmin()) {
            $inscriptions = $meal->activeInscriptions()
                ->with('user')
                ->orderBy('registered_at')
                ->get();
        }

        return view('meals.show', compact('meal', 'userInscription', 'inscriptions'));
    }

    // =============== ADMIN METHODS ===============

    /**
     * Admin: list all meals.
     */
    public function adminIndex(Request $request)
    {
        $statusFilter = $request->get('status', '');
        $periodFilter = $request->get('period', '');

        $query = SundayMeal::withCount(['activeInscriptions']);

        // Period filter
        if ($periodFilter === 'upcoming') {
            $query->where('meal_date', '>=', now()->toDateString());
        } elseif ($periodFilter === 'past') {
            $query->where('meal_date', '<', now()->toDateString());
        }

        // Status filter
        if ($statusFilter === 'open') {
            $query->where('is_open', true)
                  ->where('meal_date', '>=', now()->toDateString());
        } elseif ($statusFilter === 'closed') {
            $query->where('is_open', false);
        }

        $hasFilters = $statusFilter || $periodFilter;

        if ($hasFilters) {
            $meals = $query->orderBy('meal_date', 'desc')->get();

            // "full" filter must be done in PHP since confirmed_count is an accessor
            if ($statusFilter === 'full') {
                $meals = $meals->filter(fn($m) => $m->isFull());
            }

            return view('meals.admin.index', [
                'upcomingMeals' => $periodFilter !== 'past' ? $meals->filter(fn($m) => $m->meal_date >= now()->startOfDay()) : collect(),
                'pastMeals' => $periodFilter !== 'upcoming' ? $meals->filter(fn($m) => $m->meal_date < now()->startOfDay()) : collect(),
                'statusFilter' => $statusFilter,
                'periodFilter' => $periodFilter,
            ]);
        }

        $upcomingMeals = SundayMeal::upcoming()
            ->withCount(['activeInscriptions'])
            ->get();

        $pastMeals = SundayMeal::past()
            ->withCount(['activeInscriptions'])
            ->limit(20)
            ->get();

        return view('meals.admin.index', compact('upcomingMeals', 'pastMeals') + [
            'statusFilter' => '',
            'periodFilter' => '',
        ]);
    }

    /**
     * Admin: show create form.
     */
    public function create()
    {
        // Suggest next Sunday
        $nextSunday = Carbon::now()->next(Carbon::SUNDAY);

        // Check if a meal already exists for this Sunday
        while (SundayMeal::where('meal_date', $nextSunday->toDateString())->exists()) {
            $nextSunday = $nextSunday->addWeek();
        }

        $suggestedDeadline = $nextSunday->copy()->subDay()->setTime(18, 0);

        return view('meals.admin.create', compact('nextSunday', 'suggestedDeadline'));
    }

    /**
     * Admin: store new meal.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'meal_date' => 'required|date|unique:sunday_meals,meal_date',
            'max_capacity' => 'required|integer|min:1|max:100',
            'registration_deadline' => 'required|date',
            'cancellation_deadline' => 'required|date',
            'notes' => 'nullable|string|max:500',
            'is_open' => 'boolean',
            'allow_multiple_places' => 'boolean',
            'max_places_per_user' => 'required|integer|min:1|max:20',
            'allow_guests' => 'boolean',
            'max_guests_per_user' => 'required|integer|min:0|max:20',
        ]);

        $validated['is_open'] = $request->boolean('is_open');
        $validated['allow_multiple_places'] = $request->boolean('allow_multiple_places');
        $validated['allow_guests'] = $request->boolean('allow_guests');

        SundayMeal::create($validated);

        return redirect()->route('meals.admin.index')
            ->with('success', 'Le repas a ete cree avec succes.');
    }

    /**
     * Admin: edit meal.
     */
    public function edit(SundayMeal $meal)
    {
        return view('meals.admin.edit', compact('meal'));
    }

    /**
     * Admin: update meal.
     */
    public function update(Request $request, SundayMeal $meal)
    {
        $validated = $request->validate([
            'meal_date' => 'required|date|unique:sunday_meals,meal_date,' . $meal->id,
            'max_capacity' => 'required|integer|min:1|max:100',
            'registration_deadline' => 'required|date',
            'cancellation_deadline' => 'required|date',
            'notes' => 'nullable|string|max:500',
            'is_open' => 'boolean',
            'allow_multiple_places' => 'boolean',
            'max_places_per_user' => 'required|integer|min:1|max:20',
            'allow_guests' => 'boolean',
            'max_guests_per_user' => 'required|integer|min:0|max:20',
        ]);

        $validated['is_open'] = $request->boolean('is_open');
        $validated['allow_multiple_places'] = $request->boolean('allow_multiple_places');
        $validated['allow_guests'] = $request->boolean('allow_guests');

        $meal->update($validated);

        return redirect()->route('meals.admin.index')
            ->with('success', 'Le repas a ete modifie avec succes.');
    }

    /**
     * Admin: delete meal.
     */
    public function destroy(SundayMeal $meal)
    {
        $meal->delete();

        return redirect()->route('meals.admin.index')
            ->with('success', 'Le repas a ete supprime.');
    }

    /**
     * Admin: toggle meal open status.
     */
    public function toggleOpen(SundayMeal $meal)
    {
        $meal->update(['is_open' => !$meal->is_open]);

        $status = $meal->is_open ? 'ouvertes' : 'fermees';

        return back()->with('success', "Les inscriptions sont maintenant {$status}.");
    }

    /**
     * Admin: view meal details with all inscriptions.
     */
    public function adminShow(SundayMeal $meal)
    {
        $meal->load(['inscriptions.user']);

        $activeInscriptions = $meal->inscriptions->whereNull('cancelled_at')->sortBy('registered_at');
        $cancelledInscriptions = $meal->inscriptions->whereNotNull('cancelled_at')->sortByDesc('cancelled_at');

        return view('meals.admin.show', compact('meal', 'activeInscriptions', 'cancelledInscriptions'));
    }

    /**
     * API: Get upcoming meal data for widget.
     */
    public function widgetData()
    {
        $user = auth()->user();
        $meal = SundayMeal::upcoming()->first();

        if (!$meal) {
            return response()->json(['meal' => null]);
        }

        $inscription = MealInscription::where('sunday_meal_id', $meal->id)
            ->where('user_id', $user->id)
            ->whereNull('cancelled_at')
            ->first();

        return response()->json([
            'meal' => [
                'id' => $meal->id,
                'date' => $meal->short_date,
                'full_date' => $meal->formatted_date,
                'confirmed' => $meal->confirmed_count,
                'total' => $meal->max_capacity,
                'remaining' => $meal->remaining_places,
                'status' => $meal->status,
                'status_label' => $meal->status_label,
                'can_register' => $meal->canRegister(),
                'can_cancel' => $meal->canCancel(),
                'is_full' => $meal->isFull(),
                'is_deadline_passed' => $meal->isRegistrationClosed(),
            ],
            'is_registered' => $inscription !== null,
            'routes' => [
                'register' => route('meals.register', $meal),
                'unregister' => route('meals.unregister', $meal),
            ],
        ]);
    }

    /**
     * Get upcoming meal for widget display (used by view composers).
     */
    public static function getUpcomingMealWidget()
    {
        $user = auth()->user();
        if (!$user) {
            return null;
        }

        $meal = SundayMeal::upcoming()->first();
        if (!$meal) {
            return null;
        }

        $inscription = MealInscription::where('sunday_meal_id', $meal->id)
            ->where('user_id', $user->id)
            ->whereNull('cancelled_at')
            ->first();

        return [
            'meal' => $meal,
            'is_registered' => $inscription !== null,
        ];
    }
}














