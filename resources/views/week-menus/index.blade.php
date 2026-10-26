@extends('layouts.app')

@section('title', 'Menu de la semaine')
@section('page-title', 'Menu de la semaine')

@section('breadcrumb')
<li class="breadcrumb-item text-muted">Menu</li>
@endsection

@section('page-actions')
@if(auth()->user()->isAdmin())
<a href="{{ route('week-menus.admin.edit') }}" class="btn btn-sm btn-itcj desktop-add-btn">
    <i class="ki-duotone ki-pencil fs-2"><i class="path1"></i><i class="path2"></i></i>
    Modifier le menu
</a>
@endif
@endsection

@push('styles')
@include('week-menus._public-styles')
@endpush

@section('content')
@php
    $dayPalette = [
        'lundi'    => ['bg' => 'var(--danger-light)',  'text' => 'var(--primary)'],
        'mardi'    => ['bg' => 'var(--warning-light)', 'text' => 'var(--accent-dark)'],
        'mercredi' => ['bg' => '#E8F0E8',              'text' => 'var(--success)'],
        'jeudi'    => ['bg' => '#EAF0F2',              'text' => '#4A6B78'],
        'vendredi' => ['bg' => '#F3EAE2',              'text' => 'var(--secondary-dark)'],
        'samedi'   => ['bg' => '#EEEAF2',              'text' => '#6B5B8A'],
        'dimanche' => ['bg' => 'var(--danger-light)',  'text' => 'var(--primary-dark)'],
    ];
    // Chaque icone duotone exige un nombre precis de <i class="pathN">
    $periodIcons = [
        'midi' => ['icon' => 'ki-sun', 'paths' => 9],
        'soir' => ['icon' => 'ki-moon', 'paths' => 2],
    ];
    $emptyLabel = 'Pas de plat';
@endphp

{{-- ===== En-tete ===== --}}
<div class="menu-head">
    <div>
        <h2 class="menu-head-title">{{ $activeWeek?->name ?? 'Aucune semaine en ligne' }}</h2>
        <div class="menu-head-sub">
            @if($dateRange)
                <span>{{ ucfirst($dateRange) }}</span>
            @endif
            <span class="menu-chip menu-chip--today">Aujourd'hui : {{ $todayCode }} {{ $currentPeriodCode }}</span>
        </div>
    </div>
</div>

@if(!$activeWeek || $visibleCount === 0)
    {{-- ===== Rien a afficher ===== --}}
    <div class="menu-empty">
        <i class="ki-duotone ki-coffee"><i class="path1"></i><i class="path2"></i></i>
        <h3>Le menu n'est pas encore publie</h3>
        <p class="mb-0">
            @if(auth()->user()->isAdmin())
                Saisissez les plats de la semaine et rendez-les visibles pour qu'ils apparaissent ici.
            @else
                Revenez un peu plus tard, les plats de la semaine seront affiches ici.
            @endif
        </p>
    </div>
@else
    {{-- ===== Tableau : 7 jours fixes, une ligne par periode ===== --}}
    <div class="menu-table-wrap">
        <table class="menu-table" aria-label="Menu de la semaine par jour et par periode">
            <thead>
                <tr>
                    <th class="period-col" scope="col"><span class="visually-hidden">Periode</span></th>
                    @foreach($days as $day)
                        @php $palette = $dayPalette[$day->code] ?? $dayPalette['lundi']; @endphp
                        <th scope="col" class="{{ $day->code === $todayCode ? 'is-today' : '' }}">
                            <span class="day-pill" style="background: {{ $palette['bg'] }}; color: {{ $palette['text'] }};">{{ $day->code }}</span>
                            @if($day->code === $todayCode)
                                <span class="today-tag">Aujourd'hui</span>
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($periods as $period)
                    @php $icon = $periodIcons[$period->code] ?? $periodIcons['midi']; @endphp
                    <tr class="period--{{ $period->code }}">
                        <td class="period-col" scope="row">
                            <i class="ki-duotone {{ $icon['icon'] }}">@for($i = 1; $i <= $icon['paths']; $i++)<i class="path{{ $i }}"></i>@endfor</i>
                            {{ $period->code }}
                        </td>
                        @foreach($days as $day)
                            @php
                                $menu = $menus->get($day->id . '-' . $period->id);
                                $shown = $menu && $menu->available;
                                $isToday = $day->code === $todayCode;
                                $isNow = $isToday && $period->code === $currentPeriodCode;
                            @endphp
                            <td>
                                <div class="menu-cell {{ $shown ? '' : 'is-empty' }} {{ $isToday ? 'is-today' : '' }} {{ $isNow && $shown ? 'is-now' : '' }}">
                                    @if($shown)
                                        {{ $menu->dish_name }}
                                        @if($menu->description)
                                            <span class="menu-cell-desc">{{ $menu->description }}</span>
                                        @endif
                                    @else
                                        {{ $emptyLabel }}
                                    @endif
                                </div>
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- ===== Cartes par jour (tablette et mobile) ===== --}}
    <div class="menu-days">
        @foreach($days as $day)
            @php
                $palette = $dayPalette[$day->code] ?? $dayPalette['lundi'];
                $isToday = $day->code === $todayCode;
            @endphp
            <article class="menu-day {{ $isToday ? 'is-today' : '' }}" @if($isToday) id="menu-today" @endif>
                <div class="menu-day-head">
                    <span class="menu-day-badge" style="background: {{ $palette['bg'] }}; color: {{ $palette['text'] }};">
                        {{ strtoupper(substr($day->code, 0, 3)) }}
                    </span>
                    <div>
                        <div class="menu-day-name">{{ $day->code }}</div>
                        @if($isToday)
                            <span class="menu-day-today">Aujourd'hui</span>
                        @endif
                    </div>
                </div>
                @foreach($periods as $period)
                    @php
                        $menu = $menus->get($day->id . '-' . $period->id);
                        $shown = $menu && $menu->available;
                        $isNow = $isToday && $period->code === $currentPeriodCode;
                    @endphp
                    <div class="menu-slot menu-slot--{{ $period->code }} {{ $shown ? '' : 'is-empty' }} {{ $isNow && $shown ? 'is-now' : '' }}">
                        <span class="menu-slot-period">{{ $period->code }}</span>
                        <div>
                            <div class="menu-slot-dish">{{ $shown ? $menu->dish_name : $emptyLabel }}</div>
                            @if($shown && $menu->description)
                                <div class="menu-cell-desc">{{ $menu->description }}</div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </article>
        @endforeach
    </div>
@endif

@if(auth()->user()->isAdmin())
<a href="{{ route('week-menus.admin.edit') }}" class="fab" aria-label="Modifier le menu">
    <i class="ki-duotone ki-pencil"><i class="path1"></i><i class="path2"></i></i>
</a>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Sur petit ecran, on amene directement le jour en cours a l'ecran
    var today = document.getElementById('menu-today');
    var isFirstCard = today && today.previousElementSibling === null;
    if (today && !isFirstCard && window.matchMedia('(max-width: 991px)').matches) {
        setTimeout(function () {
            today.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 150);
    }
});
</script>
@endpush
