<?php

namespace App\Http\Controllers;

use App\Mail\AccountApprovedMail;
use App\Mail\AccountRequestRejectedMail;
use App\Mail\NewAccountRequestMail;
use App\Models\AccountRequest;
use App\Models\Community;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Traits\SafeMailSender;
use App\Enums\PlatformRole;

class AccountRequestController extends Controller
{
    use SafeMailSender;
    /**
     * Show the public registration request form.
     */
    public function create()
    {
        $communities = Community::active()->ordered()->get();

        return view('account-requests.create', compact('communities'));
    }

    /**
     * Store a new account request.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:account_requests,email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'community_id' => 'nullable|exists:communities,id',
        ], [
            'email.unique' => 'Cette adresse email est déjà utilisée ou une demande est déjà en cours.',
        ]);

        $accountRequest = AccountRequest::create($validated);

        // Send email to all admins (non-bloquant)
        $admins = User::active()->byRole(PlatformRole::ADMIN)->get();
        foreach ($admins as $admin) {
            $this->sendMailSafely($admin, new NewAccountRequestMail($accountRequest), $accountRequest);
        }

        return redirect()->route('account-request.success');
    }

    /**
     * Show success page after submitting request.
     */
    public function success()
    {
        return view('account-requests.success');
    }

    // =============== ADMIN METHODS ===============

    /**
     * Admin: List all account requests.
     */
    public function index(Request $request)
    {
        $status = $request->get('status', 'pending');

        $query = AccountRequest::with('community');

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $requests = $query->orderByDesc('created_at')->paginate(20);

        $counts = [
            'pending' => AccountRequest::pending()->count(),
            'approved' => AccountRequest::approved()->count(),
            'rejected' => AccountRequest::rejected()->count(),
        ];

        return view('account-requests.index', compact('requests', 'status', 'counts'));
    }

    /**
     * Admin: Show a single account request.
     */
    public function show(AccountRequest $accountRequest)
    {
        $accountRequest->load(['community', 'processedBy']);

        return view('account-requests.show', compact('accountRequest'));
    }

    /**
     * Admin: Approve an account request and create user.
     */
    public function approve(Request $request, AccountRequest $accountRequest)
    {
        if (!$accountRequest->isPending()) {
            return back()->with('error', 'Cette demande a déjà été traitée.');
        }

        // Check if email is still available
        if (User::where('email', $accountRequest->email)->exists()) {
            return back()->with('error', 'Un utilisateur avec cette adresse email existe déjà.');
        }

        // Generate temporary password
        $temporaryPassword = Str::random(10);

        // Create the user
        $user = User::create([
            'name' => $accountRequest->name,
            'email' => $accountRequest->email,
            'phone' => $accountRequest->phone,
            'community_id' => $accountRequest->community_id,
            'password' => Hash::make($temporaryPassword),
            'roles' => PlatformRole::MEMBRE,
            'is_active' => true,
        ]);

        // Update the request
        $accountRequest->update([
            'status' => 'approved',
            'processed_at' => now(),
            'processed_by' => auth()->id(),
            'admin_notes' => $request->input('admin_notes'),
        ]);

        // Send email to new user with credentials (non-bloquant)
        $this->sendMailSafely($user, new AccountApprovedMail($user, $temporaryPassword), $user);

        return redirect()->route('account-requests.index')
            ->with('success', "Le compte de {$user->name} a été créé. Un email avec les identifiants a été envoyé.");
    }

    /**
     * Admin: Reject an account request.
     */
    public function reject(Request $request, AccountRequest $accountRequest)
    {
        if (!$accountRequest->isPending()) {
            return back()->with('error', 'Cette demande a déjà été traitée.');
        }

        $validated = $request->validate([
            'admin_notes' => 'nullable|string|max:500',
        ]);

        $accountRequest->update([
            'status' => 'rejected',
            'processed_at' => now(),
            'processed_by' => auth()->id(),
            'admin_notes' => $validated['admin_notes'] ?? null,
        ]);

        // Notifier le demandeur du rejet (non-bloquant)
        $this->sendMailSafely($accountRequest->email, new AccountRequestRejectedMail($accountRequest), $accountRequest);

        return redirect()->route('account-requests.index')
            ->with('success', 'La demande a été rejetée. Un email a été envoyé au demandeur.');
    }
}



