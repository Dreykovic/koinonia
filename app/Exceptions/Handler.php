<?php

namespace App\Exceptions;

use App\Http\Middleware\AddRequestContext;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Sentry\Laravel\Integration;
use Sentry\State\Scope;
use Throwable;

class Handler extends ExceptionHandler
{
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
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        // Sans DSN (local, tests) le SDK Sentry est inactif : l'appel reste sans effet.
        $this->reportable(function (Throwable $e) {
            if (! app()->bound('sentry')) {
                return;
            }

            $context = request() ? AddRequestContext::context(request()) : [];

            \Sentry\configureScope(function (Scope $scope) use ($context) {
                if (isset($context['user_id'])) {
                    $scope->setUser(['id' => $context['user_id']]);
                }
                $scope->setTags(array_filter([
                    'request_id' => $context['request_id'] ?? null,
                    'route' => $context['route'] ?? null,
                ]));
            });

            Integration::captureUnhandledException($e);
        });
    }
}








