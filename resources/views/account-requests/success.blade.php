@extends('layouts.guest')

@section('title', 'Demande d\'adhesion envoyee')

@section('content')
<style>
    :root {
        --itcj-blue: #1E5AA8;
        --itcj-blue-dark: #164785;
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

    .gold-text {
        color: var(--itcj-gold);
    }

    .success-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        padding: 40px;
        max-width: 480px;
        width: 100%;
        text-align: center;
    }

    .success-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: #e8f5e9;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 24px;
    }

    .success-icon svg {
        width: 40px;
        height: 40px;
        color: #2e7d32;
    }

    .link-itcj {
        color: var(--itcj-blue);
        text-decoration: none;
    }

    .link-itcj:hover {
        color: var(--itcj-blue-dark);
        text-decoration: underline;
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
    }

    @media (max-width: 576px) {
        .login-aside {
            min-height: 180px;
            padding: 24px 16px;
        }

        .login-logo {
            width: 60px;
            height: 60px;
        }

        .success-card {
            padding: 24px 20px;
            margin: 0 12px;
        }

        .success-icon {
            width: 60px;
            height: 60px;
        }

        .success-icon svg {
            width: 30px;
            height: 30px;
        }
    }
</style>

<div class="d-flex flex-column flex-lg-row flex-column-fluid min-vh-100">
    <!-- Left Panel -->
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
                </div>
            </div>

            <div class="d-none d-lg-flex flex-center p-10">
                <span class="text-white opacity-50 fs-8">&copy; {{ date('Y') }} ITCJ - AMDG</span>
            </div>
        </div>
    </div>

    <!-- Right Panel -->
    <div class="d-flex flex-column flex-lg-row-fluid bg-light">
        <div class="d-flex flex-center flex-column flex-grow-1 p-4 p-lg-5">
            <div class="success-card">
                <div class="success-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </div>

                <h2 class="fw-bold mb-3" style="color: var(--itcj-dark);">Demande envoyee !</h2>

                <p class="text-muted fs-6 mb-4">
                    Votre demande d'adhesion a ete envoyee avec succes.
                    Un administrateur va l'examiner et vous recevrez un email avec vos identifiants
                    une fois votre demande approuvee.
                </p>

                <div class="p-4 rounded mb-4" style="background: #f8f9fa;">
                    <p class="text-muted fs-7 mb-0">
                        <strong>Que se passe-t-il ensuite ?</strong><br>
                        1. Un administrateur examine votre demande<br>
                        2. Si approuvee, vous recevez un email<br>
                        3. Connectez-vous avec les identifiants recus
                    </p>
                </div>

                <a href="{{ route('login') }}" class="link-itcj">
                    Retour a la page de connexion
                </a>
            </div>
        </div>

        <div class="d-flex d-lg-none flex-center p-4">
            <span class="text-muted fs-8">&copy; {{ date('Y') }} ITCJ Community</span>
        </div>
    </div>
</div>
@endsection
