<?php

namespace App\Http\Controllers;

use App\Models\Community;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;
use App\Enums\PlatformRole;
class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $query = User::with(['profile', 'community']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $role = $request->role;
            $query->whereJsonContains('roles', $role);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('community_id')) {
            $query->where('community_id', $request->community_id);
        }

        $users = $query->latest()
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $communities = Community::active()->ordered()->get();
        $roles = PlatformRole::options();

        return view('users.index', compact('users', 'communities', 'roles'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $communities = Community::active()->ordered()->get();
        $profiles = UserProfile::active()->orderBy('name')->get();
        
        return view('users.create', compact('profiles','communities'));
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', Password::defaults(), 'confirmed'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', Rule::in(PlatformRole::all())],
            'community_id' => ['nullable', 'exists:communities,id'],
            'room_number' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
            'user_profile_id' => 'nullable|exists:user_profiles,id',

        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = $request->boolean('is_active', true);

        User::create($validated);

        return redirect()->route('users.index')
            ->with('success', 'Membre cree avec succes.');
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        $user->load(['community','profile']);
        return view('users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        $communities = Community::active()->ordered()->get();      
        $profiles = UserProfile::active()->orderBy('name')->get();
        return view('users.edit', compact('profiles','user', 'communities'));
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,' . $user->id],
            'password' => ['nullable', Password::defaults(), 'confirmed'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', Rule::in(PlatformRole::all())],
            'community_id' => ['nullable', 'exists:communities,id'],
            'room_number' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
            'user_profile_id' => 'nullable|exists:user_profiles,id',

        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        $user->update($validated);

        return redirect()->route('users.index')
            ->with('success', 'Membre mis a jour avec succes.');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'Membre supprime avec succes.');
    }

    /**
     * Toggle user active status.
     */
    public function toggleActive(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Vous ne pouvez pas desactiver votre propre compte.');
        }

        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'active' : 'inactif';

        return back()->with('success', "Le membre {$user->name} est maintenant {$status}.");
    }
}





