@extends('layouts.app')

@section('title', 'Demande de ' . $accountRequest->name)
@section('page-title', 'Detail de la demande')

@section('breadcrumb')
<li class="breadcrumb-item">
    <a href="{{ route('account-requests.index') }}" class="text-muted text-hover-primary">Demandes d'adhesion</a>
</li>
<li class="breadcrumb-item text-muted">{{ $accountRequest->name }}</li>
@endsection

@section('content')
<div class="row g-5">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header py-4 d-flex justify-content-between align-items-center">
                <h3 class="card-title fw-bold mb-0">
                    <i class="ki-duotone ki-user fs-3 text-itcj-blue me-2"><i class="path1"></i><i class="path2"></i></i>
                    Informations du demandeur
                </h3>
                <span class="badge {{ $accountRequest->status_badge_class }} fs-7">
                    {{ $accountRequest->status_label }}
                </span>
            </div>
            <div class="card-body">
                <div class="row mb-5">
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7">Nom complet</label>
                        <p class="fw-bold fs-5 mb-0" style="color: var(--itcj-dark);">{{ $accountRequest->name }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7">Email</label>
                        <p class="fw-bold fs-5 mb-0" style="color: var(--itcj-dark);">{{ $accountRequest->email }}</p>
                    </div>
                </div>

                <div class="row mb-5">
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7">Telephone</label>
                        <p class="fw-bold fs-5 mb-0" style="color: var(--itcj-dark);">{{ $accountRequest->phone ?? '-' }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7">Communaute</label>
                        <p class="fw-bold fs-5 mb-0" style="color: var(--itcj-dark);">{{ $accountRequest->community?->name ?? 'Non specifiee' }}</p>
                    </div>
                </div>

                <div class="separator my-5"></div>

                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7">Date de la demande</label>
                        <p class="fw-semibold mb-0">{{ $accountRequest->created_at->format('d/m/Y a H:i') }}</p>
                    </div>
                    @if($accountRequest->processed_at)
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7">Traitee le</label>
                        <p class="fw-semibold mb-0">
                            {{ $accountRequest->processed_at->format('d/m/Y a H:i') }}
                            @if($accountRequest->processedBy)
                                <span class="text-muted">par {{ $accountRequest->processedBy->name }}</span>
                            @endif
                        </p>
                    </div>
                    @endif
                </div>

                @if($accountRequest->admin_notes)
                <div class="mt-5">
                    <label class="form-label text-muted fs-7">Notes de l'administrateur</label>
                    <div class="p-4 rounded" style="background: #f8f9fa;">
                        {{ $accountRequest->admin_notes }}
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        @if($accountRequest->isPending())
        <!-- Actions -->
        <div class="card mb-5">
            <div class="card-header py-4">
                <h3 class="card-title fw-bold mb-0">Actions</h3>
            </div>
            <div class="card-body">
                <!-- Approve Form -->
                <form action="{{ route('account-requests.approve', $accountRequest) }}" method="POST" class="mb-4"
                      data-confirm="Confirmer la création du compte pour {{ $accountRequest->name }} ?"
                      data-confirm-title="Approuver la demande"
                      data-confirm-type="success"
                      data-confirm-ok="Oui, créer le compte">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fs-7 text-muted">Note (optionnel)</label>
                        <textarea name="admin_notes" class="form-control" rows="2" placeholder="Ajouter une note..."></textarea>
                    </div>
                    <button type="submit" class="btn w-100" style="background: #e8f5e9; color: #2e7d32;">
                        <i class="ki-duotone ki-check fs-4 me-1"><i class="path1"></i><i class="path2"></i></i>
                        Approuver et créer le compte
                    </button>
                </form>

                <div class="separator my-4"></div>

                <!-- Reject Form -->
                <form action="{{ route('account-requests.reject', $accountRequest) }}" method="POST"
                      data-confirm="Confirmer le rejet de cette demande ?"
                      data-confirm-title="Rejeter la demande"
                      data-confirm-ok="Oui, rejeter">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fs-7 text-muted">Motif du rejet (optionnel)</label>
                        <textarea name="admin_notes" class="form-control" rows="2" placeholder="Raison du rejet..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-itcj-red w-100">
                        <i class="ki-duotone ki-cross fs-4 me-1"><i class="path1"></i><i class="path2"></i></i>
                        Rejeter la demande
                    </button>
                </form>
            </div>
        </div>
        @endif

        <!-- Info Box -->
        <div class="card">
            <div class="card-body">
                <div class="notice d-flex bg-light-info rounded border-info border border-dashed p-4">
                    <i class="ki-duotone ki-information fs-2tx text-info me-4"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                    <div class="d-flex flex-stack flex-grow-1">
                        <div class="fw-semibold">
                            <div class="fs-7 text-gray-700">
                                @if($accountRequest->isPending())
                                    En approuvant cette demande, un compte sera cree avec un mot de passe temporaire.
                                    Le membre recevra ses identifiants par email.
                                @elseif($accountRequest->isApproved())
                                    Cette demande a ete approuvee. Un compte a ete cree pour ce membre.
                                @else
                                    Cette demande a ete rejetee.
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-5">
            <a href="{{ route('account-requests.index') }}" class="btn btn-light w-100">
                <i class="ki-duotone ki-arrow-left me-1"><i class="path1"></i><i class="path2"></i></i>
                Retour a la liste
            </a>
        </div>
    </div>
</div>
@endsection
