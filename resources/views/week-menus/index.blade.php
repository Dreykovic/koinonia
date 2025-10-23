@extends('layouts.app')

@section('title', 'Menus')
@section('page-title', 'Menu de la semaine')

@section('breadcrumb')
<li class="breadcrumb-item text-muted">Menu</li>
@endsection

@section('page-actions')
@if(auth()->user()->isAdmin())
<a href="{{ route('week-menus.admin.edit') }}" class="btn btn-sm btn-itcj desktop-add-btn">
    <i class="ki-duotone ki-plus fs-2"></i> Modifier Menu
</a>
@endif

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
                             PERIODE
                        </th>
                        @foreach ($menusPerPeriods->take(1) as $menuCollection)
                            @foreach ($menuCollection as $menu)
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
                                $role = $roleColors[$menu->day->code] ?? $roleColors['lundi'];
                            @endphp
                                <th  class="bg-light text-uppercase">
                                    <span class="badge fs-8" style="background: {{ $role['bg'] }}; color: {{ $role['text'] }};">
                                         {{ $menu->day->code }}                           
                                    </span>
                                </th>
                            @endforeach
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                
                    @foreach($menusPerPeriods as $period => $menuCollection)
                         
                    <tr>
                        <td class="text-muted align-top">{{ $period}}</td>
                        @foreach ($menuCollection as  $menu)
                            <td class="align-top">        
                                @if($menu->available )
                                    <span class="fw-bold text-start" style="color: var(--itcj-dark);">
                                        {{ $menu->dish_name }}
                                    </span>
                                @else
                                    <span class="" style="color: var(--itcj-dark);">
                                        NEANT
                                    </span>
                                @endif
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
                    <div class="member-mobile-header">
                        @foreach ($menuCollection as $menu)
                            
                                <div class="member-mobile-info">
                                    <div class="member-mobile-name"   style="background: {{ $role['bg'] }}; color: {{ $role['text'] }};">{{ $menu->period->code }}</div>
                                    @if($day)
                                        <div class="member-mobile-room"> {{ $menu->dish_name }}</div>
                                    @endif
                                </div>
                        @endforeach
                    </div>
                
             
                </div>
            @endforeach
        </div>

      
   
    </div>
</div>

<!-- FAB for mobile -->
<a href="{{ route('week-menus.admin.edit') }}" class="fab" aria-label="Modifier le menu">
    <i class="ki-duotone ki-pencil"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
</a>
@endsection



