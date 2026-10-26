@extends('layouts.app')

@section('title', 'Vehicules pour Profil')
@section('page-title', 'Gestion des Profiles et vehicules')

@section('breadcrumb')
<li class="breadcrumb-item text-muted">Configuration</li>
<li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
<li class="breadcrumb-item text-muted">Profile utilisateur et Vehicule</li>
@endsection

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-5" role="alert">
            <div class="d-flex align-items-center">
                <i class="ki-duotone ki-check-circle fs-2x text-success me-3">
                    <i class="path1"></i>
                    <i class="path2"></i>
                </i>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-5" role="alert">
            <div class="d-flex align-items-center">
                <i class="ki-duotone ki-cross-circle fs-2x text-danger me-3">
                    <i class="path1"></i>
                    <i class="path2"></i>
                </i>
                <span>{{ session('error') }}</span>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-5 g-xl-8">
        <!-- Liste des profils -->
        <div class="col-xl-9">
            <div class="card card-flush h-100">
                <div class="card-header pt-7">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold text-gray-800">Profils Utilisateurs</span>
                        <span class="text-muted mt-1 fw-semibold fs-7">Gérer les autorisations de réservation</span>
                    </h3>
                </div>
                <div class="card-body pt-5">
                    <div class="table-responsive">
                        <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
                            <thead>
                                <tr class="fw-bold text-muted bg-light">
                                    <th class="ps-4 min-w-200px rounded-start">Profil</th>
                                    <th class="min-w-250px">Types de Véhicules Autorisés</th>
                                    <th class="text-center min-w-100px">Utilisateurs</th>
                                    <th class="text-center min-w-100px">Statut</th>
                                    <th class="text-end pe-4 min-w-100px rounded-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($userProfiles as $profile)
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <div class="symbol symbol-45px me-3">
                                                    <span class="symbol-label bg-light-primary">
                                                        <i class="ki-duotone ki-profile-user fs-2x text-primary">
                                                            <i class="path1"></i>
                                                            <i class="path2"></i>
                                                            <i class="path3"></i>
                                                            <i class="path4"></i>
                                                        </i>
                                                    </span>
                                                </div>
                                                <div class="d-flex flex-column">
                                                    <span class="text-gray-800 fw-bold mb-1">{{ $profile->name }}</span>
                                                    @if($profile->description)
                                                        <span class="text-muted fs-7">{{ Str::limit($profile->description, 50) }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-2">
                                                @forelse($profile->vehicleTypes as $type)
                                                    <span class="badge badge-lg {{ $type->pivot->is_active ? 'badge-light-success' : 'badge-light-secondary' }}">
                                                        {{ $type->name }}
                                                        @if(!$type->pivot->is_active)
                                                            <i class="ki-duotone ki-cross-circle fs-7 ms-1">
                                                                <i class="path1"></i>
                                                                <i class="path2"></i>
                                                            </i>
                                                        @endif
                                                    </span>
                                                @empty
                                                    <span class="text-muted fst-italic fs-7">Aucun type assigné</span>
                                                @endforelse
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-light-primary fs-7 fw-bold">{{ $profile->users_count }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if($profile->is_active)
                                                <span class="badge badge-light-success">Actif</span>
                                            @else
                                                <span class="badge badge-light-danger">Inactif</span>
                                            @endif
                                        </td>
                                        <td class="text-end pe-4">
                                            <button type="button" 
                                                    class="btn btn-sm btn-light-primary btn-active-light-primary"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#manageModal{{ $profile->id }}">
                                                <i class="ki-duotone ki-setting-2 fs-5">
                                                    <i class="path1"></i>
                                                    <i class="path2"></i>
                                                </i>
                                                Gérer
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-10">
                                            <div class="d-flex flex-column align-items-center">
                                                <i class="ki-duotone ki-information-5 fs-5x text-muted mb-3">
                                                    <i class="path1"></i>
                                                    <i class="path2"></i>
                                                    <i class="path3"></i>
                                                </i>
                                                <span class="text-muted fs-6">Aucun profil utilisateur configuré</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panneau latéral -->
        <div class="col-xl-3">
            <!-- Statistiques -->
            <div class="card card-flush mb-5">
                <div class="card-header pt-5">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold text-gray-800">Statistiques</span>
                    </h3>
                </div>
                <div class="card-body pt-5">
                    <div class="d-flex flex-column gap-7">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-50px me-4">
                                <span class="symbol-label bg-light-primary">
                                    <i class="ki-duotone ki-profile-user fs-2x text-primary">
                                        <i class="path1"></i>
                                        <i class="path2"></i>
                                        <i class="path3"></i>
                                        <i class="path4"></i>
                                    </i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <span class="text-gray-800 fw-bold d-block fs-4">{{ $userProfiles->count() }}</span>
                                <span class="text-muted fw-semibold fs-7">Profils</span>
                            </div>
                        </div>

                        <div class="separator"></div>

                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-50px me-4">
                                <span class="symbol-label bg-light-success">
                                    <i class="ki-duotone ki-truck fs-2x text-success">
                                        <i class="path1"></i>
                                        <i class="path2"></i>
                                        <i class="path3"></i>
                                        <i class="path4"></i>
                                        <i class="path5"></i>
                                    </i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <span class="text-gray-800 fw-bold d-block fs-4">{{ $vehicleTypes->count() }}</span>
                                <span class="text-muted fw-semibold fs-7">Types disponibles</span>
                            </div>
                        </div>

                        <div class="separator"></div>

                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-50px me-4">
                                <span class="symbol-label bg-light-info">
                                    <i class="ki-duotone ki-abstract-26 fs-2x text-info">
                                        <i class="path1"></i>
                                        <i class="path2"></i>
                                    </i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <span class="text-gray-800 fw-bold d-block fs-4">
                                    {{ $userProfiles->sum(fn($p) => $p->vehicleTypes->count()) }}
                                </span>
                                <span class="text-muted fw-semibold fs-7">Associations</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informations -->
            <div class="card card-flush mb-5">
                <div class="card-header pt-5">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold text-gray-800">Informations</span>
                    </h3>
                </div>
                <div class="card-body pt-5">
                    <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-5">
                        <i class="ki-duotone ki-information fs-2tx text-primary me-3 flex-shrink-0">
                            <i class="path1"></i>
                            <i class="path2"></i>
                            <i class="path3"></i>
                        </i>
                        <div class="fw-semibold">
                            <div class="fs-7 text-gray-700">
                                Gérez les types de véhicules autorisés pour chaque profil.
                                <div class="mt-3">
                                    <strong>Actions :</strong>
                                    <ul class="ps-3 mb-0 mt-2">
                                        <li class="mb-1">Cliquez sur "Gérer"</li>
                                        <li class="mb-1">Activez/désactivez les types</li>
                                        <li>Ajoutez de nouveaux types</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Liste des types -->
            <div class="card card-flush">
                <div class="card-header pt-5">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold text-gray-800">Types Disponibles</span>
                        <span class="text-muted mt-1 fw-semibold fs-7">{{ $vehicleTypes->count() }} types</span>
                    </h3>
                </div>
                <div class="card-body pt-5">
                    @foreach($vehicleTypes as $type)
                        <div class="d-flex align-items-start mb-4 {{ !$loop->last ? 'pb-3 border-bottom border-gray-300' : '' }}">
                            <i class="ki-duotone ki-check-circle fs-2 text-success me-3 mt-1">
                                <i class="path1"></i>
                                <i class="path2"></i>
                            </i>
                            <div class="flex-grow-1">
                                <span class="fw-semibold text-gray-800 d-block">{{ $type->name }}</span>
                                @if($type->description)
                                    <span class="text-muted fs-7 d-block mt-1">{{ $type->description }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    @foreach($userProfiles as $profile)
        <div class="modal fade" id="manageModal{{ $profile->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header" >
                        <h3 class="modal-title text-white">
                            <i class="ki-duotone ki-setting-2 fs-2 text-white me-2">
                                <i class="path1"></i>
                                <i class="path2"></i>
                            </i>
                            {{ $profile->name }} - Types de Véhicules
                        </h3>
                        <button type="button" class="btn btn-icon btn-sm btn-itcj ms-2" data-bs-dismiss="modal">
                            <i class="ki-duotone ki-cross fs-1 text-white">
                                <i class="path1"></i>
                                <i class="path2"></i>
                            </i>
                        </button>
                    </div>
                    
                    <div class="modal-body py-8">
                        <!-- Types assignés -->
                        @if($profile->vehicleTypes->isNotEmpty())
                        <div class="mb-10">
                            <div class="d-flex align-items-center mb-5">
                                <i class="ki-duotone ki-abstract-26 fs-2 text-primary me-2">
                                    <i class="path1"></i>
                                    <i class="path2"></i>
                                </i>
                                <h4 class="mb-0 fw-bold">Types Assignés</h4>
                                <span class="badge badge-light-primary ms-2">{{ $profile->vehicleTypes->count() }}</span>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-row-bordered table-row-gray-300 align-middle gs-0 gy-3">
                                    <thead>
                                        <tr class="fw-bold text-muted bg-light">
                                            <th class="ps-4 rounded-start">Type de Véhicule</th>
                                            <th class="text-center">Autorisation</th>
                                            <th class="pe-4 text-end rounded-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($profile->vehicleTypes as $type)
                                        <tr>
                                            <td class="ps-4">
                                                <div class="d-flex flex-column">
                                                    <span class="text-gray-800 fw-bold">{{ $type->name }}</span>
                                                    @if($type->description)
                                                        <span class="text-muted fs-7">{{ $type->description }}</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check form-switch form-check-custom form-check-solid justify-content-center">
                                                    <input 
                                                        type="checkbox" 
                                                        class="form-check-input h-30px w-50px" 
                                                        {{ $type->pivot->is_active ? 'checked' : '' }}
                                                        onchange="toggleStatus({{ $profile->id }}, {{ $type->id }}, this.checked)">
                                                    <label class="form-check-label ms-2 text-muted fs-7">
                                                        {{ $type->pivot->is_active ? 'Activé' : 'Désactivé' }}
                                                    </label>
                                                </div>
                                            </td>
                                            <td class="pe-4 text-end">
                                                <form action="{{ route('user-profile-vehicle-types.destroy', [$profile, $type]) }}" 
                                                      method="POST" 
                                                      class="d-inline"
                                                      data-confirm="Le type « {{ $type->name }} » ne sera plus associé au profil « {{ $profile->name }} »."
                                                      data-confirm-title="Dissocier ce type de véhicule ?"
                                                      data-confirm-ok="Dissocier">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-light-danger btn-active-danger">
                                                        <i class="ki-duotone ki-trash fs-5">
                                                            <i class="path1"></i>
                                                            <i class="path2"></i>
                                                            <i class="path3"></i>
                                                            <i class="path4"></i>
                                                            <i class="path5"></i>
                                                        </i>
                                                        Dissocier
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="separator separator-dashed my-8"></div>
                        @endif

                        <!-- Ajouter un type -->
                        @php
                            $assignedIds = $profile->vehicleTypes->pluck('id');
                            $availableTypes = $vehicleTypes->whereNotIn('id', $assignedIds);
                        @endphp

                        <div>
                            <div class="d-flex align-items-center mb-5">
                                <i class="ki-duotone ki-plus-circle fs-2 text-success me-2">
                                    <i class="path1"></i>
                                    <i class="path2"></i>
                                </i>
                                <h4 class="mb-0 fw-bold">Ajouter un Type</h4>
                            </div>

                            @if($availableTypes->isNotEmpty())
                                <form action="{{ route('user-profile-vehicle-types.store') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="user_profile_id" value="{{ $profile->id }}">
                                    
                                    <div class="row g-3">
                                        <div class="col-md-9">
                                            <label class="form-label fw-semibold required">Type de véhicule</label>
                                            <select name="vehicle_type_id" class="form-select form-select-solid" required>
                                                <option value="">-- Sélectionner un type --</option>
                                                @foreach($availableTypes as $type)
                                                    <option value="{{ $type->id }}">
                                                        {{ $type->name }}
                                                        @if($type->description) - {{ $type->description }} @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3 d-flex align-items-end">
                                            <button type="submit" class="btn btn-primary w-100">
                                                <i class="ki-duotone ki-plus fs-3">
                                                    <i class="path1"></i>
                                                    <i class="path2"></i>
                                                    <i class="path3"></i>
                                                </i>
                                                Ajouter
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            @else
                                <div class="alert alert-dismissible bg-light-info d-flex flex-column flex-sm-row p-5 mb-0">
                                    <i class="ki-duotone ki-information-5 fs-2hx text-info me-4 mb-5 mb-sm-0">
                                        <i class="path1"></i>
                                        <i class="path2"></i>
                                        <i class="path3"></i>
                                    </i>
                                    <div class="d-flex flex-column text-info pe-0 pe-sm-10">
                                        <h5 class="mb-1">Tous les types sont assignés</h5>
                                        <span>Tous les types de véhicules disponibles sont déjà associés à ce profil.</span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Fermer</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulaire caché pour le toggle -->
        <form id="toggleForm{{ $profile->id }}" method="POST" style="display: none;">
            @csrf
            @method('PUT')
            <input type="hidden" name="is_active" id="toggleInput{{ $profile->id }}">
        </form>
    @endforeach
@endsection

@push('scripts')
<script>
    function toggleStatus(profileId, typeId, isActive) {
        const form = document.getElementById('toggleForm' + profileId);
        const input = document.getElementById('toggleInput' + profileId);
        
        form.action = "{{ url('user-profile-vehicle-types') }}/" + profileId + "/" + typeId;
        input.value = isActive ? 1 : 0;
        
        form.submit();
    }
</script>
@endpush