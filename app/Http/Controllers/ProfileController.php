<?php

namespace App\Http\Controllers;

use App\Support\LogMask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    /**
     * Show the user's profile.
     */
    public function show()
    {
        $user = auth()->user()->load('profile');

        return view('profile.show', compact('user'));
    }

    /**
     * Show the profile edit form.
     */
    public function edit()
    {
        $user = auth()->user();

        return view('profile.edit', compact('user'));
    }

    /**
     * Update the user's profile.
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'room_number' => ['nullable', 'string', 'max:20'],
        ]);

        $oldEmail = $user->email;

        $user->update($validated);

        // Changement d'e-mail = changement d'identifiant de connexion : tracé dans security.
        if ($user->wasChanged('email')) {
            Log::channel('security')->info('profile.email.changed', [
                'old_email' => LogMask::email($oldEmail),
                'new_email' => LogMask::email($user->email),
            ]);
        }

        return back()->with('success', 'Profil mis a jour avec succes.');
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request)
    {
        try {
            $validated = $request->validate([
                'current_password' => ['required', 'current_password'],
                'password' => ['required', Password::defaults(), 'confirmed'],
            ]);
        } catch (ValidationException $e) {
            // Mot de passe actuel erroné : possible session détournée. Jamais les valeurs saisies.
            Log::channel('security')->warning('profile.password.change_failed', [
                'reason' => isset($e->errors()['current_password']) ? 'wrong_current_password' : 'invalid_new_password',
                'fields' => array_keys($e->errors()),
            ]);

            throw $e;
        }

        auth()->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        Log::channel('security')->info('profile.password.changed');

        return back()->with('success', 'Mot de passe mis a jour avec succes.');
    }
}
