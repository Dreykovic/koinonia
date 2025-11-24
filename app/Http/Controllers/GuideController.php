<?php

namespace App\Http\Controllers;

class GuideController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $sections = collect(config('guide.sections'))->map(function ($section) use ($user) {
            $section['entries'] = array_filter($section['entries'], function ($entry) use ($user) {
                return empty($entry['roles']) || $user->hasAnyRole($entry['roles']);
            });

            return $section;
        })->filter(function ($section) {
            return !empty($section['entries']);
        })->toArray();

        return view('guide.index', compact('sections'));
    }
}




