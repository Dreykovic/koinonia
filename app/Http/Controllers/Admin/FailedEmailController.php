<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FailedEmail;
use Illuminate\Http\Request;

class FailedEmailController extends Controller
{
    /**
     * Afficher la liste des emails échoués.
     */
    public function index(Request $request)
    {
        $status = $request->get('status', 'unresolved');

        $query = FailedEmail::query()->orderByDesc('created_at');

        if ($status === 'unresolved') {
            $query->unresolved();
        } elseif ($status === 'resolved') {
            $query->resolved();
        }

        $failedEmails = $query->paginate(20);

        $counts = [
            'all' => FailedEmail::count(),
            'unresolved' => FailedEmail::unresolved()->count(),
            'resolved' => FailedEmail::resolved()->count(),
        ];

        return view('admin.failed-emails.index', compact('failedEmails', 'status', 'counts'));
    }

    /**
     * Marquer un email comme résolu.
     */
    public function resolve(FailedEmail $failedEmail)
    {
        $failedEmail->markAsResolved();

        return back()->with('success', 'Email marqué comme résolu.');
    }

    /**
     * Supprimer un email échoué.
     */
    public function destroy(FailedEmail $failedEmail)
    {
        $failedEmail->delete();

        return back()->with('success', 'Entrée supprimée.');
    }
}
