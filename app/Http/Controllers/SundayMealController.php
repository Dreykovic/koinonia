<?php

namespace App\Http\Controllers;

use App\Models\SundayMeal;
use Illuminate\Http\Request;

class SundayMealController extends Controller
{
    /**
     * Display a listing of meals.
     */
    public function index()
    {
        $meals = SundayMeal::with(['inscriptions' => function ($query) {
            $query->whereNull('cancelled_at')->with('user:id,name');
        }])
        ->orderBy('meal_date', 'desc')
        ->paginate(10);

        return view('meals.index', compact('meals'));
    }

    /**
     * Show the form for creating a new meal.
     */
    public function create()
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        return view('meals.create');
    }

    /**
     * Store a newly created meal.
     */
    public function store(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'meal_date' => ['required', 'date', 'after:today', 'unique:sunday_meals'],
            'max_capacity' => ['required', 'integer', 'min:1', 'max:200'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['is_open'] = true;

        SundayMeal::create($validated);

        return redirect()->route('meals.index')
            ->with('success', 'Repas du dimanche cree avec succes.');
    }

    /**
     * Display the specified meal.
     */
    public function show(SundayMeal $meal)
    {
        $meal->load(['inscriptions' => function ($query) {
            $query->whereNull('cancelled_at')->with('user');
        }]);

        $userInscription = $meal->inscriptions
            ->where('user_id', auth()->id())
            ->first();

        return view('meals.show', compact('meal', 'userInscription'));
    }

    /**
     * Show the form for editing the specified meal.
     */
    public function edit(SundayMeal $meal)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        return view('meals.edit', compact('meal'));
    }

    /**
     * Update the specified meal.
     */
    public function update(Request $request, SundayMeal $meal)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'max_capacity' => ['required', 'integer', 'min:1', 'max:200'],
            'notes' => ['nullable', 'string'],
            'is_open' => ['boolean'],
        ]);

        $validated['is_open'] = $request->boolean('is_open', true);

        $meal->update($validated);

        return redirect()->route('meals.index')
            ->with('success', 'Repas mis a jour avec succes.');
    }

    /**
     * Remove the specified meal.
     */
    public function destroy(SundayMeal $meal)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        $meal->delete();

        return redirect()->route('meals.index')
            ->with('success', 'Repas supprime avec succes.');
    }

    /**
     * Toggle registration open/close for a meal.
     */
    public function toggleRegistration(SundayMeal $meal)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        $meal->update(['is_open' => !$meal->is_open]);

        $status = $meal->is_open ? 'ouvertes' : 'fermees';

        return back()->with('success', "Les inscriptions sont maintenant {$status}.");
    }
}




