<?php

namespace App\Http\Controllers;

use App\Models\Community;
use Illuminate\Http\Request;

class CommunityController extends Controller
{
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

        Community::create($validated);

        return back()->with('success', 'Communaute ajoutee avec succes.');
    }

    /**
     * Update the specified community.
     */
    public function update(Request $request, Community $community)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:communities,name,' . $community->id],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
        ]);

        $community->update($validated);

        return back()->with('success', 'Communaute mise a jour avec succes.');
    }

    /**
     * Remove the specified community.
     */
    public function destroy(Community $community)
    {
        // Verifier si des utilisateurs sont lies
        if ($community->users()->exists()) {
            return back()->with('error', 'Impossible de supprimer: des utilisateurs sont lies a cette communaute.');
        }

        // Verifier si des demandes sont liees
        if ($community->communityRequests()->exists()) {
            return back()->with('error', 'Impossible de supprimer: des demandes sont liees a cette communaute.');
        }

        $community->delete();

        return back()->with('success', 'Communaute supprimee avec succes.');
    }
}




