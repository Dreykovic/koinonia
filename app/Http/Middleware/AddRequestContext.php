<?php

namespace App\Http\Middleware;

use App\Support\SafeLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Attache un contexte commun (request_id, user, route…) à tous les logs d'une requête
 * et renvoie le request_id dans l'en-tête X-Request-Id pour corréler un incident.
 *
 * À placer après StartSession dans le groupe web : l'utilisateur n'est lisible qu'ensuite.
 */
class AddRequestContext
{
    public const HEADER = 'X-Request-Id';

    public function handle(Request $request, Closure $next): Response
    {
        $requestId = self::ensureRequestId($request);

        // Première lecture de l'utilisateur (session => base) : si la base est en panne,
        // on journalise sans user_id plutôt que d'échouer ici ; l'erreur réelle sera
        // levée et reportée par la suite de la requête.
        try {
            $request->user();
        } catch (Throwable $e) {
            // context() ne lit que l'utilisateur déjà chargé : la requête continue sans user_id.
            SafeLog::failure('request.context.user_unavailable', $e, ['request_id' => $requestId]);
        }

        Log::shareContext(self::context($request) + [
            'method' => $request->method(),
            'url' => self::safeUrl($request),
            'ip' => $request->ip(),
        ]);

        $response = $next($request);
        $response->headers->set(self::HEADER, $requestId);

        return $response;
    }

    /**
     * Contexte de la requête (id, route, utilisateur). Réutilisé par le handler d'exceptions
     * pour les erreurs levées avant ce middleware (404, maintenance…).
     *
     * Ne déclenche jamais de requête : seul un utilisateur déjà chargé par le guard est lu
     * (un report d'exception pendant une panne de base ne doit pas échouer à son tour).
     *
     * @return array<string, mixed>
     */
    public static function context(Request $request): array
    {
        $guard = Auth::guard();
        $user = $guard->hasUser() ? $guard->user() : null;

        return array_filter([
            'request_id' => $request->headers->get(self::HEADER),
            'route' => $request->route()?->getName() ?? $request->route()?->uri(),
            'user_id' => $user?->getAuthIdentifier(),
            'roles' => $user?->roles ?? null,
        ], fn ($value) => $value !== null && $value !== []);
    }

    /**
     * URL journalisable : sans query string (tokens, signatures, e-mails) et avec le
     * token de réinitialisation de mot de passe masqué dans le chemin.
     */
    public static function safeUrl(Request $request): string
    {
        return (string) preg_replace('#/reset-password/[^/?]+#', '/reset-password/***', $request->url());
    }

    /**
     * Garantit un request_id sûr sur la requête et le renvoie. Idempotent : utilisé aussi
     * par le handler d'exceptions quand l'erreur survient avant ce middleware (route inconnue…).
     */
    public static function ensureRequestId(Request $request): string
    {
        $incoming = (string) $request->headers->get(self::HEADER);

        // Un identifiant fourni par un proxy est conservé s'il est sûr (évite l'injection dans les logs).
        $requestId = preg_match('/^[A-Za-z0-9\-]{8,64}$/', $incoming) ? $incoming : (string) Str::uuid();
        $request->headers->set(self::HEADER, $requestId);

        return $requestId;
    }
}
