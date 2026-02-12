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
<style>
    /* Mobile members optimizations */
    @media (max-width: 768px) {
        .table-responsive {
            display: none;
        }

        .members-mobile-list {
            display: block;
        }
    }

    @media (min-width: 769px) {
        .members-mobile-list {
            display: none;
        }
    }

    /* Mobile member cards */
    .member-mobile-card {
        background: var(--bg-card);
        border-radius: 12px;
        padding: 14px;
        margin-bottom: 12px;
        box-shadow: var(--neu-shadow-sm);
    }

    .member-mobile-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 10px;
    }

    .member-mobile-header .symbol {
        flex-shrink: 0;
    }

    .member-mobile-header .symbol-label {
        width: 40px;
        height: 40px;
        font-size: 14px;
    }

    .member-mobile-info {
        flex: 1;
        min-width: 0;
    }

    .member-mobile-name {
        font-size: 15px;
        font-weight: 600;
        color: var(--text-primary);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .member-mobile-email {
        font-size: 12px;
        color: var(--text-muted);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .member-mobile-room {
        font-size: 11px;
        color: var(--text-muted);
    }

    .member-mobile-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 10px;
    }

    .member-mobile-actions {
        display: flex;
        gap: 8px;
        padding-top: 10px;
        border-top: 1px solid rgba(60, 48, 40, 0.08);
    }

    .member-mobile-actions .btn {
        flex: 1;
    }

    .member-mobile-actions form {
        flex: 1;
    }

    .member-mobile-actions form .btn {
        width: 100%;
    }
</style>
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

                                    <form class="flex-grow-1" action="{{ route('week-menus.admin.update',$menu) }}" method="POST">
                                        @csrf
                                            <div class="d-flex flex-col justify-content-between    align-items-center gap-2 " >
                                                <div class="flex-grow-1 ">
                                                    <input type="text" id="dish_name{{ $menu->id }}" name="dish_name" 
                                                        value="{{ $menu->dish_name }}" class="form-control form-control-lg w-100 form-control-solid @error('dish_name') is-invalid @enderror" required {{ $menu->available ? "" :'disabled' }}>
                                                    @error('dish_name')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                              
                                                    <button type="submit" class="btn btn-icon btn-sm  btn-itcj-gold" title="enregistrer"
                                                       >
                                                        <i class="ki-duotone ki-check fs-4"><i class="path1"></i><i class="path2"></i></i>
                                                    </button>
                                                
                                        </div>
                                    </form>
                                    <form class="d-flex  justify-content-center align-items-center" action="{{ route('week-menus.admin.toggle', $menu) }}" method="POST">
                                        @csrf
                                        
                                        @method('PATCH')

                                        @if($menu->available )
                                            <button type="submit" class="btn btn-icon btn-sm btn-itcj-red" title="cacher">
                                                <i class="ki-duotone ki-eye-slash fs-4"><i class="path1"></i><i class="path2"></i></i>
                                            </button>
                                        @else
                                            <button type="submit" class="btn btn-icon btn-sm btn-light-primary" title="Activer"
                                   >
                                         <i class="ki-duotone ki-eye fs-4"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
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

                                        <form class="flex-grow-1" action="{{ route('week-menus.admin.update',$menu) }}" method="POST">
                                            @csrf
                                                <div class="d-flex flex-col justify-content-between    align-items-center gap-2 " >
                                                    <div class="flex-grow-1 ">
                                                    
                                                        <textarea type="text" id="dish_name{{ $menu->id }}" name="dish_name" 
                                                            class="form-control     form-control-solid @error('dish_name') is-invalid @enderror" required {{ $menu->available ? "" :'disabled' }}>{{ $menu->dish_name }}
                                                        </textarea>
                                                        @error('dish_name')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                
                                                    <button type="submit" class="btn btn-icon btn-sm  btn-itcj-gold" title="enregistrer"
                                                        >
                                                            <i class="ki-duotone ki-check fs-5"><i class="path1"></i><i class="path2"></i></i>
                                                     </button>
                                                    
                                            </div>
                                        </form>
                                        <form class="d-flex  justify-content-center align-items-center" action="{{ route('week-menus.admin.toggle', $menu) }}" method="POST">
                                            @csrf
                                            
                                            @method('PATCH')

                                            @if($menu->available )
                                                <button type="submit" class="btn btn-icon btn-sm btn-itcj-red" title="cacher">
                                                    <i class="ki-duotone ki-eye-slash fs-4"><i class="path1"></i><i class="path2"></i></i>
                                                </button>
                                            @else
                                                <button type="submit" class="btn btn-icon btn-sm btn-light-primary" title="Activer"
                                    >
                                            <i class="ki-duotone ki-eye fs-4"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
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
