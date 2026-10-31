<?php

namespace App\Http\Controllers\Concerns;

use App\Http\Middleware\AddRequestContext;
use App\Services\AlertService;
use App\Traits\LogsErrors;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Gestion d'erreur commune aux routes meals.* et week-menus.* (issue #25).
 *
 * Chaque action passe par guardAction() (ecriture : retour au formulaire avec un
 * message) ou guardPage() (lecture : page 500 avec le request_id). L'erreur est
 * journalisee une seule fois dans le canal `app` sous la cle "<evenement>.failed",
 * avec le contexte metier, et comptee pour l'alerte error_spike comme une 5xx.
 *
 * Les exceptions attendues (validation, 404, 403, abort()) ne sont pas interceptees :
 * le handler les rend comme d'habitude.
 */
trait GuardsMealMenuActions
{
    use LogsErrors;

    /**
     * Execute une action d'ecriture ; en cas d'echec, retour a la page precedente
     * avec $message (suivi de la reference de la requete) et les champs saisis.
     */
    protected function guardAction(string $event, array $context, Closure $action, string $message): mixed
    {
        try {
            return $action();
        } catch (Throwable $e) {
            $this->rethrowIfExpected($e);
            $this->reportGuardedFailure($event, $e, $context);

            return $this->failureRedirect($message);
        }
    }

    /**
     * Execute une action de lecture ; en cas d'echec, page (ou JSON) d'erreur 500.
     * L'exception d'origine est conservee comme "previous" mais n'est pas reportee
     * une seconde fois par le handler (HttpException n'est jamais reportee).
     */
    protected function guardPage(string $event, array $context, Closure $action): mixed
    {
        try {
            return $action();
        } catch (Throwable $e) {
            $this->rethrowIfExpected($e);
            // Pas de comptage error_spike ici : la reponse 500 est deja comptee par
            // LogRequestDuration (HttpException non reportee).
            $this->reportGuardedFailure($event, $e, $context, countSpike: false);

            throw new HttpException(500, '', $e);
        }
    }

    private function rethrowIfExpected(Throwable $e): void
    {
        if ($e instanceof ValidationException
            || $e instanceof HttpExceptionInterface
            || $e instanceof ModelNotFoundException
            || $e instanceof AuthorizationException) {
            throw $e;
        }
    }

    private function reportGuardedFailure(string $event, Throwable $e, array $context, bool $countSpike = true): void
    {
        $this->logError("{$event}.failed", $e, $context);

        if (! $countSpike) {
            return;
        }

        try {
            app(AlertService::class)->hit(
                'error_spike',
                'Pic d\'erreurs serveur (5xx).',
                ['last_exception' => get_class($e), 'last_error' => $e->getMessage()],
                (int) config('monitoring.error_spike.threshold', 10),
                (int) config('monitoring.error_spike.window_minutes', 5),
            );
        } catch (Throwable) {
            // L'alerte ne doit jamais masquer la reponse a l'utilisateur.
        }
    }

    private function failureRedirect(string $message): RedirectResponse
    {
        $requestId = (string) request()->headers->get(AddRequestContext::HEADER);
        $reference = $requestId !== '' ? ' (ref. '.substr($requestId, 0, 8).')' : '';

        return back()->withInput()->with('error', $message.$reference);
    }
}
