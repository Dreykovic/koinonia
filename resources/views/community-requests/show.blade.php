@extends('layouts.app')

@section('title', 'Demande #' . $communityRequest->id)
@section('page-title', 'Details de la Demande')

@section('breadcrumb')
<li class="breadcrumb-item">
    <a href="{{ route('community-requests.index') }}" class="text-muted text-hover-primary">Demandes</a>
</li>
<li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
<li class="breadcrumb-item text-muted">Demande #{{ $communityRequest->id }}</li>
@endsection

@section('content')
<div class="row g-5 g-xl-10">
    <!-- Details de la demande -->
    <div class="col-xl-8">
        <div class="card mb-5">
            <div class="card-header">
                <h3 class="card-title fw-bold">
                    @if($communityRequest->isRepair())
                        <i class="ki-duotone ki-wrench fs-3 text-warning me-2"><i class="path1"></i><i class="path2"></i></i>
                        Demande de Reparation
                    @else
                        <i class="ki-duotone ki-purchase fs-3 text-success me-2"><i class="path1"></i><i class="path2"></i></i>
                        Demande d'Achat
                    @endif
                </h3>
                <div class="card-toolbar">
                    @php
                        $statusClasses = [
                            'pending' => 'badge-light-warning',
                            'in_progress' => 'badge-light-info',
                            'completed' => 'badge-light-success',
                            'rejected' => 'badge-light-danger',
                        ];
                    @endphp
                    <span class="badge {{ $statusClasses[$communityRequest->status] ?? 'badge-light' }} fs-6">
                        {{ $communityRequest->status_label }}
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-7">
                    <label class="col-lg-4 fw-semibold text-muted">Email</label>
                    <div class="col-lg-8">
                        <a href="mailto:{{ $communityRequest->email }}" class="fw-bold fs-6 text-gray-800 text-hover-primary">
                            {{ $communityRequest->email }}
                        </a>
                    </div>
                </div>

                <div class="row mb-7">
                    <label class="col-lg-4 fw-semibold text-muted">Communaute</label>
                    <div class="col-lg-8">
                        <span class="badge badge-light-primary fs-6">{{ $communityRequest->community?->name ?? '-' }}</span>
                    </div>
                </div>

                <div class="row mb-7">
                    <label class="col-lg-4 fw-semibold text-muted">Date de soumission</label>
                    <div class="col-lg-8">
                        <span class="fw-bold fs-6 text-gray-800">
                            {{ $communityRequest->created_at->format('d/m/Y a H:i') }}
                        </span>
                    </div>
                </div>

                @if($communityRequest->isRepair())
                    <div class="separator separator-dashed my-5"></div>

                    <div class="row mb-7">
                        <label class="col-lg-4 fw-semibold text-muted">Lieu</label>
                        <div class="col-lg-8">
                            <span class="fw-bold fs-6 text-gray-800">{{ $communityRequest->location_display }}</span>
                        </div>
                    </div>

                    @if($communityRequest->room_number)
                    <div class="row mb-7">
                        <label class="col-lg-4 fw-semibold text-muted">Numero de chambre</label>
                        <div class="col-lg-8">
                            <span class="fw-bold fs-6 text-gray-800">{{ $communityRequest->room_number }}</span>
                        </div>
                    </div>
                    @endif

                    <div class="row mb-7">
                        <label class="col-lg-4 fw-semibold text-muted">Description du probleme</label>
                        <div class="col-lg-8">
                            <p class="fw-bold fs-6 text-gray-800 mb-0" style="white-space: pre-line;">{{ $communityRequest->problem_description }}</p>
                        </div>
                    </div>
                @else
                    <div class="separator separator-dashed my-5"></div>

                    <div class="row mb-7">
                        <label class="col-lg-4 fw-semibold text-muted">Description de l'achat</label>
                        <div class="col-lg-8">
                            <p class="fw-bold fs-6 text-gray-800 mb-0" style="white-space: pre-line;">{{ $communityRequest->purchase_description }}</p>
                        </div>
                    </div>
                @endif

                @if($communityRequest->completed_at)
                <div class="separator separator-dashed my-5"></div>
                <div class="row mb-7">
                    <label class="col-lg-4 fw-semibold text-muted">Date de completion</label>
                    <div class="col-lg-8">
                        <span class="fw-bold fs-6 text-success">
                            {{ $communityRequest->completed_at->format('d/m/Y a H:i') }}
                        </span>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Gestion -->
    <div class="col-xl-4">
        <div class="card mb-5">
            <div class="card-header">
                <h3 class="card-title fw-bold">Gestion</h3>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success mb-5">{{ session('success') }}</div>
                @endif

                <form action="{{ route('community-requests.update', $communityRequest) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-5">
                        <label class="form-label fw-semibold">Statut</label>
                        <select name="status" class="form-select">
                            @foreach($statuses as $key => $label)
                                <option value="{{ $key }}" {{ $communityRequest->status == $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-5">
                        <label class="form-label fw-semibold">Assigner a</label>
                        <select name="assigned_to" class="form-select">
                            <option value="">-- Non assigne --</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ $communityRequest->assigned_to == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-5">
                        <label class="form-label fw-semibold">Notes administratives</label>
                        <textarea name="admin_notes" rows="4" class="form-control"
                            placeholder="Notes internes...">{{ $communityRequest->admin_notes }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-itcj w-100">
                        <i class="ki-duotone ki-check fs-4 me-1"><i class="path1"></i><i class="path2"></i></i>
                        Enregistrer
                    </button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title fw-bold text-danger">Zone de danger</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('community-requests.destroy', $communityRequest) }}" method="POST"
                      data-confirm="Êtes-vous sûr de vouloir supprimer cette demande ?"
                      data-confirm-title="Supprimer la demande"
                      data-confirm-ok="Supprimer">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-itcj-red w-100">
                        <i class="ki-duotone ki-trash fs-4 me-1"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i><i class="path5"></i></i>
                        Supprimer la demande
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
