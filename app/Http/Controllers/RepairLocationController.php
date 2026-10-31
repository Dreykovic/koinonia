<?php

namespace App\Http\Controllers;

use App\Models\RepairLocation;
use App\Traits\LogsErrors;
use Illuminate\Http\Request;
use Throwable;

class RepairLocationController extends Controller
{
    use LogsErrors;

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

        try {
            $location = RepairLocation::create($validated);
        } catch (Throwable $e) {
            $this->logHandledError('repair_location.store.failed', $e);

            return back()->withInput()->with('error', 'Le lieu n\'a pas pu etre ajoute. Reessayez.');
        }

        $this->logInfo('repair_location.created', $this->locationContext($location), 'audit');

        return back()->with('success', 'Lieu ajoute avec succes.');
    }

    /**
     * Update the specified location.
     */
    public function update(Request $request, RepairLocation $repairLocation)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:repair_locations,name,'.$repairLocation->id],
            'is_active' => ['boolean'],
        ]);

        try {
            $repairLocation->update($validated);
        } catch (Throwable $e) {
            $this->logHandledError('repair_location.update.failed', $e, $this->locationContext($repairLocation));

            return back()->withInput()->with('error', 'Le lieu n\'a pas pu etre mis a jour. Reessayez.');
        }

        $this->logInfo('repair_location.updated', $this->locationContext($repairLocation) + [
            'fields' => array_keys($repairLocation->getChanges()),
        ], 'audit');

        return back()->with('success', 'Lieu mis a jour avec succes.');
    }

    /**
     * Remove the specified location.
     */
    public function destroy(RepairLocation $repairLocation)
    {
        $linkedRequests = $repairLocation->communityRequests()->count();

        if ($linkedRequests > 0) {
            $this->logWarning('repair_location.delete.refused', $this->locationContext($repairLocation) + [
                'reason' => 'linked_requests',
                'linked_requests' => $linkedRequests,
            ]);

            return back()->with('error', 'Impossible de supprimer: des demandes sont liees a ce lieu.');
        }

        $context = $this->locationContext($repairLocation);

        try {
            $repairLocation->delete();
        } catch (Throwable $e) {
            $this->logHandledError('repair_location.delete.failed', $e, $context);

            return back()->with('error', 'Le lieu n\'a pas pu etre supprime. Reessayez.');
        }

        $this->logInfo('repair_location.deleted', $context, 'audit');

        return back()->with('success', 'Lieu supprime avec succes.');
    }

    /**
     * Contexte metier d'un lieu de reparation pour les logs.
     */
    private function locationContext(RepairLocation $location): array
    {
        return [
            'repair_location_id' => $location->id,
            'repair_location_name' => $location->name,
            'is_active' => $location->is_active,
        ];
    }
}
