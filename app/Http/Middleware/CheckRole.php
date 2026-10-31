<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * Chaque passage est tracé dans le canal security : refus (403) en warning,
     * accès accordé en debug. Utilisateur, rôles et route viennent d'AddRequestContext.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $request->user()) {
            Log::channel('security')->info('auth.unauthenticated', [
                'middleware' => 'role',
                'required_roles' => $roles,
            ]);

            return redirect()->route('login');
        }

        // user_id et roles de l'utilisateur sont déjà dans le contexte partagé.
        $context = ['required_roles' => $roles];

        if (! $request->user()->hasAnyRole($roles)) {
            Log::channel('security')->warning('auth.access.denied', $context);

            abort(403, 'Vous n\'avez pas les permissions necessaires pour acceder a cette page.');
        }

        Log::channel('security')->info('auth.access.granted', $context);

        return $next($request);
    }
}
