@extends('layouts.app')

@section('title', 'Lieux de reparation')
@section('page-title', 'Gestion des Lieux de Reparation')

@section('breadcrumb')
<li class="breadcrumb-item text-muted">Configuration</li>
<li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
<li class="breadcrumb-item text-muted">Lieux de reparation</li>
@endsection

@section('content')
<div class="row g-5 g-xl-10">
    <!-- Liste des lieux -->
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title fw-bold">Lieux de Reparation</h3>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success mb-5">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger mb-5">{{ session('error') }}</div>
                @endif

                <div class="table-responsive">
                    <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
                        <thead>
                            <tr class="fw-bold text-muted">
                                <th>Nom</th>
                                <th class="text-center">Demandes</th>
                                <th class="text-center">Statut</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($locations as $location)
                            <tr>
                                <td>
                                    <span class="text-dark fw-bold">{{ $location->name }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-light-primary">{{ $location->community_requests_count }}</span>
                                </td>
                                <td class="text-center">
                                    @if($location->is_active)
                                        <span class="badge badge-light-success">Actif</span>
                                    @else
                                        <span class="badge badge-light-danger">Inactif</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-light-primary"
                                        data-bs-toggle="modal" data-bs-target="#editModal{{ $location->id }}">
                                        <i class="ki-duotone ki-pencil fs-5"><i class="path1"></i><i class="path2"></i></i>
                                    </button>
                                    @if($location->community_requests_count == 0)
                                    <form action="{{ route('repair-locations.destroy', $location) }}" method="POST" class="d-inline"
                                          data-confirm="Supprimer ce lieu de réparation ?"
                                          data-confirm-title="Supprimer le lieu"
                                          data-confirm-ok="Supprimer">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light-danger">
                                            <i class="ki-duotone ki-trash fs-5"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i><i class="path5"></i></i>
                                        </button>
                                    </form>
                                    @endif
                                </td>
                            </tr>

                            <!-- Modal Edit -->
                            <div class="modal fade" id="editModal{{ $location->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form action="{{ route('repair-locations.update', $location) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-header">
                                                <h5 class="modal-title">Modifier le lieu</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-5">
                                                    <label class="form-label fw-semibold required">Nom</label>
                                                    <input type="text" name="name" class="form-control" value="{{ $location->name }}" required>
                                                </div>
                                                <div class="mb-5">
                                                    <label class="form-label fw-semibold">Statut</label>
                                                    <div class="form-check form-switch">
                                                        <input type="hidden" name="is_active" value="0">
                                                        <input type="checkbox" name="is_active" value="1" class="form-check-input"
                                                            {{ $location->is_active ? 'checked' : '' }}>
                                                        <label class="form-check-label">Actif</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                                                <button type="submit" class="btn btn-primary">Enregistrer</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-10">
                                    <div class="text-muted">Aucun lieu configure</div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Ajouter un lieu -->
    <div class="col-xl-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title fw-bold">Ajouter un Lieu</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('repair-locations.store') }}" method="POST">
                    @csrf
                    <div class="mb-5">
                        <label class="form-label fw-semibold required">Nom du lieu</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name') }}" placeholder="Ex: Salle de reunion" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-itcj w-100">
                        <i class="ki-duotone ki-plus fs-4 me-1"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                        Ajouter
                    </button>
                </form>
            </div>
        </div>

        <div class="card mt-5">
            <div class="card-header">
                <h3 class="card-title fw-bold">Informations</h3>
            </div>
            <div class="card-body">
                <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-6">
                    <i class="ki-duotone ki-information fs-2tx text-primary me-4"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                    <div class="d-flex flex-stack flex-grow-1">
                        <div class="fw-semibold">
                            <div class="fs-6 text-gray-700">
                                Les lieux de reparation sont utilises dans le formulaire de demande pour permettre
                                aux utilisateurs de specifier ou se trouve le probleme a reparer.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
