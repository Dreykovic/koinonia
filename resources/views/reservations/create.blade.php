@extends('layouts.app')

@section('title', 'Nouvelle Reservation')
@section('page-title', 'Nouvelle Reservation')

@section('breadcrumb')
<li class="breadcrumb-item">
    <a href="{{ route('reservations.index') }}" class="text-muted text-hover-primary">Reservations</a>
</li>
<li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
<li class="breadcrumb-item text-muted">Nouvelle</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Reserver un vehicule</h3>
    </div>
    <form action="{{ route('reservations.store') }}" method="POST">
        @csrf
        <div class="card-body">
            <div class="row mb-5">
                <div class="col-md-6">
                    <label class="form-label required">Vehicule</label>
                    <select name="vehicle_id" class="form-select form-select-solid @error('vehicle_id') is-invalid @enderror" required>
                        <option value="">Selectionnez un vehicule</option>
                        @foreach($vehicles as $vehicle)
                            <option value="{{ $vehicle->id }}" {{ old('vehicle_id') == $vehicle->id ? 'selected' : '' }}>
                                {{ $vehicle->name }} ({{ $vehicle->license_plate }}) - {{ ucfirst($vehicle->type->name) }}
                            </option>
                        @endforeach
                    </select>
                    @error('vehicle_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label required">Date</label>
                    <input type="date" name="reservation_date" class="form-control form-control-solid @error('reservation_date') is-invalid @enderror"
                        value="{{ old('reservation_date', now()->toDateString()) }}" min="{{ now()->toDateString() }}" required>
                    @error('reservation_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mb-5">
                <div class="col-md-6">
                    <label class="form-label required">Heure de debut</label>
                    <input type="time" name="start_time" class="form-control form-control-solid @error('start_time') is-invalid @enderror"
                        value="{{ old('start_time') }}" required>
                    @error('start_time')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label required">Heure de fin</label>
                    <input type="time" name="end_time" class="form-control form-control-solid @error('end_time') is-invalid @enderror"
                        value="{{ old('end_time') }}" required>
                    @error('end_time')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-5">
                <label class="form-label required">Destination</label>
                <input type="text" name="destination" class="form-control form-control-solid @error('destination') is-invalid @enderror"
                    value="{{ old('destination') }}" placeholder="Ex: Aeroport, Centre-ville..." required>
                @error('destination')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-5">
                <label class="form-label">Notes (optionnel)</label>
                <textarea name="notes" class="form-control form-control-solid @error('notes') is-invalid @enderror"
                    rows="3" placeholder="Informations supplementaires...">{{ old('notes') }}</textarea>
                @error('notes')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end">
            <a href="{{ route('reservations.index') }}" class="btn btn-light me-3">Annuler</a>
            <button type="submit" id="btn-submit" class="btn btn-primary">
                <span class="indicator-label">Reserver</span>
                <span class="indicator-progress" style="display: none;">
                    Reservation...
                    <span class="spinner-border spinner-border-sm ms-2"></span>
                </span>
            </button>
        </div>
    </form>
</div>

<!-- Mobile back button -->
<a href="{{ route('reservations.index') }}" class="mobile-back-btn d-md-none" aria-label="Retour aux reservations">
    <i class="ki-duotone ki-arrow-left"><i class="path1"></i><i class="path2"></i></i>
</a>

<script>
document.querySelector('form').addEventListener('submit', function() {
    var btn = document.getElementById('btn-submit');
    btn.disabled = true;
    btn.querySelector('.indicator-label').style.display = 'none';
    btn.querySelector('.indicator-progress').style.display = 'inline-block';
});
</script>
@endsection



