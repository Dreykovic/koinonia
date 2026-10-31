<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class Authenticate extends Middleware
{
    /**
     * Trace l'accès non authentifié (session expirée, lien direct) avant la redirection
     * vers la connexion ou la réponse 401 JSON. Niveau info : comportement normal.
     */
    protected function unauthenticated($request, array $guards)
    {
        Log::channel('security')->info('auth.unauthenticated', [
            'middleware' => 'auth',
            'guards' => $guards,
            'expects_json' => $request->expectsJson(),
        ]);

        parent::unauthenticated($request, $guards);
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        return $request->expectsJson() ? null : route('login');
    }
}
