<?php

namespace App\Http\Controllers;

use App\Mail\CommunityRequestStatusChangedMail;
use App\Models\Community;
use App\Models\CommunityRequest;
use App\Models\RepairLocation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Traits\SafeMailSender;

class CommunityRequestController extends Controller
{
    use SafeMailSender;
    /**
     * Display a listing of the requests.
     */
    public function index(Request $request)
    {
        $query = CommunityRequest::with(['community', 'repairLocation', 'assignedUser']);

        // Filtres
        if ($request->filled('status')) {
            $query->status($request->status);
        }
        if ($request->filled('type')) {
            $query->type($request->type);
        }
        if ($request->filled('community_id')) {
            $query->byCommunity($request->community_id);
        }

        $requests = $query->latest()->paginate(20);
        $communities = Community::active()->ordered()->get();
        $statuses = CommunityRequest::STATUSES;

        return view('community-requests.index', compact('requests', 'communities', 'statuses'));
    }

    /**
     * Display the specified request.
     */
    public function show(CommunityRequest $communityRequest)
    {
        $communityRequest->load(['community', 'repairLocation', 'assignedUser']);
        $users = User::orderBy('name')->get();
        $statuses = CommunityRequest::STATUSES;

        return view('community-requests.show', compact('communityRequest', 'users', 'statuses'));
    }

    /**
     * Update the specified request.
     */
    public function update(Request $request, CommunityRequest $communityRequest)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,in_progress,completed,rejected'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        $oldStatus = $communityRequest->status;

        // Marquer comme complete si le statut change vers completed
        if ($validated['status'] === 'completed' && $communityRequest->status !== 'completed') {
            $validated['completed_at'] = now();
        }

        $communityRequest->update($validated);

        // Notifier le demandeur si le statut a change (non-bloquant)
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
     * Remove the specified request.
     */
    public function destroy(CommunityRequest $communityRequest)
    {
        $communityRequest->delete();

        return redirect()->route('community-requests.index')
            ->with('success', 'Demande supprimee avec succes.');
    }

    /**
     * Display statistics.
     */
    public function statistics()
    {
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
            'by_month' => CommunityRequest::selectRaw("strftime('%Y-%m', created_at) as month, count(*) as count")
                ->groupBy('month')
                ->orderByDesc('month')
                ->limit(12)
                ->pluck('count', 'month'),
        ];

        return view('community-requests.statistics', compact('stats'));
    }

    /**
     * Export requests to CSV.
     */
    public function export(Request $request)
    {
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

        $filename = 'demandes_' . date('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($requests) {
            $file = fopen('php://output', 'w');

            // BOM pour Excel
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

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

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
