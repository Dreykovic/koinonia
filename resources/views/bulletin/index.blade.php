@extends('layouts.app')

@section('title', 'Gestion des Bulletins')
@section('page-title', 'Gestion des Bulletins')

@section('breadcrumb')
<li class="breadcrumb-item text-muted">Bulletins</li>
@endsection

@section('page-actions')
<a href="{{ route('bulletin.create') }}" class="btn btn-itcj desktop-add-btn">
    <i class="ki-duotone ki-plus fs-3 me-1"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
    Nouvelle edition
</a>
@endsection

@push('styles')
<style>
    /* Mobile bulletin optimizations */
    @media (max-width: 768px) {
        .table-responsive {
            display: none;
        }

        .bulletin-mobile-list {
            display: block;
        }
    }

    @media (min-width: 769px) {
        .bulletin-mobile-list {
            display: none;
        }
    }

    /* Mobile bulletin cards */
    .bulletin-mobile-card {
        background: var(--bg-card);
        border-radius: 12px;
        padding: 14px;
        margin-bottom: 12px;
        box-shadow: var(--neu-shadow-sm);
    }

    .bulletin-mobile-card.current {
        border-left: 4px solid var(--accent);
        background: linear-gradient(135deg, rgba(212, 168, 75, 0.08) 0%, var(--bg-card) 100%);
    }

    .bulletin-mobile-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 10px;
    }

    .bulletin-mobile-number {
        font-size: 20px;
        font-weight: 800;
        color: var(--primary);
    }

    .bulletin-mobile-title {
        font-size: 14px;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 4px;
    }

    .bulletin-mobile-week {
        font-size: 12px;
        color: var(--text-muted);
    }

    .bulletin-mobile-actions {
        display: flex;
        gap: 8px;
        padding-top: 10px;
        border-top: 1px solid rgba(60, 48, 40, 0.08);
        flex-wrap: wrap;
    }

    .bulletin-mobile-actions .btn {
        flex: 1;
        min-width: calc(50% - 4px);
    }

    .bulletin-mobile-actions form {
        flex: 1;
        min-width: calc(50% - 4px);
    }

    .bulletin-mobile-actions form .btn {
        width: 100%;
    }
</style>
@endpush

@section('content')
@php
    $today = now();
@endphp

<div class="card">
    <div class="card-header py-4">
        <div class="d-flex flex-column w-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h3 class="card-title fw-bold mb-0">Editions du Bulletin</h3>
                    <span class="text-muted fs-7">Gestion des bulletins hebdomadaires</span>
                </div>
            </div>
            <form method="GET" class="d-flex align-items-center gap-3 flex-wrap">
                <div class="position-relative">
                    <i class="ki-duotone ki-magnifier fs-3 position-absolute" style="left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"><i class="path1"></i><i class="path2"></i></i>
                    <input type="text" name="search" class="form-control form-control-sm ps-10" placeholder="Rechercher titre, numero..." value="{{ request('search') }}" style="min-width: 200px;">
                </div>
                <select name="year" class="form-select form-select-sm" style="min-width: 120px;" onchange="this.form.submit()">
                    <option value="">Toutes les annees</option>
                    @foreach($years as $year)
                        <option value="{{ $year }}" {{ request('year') == $year ? 'selected' : '' }}>{{ $year }}</option>
                    @endforeach
                </select>
                <select name="status" class="form-select form-select-sm" style="min-width: 130px;" onchange="this.form.submit()">
                    <option value="">Tous les statuts</option>
                    <option value="published" {{ request('status') == 'published' ? 'selected' : '' }}>Publie</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Brouillon</option>
                </select>
                @if(request()->hasAny(['search', 'year', 'status']))
                    <a href="{{ route('bulletin.index') }}" class="btn btn-sm btn-light">
                        <i class="ki-duotone ki-cross fs-4"></i>
                        Reinitialiser
                    </a>
                @endif
            </form>
        </div>
    </div>
    <div class="card-body py-4">
        @if($bulletins->count() > 0)
            <div class="table-responsive">
                <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
                    <thead>
                        <tr class="fw-bold text-muted">
                            <th class="min-w-80px">N</th>
                            <th class="min-w-120px">Titre</th>
                            <th class="min-w-150px">Semaine</th>
                            <th class="min-w-80px">Annee</th>
                            <th class="min-w-100px">Statut</th>
                            <th class="min-w-100px">Createur</th>
                            <th class="min-w-120px text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bulletins as $bulletin)
                            @php
                                $isCurrent = $bulletin->is_published && $bulletin->week_start <= $today && $bulletin->week_end >= $today;
                            @endphp
                            <tr class="{{ $isCurrent ? 'bg-light-warning' : '' }}">
                                <td>
                                    <span class="fw-bold text-itcj-blue fs-5">{{ $bulletin->edition_number }}</span>
                                </td>
                                <td>
                                    <span class="fw-semibold">{{ $bulletin->title }}</span>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="fw-bold">{{ $bulletin->week_start->format('d/m') }} - {{ $bulletin->week_end->format('d/m/Y') }}</span>
                                        <span class="text-muted fs-7">{{ $bulletin->week_start->locale('fr')->isoFormat('MMMM') }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-light-primary">{{ $bulletin->year }}</span>
                                </td>
                                <td>
                                    @if($isCurrent)
                                        <span class="badge badge-itcj-gold">
                                            <i class="ki-duotone ki-star fs-7 me-1"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                                            Courant
                                        </span>
                                    @elseif($bulletin->is_published)
                                        <span class="badge badge-itcj">Publie</span>
                                    @else
                                        <span class="badge badge-light-warning">Brouillon</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-muted fs-7">{{ $bulletin->creator->name ?? '-' }}</span>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="{{ route('bulletin.show', $bulletin) }}" class="btn btn-icon btn-sm btn-light-primary" title="Voir">
                                            <i class="ki-duotone ki-eye fs-4"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                                        </a>
                                        <a href="{{ route('bulletin.edit', $bulletin) }}" class="btn btn-icon btn-sm btn-light-warning" title="Modifier">
                                            <i class="ki-duotone ki-pencil fs-4"><i class="path1"></i><i class="path2"></i></i>
                                        </a>
                                        <form action="{{ route('bulletin.toggle-publish', $bulletin) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-icon btn-sm {{ $bulletin->is_published ? 'btn-light-danger' : 'btn-light-success' }}" title="{{ $bulletin->is_published ? 'Depublier' : 'Publier' }}">
                                                <i class="ki-duotone ki-{{ $bulletin->is_published ? 'eye-slash' : 'check-circle' }} fs-4"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('bulletin.destroy', $bulletin) }}" method="POST" class="d-inline"
                                              data-confirm="Supprimer cette édition du bulletin ?"
                                              data-confirm-title="Supprimer le bulletin"
                                              data-confirm-ok="Supprimer">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-icon btn-sm btn-light-danger" title="Supprimer">
                                                <i class="ki-duotone ki-trash fs-4"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i><i class="path5"></i></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-4">
                {{ $bulletins->links() }}
            </div>

            <!-- Mobile Cards -->
            <div class="bulletin-mobile-list">
                @foreach($bulletins as $bulletin)
                    @php
                        $isCurrent = $bulletin->is_published && $bulletin->week_start <= $today && $bulletin->week_end >= $today;
                    @endphp
                    <div class="bulletin-mobile-card {{ $isCurrent ? 'current' : '' }}">
                        <div class="bulletin-mobile-header">
                            <div>
                                <span class="bulletin-mobile-number">N°{{ $bulletin->edition_number }}</span>
                                <div class="bulletin-mobile-title">{{ $bulletin->title }}</div>
                                <div class="bulletin-mobile-week">
                                    {{ $bulletin->week_start->format('d/m') }} - {{ $bulletin->week_end->format('d/m/Y') }}
                                </div>
                            </div>
                            <div class="d-flex flex-column align-items-end gap-1">
                                @if($isCurrent)
                                    <span class="badge badge-itcj-gold fs-8">
                                        <i class="ki-duotone ki-star fs-8 me-1"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                                        Courant
                                    </span>
                                @elseif($bulletin->is_published)
                                    <span class="badge badge-itcj fs-8">Publié</span>
                                @else
                                    <span class="badge badge-light-warning fs-8">Brouillon</span>
                                @endif
                                <span class="badge badge-light-primary fs-8">{{ $bulletin->year }}</span>
                            </div>
                        </div>
                        <div class="bulletin-mobile-actions">
                            <a href="{{ route('bulletin.show', $bulletin) }}" class="btn btn-sm btn-light-primary">
                                <i class="ki-duotone ki-eye fs-5"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                                Voir
                            </a>
                            <a href="{{ route('bulletin.edit', $bulletin) }}" class="btn btn-sm btn-light-warning">
                                <i class="ki-duotone ki-pencil fs-5"><i class="path1"></i><i class="path2"></i></i>
                                Modifier
                            </a>
                            <form action="{{ route('bulletin.toggle-publish', $bulletin) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-sm {{ $bulletin->is_published ? 'btn-light-danger' : 'btn-light-success' }}">
                                    <i class="ki-duotone ki-{{ $bulletin->is_published ? 'eye-slash' : 'check-circle' }} fs-5"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i></i>
                                    {{ $bulletin->is_published ? 'Dépublier' : 'Publier' }}
                                </button>
                            </form>
                            <form action="{{ route('bulletin.destroy', $bulletin) }}" method="POST"
                                  data-confirm="Supprimer cette édition du bulletin ?"
                                  data-confirm-title="Supprimer le bulletin"
                                  data-confirm-ok="Supprimer">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-light-danger">
                                    <i class="ki-duotone ki-trash fs-5"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i><i class="path5"></i></i>
                                    Suppr.
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach

                <div class="d-flex justify-content-center mt-4">
                    {{ $bulletins->links() }}
                </div>
            </div>
        @else
            <div class="empty-state py-10">
                <i class="ki-duotone ki-document empty-state-icon fs-3x text-muted"><i class="path1"></i><i class="path2"></i></i>
                <p class="empty-state-text mt-4">Aucune edition de bulletin</p>
                <a href="{{ route('bulletin.create') }}" class="btn btn-itcj mt-3">
                    <i class="ki-duotone ki-plus me-2"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                    Creer la premiere edition
                </a>
            </div>
        @endif
    </div>
</div>

<!-- FAB for mobile -->
<a href="{{ route('bulletin.create') }}" class="fab" aria-label="Nouvelle édition">
    <i class="ki-duotone ki-plus"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
</a>
@endsection



