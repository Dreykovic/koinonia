@extends('layouts.app')

@section('title', 'Menus')
@section('page-title', 'Menu de la semaine')

@section('breadcrumb')
<li class="breadcrumb-item text-muted">Menu</li>
@endsection

@section('page-actions')
<a href="{{ route('week-menus.index') }}" class="btn btn-sm btn-itcj desktop-add-btn">
    <i class="ki-duotone ki-coffee fs-2"></i> Retourner a l'affichage
</a>
@endsection

@push('styles')
@include('week-menus._shared-styles')
@endpush

@section('content')
<div class="card">
    <div class="card-header py-4">
        <h3 class="card-title fw-bold mb-0">Menu de la semaine</h3>
    </div>
    <div class="card-body pt-0">
        <div class="table-responsive">
            <table class="table table-row-dashed align-middle gs-0 gy-4">
                <thead>
                    <tr>
                        <th  class="bg-light text-uppercase">
                             Jours
                        </th>

                        <th  class="bg-light text-uppercase">
                                    MIDI
                        </th>
                        <th  class="bg-light text-uppercase">
                                    SOIR
                        </th>

                    </tr>
                </thead>
                <tbody>

                    @foreach($menusPerDays as $day => $menuCollection)
                         @php
                                $roleColors = [

                                    'lundi' => ['bg' => 'var(--itcj-red-light)', 'text' => 'var(--itcj-red)'],
                                    'mardi' => ['bg' => 'var(--itcj-gold-light)', 'text' => '#9a7a30'],
                                    'mercredi' => ['bg' => '#e8f5e9', 'text' => '#2e7d32'],
                                    'jeudi' => ['bg' => '#e8f5e9', 'text' => '#2e7d82'],
                                    'vendredi' => ['bg' => 'var(--itcj-blue-light)', 'text' => 'var(--itcj-blue)'],
                                    'samedi' => ['bg' => '#f5f5f5', 'text' => '#616161'],
                                    'dimanche' => ['bg' => '#f5f5f5', 'text' => '#5151a1'],
                                ];
                                $role = $roleColors[$day] ?? $roleColors['lundi'];
                          @endphp

                    <tr>


                        <td class="bg-light text-uppercase align-middle d-flex justify-content-center">
                            <div class="symbol symbol-40px">
                                <span class="symbol-label  fw-bold"  style="background: {{ $role['bg'] }}; color: {{ $role['text'] }};">
                                    {{ strtoupper(substr($day, 0, 3)) }}
                                </span>
                            </div>

                        </td>

                        @foreach ($menuCollection as  $menu)

                            <td class="align-middle">

                                <div class=" d-flex flex-col justify-content-between align-items-center gap-2">

                                    <form class="flex-grow-1 menu-form" action="{{ route('week-menus.admin.update',$menu) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                            <div class="d-flex flex-col justify-content-between    align-items-center gap-2 " >
                                                <div class="flex-grow-1 ">
                                                    <input type="text" id="dish_name{{ $menu->id }}" name="dish_name"
                                                        value="{{ $menu->dish_name }}" class="form-control form-control-lg w-100 form-control-solid @error('dish_name') is-invalid @enderror" {{ !$menu->available ? 'disabled' : '' }} style="{{ !$menu->available ? 'opacity: 0.5;' : '' }}" required>
                                                    @error('dish_name')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                    <button type="submit" class="btn btn-icon btn-sm btn-itcj-gold" aria-label="Enregistrer"
                                                        {{ !$menu->available ? 'disabled' : '' }}>
                                                        <i class="ki-duotone ki-check fs-4"><i class="path1"></i><i class="path2"></i></i>
                                                    </button>

                                        </div>
                                    </form>
                                    <form class="d-flex  justify-content-center align-items-center" action="{{ route('week-menus.admin.toggle', $menu) }}" method="POST" onsubmit="return confirm('Modifier la visibilite de ce menu ?')">
                                        @csrf

                                        @method('PATCH')

                                        @if($menu->available)
                                            <button type="submit" class="btn btn-icon btn-sm btn-light-success" title="Masquer ce menu" aria-label="Masquer le menu">
                                                <i class="ki-duotone ki-eye fs-4"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                                            </button>
                                        @else
                                            <button type="submit" class="btn btn-icon btn-sm btn-light-danger" title="Rendre visible ce menu" aria-label="Rendre visible le menu">
                                                <i class="ki-duotone ki-eye-slash fs-4"><i class="path1"></i><i class="path2"></i></i>
                                            </button>
                                        @endif
                                    </form>
                                </div>
                            </td>
                        @endforeach

                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <!-- Mobile Cards -->
        <div class="members-mobile-list">
            @foreach($menusPerDays as $day => $menuCollection)
                 @php
                                $roleColors = [

                                    'lundi' => ['bg' => 'var(--itcj-red-light)', 'text' => 'var(--itcj-red)'],
                                    'mardi' => ['bg' => 'var(--itcj-gold-light)', 'text' => '#9a7a30'],
                                    'mercredi' => ['bg' => '#e8f5e9', 'text' => '#2e7d32'],
                                    'jeudi' => ['bg' => '#e8f5e9', 'text' => '#2e7d82'],
                                    'vendredi' => ['bg' => 'var(--itcj-blue-light)', 'text' => 'var(--itcj-blue)'],
                                    'samedi' => ['bg' => '#f5f5f5', 'text' => '#616161'],
                                    'dimanche' => ['bg' => '#f5f5f5', 'text' => '#5151a1'],
                                ];
                                $role = $roleColors[$day] ?? $roleColors['lundi'];
                            @endphp
                <div class="member-mobile-card">
                    <div class="member-mobile-header">
                        <div class="symbol symbol-40px">
                            <span class="symbol-label  fw-bold"  style="background: {{ $role['bg'] }}; color: {{ $role['text'] }};">
                                {{ strtoupper(substr($day, 0, 3)) }}
                            </span>
                        </div>
                        <div class="member-mobile-info">
                            <div class="member-mobile-name"  style="background: {{ $role['bg'] }}; color: {{ $role['text'] }};">{{ $day }}</div>
                        </div>


                    </div>

                        @foreach ($menuCollection as $menu)

                                <div class=" gap-2">
                                    <div class="d-flex   justify-content-between    align-items-center "   style="background: {{ $role['bg'] }}; color: {{ $role['text'] }};">{{ $menu->period->code }}</div>
                                    <div class=" d-flex flex-col justify-content-between align-items-center gap-2">

                                        <form class="flex-grow-1 menu-form" action="{{ route('week-menus.admin.update',$menu) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                                <div class="d-flex flex-col justify-content-between    align-items-center gap-2 " >
                                                    <div class="flex-grow-1 ">

                                                        <textarea id="dish_name{{ $menu->id }}" name="dish_name"
                                                            class="form-control form-control-solid @error('dish_name') is-invalid @enderror" {{ !$menu->available ? 'disabled' : '' }} style="{{ !$menu->available ? 'opacity: 0.5;' : '' }}" required>{{ $menu->dish_name }}</textarea>
                                                        @error('dish_name')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </div>

                                                    <button type="submit" class="btn btn-icon btn-sm btn-itcj-gold" aria-label="Enregistrer" {{ !$menu->available ? 'disabled' : '' }}>
                                                            <i class="ki-duotone ki-check fs-5"><i class="path1"></i><i class="path2"></i></i>
                                                     </button>

                                            </div>
                                        </form>
                                        <form class="d-flex  justify-content-center align-items-center" action="{{ route('week-menus.admin.toggle', $menu) }}" method="POST" onsubmit="return confirm('Modifier la visibilite de ce menu ?')">
                                            @csrf

                                            @method('PATCH')

                                            @if($menu->available)
                                                <button type="submit" class="btn btn-icon btn-sm btn-light-success" title="Masquer ce menu" aria-label="Masquer le menu">
                                                    <i class="ki-duotone ki-eye fs-4"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                                                </button>
                                            @else
                                                <button type="submit" class="btn btn-icon btn-sm btn-light-danger" title="Rendre visible ce menu" aria-label="Rendre visible le menu">
                                                    <i class="ki-duotone ki-eye-slash fs-4"><i class="path1"></i><i class="path2"></i></i>
                                                </button>
                                            @endif
                                        </form>
                                    </div>
                                </div>
                        @endforeach



                </div>
            @endforeach
        </div>



    </div>
</div>

<!-- FAB for mobile -->
<a href="{{ route('week-menus.index') }}" class="fab" aria-label="Retourner a l'affichage">
    <i class="ki-duotone ki-coffee"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
</a>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.menu-form').forEach(function(form) {
        form.addEventListener('submit', function() {
            var btn = form.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        });
    });
});
</script>
@endpush


