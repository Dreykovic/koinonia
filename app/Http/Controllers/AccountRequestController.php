<?php

namespace App\Http\Controllers;

use App\Enums\PlatformRole;
use App\Mail\AccountApprovedMail;
use App\Mail\AccountRequestRejectedMail;
use App\Mail\NewAccountRequestMail;
use App\Models\AccountRequest;
use App\Models\Community;
use App\Models\User;
use App\Support\LogMask;
use App\Traits\SafeMailSender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Demandes de compte : soumission publique, approbation/rejet par un admin.
 * Chaque étape est tracée dans le canal security (e-mail masqué, jamais de mot de passe).
 */
class AccountRequestController extends Controller
{
    use SafeMailSender;

    /**
     * Show the public registration request form.
     */
    public function create()
    {
        $communities = Community::active()->ordered()->get();

        Log::channel('security')->debug('account_request.form');

        return view('account-requests.create', compact('communities'));
    }

    /**
     * Store a new account request.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:account_requests,email|unique:users,email',
                'phone' => 'nullable|string|max:20',
                'community_id' => 'nullable|exists:communities,id',
            ], [
                'email.unique' => 'Cette adresse email est déjà utilisée ou une demande est déjà en cours.',
            ]);
        } catch (ValidationException $e) {
            // Seuls les noms de champs en erreur : un doublon d'e-mail peut signaler une énumération.
            Log::channel('security')->notice('account_request.submit.invalid', [
                'email' => LogMask::email((string) $request->input('email')),
                'fields' => array_keys($e->errors()),
            ]);

            throw $e;
        }

        $accountRequest = AccountRequest::create($validated);

        Log::channel('security')->info('account_request.submitted', [
            'account_request_id' => $accountRequest->id,
            'email' => LogMask::email($accountRequest->email),
            'community_id' => $accountRequest->community_id,
        ]);

        // Send email to all admins (non-bloquant)
        $admins = User::active()->byRole(PlatformRole::ADMIN)->get();
        foreach ($admins as $admin) {
            $this->sendMailAfterResponse($admin, new NewAccountRequestMail($accountRequest), $accountRequest);
        }

        return redirect()->route('account-request.success');
    }

    /**
     * Show success page after submitting request.
     */
    public function success()
    {
        Log::channel('security')->debug('account_request.success_page');

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
        if (! $accountRequest->isPending()) {
            $this->logSkipped('approve', $accountRequest, 'already_processed');

            return back()->with('error', 'Cette demande a déjà été traitée.');
        }

        // Check if email is still available
        if (User::where('email', $accountRequest->email)->exists()) {
            $this->logSkipped('approve', $accountRequest, 'email_taken');

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

        // Jamais le mot de passe temporaire ; processed_by = user_id du contexte partagé.
        Log::channel('security')->info('account_request.approved', [
            'account_request_id' => $accountRequest->id,
            'created_user_id' => $user->id,
            'email' => LogMask::email($user->email),
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
        if (! $accountRequest->isPending()) {
            $this->logSkipped('reject', $accountRequest, 'already_processed');

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

        // Le contenu des notes n'est pas journalisé (données libres, potentiellement personnelles).
        Log::channel('security')->info('account_request.rejected', [
            'account_request_id' => $accountRequest->id,
            'email' => LogMask::email($accountRequest->email),
            'has_notes' => filled($validated['admin_notes'] ?? null),
        ]);

        // Notifier le demandeur du rejet (non-bloquant)
        $this->sendMailSafely($accountRequest->email, new AccountRequestRejectedMail($accountRequest), $accountRequest);

        return redirect()->route('account-requests.index')
            ->with('success', 'La demande a été rejetée. Un email a été envoyé au demandeur.');
    }

    /**
     * Action d'administration refusée car la demande n'est plus traitable.
     */
    private function logSkipped(string $action, AccountRequest $accountRequest, string $reason): void
    {
        Log::channel('security')->warning("account_request.{$action}.skipped", [
            'account_request_id' => $accountRequest->id,
            'status' => $accountRequest->status,
            'reason' => $reason,
        ]);
    }
}
