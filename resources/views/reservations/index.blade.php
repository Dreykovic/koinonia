@extends('layouts.app')

@section('title', 'Mes Reservations')
@section('page-title', 'Mes Reservations')

@section('breadcrumb')
<li class="breadcrumb-item text-muted">Reservations</li>
@endsection

@section('page-actions')
<a href="{{ route('vehicles.planning') }}" class="btn btn-sm btn-itcj desktop-add-btn">
    <i class="ki-duotone ki-map fs-2"></i> Planning des vehicules
</a>
@endsection

@push('styles')
<style>
    /* Mobile card for reservations */
    @media (max-width: 768px) {
        .reservation-mobile-card {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            box-shadow: var(--neu-shadow-sm);
        }

        .reservation-mobile-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .reservation-mobile-date {
            font-size: 15px;
            font-weight: 700;
            color: var(--text-primary);
        }

        .reservation-mobile-vehicle {
            font-size: 13px;
            color: var(--text-muted);
        }

        .reservation-mobile-body {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .reservation-mobile-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
        }

        .reservation-mobile-label {
            color: var(--text-muted);
        }

        .reservation-mobile-value {
            color: var(--text-primary);
            font-weight: 500;
        }

        .reservation-mobile-actions {
            display: flex;
            gap: 8px;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid rgba(60, 48, 40, 0.08);
        }

        .reservation-mobile-actions .btn {
            flex: 1;
            padding: 10px;
            font-size: 13px;
        }
    }
</style>
@endpush

@section('content')
<div class="card">
    <div class="card-header py-4">
        <h3 class="card-title fw-bold mb-0">Liste des reservations</h3>
        <span class="text-muted fs-7">Historique de vos reservations de vehicules</span>
    </div>
    <div class="card-body pt-0">
        <!-- Desktop Table -->
        <div class="table-responsive desktop-table">
            <table class="table table-row-dashed align-middle gs-0 gy-4">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Vehicule</th>
                        <th>Horaire</th>
                        <th>Destination</th>
                        <th>Statut</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reservations as $reservation)
                    @php
                        $statusColors = [
                            'pending' => ['bg' => 'var(--itcj-gold-light)', 'text' => '#9a7a30'],
                            'approved' => ['bg' => '#e8f5e9', 'text' => '#2e7d32'],
                            'rejected' => ['bg' => 'var(--itcj-red-light)', 'text' => 'var(--itcj-red)'],
                            'cancelled' => ['bg' => '#f5f5f5', 'text' => '#616161'],
                        ];
                        $statusLabels = [
                            'pending' => 'En attente',
                            'approved' => 'Approuve',
                            'rejected' => 'Rejete',
                            'cancelled' => 'Annule'
                        ];
                        $status = $statusColors[$reservation->status] ?? $statusColors['pending'];
                    @endphp
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="symbol symbol-40px me-3">
                                    <span class="symbol-label bg-itcj-blue-light">
                                        <i class="ki-duotone ki-calendar fs-3 text-itcj-blue"><i class="path1"></i><i class="path2"></i></i>
                                    </span>
                                </div>
                                <span class="fw-bold" style="color: var(--itcj-dark);">
                                    {{ $reservation->reservation_date->format('d/m/Y') }}
                                </span>
                            </div>
                        </td>
                        <td>
                            <span class="fw-bold" style="color: var(--itcj-dark);">{{ $reservation->vehicle->name }}</span>
                            <span class="text-muted d-block fs-7">{{ $reservation->vehicle->license_plate }}</span>
                        </td>
                        <td>
                            <span class="badge badge-itcj fs-8">
                                {{ \Carbon\Carbon::parse($reservation->start_time)->format('H:i') }} -
                                {{ \Carbon\Carbon::parse($reservation->end_time)->format('H:i') }}
                            </span>
                        </td>
                        <td class="text-muted">{{ $reservation->destination ?? '-' }}</td>
                        <td>
                            <span class="badge fs-8" style="background: {{ $status['bg'] }}; color: {{ $status['text'] }};">
                                {{ $statusLabels[$reservation->status] ?? $reservation->status }}
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('reservations.show', $reservation) }}" class="btn btn-icon btn-sm btn-itcj-outline">
                                    <i class="ki-duotone ki-eye fs-4"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                                </a>
                                @if($reservation->status === 'pending' && $reservation->canBeCancelled())
                                <form action="{{ route('reservations.destroy', $reservation) }}" method="POST"
                                      data-confirm="Voulez-vous annuler cette réservation ?"
                                      data-confirm-title="Annuler la réservation"
                                      data-confirm-ok="Oui, annuler">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-icon btn-sm btn-itcj-red">
                                        <i class="ki-duotone ki-cross fs-4"><i class="path1"></i><i class="path2"></i></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-10">
                            <div class="empty-state">
                                <i class="ki-duotone ki-car-2 empty-state-icon"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i><i class="path5"></i></i>
                                <p class="empty-state-text">Aucune reservation</p>
                                <a href="{{ route('vehicles.planning') }}" class="btn btn-itcj btn-sm mt-3">
                                    Reserver un vehicule
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile Cards -->
        <div class="mobile-card-list">
            @forelse($reservations as $reservation)
                @php
                    $statusColors = [
                        'pending' => ['bg' => 'var(--itcj-gold-light)', 'text' => '#9a7a30'],
                        'approved' => ['bg' => '#e8f5e9', 'text' => '#2e7d32'],
                        'rejected' => ['bg' => 'var(--itcj-red-light)', 'text' => 'var(--itcj-red)'],
                        'cancelled' => ['bg' => '#f5f5f5', 'text' => '#616161'],
                    ];
                    $statusLabels = [
                        'pending' => 'En attente',
                        'approved' => 'Approuve',
                        'rejected' => 'Rejete',
                        'cancelled' => 'Annule'
                    ];
                    $status = $statusColors[$reservation->status] ?? $statusColors['pending'];
                @endphp
                <div class="reservation-mobile-card">
                    <div class="reservation-mobile-header">
                        <div>
                            <div class="reservation-mobile-date">{{ $reservation->reservation_date->format('d/m/Y') }}</div>
                            <div class="reservation-mobile-vehicle">{{ $reservation->vehicle->name }} - {{ $reservation->vehicle->license_plate }}</div>
                        </div>
                        <span class="badge fs-8" style="background: {{ $status['bg'] }}; color: {{ $status['text'] }};">
                            {{ $statusLabels[$reservation->status] ?? $reservation->status }}
                        </span>
                    </div>
                    <div class="reservation-mobile-body">
                        <div class="reservation-mobile-row">
                            <span class="reservation-mobile-label">Horaire</span>
                            <span class="reservation-mobile-value">
                                {{ \Carbon\Carbon::parse($reservation->start_time)->format('H:i') }} -
                                {{ \Carbon\Carbon::parse($reservation->end_time)->format('H:i') }}
                            </span>
                        </div>
                        @if($reservation->destination)
                        <div class="reservation-mobile-row">
                            <span class="reservation-mobile-label">Destination</span>
                            <span class="reservation-mobile-value">{{ $reservation->destination }}</span>
                        </div>
                        @endif
                    </div>
                    <div class="reservation-mobile-actions">
                        <a href="{{ route('reservations.show', $reservation) }}" class="btn btn-sm btn-light">
                            <i class="ki-duotone ki-eye fs-4"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                            Détails
                        </a>
                        @if($reservation->status === 'pending' && $reservation->canBeCancelled())
                        <form action="{{ route('reservations.destroy', $reservation) }}" method="POST" class="flex-grow-1"
                              data-confirm="Voulez-vous annuler cette réservation ?"
                              data-confirm-title="Annuler la réservation"
                              data-confirm-ok="Oui, annuler">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-light-danger w-100">
                                <i class="ki-duotone ki-cross fs-4"><i class="path1"></i><i class="path2"></i></i>
                                Annuler
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="mobile-empty-state">
                    <div class="mobile-empty-state-icon">
                        <i class="ki-duotone ki-car-2"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i><i class="path5"></i></i>
                    </div>
                    <div class="mobile-empty-state-title">Aucune réservation</div>
                    <div class="mobile-empty-state-text">Vous n'avez pas encore de réservation de véhicule.</div>
                    <a href="{{ route('vehicles.planning') }}" class="btn btn-itcj">
                        Réserver un véhicule
                    </a>
                </div>
            @endforelse
        </div>

        @if($reservations->hasPages())
        {{ $reservations->links() }}
        @endif
    </div>
</div>

<!-- FAB for mobile -->
<a href="{{ route('vehicles.planning') }}" class="fab" aria-label="Réserver un véhicule">
    <i class="ki-duotone ki-plus"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
</a>
@endsection
