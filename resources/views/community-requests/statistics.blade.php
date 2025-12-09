@extends('layouts.app')

@section('title', 'Statistiques des demandes')
@section('page-title', 'Statistiques des Demandes')

@section('breadcrumb')
<li class="breadcrumb-item">
    <a href="{{ route('community-requests.index') }}" class="text-muted text-hover-primary">Demandes</a>
</li>
<li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
<li class="breadcrumb-item text-muted">Statistiques</li>
@endsection

@section('content')
<!-- Stats Cards -->
<div class="row g-5 g-xl-10 mb-5 mb-xl-10">
    <div class="col-xl-3 col-md-6">
        <div class="card bg-primary card-xl-stretch">
            <div class="card-body">
                <i class="ki-duotone ki-file-added fs-2x text-white opacity-75 mb-3"><i class="path1"></i><i class="path2"></i></i>
                <div class="text-white fw-bold fs-2 mb-2">{{ $stats['total'] }}</div>
                <div class="text-white opacity-75">Total des demandes</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card bg-warning card-xl-stretch">
            <div class="card-body">
                <i class="ki-duotone ki-time fs-2x text-white opacity-75 mb-3"><i class="path1"></i><i class="path2"></i></i>
                <div class="text-white fw-bold fs-2 mb-2">{{ $stats['pending'] }}</div>
                <div class="text-white opacity-75">En attente</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card bg-info card-xl-stretch">
            <div class="card-body">
                <i class="ki-duotone ki-loading fs-2x text-white opacity-75 mb-3"><i class="path1"></i><i class="path2"></i></i>
                <div class="text-white fw-bold fs-2 mb-2">{{ $stats['in_progress'] }}</div>
                <div class="text-white opacity-75">En cours</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card bg-success card-xl-stretch">
            <div class="card-body">
                <i class="ki-duotone ki-check-circle fs-2x text-white opacity-75 mb-3"><i class="path1"></i><i class="path2"></i></i>
                <div class="text-white fw-bold fs-2 mb-2">{{ $stats['completed'] }}</div>
                <div class="text-white opacity-75">Terminees</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-5 g-xl-10">
    <!-- Par type -->
    <div class="col-xl-4">
        <div class="card card-xl-stretch">
            <div class="card-header">
                <h3 class="card-title fw-bold">Par Type</h3>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-5">
                    <div class="symbol symbol-40px me-4">
                        <span class="symbol-label bg-light-warning">
                            <i class="ki-duotone ki-wrench fs-2 text-warning"><i class="path1"></i><i class="path2"></i></i>
                        </span>
                    </div>
                    <div class="d-flex flex-column flex-grow-1">
                        <span class="text-gray-800 fw-bold fs-6">Reparations</span>
                        <span class="text-muted fw-semibold">{{ $stats['repairs'] }} demandes</span>
                    </div>
                    <span class="badge badge-light-warning fs-7 fw-bold">
                        {{ $stats['total'] > 0 ? round(($stats['repairs'] / $stats['total']) * 100) : 0 }}%
                    </span>
                </div>
                <div class="d-flex align-items-center">
                    <div class="symbol symbol-40px me-4">
                        <span class="symbol-label bg-light-success">
                            <i class="ki-duotone ki-purchase fs-2 text-success"><i class="path1"></i><i class="path2"></i></i>
                        </span>
                    </div>
                    <div class="d-flex flex-column flex-grow-1">
                        <span class="text-gray-800 fw-bold fs-6">Achats</span>
                        <span class="text-muted fw-semibold">{{ $stats['purchases'] }} demandes</span>
                    </div>
                    <span class="badge badge-light-success fs-7 fw-bold">
                        {{ $stats['total'] > 0 ? round(($stats['purchases'] / $stats['total']) * 100) : 0 }}%
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Par communaute -->
    <div class="col-xl-4">
        <div class="card card-xl-stretch">
            <div class="card-header">
                <h3 class="card-title fw-bold">Par Communaute</h3>
            </div>
            <div class="card-body">
                @forelse($stats['by_community'] as $community => $count)
                <div class="d-flex align-items-center mb-4">
                    <div class="flex-grow-1">
                        <span class="text-gray-800 fw-bold fs-6">{{ $community }}</span>
                    </div>
                    <span class="badge badge-light-primary fs-7 fw-bold">{{ $count }}</span>
                </div>
                @empty
                <div class="text-muted text-center py-5">Aucune donnee</div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Par mois -->
    <div class="col-xl-4">
        <div class="card card-xl-stretch">
            <div class="card-header">
                <h3 class="card-title fw-bold">Par Mois (12 derniers)</h3>
            </div>
            <div class="card-body">
                @forelse($stats['by_month'] as $month => $count)
                <div class="d-flex align-items-center mb-4">
                    <div class="flex-grow-1">
                        <span class="text-gray-800 fw-bold fs-6">{{ \Carbon\Carbon::parse($month . '-01')->locale('fr')->isoFormat('MMMM YYYY') }}</span>
                    </div>
                    <span class="badge badge-light-info fs-7 fw-bold">{{ $count }}</span>
                </div>
                @empty
                <div class="text-muted text-center py-5">Aucune donnee</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
