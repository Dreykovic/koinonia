@extends('layouts.app')

@section('title', 'Details Reservation')
@section('page-title', 'Details de la Reservation')

@section('breadcrumb')
<li class="breadcrumb-item">
    <a href="{{ route('reservations.index') }}" class="text-muted text-hover-primary">Reservations</a>
</li>
<li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
<li class="breadcrumb-item text-muted">Details</li>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <div class="row mb-7">
            <label class="col-lg-4 fw-semibold text-muted">Vehicule</label>
            <div class="col-lg-8">
                <span class="fw-bold fs-6 text-gray-800">{{ $reservation->vehicle->name }}</span>
                <span class="text-muted d-block">{{ $reservation->vehicle->license_plate }}</span>
            </div>
        </div>

        <div class="row mb-7">
            <label class="col-lg-4 fw-semibold text-muted">Date</label>
            <div class="col-lg-8">
                <span class="fw-bold fs-6 text-gray-800">{{ $reservation->reservation_date->format('d/m/Y') }}</span>
            </div>
        </div>

        <div class="row mb-7">
            <label class="col-lg-4 fw-semibold text-muted">Horaire</label>
            <div class="col-lg-8">
                <span class="fw-bold fs-6 text-gray-800">
                    {{ \Carbon\Carbon::parse($reservation->start_time)->format('H:i') }} -
                    {{ \Carbon\Carbon::parse($reservation->end_time)->format('H:i') }}
                </span>
            </div>
        </div>

        <div class="row mb-7">
            <label class="col-lg-4 fw-semibold text-muted">Destination</label>
            <div class="col-lg-8">
                <span class="fw-bold fs-6 text-gray-800">{{ $reservation->destination }}</span>
            </div>
        </div>

        <div class="row mb-7">
            <label class="col-lg-4 fw-semibold text-muted">Statut</label>
            <div class="col-lg-8">
                @php
                    $statusColors = [
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'cancelled' => 'secondary'
                    ];
                    $statusLabels = [
                        'pending' => 'En attente',
                        'approved' => 'Approuve',
                        'rejected' => 'Rejete',
                        'cancelled' => 'Annule'
                    ];
                @endphp
                <span class="badge badge-light-{{ $statusColors[$reservation->status] ?? 'secondary' }}">
                    {{ $statusLabels[$reservation->status] ?? $reservation->status }}
                </span>
            </div>
        </div>

        @if($reservation->notes)
        <div class="row mb-7">
            <label class="col-lg-4 fw-semibold text-muted">Notes</label>
            <div class="col-lg-8">
                <span class="fw-bold fs-6 text-gray-800">{{ $reservation->notes }}</span>
            </div>
        </div>
        @endif

        <div class="row mb-7">
            <label class="col-lg-4 fw-semibold text-muted">Reserve par</label>
            <div class="col-lg-8">
                <span class="fw-bold fs-6 text-gray-800">{{ $reservation->user->name }}</span>
            </div>
        </div>

        @if($reservation->approver)
        <div class="row mb-7">
            <label class="col-lg-4 fw-semibold text-muted">Approuve par</label>
            <div class="col-lg-8">
                <span class="fw-bold fs-6 text-gray-800">{{ $reservation->approver->name }}</span>
            </div>
        </div>
        @endif
    </div>
    <div class="card-footer d-flex justify-content-between">
        <div>
            @if($reservation->canBeCancelled() && ($reservation->user_id === auth()->id() || auth()->user()->isAdmin()))
            <form action="{{ route('reservations.destroy', $reservation) }}" method="POST" class="d-inline"
                  onsubmit="return confirm('Voulez-vous annuler cette réservation ?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    <i class="ki-duotone ki-cross fs-4"><i class="path1"></i><i class="path2"></i></i>
                    Annuler la réservation
                </button>
            </form>
            @endif
        </div>
        <a href="{{ route('reservations.index') }}" class="btn btn-light">Retour</a>
    </div>
</div>
@endsection
