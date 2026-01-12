@extends('layouts.app')

@section('title', 'Repas du ' . $meal->meal_date->format('d/m/Y'))
@section('page-title', 'Repas du Dimanche ' . $meal->meal_date->format('d/m/Y'))

@section('breadcrumb')
<li class="breadcrumb-item">
    <a href="{{ route('meals.index') }}" class="text-muted text-hover-primary">Repas</a>
</li>
<li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
<li class="breadcrumb-item text-muted">{{ $meal->meal_date->format('d/m/Y') }}</li>
@endsection

@push('styles')
<style>
    /* Mobile optimizations for meal show */
    @media (max-width: 576px) {
        /* Card headers */
        .card-header {
            padding: 1rem;
        }

        .card-header h2 {
            font-size: 1rem;
        }

        .card-body {
            padding: 1rem;
        }

        /* Progress bar */
        .progress.h-8px {
            height: 10px;
            border-radius: 5px;
        }

        /* Form inputs */
        .form-control {
            min-height: 48px;
            font-size: 16px;
        }

        /* Buttons */
        .d-flex.gap-2.mt-5 {
            flex-direction: column;
        }

        .d-flex.gap-2.mt-5 .btn {
            width: 100%;
            min-height: 48px;
        }

        .d-flex.gap-2.mt-5 form {
            width: 100%;
        }

        .d-flex.gap-2.mt-5 form .btn {
            width: 100%;
        }

        /* Admin table on mobile */
        .col-xl-8 .table-responsive {
            display: none;
        }

        .admin-inscriptions-mobile {
            display: block;
        }

        /* Info card stats */
        .d-flex.flex-stack {
            padding: 0.5rem 0;
        }
    }

    @media (min-width: 577px) {
        .admin-inscriptions-mobile {
            display: none;
        }
    }

    /* Mobile inscription cards */
    .inscription-mobile-card {
        background: var(--bg-card);
        border-radius: 12px;
        padding: 14px;
        margin-bottom: 10px;
        box-shadow: var(--neu-shadow-sm);
    }

    .inscription-mobile-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 10px;
    }

    .inscription-mobile-avatar {
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

    .inscription-mobile-info {
        flex: 1;
    }

    .inscription-mobile-name {
        font-size: 14px;
        font-weight: 600;
        color: var(--text-primary);
    }

    .inscription-mobile-community {
        font-size: 12px;
        color: var(--text-muted);
    }

    .inscription-mobile-stats {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .inscription-mobile-stat {
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 12px;
        color: var(--text-secondary);
    }

    .inscription-mobile-stat strong {
        color: var(--text-primary);
    }
</style>
@endpush

@section('content')
<div class="row g-5 g-xl-10">
    <!-- Stats et inscription -->
    <div class="col-xl-4">
        <section class="card mb-5" aria-labelledby="meal-info-heading">
            <div class="card-header py-4 d-flex justify-content-between align-items-center">
                <h2 id="meal-info-heading" class="card-title fw-bold mb-0 fs-4">
                    <i class="ki-duotone ki-dish fs-3 text-itcj-blue me-2" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
                    Informations
                </h2>
                <div>
                    @if($meal->is_open)
                        <span class="badge fs-8" style="background: #e8f5e9; color: #2e7d32;" role="status">
                            <i class="ki-duotone ki-check-circle fs-8 me-1" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
                            Inscriptions ouvertes
                        </span>
                    @else
                        <span class="badge badge-itcj-red fs-8" role="status">
                            <i class="ki-duotone ki-lock fs-8 me-1" aria-hidden="true"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                            Inscriptions fermées
                        </span>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <dl class="mb-0">
                    <div class="d-flex flex-stack mb-4">
                        <dt class="text-muted fw-normal">Places totales</dt>
                        <dd class="fw-bold mb-0" style="color: var(--itcj-dark);">{{ $meal->max_capacity }}</dd>
                    </div>
                    <div class="d-flex flex-stack mb-4">
                        <dt class="text-muted fw-normal">Inscrits</dt>
                        <dd class="fw-bold mb-0" style="color: var(--itcj-dark);">{{ $meal->totalPersons() }}</dd>
                    </div>
                    <div class="d-flex flex-stack mb-4">
                        <dt class="text-muted fw-normal">Places restantes</dt>
                        <dd class="fw-bold mb-0" style="color: {{ $meal->remainingSlots() < 10 ? 'var(--itcj-red)' : '#2e7d32' }};">
                            {{ $meal->remainingSlots() }}
                        </dd>
                    </div>
                </dl>

                @php
                    $percentage = $meal->max_capacity > 0 ? ($meal->totalPersons() / $meal->max_capacity) * 100 : 0;
                    $color = $percentage > 90 ? 'var(--itcj-red)' : ($percentage > 70 ? 'var(--itcj-gold)' : '#2e7d32');
                @endphp
                <div class="progress h-8px" style="background: #f0f0f0;"
                     role="progressbar"
                     aria-valuenow="{{ round($percentage) }}"
                     aria-valuemin="0"
                     aria-valuemax="100"
                     aria-label="Taux de remplissage: {{ round($percentage) }}%">
                    <div class="progress-bar" style="width: {{ $percentage }}%; background: {{ $color }};"></div>
                </div>

                @if($meal->notes)
                <div class="separator my-4"></div>
                <div class="p-3 rounded" style="background: var(--itcj-gold-light);" role="note">
                    <i class="ki-duotone ki-information fs-5 me-1" style="color: var(--itcj-gold);" aria-hidden="true"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                    <span class="text-muted fs-7">{{ $meal->notes }}</span>
                </div>
                @endif
            </div>
        </section>

        <!-- Inscription form -->
        @if($meal->is_open)
        <section class="card" aria-labelledby="inscription-heading">
            <div class="card-header py-4">
                <h2 id="inscription-heading" class="card-title fw-bold mb-0 fs-4">
                    {{ $userInscription ? 'Modifier mon inscription' : 'S\'inscrire au repas' }}
                </h2>
            </div>
            <div class="card-body">
                @if($userInscription)
                    <form action="{{ route('meals.inscription.update', $meal) }}" method="POST" id="inscription-form">
                        @csrf
                        @method('PUT')
                @else
                    <form action="{{ route('meals.register', $meal) }}" method="POST" id="inscription-form">
                        @csrf
                @endif
                        @php
                            $maxPlaces = $meal->getMaxPlacesAllowed();
                            $maxGuests = $meal->getMaxGuestsAllowed();
                        @endphp

                        @if($meal->allow_multiple_places)
                        <fieldset class="mb-5">
                            <legend class="form-label fw-semibold fs-6">Nombre de places</legend>
                            <input type="number"
                                   id="places_count"
                                   name="places_count"
                                   class="form-control @error('places_count') is-invalid @enderror"
                                   value="{{ old('places_count', $userInscription->places_count ?? 1) }}"
                                   min="1"
                                   max="{{ $maxPlaces }}"
                                   aria-describedby="places-help @error('places_count') places-error @enderror">
                            <div id="places-help" class="form-text text-muted">
                                Réservez de 1 à {{ $maxPlaces }} places pour vous et vos proches.
                            </div>
                            @error('places_count')
                            <div id="places-error" class="invalid-feedback" role="alert">{{ $message }}</div>
                            @enderror
                        </fieldset>
                        @else
                        <input type="hidden" name="places_count" value="1">
                        <div class="mb-4">
                            <div class="p-3 rounded" style="background: var(--itcj-blue-light);" role="note">
                                <i class="ki-duotone ki-check-circle fs-5 me-1 text-itcj-blue" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
                                <span class="text-muted fs-7">1 place réservée automatiquement pour vous.</span>
                            </div>
                        </div>
                        @endif

                        @if($meal->allow_guests)
                        <fieldset class="mb-4">
                            <legend class="form-label fw-semibold fs-6">
                                Invités supplémentaires
                                <span class="text-muted fw-normal fs-7">(optionnel)</span>
                            </legend>
                            <label for="guests_count" class="visually-hidden">Nombre d'invités</label>
                            <input type="number"
                                   id="guests_count"
                                   name="guests_count"
                                   class="form-control @error('guests_count') is-invalid @enderror"
                                   value="{{ old('guests_count', $userInscription->guests_count ?? 0) }}"
                                   min="0"
                                   max="{{ $maxGuests }}"
                                   aria-describedby="guests-help @error('guests_count') guests-error @enderror">
                            <div id="guests-help" class="form-text text-muted">
                                Jusqu'à {{ $maxGuests }} personnes supplémentaires (facultatif).
                            </div>
                            @error('guests_count')
                            <div id="guests-error" class="invalid-feedback" role="alert">{{ $message }}</div>
                            @enderror
                        </fieldset>

                        <div id="guest-names-container" style="{{ ($userInscription->guests_count ?? 0) > 0 ? '' : 'display:none' }}" class="mb-4">
                            <label class="form-label fw-semibold fs-6">
                                Noms des invités
                                <span class="text-muted fw-normal fs-7">(facultatif)</span>
                            </label>
                            @for($i = 0; $i < $maxGuests; $i++)
                            <div class="mb-2">
                                <label for="guest_name_{{ $i }}" class="visually-hidden">Nom de l'invité {{ $i + 1 }}</label>
                                <input type="text"
                                       id="guest_name_{{ $i }}"
                                       name="guest_names[]"
                                       class="form-control guest-name-input"
                                       placeholder="Nom de l'invité {{ $i + 1 }} (facultatif)"
                                       value="{{ $userInscription->guest_names[$i] ?? '' }}"
                                       style="{{ $i < ($userInscription->guests_count ?? 0) ? '' : 'display:none' }}"
                                       aria-describedby="guest-names-help">
                            </div>
                            @endfor
                            <div id="guest-names-help" class="form-text text-muted">
                                Les noms ne sont pas obligatoires.
                            </div>
                        </div>
                        @else
                        <input type="hidden" name="guests_count" value="0">
                        @endif

                        <div class="d-flex gap-2 mt-5">
                            <button type="submit" class="btn btn-itcj flex-grow-1"
                                    aria-label="{{ $userInscription ? 'Modifier mon inscription au repas' : 'Confirmer mon inscription au repas' }}">
                                <i class="ki-duotone ki-check fs-4 me-1" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
                                {{ $userInscription ? 'Modifier' : 'S\'inscrire' }}
                            </button>
                            @if($userInscription)
                            </form>
                            <form action="{{ route('meals.unregister', $meal) }}" method="POST" id="cancel-form"
                                  data-confirm="Êtes-vous sûr de vouloir annuler votre inscription ?"
                                  data-confirm-title="Annuler l'inscription"
                                  data-confirm-ok="Oui, annuler">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-itcj-red"
                                        aria-label="Annuler mon inscription au repas du {{ $meal->formatted_date }}"
                                        data-tooltip="Annuler l'inscription">
                                    <i class="ki-duotone ki-cross fs-4" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
                                    <span class="visually-hidden">Annuler</span>
                                </button>
                            </form>
                            @else
                            </form>
                            @endif
                        </div>
            </div>
        </section>
        @endif

        @if(auth()->user()->isAdmin())
        <section class="card mt-5" aria-label="Actions administrateur">
            <div class="card-body">
                <form action="{{ route('meals.toggle', $meal) }}" method="POST">
                    @csrf
                    @if($meal->is_open)
                    <button type="submit" class="btn btn-itcj-red w-100"
                            aria-label="Fermer les inscriptions pour ce repas"
                            data-tooltip="Les membres ne pourront plus s'inscrire">
                        <i class="ki-duotone ki-lock fs-4 me-1" aria-hidden="true"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                        Fermer les inscriptions
                    </button>
                    @else
                    <button type="submit" class="btn w-100" style="background: #e8f5e9; color: #2e7d32;"
                            aria-label="Ouvrir les inscriptions pour ce repas"
                            data-tooltip="Les membres pourront s'inscrire">
                        <i class="ki-duotone ki-lock-3 fs-4 me-1" aria-hidden="true"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                        Ouvrir les inscriptions
                    </button>
                    @endif
                </form>
            </div>
        </section>
        @endif
    </div>

    <!-- Liste des inscrits (admin uniquement) -->
    @if(auth()->user()->isAdmin() && $inscriptions)
    <div class="col-xl-8">
        <section class="card" aria-labelledby="inscriptions-list-heading">
            <div class="card-header py-4 d-flex justify-content-between align-items-center">
                <h2 id="inscriptions-list-heading" class="card-title fw-bold mb-0 fs-4">
                    <i class="ki-duotone ki-people fs-3 text-itcj-blue me-2" aria-hidden="true"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i><i class="path5"></i></i>
                    Liste des inscrits
                </h2>
                <span class="badge badge-itcj fs-8" role="status">{{ $inscriptions->count() }} inscriptions</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-row-dashed align-middle gs-0 gy-4" aria-describedby="inscriptions-list-heading">
                        <caption class="visually-hidden">Liste des personnes inscrites au repas du {{ $meal->formatted_date }}</caption>
                        <thead>
                            <tr>
                                <th scope="col">Membre</th>
                                <th scope="col">Places</th>
                                <th scope="col">Invités</th>
                                <th scope="col">Total</th>
                                <th scope="col">Inscrit le</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($inscriptions as $inscription)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="symbol symbol-35px me-3" aria-hidden="true">
                                            <span class="symbol-label bg-itcj-blue-light text-itcj-blue fw-bold fs-7">
                                                {{ strtoupper(substr($inscription->user->name, 0, 1)) }}
                                            </span>
                                        </div>
                                        <div>
                                            <span class="fw-bold" style="color: var(--itcj-dark);">{{ $inscription->user->name }}</span>
                                            @if($inscription->user->community)
                                                <span class="text-muted d-block fs-7">{{ $inscription->user->community }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-bold">{{ $inscription->places_count }}</span>
                                </td>
                                <td>
                                    <span class="fw-bold">{{ $inscription->guests_count }}</span>
                                    @if($inscription->guest_names && count($inscription->guest_names) > 0)
                                        <span class="text-muted d-block fs-8">
                                            {{ implode(', ', array_filter($inscription->guest_names)) }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-itcj fs-8">{{ $inscription->totalPersons() }}</span>
                                </td>
                                <td class="text-muted fs-7">
                                    <time datetime="{{ $inscription->registered_at->toIso8601String() }}">
                                        {{ $inscription->registered_at->format('d/m H:i') }}
                                    </time>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-10">
                                    <div class="empty-state">
                                        <i class="ki-duotone ki-people empty-state-icon" aria-hidden="true"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i><i class="path5"></i></i>
                                        <p class="empty-state-text">Aucune inscription pour le moment</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards for inscriptions -->
                <div class="admin-inscriptions-mobile">
                    @forelse($inscriptions as $inscription)
                        <div class="inscription-mobile-card">
                            <div class="inscription-mobile-header">
                                <div class="inscription-mobile-avatar bg-itcj-blue-light text-itcj-blue">
                                    {{ strtoupper(substr($inscription->user->name, 0, 1)) }}
                                </div>
                                <div class="inscription-mobile-info">
                                    <div class="inscription-mobile-name">{{ $inscription->user->name }}</div>
                                    @if($inscription->user->community)
                                        <div class="inscription-mobile-community">{{ $inscription->user->community }}</div>
                                    @endif
                                </div>
                                <span class="badge badge-itcj fs-8">{{ $inscription->totalPersons() }} pers.</span>
                            </div>
                            <div class="inscription-mobile-stats">
                                <div class="inscription-mobile-stat">
                                    <i class="ki-duotone ki-user fs-7"><i class="path1"></i><i class="path2"></i></i>
                                    <strong>{{ $inscription->places_count }}</strong> places
                                </div>
                                <div class="inscription-mobile-stat">
                                    <i class="ki-duotone ki-people fs-7"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i><i class="path5"></i></i>
                                    <strong>{{ $inscription->guests_count }}</strong> invités
                                </div>
                                <div class="inscription-mobile-stat">
                                    <i class="ki-duotone ki-time fs-7"><i class="path1"></i><i class="path2"></i></i>
                                    {{ $inscription->registered_at->format('d/m H:i') }}
                                </div>
                            </div>
                            @if($inscription->guest_names && count(array_filter($inscription->guest_names)) > 0)
                                <div class="mt-2 pt-2 border-top">
                                    <small class="text-muted">{{ implode(', ', array_filter($inscription->guest_names)) }}</small>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="mobile-empty-state">
                            <div class="mobile-empty-state-icon">
                                <i class="ki-duotone ki-people"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i><i class="path5"></i></i>
                            </div>
                            <div class="mobile-empty-state-title">Aucune inscription</div>
                            <div class="mobile-empty-state-text">Aucune inscription pour le moment</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const $guestsInput = $('input[name="guests_count"]');
    const $guestNamesContainer = $('#guest-names-container');
    const $guestNameInputs = $('.guest-name-input');

    function updateGuestFields() {
        const count = parseInt($guestsInput.val()) || 0;
        if (count > 0) {
            $guestNamesContainer.show();
            $guestNameInputs.each(function(index) {
                const $input = $(this);
                if (index < count) {
                    $input.show().attr('aria-hidden', 'false');
                } else {
                    $input.hide().val('').attr('aria-hidden', 'true');
                }
            });
        } else {
            $guestNamesContainer.hide();
            $guestNameInputs.val('').attr('aria-hidden', 'true');
        }
    }

    $guestsInput.on('change keyup', updateGuestFields);

    // Initial state
    updateGuestFields();
});
</script>
@endpush
