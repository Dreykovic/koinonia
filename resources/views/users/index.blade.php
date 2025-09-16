@extends('layouts.app')

@section('title', 'Membres')
@section('page-title', 'Gestion des Membres')

@section('breadcrumb')
<li class="breadcrumb-item text-muted">Membres</li>
@endsection

@section('page-actions')
<a href="{{ route('users.create') }}" class="btn btn-sm btn-itcj desktop-add-btn">
    <i class="ki-duotone ki-plus fs-2"></i> Nouveau Membre
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
        <h3 class="card-title fw-bold mb-0">Liste des membres</h3>
        <span class="text-muted fs-7">Gestion des membres de la communaute</span>
    </div>
    <div class="card-body pt-0">
        <div class="table-responsive">
            <table class="table table-row-dashed align-middle gs-0 gy-4">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Communaute</th>
                        <th>Statut</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="symbol symbol-45px me-4">
                                    <span class="symbol-label bg-itcj-blue-light text-itcj-blue fw-bold fs-5">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </span>
                                </div>
                                <div>
                                    <span class="fw-bold" style="color: var(--itcj-dark);">{{ $user->name }}</span>
                                    @if($user->room_number)
                                        <span class="text-muted d-block fs-7">Chambre {{ $user->room_number }}</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="text-muted">{{ $user->email }}</td>
                        <td>
                            @php
                                $roleColors = [
                                    
                                    'admin' => ['bg' => 'var(--itcj-red-light)', 'text' => 'var(--itcj-red)'],
                                    'communication' => ['bg' => 'var(--itcj-gold-light)', 'text' => '#9a7a30'],
                                    'sante' => ['bg' => '#e8f5e9', 'text' => '#2e7d32'],
                                    'chauffeur' => ['bg' => '#e8f5e9', 'text' => '#2e7d82'],
                                    'formateur' => ['bg' => 'var(--itcj-blue-light)', 'text' => 'var(--itcj-blue)'],
                                    'membre' => ['bg' => '#f5f5f5', 'text' => '#616161'],
                                ];
                                $role = $roleColors[$user->role] ?? $roleColors['membre'];
                            @endphp
                            <span class="badge fs-8" style="background: {{ $role['bg'] }}; color: {{ $role['text'] }};">
                                {{ $user->role_label }}
                            </span>
                        </td>
                        <td class="text-muted">{{ $user->community_label }}</td>
                        <td>
                            @if($user->is_active)
                                <span class="badge fs-8" style="background: #e8f5e9; color: #2e7d32;">Actif</span>
                            @else
                                <span class="badge badge-itcj-red fs-8">Inactif</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('users.edit', $user) }}" class="btn btn-icon btn-sm btn-itcj-gold">
                                    <i class="ki-duotone ki-pencil fs-4"><i class="path1"></i><i class="path2"></i></i>
                                </a>
                                @if($user->id !== auth()->id())
                                <form action="{{ route('users.toggle-active', $user) }}" method="POST">
                                    @csrf
                                    @if($user->is_active)
                                    <button type="submit" class="btn btn-icon btn-sm btn-itcj-red" title="Desactiver">
                                        <i class="ki-duotone ki-cross fs-4"><i class="path1"></i><i class="path2"></i></i>
                                    </button>
                                    @else
                                    <button type="submit" class="btn btn-icon btn-sm" title="Activer"
                                        style="background: #e8f5e9; color: #2e7d32;">
                                        <i class="ki-duotone ki-check fs-4"><i class="path1"></i><i class="path2"></i></i>
                                    </button>
                                    @endif
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Mobile Cards -->
        <div class="members-mobile-list">
            @foreach($users as $user)
                @php
                    $roleColors = [
                        'admin' => ['bg' => 'var(--itcj-red-light)', 'text' => 'var(--itcj-red)'],
                        'communication' => ['bg' => 'var(--itcj-gold-light)', 'text' => '#9a7a30'],
                        'sante' => ['bg' => '#e8f5e9', 'text' => '#2e7d32'],
                        'chauffeur' => ['bg' => '#e8f5e9', 'text' => '#2e7d82'],
                        'formateur' => ['bg' => 'var(--itcj-blue-light)', 'text' => 'var(--itcj-blue)'],
                        'membre' => ['bg' => '#f5f5f5', 'text' => '#616161'],
                    ];
                    $role = $roleColors[$user->role] ?? $roleColors['membre'];
                @endphp
                <div class="member-mobile-card">
                    <div class="member-mobile-header">
                        <div class="symbol symbol-40px">
                            <span class="symbol-label bg-itcj-blue-light text-itcj-blue fw-bold">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </span>
                        </div>
                        <div class="member-mobile-info">
                            <div class="member-mobile-name">{{ $user->name }}</div>
                            <div class="member-mobile-email">{{ $user->email }}</div>
                            @if($user->room_number)
                                <div class="member-mobile-room">Chambre {{ $user->room_number }}</div>
                            @endif
                        </div>
                        @if($user->is_active)
                            <span class="badge fs-8" style="background: #e8f5e9; color: #2e7d32;">Actif</span>
                        @else
                            <span class="badge badge-itcj-red fs-8">Inactif</span>
                        @endif
                    </div>
                    <div class="member-mobile-meta">
                        <span class="badge fs-8" style="background: {{ $role['bg'] }}; color: {{ $role['text'] }};">
                            {{ $user->role_label }}
                        </span>
                        @if($user->community)
                            <span class="badge badge-light-primary fs-8">{{ $user->community->name }}</span>
                        @endif
                    </div>
                    <div class="member-mobile-actions">
                        <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-itcj-gold">
                            <i class="ki-duotone ki-pencil fs-5"><i class="path1"></i><i class="path2"></i></i>
                            Modifier
                        </a>
                        @if($user->id !== auth()->id())
                            <form action="{{ route('users.toggle-active', $user) }}" method="POST">
                                @csrf
                                @if($user->is_active)
                                    <button type="submit" class="btn btn-sm btn-itcj-red">
                                        <i class="ki-duotone ki-cross fs-5"><i class="path1"></i><i class="path2"></i></i>
                                        Désactiver
                                    </button>
                                @else
                                    <button type="submit" class="btn btn-sm" style="background: #e8f5e9; color: #2e7d32;">
                                        <i class="ki-duotone ki-check fs-5"><i class="path1"></i><i class="path2"></i></i>
                                        Activer
                                    </button>
                                @endif
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        @if($users->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $users->links() }}
        </div>
        @endif
    </div>
</div>

<!-- FAB for mobile -->
<a href="{{ route('users.create') }}" class="fab" aria-label="Nouveau membre">
    <i class="ki-duotone ki-plus"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
</a>
@endsection
