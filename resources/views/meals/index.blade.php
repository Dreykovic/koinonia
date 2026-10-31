@extends('layouts.app')

@section('title', 'Repas du Dimanche')
@section('page-title', 'Repas du Dimanche')

@section('breadcrumb')
<li class="breadcrumb-item text-muted">Repas</li>
@endsection

@section('page-actions')
@if(auth()->user()->isAdmin())
<a href="{{ route('meals.admin.index') }}"
   class="btn btn-itcj"
   data-tooltip="Accéder à la gestion des repas"
   aria-label="Gestion des repas - Créer et gérer les repas du dimanche">
    <i class="ki-duotone ki-setting-2 fs-4 me-1" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
    Gestion des repas
</a>
@endif
@endsection

@push('styles')
<style>
    /* Meal Widget - Neumorphism + Tons chauds */
    .meal-widget {
        background: linear-gradient(135deg, #2C2420 0%, #1a1512 100%);
        border-radius: 20px;
        padding: 2rem;
        color: #fff;
        position: relative;
        overflow: hidden;
        box-shadow: var(--neu-shadow);
    }

    .meal-widget::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--accent), var(--primary), var(--accent));
    }

    .meal-widget-icon {
        font-size: 4rem;
        position: absolute;
        right: 1.5rem;
        top: 50%;
        transform: translateY(-50%);
        opacity: 0.12;
        color: var(--accent);
    }

    .meal-date {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .meal-places {
        font-size: 3rem;
        font-weight: 800;
        color: var(--accent);
        line-height: 1;
    }

    .meal-places-label {
        font-size: 0.9rem;
        opacity: 0.8;
    }

    .meal-progress {
        height: 10px;
        background: rgba(255,255,255,0.15);
        border-radius: 6px;
        margin: 1rem 0;
        overflow: hidden;
        box-shadow: inset 2px 2px 4px rgba(0,0,0,0.2);
    }

    .meal-progress-bar {
        height: 100%;
        border-radius: 6px;
        transition: width 0.3s ease;
    }

    .meal-action-btn {
        padding: 0.85rem 2rem;
        font-size: 1rem;
        font-weight: 600;
        border-radius: 12px;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 4px 4px 10px rgba(0,0,0,0.2),
                    -2px -2px 6px rgba(255,255,255,0.05);
    }

    .meal-action-btn.reserve {
        background: linear-gradient(135deg, #5D8A66, #4A7553);
        border: none;
        color: #fff;
    }

    .meal-action-btn.reserve:hover {
        background: linear-gradient(135deg, #4A7553, #3D6345);
        transform: translateY(-2px);
        box-shadow: 6px 6px 14px rgba(0,0,0,0.25),
                    -3px -3px 8px rgba(255,255,255,0.08);
    }

    .meal-action-btn.cancel {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        border: none;
        color: #fff;
    }

    .meal-action-btn.cancel:hover {
        background: linear-gradient(135deg, var(--primary-dark), #5A1520);
    }

    .meal-action-btn.disabled {
        background: rgba(255,255,255,0.15);
        color: rgba(255,255,255,0.5);
        cursor: not-allowed;
        box-shadow: none;
    }

    .meal-status-badge {
        display: inline-block;
        padding: 0.4rem 0.85rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .meal-status-badge.registered {
        background: rgba(93, 138, 102, 0.25);
        color: #7AB785;
    }

    .meal-deadline {
        font-size: 0.85rem;
        opacity: 0.7;
        margin-top: 1rem;
    }

    /* No meal widget - Neumorphism */
    .no-meal-widget {
        background: var(--bg-card);
        border-radius: 20px;
        padding: 3rem 2rem;
        text-align: center;
        box-shadow: var(--neu-shadow);
    }

    /* Past reservations - Neumorphism */
    .past-item {
        padding: 1rem;
        border-bottom: 1px solid rgba(60, 48, 40, 0.08);
        transition: background 0.2s ease;
    }

    .past-item:hover {
        background: rgba(212, 168, 75, 0.05);
    }

    .past-item:last-child {
        border-bottom: none;
    }

    /* ==================== MOBILE OPTIMIZATIONS ==================== */
    @media (max-width: 576px) {
        /* Meal widget mobile */
        .meal-widget {
            padding: 1.25rem;
            border-radius: 16px;
        }

        .meal-widget-icon {
            font-size: 2.5rem;
            right: 1rem;
            opacity: 0.08;
        }

        .meal-date {
            font-size: 1.15rem;
        }

        .meal-places {
            font-size: 2rem;
        }

        .meal-places-label {
            font-size: 0.75rem;
        }

        .meal-progress {
            height: 8px;
            margin: 0.75rem 0;
        }

        .meal-action-btn {
            width: 100%;
            padding: 0.85rem 1.5rem;
            font-size: 0.95rem;
        }

        .meal-status-badge {
            font-size: 0.75rem;
            padding: 0.35rem 0.7rem;
        }

        .meal-deadline {
            font-size: 0.75rem;
            margin-top: 0.75rem;
        }

        /* No meal widget mobile */
        .no-meal-widget {
            padding: 2rem 1.25rem;
            border-radius: 16px;
        }

        .no-meal-widget .fs-4x {
            font-size: 2.5rem !important;
        }

        .no-meal-widget h2 {
            font-size: 1.1rem;
        }

        /* Past items mobile */
        .past-item {
            padding: 0.85rem;
        }

        .past-item .symbol-40px {
            width: 36px !important;
            height: 36px !important;
        }

        .past-item .symbol-label {
            width: 36px !important;
            height: 36px !important;
        }

        .past-item .fw-semibold {
            font-size: 0.9rem;
        }

        .past-item .fs-7 {
            font-size: 0.75rem;
        }

        .past-item .badge {
            font-size: 0.65rem;
            padding: 0.25rem 0.5rem;
        }

        /* Action column mobile */
        .col-md-5.text-center {
            margin-top: 1rem;
        }

        /* Stats flex gap mobile */
        .d-flex.align-items-end.gap-4 {
            flex-direction: column;
            align-items: flex-start !important;
            gap: 0.75rem !important;
        }

        /* Modal mobile */
        .modal-body.py-5 {
            padding: 1.5rem !important;
        }

        .modal-body .fs-4x {
            font-size: 2.5rem !important;
        }

        .modal-body h4 {
            font-size: 1.1rem;
        }

        .modal-body .text-muted {
            font-size: 0.9rem;
        }
    }

    /* Tablet adjustments */
    @media (min-width: 577px) and (max-width: 768px) {
        .meal-widget {
            padding: 1.5rem;
        }

        .meal-date {
            font-size: 1.25rem;
        }

        .meal-places {
            font-size: 2.5rem;
        }
    }
</style>
@endpush

@section('content')
<div class="row g-5">
    <!-- Widget principal -->
    <div class="col-lg-8">
        @if($upcomingMeal)
            <section class="meal-widget" role="region" aria-labelledby="upcoming-meal-heading">
                <i class="ki-duotone ki-gift meal-widget-icon" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>

                <div class="row align-items-center">
                    <div class="col-md-7">
                        <div class="mb-4">
                            <span class="text-uppercase fs-8 opacity-75" id="upcoming-meal-heading">Prochain repas</span>
                            <h2 class="meal-date">{{ $upcomingMeal->formatted_date }}</h2>
                        </div>

                        <div class="d-flex align-items-end gap-4 mb-3">
                            <div>
                                <div class="meal-places" aria-label="{{ $upcomingMeal->confirmed_count }} places sur {{ $upcomingMeal->max_capacity }} réservées">
                                    {{ $upcomingMeal->confirmed_count }}/{{ $upcomingMeal->max_capacity }}
                                </div>
                                <div class="meal-places-label">places réservées</div>
                            </div>
                            <div>
                                <span class="fs-2 fw-bold {{ $upcomingMeal->remaining_places < 3 ? 'text-warning' : '' }}"
                                      aria-label="{{ $upcomingMeal->remaining_places }} places restantes">
                                    {{ $upcomingMeal->remaining_places }}
                                </span>
                                <span class="opacity-75">restantes</span>
                            </div>
                        </div>

                        @php
                            $percentage = $upcomingMeal->max_capacity > 0 ? ($upcomingMeal->confirmed_count / $upcomingMeal->max_capacity) * 100 : 0;
                            $progressColor = $percentage > 90 ? '#ef4444' : ($percentage > 70 ? '#f59e0b' : '#10b981');
                        @endphp
                        <div class="meal-progress"
                             role="progressbar"
                             aria-valuenow="{{ round($percentage) }}"
                             aria-valuemin="0"
                             aria-valuemax="100"
                             aria-label="Taux de remplissage: {{ round($percentage) }}%">
                            <div class="meal-progress-bar" style="width: {{ $percentage }}%; background: {{ $progressColor }};"></div>
                        </div>

                        @if($userInscription)
                            <span class="meal-status-badge registered" role="status">
                                <i class="ki-duotone ki-check-circle fs-6 me-1" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
                                Vous êtes inscrit
                            </span>
                        @endif

                        <div class="meal-deadline">
                            <i class="ki-duotone ki-time fs-7 me-1" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
                            @if($upcomingMeal->registration_deadline)
                                Inscription jusqu'au {{ $upcomingMeal->registration_deadline->locale('fr')->isoFormat('dddd D MMMM [à] HH[h]mm') }}
                            @else
                                Inscription ouverte jusqu'au repas
                            @endif
                        </div>
                    </div>

                    <div class="col-md-5 text-center text-md-end mt-4 mt-md-0">
                        @if($userInscription)
                            <!-- Déjà inscrit - Bouton annuler -->
                            @if($upcomingMeal->canCancel())
                                <form action="{{ route('meals.unregister', $upcomingMeal) }}" method="POST" id="cancelForm">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button"
                                            class="meal-action-btn cancel"
                                            onclick="confirmCancel()"
                                            aria-label="Annuler mon inscription au repas du {{ $upcomingMeal->formatted_date }}">
                                        <i class="ki-duotone ki-cross fs-4 me-1" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
                                        Annuler
                                    </button>
                                </form>
                            @else
                                <button type="button"
                                        class="meal-action-btn disabled"
                                        disabled
                                        aria-disabled="true"
                                        aria-label="Annulation plus possible">
                                    <i class="ki-duotone ki-lock fs-4 me-1" aria-hidden="true"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                                    Annulation fermée
                                </button>
                            @endif
                        @else
                            <!-- Pas inscrit -->
                            @if($upcomingMeal->canRegister())
                                <a href="{{ route('meals.show', $upcomingMeal) }}"
                                   class="meal-action-btn reserve"
                                   aria-label="S'inscrire au repas du {{ $upcomingMeal->formatted_date }}">
                                    <i class="ki-duotone ki-check fs-4 me-1" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
                                    S'inscrire
                                </a>
                            @elseif($upcomingMeal->isFull())
                                <button type="button"
                                        class="meal-action-btn disabled"
                                        disabled
                                        aria-disabled="true"
                                        aria-label="Ce repas est complet">
                                    <i class="ki-duotone ki-information fs-4 me-1" aria-hidden="true"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                                    Complet
                                </button>
                            @else
                                <button type="button"
                                        class="meal-action-btn disabled"
                                        disabled
                                        aria-disabled="true"
                                        aria-label="Les inscriptions sont fermées">
                                    <i class="ki-duotone ki-lock fs-4 me-1" aria-hidden="true"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                                    Fermé
                                </button>
                            @endif
                        @endif
                    </div>
                </div>

                @if($upcomingMeal->notes)
                    <div class="mt-4 p-3 rounded" style="background: rgba(255,255,255,0.1);" role="note">
                        <i class="ki-duotone ki-information fs-5 me-1 text-warning" aria-hidden="true"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                        <span class="fs-7">{{ $upcomingMeal->notes }}</span>
                    </div>
                @endif
            </section>
        @else
            <section class="no-meal-widget" aria-label="Information sur les repas">
                <i class="ki-duotone ki-gift fs-4x text-muted mb-4" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
                <h2 class="text-muted mb-2 fs-4">Aucun repas programmé</h2>
                <p class="text-muted">Il n'y a pas de repas du dimanche programmé pour le moment.</p>
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('meals.admin.create') }}"
                       class="btn btn-itcj mt-3"
                       aria-label="Créer un nouveau repas du dimanche">
                        <i class="ki-duotone ki-plus me-1" aria-hidden="true"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                        Créer un repas
                    </a>
                @endif
            </section>
        @endif
    </div>

    <!-- Historique des réservations -->
    <div class="col-lg-4">
        <section class="card" aria-labelledby="past-reservations-heading">
            <div class="card-header py-4">
                <h3 id="past-reservations-heading" class="card-title fw-bold mb-0">
                    <i class="ki-duotone ki-time text-muted me-2" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
                    Mes réservations
                </h3>
            </div>
            <div class="card-body p-0">
                <ul class="list-unstyled mb-0" role="list">
                    @forelse($pastInscriptions as $inscription)
                        <li class="past-item d-flex align-items-center">
                            <div class="symbol symbol-40px me-3" aria-hidden="true">
                                <span class="symbol-label {{ $inscription->isActive() ? 'bg-light-success text-success' : 'bg-light-danger text-danger' }}">
                                    <i class="ki-duotone ki-{{ $inscription->isActive() ? 'check' : 'cross' }} fs-4"><i class="path1"></i><i class="path2"></i></i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <span class="fw-semibold text-dark d-block">{{ $inscription->sundayMeal->short_date }}</span>
                                <span class="text-muted fs-7">{{ $inscription->sundayMeal->meal_date->locale('fr')->isoFormat('dddd') }}</span>
                            </div>
                            <span class="badge {{ $inscription->isActive() ? 'badge-light-success' : 'badge-light-danger' }} fs-8 d-inline-flex align-items-center gap-1"
                                  role="status">
                                @if($inscription->isActive())
                                    <i class="ki-duotone ki-check-circle fs-8" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
                                @else
                                    <i class="ki-duotone ki-cross-circle fs-8" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
                                @endif
                                {{ $inscription->status_label }}
                            </span>
                        </li>
                    @empty
                        <li class="text-center text-muted py-5">
                            <i class="ki-duotone ki-calendar fs-3x text-gray-300 mb-2" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
                            <p class="mb-0">Aucune réservation passée</p>
                        </li>
                    @endforelse
                </ul>
            </div>
        </section>
    </div>
</div>

<!-- Modal Confirmation Reservation -->
<div class="modal fade" id="confirmReserveModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-5">
                <i class="ki-duotone ki-gift fs-4x text-success mb-4"><i class="path1"></i><i class="path2"></i></i>
                <h4 class="mb-3">Confirmer votre reservation ?</h4>
                <p class="text-muted mb-5">Vous confirmez votre presence au repas de dimanche {{ $upcomingMeal ? $upcomingMeal->meal_date->format('d/m/Y') : '' }}.</p>
                <div class="d-flex justify-content-center gap-3">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-success px-4" onclick="document.getElementById('reserveForm').submit()">
                        <i class="ki-duotone ki-check me-1"><i class="path1"></i><i class="path2"></i></i>
                        Confirmer
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Confirmation Annulation -->
<div class="modal fade" id="confirmCancelModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-5">
                <i class="ki-duotone ki-information-4 fs-4x text-warning mb-4"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                <h4 class="mb-3">Annuler votre reservation ?</h4>
                <p class="text-muted mb-5">Votre place sera liberee et disponible pour un autre membre.</p>
                <div class="d-flex justify-content-center gap-3">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Non, garder</button>
                    <button type="button" class="btn btn-danger px-4" onclick="document.getElementById('cancelForm').submit()">
                        <i class="ki-duotone ki-cross me-1"><i class="path1"></i><i class="path2"></i></i>
                        Oui, annuler
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function confirmReserve() {
    new bootstrap.Modal(document.getElementById('confirmReserveModal')).show();
}

function confirmCancel() {
    new bootstrap.Modal(document.getElementById('confirmCancelModal')).show();
}

// Real-time updates for meal registrations
@if($upcomingMeal)
// app.js est un module (différé) : Echo n'existe qu'une fois le DOM analysé.
document.addEventListener('DOMContentLoaded', () => {
    if (typeof Echo === 'undefined') return;
    Echo.channel('meal.{{ $upcomingMeal->id }}')
        .listen('MealInscriptionChanged', (e) => {
            console.log('MealInscriptionChanged:', e);
            // Reload page to show updated data
            location.reload();
        });
});
@endif
</script>
@endpush


