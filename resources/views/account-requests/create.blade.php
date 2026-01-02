@extends('layouts.guest')

@section('title', 'Demande d\'adhesion')

@section('content')
<style>
    :root {
        --itcj-blue: #1E5AA8;
        --itcj-blue-dark: #164785;
        --itcj-red: #8B2332;
        --itcj-gold: #D4A84B;
        --itcj-dark: #1a1a2e;
    }

    .login-aside {
        background: var(--itcj-dark);
        position: relative;
        overflow: hidden;
    }

    .login-aside::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(139, 35, 50, 0.15) 0%, transparent 50%);
        animation: pulse 15s ease-in-out infinite;
    }

    @keyframes pulse {
        0%, 100% { transform: translate(0, 0); }
        50% { transform: translate(-5%, -5%); }
    }

    .login-aside-content {
        position: relative;
        z-index: 1;
    }

    .login-logo {
        width: 100px;
        height: 100px;
        border-radius: 16px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.3);
    }

    .btn-itcj-login {
        background: var(--itcj-blue);
        border: none;
        color: #fff;
        padding: 14px;
        font-weight: 600;
        border-radius: 8px;
        transition: background 0.2s;
        font-size: 16px;
    }

    .btn-itcj-login:hover,
    .btn-itcj-login:active {
        background: var(--itcj-blue-dark);
        color: #fff;
    }

    .form-control-itcj {
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 14px 16px;
        font-size: 16px;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .form-control-itcj:focus {
        border-color: var(--itcj-blue);
        box-shadow: 0 0 0 3px rgba(30, 90, 168, 0.15);
        outline: none;
    }

    .form-select-itcj {
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 14px 16px;
        font-size: 16px;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .form-select-itcj:focus {
        border-color: var(--itcj-blue);
        box-shadow: 0 0 0 3px rgba(30, 90, 168, 0.15);
        outline: none;
    }

    .link-itcj {
        color: var(--itcj-blue);
        text-decoration: none;
    }

    .link-itcj:hover {
        color: var(--itcj-blue-dark);
        text-decoration: underline;
    }

    .gold-text {
        color: var(--itcj-gold);
    }

    .login-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        padding: 40px;
        max-width: 480px;
        width: 100%;
    }

    .form-label-itcj {
        font-weight: 500;
        color: var(--itcj-dark);
        margin-bottom: 8px;
        font-size: 14px;
    }

    @media (max-width: 991px) {
        .login-aside {
            min-height: 200px;
            padding: 30px 20px;
        }

        .login-logo {
            width: 70px;
            height: 70px;
            border-radius: 12px;
        }

        .login-aside h1 {
            font-size: 24px !important;
        }

        .login-aside .gold-text {
            font-size: 14px !important;
            margin-bottom: 0 !important;
        }
    }

    @media (max-width: 576px) {
        .login-aside {
            min-height: 180px;
            padding: 24px 16px;
        }

        .login-logo {
            width: 60px;
            height: 60px;
            margin-bottom: 12px !important;
        }

        .login-aside h1 {
            font-size: 20px !important;
            margin-bottom: 8px !important;
        }

        .login-aside .gold-text {
            font-size: 12px !important;
        }

        .login-card {
            padding: 24px 20px;
            margin: 0 12px;
            border-radius: 12px;
        }

        .login-card h2 {
            font-size: 22px !important;
        }

        .login-card p {
            font-size: 14px !important;
        }

        .form-control-itcj,
        .form-select-itcj {
            padding: 12px 14px;
        }

        .btn-itcj-login {
            padding: 14px;
            font-size: 15px;
        }

        .form-label-itcj {
            font-size: 13px;
        }
    }

    @supports (padding-top: env(safe-area-inset-top)) {
        .login-aside {
            padding-top: calc(env(safe-area-inset-top) + 30px);
        }

        .d-flex.d-lg-none.flex-center {
            padding-bottom: calc(env(safe-area-inset-bottom) + 20px);
        }
    }
</style>

<div class="d-flex flex-column flex-lg-row flex-column-fluid min-vh-100">
    <!-- Left Panel / Top on mobile -->
    <div class="d-flex flex-column flex-lg-row-auto login-aside w-xl-550px">
        <div class="d-flex flex-column position-xl-fixed top-0 bottom-0 w-xl-550px login-aside-content">
            <div class="d-flex flex-column flex-center flex-grow-1 p-5 p-lg-10 text-center">
                <img src="{{ asset('assets/media/logos/itcj-logo.jpg') }}" alt="ITCJ" class="login-logo mb-3 mb-lg-5">

                <h1 class="text-white fw-bold fs-2x mb-2 mb-lg-3">ITCJ Community</h1>

                <p class="gold-text fs-5 fw-semibold mb-3 mb-lg-5">
                    Institut de Theologie de la Compagnie de Jesus
                </p>

                <div class="d-none d-lg-block">
                    <p class="text-white opacity-75 fs-6 mb-0">
                        Plateforme de gestion communautaire
                    </p>
                    <p class="text-white opacity-50 fs-7">
                        Vehicules - Repas - Maintenance - Communication
                    </p>
                </div>
            </div>

            <div class="d-none d-lg-flex flex-center p-10">
                <span class="text-white opacity-50 fs-8">&copy; {{ date('Y') }} ITCJ - AMDG</span>
            </div>
        </div>
    </div>

    <!-- Right Panel - Form -->
    <div class="d-flex flex-column flex-lg-row-fluid bg-light">
        <div class="d-flex flex-center flex-column flex-grow-1 p-4 p-lg-5">
            <div class="login-card">
                <div class="text-center mb-6 mb-lg-8">
                    <h2 class="fw-bold mb-2" style="color: var(--itcj-dark);">Demande d'adhesion</h2>
                    <p class="text-muted fs-6 mb-0">Remplissez ce formulaire pour rejoindre la communaute</p>
                </div>

                @if($errors->any())
                    <div class="alert mb-4" style="background: #F5E8EA; border: none; color: var(--itcj-red); border-radius: 8px; padding: 12px 16px;">
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('account-request.store') }}">
                    @csrf

                    <div class="mb-4">
                        <label class="form-label-itcj">Nom complet <span class="text-danger">*</span></label>
                        <input class="form-control form-control-itcj" type="text" name="name"
                            value="{{ old('name') }}" required
                            placeholder="Ex: Jean-Pierre MBALA">
                    </div>

                    <div class="mb-4">
                        <label class="form-label-itcj">Adresse email <span class="text-danger">*</span></label>
                        <input class="form-control form-control-itcj" type="email" name="email"
                            value="{{ old('email') }}" required
                            placeholder="votre.email@exemple.com" inputmode="email">
                    </div>

                    <div class="mb-4">
                        <label class="form-label-itcj">Telephone</label>
                        <input class="form-control form-control-itcj" type="tel" name="phone"
                            value="{{ old('phone') }}"
                            placeholder="+237 6XX XXX XXX" inputmode="tel">
                    </div>

                    <div class="mb-5">
                        <label class="form-label-itcj">Communaute</label>
                        <select class="form-select form-select-itcj" name="community_id">
                            <option value="">-- Selectionnez votre communaute --</option>
                            @foreach($communities as $community)
                                <option value="{{ $community->id }}" {{ old('community_id') == $community->id ? 'selected' : '' }}>
                                    {{ $community->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="btn btn-itcj-login w-100 mb-4">
                        Envoyer ma demande
                    </button>

                    <div class="text-center">
                        <span class="text-muted fs-7">Vous avez deja un compte ?</span>
                        <a href="{{ route('login') }}" class="link-itcj fs-7 ms-1">Se connecter</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="d-flex d-lg-none flex-center p-4">
            <span class="text-muted fs-8">&copy; {{ date('Y') }} ITCJ Community</span>
        </div>
    </div>
</div>
@endsection







