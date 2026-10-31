<?php

namespace App\Http\Controllers;

use App\Traits\LogsErrors;
use Throwable;

class GuideController extends Controller
{
    use LogsErrors;

    public function index()
    {
        $user = auth()->user();
        $degradedNotice = null;

        try {
            $sections = collect(config('guide.sections', []))->map(function ($section) use ($user) {
                $section['entries'] = array_filter($section['entries'] ?? [], function ($entry) use ($user) {
                    return empty($entry['roles']) || $user->hasAnyRole($entry['roles']);
                });

                return $section;
            })->filter(function ($section) {
                return ! empty($section['entries']);
            })->toArray();
        } catch (Throwable $e) {
            // Config du guide invalide : on affiche une page vide plutôt qu'une erreur 500.
            $this->logError('guide.load.failed', $e);
            $sections = [];
            $degradedNotice = 'Le guide est momentanément indisponible. Veuillez réessayer plus tard.';
        }

        return view('guide.index', compact('sections', 'degradedNotice'));
    }
}
