@extends('layouts.app')

@section('title', 'Repas du ' . $meal->meal_date->format('d/m/Y'))
@section('page-title', 'Repas du Dimanche ' . $meal->meal_date->format('d/m/Y'))

@section('breadcrumb')
<li class="breadcrumb-item">
    <a href="{{ route('meals.admin.index') }}" class="text-muted text-hover-primary">Repas</a>
</li>
<li class="breadcrumb-item text-muted">{{ $meal->meal_date->format('d/m/Y') }}</li>
@endsection

@section('page-actions')
<a href="{{ route('meals.admin.edit', $meal) }}" class="btn btn-itcj-outline me-2">
    <i class="ki-duotone ki-pencil fs-4 me-1"><i class="path1"></i><i class="path2"></i></i>
    Modifier
</a>
<form action="{{ route('meals.admin.toggle', $meal) }}" method="POST" class="d-inline">
    @csrf
    @if($meal->is_open)
        <button type="submit" class="btn btn-light-danger">
            <i class="ki-duotone ki-lock fs-4 me-1"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
            Fermer inscriptions
        </button>
    @else
        <button type="submit" class="btn btn-light-success">
            <i class="ki-duotone ki-lock-3 fs-4 me-1"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
            Ouvrir inscriptions
        </button>
    @endif
</form>
@endsection

@section('content')
<div class="row g-5">
    <!-- Stats et infos -->
    <div class="col-xl-4">
        <div class="card mb-5">
            <div class="card-header py-4">
                <h3 class="card-title fw-bold mb-0">
                    <i class="ki-duotone ki-dish text-itcj-blue me-2"><i class="path1"></i><i class="path2"></i></i>
                    Informations
                </h3>
            </div>
            <div class="card-body">
                <div class="d-flex flex-stack py-3 border-bottom">
                    <span class="text-muted">Date</span>
                    <span class="fw-bold text-dark">{{ $meal->formatted_date }}</span>
                </div>
                <div class="d-flex flex-stack py-3 border-bottom">
                    <span class="text-muted">Capacite</span>
                    <span class="fw-bold text-dark">{{ $meal->max_capacity }} places</span>
                </div>
                <div class="d-flex flex-stack py-3 border-bottom">
                    <span class="text-muted">Inscrits</span>
                    <span class="fw-bold text-itcj-blue">{{ $activeInscriptions->count() }}</span>
                </div>
                <div class="d-flex flex-stack py-3 border-bottom">
                    <span class="text-muted">Places restantes</span>
                    <span class="fw-bold {{ $meal->remaining_places < 3 ? 'text-danger' : 'text-success' }}">
                        {{ $meal->remaining_places }}
                    </span>
                </div>
                <div class="d-flex flex-stack py-3 border-bottom">
                    <span class="text-muted">Deadline inscription</span>
                    <span class="text-dark fs-7">{{ $meal->registration_deadline ? $meal->registration_deadline->format('d/m/Y H:i') : '-' }}</span>
                </div>
                <div class="d-flex flex-stack py-3 border-bottom">
                    <span class="text-muted">Deadline annulation</span>
                    <span class="text-dark fs-7">{{ $meal->cancellation_deadline ? $meal->cancellation_deadline->format('d/m/Y H:i') : '-' }}</span>
                </div>
                <div class="d-flex flex-stack py-3">
                    <span class="text-muted">Statut</span>
                    <span class="badge {{ $meal->is_open ? 'badge-light-success' : 'badge-light-danger' }}">
                        {{ $meal->status_label }}
                    </span>
                </div>

                @if($meal->notes)
                    <div class="mt-4 p-3 rounded" style="background: var(--itcj-gold-light);">
                        <div class="fw-semibold text-muted mb-1">Notes:</div>
                        <div class="fs-7">{{ $meal->notes }}</div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Progression -->
        <div class="card">
            <div class="card-body">
                @php
                    $percentage = $meal->max_capacity > 0 ? ($activeInscriptions->count() / $meal->max_capacity) * 100 : 0;
                    $progressColor = $percentage > 90 ? 'bg-danger' : ($percentage > 70 ? 'bg-warning' : 'bg-success');
                @endphp
                <div class="d-flex justify-content-between mb-2">
                    <span class="fw-bold">Taux de remplissage</span>
                    <span class="fw-bold">{{ number_format($percentage, 0) }}%</span>
                </div>
                <div class="progress h-10px">
                    <div class="progress-bar {{ $progressColor }}" style="width: {{ $percentage }}%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des inscrits -->
    <div class="col-xl-8">
        <div class="card mb-5">
            <div class="card-header py-4 d-flex justify-content-between align-items-center">
                <h3 class="card-title fw-bold mb-0">
                    <i class="ki-duotone ki-people text-itcj-blue me-2"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i><i class="path5"></i></i>
                    Inscrits ({{ $activeInscriptions->count() }})
                </h3>
            </div>
            <div class="card-body">
                @if($activeInscriptions->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
                            <thead>
                                <tr class="fw-bold text-muted">
                                    <th class="min-w-50px">#</th>
                                    <th class="min-w-200px">Membre</th>
                                    <th class="min-w-150px">Inscrit le</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($activeInscriptions as $index => $inscription)
                                    <tr>
                                        <td>
                                            <span class="fw-bold text-muted">{{ $index + 1 }}</span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="symbol symbol-40px me-3">
                                                    <span class="symbol-label bg-itcj-blue-light text-itcj-blue fw-bold">
                                                        {{ strtoupper(substr($inscription->user->name, 0, 1)) }}
                                                    </span>
                                                </div>
                                                <div>
                                                    <span class="fw-bold text-dark">{{ $inscription->user->title_prefix ?? '' }} {{ $inscription->user->name }}</span>
                                                    @if($inscription->user->community)
                                                        <span class="text-muted d-block fs-7">{{ $inscription->user->community }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-muted fs-7">{{ $inscription->registered_at->format('d/m/Y H:i') }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-10">
                        <i class="ki-duotone ki-people fs-3x text-muted mb-3"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i><i class="path5"></i></i>
                        <p class="text-muted">Aucune inscription pour ce repas</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Annulations -->
        @if($cancelledInscriptions->count() > 0)
            <div class="card">
                <div class="card-header py-4">
                    <h3 class="card-title fw-bold mb-0">
                        <i class="ki-duotone ki-cross-circle text-muted me-2"><i class="path1"></i><i class="path2"></i></i>
                        Annulations ({{ $cancelledInscriptions->count() }})
                    </h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
                            <thead>
                                <tr class="fw-bold text-muted">
                                    <th class="min-w-200px">Membre</th>
                                    <th class="min-w-150px">Inscrit le</th>
                                    <th class="min-w-150px">Annule le</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cancelledInscriptions as $inscription)
                                    <tr class="text-muted">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="symbol symbol-40px me-3">
                                                    <span class="symbol-label bg-light text-muted fw-bold">
                                                        {{ strtoupper(substr($inscription->user->name, 0, 1)) }}
                                                    </span>
                                                </div>
                                                <span class="text-muted">{{ $inscription->user->name }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-muted fs-7">{{ $inscription->registered_at->format('d/m/Y H:i') }}</span>
                                        </td>
                                        <td>
                                            <span class="text-danger fs-7">{{ $inscription->cancelled_at->format('d/m/Y H:i') }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
