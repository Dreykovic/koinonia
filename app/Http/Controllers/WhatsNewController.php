<?php

namespace App\Http\Controllers;

use App\Support\ReleaseNotes;
use Illuminate\Http\Request;

class WhatsNewController extends Controller
{
    /**
     * L'utilisateur a ferme l'encart des nouveautes : on ne le remontre plus
     * pour cette version.
     */
    public function seen(Request $request)
    {
        $request->user()->forceFill([
            'whats_new_seen_version' => ReleaseNotes::currentVersion(),
        ])->save();

        return $request->expectsJson()
            ? response()->noContent()
            : redirect()->back();
    }
}
