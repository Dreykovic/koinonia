<?php

namespace App\Http\Controllers;

use App\Enums\PlatformRole;
use App\Models\ActivityLog;
use App\Models\Community;
use App\Models\User;
use App\Models\UserProfile;
use App\Support\LogMask;
use App\Traits\LogsErrors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Throwable;

class UserController extends Controller
{
    use LogsErrors;

    /**
     * Champs jamais renvoyés au formulaire après un échec.
     */
    private const SENSITIVE_INPUT = ['password', 'password_confirmation'];

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

        return view('users.create', compact('profiles', 'communities'));
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

        try {
            $user = User::create($validated);
        } catch (Throwable $e) {
            $this->logHandledError('user.store.failed', $e, [
                'email' => LogMask::email($validated['email']),
                'roles' => $validated['roles'],
            ]);

            return back()->withInput($request->except(self::SENSITIVE_INPUT))
                ->with('error', 'Le membre n\'a pas pu etre cree. Veuillez reessayer.');
        }

        // Pas d'ActivityLog pour la creation : trace d'administration dans le canal audit.
        $this->logInfo('user.created', [
            'target_user_id' => $user->id,
            'roles' => $user->roles,
            'community_id' => $user->community_id,
            'is_active' => $user->is_active,
        ], 'audit');

        return redirect()->route('users.index')
            ->with('success', 'Membre cree avec succes.');
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        // Pas de fiche de consultation dediee : la fiche d'edition en tient lieu.
        return redirect()->route('users.edit', $user);
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        $communities = Community::active()->ordered()->get();
        $profiles = UserProfile::active()->orderBy('name')->get();

        return view('users.edit', compact('profiles', 'user', 'communities'));
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,'.$user->id],
            'password' => ['nullable', Password::defaults(), 'confirmed'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', Rule::in(PlatformRole::all())],
            'community_id' => ['nullable', 'exists:communities,id'],
            'room_number' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
            'user_profile_id' => 'nullable|exists:user_profiles,id',

        ]);

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        $oldRoles = $user->roles;

        try {
            $user->update($validated);
        } catch (Throwable $e) {
            $this->logHandledError('user.update.failed', $e, ['target_user_id' => $user->id]);

            return back()->withInput($request->except(self::SENSITIVE_INPUT))
                ->with('error', 'Le membre n\'a pas pu etre mis a jour. Veuillez reessayer.');
        }

        $this->auditUpdate($user, $oldRoles);

        return redirect()->route('users.index')
            ->with('success', 'Membre mis a jour avec succes.');
    }

    /**
     * Permanently remove the specified user.
     *
     * Bulletins, activities and reflections created by the user are kept
     * (created_by becomes NULL); the user's own registrations, reservations
     * and notifications are deleted with the account.
     */
    public function destroy(Request $request, User $user)
    {
        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            $this->logWarning('user.destroy.refused', ['target_user_id' => $user->id, 'reason' => 'self']);

            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        // Never leave the platform without an administrator
        if ($user->isAdmin() && User::whereJsonContains('roles', PlatformRole::ADMIN)->count() <= 1) {
            $this->logWarning('user.destroy.refused', ['target_user_id' => $user->id, 'reason' => 'last_admin']);

            return back()->with('error', 'Impossible de supprimer le dernier administrateur.');
        }

        $request->validate([
            'confirm_name' => ['required', 'string', Rule::in([$user->name])],
        ], [
            'confirm_name.in' => 'Le nom saisi ne correspond pas au membre a supprimer.',
            'confirm_name.required' => 'Saisissez le nom du membre pour confirmer la suppression.',
        ]);

        $name = $user->name;

        // L'audit de la suppression est porte par ActivityLog (user.deleted) : pas de doublon dans les logs.
        try {
            DB::transaction(function () use ($user) {
                ActivityLog::log('user.deleted', $user, $user->only(['id', 'name', 'email', 'roles']));
                $user->delete();
            });
        } catch (Throwable $e) {
            $this->logHandledError('user.destroy.failed', $e, ['target_user_id' => $user->id, 'rolled_back' => true]);

            return back()->with('error', "Le membre {$name} n'a pas pu etre supprime. Aucune donnee n'a ete modifiee.");
        }

        return redirect()->route('users.index')
            ->with('success', "Le membre {$name} a ete supprime definitivement.");
    }

    /**
     * Toggle user active status.
     */
    public function toggleActive(User $user)
    {
        if ($user->id === auth()->id()) {
            $this->logWarning('user.toggle_active.refused', ['target_user_id' => $user->id, 'reason' => 'self']);

            return back()->with('error', 'Vous ne pouvez pas desactiver votre propre compte.');
        }

        try {
            $user->update(['is_active' => ! $user->is_active]);
        } catch (Throwable $e) {
            $this->logHandledError('user.toggle_active.failed', $e, ['target_user_id' => $user->id]);

            return back()->with('error', "Le statut du membre {$user->name} n'a pas pu etre modifie.");
        }

        $this->logInfo('user.active_toggled', ['target_user_id' => $user->id, 'is_active' => $user->is_active], 'audit');

        $status = $user->is_active ? 'active' : 'inactif';

        return back()->with('success', "Le membre {$user->name} est maintenant {$status}.");
    }

    /**
     * Trace d'audit d'une mise a jour : noms des champs modifies seulement (aucune valeur
     * personnelle), plus l'ancien et le nouveau jeu de roles s'ils ont change.
     */
    private function auditUpdate(User $user, ?array $oldRoles): void
    {
        $changed = array_values(array_diff(array_keys($user->getChanges()), ['updated_at', 'remember_token']));

        if ($changed === []) {
            return;
        }

        $context = ['target_user_id' => $user->id, 'changed' => $changed];

        if (in_array('roles', $changed, true)) {
            $context['old_roles'] = $oldRoles;
            $context['new_roles'] = $user->roles;
        }

        $this->logInfo('user.updated', $context, 'audit');
    }
}
