@extends('layouts.app')

@section('title', 'Gestion des Repas')
@section('page-title', 'Gestion des Repas du Dimanche')

@section('breadcrumb')
<li class="breadcrumb-item text-muted">Repas</li>
@endsection

@section('page-actions')
<a href="{{ route('meals.admin.create') }}" class="btn btn-itcj">
    <i class="ki-duotone ki-plus fs-3 me-1"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
    Nouveau Repas
</a>
@endsection

@section('content')
@php
    $today = now();
@endphp

<!-- Filtres -->
<div class="card mb-5">
    <div class="card-body py-4">
        <form method="GET" class="d-flex align-items-center gap-3 flex-wrap">
            <select name="status" class="form-select form-select-sm" style="min-width: 140px;" onchange="this.form.submit()">
                <option value="">Tous les statuts</option>
                <option value="open" {{ ($statusFilter ?? '') === 'open' ? 'selected' : '' }}>Ouvert</option>
                <option value="closed" {{ ($statusFilter ?? '') === 'closed' ? 'selected' : '' }}>Ferme</option>
                <option value="full" {{ ($statusFilter ?? '') === 'full' ? 'selected' : '' }}>Complet</option>
            </select>
            <select name="period" class="form-select form-select-sm" style="min-width: 140px;" onchange="this.form.submit()">
                <option value="">Toutes les periodes</option>
                <option value="upcoming" {{ ($periodFilter ?? '') === 'upcoming' ? 'selected' : '' }}>A venir</option>
                <option value="past" {{ ($periodFilter ?? '') === 'past' ? 'selected' : '' }}>Passes</option>
            </select>
            @if(($statusFilter ?? '') || ($periodFilter ?? ''))
                <a href="{{ route('meals.admin.index') }}" class="btn btn-sm btn-light">
                    <i class="ki-duotone ki-cross fs-4"></i>
                    Reinitialiser
                </a>
            @endif
        </form>
    </div>
</div>

<!-- Repas a venir -->
@if(($periodFilter ?? '') !== 'past')
<div class="card mb-5">
    <div class="card-header py-4">
        <h3 class="card-title fw-bold mb-0">
            <i class="ki-duotone ki-calendar text-itcj-blue me-2"><i class="path1"></i><i class="path2"></i></i>
            Repas a venir
        </h3>
    </div>
    <div class="card-body py-4">
        @if($upcomingMeals->count() > 0)
            <div class="table-responsive">
                <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
                    <thead>
                        <tr class="fw-bold text-muted">
                            <th class="min-w-150px">Date</th>
                            <th class="min-w-100px">Inscriptions</th>
                            <th class="min-w-120px">Capacite</th>
                            <th class="min-w-150px">Deadline inscription</th>
                            <th class="min-w-100px">Statut</th>
                            <th class="min-w-120px text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($upcomingMeals as $meal)
                            @php
                                $percentage = $meal->max_capacity > 0 ? ($meal->active_inscriptions_count / $meal->max_capacity) * 100 : 0;
                                $progressColor = $percentage > 90 ? 'bg-danger' : ($percentage > 70 ? 'bg-warning' : 'bg-success');
                            @endphp
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="symbol symbol-45px me-3">
                                            <span class="symbol-label bg-itcj-blue-light">
                                                <i class="ki-duotone ki-gift fs-2 text-itcj-blue"><i class="path1"></i><i class="path2"></i></i>
                                            </span>
                                        </div>
                                        <div>
                                            <span class="fw-bold text-dark d-block">{{ $meal->formatted_date }}</span>
                                            <span class="text-muted fs-7">{{ $meal->meal_date->locale('fr')->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="fw-bold text-dark me-2">{{ $meal->active_inscriptions_count }}</span>
                                        <span class="text-muted">/ {{ $meal->max_capacity }}</span>
                                    </div>
                                    <div class="progress h-6px w-100px mt-1">
                                        <div class="progress-bar {{ $progressColor }}" style="width: {{ $percentage }}%"></div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-light-primary fs-7">{{ $meal->remaining_places }} restantes</span>
                                </td>
                                <td>
                                    <span class="text-muted fs-7">{{ $meal->registration_deadline ? $meal->registration_deadline->format('d/m/Y H:i') : '-' }}</span>
                                    @if($meal->isRegistrationClosed())
                                        <span class="badge badge-light-danger fs-8 ms-1">Passee</span>
                                    @endif
                                </td>
                                <td>
                                    @if(!$meal->is_open)
                                        <span class="badge badge-light-danger">Ferme</span>
                                    @elseif($meal->isFull())
                                        <span class="badge badge-light-warning">Complet</span>
                                    @elseif($meal->isRegistrationClosed())
                                        <span class="badge badge-light-secondary">Termine</span>
                                    @else
                                        <span class="badge badge-light-success">Ouvert</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="{{ route('meals.admin.show', $meal) }}" class="btn btn-icon btn-sm btn-light-primary" title="Voir">
                                            <i class="ki-duotone ki-eye fs-4"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                                        </a>
                                        <a href="{{ route('meals.admin.edit', $meal) }}" class="btn btn-icon btn-sm btn-light-warning" title="Modifier">
                                            <i class="ki-duotone ki-pencil fs-4"><i class="path1"></i><i class="path2"></i></i>
                                        </a>
                                        <form action="{{ route('meals.admin.toggle', $meal) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-icon btn-sm {{ $meal->is_open ? 'btn-light-danger' : 'btn-light-success' }}" title="{{ $meal->is_open ? 'Fermer' : 'Ouvrir' }}">
                                                <i class="ki-duotone ki-{{ $meal->is_open ? 'lock' : 'lock-3' }} fs-4"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('meals.admin.destroy', $meal) }}" method="POST" class="d-inline"
                                              data-confirm="Supprimer ce repas et toutes ses inscriptions ?"
                                              data-confirm-title="Supprimer le repas"
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
        @else
            <div class="text-center py-10">
                <i class="ki-duotone ki-gift fs-3x text-muted mb-3"><i class="path1"></i><i class="path2"></i></i>
                <p class="text-muted mb-3">Aucun repas programme</p>
                <a href="{{ route('meals.admin.create') }}" class="btn btn-itcj">
                    <i class="ki-duotone ki-plus me-1"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                    Creer un repas
                </a>
            </div>
        @endif
    </div>
</div>
@endif

<!-- Repas passes -->
@if(($periodFilter ?? '') !== 'upcoming' && $pastMeals->count() > 0)
<div class="card">
    <div class="card-header py-4">
        <h3 class="card-title fw-bold mb-0">
            <i class="ki-duotone ki-time text-muted me-2"><i class="path1"></i><i class="path2"></i></i>
            Historique
        </h3>
    </div>
    <div class="card-body py-4">
        <div class="table-responsive">
            <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
                <thead>
                    <tr class="fw-bold text-muted">
                        <th class="min-w-150px">Date</th>
                        <th class="min-w-100px">Participants</th>
                        <th class="min-w-100px text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pastMeals as $meal)
                        <tr>
                            <td>
                                <span class="text-muted">{{ $meal->formatted_date }}</span>
                            </td>
                            <td>
                                <span class="fw-bold">{{ $meal->active_inscriptions_count }}</span>
                                <span class="text-muted">/ {{ $meal->max_capacity }}</span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('meals.admin.show', $meal) }}" class="btn btn-icon btn-sm btn-light-primary" title="Voir details">
                                    <i class="ki-duotone ki-eye fs-4"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection




