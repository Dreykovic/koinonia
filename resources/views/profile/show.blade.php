@extends('layouts.app')

@section('title', 'Mon Profil')
@section('page-title', 'Mon Profil')

@section('breadcrumb')
<li class="breadcrumb-item text-muted">Profil</li>
@endsection

@push('styles')
<style>
    /* Mobile profile optimizations */
    @media (max-width: 576px) {
        /* Avatar card */
        .col-xl-4 .card-body {
            padding: 1.5rem 1rem;
        }

        .symbol-150px {
            width: 100px !important;
            height: 100px !important;
        }

        .symbol-150px .symbol-label {
            width: 100px !important;
            height: 100px !important;
            font-size: 2rem !important;
        }

        .col-xl-4 h3 {
            font-size: 1.25rem;
        }

        .col-xl-4 .btn {
            width: 100%;
            min-height: 48px;
        }

        /* Info card */
        .col-xl-8 .card-header {
            padding: 1rem;
        }

        .col-xl-8 .card-header h3 {
            font-size: 1rem;
        }

        .col-xl-8 .card-body {
            padding: 1rem;
        }

        .col-xl-8 .row.mb-5,
        .col-xl-8 .row {
            flex-direction: column;
            gap: 0.25rem;
        }

        .col-xl-8 .col-lg-4 {
            width: 100%;
            margin-bottom: 0;
        }

        .col-xl-8 .col-lg-4 label {
            font-size: 0.8rem;
        }

        .col-xl-8 .col-lg-8 {
            width: 100%;
        }

        .col-xl-8 .col-lg-8 span {
            font-size: 0.95rem;
        }

        .separator.my-4 {
            margin: 0.75rem 0 !important;
        }
    }
</style>
@endpush

@section('content')
<div class="row g-5 g-xl-10">
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-body text-center pt-10 pb-8">
                <div class="symbol symbol-150px mb-6">
                    <span class="symbol-label bg-itcj-blue text-white fs-2hx fw-bold">
                        {{ strtoupper(substr($user->name, 0, 2)) }}
                    </span>
                </div>
                <h3 class="fw-bold mb-2" style="color: var(--itcj-dark);">
                    @if($user->title_prefix)
                        <span class="text-muted">{{ $user->title_prefix }}</span>
                    @endif
                    {{ $user->name }}
                </h3>
                <div class="mb-6">
                    @php
                        $profileColors = [
                            'administrateur' => ['bg' => 'var(--itcj-red-light)', 'text' => 'var(--itcj-red)'],
                            'chauffeur' => ['bg' => 'var(--itcj-gold-light)', 'text' => '#9a7a30'],
                            'sante' => ['bg' => '#e8f5e9', 'text' => '#2e7d32'],
                            'formateur' => ['bg' => 'var(--itcj-blue-light)', 'text' => 'var(--itcj-blue)'],
                            'etudiant' => ['bg' => '#f5f5f5', 'text' => '#616161'],
                        ];
                        $profile = $profileColors[$user->profile ? $user->profile->name :'Profile Non Defini' ] ?? $profileColors['etudiant'];
                    @endphp
                    <span class="badge fs-7 px-4 py-2" style="background: {{ $profile['bg'] }}; color: {{ $profile['text'] }};">
                        {{ $user->profile ? $user->profile->name :'Profile Non Defini' }}
                    </span>
                </div>
                <a href="{{ route('profile.edit') }}" class="btn btn-itcj">
                    <i class="ki-duotone ki-pencil fs-4 me-1"><i class="path1"></i><i class="path2"></i></i>
                    Modifier mon profil
                </a>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header py-4">
                <h3 class="card-title fw-bold mb-0">
                    <i class="ki-duotone ki-user fs-3 text-itcj-blue me-2"><i class="path1"></i><i class="path2"></i></i>
                    Informations personnelles
                </h3>
            </div>
            <div class="card-body">
                <div class="row mb-5">
                    <label class="col-lg-4 fw-semibold text-muted">
                        <i class="ki-duotone ki-sms fs-5 me-2"><i class="path1"></i><i class="path2"></i></i>
                        Email
                    </label>
                    <div class="col-lg-8">
                        <span class="fw-bold" style="color: var(--itcj-dark);">{{ $user->email }}</span>
                    </div>
                </div>

                <div class="separator my-4"></div>

                <div class="row mb-5">
                    <label class="col-lg-4 fw-semibold text-muted">
                        <i class="ki-duotone ki-phone fs-5 me-2"><i class="path1"></i><i class="path2"></i></i>
                        Telephone
                    </label>
                    <div class="col-lg-8">
                        <span class="fw-bold" style="color: var(--itcj-dark);">{{ $user->phone ?? '-' }}</span>
                    </div>
                </div>

                <div class="separator my-4"></div>

                <div class="row mb-5">
                    <label class="col-lg-4 fw-semibold text-muted">
                        <i class="ki-duotone ki-home-2 fs-5 me-2"><i class="path1"></i><i class="path2"></i></i>
                        Communaute
                    </label>
                    <div class="col-lg-8">
                        <span class="fw-bold" style="color: var(--itcj-dark);">{{ $user->community->name ?? '-' }}</span>
                    </div>
                </div>

                <div class="separator my-4"></div>

                <div class="row mb-5">
                    <label class="col-lg-4 fw-semibold text-muted">
                        <i class="ki-duotone ki-home fs-5 me-2"><i class="path1"></i><i class="path2"></i></i>
                        Numero de chambre
                    </label>
                    <div class="col-lg-8">
                        <span class="fw-bold" style="color: var(--itcj-dark);">{{ $user->room_number ?? '-' }}</span>
                    </div>
                </div>

                <div class="separator my-4"></div>

                <div class="row">
                    <label class="col-lg-4 fw-semibold text-muted">
                        <i class="ki-duotone ki-calendar fs-5 me-2"><i class="path1"></i><i class="path2"></i></i>
                        Membre depuis
                    </label>
                    <div class="col-lg-8">
                        <span class="fw-bold" style="color: var(--itcj-dark);">{{ $user->created_at->format('d/m/Y') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


