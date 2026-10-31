<?php

namespace App\Http\Controllers;

use App\Mail\CommunityRequestStatusChangedMail;
use App\Models\Community;
use App\Models\CommunityRequest;
use App\Models\User;
use App\Traits\LogsErrors;
use App\Traits\SafeMailSender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class CommunityRequestController extends Controller
{
    use LogsErrors, SafeMailSender;

    /**
     * Display a listing of the requests.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = CommunityRequest::with(['community', 'repairLocation', 'assignedUser']);

        // Les membres ne voient que leurs propres demandes
        if (! $user->isAdmin()) {
            $query->where('email', $user->email);
        }

        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('problem_description', 'like', "%{$search}%")
                    ->orWhere('purchase_description', 'like', "%{$search}%")
                    ->orWhere('repair_location_text', 'like', "%{$search}%")
                    ->orWhere('room_number', 'like', "%{$search}%");
            });
        }

        // Filtres
        if ($request->filled('status')) {
            $query->status($request->status);
        }
        if ($request->filled('type')) {
            $query->type($request->type);
        }
        if ($request->filled('community_id') && $user->isAdmin()) {
            $query->byCommunity($request->community_id);
        }

        $requests = $query->latest()->paginate(20)->withQueryString();
        $communities = $user->isAdmin() ? Community::active()->ordered()->get() : collect();
        $statuses = CommunityRequest::STATUSES;
        $isAdmin = $user->isAdmin();

        return view('community-requests.index', compact('requests', 'communities', 'statuses', 'isAdmin'));
    }

    /**
     * Display the specified request.
     */
    public function show(CommunityRequest $communityRequest)
    {
        $user = auth()->user();

        // Les membres ne peuvent voir que leurs propres demandes
        if (! $user->isAdmin() && $communityRequest->email !== $user->email) {
            $this->logWarning('community_request.access.denied', ['community_request_id' => $communityRequest->id], 'security');

            abort(403, 'Vous n\'avez pas acces a cette demande.');
        }

        $communityRequest->load(['community', 'repairLocation', 'assignedUser']);
        $users = $user->isAdmin() ? User::orderBy('name')->get() : collect();
        $statuses = CommunityRequest::STATUSES;
        $isAdmin = $user->isAdmin();

        return view('community-requests.show', compact('communityRequest', 'users', 'statuses', 'isAdmin'));
    }

    /**
     * Update the specified request (admin only).
     */
    public function update(Request $request, CommunityRequest $communityRequest)
    {
        $this->ensureAdmin('update', $communityRequest);

        $validated = $request->validate([
            'status' => ['required', 'in:pending,in_progress,completed,rejected'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        $oldStatus = $communityRequest->status;
        $oldAssignee = $communityRequest->assigned_to;

        // Explicit assignment for admin-only fields
        $communityRequest->status = $validated['status'];
        $communityRequest->admin_notes = $validated['admin_notes'] ?? $communityRequest->admin_notes;
        $communityRequest->assigned_to = $validated['assigned_to'] ?? $communityRequest->assigned_to;

        if ($validated['status'] === 'completed' && $oldStatus !== 'completed') {
            $communityRequest->completed_at = now();
        }

        try {
            $communityRequest->save();
        } catch (Throwable $e) {
            $this->logError('community_request.update.failed', $e, [
                'community_request_id' => $communityRequest->id,
                'old_status' => $oldStatus,
                'new_status' => $validated['status'],
            ]);

            return back()->withInput()->with('error', 'La demande n\'a pas pu etre mise a jour. Veuillez reessayer.');
        }

        // Pas d'ActivityLog pour les demandes : trace d'administration dans le canal audit
        // (notes admin exclues : texte libre).
        $this->logInfo('community_request.updated', [
            'community_request_id' => $communityRequest->id,
            'old_status' => $oldStatus,
            'new_status' => $communityRequest->status,
            'old_assigned_to' => $oldAssignee,
            'assigned_to' => $communityRequest->assigned_to,
        ], 'audit');

        // Notifier le demandeur si le statut a change (non-bloquant, echecs traces par SafeMailSender)
        if ($oldStatus !== $validated['status']) {
            $this->sendMailSafely(
                $communityRequest->email,
                new CommunityRequestStatusChangedMail($communityRequest, $oldStatus),
                $communityRequest
            );
        }

        return back()->with('success', 'Demande mise a jour avec succes.');
    }

    /**
     * Remove the specified request (admin only).
     */
    public function destroy(CommunityRequest $communityRequest)
    {
        $this->ensureAdmin('destroy', $communityRequest);

        try {
            $communityRequest->delete();
        } catch (Throwable $e) {
            $this->logError('community_request.destroy.failed', $e, ['community_request_id' => $communityRequest->id]);

            return back()->with('error', 'La demande n\'a pas pu etre supprimee. Veuillez reessayer.');
        }

        $this->logInfo('community_request.deleted', [
            'community_request_id' => $communityRequest->id,
            'request_type' => $communityRequest->request_type,
            'status' => $communityRequest->status,
        ], 'audit');

        return redirect()->route('community-requests.index')
            ->with('success', 'Demande supprimee avec succes.');
    }

    /**
     * Display statistics (admin only).
     */
    public function statistics()
    {
        $this->ensureAdmin('statistics');

        try {
            $stats = [
                'total' => CommunityRequest::count(),
                'pending' => CommunityRequest::pending()->count(),
                'in_progress' => CommunityRequest::status('in_progress')->count(),
                'completed' => CommunityRequest::status('completed')->count(),
                'rejected' => CommunityRequest::status('rejected')->count(),
                'repairs' => CommunityRequest::repairs()->count(),
                'purchases' => CommunityRequest::purchases()->count(),
                'by_community' => CommunityRequest::with('community')
                    ->selectRaw('community_id, count(*) as count')
                    ->groupBy('community_id')
                    ->get()
                    ->mapWithKeys(function ($item) {
                        return [$item->community?->name ?? 'Non defini' => $item->count];
                    }),
                'by_month' => $this->getRequestsByMonth(),
            ];
        } catch (Throwable $e) {
            $this->logError('community_request.statistics.failed', $e);

            return redirect()->route('community-requests.index')
                ->with('error', 'Les statistiques n\'ont pas pu etre calculees. Veuillez reessayer.');
        }

        return view('community-requests.statistics', compact('stats'));
    }

    /**
     * Get requests grouped by month (database-agnostic).
     */
    private function getRequestsByMonth()
    {
        $driver = DB::connection()->getDriverName();

        $dateFormat = match ($driver) {
            'mysql' => "DATE_FORMAT(created_at, '%Y-%m')",
            'pgsql' => "TO_CHAR(created_at, 'YYYY-MM')",
            'sqlite' => "strftime('%Y-%m', created_at)",
            default => "DATE_FORMAT(created_at, '%Y-%m')",
        };

        return CommunityRequest::selectRaw("{$dateFormat} as month, count(*) as count")
            ->groupBy('month')
            ->orderByDesc('month')
            ->limit(12)
            ->pluck('count', 'month');
    }

    /**
     * Export requests to CSV (admin only).
     */
    public function export(Request $request)
    {
        $this->ensureAdmin('export');

        $filters = array_filter($request->only(['status', 'type', 'community_id']), fn ($value) => filled($value));

        try {
            $query = CommunityRequest::with(['community', 'repairLocation', 'assignedUser']);

            if ($request->filled('status')) {
                $query->status($request->status);
            }
            if ($request->filled('type')) {
                $query->type($request->type);
            }
            if ($request->filled('community_id')) {
                $query->byCommunity($request->community_id);
            }

            $requests = $query->latest()->get();
        } catch (Throwable $e) {
            $this->logError('community_request.export.failed', $e, ['filters' => $filters]);

            return back()->with('error', 'L\'export des demandes a echoue. Veuillez reessayer.');
        }

        // Export de donnees personnelles (e-mails des demandeurs) : trace d'audit.
        $this->logInfo('community_request.exported', ['filters' => $filters, 'count' => $requests->count()], 'audit');

        $filename = 'demandes_'.date('Y-m-d_H-i-s').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($requests, $filters) {
            $file = fopen('php://output', 'w');

            try {
                // BOM pour Excel
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

                // En-tetes
                fputcsv($file, [
                    'ID',
                    'Date',
                    'Email',
                    'Communaute',
                    'Type',
                    'Lieu',
                    'Numero Chambre',
                    'Description',
                    'Statut',
                    'Assigne a',
                    'Notes Admin',
                    'Date Completion',
                ], ';');

                foreach ($requests as $req) {
                    fputcsv($file, [
                        $req->id,
                        $req->created_at->format('d/m/Y H:i'),
                        $req->email,
                        $req->community?->name ?? '-',
                        $req->type_label,
                        $req->isRepair() ? $req->location_display : '-',
                        $req->room_number ?? '-',
                        $req->isRepair() ? $req->problem_description : $req->purchase_description,
                        $req->status_label,
                        $req->assignedUser?->name ?? '-',
                        $req->admin_notes ?? '-',
                        $req->completed_at?->format('d/m/Y H:i') ?? '-',
                    ], ';');
                }
            } catch (Throwable $e) {
                // Les en-tetes sont deja envoyes : impossible d'afficher un message, on trace le fichier tronque.
                $this->logError('community_request.export.stream_failed', $e, ['filters' => $filters, 'truncated' => true]);
            } finally {
                fclose($file);
            }
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Refuse l'action aux non-administrateurs, en tracant la tentative.
     */
    private function ensureAdmin(string $action, ?CommunityRequest $communityRequest = null): void
    {
        if (auth()->user()->isAdmin()) {
            return;
        }

        $this->logWarning('community_request.access.denied', array_filter([
            'action' => $action,
            'community_request_id' => $communityRequest?->id,
        ]), 'security');

        abort(403, 'Action reservee aux administrateurs.');
    }
}
