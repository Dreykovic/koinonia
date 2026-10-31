<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FailedEmail;
use App\Services\FailedEmailRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
     * Renvoyer immédiatement un email échoué (succès et échec journalisés dans `mail`).
     */
    public function retry(FailedEmail $failedEmail, FailedEmailRecorder $recorder)
    {
        if ($failedEmail->is_resolved) {
            return back()->with('error', 'Cet email est déjà marqué comme résolu.');
        }

        $result = $recorder->retry($failedEmail);

        Log::channel('audit')->info('admin.failed_email.retried', [
            'failed_email_id' => $failedEmail->id,
            'result' => $result,
        ]);

        return match ($result) {
            'sent' => back()->with('success', 'Email renvoyé avec succès.'),
            'unavailable' => back()->with('error', 'Renvoi impossible : le contenu de cet email n\'a pas été conservé.'),
            default => back()->with('error', 'Le renvoi a de nouveau échoué : '.Str::limit($failedEmail->fresh()->error_message, 150)),
        };
    }

    /**
     * Marquer un email comme résolu.
     */
    public function resolve(FailedEmail $failedEmail)
    {
        $failedEmail->markAsResolved();

        Log::channel('audit')->info('admin.failed_email.resolved', ['failed_email_id' => $failedEmail->id]);

        return back()->with('success', 'Email marqué comme résolu.');
    }

    /**
     * Supprimer un email échoué.
     */
    public function destroy(FailedEmail $failedEmail)
    {
        $id = $failedEmail->id;
        $failedEmail->delete();

        Log::channel('audit')->info('admin.failed_email.deleted', ['failed_email_id' => $id]);

        return back()->with('success', 'Entrée supprimée.');
    }
}
