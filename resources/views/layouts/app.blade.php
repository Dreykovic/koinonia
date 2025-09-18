<!DOCTYPE html>
<html lang="fr">
<head>
    <title>@yield('title', 'Dashboard') - ITCJ Community</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ asset('assets/media/logos/favicon.ico') }}">

    <!-- Fonts - Combinaison élégante -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700;800&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Global Stylesheets -->
    <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css">

    <!-- Vite for Echo/Broadcasting -->
    @vite(['resources/js/app.js'])

    <style>
        /* ==================== ITCJ NEUMORPHISM DESIGN SYSTEM ==================== */
        /* Palette: Tons chauds - Bordeaux, Or, Crème */
        /* Style: Neumorphism avec ambiance chaleureuse et communautaire */

        :root {
            /* Couleurs principales - Tons chauds */
            --primary: #8B2332;
            --primary-light: #A83D4D;
            --primary-dark: #6B1A27;

            --secondary: #C4956A;
            --secondary-light: #D4A87A;
            --secondary-dark: #A67B52;

            --accent: #D4A84B;
            --accent-light: #E4C070;
            --accent-dark: #B08A3A;

            /* Fonds Neumorphism */
            --bg-base: #F5F0E8;
            --bg-card: #F8F4ED;
            --bg-dark: #2C2420;

            /* Textes */
            --text-primary: #3D3028;
            --text-secondary: #6B5D52;
            --text-muted: #9A8B7D;
            --text-light: #F5F0E8;

            /* Success/Warning/Danger */
            --success: #5D8A66;
            --success-light: #E8F0E8;
            --warning: #D4A84B;
            --warning-light: #FBF6EC;
            --danger: #8B2332;
            --danger-light: #F5E8EA;

            /* Neumorphism shadows */
            --shadow-light: rgba(255, 255, 255, 0.8);
            --shadow-dark: rgba(60, 50, 40, 0.15);
            --neu-shadow: 6px 6px 12px var(--shadow-dark), -6px -6px 12px var(--shadow-light);
            --neu-shadow-sm: 3px 3px 6px var(--shadow-dark), -3px -3px 6px var(--shadow-light);
            --neu-shadow-inset: inset 4px 4px 8px var(--shadow-dark), inset -4px -4px 8px var(--shadow-light);

            /* Legacy compatibility */
            --itcj-blue: var(--primary);
            --itcj-blue-dark: var(--primary-dark);
            --itcj-blue-light: var(--danger-light);
            --itcj-red: var(--primary);
            --itcj-red-light: var(--danger-light);
            --itcj-gold: var(--accent);
            --itcj-gold-light: var(--warning-light);
            --itcj-terracotta: var(--secondary);
            --itcj-dark: var(--text-primary);
        }

        /* ==================== TYPOGRAPHY ==================== */
        body, .font-body {
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, sans-serif !important;
        }

        h1, h2, h3, .font-heading {
            font-family: 'Playfair Display', Georgia, serif !important;
        }

        .card-title, .page-title, .fw-bold {
            font-family: 'Nunito', sans-serif !important;
            font-weight: 700 !important;
        }

        /* ==================== BASE BODY + ANIMATED BACKGROUND ==================== */
        body {
            background: var(--bg-base) !important;
            color: var(--text-primary);
            position: relative;
            overflow-x: hidden;
        }

        /* Fond animé avec particules subtiles */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
            background:
                /* Motif subtil de croix/étoiles */
                radial-gradient(circle at 20% 30%, rgba(139, 35, 50, 0.03) 0%, transparent 25%),
                radial-gradient(circle at 80% 20%, rgba(212, 168, 75, 0.04) 0%, transparent 20%),
                radial-gradient(circle at 60% 70%, rgba(196, 149, 106, 0.03) 0%, transparent 25%),
                radial-gradient(circle at 10% 80%, rgba(139, 35, 50, 0.02) 0%, transparent 20%),
                radial-gradient(circle at 90% 60%, rgba(212, 168, 75, 0.03) 0%, transparent 25%),
                /* Texture grain subtile */
                url("data:image/svg+xml,%3Csvg viewBox='0 0 400 400' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)'/%3E%3C/svg%3E");
            opacity: 0.4;
        }

        /* Particules flottantes animées */
        .floating-particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
            overflow: hidden;
        }

        .floating-particles::before,
        .floating-particles::after {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            opacity: 0.03;
            animation: float 25s infinite ease-in-out;
        }

        .floating-particles::before {
            background: var(--primary);
            top: -100px;
            left: 10%;
            animation-delay: 0s;
        }

        .floating-particles::after {
            background: var(--accent);
            bottom: -100px;
            right: 10%;
            animation-delay: -12s;
        }

        @keyframes float {
            0%, 100% {
                transform: translate(0, 0) scale(1);
            }
            25% {
                transform: translate(30px, 50px) scale(1.05);
            }
            50% {
                transform: translate(-20px, 80px) scale(0.95);
            }
            75% {
                transform: translate(40px, 30px) scale(1.02);
            }
        }

        /* Petites particules SVG animées */
        .particle-dot {
            position: fixed;
            width: 4px;
            height: 4px;
            border-radius: 50%;
            pointer-events: none;
            z-index: -1;
            animation: particle-float 20s infinite ease-in-out;
        }

        .particle-dot:nth-child(1) { left: 15%; top: 20%; background: rgba(139, 35, 50, 0.08); animation-delay: 0s; }
        .particle-dot:nth-child(2) { left: 85%; top: 15%; background: rgba(212, 168, 75, 0.1); animation-delay: -4s; }
        .particle-dot:nth-child(3) { left: 45%; top: 60%; background: rgba(196, 149, 106, 0.08); animation-delay: -8s; }
        .particle-dot:nth-child(4) { left: 70%; top: 80%; background: rgba(139, 35, 50, 0.06); animation-delay: -12s; }
        .particle-dot:nth-child(5) { left: 25%; top: 75%; background: rgba(212, 168, 75, 0.08); animation-delay: -16s; }
        .particle-dot:nth-child(6) { left: 90%; top: 45%; background: rgba(196, 149, 106, 0.06); animation-delay: -2s; }

        @keyframes particle-float {
            0%, 100% { transform: translateY(0) translateX(0); opacity: 0.6; }
            25% { transform: translateY(-20px) translateX(10px); opacity: 0.8; }
            50% { transform: translateY(-10px) translateX(-15px); opacity: 0.5; }
            75% { transform: translateY(-30px) translateX(5px); opacity: 0.7; }
        }

        /* ==================== ACCESSIBILITÉ & UX ==================== */

        /* Skip-to-content link */
        .skip-link {
            position: absolute;
            top: -100px;
            left: 0;
            background: var(--primary);
            color: #fff;
            padding: 12px 24px;
            z-index: 9999;
            border-radius: 0 0 12px 0;
            font-weight: 600;
            text-decoration: none;
            transition: top 0.2s ease;
        }
        .skip-link:focus {
            top: 0;
            outline: 3px solid var(--accent);
            outline-offset: 2px;
        }

        /* Focus visible amélioré */
        :focus-visible {
            outline: 3px solid var(--accent) !important;
            outline-offset: 2px;
        }
        button:focus-visible,
        a:focus-visible,
        input:focus-visible,
        select:focus-visible,
        textarea:focus-visible,
        [tabindex]:focus-visible {
            outline: 3px solid var(--accent) !important;
            outline-offset: 2px;
        }

        /* Tooltips CSS personnalisés avec gestion collision automatique */
        [data-tooltip] {
            position: relative;
        }
        [data-tooltip]::after {
            content: attr(data-tooltip);
            position: absolute;
            padding: 8px 14px;
            background: var(--bg-dark);
            color: var(--text-light);
            font-size: 12px;
            font-weight: 500;
            border-radius: 8px;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s ease;
            z-index: 1050;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            pointer-events: none;
            /* Position par défaut: en bas */
            top: calc(100% + 8px);
            left: 50%;
            transform: translateX(-50%);
        }
        [data-tooltip]::before {
            content: '';
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            border: 6px solid transparent;
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s ease;
            z-index: 1051;
            /* Flèche vers le haut par défaut (tooltip en bas) */
            top: calc(100% + 2px);
            border-bottom-color: var(--bg-dark);
        }
        [data-tooltip]:hover::after,
        [data-tooltip]:hover::before,
        [data-tooltip]:focus::after,
        [data-tooltip]:focus::before {
            opacity: 1;
            visibility: visible;
        }
        /* Tooltip en haut (pour les éléments en bas de page) */
        [data-tooltip-pos="top"]::after {
            top: auto;
            bottom: calc(100% + 8px);
        }
        [data-tooltip-pos="top"]::before {
            top: auto;
            bottom: calc(100% + 2px);
            border-bottom-color: transparent;
            border-top-color: var(--bg-dark);
        }
        /* Tooltip en bas explicite */
        [data-tooltip-pos="bottom"]::after {
            bottom: auto;
            top: calc(100% + 8px);
        }
        [data-tooltip-pos="bottom"]::before {
            bottom: auto;
            top: calc(100% + 2px);
            border-top-color: transparent;
            border-bottom-color: var(--bg-dark);
        }
        /* Tooltip à gauche */
        [data-tooltip-pos="left"]::after {
            top: 50%;
            bottom: auto;
            left: auto;
            right: calc(100% + 8px);
            transform: translateY(-50%);
        }
        [data-tooltip-pos="left"]::before {
            top: 50%;
            left: auto;
            right: calc(100% + 2px);
            transform: translateY(-50%);
            border-bottom-color: transparent;
            border-left-color: var(--bg-dark);
        }
        /* Tooltip à droite */
        [data-tooltip-pos="right"]::after {
            top: 50%;
            bottom: auto;
            left: calc(100% + 8px);
            transform: translateY(-50%);
        }
        [data-tooltip-pos="right"]::before {
            top: 50%;
            left: calc(100% + 2px);
            transform: translateY(-50%);
            border-bottom-color: transparent;
            border-right-color: var(--bg-dark);
        }
        /* Empêcher le tooltip de dépasser l'écran */
        @media (max-width: 768px) {
            [data-tooltip]::after {
                max-width: 200px;
                white-space: normal;
                text-align: center;
            }
        }

        /* Cards cliquables */
        .card-clickable {
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            display: block;
            color: inherit;
        }
        .card-clickable:hover {
            transform: translateY(-3px);
            box-shadow: 8px 8px 16px var(--shadow-dark), -8px -8px 16px var(--shadow-light);
        }
        .card-clickable:active {
            transform: translateY(-1px);
        }

        /* Badges statut accessibles */
        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 12px;
        }
        .badge-status-success {
            background: var(--success-light);
            color: var(--success);
        }
        .badge-status-warning {
            background: var(--warning-light);
            color: var(--accent-dark);
        }
        .badge-status-danger {
            background: var(--danger-light);
            color: var(--primary);
        }
        .badge-status-info {
            background: rgba(196, 149, 106, 0.15);
            color: var(--secondary-dark);
        }

        /* Boutons d'action avec texte */
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        .btn-action:hover {
            transform: translateY(-1px);
        }
        .btn-action i {
            font-size: 16px;
        }

        /* Actions rapides grid */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }
        @media (max-width: 768px) {
            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        .quick-action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 16px 12px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 14px;
            text-decoration: none;
            color: #fff;
            transition: all 0.2s ease;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .quick-action-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
            color: #fff;
        }
        .quick-action-btn i {
            font-size: 24px;
            margin-bottom: 8px;
            opacity: 0.9;
        }
        .quick-action-btn span {
            font-size: 12px;
            font-weight: 600;
            opacity: 0.95;
        }

        /* Widget repas dashboard */
        .meal-dashboard-widget {
            background: linear-gradient(135deg, var(--bg-card) 0%, #fff 100%);
            border-radius: 20px;
            padding: 20px 24px;
            box-shadow: var(--neu-shadow);
            border-left: 4px solid var(--accent);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
        }
        .meal-dashboard-widget.registered {
            border-left-color: var(--success);
        }
        .meal-widget-info {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .meal-widget-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 24px;
        }
        .meal-widget-details h4 {
            margin: 0 0 4px;
            font-size: 16px;
            font-weight: 700;
            color: var(--text-primary);
        }
        .meal-widget-details p {
            margin: 0;
            font-size: 13px;
            color: var(--text-secondary);
        }
        .meal-widget-progress {
            flex: 1;
            max-width: 200px;
            min-width: 150px;
        }
        .meal-widget-progress .progress {
            height: 8px;
            border-radius: 4px;
            background: rgba(60, 48, 40, 0.1);
            margin-bottom: 6px;
        }
        .meal-widget-progress .progress-bar {
            border-radius: 4px;
        }
        .meal-widget-progress small {
            font-size: 12px;
            color: var(--text-muted);
        }
        .meal-widget-action .btn {
            white-space: nowrap;
        }

        /* Animation pulse pour badges */
        @keyframes pulse-badge {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        .badge-pulse {
            animation: pulse-badge 2s ease-in-out infinite;
        }

        /* Utility classes */
        .text-itcj-blue, .text-primary { color: var(--primary) !important; }
        .text-itcj-red { color: var(--primary) !important; }
        .text-itcj-gold { color: var(--accent) !important; }
        .text-secondary { color: var(--secondary) !important; }
        .bg-itcj-blue { background-color: var(--primary) !important; }
        .bg-itcj-blue-light { background-color: var(--danger-light) !important; }
        .bg-itcj-red { background-color: var(--primary) !important; }
        .bg-itcj-red-light { background-color: var(--danger-light) !important; }
        .bg-itcj-gold { background-color: var(--accent) !important; }
        .bg-itcj-gold-light { background-color: var(--warning-light) !important; }

        /* ==================== SIDEBAR - Neumorphism Crème élégant ==================== */
        .app-sidebar {
            background: linear-gradient(180deg, #F0E8DC 0%, #E8E0D4 100%) !important;
            box-shadow: 4px 0 16px rgba(60, 50, 40, 0.08);
            border-right: 1px solid rgba(60, 48, 40, 0.05);
        }

        .app-sidebar .app-sidebar-logo {
            border-bottom: 1px solid rgba(60, 48, 40, 0.1);
            padding: 20px 16px !important;
            background: rgba(255, 255, 255, 0.3);
        }

        .app-sidebar .app-sidebar-logo a {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .app-sidebar .app-sidebar-logo a span.text-white {
            color: var(--text-primary) !important;
        }

        .app-sidebar .text-itcj-gold {
            color: var(--primary) !important;
        }

        /* Menu items - Neumorphism subtle */
        .app-sidebar .menu-link {
            color: var(--text-secondary) !important;
            border-radius: 12px;
            margin: 3px 12px;
            padding: 11px 14px !important;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            background: transparent;
            position: relative;
        }

        .app-sidebar .menu-link:hover {
            background: rgba(255, 255, 255, 0.6) !important;
            color: var(--text-primary) !important;
            box-shadow: 3px 3px 8px rgba(60, 50, 40, 0.1),
                        -3px -3px 8px rgba(255, 255, 255, 0.9);
        }

        .app-sidebar .menu-link.active {
            background: var(--primary) !important;
            color: #fff !important;
            box-shadow: 4px 4px 12px rgba(139, 35, 50, 0.3),
                        -2px -2px 8px rgba(255, 255, 255, 0.2),
                        inset 0 1px 2px rgba(255, 255, 255, 0.2);
        }

        .app-sidebar .menu-icon {
            color: var(--secondary) !important;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .app-sidebar .menu-link:hover .menu-icon {
            color: var(--primary) !important;
            transform: scale(1.05);
        }

        .app-sidebar .menu-link.active .menu-icon {
            color: #fff !important;
        }

        .app-sidebar .menu-title {
            font-weight: 500;
            font-size: 13.5px;
            letter-spacing: 0.2px;
        }

        .app-sidebar .menu-link.active .menu-title {
            font-weight: 600;
        }

        /* Section headings */
        .app-sidebar .menu-heading {
            color: var(--primary) !important;
            font-size: 10px !important;
            letter-spacing: 1.2px;
            padding: 0 16px;
            margin-top: 8px;
        }

        /* Badge notifications */
        .app-sidebar .badge {
            font-size: 10px;
            padding: 4px 8px;
            min-width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Footer button */
        .app-sidebar .btn-itcj-gold {
            background: var(--primary);
            color: #fff;
            border-radius: 12px;
            font-weight: 600;
            box-shadow: 4px 4px 10px rgba(60, 50, 40, 0.15),
                        -2px -2px 6px rgba(255, 255, 255, 0.7);
            transition: all 0.2s ease;
        }

        .app-sidebar .btn-itcj-gold:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 6px 6px 14px rgba(60, 50, 40, 0.2),
                        -3px -3px 8px rgba(255, 255, 255, 0.8);
        }

        /* Sidebar footer */
        .app-sidebar-footer {
            background: rgba(255, 255, 255, 0.2);
            border-top: 1px solid rgba(60, 48, 40, 0.08);
            padding: 16px !important;
        }

        /* Scrollbar personnalisé */
        .app-sidebar .app-sidebar-wrapper::-webkit-scrollbar {
            width: 4px;
        }
        .app-sidebar .app-sidebar-wrapper::-webkit-scrollbar-track {
            background: transparent;
        }
        .app-sidebar .app-sidebar-wrapper::-webkit-scrollbar-thumb {
            background: rgba(139, 35, 50, 0.2);
            border-radius: 4px;
        }
        .app-sidebar .app-sidebar-wrapper::-webkit-scrollbar-thumb:hover {
            background: rgba(139, 35, 50, 0.4);
        }

        /* ==================== HEADER - Crème avec ombre douce ==================== */
        .app-header {
            background: var(--bg-base) !important;
            border-bottom: none !important;
            box-shadow: 0 2px 8px var(--shadow-dark) !important;
        }

        /* ==================== BUTTONS - Neumorphism ==================== */
        .btn-itcj, .btn-neu-primary {
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 12px;
            box-shadow: var(--neu-shadow-sm);
            transition: all 0.2s ease;
        }
        .btn-itcj:hover, .btn-neu-primary:hover {
            background: var(--primary-dark);
            color: #fff;
            box-shadow: var(--neu-shadow);
            transform: translateY(-1px);
        }
        .btn-itcj:active, .btn-neu-primary:active {
            box-shadow: var(--neu-shadow-inset);
            transform: translateY(0);
        }

        .btn-itcj-outline, .btn-neu-outline {
            background: var(--bg-card);
            color: var(--primary);
            border: none;
            border-radius: 12px;
            box-shadow: var(--neu-shadow-sm);
            transition: all 0.2s ease;
        }
        .btn-itcj-outline:hover, .btn-neu-outline:hover {
            background: var(--primary);
            color: #fff;
            box-shadow: var(--neu-shadow);
        }

        .btn-itcj-gold, .btn-neu-accent {
            background: var(--accent);
            color: var(--text-primary);
            border: none;
            border-radius: 12px;
            box-shadow: var(--neu-shadow-sm);
            transition: all 0.2s ease;
        }
        .btn-itcj-gold:hover, .btn-neu-accent:hover {
            background: var(--accent-dark);
            color: #fff;
            box-shadow: var(--neu-shadow);
        }

        .btn-itcj-red {
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 12px;
            box-shadow: var(--neu-shadow-sm);
        }
        .btn-itcj-red:hover {
            background: var(--primary-dark);
            color: #fff;
        }

        /* Secondary button */
        .btn-secondary, .btn-light {
            background: var(--bg-card);
            color: var(--text-primary);
            border: none;
            border-radius: 12px;
            box-shadow: var(--neu-shadow-sm);
        }
        .btn-secondary:hover, .btn-light:hover {
            background: var(--bg-base);
            color: var(--text-primary);
            box-shadow: var(--neu-shadow);
        }

        /* ==================== CARDS - Neumorphism ==================== */
        .card {
            background: var(--bg-card);
            border: none;
            border-radius: 16px;
            box-shadow: var(--neu-shadow);
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 8px 8px 16px var(--shadow-dark), -8px -8px 16px var(--shadow-light);
        }

        .card-header {
            background: transparent;
            border-bottom: 1px solid rgba(60, 48, 40, 0.08);
            border-radius: 16px 16px 0 0;
        }

        .card-title {
            color: var(--text-primary);
        }

        /* ==================== STAT CARDS ==================== */
        .stat-card {
            background: var(--bg-card);
            border-radius: 20px;
            padding: 24px;
            box-shadow: var(--neu-shadow);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 8px 8px 20px var(--shadow-dark), -8px -8px 20px var(--shadow-light);
        }

        .stat-card .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: var(--neu-shadow-sm);
        }

        .stat-icon-primary {
            background: rgba(139, 35, 50, 0.12);
            color: var(--primary);
        }

        .stat-icon-accent, .stat-icon-gold {
            background: rgba(212, 168, 75, 0.15);
            color: var(--accent-dark);
        }

        .stat-icon-secondary {
            background: rgba(196, 149, 106, 0.15);
            color: var(--secondary-dark);
        }

        .stat-icon-success {
            background: rgba(93, 138, 102, 0.15);
            color: var(--success);
        }

        .stat-card .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-primary);
        }

        .stat-card .stat-label {
            font-size: 13px;
            color: var(--text-muted);
        }

        /* ==================== BADGES - Neumorphism léger ==================== */
        .badge-itcj, .badge-primary {
            background: var(--danger-light);
            color: var(--primary);
            border-radius: 20px;
            padding: 6px 14px;
            font-weight: 600;
            /* box-shadow: var(--neu-shadow-sm); */
        }

        .badge-itcj-gold, .badge-accent {
            background: var(--warning-light);
            color: var(--accent-dark);
            border-radius: 20px;
            padding: 6px 14px;
            font-weight: 600;
        }

        .badge-itcj-red {
            background: var(--danger-light);
            color: var(--primary);
            border-radius: 20px;
        }

        .badge-success {
            background: var(--success-light) !important;
            color: var(--success) !important;
        }

        .badge-warning {
            background: var(--warning-light) !important;
            color: var(--accent-dark) !important;
        }

        .badge-danger {
            background: var(--danger-light) !important;
            color: var(--primary) !important;
        }

        /* ==================== FORMS - Neumorphism inset ==================== */
        .form-control, .form-select {
            background: var(--bg-card);
            border: none;
            border-radius: 12px;
            box-shadow: var(--neu-shadow-inset);
            color: var(--text-primary);
            transition: all 0.2s ease;
        }

        .form-control:focus, .form-select:focus {
            background: var(--bg-card);
            border: none;
            box-shadow: var(--neu-shadow-inset), 0 0 0 3px rgba(139, 35, 50, 0.15);
            color: var(--text-primary);
        }

        .form-control::placeholder {
            color: var(--text-muted);
        }

        .form-label {
            color: var(--text-secondary);
            font-weight: 500;
        }

        /* ==================== LINKS ==================== */
        a {
            color: var(--primary);
        }
        a:hover {
            color: var(--primary-dark);
        }

        /* ==================== PAGE HEADING ==================== */
        .page-heading {
            color: var(--text-primary) !important;
        }

        .breadcrumb-item a {
            color: var(--text-muted);
        }

        .breadcrumb-item a:hover {
            color: var(--primary);
        }

        /* ==================== ALERTS ==================== */
        .alert-success {
            background: var(--success-light);
            border: none;
            color: var(--success);
            border-radius: 12px;
            box-shadow: var(--neu-shadow-sm);
        }

        .alert-danger {
            background: var(--danger-light);
            border: none;
            color: var(--primary);
            border-radius: 12px;
            box-shadow: var(--neu-shadow-sm);
        }

        .alert-warning {
            background: var(--warning-light);
            border: none;
            color: var(--accent-dark);
            border-radius: 12px;
            box-shadow: var(--neu-shadow-sm);
        }

        /* ==================== TABLES ==================== */
        .table {
            background: var(--bg-card);
            border-radius: 16px;
            overflow: hidden;
        }

        .table thead th {
            background: rgba(139, 35, 50, 0.06);
            color: var(--text-primary);
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
        }

        .table tbody tr {
            transition: background 0.2s ease;
        }

        .table tbody tr:hover {
            background: rgba(212, 168, 75, 0.06);
        }

        .table td {
            border-color: rgba(60, 48, 40, 0.06);
            color: var(--text-primary);
        }

        .table-responsive {
            background: var(--bg-card);
            border-radius: 16px;
            box-shadow: var(--neu-shadow);
        }

        /* Pas de double neumorphisme pour les tables dans les cards */
        .card .table-responsive,
        .card-body .table-responsive {
            background: transparent;
            border-radius: 0;
            box-shadow: none;
            margin: 0;
        }

        .card-body .table {
            margin-bottom: 0;
        }

        /* ==================== EMPTY STATE ==================== */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: var(--text-muted);
        }

        .empty-state-icon {
            font-size: 50px;
            opacity: 0.4;
            margin-bottom: 15px;
            color: var(--secondary);
        }

        /* ==================== MODALS ==================== */
        .modal-content {
            background: var(--bg-card);
            border: none;
            border-radius: 20px;
            box-shadow: var(--neu-shadow);
        }

        .modal-header {
            border-bottom: 1px solid rgba(60, 48, 40, 0.08);
        }

        .modal-footer {
            border-top: 1px solid rgba(60, 48, 40, 0.08);
        }

        /* ==================== SYMBOLS/AVATARS ==================== */
        .symbol-label {
            background: var(--bg-card);
            box-shadow: var(--neu-shadow-sm);
        }

        /* ==================== DROPDOWN MENUS ==================== */
        .dropdown-menu {
            background: var(--bg-card);
            border: none;
            border-radius: 12px;
            box-shadow: var(--neu-shadow);
        }

        .dropdown-item {
            color: var(--text-primary);
            border-radius: 8px;
            margin: 2px 8px;
            transition: all 0.2s ease;
        }

        .dropdown-item:hover {
            background: rgba(212, 168, 75, 0.1);
            color: var(--primary);
        }

        /* ==================== PAGINATION ==================== */
        .pagination .page-link {
            background: var(--bg-card);
            border: none;
            color: var(--text-primary);
            border-radius: 10px;
            margin: 0 3px;
            box-shadow: var(--neu-shadow-sm);
        }

        .pagination .page-link:hover {
            background: var(--bg-base);
            box-shadow: var(--neu-shadow);
        }

        .pagination .page-item.active .page-link {
            background: var(--primary);
            color: #fff;
        }

        /* Pagination Mobile */
        @media (max-width: 768px) {
            .pagination {
                gap: 4px;
                flex-wrap: wrap;
                justify-content: center;
            }

            .pagination .page-link {
                padding: 8px 12px;
                font-size: 13px;
                margin: 2px;
                min-width: 36px;
                min-height: 36px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .pagination .page-link {
                padding: 6px 10px;
                font-size: 12px;
                min-width: 32px;
                min-height: 32px;
                margin: 1px;
                border-radius: 8px;
            }
        }

        /* ==================== CONTENT CARDS with border ==================== */
        .content-card {
            background: var(--bg-card);
            border-radius: 16px;
            box-shadow: var(--neu-shadow);
            border-left: 4px solid var(--accent);
        }

        .content-card-primary {
            border-left-color: var(--primary);
        }

        /* ==================== BULLETIN CARD ==================== */
        .bulletin-card {
            background: linear-gradient(135deg, var(--bg-dark) 0%, #1a1512 100%);
            border-radius: 20px;
            box-shadow: var(--neu-shadow);
            overflow: hidden;
        }

        .bulletin-overlay {
            background: rgba(212, 168, 75, 0.08);
            backdrop-filter: blur(4px);
        }

        /* ==================== FORMS INSIDE CARDS (flat, no nested neumorphism) ==================== */
        .card .form-control,
        .card .form-select,
        .form-control-solid,
        .form-select-solid {
            background: #fff;
            border: 1px solid rgba(60, 48, 40, 0.12);
            box-shadow: none;
        }

        .card .form-control:focus,
        .card .form-select:focus,
        .form-control-solid:focus,
        .form-select-solid:focus {
            background: #fff;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(139, 35, 50, 0.12);
        }

        /* ==================== MOBILE RESPONSIVENESS ==================== */

        /* Mobile-first button improvements */
        @media (max-width: 576px) {
            .btn {
                padding: 10px 16px;
                font-size: 14px;
            }

            .btn-sm {
                padding: 8px 12px;
                font-size: 13px;
            }

            .btn-icon.btn-sm {
                width: 44px;
                height: 44px;
                min-width: 44px;
                min-height: 44px;
            }

            /* Full width buttons on mobile */
            .page-actions .btn,
            .card-footer .btn {
                width: 100%;
                margin-bottom: 8px;
            }

            .card-footer .btn:last-child {
                margin-bottom: 0;
            }

            .card-footer {
                flex-direction: column !important;
            }

            .card-footer .gap-3 {
                gap: 8px !important;
            }
        }

        /* Mobile back button sticky */
        @media (max-width: 768px) {
            .mobile-back-btn {
                position: fixed;
                bottom: 20px;
                left: 20px;
                width: 44px;
                height: 44px;
                border-radius: 50%;
                background: var(--bg-card);
                box-shadow: 0 2px 10px rgba(0,0,0,0.15);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 100;
                color: var(--text-primary);
                text-decoration: none;
                border: 1px solid rgba(0,0,0,0.08);
                transition: all 0.2s ease;
            }

            .mobile-back-btn:hover,
            .mobile-back-btn:active {
                background: var(--primary);
                color: #fff;
                transform: scale(1.05);
            }

            .mobile-back-btn i {
                font-size: 18px;
            }
        }

        /* Mobile stat cards */
        @media (max-width: 768px) {
            .stat-card {
                padding: 16px;
            }

            .stat-card .stat-icon {
                width: 42px;
                height: 42px;
                font-size: 20px;
            }

            .stat-card .stat-value {
                font-size: 22px;
            }

            .stat-card .stat-label {
                font-size: 12px;
            }

            /* Hide stat card arrows on mobile */
            .stat-card .btn-itcj-outline {
                display: none;
            }

            /* Stack stat card content */
            .stat-card.d-flex {
                flex-wrap: wrap;
            }
        }

        /* Mobile table improvements */
        @media (max-width: 768px) {
            .table-responsive {
                margin: 0 -15px;
                padding: 0 15px;
            }

            .table {
                font-size: 13px;
            }

            .table thead th {
                font-size: 10px;
                padding: 10px 8px;
                white-space: nowrap;
            }

            .table td {
                padding: 12px 8px;
                vertical-align: middle;
            }

            /* Hide less important columns on mobile */
            .table .d-none-mobile {
                display: none !important;
            }

            /* Smaller symbols on mobile */
            .symbol.symbol-45px,
            .symbol.symbol-40px,
            .symbol.symbol-35px {
                width: 32px !important;
                height: 32px !important;
            }

            .symbol.symbol-45px .symbol-label,
            .symbol.symbol-40px .symbol-label,
            .symbol.symbol-35px .symbol-label {
                width: 32px !important;
                height: 32px !important;
                font-size: 12px !important;
            }
        }

        /* Mobile card improvements */
        @media (max-width: 576px) {
            .card {
                border-radius: 8px;
                margin-bottom: 16px;
            }

            .card-header {
                padding: 16px;
                flex-direction: column;
                align-items: flex-start !important;
            }

            .card-header .card-title {
                margin-bottom: 8px;
            }

            .card-body {
                padding: 16px;
            }

            .card-footer {
                padding: 16px;
            }

            /* Card header with badge */
            .card-header.d-flex {
                flex-direction: column;
                gap: 10px;
            }
        }

        /* Mobile form improvements */
        @media (max-width: 576px) {
            .form-control,
            .form-select {
                font-size: 16px; /* Prevents zoom on iOS */
                padding: 12px 14px;
            }

            .form-label {
                font-size: 14px;
                margin-bottom: 6px;
            }

            .row.mb-5 > [class*="col-"] {
                margin-bottom: 16px;
            }

            .row.mb-5 > [class*="col-"]:last-child {
                margin-bottom: 0;
            }
        }

        /* Mobile toolbar/header */
        @media (max-width: 576px) {
            #kt_app_toolbar {
                padding: 12px 0 !important;
            }

            .page-heading {
                font-size: 18px !important;
            }

            .breadcrumb {
                display: none;
            }

            .page-actions {
                margin-top: 12px;
                width: 100%;
            }

            #kt_app_toolbar_container {
                flex-direction: column;
                align-items: flex-start !important;
            }
        }

        /* Mobile sidebar improvements */
        @media (max-width: 991px) {
            .app-sidebar {
                width: 300px !important;
                background: linear-gradient(180deg, #F5EDE3 0%, #EDE5D9 100%) !important;
            }

            .app-sidebar .app-sidebar-logo {
                padding: 24px 20px !important;
            }

            .app-sidebar .app-sidebar-logo img {
                width: 50px !important;
                height: 50px !important;
            }

            .app-sidebar .menu-link {
                padding: 14px 18px !important;
                margin: 4px 16px;
                border-radius: 14px;
            }

            .app-sidebar .menu-title {
                font-size: 15px;
            }

            .app-sidebar .menu-icon {
                width: 28px;
                height: 28px;
                font-size: 18px;
            }

            .app-sidebar .menu-heading {
                padding: 0 20px;
                font-size: 11px !important;
            }

            .app-sidebar-footer {
                padding: 20px !important;
            }

            .app-sidebar .btn-itcj-gold {
                padding: 14px 20px !important;
                font-size: 15px;
            }

            /* Backdrop overlay */
            .drawer-overlay {
                background: rgba(44, 36, 32, 0.5) !important;
                backdrop-filter: blur(4px);
            }
        }

        /* Extra small screens */
        @media (max-width: 576px) {
            .app-sidebar {
                width: 85vw !important;
                max-width: 320px;
            }

            .app-sidebar .menu-link {
                padding: 12px 14px !important;
                margin: 3px 12px;
            }

            .app-sidebar .menu-title {
                font-size: 14px;
            }
        }

        /* Mobile profile page */
        @media (max-width: 768px) {
            .symbol.symbol-150px {
                width: 100px !important;
                height: 100px !important;
            }

            .symbol.symbol-150px .symbol-label {
                width: 100px !important;
                height: 100px !important;
                font-size: 32px !important;
            }
        }

        /* Mobile badges */
        @media (max-width: 576px) {
            .badge {
                font-size: 11px;
                padding: 5px 8px;
            }
        }

        /* Touch-friendly spacing */
        @media (max-width: 768px) {
            .d-flex.gap-2 {
                gap: 8px !important;
            }

            /* Ensure minimum touch target size */
            .btn-icon {
                min-width: 44px;
                min-height: 44px;
            }

            /* Better spacing for action buttons */
            .text-end .d-flex {
                gap: 8px;
            }
        }

        /* Mobile empty state */
        @media (max-width: 576px) {
            .empty-state {
                padding: 30px 16px;
            }

            .empty-state-icon {
                font-size: 40px;
            }

            .empty-state-text {
                font-size: 14px;
            }
        }

        /* Mobile bottom navigation bar style */
        @media (max-width: 576px) {
            /* Add padding at bottom for fixed elements if needed */
            #kt_app_content {
                padding-bottom: 20px;
            }

            /* Improve content container padding */
            #kt_app_content_container {
                padding-left: 12px !important;
                padding-right: 12px !important;
            }
        }

        /* Mobile grid improvements */
        @media (max-width: 576px) {
            .row.g-5,
            .row.g-4 {
                --bs-gutter-x: 12px;
                --bs-gutter-y: 12px;
            }

            .col-sm-6 {
                width: 100%;
            }
        }

        /* Mobile announcement cards */
        @media (max-width: 576px) {
            .card.border-start.border-4 {
                border-width: 3px !important;
            }
        }

        /* PWA-like mobile header */
        @media (max-width: 576px) {
            .app-header {
                position: sticky;
                top: 0;
                z-index: 100;
            }
        }

        /* Safe area for iOS notch */
        @supports (padding-top: env(safe-area-inset-top)) {
            .app-header {
                padding-top: env(safe-area-inset-top);
            }

            #kt_app_footer {
                padding-bottom: env(safe-area-inset-bottom);
            }
        }

        /* ==================== MOBILE-FIRST COMPONENTS ==================== */

        /* Mobile Card List (remplace les tables) */
        .mobile-card-list { display: none; }
        .desktop-table { display: block; }

        @media (max-width: 768px) {
            .mobile-card-list { display: block; }
            .desktop-table { display: none !important; }
        }

        .mobile-card-item {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            box-shadow: var(--neu-shadow-sm);
        }

        .mobile-card-item-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .mobile-card-item-title {
            font-weight: 600;
            font-size: 15px;
            color: var(--text-primary);
        }

        .mobile-card-item-body {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .mobile-card-item-row {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
        }

        .mobile-card-item-label {
            color: var(--text-muted);
        }

        .mobile-card-item-value {
            color: var(--text-primary);
            font-weight: 500;
        }

        .mobile-card-item-actions {
            display: flex;
            gap: 8px;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid rgba(60, 48, 40, 0.08);
        }

        .mobile-card-item-actions .btn {
            flex: 1;
            padding: 10px;
            font-size: 13px;
        }

        /* Floating Action Button */
        .fab {
            position: fixed;
            bottom: calc(24px + env(safe-area-inset-bottom));
            right: 24px;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: var(--primary);
            color: #fff;
            display: none;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(139, 35, 50, 0.4);
            z-index: 1000;
            border: none;
            font-size: 24px;
            transition: transform 0.2s;
            text-decoration: none;
        }

        .fab:hover {
            color: #fff;
            text-decoration: none;
        }

        .fab:active {
            transform: scale(0.95);
        }

        @media (max-width: 768px) {
            .fab { display: flex; }
            .desktop-add-btn { display: none !important; }
        }

        /* Filter Pills (scroll horizontal) */
        .filter-pills-container {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
            padding: 8px 0;
            margin: 0 -16px;
        }

        .filter-pills-container::-webkit-scrollbar { display: none; }

        .filter-pills {
            display: flex;
            gap: 8px;
            padding: 0 16px;
        }

        .filter-pill {
            flex-shrink: 0;
            padding: 10px 20px;
            border-radius: 20px;
            background: var(--bg-card);
            color: var(--text-secondary);
            font-size: 13px;
            font-weight: 600;
            border: none;
            white-space: nowrap;
            transition: all 0.2s;
            text-decoration: none;
            box-shadow: var(--neu-shadow-sm);
        }

        .filter-pill:hover {
            color: var(--text-primary);
            text-decoration: none;
        }

        .filter-pill.active {
            background: var(--primary);
            color: #fff;
        }

        /* Bottom Sheet */
        .bottom-sheet-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1040;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }

        .bottom-sheet-backdrop.show {
            opacity: 1;
            visibility: visible;
        }

        .bottom-sheet {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--bg-card);
            border-radius: 20px 20px 0 0;
            padding: 20px;
            padding-bottom: calc(20px + env(safe-area-inset-bottom));
            z-index: 1050;
            transform: translateY(100%);
            transition: transform 0.3s ease;
            max-height: 85vh;
            overflow-y: auto;
        }

        .bottom-sheet.show {
            transform: translateY(0);
        }

        .bottom-sheet-handle {
            width: 40px;
            height: 4px;
            background: var(--text-muted);
            border-radius: 2px;
            margin: 0 auto 16px;
            opacity: 0.3;
        }

        .bottom-sheet-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 16px;
            color: var(--text-primary);
        }

        /* Mobile page header */
        .page-header-mobile {
            display: none;
        }

        @media (max-width: 768px) {
            .page-header-mobile {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 16px;
                background: var(--bg-card);
                position: sticky;
                top: 0;
                z-index: 50;
                box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            }

            .page-header-mobile h1 {
                font-size: 18px;
                font-weight: 700;
                margin: 0;
                color: var(--text-primary);
            }

            .page-header-mobile .btn-back {
                width: 40px;
                height: 40px;
                border-radius: 10px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: transparent;
                border: none;
                color: var(--text-primary);
                font-size: 20px;
            }
        }

        /* Touch-friendly improvements */
        @media (max-width: 768px) {
            /* Bigger touch targets */
            .btn {
                min-height: 44px;
            }

            .btn-sm {
                min-height: 40px;
            }

            /* Form inputs */
            .form-control,
            .form-select {
                min-height: 48px;
                font-size: 16px;
            }

            /* Checkboxes and radios */
            .form-check-input {
                width: 24px;
                height: 24px;
            }

            .form-check-label {
                padding-left: 8px;
                font-size: 15px;
                line-height: 24px;
            }

            /* Mobile search bar */
            .mobile-search-bar {
                padding: 12px 16px;
                background: var(--bg-base);
            }

            .mobile-search-bar .form-control {
                border-radius: 12px;
                padding-left: 44px;
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' fill='%236B5D52' viewBox='0 0 16 16'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z'/%3E%3C/svg%3E");
                background-repeat: no-repeat;
                background-position: 14px center;
            }
        }

        /* Mobile list empty state */
        .mobile-empty-state {
            text-align: center;
            padding: 48px 24px;
        }

        .mobile-empty-state-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--bg-card);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            font-size: 32px;
            color: var(--text-muted);
        }

        .mobile-empty-state-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .mobile-empty-state-text {
            font-size: 14px;
            color: var(--text-muted);
            margin-bottom: 24px;
        }

        /* ============================================
           CONFIRM MODAL - Mobile-first (bottom sheet)
           ============================================ */
        .confirm-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(44, 36, 32, 0.7);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            z-index: 9999;
            display: none;
            align-items: flex-end;
            justify-content: center;
            padding: 0;
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .confirm-modal-overlay.show {
            display: flex;
            opacity: 1;
        }

        .confirm-modal {
            background: var(--bg-card);
            width: 100%;
            max-width: 100%;
            border-radius: 20px 20px 0 0;
            padding: 24px 20px;
            padding-bottom: calc(24px + env(safe-area-inset-bottom));
            box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.25);
            transform: translateY(100%);
            transition: transform 0.3s cubic-bezier(0.32, 0.72, 0, 1);
        }

        .confirm-modal-overlay.show .confirm-modal {
            transform: translateY(0);
        }

        .confirm-modal-icon {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            font-size: 28px;
        }

        .confirm-modal-icon.danger {
            background: rgba(183, 65, 51, 0.12);
            color: var(--primary);
        }

        .confirm-modal-icon.warning {
            background: rgba(214, 164, 72, 0.15);
            color: var(--accent);
        }

        .confirm-modal-icon.info {
            background: rgba(93, 138, 102, 0.12);
            color: #5D8A66;
        }

        .confirm-modal-icon.success {
            background: rgba(93, 138, 102, 0.15);
            color: #5D8A66;
        }

        .confirm-modal-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
            text-align: center;
            margin-bottom: 8px;
        }

        .confirm-modal-message {
            font-size: 14px;
            color: var(--text-muted);
            text-align: center;
            margin-bottom: 24px;
            line-height: 1.5;
        }

        .confirm-modal-actions {
            display: flex;
            gap: 12px;
        }

        .confirm-modal-actions .btn {
            flex: 1;
            min-height: 48px;
            font-weight: 600;
            border-radius: 12px;
        }

        .btn-confirm-cancel {
            background: var(--bg-base);
            color: var(--text-primary);
            border: none;
        }

        .btn-confirm-cancel:hover {
            background: rgba(60, 48, 40, 0.08);
        }

        .btn-confirm-ok {
            border: none;
        }

        .btn-confirm-ok.danger {
            background: var(--primary);
            color: #fff;
        }

        .btn-confirm-ok.danger:hover {
            background: #a33b2d;
        }

        .btn-confirm-ok.warning {
            background: var(--accent);
            color: #2C2420;
        }

        .btn-confirm-ok.info,
        .btn-confirm-ok.success {
            background: #5D8A66;
            color: #fff;
        }

        /* Desktop: modal centré */
        @media (min-width: 576px) {
            .confirm-modal-overlay {
                align-items: center;
                padding: 20px;
            }

            .confirm-modal {
                max-width: 380px;
                border-radius: 16px;
                padding: 28px;
                transform: scale(0.9) translateY(20px);
            }

            .confirm-modal-overlay.show .confirm-modal {
                transform: scale(1) translateY(0);
            }
        }

        /* ============================================
           TOAST NOTIFICATIONS - Mobile-first
           ============================================ */
        .toast-container {
            position: fixed;
            bottom: calc(20px + env(safe-area-inset-bottom));
            left: 16px;
            right: 16px;
            z-index: 9998;
            display: flex;
            flex-direction: column;
            gap: 10px;
            pointer-events: none;
        }

        .toast-item {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 14px 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 12px;
            border-left: 4px solid var(--accent);
            animation: toastSlideIn 0.35s cubic-bezier(0.32, 0.72, 0, 1);
            pointer-events: auto;
        }

        .toast-item.toast-out {
            animation: toastSlideOut 0.25s ease forwards;
        }

        .toast-item.success {
            border-left-color: #5D8A66;
        }

        .toast-item.error {
            border-left-color: var(--primary);
        }

        .toast-item.warning {
            border-left-color: var(--accent);
        }

        .toast-item.info {
            border-left-color: #5B8FB9;
        }

        .toast-icon {
            width: 32px;
            height: 32px;
            min-width: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }

        .toast-item.success .toast-icon {
            background: rgba(93, 138, 102, 0.12);
            color: #5D8A66;
        }

        .toast-item.error .toast-icon {
            background: rgba(183, 65, 51, 0.12);
            color: var(--primary);
        }

        .toast-item.warning .toast-icon {
            background: rgba(214, 164, 72, 0.15);
            color: var(--accent);
        }

        .toast-item.info .toast-icon {
            background: rgba(91, 143, 185, 0.12);
            color: #5B8FB9;
        }

        .toast-content {
            flex: 1;
            min-width: 0;
        }

        .toast-message {
            font-size: 14px;
            font-weight: 500;
            color: var(--text-primary);
            line-height: 1.4;
        }

        .toast-close {
            width: 28px;
            height: 28px;
            min-width: 28px;
            border: none;
            background: transparent;
            color: var(--text-muted);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            font-size: 18px;
            transition: background 0.15s;
        }

        .toast-close:hover {
            background: rgba(60, 48, 40, 0.08);
        }

        @keyframes toastSlideIn {
            from {
                transform: translateY(30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes toastSlideOut {
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        /* Desktop: toasts en haut à droite */
        @media (min-width: 576px) {
            .toast-container {
                top: 20px;
                right: 20px;
                left: auto;
                bottom: auto;
                width: 360px;
            }

            @keyframes toastSlideIn {
                from {
                    transform: translateX(30px);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
        }

        /* ============================================
           BUTTON LOADING STATE
           ============================================ */
        .btn-loading {
            position: relative;
            pointer-events: none;
            opacity: 0.75;
        }

        .btn-loading .btn-text,
        .btn-loading > span:not(.spinner-border),
        .btn-loading > i {
            visibility: hidden;
        }

        .btn-loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 18px;
            height: 18px;
            margin: -9px 0 0 -9px;
            border: 2px solid currentColor;
            border-right-color: transparent;
            border-radius: 50%;
            animation: btnSpin 0.6s linear infinite;
        }

        @keyframes btnSpin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Mobile: spinner plus grand pour meilleure visibilité */
        @media (max-width: 576px) {
            .btn-loading::after {
                width: 20px;
                height: 20px;
                margin: -10px 0 0 -10px;
            }
        }

        /* ============================================
           DISABLED STATES AMÉLIORÉS
           ============================================ */
        .btn:disabled,
        .btn.disabled,
        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }

        /* Mobile: feedback visuel plus clair */
        @media (max-width: 576px) {
            .btn:disabled,
            .btn.disabled {
                background: var(--bg-base) !important;
                color: var(--text-muted) !important;
                border-color: transparent !important;
            }
        }

        .form-control:disabled,
        .form-select:disabled,
        input:disabled,
        textarea:disabled,
        select:disabled {
            background: rgba(60, 48, 40, 0.04) !important;
            color: var(--text-muted) !important;
            cursor: not-allowed;
        }

        /* ============================================
           VALIDATION FEEDBACK
           ============================================ */
        .form-control.is-invalid,
        .form-select.is-invalid {
            border-color: var(--primary) !important;
            box-shadow: 0 0 0 3px rgba(183, 65, 51, 0.1) !important;
        }

        .form-control.is-valid,
        .form-select.is-valid {
            border-color: #5D8A66 !important;
            box-shadow: 0 0 0 3px rgba(93, 138, 102, 0.1) !important;
        }

        .invalid-feedback {
            color: var(--primary);
            font-size: 12px;
            margin-top: 6px;
        }

        .valid-feedback {
            color: #5D8A66;
            font-size: 12px;
            margin-top: 6px;
        }
    </style>

    @stack('styles')
</head>

<body id="kt_app_body" data-kt-app-header-fixed="true" data-kt-app-header-fixed-mobile="true"
    data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" data-kt-app-sidebar-push-header="true"
    data-kt-app-sidebar-push-footer="true" class="app-default">

    <!-- Skip link pour accessibilité clavier -->
    <a href="#main-content" class="skip-link">Aller au contenu principal</a>

    <!-- Particules flottantes animées -->
    <div class="floating-particles" aria-hidden="true"></div>
    <div class="particle-dot" aria-hidden="true"></div>
    <div class="particle-dot" aria-hidden="true"></div>
    <div class="particle-dot" aria-hidden="true"></div>
    <div class="particle-dot" aria-hidden="true"></div>
    <div class="particle-dot" aria-hidden="true"></div>
    <div class="particle-dot" aria-hidden="true"></div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer" role="region" aria-live="polite" aria-label="Notifications"></div>

    <!-- Confirm Modal -->
    <div class="confirm-modal-overlay" id="confirmModal" role="dialog" aria-modal="true" aria-labelledby="confirmModalTitle">
        <div class="confirm-modal">
            <div class="confirm-modal-icon" id="confirmModalIcon" aria-hidden="true">
                <i class="ki-duotone ki-information-2"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
            </div>
            <div class="confirm-modal-title" id="confirmModalTitle">Confirmation</div>
            <div class="confirm-modal-message" id="confirmModalMessage">Êtes-vous sûr de vouloir effectuer cette action ?</div>
            <div class="confirm-modal-actions">
                <button type="button" class="btn btn-confirm-cancel" id="confirmModalCancel">Annuler</button>
                <button type="button" class="btn btn-confirm-ok" id="confirmModalOk">Confirmer</button>
            </div>
        </div>
    </div>

    <script>
        var defaultThemeMode = "light";
        var themeMode = localStorage.getItem("data-bs-theme") || defaultThemeMode;
        document.documentElement.setAttribute("data-bs-theme", themeMode);
    </script>

    <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
        <div class="app-page flex-column flex-column-fluid" id="kt_app_page">

            <!-- Header -->
            @include('components.header')

            <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
                <!-- Sidebar -->
                @include('components.sidebar')

                <!-- Main Content -->
                <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
                    <div class="d-flex flex-column flex-column-fluid">
                        <!-- Toolbar -->
                        <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-5">
                            <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
                                <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                                    <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
                                        @yield('page-title', 'Dashboard')
                                    </h1>
                                    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                        <li class="breadcrumb-item text-muted">
                                            <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">Accueil</a>
                                        </li>
                                        @hasSection('breadcrumb')
                                            <li class="breadcrumb-item">
                                                <span class="bullet bg-gray-400 w-5px h-2px"></span>
                                            </li>
                                            @yield('breadcrumb')
                                        @endif
                                    </ul>
                                </div>
                                <div class="d-flex align-items-center gap-2 gap-lg-3">
                                    @yield('page-actions')
                                </div>
                            </div>
                        </div>

                        <!-- Content -->
                        <div id="kt_app_content" class="app-content flex-column-fluid">
                            <div id="kt_app_content_container" class="app-container container-fluid">
                                <!-- Flash Messages avec ARIA pour accessibilité -->
                                @if(session('success'))
                                    <div class="alert alert-success alert-dismissible fade show mb-5" role="alert" aria-live="polite">
                                        <i class="ki-duotone ki-check-circle fs-2 me-2" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
                                        <span>{{ session('success') }}</span>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer cette notification"></button>
                                    </div>
                                @endif

                                @if(session('error'))
                                    <div class="alert alert-danger alert-dismissible fade show mb-5" role="alert" aria-live="assertive">
                                        <i class="ki-duotone ki-cross-circle fs-2 me-2" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
                                        <span>{{ session('error') }}</span>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer cette notification"></button>
                                    </div>
                                @endif

                                @if($errors->any())
                                    <div class="alert alert-danger alert-dismissible fade show mb-5" role="alert" aria-live="assertive">
                                        <i class="ki-duotone ki-information-2 fs-2 me-2" aria-hidden="true"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                                        <div>
                                            <strong>Veuillez corriger les erreurs suivantes :</strong>
                                            <ul class="mb-0 mt-2">
                                                @foreach($errors->all() as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer cette notification"></button>
                                    </div>
                                @endif

                                <!-- Contenu principal -->
                                <main id="main-content" tabindex="-1">
                                    @yield('content')
                                </main>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div id="kt_app_footer" class="app-footer">
                        <div class="app-container container-fluid d-flex flex-column flex-md-row flex-center flex-md-stack py-3">
                            <div class="text-dark order-2 order-md-1">
                                <span class="text-muted fw-semibold me-1">&copy; {{ date('Y') }}</span>
                                <span class="text-gray-800">ITCJ Community</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Global Javascript -->
    <script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>

    <script>
        // Setup CSRF token for AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // ============================================
        // TOAST NOTIFICATION SYSTEM
        // ============================================
        const Toast = {
            container: null,

            init() {
                this.container = document.getElementById('toastContainer');
            },

            getIcon(type) {
                const icons = {
                    success: '<i class="ki-duotone ki-check-circle"><i class="path1"></i><i class="path2"></i></i>',
                    error: '<i class="ki-duotone ki-cross-circle"><i class="path1"></i><i class="path2"></i></i>',
                    warning: '<i class="ki-duotone ki-information-2"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>',
                    info: '<i class="ki-duotone ki-information"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>'
                };
                return icons[type] || icons.info;
            },

            show(message, type = 'info', duration = 4000) {
                if (!this.container) this.init();

                const toast = document.createElement('div');
                toast.className = `toast-item ${type}`;
                toast.innerHTML = `
                    <div class="toast-icon">${this.getIcon(type)}</div>
                    <div class="toast-content">
                        <div class="toast-message">${message}</div>
                    </div>
                    <button type="button" class="toast-close" aria-label="Fermer">&times;</button>
                `;

                const closeBtn = toast.querySelector('.toast-close');
                closeBtn.addEventListener('click', () => this.dismiss(toast));

                this.container.appendChild(toast);

                if (duration > 0) {
                    setTimeout(() => this.dismiss(toast), duration);
                }

                return toast;
            },

            dismiss(toast) {
                if (!toast || !toast.parentNode) return;
                toast.classList.add('toast-out');
                setTimeout(() => {
                    if (toast.parentNode) toast.remove();
                }, 250);
            },

            success(message, duration) { return this.show(message, 'success', duration); },
            error(message, duration) { return this.show(message, 'error', duration); },
            warning(message, duration) { return this.show(message, 'warning', duration); },
            info(message, duration) { return this.show(message, 'info', duration); }
        };

        // ============================================
        // CONFIRM MODAL SYSTEM
        // ============================================
        const ConfirmModal = {
            overlay: null,
            titleEl: null,
            messageEl: null,
            iconEl: null,
            okBtn: null,
            cancelBtn: null,
            currentCallback: null,

            init() {
                this.overlay = document.getElementById('confirmModal');
                this.titleEl = document.getElementById('confirmModalTitle');
                this.messageEl = document.getElementById('confirmModalMessage');
                this.iconEl = document.getElementById('confirmModalIcon');
                this.okBtn = document.getElementById('confirmModalOk');
                this.cancelBtn = document.getElementById('confirmModalCancel');

                this.cancelBtn.addEventListener('click', () => this.hide(false));
                this.okBtn.addEventListener('click', () => this.hide(true));
                this.overlay.addEventListener('click', (e) => {
                    if (e.target === this.overlay) this.hide(false);
                });

                // Fermer avec Escape
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && this.overlay.classList.contains('show')) {
                        this.hide(false);
                    }
                });
            },

            getIcon(type) {
                const icons = {
                    danger: '<i class="ki-duotone ki-trash"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i><i class="path5"></i></i>',
                    warning: '<i class="ki-duotone ki-information-2"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>',
                    info: '<i class="ki-duotone ki-questionnaire-tablet"><i class="path1"></i><i class="path2"></i></i>',
                    success: '<i class="ki-duotone ki-check-circle"><i class="path1"></i><i class="path2"></i></i>'
                };
                return icons[type] || icons.info;
            },

            show(options) {
                if (!this.overlay) this.init();

                const {
                    title = 'Confirmation',
                    message = 'Êtes-vous sûr de vouloir effectuer cette action ?',
                    type = 'danger',
                    confirmText = 'Confirmer',
                    cancelText = 'Annuler',
                    onConfirm = null
                } = options;

                this.titleEl.textContent = title;
                this.messageEl.textContent = message;
                this.iconEl.innerHTML = this.getIcon(type);
                this.iconEl.className = `confirm-modal-icon ${type}`;
                this.okBtn.textContent = confirmText;
                this.okBtn.className = `btn btn-confirm-ok ${type}`;
                this.cancelBtn.textContent = cancelText;
                this.currentCallback = onConfirm;

                this.overlay.classList.add('show');
                document.body.style.overflow = 'hidden';
                this.okBtn.focus();
            },

            hide(confirmed) {
                this.overlay.classList.remove('show');
                document.body.style.overflow = '';

                if (confirmed && typeof this.currentCallback === 'function') {
                    this.currentCallback();
                }
                this.currentCallback = null;
            }
        };

        // ============================================
        // AUTO-LOADING FOR FORM BUTTONS
        // ============================================
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize systems
            Toast.init();
            ConfirmModal.init();

            // Auto-loading sur les formulaires
            document.querySelectorAll('form').forEach(form => {
                // Skip forms with data-no-loading attribute
                if (form.hasAttribute('data-no-loading')) return;

                form.addEventListener('submit', function(e) {
                    const btn = this.querySelector('[type="submit"]');
                    if (btn && !btn.classList.contains('btn-loading')) {
                        btn.classList.add('btn-loading');
                    }
                });
            });

            // Handle data-confirm attribute on forms
            document.querySelectorAll('form[data-confirm]').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const message = this.getAttribute('data-confirm');
                    const type = this.getAttribute('data-confirm-type') || 'danger';
                    const title = this.getAttribute('data-confirm-title') || 'Confirmation';
                    const confirmText = this.getAttribute('data-confirm-ok') || 'Confirmer';

                    // Remove loading if added
                    const btn = this.querySelector('[type="submit"]');
                    if (btn) btn.classList.remove('btn-loading');

                    ConfirmModal.show({
                        title: title,
                        message: message,
                        type: type,
                        confirmText: confirmText,
                        onConfirm: () => {
                            // Re-add loading and submit
                            if (btn) btn.classList.add('btn-loading');
                            this.removeAttribute('data-confirm');
                            this.submit();
                        }
                    });
                });
            });

            // Handle data-confirm on buttons/links
            document.querySelectorAll('[data-confirm]:not(form)').forEach(el => {
                el.addEventListener('click', function(e) {
                    e.preventDefault();

                    const message = this.getAttribute('data-confirm');
                    const type = this.getAttribute('data-confirm-type') || 'danger';
                    const title = this.getAttribute('data-confirm-title') || 'Confirmation';
                    const confirmText = this.getAttribute('data-confirm-ok') || 'Confirmer';
                    const href = this.getAttribute('href');
                    const form = this.closest('form');

                    ConfirmModal.show({
                        title: title,
                        message: message,
                        type: type,
                        confirmText: confirmText,
                        onConfirm: () => {
                            if (form) {
                                form.submit();
                            } else if (href) {
                                window.location.href = href;
                            }
                        }
                    });
                });
            });

            // Convert flash messages to toasts
            document.querySelectorAll('.alert.alert-success, .alert.alert-danger, .alert.alert-warning, .alert.alert-info').forEach(alert => {
                const text = alert.querySelector('span')?.textContent?.trim() || alert.textContent.trim();
                const type = alert.classList.contains('alert-success') ? 'success' :
                             alert.classList.contains('alert-danger') ? 'error' :
                             alert.classList.contains('alert-warning') ? 'warning' : 'info';

                // Only convert simple flash messages, not error lists
                if (!alert.querySelector('ul') && text) {
                    Toast.show(text, type, 5000);
                    alert.remove();
                }
            });
        });
    </script>

    @stack('scripts')
</body>
</html>



