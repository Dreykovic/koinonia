<?php

namespace App\Listeners;

use App\Services\AlertService;
use App\Support\LogMask;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

/**
 * Trace les événements d'authentification de Laravel dans le canal `security`.
 *
 * Jamais de mot de passe ni de token : seuls l'identifiant de l'utilisateur et
 * l'e-mail masqué (LogMask) sont journalisés. Le contexte de requête (ip, route,
 * request_id) est ajouté par AddRequestContext.
 */
class LogAuthenticationEvents
{
    public function handleLogin(Login $event): void
    {
        Log::channel('security')->info('auth.login.succeeded', [
            'user_id' => $event->user->getAuthIdentifier(),
            'guard' => $event->guard,
            'remember' => $event->remember,
        ]);
    }

    /**
     * Mot de passe incorrect pour un compte actif (les comptes inconnus ou inactifs
     * sont tracés par AuthController, Auth::attempt n'étant pas appelé).
     */
    public function handleFailed(Failed $event): void
    {
        Log::channel('security')->warning('auth.login.failed', [
            'reason' => 'invalid_credentials',
            'user_id' => $event->user?->getAuthIdentifier(),
            'email' => LogMask::email($event->credentials['email'] ?? null),
            'guard' => $event->guard,
        ]);
    }

    public function handleLogout(Logout $event): void
    {
        Log::channel('security')->info('auth.logout', [
            'user_id' => $event->user?->getAuthIdentifier(),
            'guard' => $event->guard,
        ]);
    }

    public function handlePasswordReset(PasswordReset $event): void
    {
        Log::channel('security')->info('auth.password.reset', [
            'user_id' => $event->user->getAuthIdentifier(),
        ]);
    }

    /**
     * Trop de tentatives de connexion : warning + alerte si les blocages se multiplient
     * (signe d'une attaque par force brute).
     */
    public function handleLockout(Lockout $event): void
    {
        Log::channel('security')->warning('auth.lockout', [
            'email' => LogMask::email((string) $event->request->input('email')),
        ]);

        app(AlertService::class)->hit(
            'auth_lockout',
            'Blocages répétés de connexion (trop de tentatives échouées).',
            ['ip' => $event->request->ip()],
            (int) config('monitoring.auth_lockout.threshold', 10),
            (int) config('monitoring.auth_lockout.window_minutes', 15),
        );
    }

    /**
     * @return array<class-string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'handleLogin',
            Failed::class => 'handleFailed',
            Logout::class => 'handleLogout',
            PasswordReset::class => 'handlePasswordReset',
            Lockout::class => 'handleLockout',
        ];
    }
}
