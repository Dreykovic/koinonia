<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Acces Refuse - ITCJ Community</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="{{ asset('assets/media/logos/favicon.ico') }}">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700">
    <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css">
    <style>
        :root {
            --itcj-blue: #1E5AA8;
            --itcj-red: #8B2332;
            --itcj-gold: #D4A84B;
            --itcj-dark: #1a1a2e;
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
            padding: 50px;
            max-width: 500px;
        }
        .error-code {
            font-size: 120px;
            font-weight: 800;
            color: var(--itcj-red);
            line-height: 1;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(139, 35, 50, 0.2);
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
            background: rgba(139, 35, 50, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
        }
        .error-icon i {
            font-size: 40px;
            color: var(--itcj-red);
        }
        .btn-itcj {
            background: var(--itcj-blue);
            color: #fff;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        .btn-itcj:hover {
            background: #164785;
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
    </style>
</head>
<body>
    <div class="error-container">
        <div class="logo">
            <img src="{{ asset('assets/media/logos/itcj-logo.jpg') }}" alt="ITCJ">
        </div>

        <div class="error-icon">
            <i class="ki-duotone ki-lock-3">
                <i class="path1"></i>
                <i class="path2"></i>
                <i class="path3"></i>
            </i>
        </div>

        <div class="error-code">403</div>
        <h1 class="error-title">Acces Refuse</h1>
        <p class="error-message">
            {{ $exception->getMessage() ?: 'Vous n\'avez pas les permissions necessaires pour acceder a cette page. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.' }}
        </p>

        <a href="{{ route('home') }}" class="btn-itcj">
            <i class="ki-duotone ki-home-2 me-2">
                <i class="path1"></i>
                <i class="path2"></i>
            </i>
            Retour a l'accueil
        </a>
    </div>
</body>
</html>





