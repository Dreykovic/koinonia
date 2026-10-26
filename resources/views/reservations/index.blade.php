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
    /* Past/archived reservations */
    .reservation-past {
        opacity: 0.6;
    }
    .reservation-past td {
        background-color: #f8f9fa !important;
    }
    .reservation-mobile-card.reservation-past {
        background: #f8f9fa;
        opacity: 0.7;
    }
    .badge-archived {
        background: #e0e0e0 !important;
        color: #757575 !important;
    }

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
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center w-100 gap-3">
            <div>
                <h3 class="card-title fw-bold mb-0">Liste des reservations</h3>
                <span class="text-muted fs-7">
                    @if(auth()->user()->isAdmin())
                        Gestion des reservations de vehicules
                    @else
                        Historique de vos reservations de vehicules
                    @endif
                </span>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <!-- Search -->
                <div class="position-relative">
                    <i class="ki-duotone ki-magnifier fs-3 position-absolute" style="left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"><i class="path1"></i><i class="path2"></i></i>
                    <input type="text" id="search-input" class="form-control form-control-sm ps-10" placeholder="Vehicule, destination..." value="{{ request('search') }}" style="min-width: 180px;" onkeydown="if(event.key==='Enter'){updateFilters();}">
                </div>
                <!-- Date filter -->
                <select class="form-select form-select-sm" style="min-width: 140px;" onchange="updateFilters();" id="date-filter">
                    <option value="upcoming" {{ ($dateFilter ?? 'upcoming') === 'upcoming' ? 'selected' : '' }}>A venir</option>
                    <option value="today" {{ ($dateFilter ?? 'upcoming') === 'today' ? 'selected' : '' }}>Aujourd'hui</option>
                    <option value="tomorrow" {{ ($dateFilter ?? 'upcoming') === 'tomorrow' ? 'selected' : '' }}>Demain</option>
                    <option value="week" {{ ($dateFilter ?? 'upcoming') === 'week' ? 'selected' : '' }}>Cette semaine</option>
                    <option value="past" {{ ($dateFilter ?? 'upcoming') === 'past' ? 'selected' : '' }}>Archives</option>
                    <option value="all" {{ ($dateFilter ?? 'upcoming') === 'all' ? 'selected' : '' }}>Tout</option>
                </select>
                @if(auth()->user()->isAdmin())
                <!-- User filter (admin only) -->
                <select id="filter-reservations" class="form-select form-select-sm" style="min-width: 160px;" onchange="updateFilters();">
                    <option value="all" {{ ($filter ?? 'all') === 'all' ? 'selected' : '' }}>Tous les membres</option>
                    <option value="mine" {{ ($filter ?? 'all') === 'mine' ? 'selected' : '' }}>Mes reservations</option>
                    <option value="others" {{ ($filter ?? 'all') === 'others' ? 'selected' : '' }}>Autres membres</option>
                </select>
                @endif
            </div>
        </div>
    </div>
    <div class="card-body pt-0">
        <!-- Desktop Table -->
        <div class="table-responsive desktop-table">
            <table class="table table-row-dashed align-middle gs-0 gy-4">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Vehicule</th>
                        @if(auth()->user()->isAdmin())
                        <th>Reserve par</th>
                        @endif
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
                        $isPast = $reservation->reservation_date->lt(now()->startOfDay());
                    @endphp
                    <tr class="{{ $isPast ? 'reservation-past' : '' }}">
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
                        @if(auth()->user()->isAdmin())
                        <td>
                            <span class="fw-semibold" style="color: var(--itcj-dark);">{{ $reservation->user->name ?? '-' }}</span>
                            @if($reservation->user_id === auth()->id())
                            <span class="badge badge-light-primary fs-9 ms-1">Moi</span>
                            @endif
                        </td>
                        @endif
                        <td>
                            <span class="badge badge-itcj fs-8">
                                {{ \Carbon\Carbon::parse($reservation->start_time)->format('H:i') }} -
                                {{ \Carbon\Carbon::parse($reservation->end_time)->format('H:i') }}
                            </span>
                        </td>
                        <td class="text-muted">{{ $reservation->destination ?? '-' }}</td>
                        <td>
                            @if($isPast)
                                <span class="badge badge-archived fs-8">
                                    <i class="ki-duotone ki-archive fs-7 me-1"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                                    Archive
                                </span>
                            @else
                                <span class="badge fs-8" style="background: {{ $status['bg'] }}; color: {{ $status['text'] }};">
                                    {{ $statusLabels[$reservation->status] ?? $reservation->status }}
                                </span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('reservations.show', $reservation) }}" class="btn btn-icon btn-sm btn-itcj-outline">
                                    <i class="ki-duotone ki-eye fs-4"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                                </a>
                                @if($reservation->canBeCancelled() && ($reservation->user_id === auth()->id() || auth()->user()->isAdmin()))
                                <form action="{{ route('reservations.destroy', $reservation) }}" method="POST" class="d-inline"
                                      data-confirm="{{ $reservation->vehicle->name }} le {{ $reservation->reservation_date->format('d/m/Y') }} de {{ \Carbon\Carbon::parse($reservation->start_time)->format('H:i') }} à {{ \Carbon\Carbon::parse($reservation->end_time)->format('H:i') }}. Le créneau redeviendra disponible."
                                      data-confirm-title="Annuler cette réservation ?"
                                      data-confirm-ok="Annuler la réservation"
                                      data-confirm-cancel="Revenir">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-icon btn-sm btn-itcj-red" title="Annuler la réservation">
                                        <i class="ki-duotone ki-cross fs-4"><i class="path1"></i><i class="path2"></i></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ auth()->user()->isAdmin() ? 7 : 6 }}" class="text-center py-10">
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
                    $isPast = $reservation->reservation_date->lt(now()->startOfDay());
                @endphp
                <div class="reservation-mobile-card {{ $isPast ? 'reservation-past' : '' }}">
                    <div class="reservation-mobile-header">
                        <div>
                            <div class="reservation-mobile-date">{{ $reservation->reservation_date->format('d/m/Y') }}</div>
                            <div class="reservation-mobile-vehicle">{{ $reservation->vehicle->name }} - {{ $reservation->vehicle->license_plate }}</div>
                        </div>
                        @if($isPast)
                            <span class="badge badge-archived fs-8">
                                <i class="ki-duotone ki-archive fs-7 me-1"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                                Archive
                            </span>
                        @else
                            <span class="badge fs-8" style="background: {{ $status['bg'] }}; color: {{ $status['text'] }};">
                                {{ $statusLabels[$reservation->status] ?? $reservation->status }}
                            </span>
                        @endif
                    </div>
                    <div class="reservation-mobile-body">
                        @if(auth()->user()->isAdmin())
                        <div class="reservation-mobile-row">
                            <span class="reservation-mobile-label">Reserve par</span>
                            <span class="reservation-mobile-value">
                                {{ $reservation->user->name ?? '-' }}
                                @if($reservation->user_id === auth()->id())
                                <span class="badge badge-light-primary fs-9 ms-1">Moi</span>
                                @endif
                            </span>
                        </div>
                        @endif
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
                        @if($reservation->canBeCancelled() && ($reservation->user_id === auth()->id() || auth()->user()->isAdmin()))
                        <form action="{{ route('reservations.destroy', $reservation) }}" method="POST" class="flex-grow-1"
                              data-confirm="{{ $reservation->vehicle->name }} le {{ $reservation->reservation_date->format('d/m/Y') }} de {{ \Carbon\Carbon::parse($reservation->start_time)->format('H:i') }} à {{ \Carbon\Carbon::parse($reservation->end_time)->format('H:i') }}. Le créneau redeviendra disponible."
                                      data-confirm-title="Annuler cette réservation ?"
                                      data-confirm-ok="Annuler la réservation"
                                      data-confirm-cancel="Revenir">
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

<script>
function updateFilters() {
    const dateFilter = document.getElementById('date-filter').value;
    const userFilter = document.getElementById('filter-reservations');
    const searchInput = document.getElementById('search-input');

    let url = '{{ route('reservations.index') }}?date=' + dateFilter;

    if (userFilter) {
        url += '&filter=' + userFilter.value;
    }

    if (searchInput && searchInput.value.trim()) {
        url += '&search=' + encodeURIComponent(searchInput.value.trim());
    }

    window.location.href = url;
}
</script>
@endsection
