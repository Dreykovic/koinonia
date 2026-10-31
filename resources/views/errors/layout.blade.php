{{--
    Gabarit commun des pages d'erreur. Variables fournies par App\Exceptions\Handler :
    $errorMessage (message français) et $requestId (référence à communiquer au support).
    Sections : title, code, heading, message (défaut : $errorMessage), icon, actions.
    @php $accent = '#1E5AA8' @endphp avant @extends pour changer la couleur du code.
--}}
@php
    $accent = $accent ?? '#1E5AA8';
    $requestId = $requestId ?? request()?->headers->get(\App\Http\Middleware\AddRequestContext::HEADER);
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <title>@yield('title') - ITCJ Community</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <link rel="shortcut icon" href="{{ asset('assets/media/logos/favicon.ico') }}">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700">
    <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css">
    <style>
        :root {
            --itcj-blue: #1E5AA8;
            --itcj-dark: #1a1a2e;
            --error-accent: {{ $accent }};
        }
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8eb 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-container {
            text-align: center;
            padding: 50px 20px;
            max-width: 520px;
        }
        .error-code {
            font-size: 120px;
            font-weight: 800;
            color: var(--error-accent);
            line-height: 1;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.12);
        }
        .error-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--itcj-dark);
            margin-bottom: 15px;
        }
        .error-message {
            font-size: 16px;
            color: #6c7293;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .error-icon {
            width: 80px;
            height: 80px;
            background: color-mix(in srgb, var(--error-accent) 10%, transparent);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
        }
        .error-icon i {
            font-size: 40px;
            color: var(--error-accent);
        }
        .btn-itcj, .btn-itcj-outline {
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            margin: 5px;
        }
        .btn-itcj {
            background: var(--itcj-blue);
            color: #fff;
            border: none;
        }
        .btn-itcj:hover {
            background: #164785;
            color: #fff;
            transform: translateY(-2px);
        }
        .btn-itcj-outline {
            background: transparent;
            color: var(--itcj-blue);
            border: 2px solid var(--itcj-blue);
            padding: 10px 28px;
        }
        .btn-itcj-outline:hover {
            background: var(--itcj-blue);
            color: #fff;
            transform: translateY(-2px);
        }
        .logo {
            margin-bottom: 30px;
        }
        .logo img {
            height: 60px;
            border-radius: 8px;
        }
        .actions {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .request-id {
            margin-top: 30px;
            font-size: 13px;
            color: #99a1b7;
        }
        .request-id code {
            user-select: all;
            color: var(--itcj-dark);
            background: rgba(0, 0, 0, 0.05);
            padding: 2px 6px;
            border-radius: 4px;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="logo">
            <img src="{{ asset('assets/media/logos/itcj-logo.jpg') }}" alt="ITCJ">
        </div>

        <div class="error-icon">
            @yield('icon')
        </div>

        <div class="error-code">@yield('code')</div>
        <h1 class="error-title">@yield('heading')</h1>
        <p class="error-message">
            @hasSection('message')
                @yield('message')
            @else
                {{ $errorMessage ?? (\App\Exceptions\Handler::MESSAGES[(int) trim($__env->yieldContent('code'))] ?? 'Une erreur est survenue.') }}
            @endif
        </p>

        <div class="actions">
            @hasSection('actions')
                @yield('actions')
            @else
                <a href="{{ route('home') }}" class="btn-itcj">
                    <i class="ki-duotone ki-home-2 me-2"><i class="path1"></i><i class="path2"></i></i>
                    Retour à l'accueil
                </a>
            @endif
        </div>

        @if ($requestId)
            <p class="request-id">
                Référence de l'erreur à communiquer au support : <code>{{ $requestId }}</code>
            </p>
        @endif
    </div>
</body>
</html>
