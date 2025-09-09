@extends('layouts.app')

@section('title', 'Emails Echoues')
@section('page-title', 'Emails Echoues')

@section('breadcrumb')
<li class="breadcrumb-item text-muted">Administration</li>
<li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
<li class="breadcrumb-item text-muted">Emails echoues</li>
@endsection

@push('styles')
<style>
    /* Mobile optimizations for failed emails */
    @media (max-width: 768px) {
        .table-responsive {
            display: none;
        }

        .failed-emails-mobile {
            display: block;
        }
    }

    @media (min-width: 769px) {
        .failed-emails-mobile {
            display: none;
        }
    }

    /* Mobile failed email cards */
    .fe-mobile-card {
        background: var(--bg-card);
        border-radius: 12px;
        padding: 14px;
        margin-bottom: 12px;
        box-shadow: var(--neu-shadow-sm);
    }

    .fe-mobile-card.resolved {
        opacity: 0.7;
    }

    .fe-mobile-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 10px;
    }

    .fe-mobile-date {
        font-size: 12px;
        color: var(--text-muted);
    }

    .fe-mobile-recipient {
        font-size: 14px;
        font-weight: 600;
        color: var(--text-primary);
        word-break: break-all;
    }

    .fe-mobile-type {
        font-size: 12px;
        color: var(--text-muted);
        margin-top: 4px;
    }

    .fe-mobile-error {
        font-size: 12px;
        color: var(--itcj-red);
        background: var(--itcj-red-light);
        padding: 8px;
        border-radius: 8px;
        margin: 10px 0;
        word-break: break-word;
    }

    .fe-mobile-actions {
        display: flex;
        gap: 8px;
        padding-top: 10px;
        border-top: 1px solid rgba(60, 48, 40, 0.08);
    }

    .fe-mobile-actions .btn {
        flex: 1;
    }

    .fe-mobile-actions form {
        flex: 1;
    }

    .fe-mobile-actions form .btn {
        width: 100%;
    }

    /* Filter pills */
    .status-filters {
        display: flex;
        gap: 8px;
        margin-bottom: 16px;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        padding-bottom: 4px;
    }

    .status-filter {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 500;
        text-decoration: none;
        white-space: nowrap;
        transition: all 0.2s ease;
        background: var(--bg-card);
        color: var(--text-primary);
        box-shadow: var(--neu-shadow-sm);
    }

    .status-filter:hover {
        background: var(--bg-base);
    }

    .status-filter.active {
        background: var(--primary);
        color: #fff;
    }

    .status-filter .count {
        background: rgba(255, 255, 255, 0.2);
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 11px;
    }

    .status-filter:not(.active) .count {
        background: rgba(60, 48, 40, 0.1);
    }
</style>
@endpush

@section('content')
<div class="card">
    <div class="card-header py-4">
        <div>
            <h3 class="card-title fw-bold mb-0">
                <i class="ki-duotone ki-sms fs-3 text-itcj-red me-2"><i class="path1"></i><i class="path2"></i></i>
                Emails echoues
            </h3>
            <span class="text-muted fs-7">Suivi des emails qui n'ont pas pu etre envoyes</span>
        </div>
    </div>
    <div class="card-body pt-0">
        @if(session('success'))
            <div class="alert alert-success mb-5">{{ session('success') }}</div>
        @endif

        <!-- Status Filters -->
        <div class="status-filters">
            <a href="{{ route('admin.failed-emails.index', ['status' => 'unresolved']) }}"
               class="status-filter {{ $status === 'unresolved' ? 'active' : '' }}">
                <i class="ki-duotone ki-cross-circle fs-5"><i class="path1"></i><i class="path2"></i></i>
                Non resolus
                <span class="count">{{ $counts['unresolved'] }}</span>
            </a>
            <a href="{{ route('admin.failed-emails.index', ['status' => 'resolved']) }}"
               class="status-filter {{ $status === 'resolved' ? 'active' : '' }}">
                <i class="ki-duotone ki-check-circle fs-5"><i class="path1"></i><i class="path2"></i></i>
                Resolus
                <span class="count">{{ $counts['resolved'] }}</span>
            </a>
            <a href="{{ route('admin.failed-emails.index', ['status' => 'all']) }}"
               class="status-filter {{ $status === 'all' ? 'active' : '' }}">
                <i class="ki-duotone ki-element-11 fs-5"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i></i>
                Tous
                <span class="count">{{ $counts['all'] }}</span>
            </a>
        </div>

        <!-- Desktop Table -->
        <div class="table-responsive">
            <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
                <thead>
                    <tr class="fw-bold text-muted">
                        <th class="min-w-100px">Date</th>
                        <th class="min-w-150px">Destinataire</th>
                        <th class="min-w-120px">Type</th>
                        <th class="min-w-200px">Erreur</th>
                        <th class="min-w-80px">Statut</th>
                        <th class="min-w-100px text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($failedEmails as $email)
                    <tr class="{{ $email->is_resolved ? 'opacity-50' : '' }}">
                        <td>
                            <span class="text-dark fw-bold d-block">{{ $email->created_at->format('d/m/Y') }}</span>
                            <span class="text-muted fs-8">{{ $email->created_at->format('H:i') }}</span>
                        </td>
                        <td>
                            <span class="text-dark">{{ $email->recipient_email }}</span>
                            @if($email->recipient_name)
                                <span class="text-muted d-block fs-8">{{ $email->recipient_name }}</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-light-primary fs-8">{{ $email->mailable_name }}</span>
                        </td>
                        <td>
                            <span class="text-danger fs-8" title="{{ $email->error_message }}">
                                {{ Str::limit($email->error_message, 80) }}
                            </span>
                        </td>
                        <td>
                            @if($email->is_resolved)
                                <span class="badge fs-8" style="background: #e8f5e9; color: #2e7d32;">Resolu</span>
                            @else
                                <span class="badge badge-light-danger fs-8">En attente</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-2">
                                @if(!$email->is_resolved)
                                    <form action="{{ route('admin.failed-emails.resolve', $email) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-icon btn-sm btn-light-success" title="Marquer comme resolu">
                                            <i class="ki-duotone ki-check fs-4"><i class="path1"></i><i class="path2"></i></i>
                                        </button>
                                    </form>
                                @endif
                                <form action="{{ route('admin.failed-emails.destroy', $email) }}" method="POST" class="d-inline"
                                      data-confirm="Supprimer cette entrée ?"
                                      data-confirm-title="Supprimer l'entrée"
                                      data-confirm-ok="Supprimer">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-icon btn-sm btn-light-danger" title="Supprimer">
                                        <i class="ki-duotone ki-trash fs-4"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i><i class="path5"></i></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-10">
                            <div class="text-muted">
                                <i class="ki-duotone ki-check-circle fs-3x text-success mb-3"><i class="path1"></i><i class="path2"></i></i>
                                <p class="mb-0">Aucun email echoue {{ $status === 'unresolved' ? 'en attente' : '' }}</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile Cards -->
        <div class="failed-emails-mobile">
            @forelse($failedEmails as $email)
                <div class="fe-mobile-card {{ $email->is_resolved ? 'resolved' : '' }}">
                    <div class="fe-mobile-header">
                        <div>
                            <div class="fe-mobile-date">{{ $email->created_at->format('d/m/Y H:i') }}</div>
                            <div class="fe-mobile-recipient">{{ $email->recipient_email }}</div>
                            <div class="fe-mobile-type">{{ $email->mailable_name }}</div>
                        </div>
                        @if($email->is_resolved)
                            <span class="badge fs-8" style="background: #e8f5e9; color: #2e7d32;">Resolu</span>
                        @else
                            <span class="badge badge-light-danger fs-8">En attente</span>
                        @endif
                    </div>
                    <div class="fe-mobile-error">
                        {{ Str::limit($email->error_message, 150) }}
                    </div>
                    <div class="fe-mobile-actions">
                        @if(!$email->is_resolved)
                            <form action="{{ route('admin.failed-emails.resolve', $email) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-light-success">
                                    <i class="ki-duotone ki-check fs-5"><i class="path1"></i><i class="path2"></i></i>
                                    Resoudre
                                </button>
                            </form>
                        @endif
                        <form action="{{ route('admin.failed-emails.destroy', $email) }}" method="POST"
                              data-confirm="Supprimer cette entrée ?"
                              data-confirm-title="Supprimer l'entrée"
                              data-confirm-ok="Supprimer">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-light-danger">
                                <i class="ki-duotone ki-trash fs-5"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i><i class="path5"></i></i>
                                Supprimer
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="mobile-empty-state">
                    <div class="mobile-empty-state-icon">
                        <i class="ki-duotone ki-check-circle" style="color: #2e7d32;"><i class="path1"></i><i class="path2"></i></i>
                    </div>
                    <div class="mobile-empty-state-title">Aucun email echoue</div>
                    <div class="mobile-empty-state-text">
                        {{ $status === 'unresolved' ? 'Tous les emails ont ete envoyes avec succes' : 'Aucune entree trouvee' }}
                    </div>
                </div>
            @endforelse
        </div>

        @if($failedEmails->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $failedEmails->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
