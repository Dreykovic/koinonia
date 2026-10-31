<?php

namespace App\Http\Controllers;

use App\Models\Community;
use App\Traits\LogsErrors;
use Illuminate\Http\Request;
use Throwable;

class CommunityController extends Controller
{
    use LogsErrors;

    /**
     * Display a listing of the communities.
     */
    public function index()
    {
        $communities = Community::withCount(['users', 'communityRequests'])
            ->orderBy('name')
            ->get();

        return view('communities.index', compact('communities'));
    }

    /**
     * Store a newly created community.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:communities'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $community = Community::create($validated);
        } catch (Throwable $e) {
            $this->logHandledError('community.store.failed', $e, ['name' => $validated['name']]);

            return back()->withInput()->with('error', 'La communaute n\'a pas pu etre ajoutee. Veuillez reessayer.');
        }

        // Pas d'ActivityLog pour les communautes : trace d'administration dans le canal audit.
        $this->logInfo('community.created', ['community_id' => $community->id, 'name' => $community->name], 'audit');

        return back()->with('success', 'Communaute ajoutee avec succes.');
    }

    /**
     * Update the specified community.
     */
    public function update(Request $request, Community $community)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:communities,name,'.$community->id],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
        ]);

        try {
            $community->update($validated);
        } catch (Throwable $e) {
            $this->logHandledError('community.update.failed', $e, ['community_id' => $community->id]);

            return back()->withInput()->with('error', 'La communaute n\'a pas pu etre mise a jour. Veuillez reessayer.');
        }

        $changed = array_values(array_diff(array_keys($community->getChanges()), ['updated_at']));

        if ($changed !== []) {
            $this->logInfo('community.updated', [
                'community_id' => $community->id,
                'changed' => $changed,
                'is_active' => (bool) $community->is_active,
            ], 'audit');
        }

        return back()->with('success', 'Communaute mise a jour avec succes.');
    }

    /**
     * Remove the specified community.
     */
    public function destroy(Community $community)
    {
        // Verifier si des utilisateurs sont lies
        if ($community->users()->exists()) {
            $this->logWarning('community.destroy.refused', ['community_id' => $community->id, 'reason' => 'has_users']);

            return back()->with('error', 'Impossible de supprimer: des utilisateurs sont lies a cette communaute.');
        }

        // Verifier si des demandes sont liees
        if ($community->communityRequests()->exists()) {
            $this->logWarning('community.destroy.refused', ['community_id' => $community->id, 'reason' => 'has_requests']);

            return back()->with('error', 'Impossible de supprimer: des demandes sont liees a cette communaute.');
        }

        try {
            $community->delete();
        } catch (Throwable $e) {
            $this->logHandledError('community.destroy.failed', $e, ['community_id' => $community->id]);

            return back()->with('error', 'La communaute n\'a pas pu etre supprimee. Veuillez reessayer.');
        }

        $this->logInfo('community.deleted', ['community_id' => $community->id, 'name' => $community->name], 'audit');

        return back()->with('success', 'Communaute supprimee avec succes.');
    }
}
