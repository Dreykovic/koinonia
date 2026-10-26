@extends('layouts.app')

@section('title', 'Menus')
@section('page-title', 'Menu de la semaine')

@section('breadcrumb')
<li class="breadcrumb-item text-muted">Menu</li>
@endsection

@section('page-actions')
<a href="{{ route('week-menus.admin.weeks.index') }}" class="btn btn-sm btn-itcj-outline desktop-add-btn">
    <i class="ki-duotone ki-abstract-26 fs-2"><i class="path1"></i><i class="path2"></i></i>
    Gerer le cycle
</a>
<a href="{{ route('week-menus.index') }}" class="btn btn-sm btn-itcj-outline desktop-add-btn">
    <i class="ki-duotone ki-eye fs-2"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
    Voir le menu affiche
</a>
@endsection

@push('styles')
@include('week-menus._menu-styles')
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
    $todayCode = strtolower(now()->locale('fr_FR')->dayName);
    // Chaque icone duotone exige un nombre precis de <i class="pathN">
    $periodIcons = [
        'midi' => ['icon' => 'ki-sun', 'paths' => 9],
        'soir' => ['icon' => 'ki-moon', 'paths' => 2],
    ];
@endphp

{{-- ===== Selecteur de semaine ===== --}}
@php
    $cycleCount = $weeks->where('in_cycle', true)->count();
    $cyclePositions = $weeks->where('in_cycle', true)->values()->mapWithKeys(fn ($w, $i) => [$w->id => $i + 1]);
@endphp
<div class="week-switcher">
    <div class="week-switcher-head">
        <div class="week-live">
            <span class="week-live-dot" aria-hidden="true"></span>
            @if($activeWeek)
                <span>En ligne actuellement : <strong>{{ $activeWeek->name }}</strong></span>
            @else
                <span>Aucune semaine en ligne</span>
            @endif
        </div>
        <a href="{{ route('week-menus.admin.weeks.index') }}" class="week-cycle-link">
            Cycle de {{ $cycleCount }} semaine{{ $cycleCount > 1 ? 's' : '' }} · Gerer
        </a>
    </div>

    <div class="week-tabs" role="tablist" aria-label="Semaines du cycle">
        @foreach($weeks as $w)
            @php
                $isCurrent = $w->id === $week->id;
                $isOnline = $activeWeek && $w->id === $activeWeek->id;
            @endphp
            <a href="{{ route('week-menus.admin.edit', ['week' => $w->id]) }}"
               class="week-tab {{ $isCurrent ? 'is-current' : '' }} {{ $w->in_cycle ? '' : 'is-reserve' }}"
               role="tab"
               aria-selected="{{ $isCurrent ? 'true' : 'false' }}"
               aria-label="{{ $w->name }}{{ $isOnline ? ' (affichee sur le site)' : '' }}{{ $w->in_cycle ? '' : ' (en reserve)' }}">
                <span class="week-tab-label">{{ $w->in_cycle ? 'N° ' . $cyclePositions[$w->id] : 'Reserve' }}</span>
                <span class="week-tab-name">{{ $w->name }}</span>
                @if($isOnline)
                    <span class="week-tab-state">En ligne</span>
                @endif
            </a>
        @endforeach
    </div>

    @if(!$activeWeek || $week->id !== $activeWeek->id)
        <div class="week-publish">
            <div class="week-publish-text">
                Vous modifiez <strong>{{ $week->name }}</strong>. Elle n'est pas encore visible par la communaute.
            </div>
            <form action="{{ route('week-menus.admin.active-week') }}" method="POST"
                  data-confirm="« {{ $week->name }} » remplacera « {{ $activeWeek?->name ?? 'la semaine actuelle' }} » sur le site et sur le tableau de bord."
                  data-confirm-title="Afficher « {{ $week->name }} » ?"
                  data-confirm-type="info"
                  data-confirm-ok="Afficher">
                @csrf
                @method('PATCH')
                <input type="hidden" name="week" value="{{ $week->id }}">
                <button type="submit" class="btn btn-sm btn-itcj-gold">
                    <i class="ki-duotone ki-rocket fs-4"><i class="path1"></i><i class="path2"></i></i>
                    Afficher cette semaine
                </button>
            </form>
        </div>
    @endif
</div>

{{-- ===== Plateau des jours : un seul formulaire ===== --}}
<form action="{{ route('week-menus.admin.update-week', ['week' => $week->id]) }}" method="POST" id="weekMenuForm" class="week-form" data-no-loading>
    @csrf
    @method('PUT')

    <div class="day-board">
        @foreach($menusPerDays as $day => $menuCollection)
            @php
                $palette = $dayPalette[$day] ?? $dayPalette['lundi'];
                $isToday = $day === $todayCode && $activeWeek && $week->id === $activeWeek->id;
            @endphp
            <div class="day-card {{ $isToday ? 'is-today' : '' }}">
                <div class="day-card-head">
                    <span class="day-badge" style="background: {{ $palette['bg'] }}; color: {{ $palette['text'] }};">
                        {{ strtoupper(substr($day, 0, 3)) }}
                    </span>
                    <div>
                        <div class="day-name">{{ $day }}</div>
                        @if($isToday)
                            <span class="day-today-tag">Au menu aujourd'hui</span>
                        @endif
                    </div>
                </div>

                @foreach($menuCollection as $menu)
                    @php $period = $menu->period->code; @endphp
                    <div class="slot slot--{{ $period }} {{ $menu->available ? '' : 'is-hidden' }}">
                        <div class="slot-head">
                            @php $icon = $periodIcons[$period] ?? $periodIcons['midi']; @endphp
                            <span class="slot-label">
                                <i class="ki-duotone {{ $icon['icon'] }}">@for($i = 1; $i <= $icon['paths']; $i++)<i class="path{{ $i }}"></i>@endfor</i>
                                {{ $period }}
                            </span>
                            <label class="slot-visibility">
                                <input type="hidden" name="menus[{{ $menu->id }}][available]" value="0">
                                <input type="checkbox"
                                       name="menus[{{ $menu->id }}][available]"
                                       value="1"
                                       {{ $menu->available ? 'checked' : '' }}
                                       aria-label="Afficher le plat du {{ $period }} ({{ $day }}) sur le site">
                                <span class="slot-switch" aria-hidden="true"></span>
                                <span class="slot-visibility-text">{{ $menu->available ? 'Visible' : 'Masque' }}</span>
                            </label>
                        </div>

                        <textarea name="menus[{{ $menu->id }}][dish_name]"
                                  class="slot-input"
                                  rows="1"
                                  maxlength="150"
                                  required
                                  placeholder="Nom du plat"
                                  aria-label="Plat du {{ $period }}, {{ $day }}">{{ old("menus.{$menu->id}.dish_name", $menu->dish_name) }}</textarea>
                        <div class="slot-error">Indiquez le nom du plat.</div>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>

    {{-- ===== Barre d'enregistrement ===== --}}
    <div class="save-bar">
        <div class="save-bar-status" id="saveStatus">
            <span class="save-dot" aria-hidden="true"></span>
            <span id="saveStatusText">Aucune modification</span>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-itcj-outline" id="resetWeek" disabled>Annuler</button>
            <button type="submit" class="btn btn-sm btn-itcj" id="saveWeek" disabled>
                <i class="ki-duotone ki-check fs-4"><i class="path1"></i><i class="path2"></i></i>
                <span class="save-label-full">Enregistrer la semaine</span>
                <span class="save-label-short">Enregistrer</span>
            </button>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // L'onglet de la semaine consultee reste visible meme si la liste defile
    var currentTab = document.querySelector('.week-tab.is-current');
    if (currentTab && currentTab.scrollIntoView) {
        currentTab.scrollIntoView({ inline: 'center', block: 'nearest' });
    }

    var form = document.getElementById('weekMenuForm');
    if (!form) return;

    var saveBtn = document.getElementById('saveWeek');
    var resetBtn = document.getElementById('resetWeek');
    var status = document.getElementById('saveStatus');
    var statusText = document.getElementById('saveStatusText');
    var submitting = false;

    // Hauteur du champ ajustee au contenu
    function autoGrow(el) {
        el.style.height = 'auto';
        el.style.height = el.scrollHeight + 'px';
    }

    // Etat initial, pour detecter ce qui a change
    var initial = new Map();
    form.querySelectorAll('.slot-input').forEach(function (el) {
        initial.set(el.name, el.value);
        autoGrow(el);
    });
    form.querySelectorAll('.slot-visibility input[type="checkbox"]').forEach(function (el) {
        initial.set(el.name, el.checked ? '1' : '0');
    });

    function countChanges() {
        var changed = 0;
        initial.forEach(function (value, name) {
            var el = form.querySelector('[name="' + CSS.escape(name) + '"]:not([type="hidden"])');
            if (!el) return;
            var current = el.type === 'checkbox' ? (el.checked ? '1' : '0') : el.value;
            if (current !== value) changed++;
        });
        return changed;
    }

    function refresh() {
        var n = countChanges();
        var empty = [...form.querySelectorAll('.slot-input')].some(function (el) {
            return el.value.trim() === '';
        });

        status.classList.toggle('is-dirty', n > 0);
        statusText.textContent = n === 0
            ? 'Aucune modification'
            : (n === 1 ? '1 modification non enregistree' : n + ' modifications non enregistrees');

        saveBtn.disabled = n === 0 || empty;
        resetBtn.disabled = n === 0;
    }

    form.addEventListener('input', function (e) {
        if (e.target.classList.contains('slot-input')) {
            autoGrow(e.target);
            var invalid = e.target.value.trim() === '';
            e.target.classList.toggle('is-invalid', invalid);
            e.target.closest('.slot').querySelector('.slot-error').classList.toggle('show', invalid);
        }
        refresh();
    });

    form.addEventListener('change', function (e) {
        if (e.target.type === 'checkbox') {
            var slot = e.target.closest('.slot');
            slot.classList.toggle('is-hidden', !e.target.checked);
            slot.querySelector('.slot-visibility-text').textContent = e.target.checked ? 'Visible' : 'Masque';
        }
        refresh();
    });

    resetBtn.addEventListener('click', function () {
        ConfirmModal.show({
            title: 'Annuler les modifications ?',
            message: 'Les plats retrouveront les valeurs enregistrees pour « {{ $week->name }} ».',
            type: 'warning',
            confirmText: 'Annuler les modifications',
            cancelText: 'Continuer l\'edition',
            onConfirm: function () {
                initial.forEach(function (value, name) {
                    var el = form.querySelector('[name="' + CSS.escape(name) + '"]:not([type="hidden"])');
                    if (!el) return;
                    if (el.type === 'checkbox') {
                        el.checked = value === '1';
                        el.dispatchEvent(new Event('change', { bubbles: true }));
                    } else {
                        el.value = value;
                        el.classList.remove('is-invalid');
                        el.closest('.slot').querySelector('.slot-error').classList.remove('show');
                        autoGrow(el);
                    }
                });
                refresh();
            }
        });
    });

    form.addEventListener('submit', function () {
        submitting = true;
        saveBtn.classList.add('btn-loading');
        saveBtn.disabled = true;
    });

    // Garde-fou : ne pas perdre une saisie en quittant la page
    window.addEventListener('beforeunload', function (e) {
        if (!submitting && countChanges() > 0) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    refresh();
});
</script>
@endpush
