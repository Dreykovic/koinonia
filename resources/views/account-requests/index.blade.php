@extends('layouts.app')

@section('title', 'Demandes d\'adhésion')
@section('page-title', 'Demandes d\'adhésion')

@section('breadcrumb')
<li class="breadcrumb-item text-muted">Demandes d'adhésion</li>
@endsection

@push('styles')
<style>
    /* Mobile optimizations for account requests */
    @media (max-width: 768px) {
        .desktop-table {
            display: none !important;
        }

        .mobile-card-list {
            display: block !important;
        }
    }

    @media (min-width: 769px) {
        .mobile-card-list {
            display: none !important;
        }
    }

    /* Filter pills container for mobile */
    @media (max-width: 768px) {
        .filter-nav-mobile {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
            margin: 0 -1rem;
            padding: 0 1rem;
            width: 100%;
        }

        .filter-nav-mobile::-webkit-scrollbar {
            display: none;
        }

        .filter-nav-mobile nav {
            display: flex;
            gap: 8px;
            flex-wrap: nowrap;
            padding-bottom: 8px;
        }

        .filter-nav-mobile .btn {
            flex-shrink: 0;
            white-space: nowrap;
        }

        /* Hide header description on mobile */
        .card-header .text-muted.fs-7 {
            display: none;
        }

        .card-header h2 {
            font-size: 1.1rem !important;
        }

        .card-header .d-flex {
            flex-direction: column;
            align-items: flex-start !important;
        }
    }

    /* Mobile request card */
    .request-mobile-card {
        background: var(--bg-card);
        border-radius: 12px;
        padding: 14px;
        margin-bottom: 12px;
        box-shadow: var(--neu-shadow-sm);
    }

    .request-mobile-header {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 10px;
    }

    .request-mobile-avatar {
        width: 40px;
        height: 40px;
        min-width: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        font-weight: 700;
    }

    .request-mobile-info {
        flex: 1;
        min-width: 0;
    }

    .request-mobile-name {
        font-size: 15px;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 2px;
    }

    .request-mobile-email {
        font-size: 12px;
        color: var(--text-muted);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .request-mobile-body {
        display: flex;
        flex-direction: column;
        gap: 6px;
        padding: 10px 0;
        border-top: 1px solid rgba(60, 48, 40, 0.08);
        border-bottom: 1px solid rgba(60, 48, 40, 0.08);
        margin-bottom: 10px;
    }

    .request-mobile-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 13px;
    }

    .request-mobile-label {
        color: var(--text-muted);
    }

    .request-mobile-value {
        color: var(--text-primary);
        font-weight: 500;
    }

    .request-mobile-action .btn {
        width: 100%;
        min-height: 44px;
    }
</style>
@endpush

@section('content')
<section class="card" aria-labelledby="requests-heading">
    <div class="card-header py-4">
        <div class="d-flex justify-content-between align-items-center w-100 flex-wrap gap-3">
            <div>
                <h2 id="requests-heading" class="card-title fw-bold mb-0 fs-4">Demandes d'adhésion</h2>
                <span class="text-muted fs-7">Gérer les demandes d'adhésion à la communauté</span>
            </div>
            <div class="filter-nav-mobile">
                <nav class="d-flex gap-2" aria-label="Filtrer par statut">
                    <a href="{{ route('account-requests.index', ['status' => 'pending']) }}"
                       class="btn btn-sm {{ $status === 'pending' ? 'btn-warning' : 'btn-light' }}"
                       aria-current="{{ $status === 'pending' ? 'page' : 'false' }}"
                       aria-label="Voir les demandes en attente{{ $counts['pending'] > 0 ? ' - ' . $counts['pending'] . ' demandes' : '' }}">
                        En attente
                        @if($counts['pending'] > 0)
                            <span class="badge badge-circle badge-sm ms-1" style="background: #fff; color: #f59e0b;" aria-hidden="true">{{ $counts['pending'] }}</span>
                        @endif
                    </a>
                    <a href="{{ route('account-requests.index', ['status' => 'approved']) }}"
                       class="btn btn-sm {{ $status === 'approved' ? 'btn-success' : 'btn-light' }}"
                       aria-current="{{ $status === 'approved' ? 'page' : 'false' }}"
                       aria-label="Voir les demandes approuvées">
                        Approuvées
                    </a>
                    <a href="{{ route('account-requests.index', ['status' => 'rejected']) }}"
                       class="btn btn-sm {{ $status === 'rejected' ? 'btn-danger' : 'btn-light' }}"
                       aria-current="{{ $status === 'rejected' ? 'page' : 'false' }}"
                       aria-label="Voir les demandes rejetées">
                        Rejetées
                    </a>
                    <a href="{{ route('account-requests.index', ['status' => 'all']) }}"
                       class="btn btn-sm {{ $status === 'all' ? 'btn-itcj' : 'btn-light' }}"
                       aria-current="{{ $status === 'all' ? 'page' : 'false' }}"
                       aria-label="Voir toutes les demandes">
                        Toutes
                    </a>
                </nav>
            </div>
        </div>
    </div>
    <div class="card-body pt-0">
        <!-- Desktop Table -->
        <div class="table-responsive desktop-table">
            <table class="table table-row-dashed align-middle gs-0 gy-4" aria-describedby="requests-heading">
                <caption class="visually-hidden">
                    Liste des demandes d'adhésion
                    @if($status === 'pending') en attente
                    @elseif($status === 'approved') approuvées
                    @elseif($status === 'rejected') rejetées
                    @endif
                </caption>
                <thead>
                    <tr>
                        <th scope="col">Demandeur</th>
                        <th scope="col">Email</th>
                        <th scope="col">Communauté</th>
                        <th scope="col">Date</th>
                        <th scope="col">Statut</th>
                        <th scope="col" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $request)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="symbol symbol-45px me-4" aria-hidden="true">
                                    <span class="symbol-label bg-itcj-blue-light text-itcj-blue fw-bold fs-5">
                                        {{ strtoupper(substr($request->name, 0, 1)) }}
                                    </span>
                                </div>
                                <div>
                                    <span class="fw-bold" style="color: var(--itcj-dark);">{{ $request->name }}</span>
                                    @if($request->phone)
                                        <span class="text-muted d-block fs-7">{{ $request->phone }}</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="text-muted">{{ $request->email }}</td>
                        <td class="text-muted">{{ $request->community?->name ?? '-' }}</td>
                        <td class="text-muted fs-7">
                            <time datetime="{{ $request->created_at->toIso8601String() }}">
                                {{ $request->created_at->format('d/m/Y H:i') }}
                            </time>
                        </td>
                        <td>
                            <span class="badge {{ $request->status_badge_class }} fs-8 d-inline-flex align-items-center gap-1" role="status">
                                @if($request->status === 'pending')
                                    <i class="ki-duotone ki-time fs-8" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
                                @elseif($request->status === 'approved')
                                    <i class="ki-duotone ki-check-circle fs-8" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
                                @else
                                    <i class="ki-duotone ki-cross-circle fs-8" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
                                @endif
                                {{ $request->status_label }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('account-requests.show', $request) }}"
                               class="btn btn-sm btn-itcj-gold"
                               data-tooltip="Voir les détails"
                               aria-label="Voir les détails de la demande de {{ $request->name }}">
                                <i class="ki-duotone ki-eye fs-4" aria-hidden="true"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                                <span class="d-none d-md-inline ms-1">Détails</span>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-10">
                            <div class="empty-state">
                                <i class="ki-duotone ki-people empty-state-icon" aria-hidden="true"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i><i class="path5"></i></i>
                                <p class="empty-state-text">
                                    Aucune demande d'adhésion
                                    @if($status === 'pending') en attente
                                    @elseif($status === 'approved') approuvée
                                    @elseif($status === 'rejected') rejetée
                                    @endif
                                </p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile Cards -->
        <div class="mobile-card-list">
            @forelse($requests as $request)
                <div class="request-mobile-card">
                    <div class="request-mobile-header">
                        <div class="request-mobile-avatar bg-itcj-blue-light text-itcj-blue">
                            {{ strtoupper(substr($request->name, 0, 1)) }}
                        </div>
                        <div class="request-mobile-info">
                            <div class="request-mobile-name">{{ $request->name }}</div>
                            <div class="request-mobile-email">{{ $request->email }}</div>
                        </div>
                        <span class="badge {{ $request->status_badge_class }} fs-8">
                            {{ $request->status_label }}
                        </span>
                    </div>
                    <div class="request-mobile-body">
                        @if($request->phone)
                        <div class="request-mobile-row">
                            <span class="request-mobile-label">Téléphone</span>
                            <span class="request-mobile-value">{{ $request->phone }}</span>
                        </div>
                        @endif
                        @if($request->community)
                        <div class="request-mobile-row">
                            <span class="request-mobile-label">Communauté</span>
                            <span class="request-mobile-value">{{ $request->community->name }}</span>
                        </div>
                        @endif
                        <div class="request-mobile-row">
                            <span class="request-mobile-label">Date</span>
                            <span class="request-mobile-value">{{ $request->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                    </div>
                    <div class="request-mobile-action">
                        <a href="{{ route('account-requests.show', $request) }}" class="btn btn-sm btn-itcj-gold">
                            <i class="ki-duotone ki-eye fs-4" aria-hidden="true"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                            Voir les détails
                        </a>
                    </div>
                </div>
            @empty
                <div class="mobile-empty-state">
                    <div class="mobile-empty-state-icon">
                        <i class="ki-duotone ki-people"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i><i class="path5"></i></i>
                    </div>
                    <div class="mobile-empty-state-title">Aucune demande</div>
                    <div class="mobile-empty-state-text">
                        Aucune demande d'adhésion
                        @if($status === 'pending') en attente
                        @elseif($status === 'approved') approuvée
                        @elseif($status === 'rejected') rejetée
                        @endif
                    </div>
                </div>
            @endforelse
        </div>

        @if($requests->hasPages())
        <nav aria-label="Pagination des demandes">
            {{ $requests->links() }}
        </nav>
        @endif
    </div>
</section>
@endsection
