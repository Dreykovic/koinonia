@extends('layouts.guest')

@section('title', 'Connexion')

@section('content')
<style>
    :root {
        --primary: #8B2332;
        --primary-dark: #6B1A27;
        --accent: #D4A84B;
        --accent-dark: #B08A3A;
        --bg-base: #F5F0E8;
        --bg-card: #F8F4ED;
        --bg-dark: #2C2420;
        --text-primary: #3D3028;
        --text-muted: #9A8B7D;
        --shadow-light: rgba(255, 255, 255, 0.8);
        --shadow-dark: rgba(60, 50, 40, 0.15);
        --neu-shadow: 6px 6px 12px var(--shadow-dark), -6px -6px 12px var(--shadow-light);
        --neu-shadow-inset: inset 4px 4px 8px var(--shadow-dark), inset -4px -4px 8px var(--shadow-light);
    }

    body {
        background: var(--bg-base);
    }

    .login-aside {
        background: var(--bg-dark);
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
        background: radial-gradient(circle, rgba(212, 168, 75, 0.1) 0%, transparent 50%);
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
        background: var(--primary);
        border: none;
        color: #fff;
        padding: 14px;
        font-weight: 600;
        border-radius: 12px;
        transition: all 0.2s ease;
        font-size: 16px;
        box-shadow: 3px 3px 6px var(--shadow-dark), -3px -3px 6px var(--shadow-light);
    }

    .btn-itcj-login:hover,
    .btn-itcj-login:active {
        background: var(--primary-dark);
        color: #fff;
        box-shadow: var(--neu-shadow);
        transform: translateY(-1px);
    }

    .form-control-itcj {
        background: var(--bg-card);
        border: none;
        border-radius: 12px;
        padding: 14px 16px;
        font-size: 16px;
        color: var(--text-primary);
        box-shadow: var(--neu-shadow-inset);
        transition: all 0.2s ease;
    }

    .form-control-itcj:focus {
        background: var(--bg-card);
        border: none;
        box-shadow: var(--neu-shadow-inset), 0 0 0 3px rgba(139, 35, 50, 0.15);
        outline: none;
        color: var(--text-primary);
    }

    .form-control-itcj::placeholder {
        color: var(--text-muted);
    }

    .link-itcj {
        color: var(--primary);
        text-decoration: none;
    }

    .link-itcj:hover {
        color: var(--primary-dark);
        text-decoration: underline;
    }

    .gold-text {
        color: var(--accent);
    }

    .login-card {
        background: var(--bg-card);
        border-radius: 20px;
        box-shadow: var(--neu-shadow);
        padding: 40px;
        max-width: 420px;
        width: 100%;
    }

    .form-label-itcj {
        font-weight: 500;
        color: var(--text-primary);
        margin-bottom: 8px;
        font-size: 14px;
    }

    /* Mobile Responsiveness */
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

        .form-control-itcj {
            padding: 12px 14px;
        }

        .btn-itcj-login {
            padding: 14px;
            font-size: 15px;
        }

        .form-label-itcj {
            font-size: 13px;
        }

        /* Better touch targets */
        .form-check-input {
            width: 20px;
            height: 20px;
        }
    }

    /* Safe area for iOS */
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
    <div class="d-flex flex-column flex-lg-row-fluid" style="background: var(--bg-base);">
        <div class="d-flex flex-center flex-column flex-grow-1 p-4 p-lg-5">
            <div class="login-card">
                <div class="text-center mb-6 mb-lg-8">
                    <h2 class="fw-bold mb-2" style="color: var(--text-primary);">Connexion</h2>
                    <p class="text-muted fs-6 mb-0">Entrez vos identifiants pour acceder a votre espace</p>
                </div>

                @if(session('status'))
                    <div class="alert alert-success mb-4" style="background: #e8f5e9; border: none; color: #2e7d32; border-radius: 8px; padding: 12px 16px;">
                        {{ session('status') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert mb-4" style="background: #F5E8EA; border: none; color: var(--itcj-red); border-radius: 8px; padding: 12px 16px;">
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="mb-4">
                        <label class="form-label-itcj">Email</label>
                        <input class="form-control form-control-itcj" type="email" name="email"
                            value="{{ old('email') }}" autocomplete="email" autofocus required
                            placeholder="votre.email@itcj.org" inputmode="email">
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label-itcj mb-0">Mot de passe</label>
                            <a href="{{ route('password.request') }}" class="link-itcj fs-7">
                                Mot de passe oublie ?
                            </a>
                        </div>
                        <input class="form-control form-control-itcj" type="password" name="password"
                            autocomplete="current-password" required placeholder="Votre mot de passe">
                    </div>

                    <div class="mb-5">
                        <label class="d-flex align-items-center cursor-pointer py-2">
                            <input type="checkbox" name="remember" class="form-check-input me-3"
                                {{ old('remember') ? 'checked' : '' }} style="border-color: #ccc; cursor: pointer;">
                            <span class="text-muted fs-7">Se souvenir de moi</span>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-itcj-login w-100 mb-4">
                        Se connecter
                    </button>

                    <div class="text-center">
                        <span class="text-muted fs-7">Pas encore de compte ?</span>
                        <a href="{{ route('account-request.create') }}" class="link-itcj fs-7 ms-1">Demander l'adhesion</a>
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
