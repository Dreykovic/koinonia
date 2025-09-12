<?php

namespace App\Http\Controllers;

use App\Models\RepairLocation;
use Illuminate\Http\Request;

class RepairLocationController extends Controller
{
    /**
     * Display a listing of the locations.
     */
    public function index()
    {
        $locations = RepairLocation::withCount('communityRequests')->orderBy('name')->get();

        return view('repair-locations.index', compact('locations'));
    }

    /**
     * Store a newly created location.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:repair_locations'],
        ]);

        RepairLocation::create($validated);

        return back()->with('success', 'Lieu ajoute avec succes.');
    }

    /**
     * Update the specified location.
     */
    public function update(Request $request, RepairLocation $repairLocation)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:repair_locations,name,' . $repairLocation->id],
            'is_active' => ['boolean'],
        ]);

        $repairLocation->update($validated);

        return back()->with('success', 'Lieu mis a jour avec succes.');
    }

    /**
     * Remove the specified location.
     */
    public function destroy(RepairLocation $repairLocation)
    {
        if ($repairLocation->communityRequests()->exists()) {
            return back()->with('error', 'Impossible de supprimer: des demandes sont liees a ce lieu.');
        }

        $repairLocation->delete();

        return back()->with('success', 'Lieu supprime avec succes.');
    }
}
