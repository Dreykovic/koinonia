<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

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
        $requestId = $this->requestId($request);
        $request->headers->set(self::HEADER, $requestId);

        Log::shareContext(self::context($request) + [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
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
     * @return array<string, mixed>
     */
    public static function context(Request $request): array
    {
        $user = $request->user();

        return array_filter([
            'request_id' => $request->headers->get(self::HEADER),
            'route' => $request->route()?->getName() ?? $request->route()?->uri(),
            'user_id' => $user?->getAuthIdentifier(),
            'roles' => $user?->roles ?? null,
        ], fn ($value) => $value !== null && $value !== []);
    }

    private function requestId(Request $request): string
    {
        $incoming = (string) $request->header(self::HEADER);

        // Un identifiant fourni par un proxy est conservé s'il est sûr (évite l'injection dans les logs).
        return preg_match('/^[A-Za-z0-9\-]{8,64}$/', $incoming) ? $incoming : (string) Str::uuid();
    }
}
