<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\LogMask;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Tentatives de connexion autorisées par couple e-mail/IP avant blocage.
     */
    public const MAX_LOGIN_ATTEMPTS = 5;

    /**
     * Durée du blocage (secondes) après MAX_LOGIN_ATTEMPTS échecs.
     */
    public const LOGIN_DECAY_SECONDS = 60;

    /**
     * Show the login form.
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        Log::channel('security')->debug('auth.login.form');

        return view('auth.login');
    }

    /**
     * Handle login request.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $throttleKey = $this->throttleKey($request);

        // Blocage après trop d'échecs : l'événement Lockout est journalisé (et alerté si répété).
        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_LOGIN_ATTEMPTS)) {
            event(new Lockout($request));

            throw ValidationException::withMessages([
                'email' => ['Trop de tentatives de connexion. Veuillez réessayer dans '
                    .RateLimiter::availableIn($throttleKey).' secondes.'],
            ]);
        }

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! $user->is_active) {
            // Auth::attempt n'est pas appelé : pas d'événement Failed, on trace ici.
            RateLimiter::hit($throttleKey, self::LOGIN_DECAY_SECONDS);
            Log::channel('security')->warning('auth.login.failed', [
                'reason' => $user ? 'inactive_account' : 'unknown_account',
                'user_id' => $user?->id,
                'email' => LogMask::email($credentials['email']),
            ]);

            throw ValidationException::withMessages([
                'email' => ['Ce compte est inactif ou n\'existe pas.'],
            ]);
        }

        // Succès et échec sont tracés par LogAuthenticationEvents (événements Login / Failed).
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        RateLimiter::hit($throttleKey, self::LOGIN_DECAY_SECONDS);

        throw ValidationException::withMessages([
            'email' => ['Les identifiants fournis sont incorrects.'],
        ]);
    }

    /**
     * Clé du limiteur de connexion : e-mail (en minuscules) + IP.
     */
    protected function throttleKey(Request $request): string
    {
        return 'login|'.Str::lower((string) $request->input('email')).'|'.$request->ip();
    }

    /**
     * Handle logout request.
     */
    public function logout(Request $request)
    {
        // Tracé par LogAuthenticationEvents (événement Logout).
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Show the forgot password form.
     */
    public function showForgotPassword()
    {
        Log::channel('security')->debug('auth.password.forgot_form');

        return view('auth.forgot-password');
    }

    /**
     * Send password reset link.
     */
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        $context = [
            'email' => LogMask::email($request->input('email')),
            'status' => $status,
        ];

        if ($status === Password::RESET_LINK_SENT) {
            Log::channel('security')->info('auth.password.reset_link.sent', $context);

            return back()->with('status', __($status));
        }

        // Compte inconnu (passwords.user) ou demandes trop rapprochées (passwords.throttled).
        Log::channel('security')->warning('auth.password.reset_link.failed', $context);

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }

    /**
     * Show the password reset form.
     */
    public function showResetForm(Request $request, string $token)
    {
        // L'URL partagée par AddRequestContext contient le token et l'e-mail : on la masque
        // pour tous les logs de cette requête.
        Log::shareContext(['url' => route('password.reset', ['token' => '***'])]);

        // Ouverture d'un lien de réinitialisation : jamais le token.
        Log::channel('security')->info('auth.password.reset_form', [
            'email' => LogMask::email($request->query('email')),
        ]);

        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    /**
     * Handle password reset.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                // Le broker ne déclenche pas l'événement : tracé par LogAuthenticationEvents.
                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', __($status));
        }

        // Token invalide/expiré (passwords.token), compte inconnu ou throttling.
        Log::channel('security')->warning('auth.password.reset.failed', [
            'email' => LogMask::email($request->input('email')),
            'status' => $status,
        ]);

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }
}
