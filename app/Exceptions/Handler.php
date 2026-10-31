<?php

namespace App\Exceptions;

use App\Http\Middleware\AddRequestContext;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
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
            $request ? AddRequestContext::context($request) : [],
        );
    }

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }
}








