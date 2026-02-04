@extends('layouts.app')

@section('title', 'Nouveau Repas')
@section('page-title', 'Creer un Repas du Dimanche')

@section('breadcrumb')
<li class="breadcrumb-item">
    <a href="{{ route('meals.admin.index') }}" class="text-muted text-hover-primary">Repas</a>
</li>
<li class="breadcrumb-item text-muted">Nouveau</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header py-4">
                <h3 class="card-title fw-bold mb-0">
                    <i class="ki-duotone ki-gift text-itcj-blue me-2"><i class="path1"></i><i class="path2"></i></i>
                    Nouveau Repas
                </h3>
            </div>
            <div class="card-body">
                <form action="{{ route('meals.admin.store') }}" method="POST">
                    @csrf

                    <div class="row mb-5">
                        <div class="col-md-6">
                            <label class="form-label required fw-semibold">Date du repas</label>
                            <input type="date" name="meal_date" class="form-control @error('meal_date') is-invalid @enderror"
                                value="{{ old('meal_date', $nextSunday->format('Y-m-d')) }}" required>
                            @error('meal_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text text-muted">Le prochain dimanche disponible est suggere</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required fw-semibold">Nombre de places</label>
                            <input type="number" name="max_capacity" class="form-control @error('max_capacity') is-invalid @enderror"
                                value="{{ old('max_capacity', 10) }}" min="1" max="100" required>
                            @error('max_capacity')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-5">
                        <div class="col-md-6">
                            <label class="form-label required fw-semibold">Heure limite d'inscription</label>
                            <input type="datetime-local" name="registration_deadline" class="form-control @error('registration_deadline') is-invalid @enderror"
                                value="{{ old('registration_deadline', $suggestedDeadline->format('Y-m-d\TH:i')) }}" required>
                            @error('registration_deadline')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text text-muted">Par defaut: Samedi 18h00</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required fw-semibold">Heure limite d'annulation</label>
                            <input type="datetime-local" name="cancellation_deadline" class="form-control @error('cancellation_deadline') is-invalid @enderror"
                                value="{{ old('cancellation_deadline', $suggestedDeadline->format('Y-m-d\TH:i')) }}" required>
                            @error('cancellation_deadline')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text text-muted">Par defaut: Samedi 18h00</div>
                        </div>
                    </div>

                    <div class="mb-5">
                        <label class="form-label fw-semibold">Notes (optionnel)</label>
                        <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3" placeholder="Informations supplementaires...">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-5">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_open" id="is_open" value="1" {{ old('is_open', true) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="is_open">Ouvrir les inscriptions immediatement</label>
                        </div>
                    </div>

                    <div class="separator my-5"></div>

                    <!-- Options de reservation -->
                    <h4 class="mb-4 fw-bold text-gray-700">Options de reservation</h4>

                    <div class="row mb-5">
                        <div class="col-md-6">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="allow_multiple_places" id="allow_multiple_places" value="1"
                                    {{ old('allow_multiple_places', false) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="allow_multiple_places">Autoriser plusieurs places</label>
                            </div>
                            <div id="max_places_wrapper">
                                <label class="form-label fw-semibold">Max places par personne</label>
                                <input type="number" name="max_places_per_user" class="form-control @error('max_places_per_user') is-invalid @enderror"
                                    value="{{ old('max_places_per_user', 1) }}" min="1" max="20">
                                @error('max_places_per_user')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="allow_guests" id="allow_guests" value="1"
                                    {{ old('allow_guests', true) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="allow_guests">Autoriser les invites</label>
                            </div>
                            <div id="max_guests_wrapper">
                                <label class="form-label fw-semibold">Max invites par personne</label>
                                <input type="number" name="max_guests_per_user" class="form-control @error('max_guests_per_user') is-invalid @enderror"
                                    value="{{ old('max_guests_per_user', 5) }}" min="0" max="20">
                                @error('max_guests_per_user')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="notice d-flex bg-light-info rounded border-info border border-dashed p-4 mb-5">
                        <i class="ki-duotone ki-information fs-2tx text-info me-4"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                        <div class="d-flex flex-stack flex-grow-1">
                            <div class="fw-semibold">
                                <div class="fs-7 text-gray-700">
                                    Si les deux options sont desactivees, chaque personne ne pourra reserver qu'une seule place.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="separator my-5"></div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('meals.admin.index') }}" class="btn btn-light">
                            <i class="ki-duotone ki-arrow-left me-1"><i class="path1"></i><i class="path2"></i></i>
                            Annuler
                        </a>
                        <button type="submit" class="btn btn-itcj">
                            <i class="ki-duotone ki-check me-1"><i class="path1"></i><i class="path2"></i></i>
                            Creer le repas
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    function toggleMaxPlaces() {
        if ($('#allow_multiple_places').is(':checked')) {
            $('#max_places_wrapper').show();
        } else {
            $('#max_places_wrapper').hide();
        }
    }

    function toggleMaxGuests() {
        if ($('#allow_guests').is(':checked')) {
            $('#max_guests_wrapper').show();
        } else {
            $('#max_guests_wrapper').hide();
        }
    }

    $('#allow_multiple_places').on('change', toggleMaxPlaces);
    $('#allow_guests').on('change', toggleMaxGuests);

    toggleMaxPlaces();
    toggleMaxGuests();
});
</script>
@endpush

