<?php

namespace App\Http\Controllers;

use App\Mail\NewCommunityRequestMail;
use App\Models\Community;
use App\Models\CommunityRequest;
use App\Models\RepairLocation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Traits\SafeMailSender;
use App\Enums\PlatformRole;

class CommunityRequestFormController extends Controller
{
    use SafeMailSender;
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
        if (!session('community_request_form')) {
            return redirect()->route('community-requests.form.step1');
        }

        $locations = RepairLocation::active()->orderBy('name')->get();
        return view('community-requests.form.step2-repair', compact('locations'));
    }

    /**
     * Traitement etape 2A: Reparation.
     */
    public function postStep2Repair(Request $request)
    {
        if (!session('community_request_form')) {
            return redirect()->route('community-requests.form.step1');
        }

        $validated = $request->validate([
            'repair_location_id' => ['nullable', 'exists:repair_locations,id'],
            'other_location' => ['nullable', 'string', 'max:255'],
            'room_number' => ['nullable', 'string', 'max:50'],
            'problem_description' => ['required', 'string', 'max:2000'],
        ]);

        // Verifier qu'un lieu est selectionne
        if (empty($validated['repair_location_id']) && empty($validated['other_location'])) {
            return back()->withErrors(['repair_location_id' => 'Veuillez selectionner un lieu ou specifier un autre lieu.']);
        }

        $formData = array_merge(session('community_request_form'), $validated);

        $communityRequest = CommunityRequest::create($formData);

        // Notifier les admins (non-bloquant)
        $admins = User::active()->byRole(PlatformRole::ADMIN)->get();
        foreach ($admins as $admin) {
            $this->sendMailSafely($admin, new NewCommunityRequestMail($communityRequest), $communityRequest);
        }

        session()->forget('community_request_form');

        return redirect()->route('community-requests.form.success');
    }

    /**
     * Etape 2B: Achat.
     */
    public function step2Purchase()
    {
        if (!session('community_request_form')) {
            return redirect()->route('community-requests.form.step1');
        }

        return view('community-requests.form.step2-purchase');
    }

    /**
     * Traitement etape 2B: Achat.
     */
    public function postStep2Purchase(Request $request)
    {
        if (!session('community_request_form')) {
            return redirect()->route('community-requests.form.step1');
        }

        $validated = $request->validate([
            'purchase_description' => ['required', 'string', 'max:2000'],
        ]);

        $formData = array_merge(session('community_request_form'), $validated);

        $communityRequest = CommunityRequest::create($formData);

        // Notifier les admins (non-bloquant)
        $admins = User::active()->byRole(PlatformRole::ADMIN)->get();

        foreach ($admins as $admin) {
            $this->sendMailSafely($admin, new NewCommunityRequestMail($communityRequest), $communityRequest);
        }

        session()->forget('community_request_form');

        return redirect()->route('community-requests.form.success');
    }

    /**
     * Page de succes.
     */
    public function success()
    {
        return view('community-requests.form.success');
    }
}
