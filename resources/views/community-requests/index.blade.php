@extends('layouts.app')

@section('title', 'Demandes communautaires')
@section('page-title', 'Demandes de Reparations et Achats')

@section('breadcrumb')
<li class="breadcrumb-item text-muted">Demandes communautaires</li>
@endsection

@section('toolbar')
<div class="d-flex align-items-center gap-2 desktop-add-btn">
    <a href="{{ route('community-requests.form.step1') }}" class="btn btn-sm btn-itcj">
        <i class="ki-duotone ki-plus fs-4 me-1"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
        Nouvelle demande
    </a>
    @if($isAdmin)
    <a href="{{ route('community-requests.statistics') }}" class="btn btn-sm" style="background: var(--itcj-blue-light); color: var(--itcj-blue);">
        <i class="ki-duotone ki-chart-simple fs-4 me-1"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i></i>
        Statistiques
    </a>
    <a href="{{ route('community-requests.export', request()->all()) }}" class="btn btn-sm" style="background: #e8f5e9; color: #2e7d32;">
        <i class="ki-duotone ki-file-down fs-4 me-1"><i class="path1"></i><i class="path2"></i></i>
        Exporter CSV
    </a>
    @endif
</div>
@endsection

@push('styles')
<style>
    /* Mobile optimizations for community requests */
    @media (max-width: 768px) {
        /* Filters scroll horizontal */
        .card-title form {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
            padding-bottom: 8px;
            margin: 0 -1rem;
            padding-left: 1rem;
            padding-right: 1rem;
        }

        .card-title form::-webkit-scrollbar {
            display: none;
        }

        .card-title form .form-select {
            min-width: 130px;
            flex-shrink: 0;
        }

        /* Hide table on mobile */
        .table-responsive {
            display: none;
        }

        .community-requests-mobile {
            display: block;
        }
    }

    @media (min-width: 769px) {
        .community-requests-mobile {
            display: none;
        }
    }

    /* Mobile request cards */
    .cr-mobile-card {
        background: var(--bg-card);
        border-radius: 12px;
        padding: 14px;
        margin-bottom: 12px;
        box-shadow: var(--neu-shadow-sm);
    }

    .cr-mobile-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 10px;
    }

    .cr-mobile-id {
        font-size: 12px;
        color: var(--text-muted);
    }

    .cr-mobile-date {
        font-size: 13px;
        font-weight: 600;
        color: var(--text-primary);
    }

    .cr-mobile-body {
        margin-bottom: 10px;
    }

    .cr-mobile-email {
        font-size: 14px;
        color: var(--text-primary);
        margin-bottom: 8px;
        word-break: break-all;
    }

    .cr-mobile-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    .cr-mobile-action {
        padding-top: 10px;
        border-top: 1px solid rgba(60, 48, 40, 0.08);
    }

    .cr-mobile-action .btn {
        width: 100%;
    }
</style>
@endpush

@section('content')
<div class="card">
    <div class="card-header border-0 pt-6">
        <div class="card-title">
            <form method="GET" class="d-flex align-items-center gap-3 flex-wrap">
                <div class="position-relative">
                    <i class="ki-duotone ki-magnifier fs-3 position-absolute" style="left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"><i class="path1"></i><i class="path2"></i></i>
                    <input type="text" name="search" class="form-control form-control-sm ps-10" placeholder="Rechercher description, email..." value="{{ request('search') }}">
                </div>
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Tous les statuts</option>
                    @foreach($statuses as $key => $label)
                        <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Tous les types</option>
                    <option value="reparation" {{ request('type') == 'reparation' ? 'selected' : '' }}>Reparation</option>
                    <option value="achat" {{ request('type') == 'achat' ? 'selected' : '' }}>Achat</option>
                </select>
                @if($isAdmin)
                <select name="community_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Toutes les communautes</option>
                    @foreach($communities as $community)
                        <option value="{{ $community->id }}" {{ request('community_id') == $community->id ? 'selected' : '' }}>{{ $community->name }}</option>
                    @endforeach
                </select>
                @endif
                @if(request()->hasAny(['search', 'status', 'type', 'community_id']))
                    <a href="{{ route('community-requests.index') }}" class="btn btn-sm btn-light">
                        <i class="ki-duotone ki-cross fs-4"></i>
                        Effacer
                    </a>
                @endif
            </form>
        </div>
    </div>

    <div class="card-body pt-0">
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
                        <th class="min-w-50px">#</th>
                        <th class="min-w-100px">Date</th>
                        @if($isAdmin)
                        <th class="min-w-150px">Email</th>
                        <th class="min-w-100px">Communaute</th>
                        @endif
                        <th class="min-w-80px">Type</th>
                        <th class="min-w-80px">Statut</th>
                        @if($isAdmin)
                        <th class="min-w-100px">Assigne a</th>
                        @endif
                        <th class="min-w-80px text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $request)
                    <tr>
                        <td>
                            <span class="text-dark fw-bold">{{ $request->id }}</span>
                        </td>
                        <td>
                            <span class="text-muted">{{ $request->created_at->format('d/m/Y') }}</span>
                            <span class="text-muted d-block fs-8">{{ $request->created_at->format('H:i') }}</span>
                        </td>
                        @if($isAdmin)
                        <td>
                            <span class="text-dark">{{ $request->email }}</span>
                        </td>
                        <td>
                            <span class="badge badge-light-primary fs-7">{{ $request->community?->name ?? '-' }}</span>
                        </td>
                        @endif
                        <td>
                            @if($request->isRepair())
                                <span class="badge badge-light-warning fs-7">
                                    <i class="ki-duotone ki-wrench fs-6 me-1"><i class="path1"></i><i class="path2"></i></i>
                                    Reparation
                                </span>
                            @else
                                <span class="badge badge-light-success fs-7">
                                    <i class="ki-duotone ki-purchase fs-6 me-1"><i class="path1"></i><i class="path2"></i></i>
                                    Achat
                                </span>
                            @endif
                        </td>
                        <td>
                            @php
                                $statusClasses = [
                                    'pending' => 'badge-light-warning',
                                    'in_progress' => 'badge-light-info',
                                    'completed' => 'badge-light-success',
                                    'rejected' => 'badge-light-danger',
                                ];
                            @endphp
                            <span class="badge {{ $statusClasses[$request->status] ?? 'badge-light' }} fs-7">
                                {{ $request->status_label }}
                            </span>
                        </td>
                        @if($isAdmin)
                        <td>
                            @if($request->assignedUser)
                                <span class="text-dark">{{ $request->assignedUser->name }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        @endif
                        <td class="text-end">
                            <a href="{{ route('community-requests.show', $request) }}" class="btn btn-sm btn-light-primary">
                                <i class="ki-duotone ki-eye fs-4"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                                Voir
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $isAdmin ? 8 : 5 }}" class="text-center py-10">
                            <div class="text-muted">
                                <i class="ki-duotone ki-file-deleted fs-3x text-gray-400 mb-3"><i class="path1"></i><i class="path2"></i></i>
                                <p class="mb-0">Aucune demande trouvee</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile Cards -->
        <div class="community-requests-mobile">
            @forelse($requests as $request)
                @php
                    $statusClasses = [
                        'pending' => 'badge-light-warning',
                        'in_progress' => 'badge-light-info',
                        'completed' => 'badge-light-success',
                        'rejected' => 'badge-light-danger',
                    ];
                @endphp
                <div class="cr-mobile-card">
                    <div class="cr-mobile-header">
                        <div>
                            <div class="cr-mobile-id">#{{ $request->id }}</div>
                            <div class="cr-mobile-date">{{ $request->created_at->format('d/m/Y H:i') }}</div>
                        </div>
                        <span class="badge {{ $statusClasses[$request->status] ?? 'badge-light' }} fs-8">
                            {{ $request->status_label }}
                        </span>
                    </div>
                    <div class="cr-mobile-body">
                        @if($isAdmin)
                        <div class="cr-mobile-email">{{ $request->email }}</div>
                        @endif
                        <div class="cr-mobile-badges">
                            @if($request->isRepair())
                                <span class="badge badge-light-warning fs-8">
                                    <i class="ki-duotone ki-wrench fs-7 me-1"><i class="path1"></i><i class="path2"></i></i>
                                    Réparation
                                </span>
                            @else
                                <span class="badge badge-light-success fs-8">
                                    <i class="ki-duotone ki-purchase fs-7 me-1"><i class="path1"></i><i class="path2"></i></i>
                                    Achat
                                </span>
                            @endif
                            @if($isAdmin && $request->community)
                                <span class="badge badge-light-primary fs-8">{{ $request->community->name }}</span>
                            @endif
                            @if($isAdmin && $request->assignedUser)
                                <span class="badge bg-light text-muted fs-8">{{ $request->assignedUser->name }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="cr-mobile-action">
                        <a href="{{ route('community-requests.show', $request) }}" class="btn btn-sm btn-light-primary">
                            <i class="ki-duotone ki-eye fs-4"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                            Voir les détails
                        </a>
                    </div>
                </div>
            @empty
                <div class="mobile-empty-state">
                    <div class="mobile-empty-state-icon">
                        <i class="ki-duotone ki-file-deleted"><i class="path1"></i><i class="path2"></i></i>
                    </div>
                    <div class="mobile-empty-state-title">Aucune demande</div>
                    <div class="mobile-empty-state-text">Aucune demande trouvée</div>
                </div>
            @endforelse
        </div>

        <div class="d-flex justify-content-end mt-5">
            {{ $requests->withQueryString()->links() }}
        </div>
    </div>
</div>

<!-- FAB for mobile -->
<a href="{{ route('community-requests.form.step1') }}" class="fab" aria-label="Nouvelle demande">
    <i class="ki-duotone ki-plus"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
</a>
@endsection






