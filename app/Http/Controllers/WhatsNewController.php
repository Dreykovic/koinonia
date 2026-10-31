<?php

namespace App\Http\Controllers;

use App\Http\Middleware\AddRequestContext;
use App\Support\ReleaseNotes;
use App\Traits\LogsErrors;
use Illuminate\Http\Request;
use Throwable;

class WhatsNewController extends Controller
{
    use LogsErrors;

    /**
     * L'utilisateur a ferme l'encart des nouveautes : on ne le remontre plus
     * pour cette version.
     */
    public function seen(Request $request)
    {
        try {
            $version = ReleaseNotes::currentVersion();

            $request->user()->forceFill([
                'whats_new_seen_version' => $version,
            ])->save();
        } catch (Throwable $e) {
            // Sans gravite : l'encart sera simplement reaffiche a la prochaine page.
            $this->logError('whats_new.seen.failed', $e, ['version' => $version ?? null]);

            $message = "Impossible d'enregistrer la lecture des nouveautés. Veuillez réessayer plus tard.";

            return $request->expectsJson()
                ? response()->json([
                    'message' => $message,
                    'status' => 500,
                    'request_id' => AddRequestContext::ensureRequestId($request),
                ], 500)
                : redirect()->back()->with('error', $message);
        }

        return $request->expectsJson()
            ? response()->noContent()
            : redirect()->back();
    }
}
