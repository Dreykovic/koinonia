@extends('layouts.app')

@section('title', 'Modifier Vehicule')
@section('page-title', 'Modifier le Vehicule')

@section('breadcrumb')
<li class="breadcrumb-item">
    <a href="{{ route('vehicles.index') }}" class="text-muted text-hover-primary">Vehicules</a>
</li>
<li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
<li class="breadcrumb-item text-muted">Modifier</li>
@endsection

@push('styles')
<style>
    /* Mobile form optimizations */
    @media (max-width: 576px) {
        .card-header h3 {
            font-size: 1.1rem;
        }

        .card-body .row.mb-5 {
            gap: 1rem;
        }

        .card-footer {
            flex-direction: column-reverse;
            gap: 0.75rem;
            padding: 1.25rem;
        }

        .card-footer .btn {
            width: 100%;
            min-height: 48px;
        }

        .card-footer .btn.me-3 {
            margin-right: 0 !important;
        }

        /* Better form switch on mobile */
        .form-check-custom {
            padding: 1rem;
            background: var(--bg-card);
            border-radius: 12px;
            margin: 0 -0.5rem;
        }

        .form-check-input {
            width: 48px;
            height: 26px;
        }
    }
</style>
@endpush

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Modifier {{ $vehicle->name }}</h3>
    </div>
    <form action="{{ route('vehicles.update', $vehicle) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="card-body">
            <div class="row mb-5">
                <div class="col-md-6">
                    <label class="form-label required">Nom du vehicule</label>
                    <input type="text" name="name" class="form-control form-control-solid @error('name') is-invalid @enderror"
                        value="{{ old('name', $vehicle->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            <div class="col-md-6">
                <label class="form-label required" for="vehicle_type_id">Type de véhicule</label>
                <select id="vehicle_type_id" name="vehicle_type_id" class="form-select form-select-solid @error('vehicle_type_id') is-invalid @enderror" required>
                    <option value="">-- Sélectionner un type --</option>
                    @foreach($vehicleTypes as $type)
                        <option value="{{ $type->id }}" {{ old('vehicle_type_id', $vehicle->vehicle_type_id ?? '') == $type->id ? 'selected' : '' }}>
                            {{ $type->name }}
                        </option>
                    @endforeach
                </select>
                @error('vehicle_type_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                @if(isset($vehicleTypes) && $vehicleTypes->isNotEmpty())
                    <div class="form-text" id="vehicle_type_help">
                        @foreach($vehicleTypes as $type)
                            @if($type->description)
                                <strong>{{ $type->name }}:</strong> {{ $type->description }}<br>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
            </div>

            <div class="row mb-5">
                <div class="col-md-6">
                    <label class="form-label">Immatriculation <span class="text-muted fw-normal">(optionnel)</span></label>
                    <input type="text" name="license_plate" class="form-control form-control-solid @error('license_plate') is-invalid @enderror"
                        value="{{ old('license_plate', $vehicle->license_plate) }}" placeholder="Ex: AB-123-CD">
                    @error('license_plate')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Capacite <span class="text-muted fw-normal">(optionnel)</span></label>
                    <input type="number" name="capacity" class="form-control form-control-solid @error('capacity') is-invalid @enderror"
                        value="{{ old('capacity', $vehicle->capacity) }}" min="1" max="50" placeholder="5">
                    @error('capacity')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-5">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control form-control-solid @error('description') is-invalid @enderror"
                    rows="3">{{ old('description', $vehicle->description) }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-5">
                <div class="form-check form-switch form-check-custom form-check-solid">
                    <input class="form-check-input" type="checkbox" name="is_available" value="1"
                        {{ old('is_available', $vehicle->is_available) ? 'checked' : '' }}>
                    <label class="form-check-label">Vehicule disponible</label>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end">
            <a href="{{ route('vehicles.index') }}" class="btn btn-light me-3">Annuler</a>
            <button type="submit" class="btn btn-primary">Enregistrer</button>
        </div>
    </form>
</div>
@endsection


