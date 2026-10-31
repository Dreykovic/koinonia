<?php

namespace App\Http\Controllers;

use App\Enums\PlatformRole;
use App\Mail\NewCommunityRequestMail;
use App\Models\Community;
use App\Models\CommunityRequest;
use App\Models\RepairLocation;
use App\Models\User;
use App\Services\QRCodeService;
use App\Traits\LogsErrors;
use App\Traits\SafeMailSender;
use Illuminate\Http\Request;
use Throwable;

class CommunityRequestFormController extends Controller
{
    use LogsErrors, SafeMailSender;

    /**
     * Etape 1: Informations de base.
     */
    public function step1()
    {
        $communities = Community::active()->ordered()->get();
        $user = auth()->user();

        return view('community-requests.form.step1', compact('communities', 'user'));
    }

    /**
     * Traitement etape 1.
     */
    public function postStep1(Request $request)
    {
        $validated = $request->validate([
            'community_id' => ['required', 'exists:communities,id'],
            'request_type' => ['required', 'in:reparation,achat'],
        ]);

        // Ajouter l'email de l'utilisateur connecte
        $validated['email'] = auth()->user()->email;

        session(['community_request_form' => $validated]);

        if ($validated['request_type'] === 'reparation') {
            return redirect()->route('community-requests.form.step2.repair');
        }

        return redirect()->route('community-requests.form.step2.purchase');
    }

    /**
     * Etape 2A: Reparation.
     */
    public function step2Repair()
    {
        if (! session('community_request_form')) {
            return $this->sessionExpired('step2_repair');
        }

        $locations = RepairLocation::active()->orderBy('name')->get();

        return view('community-requests.form.step2-repair', compact('locations'));
    }

    /**
     * Traitement etape 2A: Reparation.
     */
    public function postStep2Repair(Request $request)
    {
        if (! session('community_request_form')) {
            return $this->sessionExpired('step2_repair_post');
        }

        $validated = $request->validate([
            'repair_location_id' => ['nullable', 'exists:repair_locations,id'],
            'other_location' => ['nullable', 'string', 'max:255'],
            'room_number' => ['nullable', 'string', 'max:50'],
            'problem_description' => ['required', 'string', 'max:2000'],
        ]);

        // Verifier qu'un lieu est selectionne
        if (empty($validated['repair_location_id']) && empty($validated['other_location'])) {
            $this->logWarning('community_request.form.location_missing', [
                'community_id' => session('community_request_form.community_id'),
            ]);

            return back()->withInput()->withErrors(['repair_location_id' => 'Veuillez selectionner un lieu ou specifier un autre lieu.']);
        }

        return $this->createRequest(array_merge(session('community_request_form'), $validated));
    }

    /**
     * Etape 2B: Achat.
     */
    public function step2Purchase()
    {
        if (! session('community_request_form')) {
            return $this->sessionExpired('step2_purchase');
        }

        return view('community-requests.form.step2-purchase');
    }

    /**
     * Traitement etape 2B: Achat.
     */
    public function postStep2Purchase(Request $request)
    {
        if (! session('community_request_form')) {
            return $this->sessionExpired('step2_purchase_post');
        }

        $validated = $request->validate([
            'purchase_description' => ['required', 'string', 'max:2000'],
        ]);

        return $this->createRequest(array_merge(session('community_request_form'), $validated));
    }

    /**
     * Page de succes.
     */
    public function success()
    {
        return view('community-requests.form.success');
    }

    /**
     * Download QR code as PNG for the form URL.
     */
    public function downloadQR(QRCodeService $qrCodeService)
    {
        $url = route('community-requests.form.step1');

        try {
            return $qrCodeService->downloadPng($url);
        } catch (Throwable $e) {
            // Pas d'URL ni de contenu du QR code dans le log.
            $this->logHandledError('community_request.qr.failed', $e);

            return back()->with('error', 'Le QR code n\'a pas pu etre genere. Veuillez reessayer.');
        }
    }

    /**
     * Enregistre la demande puis previent les administrateurs.
     *
     * En cas d'echec, les donnees de session sont conservees pour que l'utilisateur
     * puisse renvoyer le formulaire sans tout ressaisir.
     */
    private function createRequest(array $formData)
    {
        try {
            $communityRequest = CommunityRequest::create($formData);
        } catch (Throwable $e) {
            $this->logHandledError('community_request.store.failed', $e, [
                'request_type' => $formData['request_type'] ?? null,
                'community_id' => $formData['community_id'] ?? null,
            ]);

            return back()->withInput()
                ->with('error', 'Votre demande n\'a pas pu etre enregistree. Veuillez reessayer dans quelques instants.');
        }

        $this->logInfo('community_request.created', [
            'community_request_id' => $communityRequest->id,
            'request_type' => $communityRequest->request_type,
            'community_id' => $communityRequest->community_id,
        ]);

        // La demande est enregistree : un echec de notification ne doit pas la faire echouer.
        try {
            $this->notifyAdmins($communityRequest);
        } catch (Throwable $e) {
            $this->logError('community_request.notify.failed', $e, ['community_request_id' => $communityRequest->id]);
        }

        session()->forget('community_request_form');
        session()->flash('request_id', $communityRequest->id);

        return redirect()->route('community-requests.form.success');
    }

    /**
     * Donnees de l'etape 1 absentes (session expiree ou acces direct a l'etape 2).
     */
    private function sessionExpired(string $step)
    {
        $this->logWarning('community_request.form.session_expired', ['step' => $step]);

        return redirect()->route('community-requests.form.step1')
            ->with('error', 'Votre session a expire. Veuillez recommencer.');
    }

    /**
     * Notify all admins about a new community request.
     *
     * L'envoi a lieu apres la reponse HTTP : le formulaire n'attend jamais
     * le serveur SMTP, et une panne mail ne bloque pas la demande.
     */
    private function notifyAdmins(CommunityRequest $communityRequest): void
    {
        $admins = User::active()->byRole(PlatformRole::ADMIN)->get();

        if ($admins->isEmpty()) {
            $this->logWarning('community_request.notify.no_admin', ['community_request_id' => $communityRequest->id]);

            return;
        }

        foreach ($admins as $admin) {
            $this->sendMailAfterResponse($admin, new NewCommunityRequestMail($communityRequest), $communityRequest);
        }
    }
}
