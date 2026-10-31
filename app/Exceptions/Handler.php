<?php

namespace App\Exceptions;

use App\Http\Middleware\AddRequestContext;
use App\Services\AlertService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Messages affichés à l'utilisateur (pages d'erreur et JSON), par code HTTP.
     */
    public const MESSAGES = [
        400 => 'La requête est invalide.',
        401 => 'Vous devez être connecté pour accéder à cette ressource.',
        403 => 'Vous n\'avez pas les permissions nécessaires pour accéder à cette ressource.',
        404 => 'La ressource demandée est introuvable.',
        405 => 'Cette méthode n\'est pas autorisée pour cette adresse.',
        419 => 'Votre session a expiré. Rechargez la page puis réessayez.',
        429 => 'Trop de requêtes. Patientez quelques instants avant de réessayer.',
        500 => 'Une erreur inattendue s\'est produite. Réessayez dans quelques instants.',
        503 => 'Le service est momentanément indisponible (maintenance). Réessayez dans quelques instants.',
    ];

    /**
     * Codes pour lesquels le message de l'exception n'est jamais montré (textes du framework en anglais
     * ou détails techniques) : on affiche toujours le message français ci-dessus.
     */
    private const GENERIC_ONLY = [401, 404, 405, 419, 429, 500, 503];

    /**
     * Bruit attendu, jamais reporté dans les logs d'erreur (déjà ignoré par le framework,
     * listé ici pour être explicite). 419 et 429 sont tracés à part dans le canal security.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        ValidationException::class,
        AuthenticationException::class,
        AuthorizationException::class,
        ModelNotFoundException::class,
        NotFoundHttpException::class,
        TokenMismatchException::class,
        ThrottleRequestsException::class,
    ];

    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Contexte ajouté à chaque exception loguée : request_id, route et utilisateur,
     * y compris pour les erreurs levées avant le middleware (404, maintenance…).
     *
     * @return array<string, mixed>
     */
    protected function context(): array
    {
        $request = request();

        return array_merge(
            parent::context(),
            $request ? ['request_id' => AddRequestContext::ensureRequestId($request)] + AddRequestContext::context($request) : [],
        );
    }

    /**
     * Ajoute la classe de l'exception pour filtrer les logs JSON sans parser la trace.
     *
     * @return array<string, mixed>
     */
    protected function exceptionContext(Throwable $e): array
    {
        return array_merge(parent::exceptionContext($e), ['exception_class' => get_class($e)]);
    }

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        // Toute exception reportée (hors bruit ci-dessus) compte pour l'alerte de pic d'erreurs 5xx.
        $this->reportable(function (Throwable $e) {
            try {
                app(AlertService::class)->hit(
                    'error_spike',
                    'Pic d\'erreurs serveur (5xx).',
                    ['last_exception' => get_class($e), 'last_error' => $e->getMessage()],
                    (int) config('monitoring.error_spike.threshold', 10),
                    (int) config('monitoring.error_spike.window_minutes', 5),
                );
            } catch (Throwable) {
                // L'alerte ne doit jamais empêcher le report de l'exception d'origine.
            }
        });
    }

    /**
     * Garantit un request_id (même avant le middleware), trace 419/429 dans le canal
     * security, puis renvoie la réponse avec l'en-tête X-Request-Id.
     */
    public function render($request, Throwable $e)
    {
        $requestId = AddRequestContext::ensureRequestId($request);

        $this->logSecurityNoise($request, $e);

        $response = parent::render($request, $e);
        $response->headers->set(AddRequestContext::HEADER, $requestId);

        return $response;
    }

    /**
     * Message français à montrer pour une exception HTTP donnée. Le message d'un abort()
     * explicite est conservé (ex. CheckRole), sauf pour les codes de GENERIC_ONLY.
     */
    public static function userMessage(Throwable $e, int $status): string
    {
        $default = self::MESSAGES[$status] ?? ($status >= 500 ? self::MESSAGES[500] : self::MESSAGES[400]);

        // abort(403, '…') n'a pas d'exception précédente ; une policy (Response::deny('…')) arrive
        // en AccessDeniedHttpException enveloppant une AuthorizationException.
        $previous = $e->getPrevious();
        $custom = $e instanceof HttpExceptionInterface && ($previous === null || $previous instanceof AuthorizationException)
            ? $e->getMessage()
            : '';

        $isFrameworkDefault = in_array($custom, ['', 'This action is unauthorized.'], true);

        return $isFrameworkDefault || in_array($status, self::GENERIC_ONLY, true) ? $default : $custom;
    }

    /**
     * Pages d'erreur : ajoute le message français et le request_id aux vues errors/*.
     */
    protected function renderHttpException(HttpExceptionInterface $e)
    {
        $this->registerErrorViewPaths();

        if ($view = $this->getHttpExceptionView($e)) {
            try {
                return response()->view($view, [
                    'errors' => new ViewErrorBag,
                    'exception' => $e,
                    'errorMessage' => static::userMessage($e, $e->getStatusCode()),
                    'requestId' => request()?->headers->get(AddRequestContext::HEADER),
                ], $e->getStatusCode(), $e->getHeaders());
            } catch (Throwable $t) {
                config('app.debug') && throw $t;

                $this->report($t);
            }
        }

        return $this->convertExceptionToResponse($e);
    }

    /**
     * JSON homogène : {"message", "status", "request_id"} ; détails techniques uniquement en debug.
     */
    protected function prepareJsonResponse($request, Throwable $e)
    {
        $status = $this->isHttpException($e) ? $e->getStatusCode() : 500;

        $payload = [
            'message' => static::userMessage($e, $status),
            'status' => $status,
            'request_id' => $request->headers->get(AddRequestContext::HEADER),
        ];

        if (config('app.debug')) {
            $payload += Arr::except($this->convertExceptionToArray($e), 'message');
        }

        return new JsonResponse(
            $payload,
            $status,
            $this->isHttpException($e) ? $e->getHeaders() : [],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
    }

    /**
     * 401 JSON au même format que les autres erreurs (le web garde la redirection vers login).
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if (! $this->shouldReturnJson($request, $exception)) {
            return parent::unauthenticated($request, $exception);
        }

        return response()->json([
            'message' => self::MESSAGES[401],
            'status' => 401,
            'request_id' => $request->headers->get(AddRequestContext::HEADER),
        ], 401);
    }

    /**
     * Session expirée (419) et throttling (429) ne sont pas des erreurs applicatives,
     * mais leur fréquence est utile côté sécurité : on les trace au niveau notice.
     */
    private function logSecurityNoise($request, Throwable $e): void
    {
        $event = match (true) {
            $e instanceof TokenMismatchException => 'security.csrf.mismatch',
            $e instanceof ThrottleRequestsException => 'security.throttled',
            default => null,
        };

        if ($event === null) {
            return;
        }

        Log::channel('security')->notice($event, array_filter(AddRequestContext::context($request) + [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'retry_after' => $e instanceof ThrottleRequestsException ? ($e->getHeaders()['Retry-After'] ?? null) : null,
        ], fn ($value) => $value !== null));
    }
}
