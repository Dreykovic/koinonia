@extends('layouts.app')

@section('title', 'Gestion Vehicules')
@section('page-title', 'Gestion des Vehicules')

@section('breadcrumb')
<li class="breadcrumb-item text-muted">Vehicules</li>
@endsection

@section('page-actions')
<a href="{{ route('vehicles.create') }}" class="btn btn-sm btn-itcj desktop-add-btn">
    <i class="ki-duotone ki-plus fs-2"></i> Nouveau Vehicule
</a>
@endsection

@push('styles')
<style>
    /* Vehicle mobile card */
    .vehicle-mobile-card {
        background: var(--bg-card);
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 12px;
        box-shadow: var(--neu-shadow-sm);
    }

    .vehicle-mobile-header {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 12px;
    }

    .vehicle-mobile-icon {
        width: 48px;
        height: 48px;
        min-width: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }

    .vehicle-mobile-info {
        flex: 1;
        min-width: 0;
    }

    .vehicle-mobile-name {
        font-size: 15px;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 2px;
    }

    .vehicle-mobile-plate {
        font-size: 12px;
        color: var(--text-muted);
    }

    .vehicle-mobile-body {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 12px;
    }

    .vehicle-mobile-actions {
        display: flex;
        gap: 8px;
        padding-top: 12px;
        border-top: 1px solid rgba(60, 48, 40, 0.08);
    }

    .vehicle-mobile-actions .btn {
        flex: 1;
    }
</style>
@endpush

@section('content')
<div class="card">
    <div class="card-header py-4">
        <h3 class="card-title fw-bold mb-0">Parc automobile</h3>
        <span class="text-muted fs-7">Gestion des vehicules de la communaute</span>
    </div>
    <div class="card-body pt-0">
        <!-- Desktop Table -->
        <div class="table-responsive desktop-table">
            <table class="table table-row-dashed align-middle gs-0 gy-4">
                <thead>
                    <tr>
                        <th>Vehicule</th>
                        <th>Immatriculation</th>
                        <th>Type</th>
                        <th>Capacite</th>
                        <th>Statut</th>
                        <th>Reservations</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vehicles as $vehicle)
                    @php
                        $typeColors = [
                            'sante' => ['bg' => 'var(--itcj-red-light)', 'text' => 'var(--itcj-red)'],
                            'formation' => ['bg' => 'var(--itcj-blue-light)', 'text' => 'var(--itcj-blue)'],
                            'communautaire' => ['bg' => '#e8f5e9', 'text' => '#2e7d32'],
                        ];
                        $type = $typeColors[$vehicle->type->name] ?? ['bg' => '#f5f5f5', 'text' => '#616161'];
                    @endphp
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="symbol symbol-40px me-3">
                                    <span class="symbol-label bg-itcj-blue-light">
                                        <i class="ki-duotone ki-car-2 fs-3 text-itcj-blue"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i><i class="path5"></i></i>
                                    </span>
                                </div>
                                <div>
                                    <span class="fw-bold" style="color: var(--itcj-dark);">{{ $vehicle->name }}</span>
                                    @if($vehicle->description)
                                        <span class="text-muted d-block fs-7">{{ Str::limit($vehicle->description, 30) }}</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-itcj fs-8">{{ $vehicle->license_plate }}</span>
                        </td>
                        <td>
                            <span class="badge fs-8" style="background: {{ $type['bg'] }}; color: {{ $type['text'] }};">
                                {{ ucfirst($vehicle->type->name) }}
                            </span>
                        </td>
                        <td class="text-muted">{{ $vehicle->capacity }} places</td>
                        <td>
                            @if($vehicle->is_available)
                                <span class="badge fs-8" style="background: #e8f5e9; color: #2e7d32;">Disponible</span>
                            @else
                                <span class="badge badge-itcj-red fs-8">Indisponible</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-itcj fs-8">{{ $vehicle->reservations_count ?? 0 }}</span>
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('vehicles.edit', $vehicle) }}" class="btn btn-icon btn-sm btn-itcj-gold">
                                    <i class="ki-duotone ki-pencil fs-4"><i class="path1"></i><i class="path2"></i></i>
                                </a>
                                <form action="{{ route('vehicles.toggle-availability', $vehicle) }}" method="POST">
                                    @csrf
                                    @if($vehicle->is_available)
                                    <button type="submit" class="btn btn-icon btn-sm btn-itcj-red" title="Rendre indisponible">
                                        <i class="ki-duotone ki-cross fs-4"><i class="path1"></i><i class="path2"></i></i>
                                    </button>
                                    @else
                                    <button type="submit" class="btn btn-icon btn-sm" title="Rendre disponible"
                                        style="background: #e8f5e9; color: #2e7d32;">
                                        <i class="ki-duotone ki-check fs-4"><i class="path1"></i><i class="path2"></i></i>
                                    </button>
                                    @endif
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-10">
                            <div class="empty-state">
                                <i class="ki-duotone ki-car-2 empty-state-icon"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i><i class="path5"></i></i>
                                <p class="empty-state-text">Aucun vehicule enregistre</p>
                                <a href="{{ route('vehicles.create') }}" class="btn btn-itcj btn-sm mt-3">
                                    Ajouter un vehicule
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile Cards -->
        <div class="mobile-card-list">
            @forelse($vehicles as $vehicle)
                @php
                    $typeColors = [
                        'sante' => ['bg' => 'var(--itcj-red-light)', 'text' => 'var(--itcj-red)'],
                        'formation' => ['bg' => 'var(--itcj-blue-light)', 'text' => 'var(--itcj-blue)'],
                        'communautaire' => ['bg' => '#e8f5e9', 'text' => '#2e7d32'],
                    ];
                    $type = $typeColors[$vehicle->type->name] ?? ['bg' => '#f5f5f5', 'text' => '#616161'];
                @endphp
                <div class="vehicle-mobile-card">
                    <div class="vehicle-mobile-header">
                        <div class="vehicle-mobile-icon bg-itcj-blue-light text-itcj-blue">
                            <i class="ki-duotone ki-car-2"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i><i class="path5"></i></i>
                        </div>
                        <div class="vehicle-mobile-info">
                            <div class="vehicle-mobile-name">{{ $vehicle->name }}</div>
                            <div class="vehicle-mobile-plate">{{ $vehicle->license_plate }}</div>
                        </div>
                        @if($vehicle->is_available)
                            <span class="badge fs-8" style="background: #e8f5e9; color: #2e7d32;">Dispo</span>
                        @else
                            <span class="badge badge-itcj-red fs-8">Indispo</span>
                        @endif
                    </div>
                    <div class="vehicle-mobile-body">
                        <span class="badge fs-8" style="background: {{ $type['bg'] }}; color: {{ $type['text'] }};">
                            {{ ucfirst($vehicle->type->name) }}
                        </span>
                        <span class="badge bg-light text-muted fs-8">{{ $vehicle->capacity }} places</span>
                        <span class="badge badge-itcj fs-8">{{ $vehicle->reservations_count ?? 0 }} réserv.</span>
                    </div>
                    <div class="vehicle-mobile-actions">
                        <a href="{{ route('vehicles.edit', $vehicle) }}" class="btn btn-sm btn-light">
                            <i class="ki-duotone ki-pencil fs-4"><i class="path1"></i><i class="path2"></i></i>
                            Modifier
                        </a>
                        <form action="{{ route('vehicles.toggle-availability', $vehicle) }}" method="POST" class="flex-grow-1">
                            @csrf
                            @if($vehicle->is_available)
                            <button type="submit" class="btn btn-sm btn-light-danger w-100">
                                <i class="ki-duotone ki-cross fs-4"><i class="path1"></i><i class="path2"></i></i>
                                Désactiver
                            </button>
                            @else
                            <button type="submit" class="btn btn-sm btn-light-success w-100">
                                <i class="ki-duotone ki-check fs-4"><i class="path1"></i><i class="path2"></i></i>
                                Activer
                            </button>
                            @endif
                        </form>
                    </div>
                </div>
            @empty
                <div class="mobile-empty-state">
                    <div class="mobile-empty-state-icon">
                        <i class="ki-duotone ki-car-2"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i><i class="path5"></i></i>
                    </div>
                    <div class="mobile-empty-state-title">Aucun véhicule</div>
                    <div class="mobile-empty-state-text">Aucun véhicule enregistré dans le parc automobile.</div>
                    <a href="{{ route('vehicles.create') }}" class="btn btn-itcj">
                        Ajouter un véhicule
                    </a>
                </div>
            @endforelse
        </div>
    </div>
</div>

<!-- FAB for mobile -->
<a href="{{ route('vehicles.create') }}" class="fab" aria-label="Ajouter un véhicule">
    <i class="ki-duotone ki-plus"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
</a>
@endsection




