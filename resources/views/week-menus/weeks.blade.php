@extends('layouts.app')

@section('title', 'Cycle des menus')
@section('page-title', 'Cycle des menus')

@section('breadcrumb')
<li class="breadcrumb-item text-muted">Menu</li>
<li class="breadcrumb-item text-muted">Cycle</li>
@endsection

@section('page-actions')
<a href="{{ route('week-menus.admin.edit') }}" class="btn btn-sm btn-itcj-outline desktop-add-btn">
    <i class="ki-duotone ki-pencil fs-2"><i class="path1"></i><i class="path2"></i></i>
    Modifier les plats
</a>
<button type="button" class="btn btn-sm btn-itcj desktop-add-btn" id="toggleCreate" aria-controls="createPanel" aria-expanded="false">
    <i class="ki-duotone ki-plus fs-2"></i>
    Nouvelle semaine
</button>
@endsection

@push('styles')
@include('week-menus._menu-styles')
<style>
    /* ============ PAGE CYCLE ============ */
    .cycle-section { margin-bottom: 24px; }

    .cycle-section-head {
        display: flex;
        flex-wrap: wrap;
        align-items: baseline;
        justify-content: space-between;
        gap: 8px 14px;
        margin: 0 4px 12px;
    }

    .cycle-section-title {
        font-family: 'Playfair Display', Georgia, serif;
        font-size: 18px;
        font-weight: 700;
        color: var(--text-primary);
        margin: 0;
    }

    .cycle-section-hint { font-size: 12.5px; color: var(--text-muted); }

    .cycle-list { display: flex; flex-direction: column; gap: 12px; }

    .cycle-row {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto auto;
        align-items: center;
        gap: 14px;
        padding: 14px 16px;
        border-radius: 18px;
        background: var(--bg-card);
        box-shadow: var(--neu-shadow-sm);
        transition: box-shadow 0.25s ease, transform 0.25s ease;
    }

    .cycle-row:hover, .cycle-row:focus-within {
        box-shadow: var(--neu-shadow);
        transform: translateY(-1px);
    }

    .cycle-row.is-active { box-shadow: var(--neu-shadow-sm), 0 0 0 2px var(--success); }
    .cycle-row.is-reserve { background: var(--bg-base); box-shadow: var(--neu-shadow-inset); }
    .cycle-row.is-reserve:hover { transform: none; box-shadow: var(--neu-shadow-inset); }

    .cycle-pos {
        width: 46px;
        height: 46px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Playfair Display', Georgia, serif;
        font-size: 21px;
        font-weight: 700;
        color: var(--primary);
        background: var(--bg-base);
        box-shadow: var(--neu-shadow-inset);
        flex-shrink: 0;
    }

    .is-reserve .cycle-pos { color: var(--text-muted); font-size: 17px; }

    .cycle-main { min-width: 0; }

    .cycle-name-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 6px 10px;
    }

    .cycle-name {
        font-family: 'Playfair Display', Georgia, serif;
        font-size: 17px;
        font-weight: 700;
        color: var(--text-primary);
        line-height: 1.25;
        overflow-wrap: anywhere;
    }

    .cycle-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 9px;
        border-radius: 999px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .cycle-badge--live { background: #E8F0E8; color: var(--success); }
    .cycle-badge--live::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: var(--success); }
    .cycle-badge--empty { background: var(--warning-light); color: var(--accent-dark); }

    .cycle-meta {
        margin-top: 3px;
        font-size: 12.5px;
        color: var(--text-secondary);
    }

    .cycle-meta strong { color: var(--text-primary); font-weight: 700; }

    /* Renommage en place */
    .cycle-rename { display: none; align-items: center; gap: 8px; flex-wrap: wrap; }
    .cycle-row.is-renaming .cycle-rename { display: flex; }
    .cycle-row.is-renaming .cycle-name-row, .cycle-row.is-renaming .cycle-meta { display: none; }

    .cycle-rename input {
        flex: 1 1 180px;
        min-width: 0;
        border: none;
        border-radius: 10px;
        padding: 9px 12px;
        font-family: 'Nunito', sans-serif;
        font-size: 14.5px;
        font-weight: 600;
        color: var(--text-primary);
        background: var(--bg-card);
        box-shadow: var(--neu-shadow-inset);
    }

    .cycle-rename input:focus { outline: none; box-shadow: var(--neu-shadow-inset), 0 0 0 2px var(--accent); }

    /* Glisser-deposer */
    .cycle-list--sortable .cycle-row[draggable="true"] { cursor: grab; }
    .cycle-list--sortable .cycle-row[draggable="true"]:active { cursor: grabbing; }
    .cycle-list--sortable .cycle-row .cycle-actions, .cycle-list--sortable .cycle-row .cycle-rename { cursor: default; }

    .cycle-pos { position: relative; }
    .cycle-grip {
        position: absolute;
        left: 50%;
        bottom: 5px;
        width: 14px;
        height: 4px;
        transform: translateX(-50%);
        border-top: 2px dotted var(--text-muted);
        border-bottom: 2px dotted var(--text-muted);
        opacity: 0.55;
    }

    .cycle-row.is-dragging { opacity: 0.45; }
    .cycle-row.is-drop-before { box-shadow: var(--neu-shadow-sm), 0 -3px 0 0 var(--accent); }
    .cycle-row.is-drop-after { box-shadow: var(--neu-shadow-sm), 0 3px 0 0 var(--accent); }
    .cycle-row.is-active.is-drop-before { box-shadow: var(--neu-shadow-sm), 0 0 0 2px var(--success), 0 -3px 0 0 var(--accent); }
    .cycle-row.is-active.is-drop-after { box-shadow: var(--neu-shadow-sm), 0 0 0 2px var(--success), 0 3px 0 0 var(--accent); }

    /* Fleches d'ordre */
    .cycle-order { display: flex; flex-direction: column; gap: 4px; }

    .cycle-order .btn, .cycle-actions .btn {
        width: 36px;
        height: 36px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
    }

    .cycle-order .btn { background: var(--bg-base); color: var(--text-secondary); box-shadow: var(--neu-shadow-sm); border: none; }
    .cycle-order .btn:hover:not(:disabled) { color: var(--primary); }
    .cycle-order .btn:disabled { opacity: 0.35; box-shadow: none; cursor: not-allowed; }

    .cycle-actions { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; justify-content: flex-end; }
    .cycle-actions form { margin: 0; }

    .cycle-actions .btn-ghost {
        background: var(--bg-base);
        color: var(--text-secondary);
        box-shadow: var(--neu-shadow-sm);
        border: none;
    }
    .cycle-actions .btn-ghost:hover { color: var(--primary); }

    /* Rotation automatique */
    .rotation-range {
        display: inline-block;
        margin-left: 6px;
        padding: 2px 8px;
        border-radius: 999px;
        background: var(--bg-base);
        box-shadow: var(--neu-shadow-inset);
        font-size: 11.5px;
        font-weight: 700;
        color: var(--text-secondary);
    }

    .rotation-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px 18px;
        margin-top: 16px;
        padding: 13px 16px;
        border-radius: 14px;
        background: var(--bg-base);
        box-shadow: var(--neu-shadow-inset);
    }

    .rotation-card.is-auto .rotation-row { background: #E8F0E8; box-shadow: none; }

    .rotation-toggle { margin: 0; }
    .rotation-switch { gap: 10px; }
    .rotation-switch .slot-switch { width: 44px; height: 24px; }
    .rotation-switch .slot-switch::after { width: 18px; height: 18px; }
    .rotation-switch input:checked + .slot-switch::after { transform: translateX(20px); }

    .rotation-switch-text {
        font-size: 13.5px;
        font-weight: 700;
        color: var(--text-primary);
        white-space: nowrap;
    }

    .rotation-hint {
        flex: 1;
        min-width: 220px;
        font-size: 12.5px;
        color: var(--text-secondary);
        line-height: 1.45;
    }

    .rotation-hint strong { color: var(--text-primary); }

    .cycle-when {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        margin-top: 4px;
        font-size: 11.5px;
        font-weight: 700;
        color: var(--accent-dark);
    }

    .cycle-row.is-active .cycle-when { color: var(--success); }

    /* Panneau de creation */
    .create-panel {
        display: none;
        margin-bottom: 22px;
        padding: 20px 22px;
        border-radius: 20px;
        background: var(--bg-card);
        box-shadow: var(--neu-shadow);
    }

    .create-panel.is-open { display: block; }

    .create-panel h3 {
        font-family: 'Playfair Display', Georgia, serif;
        font-size: 18px;
        font-weight: 700;
        margin: 0 0 4px;
        color: var(--text-primary);
    }

    .create-panel p { font-size: 13px; color: var(--text-secondary); margin: 0 0 16px; }

    .create-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) auto;
        gap: 12px;
        align-items: end;
    }

    .create-field label {
        display: block;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--text-muted);
        margin-bottom: 6px;
    }

    .create-field input, .create-field select {
        width: 100%;
        border: none;
        border-radius: 12px;
        padding: 12px 14px;
        min-height: 46px;
        font-family: 'Nunito', sans-serif;
        font-size: 14.5px;
        font-weight: 600;
        color: var(--text-primary);
        background: var(--bg-base);
        box-shadow: var(--neu-shadow-inset);
    }

    .create-field input:focus, .create-field select:focus { outline: none; box-shadow: var(--neu-shadow-inset), 0 0 0 2px var(--accent); }
    .create-field .field-error { margin-top: 6px; font-size: 11.5px; color: var(--danger); }
    .create-panel .btn { min-height: 46px; white-space: nowrap; }

    .cycle-empty {
        padding: 36px 20px;
        text-align: center;
        border-radius: 18px;
        background: var(--bg-card);
        box-shadow: var(--neu-shadow-sm);
        color: var(--text-secondary);
    }

    @media (max-width: 767px) {
        .cycle-row {
            grid-template-columns: auto minmax(0, 1fr) auto;
            grid-template-areas: "pos main order" "actions actions actions";
            gap: 12px;
            padding: 13px 14px;
        }
        .cycle-pos { grid-area: pos; }
        .cycle-main { grid-area: main; }
        .cycle-order { grid-area: order; }
        .cycle-actions { grid-area: actions; justify-content: flex-start; padding-top: 10px; border-top: 1px dashed rgba(0,0,0,0.08); }
        .cycle-actions .btn { width: 40px; height: 40px; }
        .create-grid { grid-template-columns: 1fr; }
        .create-panel { padding: 16px; border-radius: 16px; }
        .create-panel .btn { width: 100%; }
    }
</style>
@endpush

@section('content')
@php
    $activeId = $activeWeek?->id;
    $openCreate = $errors->hasAny(['name', 'source_id']) || old('name') !== null;
    $trashIcon = '<i class="ki-duotone ki-trash fs-4"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i><i class="path5"></i></i>';
@endphp

{{-- ===== Etat du cycle et rotation ===== --}}
@php
    $current = $activeWeek ? ($schedule[$activeWeek->id] ?? null) : null;
@endphp
<div class="week-switcher rotation-card {{ $autoRotation ? 'is-auto' : '' }}">
    <div class="week-switcher-head" style="margin-bottom: 0;">
        <div class="week-live">
            <span class="week-live-dot" aria-hidden="true"></span>
            @if($activeWeek)
                <span>
                    En ligne actuellement : <strong>{{ $activeWeek->name }}</strong>
                    @if($current)
                        <span class="rotation-range">{{ \App\Services\MenuRotation::formatRange($current['start'], $current['end']) }}</span>
                    @endif
                </span>
            @else
                <span>Aucune semaine en ligne</span>
            @endif
        </div>
        <span class="text-muted fs-8">
            Cycle de {{ $cycleWeeks->count() }} semaine{{ $cycleWeeks->count() > 1 ? 's' : '' }}
            @if($reserveWeeks->isNotEmpty())
                · {{ $reserveWeeks->count() }} en reserve
            @endif
        </span>
    </div>

    <div class="rotation-row">
        <form action="{{ route('week-menus.admin.rotation') }}" method="POST" class="rotation-toggle" data-no-loading id="rotationForm">
            @csrf
            @method('PATCH')
            <input type="hidden" name="enabled" value="{{ $autoRotation ? 0 : 1 }}">
            <label class="slot-visibility rotation-switch">
                <input type="checkbox" id="rotationSwitch" {{ $autoRotation ? 'checked' : '' }} aria-describedby="rotationHint">
                <span class="slot-switch" aria-hidden="true"></span>
                <span class="rotation-switch-text">Rotation automatique</span>
            </label>
        </form>
        <div class="rotation-hint" id="rotationHint">
            @if($autoRotation)
                @if($nextSwitch)
                    Chaque lundi, la semaine suivante du cycle prend le relais.
                    Prochaine bascule le {{ \App\Services\MenuRotation::formatDate($nextSwitch['at']) }} : <strong>« {{ $nextSwitch['week']->name }} »</strong> prend le relais.
                @elseif($cycleWeeks->count() === 1)
                    Une seule semaine dans le cycle : elle reste en ligne. Ajoutez-en une autre pour que la rotation tourne.
                @else
                    Ajoutez une semaine au cycle pour que la rotation tourne.
                @endif
            @else
                Desactivee : la semaine en ligne ne change pas tant que vous n'en choisissez pas une autre.
                Activez-la pour que le cycle avance seul chaque lundi.
            @endif
        </div>
    </div>
</div>

{{-- ===== Creation ===== --}}
<div class="create-panel {{ $openCreate ? 'is-open' : '' }}" id="createPanel">
    <h3>Nouvelle semaine</h3>
    <p>Elle se place en fin de cycle. Partez d'une semaine vide, ou copiez les plats d'une semaine existante.</p>
    <form action="{{ route('week-menus.admin.weeks.store') }}" method="POST">
        @csrf
        <div class="create-grid">
            <div class="create-field">
                <label for="newWeekName">Nom</label>
                <input type="text" id="newWeekName" name="name" maxlength="{{ \App\Models\MenuWeek::NAME_MAX }}" required
                       placeholder="Ex. Semaine {{ $allWeeks->count() + 1 }}, Menu de fete"
                       value="{{ old('name') }}">
                @error('name')<div class="field-error">{{ $message }}</div>@enderror
            </div>
            <div class="create-field">
                <label for="newWeekSource">Partir de</label>
                <select id="newWeekSource" name="source_id">
                    <option value="">Une semaine vide (plats masques)</option>
                    @foreach($allWeeks as $w)
                        <option value="{{ $w->id }}" {{ (string) old('source_id') === (string) $w->id ? 'selected' : '' }}>
                            Copie de « {{ $w->name }} »
                        </option>
                    @endforeach
                </select>
                @error('source_id')<div class="field-error">{{ $message }}</div>@enderror
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-itcj-outline" id="cancelCreate">Annuler</button>
                <button type="submit" class="btn btn-sm btn-itcj">
                    <i class="ki-duotone ki-check fs-4"><i class="path1"></i><i class="path2"></i></i>
                    Creer la semaine
                </button>
            </div>
        </div>
    </form>
</div>

{{-- ===== Ordre du cycle ===== --}}
<section class="cycle-section" aria-labelledby="cycleTitle">
    <div class="cycle-section-head">
        <h2 class="cycle-section-title" id="cycleTitle">Ordre du cycle</h2>
        <span class="cycle-section-hint">Glissez une semaine ou utilisez les fleches. Reordonner ne change pas la semaine en ligne, seulement ce qui vient ensuite.</span>
    </div>

    @if($cycleWeeks->isEmpty())
        <div class="cycle-empty">
            @if($allWeeks->isEmpty())
                Aucune semaine pour l'instant. Creez-en une pour commencer.
            @else
                Toutes les semaines sont en reserve. Remettez-en une dans le cycle pour qu'elle tourne.
            @endif
        </div>
    @else
        <form action="{{ route('week-menus.admin.weeks.reorder') }}" method="POST" id="reorderForm" data-no-loading hidden>
            @csrf
            @method('PATCH')
        </form>
        <div class="cycle-list cycle-list--sortable" id="cycleList" aria-describedby="cycleTitle">
            @foreach($cycleWeeks as $index => $w)
                @include('week-menus._week-row', [
                    'w' => $w,
                    'position' => $index + 1,
                    'isFirst' => $index === 0,
                    'isLast' => $index === $cycleWeeks->count() - 1,
                    'inCycle' => true,
                ])
            @endforeach
        </div>
    @endif
</section>

{{-- ===== En reserve ===== --}}
@if($reserveWeeks->isNotEmpty())
<section class="cycle-section" aria-labelledby="reserveTitle">
    <div class="cycle-section-head">
        <h2 class="cycle-section-title" id="reserveTitle">En reserve</h2>
        <span class="cycle-section-hint">Ces semaines ne tournent pas dans le cycle. Vous pouvez les afficher ponctuellement ou les remettre dans le cycle.</span>
    </div>
    <div class="cycle-list">
        @foreach($reserveWeeks as $w)
            @include('week-menus._week-row', [
                'w' => $w,
                'position' => null,
                'isFirst' => true,
                'isLast' => true,
                'inCycle' => false,
            ])
        @endforeach
    </div>
</section>
@endif

{{-- FAB mobile : nouvelle semaine --}}
<button type="button" class="fab" id="fabCreate" aria-label="Nouvelle semaine">
    <i class="ki-duotone ki-plus"></i>
</button>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var panel = document.getElementById('createPanel');
    var toggle = document.getElementById('toggleCreate');
    var fab = document.getElementById('fabCreate');
    var cancel = document.getElementById('cancelCreate');
    var nameInput = document.getElementById('newWeekName');

    function openCreate() {
        panel.classList.add('is-open');
        if (toggle) toggle.setAttribute('aria-expanded', 'true');
        panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        setTimeout(function () { nameInput.focus(); }, 250);
    }

    function closeCreate() {
        panel.classList.remove('is-open');
        if (toggle) toggle.setAttribute('aria-expanded', 'false');
    }

    if (toggle) toggle.addEventListener('click', function () {
        panel.classList.contains('is-open') ? closeCreate() : openCreate();
    });
    if (fab) fab.addEventListener('click', openCreate);
    if (cancel) cancel.addEventListener('click', closeCreate);

    // Interrupteur Auto / Manuel : la case soumet le formulaire
    var rotationSwitch = document.getElementById('rotationSwitch');
    if (rotationSwitch) {
        rotationSwitch.addEventListener('change', function () {
            rotationSwitch.disabled = true;
            document.getElementById('rotationForm').submit();
        });
    }

    // Glisser-deposer des semaines du cycle (les fleches restent disponibles,
    // notamment au clavier et sur mobile).
    var list = document.getElementById('cycleList');
    var reorderForm = document.getElementById('reorderForm');
    if (list && reorderForm && 'draggable' in document.createElement('div')) {
        var dragged = null;
        var initialOrder = [...list.querySelectorAll('.cycle-row')].map(function (r) { return r.dataset.weekId; }).join(',');

        function clearMarks() {
            list.querySelectorAll('.is-drop-before, .is-drop-after').forEach(function (r) {
                r.classList.remove('is-drop-before', 'is-drop-after');
            });
        }

        list.addEventListener('dragstart', function (e) {
            var row = e.target.closest('.cycle-row[draggable="true"]');
            if (!row || e.target.closest('.cycle-actions, .cycle-rename, .cycle-order')) { e.preventDefault(); return; }
            dragged = row;
            row.classList.add('is-dragging');
            e.dataTransfer.effectAllowed = 'move';
            try { e.dataTransfer.setData('text/plain', row.dataset.weekId); } catch (err) {}
        });

        list.addEventListener('dragover', function (e) {
            if (!dragged) return;
            var row = e.target.closest('.cycle-row[draggable="true"]');
            if (!row || row === dragged) return;
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            var rect = row.getBoundingClientRect();
            var before = (e.clientY - rect.top) < rect.height / 2;
            clearMarks();
            row.classList.add(before ? 'is-drop-before' : 'is-drop-after');
        });

        list.addEventListener('dragleave', function (e) {
            if (!list.contains(e.relatedTarget)) clearMarks();
        });

        list.addEventListener('drop', function (e) {
            if (!dragged) return;
            e.preventDefault();
            var row = e.target.closest('.cycle-row[draggable="true"]');
            if (row && row !== dragged) {
                var rect = row.getBoundingClientRect();
                var before = (e.clientY - rect.top) < rect.height / 2;
                row.parentNode.insertBefore(dragged, before ? row : row.nextSibling);
            }
            clearMarks();
        });

        list.addEventListener('dragend', function () {
            if (!dragged) return;
            dragged.classList.remove('is-dragging');
            clearMarks();
            dragged = null;

            var ids = [...list.querySelectorAll('.cycle-row')].map(function (r) { return r.dataset.weekId; });
            if (ids.join(',') === initialOrder) return;

            // Numeros mis a jour tout de suite, puis enregistrement
            list.querySelectorAll('.cycle-pos-num').forEach(function (n, i) { n.textContent = i + 1; });
            reorderForm.querySelectorAll('input[name="order[]"]').forEach(function (i) { i.remove(); });
            ids.forEach(function (id) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'order[]';
                input.value = id;
                reorderForm.appendChild(input);
            });
            reorderForm.submit();
        });
    }

    // Renommage en place : un formulaire par ligne, affiche a la demande
    document.querySelectorAll('[data-rename]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var row = btn.closest('.cycle-row');
            row.classList.add('is-renaming');
            var input = row.querySelector('.cycle-rename input[name="name"]');
            input.focus();
            input.select();
        });
    });

    document.querySelectorAll('[data-rename-cancel]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var row = btn.closest('.cycle-row');
            var input = row.querySelector('.cycle-rename input[name="name"]');
            input.value = input.defaultValue;
            row.classList.remove('is-renaming');
        });
    });

    document.querySelectorAll('.cycle-rename input[name="name"]').forEach(function (input) {
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                e.preventDefault();
                input.closest('.cycle-row').querySelector('[data-rename-cancel]').click();
            }
        });
    });
});
</script>
@endpush
