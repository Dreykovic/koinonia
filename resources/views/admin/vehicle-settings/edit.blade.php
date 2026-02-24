@extends('layouts.app')

@section('title', 'Parametres Reservations')
@section('page-title', 'Parametres des Reservations')

@section('breadcrumb')
<li class="breadcrumb-item text-muted">Administration</li>
<li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
<li class="breadcrumb-item text-muted">Parametres vehicules</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header py-4">
        <div>
            <h3 class="card-title fw-bold mb-0">
                <i class="ki-duotone ki-setting-2 fs-3 text-primary me-2"><i class="path1"></i><i class="path2"></i></i>
                Parametres des Reservations de Vehicules
            </h3>
            <span class="text-muted fs-7">Configurez les horaires et limites de reservation</span>
        </div>
    </div>
    <form action="{{ route('admin.vehicle-settings.update') }}" method="POST">
        @csrf
        @method('PUT')
        <div class="card-body">
            @if(session('success'))
            <div class="alert alert-success d-flex align-items-center mb-5">
                <i class="ki-duotone ki-check-circle fs-2 me-3"><i class="path1"></i><i class="path2"></i></i>
                <div>{{ session('success') }}</div>
            </div>
            @endif

            <div class="row mb-5">
                <div class="col-md-6 mb-4 mb-md-0">
                    <label for="opening_hour" class="form-label required fw-semibold">
                        Heure d'ouverture
                    </label>
                    <input type="time"
                           class="form-control @error('opening_hour') is-invalid @enderror"
                           id="opening_hour"
                           name="opening_hour"
                           value="{{ old('opening_hour', $settings->opening_hour_formatted) }}"
                           required>
                    <div class="form-text">Heure a partir de laquelle les reservations peuvent commencer</div>
                    @error('opening_hour')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label for="closing_hour" class="form-label required fw-semibold">
                        Heure de fermeture
                    </label>
                    <input type="time"
                           class="form-control @error('closing_hour') is-invalid @enderror"
                           id="closing_hour"
                           name="closing_hour"
                           value="{{ old('closing_hour', $settings->closing_hour_formatted) }}"
                           required>
                    <div class="form-text">Heure jusqu'a laquelle les reservations peuvent se terminer</div>
                    @error('closing_hour')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mb-5">
                <div class="col-md-6 mb-4 mb-md-0">
                    <label for="max_future_days" class="form-label required fw-semibold">
                        Jours maximum a l'avance
                    </label>
                    <div class="input-group">
                        <input type="number"
                               class="form-control @error('max_future_days') is-invalid @enderror"
                               id="max_future_days"
                               name="max_future_days"
                               value="{{ old('max_future_days', $settings->max_future_days) }}"
                               min="1"
                               max="365"
                               required>
                        <span class="input-group-text">jours</span>
                    </div>
                    <div class="form-text">Nombre de jours maximum pour reserver a l'avance</div>
                    @error('max_future_days')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Date limite actuelle</label>
                    <div class="form-control-plaintext">
                        <span class="badge badge-itcj fs-7">
                            {{ \Carbon\Carbon::parse($settings->max_reservation_date)->format('d/m/Y') }}
                        </span>
                    </div>
                    <div class="form-text">Les membres peuvent reserver jusqu'a cette date</div>
                </div>
            </div>

            <!-- Preview Section -->
            <div class="alert alert-info d-flex align-items-center">
                <i class="ki-duotone ki-information-2 fs-2 me-3"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                <div>
                    <strong>Apercu des parametres :</strong><br>
                    Les reservations sont possibles de <strong>{{ $settings->opening_hour_formatted }}</strong>
                    a <strong>{{ $settings->closing_hour_formatted }}</strong>,
                    jusqu'a <strong>{{ $settings->max_future_days }} jours</strong> a l'avance.
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end gap-3">
            <a href="{{ route('vehicles.planning') }}" class="btn btn-light">Retour au planning</a>
            <button type="submit" class="btn btn-itcj">
                <i class="ki-duotone ki-check fs-4 me-1"><i class="path1"></i><i class="path2"></i></i>
                Enregistrer
            </button>
        </div>
    </form>
</div>
@endsection

