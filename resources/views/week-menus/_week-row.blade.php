{{-- Ligne d'une semaine sur la page "Cycle des menus" --}}
@php
    $isActive = $w->id === $activeId;
    $allHidden = (int) $w->visible_count === 0;
@endphp
<div class="cycle-row {{ $isActive ? 'is-active' : '' }} {{ $inCycle ? '' : 'is-reserve' }}" id="week-{{ $w->id }}">
    <div class="cycle-pos" aria-hidden="true">
        @if($inCycle)
            {{ $position }}
        @else
            <i class="ki-duotone ki-archive fs-3"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
        @endif
    </div>

    <div class="cycle-main">
        <div class="cycle-name-row">
            <span class="cycle-name">{{ $w->name }}</span>
            @if($isActive)
                <span class="cycle-badge cycle-badge--live">En ligne</span>
            @endif
            @if($allHidden)
                <span class="cycle-badge cycle-badge--empty">Aucun plat visible</span>
            @endif
        </div>
        <div class="cycle-meta">
            <strong>{{ $w->visible_count }}</strong> plat{{ $w->visible_count > 1 ? 's' : '' }} visible{{ $w->visible_count > 1 ? 's' : '' }} sur {{ $w->slots_count }}
            @if(!$inCycle)
                · en reserve
            @endif
        </div>

        <form action="{{ route('week-menus.admin.weeks.update', $w) }}" method="POST" class="cycle-rename" data-no-loading>
            @csrf
            @method('PATCH')
            <input type="text" name="name" value="{{ $w->name }}" maxlength="{{ \App\Models\MenuWeek::NAME_MAX }}" required aria-label="Nouveau nom de la semaine">
            <button type="submit" class="btn btn-sm btn-itcj">Enregistrer</button>
            <button type="button" class="btn btn-sm btn-itcj-outline" data-rename-cancel>Annuler</button>
        </form>
    </div>

    <div class="cycle-order" aria-label="Ordre dans le cycle">
        @if($inCycle)
            <form action="{{ route('week-menus.admin.weeks.move', $w) }}" method="POST" data-no-loading>
                @csrf
                @method('PATCH')
                <input type="hidden" name="direction" value="up">
                <button type="submit" class="btn" title="Monter" aria-label="Monter « {{ $w->name }} »" {{ $isFirst ? 'disabled' : '' }}>
                    <i class="ki-duotone ki-up fs-3"><i class="path1"></i></i>
                </button>
            </form>
            <form action="{{ route('week-menus.admin.weeks.move', $w) }}" method="POST" data-no-loading>
                @csrf
                @method('PATCH')
                <input type="hidden" name="direction" value="down">
                <button type="submit" class="btn" title="Descendre" aria-label="Descendre « {{ $w->name }} »" {{ $isLast ? 'disabled' : '' }}>
                    <i class="ki-duotone ki-down fs-3"><i class="path1"></i></i>
                </button>
            </form>
        @endif
    </div>

    <div class="cycle-actions">
        <a href="{{ route('week-menus.admin.edit', ['week' => $w->id]) }}" class="btn btn-sm btn-itcj-gold" title="Modifier les plats" aria-label="Modifier les plats de « {{ $w->name }} »">
            <i class="ki-duotone ki-pencil fs-4"><i class="path1"></i><i class="path2"></i></i>
        </a>

        <button type="button" class="btn btn-sm btn-ghost" title="Renommer" aria-label="Renommer « {{ $w->name }} »" data-rename>
            <i class="ki-duotone ki-text fs-4"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i></i>
        </button>

        <form action="{{ route('week-menus.admin.weeks.duplicate', $w) }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-sm btn-ghost" title="Dupliquer" aria-label="Dupliquer « {{ $w->name }} »">
                <i class="ki-duotone ki-copy fs-4"><i class="path1"></i><i class="path2"></i></i>
            </button>
        </form>

        @if(!$isActive)
            <form action="{{ route('week-menus.admin.active-week') }}" method="POST"
                  data-confirm="« {{ $w->name }} » remplacera « {{ $activeWeek?->name ?? 'la semaine actuelle' }} » sur le site et sur le tableau de bord."
                  data-confirm-title="Afficher « {{ $w->name }} » ?"
                  data-confirm-type="info"
                  data-confirm-ok="Afficher">
                @csrf
                @method('PATCH')
                <input type="hidden" name="week" value="{{ $w->id }}">
                <input type="hidden" name="return" value="weeks">
                <button type="submit" class="btn btn-sm btn-ghost" title="Afficher cette semaine" aria-label="Afficher « {{ $w->name }} » sur le site">
                    <i class="ki-duotone ki-rocket fs-4"><i class="path1"></i><i class="path2"></i></i>
                </button>
            </form>
        @endif

        <form action="{{ route('week-menus.admin.weeks.update', $w) }}" method="POST"
              @if($inCycle)
              data-confirm="« {{ $w->name }} » ne tournera plus dans le cycle{{ $isActive ? ', mais elle reste en ligne tant que vous n\'en affichez pas une autre' : '' }}. Vous pourrez la remettre dans le cycle a tout moment."
              data-confirm-title="Mettre « {{ $w->name }} » en reserve ?"
              data-confirm-type="warning"
              data-confirm-ok="Mettre en reserve"
              @endif>
            @csrf
            @method('PATCH')
            <input type="hidden" name="in_cycle" value="{{ $inCycle ? 0 : 1 }}">
            <button type="submit" class="btn btn-sm btn-ghost" title="{{ $inCycle ? 'Mettre en reserve' : 'Remettre dans le cycle' }}" aria-label="{{ $inCycle ? 'Mettre en reserve' : 'Remettre dans le cycle' }} « {{ $w->name }} »">
                @if($inCycle)
                    <i class="ki-duotone ki-archive fs-4"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                @else
                    <i class="ki-duotone ki-arrows-circle fs-4"><i class="path1"></i><i class="path2"></i></i>
                @endif
            </button>
        </form>

        <form action="{{ route('week-menus.admin.weeks.destroy', $w) }}" method="POST"
              data-confirm="Les {{ $w->slots_count }} plats de « {{ $w->name }} » seront supprimes definitivement.{{ $isActive ? ' Cette semaine est en ligne : la premiere semaine du cycle prendra sa place.' : '' }}"
              data-confirm-title="Supprimer « {{ $w->name }} » ?"
              data-confirm-type="danger"
              data-confirm-ok="Supprimer">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-light-danger" title="Supprimer" aria-label="Supprimer « {{ $w->name }} »">
                {!! $trashIcon !!}
            </button>
        </form>
    </div>
</div>
