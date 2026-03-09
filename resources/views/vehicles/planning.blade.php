<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Planning Vehicules - ITCJ Community</title>

    <!-- Fonts - Combinaison élégante -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700;800&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- CSS -->
    <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css">

    <!-- Vite for Echo/Broadcasting -->
    @vite(['resources/js/app.js'])

    <style>
        :root {
            /* Tons chauds ITCJ */
            --primary: #8B2332;
            --primary-light: #A83D4D;
            --primary-dark: #6B1A27;
            --accent: #D4A84B;
            --accent-dark: #B08A3A;
            --secondary: #C4956A;
            --bg-base: #F5F0E8;
            --bg-card: #F8F4ED;
            --text-primary: #3D3028;
            --text-secondary: #6B5D52;
            --text-muted: #8B7D72;

            /* Planning colors */
            --color-disponible: #5D8A66;
            --color-reserve: #C4956A;
            --color-my-reservation: var(--primary);
            --color-primary: var(--primary);
            --sidebar-width: 280px;

            /* Neumorphism shadows */
            --neu-shadow-sm: 3px 3px 6px rgba(60, 50, 40, 0.1), -3px -3px 6px rgba(255, 255, 255, 0.8);
            --neu-shadow-inset: inset 2px 2px 4px rgba(60, 50, 40, 0.1), inset -2px -2px 4px rgba(255, 255, 255, 0.7);
        }

        body {
            background: var(--bg-base);
            overflow: hidden;
        }

        @media (max-width: 768px) {
            body {
                overflow: visible;
                overflow-x: hidden;
            }
        }

        /* Layout */
        .planning-layout {
            display: flex;
            height: 100vh;
            height: 100dvh;
        }

        /* Sidebar - Neumorphism crème */
        .planning-sidebar {
            width: var(--sidebar-width);
            min-width: var(--sidebar-width);
            background: linear-gradient(180deg, #F0E8DC 0%, #E8E0D4 100%);
            color: var(--text-primary);
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            box-shadow: 4px 0 16px rgba(60, 50, 40, 0.08);
        }

        .sidebar-header {
            padding: 24px 20px;
            border-bottom: 1px solid rgba(60, 48, 40, 0.1);
            background: rgba(255, 255, 255, 0.3);
        }

        .sidebar-header h1 {
            font-size: 17px;
            font-weight: 700;
            margin: 0;
            color: var(--text-primary);
        }

        .sidebar-header small {
            color: var(--primary);
            font-size: 12px;
            font-weight: 500;
        }

        .sidebar-content {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }

        .sidebar-section {
            margin-bottom: 24px;
        }

        .sidebar-section-title {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: var(--primary);
            margin-bottom: 12px;
            font-weight: 700;
        }

        .sidebar-footer {
            padding: 16px 20px;
            border-top: 1px solid rgba(60, 48, 40, 0.08);
            background: rgba(255, 255, 255, 0.2);
        }

        /* User info */
        .user-badge {
            display: flex;
            align-items: center;
            padding: 14px;
            background: rgba(255,255,255,0.5);
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 3px 3px 8px rgba(60, 50, 40, 0.1),
                        -3px -3px 8px rgba(255, 255, 255, 0.8);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 15px;
            margin-right: 12px;
            color: #fff;
        }

        .user-name {
            font-weight: 600;
            font-size: 14px;
            color: var(--text-primary);
        }

        .user-role {
            font-size: 11px;
            color: var(--text-secondary);
        }

        /* Date input */
        .date-input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid rgba(60, 48, 40, 0.12);
            border-radius: 10px;
            background: #fff;
            color: var(--text-primary);
            font-size: 16px;
            min-height: 44px;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            box-shadow: inset 2px 2px 4px rgba(60, 50, 40, 0.06);
        }

        .date-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: inset 2px 2px 4px rgba(60, 50, 40, 0.06),
                        0 0 0 3px rgba(139, 35, 50, 0.1);
        }

        /* Action buttons */
        .btn-action {
            width: 100%;
            padding: 12px 16px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s ease;
            margin-bottom: 8px;
            box-shadow: 3px 3px 8px rgba(60, 50, 40, 0.12),
                        -2px -2px 6px rgba(255, 255, 255, 0.7);
        }

        .btn-action:hover {
            transform: translateY(-1px);
            box-shadow: 4px 4px 10px rgba(60, 50, 40, 0.15),
                        -3px -3px 8px rgba(255, 255, 255, 0.8);
        }

        .btn-action:active {
            transform: scale(0.98);
        }

        .btn-reserve {
            background: var(--primary);
            color: #fff;
        }

        /* Stats */
        .stat-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid rgba(60, 48, 40, 0.06);
        }

        .stat-item:last-child {
            border-bottom: none;
        }

        .stat-label {
            font-size: 12px;
            color: var(--text-secondary);
        }

        .stat-value {
            font-size: 20px;
            font-weight: 700;
        }

        .stat-value.disponible { color: var(--color-disponible); }
        .stat-value.reservations { color: var(--primary); }

        /* Legend */
        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 12px;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }

        .legend-dot {
            width: 14px;
            height: 14px;
            border-radius: 4px;
        }

        .legend-dot.disponible { background: var(--color-disponible); }
        .legend-dot.reserve { background: var(--color-reserve); }
        .legend-dot.my-reservation { background: var(--color-my-reservation); }

        /* Main content */
        .planning-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background: var(--bg-base);
        }

        /* Header - Neumorphism */
        .planning-header {
            padding: 18px 28px;
            background: var(--bg-card);
            border-bottom: none;
            box-shadow: 0 2px 8px rgba(60, 50, 40, 0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .planning-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
            font-family: 'Nunito', sans-serif;
        }

        .planning-title .date {
            color: var(--primary);
        }

        .planning-controls {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .search-input {
            padding: 10px 14px 10px 40px;
            border: 1px solid rgba(60, 48, 40, 0.12);
            border-radius: 10px;
            font-size: 14px;
            width: 220px;
            background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%236B5D52' viewBox='0 0 16 16'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z'/%3E%3C/svg%3E") no-repeat 14px center;
            box-shadow: inset 2px 2px 4px rgba(60, 50, 40, 0.06);
            color: var(--text-primary);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: inset 2px 2px 4px rgba(60, 50, 40, 0.06),
                        0 0 0 3px rgba(139, 35, 50, 0.1);
        }

        .filter-select {
            padding: 10px 14px;
            border: 1px solid rgba(60, 48, 40, 0.12);
            border-radius: 10px;
            font-size: 14px;
            background: #fff;
            cursor: pointer;
            color: var(--text-primary);
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--primary);
        }

        .btn-refresh {
            padding: 10px 18px;
            border: none;
            border-radius: 10px;
            background: var(--bg-card);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            color: var(--text-primary);
            box-shadow: 3px 3px 8px rgba(60, 50, 40, 0.1),
                        -2px -2px 6px rgba(255, 255, 255, 0.7);
        }

        .btn-refresh:hover {
            background: #fff;
            transform: translateY(-1px);
            box-shadow: 4px 4px 10px rgba(60, 50, 40, 0.12),
                        -3px -3px 8px rgba(255, 255, 255, 0.8);
        }

        /* Planning container - Neumorphism */
        .planning-container {
            flex: 1;
            overflow: hidden;
            position: relative;
            background: var(--bg-card);
            margin: 20px 28px;
            border-radius: 16px;
            box-shadow: 6px 6px 12px rgba(60, 50, 40, 0.1),
                        -6px -6px 12px rgba(255, 255, 255, 0.8);
        }

        /* Table wrapper for scroll */
        .table-wrapper {
            height: 100%;
            overflow: auto;
        }

        /* Planning table */
        .planning-table {
            border-collapse: separate;
            border-spacing: 0;
            width: max-content;
            min-width: 100%;
            font-family: 'Nunito', sans-serif;
        }

        .planning-table th,
        .planning-table td {
            border: 1px solid rgba(60, 48, 40, 0.1);
            text-align: center;
            vertical-align: middle;
        }

        /* Fixed header - Tons chauds */
        .planning-table thead {
            position: sticky;
            top: 0;
            z-index: 20;
        }

        .planning-table thead th {
            background: linear-gradient(135deg, #2C2420 0%, #1a1512 100%);
            color: #fff;
            font-weight: 600;
            font-size: 12px;
            padding: 14px 10px;
            min-width: 110px;
            max-width: 130px;
            white-space: nowrap;
        }

        .planning-table thead th .vehicle-plate {
            font-size: 10px;
            font-weight: 400;
            opacity: 0.7;
            display: block;
            margin-top: 3px;
            color: var(--accent);
        }

        /* Fixed first column (hours) */
        .planning-table th:first-child,
        .planning-table td:first-child {
            position: sticky;
            left: 0;
            z-index: 10;
            min-width: 65px;
            width: 65px;
        }

        .planning-table thead th:first-child {
            z-index: 30;
            background: linear-gradient(135deg, #2C2420 0%, #1a1512 100%);
        }

        .planning-table tbody td:first-child {
            background: var(--bg-card);
            font-weight: 700;
            font-size: 12px;
            color: var(--text-secondary);
        }

        /* Table cells */
        .planning-table tbody td {
            padding: 0;
            height: 48px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        /* Cell states - Tons chauds */
        .cell-disponible {
            background: var(--color-disponible);
            color: #fff;
        }

        .cell-disponible:hover {
            background: #4A7553;
            box-shadow: inset 0 0 0 2px rgba(255,255,255,0.2);
        }

        .cell-reserve {
            background: rgba(196, 149, 106, 0.2);
            color: var(--text-secondary);
        }

        .cell-reserve:hover {
            background: rgba(196, 149, 106, 0.3);
        }

        .cell-reserve.mine {
            background: var(--primary);
            color: #fff;
        }

        .cell-reserve.mine:hover {
            background: var(--primary-dark);
            box-shadow: inset 0 0 0 2px rgba(255,255,255,0.2);
        }

        .cell-disabled {
            background: rgba(60, 48, 40, 0.04);
            cursor: not-allowed;
        }

        .cell-disabled::after {
            content: '';
            display: block;
            width: 100%;
            height: 100%;
            background: repeating-linear-gradient(
                45deg,
                transparent,
                transparent 4px,
                rgba(60, 48, 40, 0.04) 4px,
                rgba(60, 48, 40, 0.04) 8px
            );
        }

        /* Cell content */
        .cell-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            padding: 4px;
            font-size: 11px;
            line-height: 1.3;
        }

        .cell-content .status-text {
            font-weight: 700;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .cell-content .driver-name {
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 90px;
        }

        .cell-content .destination-text {
            font-size: 9px;
            opacity: 0.85;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 90px;
        }

        /* Loading overlay */
        .loading-overlay {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            background: rgba(255,255,255,0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 50;
        }

        .loading-overlay.hidden {
            display: none;
        }

        /* Vehicle counter badge */
        .vehicle-count-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            background: rgba(255,255,255,0.1);
            border-radius: 20px;
            font-size: 11px;
            color: #a0a3bd;
        }

        .vehicle-count-badge strong {
            color: #fff;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c7293;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        /* Responsive Desktop/Tablet */
        @media (max-width: 991px) and (min-width: 769px) {
            .planning-layout {
                flex-direction: column;
            }

            .planning-sidebar {
                width: 100%;
                min-width: 100%;
                height: auto;
                max-height: 40vh;
            }

            .planning-header {
                padding: 12px 16px;
            }

            .planning-container {
                margin: 12px 16px;
            }

            .search-input {
                width: 150px;
            }
        }

        /* ==================== MOBILE VIEW ==================== */
        .mobile-planning {
            display: none;
            flex-direction: column;
            min-height: 100vh;
            min-height: 100dvh; /* Dynamic viewport height for mobile browsers */
            background: var(--bg-base);
            overflow-x: hidden;
            width: 100%;
        }

        /* Hide scrollbar on mobile */
        .mobile-planning-content::-webkit-scrollbar {
            display: none;
        }
        .mobile-planning-content {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        @media (max-width: 768px) {
            .planning-sidebar,
            .planning-main {
                display: none !important;
            }

            .mobile-planning {
                display: flex;
            }
        }

        /* Mobile Header */
        .mobile-planning-header {
            position: sticky;
            top: 0;
            z-index: 100;
            background: var(--bg-card);
            padding: 12px 16px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            gap: 8px 12px;
        }

        .mobile-planning-header .btn-back {
            width: 40px;
            height: 40px;
            min-width: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            border: none;
            color: var(--text-primary);
            font-size: 20px;
            text-decoration: none;
        }

        .mobile-planning-title {
            flex: 1;
            font-size: 17px;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Mobile Date Navigator */
        .mobile-date-nav {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-left: auto;
        }

        .mobile-date-nav-btn {
            width: 36px;
            height: 36px;
            min-width: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(139, 35, 50, 0.08);
            border: none;
            color: var(--primary);
            font-size: 16px;
            cursor: pointer;
            transition: background 0.15s;
        }

        .mobile-date-nav-btn:active {
            background: rgba(139, 35, 50, 0.2);
            transform: scale(0.92);
        }

        .mobile-date-nav-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }

        .mobile-date-label {
            position: relative;
            padding: 6px 12px;
            background: rgba(139, 35, 50, 0.1);
            border-radius: 10px;
            font-weight: 600;
            font-size: 13px;
            color: var(--primary);
            cursor: pointer;
            min-width: 100px;
            text-align: center;
            overflow: hidden;
        }

        .mobile-date-label span {
            position: relative;
            z-index: 1;
            pointer-events: none;
        }

        /* Date input overlaid on the label — native picker opens on tap */
        .mobile-date-label input[type="date"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
            min-height: unset;
            padding: 0;
            margin: 0;
            border: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            font-size: 16px;
        }

        .mobile-date-today {
            padding: 6px 10px;
            border-radius: 10px;
            background: var(--primary);
            color: #fff;
            border: none;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
            transition: opacity 0.15s;
        }

        .mobile-date-today:active {
            opacity: 0.8;
        }

        .mobile-date-today.hidden {
            display: none;
        }

        /* Mobile Search */
        .mobile-planning-search {
            padding: 12px 16px;
            background: var(--bg-base);
        }

        .mobile-planning-search input {
            width: 100%;
            padding: 12px 16px 12px 44px;
            border: none;
            border-radius: 12px;
            background: var(--bg-card);
            font-size: 15px;
            color: var(--text-primary);
            box-shadow: var(--neu-shadow-sm);
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' fill='%236B5D52' viewBox='0 0 16 16'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: 14px center;
        }

        .mobile-planning-search input:focus {
            outline: none;
            box-shadow: var(--neu-shadow-sm), 0 0 0 3px rgba(139, 35, 50, 0.1);
        }

        /* Mobile Content */
        .mobile-planning-content {
            flex: 1;
            overflow-y: auto;
            padding-bottom: 20px;
            -webkit-overflow-scrolling: touch;
        }

        /* Vehicle Cards (Liste) */
        .vehicle-card-mobile {
            background: var(--bg-card);
            border-radius: 14px;
            padding: 16px;
            margin: 12px 16px;
            box-shadow: var(--neu-shadow-sm);
            display: flex;
            align-items: center;
            gap: 14px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            color: inherit;
        }

        .vehicle-card-mobile:active {
            transform: scale(0.98);
            box-shadow: var(--neu-shadow-inset);
        }

        .vehicle-card-mobile-icon {
            width: 52px;
            height: 52px;
            min-width: 52px;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        .vehicle-card-mobile-info {
            flex: 1;
            min-width: 0;
        }

        .vehicle-card-mobile-name {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 2px;
        }

        .vehicle-card-mobile-plate {
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        .vehicle-card-mobile-status {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 600;
        }

        .vehicle-card-mobile-status.disponible {
            color: var(--color-disponible);
        }

        .vehicle-card-mobile-status.indisponible {
            color: var(--color-reserve);
        }

        .vehicle-card-mobile-arrow {
            color: var(--text-muted);
            font-size: 18px;
        }

        /* Time Slots (Planning véhicule) */
        .mobile-vehicle-detail-header {
            padding: 16px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #fff;
        }

        .mobile-vehicle-detail-name {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .mobile-vehicle-detail-plate {
            font-size: 13px;
            opacity: 0.8;
        }

        .mobile-date-display {
            padding: 12px 16px;
            background: var(--bg-card);
            font-size: 14px;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: capitalize;
            border-bottom: 1px solid rgba(60, 48, 40, 0.08);
        }

        .time-slot-mobile {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 14px 16px;
            margin: 8px 16px;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: var(--neu-shadow-sm);
            cursor: pointer;
            transition: all 0.2s;
        }

        .time-slot-mobile:active {
            transform: scale(0.98);
        }

        .time-slot-mobile.disponible {
            border-left: 4px solid var(--color-disponible);
        }

        .time-slot-mobile.reserve {
            border-left: 4px solid var(--color-reserve);
            cursor: default;
        }

        .time-slot-mobile.mine {
            border-left: 4px solid var(--primary);
            background: rgba(139, 35, 50, 0.04);
        }

        .time-slot-mobile-time {
            font-size: 15px;
            font-weight: 700;
            color: var(--text-primary);
            min-width: 55px;
        }

        .time-slot-mobile-info {
            flex: 1;
        }

        .time-slot-mobile-status {
            font-size: 13px;
            font-weight: 600;
        }

        .time-slot-mobile.disponible .time-slot-mobile-status {
            color: var(--color-disponible);
        }

        .time-slot-mobile-driver {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .time-slot-mobile-destination {
            font-size: 12px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 4px;
            margin-top: 2px;
        }

        .time-slot-mobile-actions {
            display: flex;
            gap: 8px;
        }

        .time-slot-mobile-actions .btn {
            padding: 6px 12px;
            font-size: 12px;
            min-height: 32px;
        }

        /* Mobile Legend */
        .mobile-legend {
            padding: 16px;
            background: var(--bg-card);
            margin: 16px;
            border-radius: 12px;
            box-shadow: var(--neu-shadow-sm);
        }

        .mobile-legend-title {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            margin-bottom: 12px;
        }

        .mobile-legend-items {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
        }

        .mobile-legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--text-secondary);
        }

        .mobile-legend-dot {
            width: 12px;
            height: 12px;
            border-radius: 3px;
        }

        .mobile-legend-dot.disponible { background: var(--color-disponible); }
        .mobile-legend-dot.reserve { background: var(--color-reserve); }
        .mobile-legend-dot.mine { background: var(--primary); }

        /* Mobile Stats */
        .mobile-stats {
            display: flex;
            gap: 12px;
            padding: 0 16px;
            margin-bottom: 8px;
        }

        .mobile-stat-item {
            flex: 1;
            background: var(--bg-card);
            border-radius: 12px;
            padding: 14px;
            text-align: center;
            box-shadow: var(--neu-shadow-sm);
        }

        .mobile-stat-value {
            font-size: 24px;
            font-weight: 800;
            color: var(--primary);
        }

        .mobile-stat-label {
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Date/Time inputs - Mobile compatibility */
        input[type="date"],
        input[type="time"],
        input[type="datetime-local"] {
            font-size: 16px;
            min-height: 44px;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }

        @media (max-width: 768px) {
            input[type="date"],
            input[type="time"],
            input[type="datetime-local"] {
                min-height: 48px;
                padding: 12px 14px;
                font-size: 16px;
                line-height: 1.5;
                cursor: pointer;
            }

            input[type="date"]::-webkit-calendar-picker-indicator,
            input[type="time"]::-webkit-calendar-picker-indicator,
            input[type="datetime-local"]::-webkit-calendar-picker-indicator {
                padding: 8px;
                margin: 0;
                cursor: pointer;
                opacity: 0.6;
            }
        }

        /* Hidden date input (legacy, kept for desktop sidebar sync) */

        /* Scrollbar styling */
        .table-wrapper::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .table-wrapper::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .table-wrapper::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        .table-wrapper::-webkit-scrollbar-thumb:hover {
            background: #a1a1a1;
        }

        .sidebar-content::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar-content::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar-content::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.2);
            border-radius: 2px;
        }
    </style>
</head>
<body>
    <!-- Skip link pour accessibilité -->
    <a href="#planning-table" class="skip-link" style="position:absolute;top:-100px;left:0;background:var(--primary);color:#fff;padding:12px 24px;z-index:9999;border-radius:0 0 12px 0;text-decoration:none;transition:top 0.2s;" onfocus="this.style.top='0'" onblur="this.style.top='-100px'">
        Aller au planning
    </a>

    <div class="planning-layout">
        <!-- ==================== MOBILE VIEW ==================== -->
        <div class="mobile-planning" id="mobile-planning">
            <!-- Mobile Header -->
            <header class="mobile-planning-header">
                <a href="{{ route('dashboard') }}" class="btn-back" aria-label="Retour au dashboard">
                    <i class="ki-duotone ki-arrow-left"><i class="path1"></i><i class="path2"></i></i>
                </a>
                <h1 class="mobile-planning-title" id="mobile-title">Planning Véhicules</h1>
                <nav class="mobile-date-nav" aria-label="Navigation par date">
                    <button type="button" class="mobile-date-nav-btn" id="mobile-date-prev" aria-label="Jour précédent">
                        <i class="ki-duotone ki-arrow-left"><i class="path1"></i><i class="path2"></i></i>
                    </button>
                    <div class="mobile-date-label" id="mobile-date-label">
                        <span id="mobile-date-display">Aujourd'hui</span>
                        <input type="date" id="mobile-date-input" value="{{ now()->toDateString() }}" aria-label="Choisir une date">
                    </div>
                    <button type="button" class="mobile-date-nav-btn" id="mobile-date-next" aria-label="Jour suivant">
                        <i class="ki-duotone ki-arrow-right"><i class="path1"></i><i class="path2"></i></i>
                    </button>
                    <button type="button" class="mobile-date-today hidden" id="mobile-date-today" aria-label="Revenir à aujourd'hui">Auj.</button>
                </nav>
            </header>

            <!-- Mobile Search -->
            <div class="mobile-planning-search">
                <input type="text"
                       id="mobile-search"
                       placeholder="Rechercher un véhicule..."
                       aria-label="Rechercher un véhicule">
            </div>

            <!-- Mobile Stats -->
            <div class="mobile-stats">
                <div class="mobile-stat-item">
                    <div class="mobile-stat-value" id="mobile-stat-vehicles">{{ $vehicles->count() }}</div>
                    <div class="mobile-stat-label">Véhicules</div>
                </div>
                <div class="mobile-stat-item">
                    <div class="mobile-stat-value" id="mobile-stat-reservations">-</div>
                    <div class="mobile-stat-label">Réservations</div>
                </div>
            </div>

            <!-- Mobile Content -->
            <div class="mobile-planning-content" id="mobile-content">
                <!-- Vue Liste Véhicules (par défaut) -->
                <div id="mobile-vehicles-list">
                    @foreach($vehicles as $vehicle)
                        @if(auth()->user()->canAccessVehicle($vehicle))
                        <div class="vehicle-card-mobile"
                             data-vehicle-id="{{ $vehicle->id }}"
                             data-vehicle-name="{{ $vehicle->name }}"
                             data-vehicle-plate="{{ $vehicle->license_plate }}"
                             onclick="MobilePlanning.showVehicleDetail({{ $vehicle->id }})">
                            <div class="vehicle-card-mobile-icon">
                                <i class="ki-duotone ki-car-2"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i><i class="path5"></i></i>
                            </div>
                            <div class="vehicle-card-mobile-info">
                                <div class="vehicle-card-mobile-name">{{ $vehicle->name }}</div>
                                <div class="vehicle-card-mobile-plate">{{ $vehicle->license_plate }}</div>
                                <div class="vehicle-card-mobile-status disponible" id="mobile-vehicle-status-{{ $vehicle->id }}">
                                    <i class="ki-duotone ki-check-circle"><i class="path1"></i><i class="path2"></i></i>
                                    <span>Chargement...</span>
                                </div>
                            </div>
                            <div class="vehicle-card-mobile-arrow">
                                <i class="ki-duotone ki-arrow-right"><i class="path1"></i><i class="path2"></i></i>
                            </div>
                        </div>
                        @endif
                    @endforeach

                    <!-- Mobile Legend (inside scrollable content) -->
                    <div class="mobile-legend" id="mobile-legend-inline">
                        <div class="mobile-legend-title">Légende</div>
                        <div class="mobile-legend-items">
                            <div class="mobile-legend-item">
                                <span class="mobile-legend-dot disponible"></span>
                                <span>Disponible</span>
                            </div>
                            <div class="mobile-legend-item">
                                <span class="mobile-legend-dot reserve"></span>
                                <span>Réservé</span>
                            </div>
                            <div class="mobile-legend-item">
                                <span class="mobile-legend-dot mine"></span>
                                <span>Ma réservation</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Vue Détail Véhicule (créneaux horaires) -->
                <div id="mobile-vehicle-detail" style="display: none;">
                    <div class="mobile-vehicle-detail-header">
                        <div class="mobile-vehicle-detail-name" id="detail-vehicle-name"></div>
                        <div class="mobile-vehicle-detail-plate" id="detail-vehicle-plate"></div>
                    </div>
                    <div class="mobile-date-display" id="detail-date-display"></div>
                    <div id="mobile-time-slots">
                        <!-- Créneaux générés par JS -->
                    </div>
                </div>
            </div>

        </div>

        <!-- ==================== DESKTOP VIEW ==================== -->
        <!-- Sidebar -->
        <aside class="planning-sidebar" role="complementary" aria-label="Panneau de contrôle du planning">
            <div class="sidebar-header">
                <h1>Gestion Parc Auto</h1>
                <small>ITCJ Community</small>
            </div>

            <div class="sidebar-content">
                <div class="user-badge" aria-label="Utilisateur connecté">
                    <div class="user-avatar" aria-hidden="true">{{ substr(auth()->user()->name, 0, 1) }}</div>
                    <div>
                        <div class="user-name">{{ auth()->user()->name }}</div>
                        <div class="user-role">{{ auth()->user()->role_label }}</div>
                    </div>
                </div>

                <div class="sidebar-section">
                    <label for="planning-date" class="sidebar-section-title">Date</label>
                    <input type="date"
                           id="planning-date"
                           class="date-input"
                           value="{{ now()->toDateString() }}"
                           aria-describedby="date-help">
                    <span id="date-help" class="visually-hidden">Sélectionnez une date pour voir le planning</span>
                </div>

                <section class="sidebar-section" aria-labelledby="stats-heading">
                    <h2 id="stats-heading" class="sidebar-section-title">Statistiques</h2>
                    <dl>
                        <div class="stat-item">
                            <dt class="stat-label">Total véhicules</dt>
                            <dd class="stat-value disponible" id="stat-total" aria-live="polite">-</dd>
                        </div>
                        <div class="stat-item">
                            <dt class="stat-label">Réservations du jour</dt>
                            <dd class="stat-value reservations" id="stat-reservations" aria-live="polite">-</dd>
                        </div>
                    </dl>
                </section>

                <section class="sidebar-section" aria-labelledby="legend-heading">
                    <h2 id="legend-heading" class="sidebar-section-title">Légende</h2>
                    <ul class="list-unstyled" role="list">
                        <li class="legend-item">
                            <span class="legend-dot disponible" aria-hidden="true"></span>
                            <span>Disponible (cliquez pour réserver)</span>
                        </li>
                        <li class="legend-item">
                            <span class="legend-dot reserve" aria-hidden="true"></span>
                            <span>Réservé (autre membre)</span>
                        </li>
                        <li class="legend-item">
                            <span class="legend-dot my-reservation" aria-hidden="true"></span>
                            <span>Ma réservation</span>
                        </li>
                    </ul>
                </section>
            </div>

            <div class="sidebar-footer">
                <a href="{{ route('dashboard') }}"
                   class="btn btn-sm btn-light w-100"
                   aria-label="Retourner au tableau de bord">
                    <i class="ki-duotone ki-arrow-left fs-4 me-1" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
                    Retour au Dashboard
                </a>
            </div>
        </aside>

        <!-- Main -->
        <main class="planning-main" role="main" aria-label="Planning des véhicules">
            <header class="planning-header">
                <div>
                    <h2 class="planning-title">
                        Planning du <span class="date" id="date-display">{{ now()->format('d/m/Y') }}</span>
                    </h2>
                    <span class="vehicle-count-badge" role="status" aria-live="polite">
                        <strong id="vehicle-count">{{ $vehicles->count() }}</strong> véhicules
                    </span>
                </div>
                <div class="planning-controls" role="search" aria-label="Filtres du planning">
                    <label for="search-vehicle" class="visually-hidden">Rechercher un véhicule</label>
                    <input type="text"
                           class="search-input"
                           id="search-vehicle"
                           placeholder="Rechercher..."
                           aria-describedby="search-help">
                    <span id="search-help" class="visually-hidden">Tapez pour filtrer les véhicules par nom ou immatriculation</span>

                    <label for="filter-status" class="visually-hidden">Filtrer par disponibilité</label>
                    <select class="filter-select" id="filter-status" aria-label="Filtrer les véhicules">
                        <option value="all">Tous les véhicules</option>
                        <option value="available">Disponibles uniquement</option>
                    </select>

                    <button type="button"
                            class="btn-refresh"
                            id="btn-refresh"
                            aria-label="Actualiser le planning"
                            data-tooltip="Recharger les données">
                        <i class="ki-duotone ki-arrows-circle fs-4" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
                        Actualiser
                    </button>
                </div>
            </header>

            <div class="planning-container" role="region" aria-label="Grille du planning">
                <div class="loading-overlay" id="loading-overlay" role="status" aria-live="polite">
                    <div class="text-center">
                        <div class="spinner-border text-primary" aria-hidden="true"></div>
                        <p class="mt-2 text-muted small">Chargement du planning...</p>
                    </div>
                </div>

                <div class="table-wrapper" tabindex="0" aria-label="Grille de réservation. Utilisez les flèches pour naviguer.">
                    <table class="planning-table"
                           id="planning-table"
                           role="grid"
                           aria-label="Planning des réservations de véhicules">
                        <caption class="visually-hidden">
                            Planning des véhicules pour le <span id="caption-date">{{ now()->format('d/m/Y') }}</span>.
                            Utilisez les flèches pour naviguer entre les cellules, Entrée ou Espace pour réserver.
                        </caption>
                        <thead>
                            <tr id="table-header" role="row">
                                <th scope="col">Heure</th>
                            </tr>
                        </thead>
                        <tbody id="table-body">
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Details -->
    <div class="modal fade"
         id="detailsModal"
         tabindex="-1"
         role="dialog"
         aria-labelledby="details-modal-title"
         aria-modal="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="details-modal-title">Détails de la réservation</h5>
                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Fermer les détails"></button>
                </div>
                <div class="modal-body">
                    <dl>
                        <div class="mb-3">
                            <dt class="text-muted small">Conducteur</dt>
                            <dd class="fw-semibold mb-0" id="detail-user">-</dd>
                        </div>
                        <div class="mb-3">
                            <dt class="text-muted small">Véhicule</dt>
                            <dd class="fw-semibold mb-0" id="detail-vehicle">-</dd>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <dt class="text-muted small">Départ</dt>
                                <dd class="fw-semibold mb-0" id="detail-start">-</dd>
                            </div>
                            <div class="col-6">
                                <dt class="text-muted small">Retour</dt>
                                <dd class="fw-semibold mb-0" id="detail-end">-</dd>
                            </div>
                        </div>
                        <div class="mb-3" id="detail-destination-container">
                            <dt class="text-muted small">Destination</dt>
                            <dd class="fw-semibold mb-0" id="detail-destination">-</dd>
                        </div>
                        <div id="detail-notes-container">
                            <dt class="text-muted small">Notes</dt>
                            <dd class="mb-0" id="detail-notes">-</dd>
                        </div>
                    </dl>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>

    <script>
        $.ajaxSetup({
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        });

        const Planning = {
            vehicles: @json($vehicles),
            accessibleVehicles: @json($vehicles->filter(fn($v) => auth()->user()->canAccessVehicle($v))->pluck('id')),
            reservations: [],
            currentUserId: {{ auth()->id() }},
            selectedDate: '{{ now()->toDateString() }}',
            pollingInterval: null,
            isSubmitting: false,
            searchFilter: '',
            statusFilter: 'all',
            // Vehicle settings
            settings: {
                openingHour: {{ $settings->opening_hour_int }},
                closingHour: {{ $settings->closing_hour_int }},
                openingHourFormatted: '{{ $settings->opening_hour_formatted }}',
                closingHourFormatted: '{{ $settings->closing_hour_formatted }}',
                maxFutureDays: {{ $settings->max_future_days }},
                maxReservationDate: '{{ $settings->max_reservation_date }}',
            },

            init() {
                this.bindEvents();
                this.loadData();
                this.startPolling();
                // Set max date on date picker
                $('#planning-date').attr('max', this.settings.maxReservationDate);
            },

            bindEvents() {
                $('#planning-date').on('change', () => {
                    this.selectedDate = $('#planning-date').val();
                    this.updateDateDisplay();
                    this.loadData();
                });

                $('#search-vehicle').on('input', () => {
                    this.searchFilter = $('#search-vehicle').val().toLowerCase();
                    this.renderTable();
                });

                $('#filter-status').on('change', () => {
                    this.statusFilter = $('#filter-status').val();
                    this.renderTable();
                });

                $('#btn-refresh').on('click', () => this.loadData());
            },

            updateDateDisplay() {
                const date = new Date(this.selectedDate);
                $('#date-display').text(date.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' }));
            },

            loadData() {
                $('#loading-overlay').removeClass('hidden');

                $.when(
                    $.get('/api/v1/reservations', { date: this.selectedDate }),
                    $.get('/api/v1/vehicles/stats', { date: this.selectedDate })
                ).done((reservationsRes, statsRes) => {
                    this.reservations = reservationsRes[0];
                    this.updateStats(statsRes[0]);
                    this.renderTable();
                    $('#loading-overlay').addClass('hidden');
                }).fail((error) => {
                    console.error('Error:', error);
                    Swal.fire('Erreur', 'Impossible de charger le planning', 'error');
                    $('#loading-overlay').addClass('hidden');
                });
            },

            updateStats(stats) {
                $('#stat-total').text(stats.total_vehicles || 0);
                $('#stat-reservations').text(stats.reservations_today || 0);
            },

            renderTable() {
                let filteredVehicles = this.vehicles.filter(v => {
                    if (this.searchFilter) {
                        return v.name.toLowerCase().includes(this.searchFilter) ||
                               v.license_plate.toLowerCase().includes(this.searchFilter);
                    }
                    return true;
                });

                // Update count
                $('#vehicle-count').text(filteredVehicles.length);

                // Header with proper accessibility
                let headerHtml = '<th scope="col">Heure</th>';
                filteredVehicles.forEach(v => {
                    const accessible = this.accessibleVehicles.includes(v.id);
                    headerHtml += `<th scope="col" style="${accessible ? '' : 'opacity:0.5'}" ${!accessible ? 'aria-label="' + v.name + ' - Non accessible"' : ''}>
                        ${v.name}
                        <span class="vehicle-plate">${v.license_plate}</span>
                    </th>`;
                });
                $('#table-header').html(headerHtml);

                // Body with accessible cells
                let bodyHtml = '';
                const today = new Date().toISOString().split('T')[0];
                const currentHour = new Date().getHours();
                const isToday = this.selectedDate === today;
                const isPastDate = this.selectedDate < today;
                const isFutureDate = this.selectedDate > this.settings.maxReservationDate;

                // Use settings for hour range
                const startHour = this.settings.openingHour;
                const endHour = this.settings.closingHour;

                for (let hour = startHour; hour <= endHour; hour++) {
                    const timeStr = String(hour).padStart(2, '0') + ':00';
                    const isPastHour = isPastDate || isFutureDate || (isToday && hour <= currentHour);
                    bodyHtml += '<tr role="row">';
                    bodyHtml += `<th scope="row">${timeStr}</th>`;

                    filteredVehicles.forEach((v, colIndex) => {
                        const accessible = this.accessibleVehicles.includes(v.id);
                        const reservation = this.findReservation(v.id, hour);

                        if (!accessible) {
                            bodyHtml += `<td class="cell-disabled" role="gridcell" aria-label="${v.name} à ${timeStr} - Non accessible"></td>`;
                        } else if (reservation) {
                            const isMine = reservation.user_id === this.currentUserId;
                            const cellClass = isMine ? 'cell-reserve mine' : 'cell-reserve';
                            const content = `<span class="driver-name">${reservation.user_name}</span>
                                           ${reservation.destination ? `<span class="destination-text">${reservation.destination}</span>` : ''}`;
                            const ariaLabel = isMine
                                ? `${v.name} à ${timeStr} - Votre réservation. Appuyez sur Entrée pour modifier.`
                                : `${v.name} à ${timeStr} - Réservé par ${reservation.user_name}. Appuyez sur Entrée pour voir les détails.`;

                            bodyHtml += `<td class="${cellClass}"
                                role="gridcell"
                                tabindex="0"
                                aria-label="${ariaLabel}"
                                data-reservation='${JSON.stringify(reservation).replace(/'/g, "&#39;")}'
                                data-vehicle-id="${v.id}"
                                data-hour="${hour}"
                                onclick="Planning.showDetails(this)"
                                onkeydown="Planning.handleCellKeydown(event, this)">
                                <div class="cell-content">${content}</div>
                            </td>`;
                        } else if (isPastHour) {
                            // Créneau passé - désactivé
                            bodyHtml += `<td class="cell-disabled" role="gridcell" aria-label="${v.name} à ${timeStr} - Créneau passé"></td>`;
                        } else {
                            const ariaLabel = `${v.name} à ${timeStr} - Disponible. Appuyez sur Entrée ou Espace pour réserver.`;
                            bodyHtml += `<td class="cell-disponible"
                                role="gridcell"
                                tabindex="0"
                                aria-label="${ariaLabel}"
                                data-vehicle-id="${v.id}"
                                data-hour="${hour}"
                                onclick="Planning.openNewReservation(${v.id}, ${hour})"
                                onkeydown="Planning.handleCellKeydown(event, this)">
                                <div class="cell-content"><span class="status-text">Dispo</span></div>
                            </td>`;
                        }
                    });

                    bodyHtml += '</tr>';
                }
                $('#table-body').html(bodyHtml);

                // Initialize keyboard navigation
                this.initKeyboardNav();
            },

            // Keyboard navigation handler
            handleCellKeydown(event, cell) {
                const $cell = $(cell);
                const vehicleId = parseInt($cell.data('vehicle-id'));
                const hour = parseInt($cell.data('hour'));

                switch (event.key) {
                    case 'Enter':
                    case ' ':
                        event.preventDefault();
                        if ($cell.hasClass('cell-disponible')) {
                            this.openNewReservation(vehicleId, hour);
                        } else if ($cell.data('reservation')) {
                            this.showDetails(cell);
                        }
                        break;
                    case 'ArrowRight':
                        event.preventDefault();
                        this.moveFocus($cell, 'right');
                        break;
                    case 'ArrowLeft':
                        event.preventDefault();
                        this.moveFocus($cell, 'left');
                        break;
                    case 'ArrowDown':
                        event.preventDefault();
                        this.moveFocus($cell, 'down');
                        break;
                    case 'ArrowUp':
                        event.preventDefault();
                        this.moveFocus($cell, 'up');
                        break;
                }
            },

            moveFocus($current, direction) {
                const $row = $current.closest('tr');
                const cellIndex = $current.index();
                let $target;

                switch (direction) {
                    case 'right':
                        $target = $current.next('td[tabindex="0"]');
                        break;
                    case 'left':
                        $target = $current.prev('td[tabindex="0"]');
                        break;
                    case 'down':
                        $target = $row.next('tr').find(`td:eq(${cellIndex - 1})`).filter('[tabindex="0"]');
                        break;
                    case 'up':
                        $target = $row.prev('tr').find(`td:eq(${cellIndex - 1})`).filter('[tabindex="0"]');
                        break;
                }

                if ($target && $target.length) {
                    $target.focus();
                }
            },

            initKeyboardNav() {
                // Add focus styles
                $('#planning-table td[tabindex="0"]').on('focus', function() {
                    $(this).css('outline', '3px solid var(--accent)').css('outline-offset', '-3px');
                }).on('blur', function() {
                    $(this).css('outline', 'none');
                });
            },

            findReservation(vehicleId, hour) {
                const hourStr = String(hour).padStart(2, '0') + ':00:00';
                return this.reservations.find(r => r.vehicle_id === vehicleId && r.start_time <= hourStr && r.end_time > hourStr);
            },

            openNewReservation(vehicleId, hour) {
                if (this.isSubmitting) return;
                this.isSubmitting = true;
                var self = this;
                var data = {
                    vehicle_id: vehicleId,
                    reservation_date: this.selectedDate,
                    start_time: String(hour).padStart(2, '0') + ':00',
                    end_time: String(hour + 1).padStart(2, '0') + ':00'
                };

                $.ajax({ url: '/api/v1/reservations', method: 'POST', data: data })
                    .done(function() {
                        self.loadData();
                    })
                    .fail(function(xhr) {
                        var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Erreur lors de la reservation';
                        Swal.fire('Erreur', msg, 'error');
                    })
                    .always(function() { self.isSubmitting = false; });
            },

            showDetails(el) {
                var r = JSON.parse($(el).attr('data-reservation'));
                var isMine = r.user_id === this.currentUserId;
                var self = this;

                if (isMine && r.can_cancel) {
                    Swal.fire({
                        title: 'Annuler cette reservation ?',
                        text: r.vehicle_name + ' — ' + r.start_time.substring(0, 5) + ' à ' + r.end_time.substring(0, 5),
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#e74c3c',
                        confirmButtonText: 'Oui, annuler',
                        cancelButtonText: 'Non'
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            $.ajax({ url: '/api/v1/reservations/' + r.id, method: 'DELETE' })
                                .done(function() {
                                    self.loadData();
                                })
                                .fail(function(xhr) {
                                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Erreur';
                                    Swal.fire('Erreur', msg, 'error');
                                });
                        }
                    });
                } else if (!isMine) {
                    $('#detail-user').text(r.user_name);
                    $('#detail-vehicle').text(r.vehicle_name);
                    $('#detail-start').text(r.start_time.substring(0, 5));
                    $('#detail-end').text(r.end_time.substring(0, 5));

                    if (r.destination) {
                        $('#detail-destination').text(r.destination);
                        $('#detail-destination-container').show();
                    } else {
                        $('#detail-destination-container').hide();
                    }

                    if (r.notes) {
                        $('#detail-notes').text(r.notes);
                        $('#detail-notes-container').show();
                    } else {
                        $('#detail-notes-container').hide();
                    }

                    $('#detailsModal').modal('show');
                }
            },

            startPolling() {
                this.pollingInterval = setInterval(() => this.loadData(), 15000);
            },

            stopPolling() {
                if (this.pollingInterval) clearInterval(this.pollingInterval);
            }
        };

        $(document).ready(() => Planning.init());
        $(window).on('beforeunload', () => Planning.stopPolling());

        // ==================== MOBILE PLANNING ====================
        const MobilePlanning = {
            vehicles: @json($vehicles->filter(fn($v) => auth()->user()->canAccessVehicle($v))->values()),
            reservations: [],
            currentUserId: {{ auth()->id() }},
            selectedDate: '{{ now()->toDateString() }}',
            currentVehicleId: null,
            pollingInterval: null,
            isSubmitting: false,
            // Vehicle settings
            settings: {
                openingHour: {{ $settings->opening_hour_int }},
                closingHour: {{ $settings->closing_hour_int }},
                openingHourFormatted: '{{ $settings->opening_hour_formatted }}',
                closingHourFormatted: '{{ $settings->closing_hour_formatted }}',
                maxFutureDays: {{ $settings->max_future_days }},
                maxReservationDate: '{{ $settings->max_reservation_date }}',
            },

            init() {
                this.bindEvents();
                this.loadData();
                this.updateDateDisplay();
                this.startPolling();
                // Set max date on date picker
                $('#mobile-date-input').attr('max', this.settings.maxReservationDate);
            },

            bindEvents() {
                // Date navigation: previous day
                $('#mobile-date-prev').on('click', () => {
                    this.navigateDate(-1);
                });

                // Date navigation: next day
                $('#mobile-date-next').on('click', () => {
                    this.navigateDate(1);
                });

                // Date picker (native input overlaid on label)
                $('#mobile-date-input').on('change', (e) => {
                    this.setDate(e.target.value);
                });

                // Today button
                $('#mobile-date-today').on('click', () => {
                    this.setDate(new Date().toISOString().split('T')[0]);
                });

                // Search
                $('#mobile-search').on('input', (e) => {
                    this.filterVehicles(e.target.value);
                });

                // Back from detail view
                $(document).on('click', '#mobile-back-btn', () => {
                    this.showVehiclesList();
                });
            },

            navigateDate(offset) {
                var d = new Date(this.selectedDate);
                d.setDate(d.getDate() + offset);
                var newDate = d.toISOString().split('T')[0];

                // Don't go beyond max date
                if (newDate > this.settings.maxReservationDate) return;

                this.setDate(newDate);
            },

            setDate(dateStr) {
                this.selectedDate = dateStr;
                $('#mobile-date-input').val(dateStr);
                this.updateDateDisplay();
                this.loadData();

                // Sync with desktop
                $('#planning-date').val(dateStr);
                Planning.selectedDate = dateStr;
            },

            updateDateDisplay() {
                var today = new Date();
                today.setHours(0, 0, 0, 0);
                var selected = new Date(this.selectedDate);
                selected.setHours(0, 0, 0, 0);

                var isToday = selected.getTime() === today.getTime();
                var displayText;

                if (isToday) {
                    displayText = "Aujourd'hui";
                } else {
                    displayText = selected.toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric', month: 'short' });
                }
                $('#mobile-date-display').text(displayText);

                // Show/hide "Aujourd'hui" button
                if (isToday) {
                    $('#mobile-date-today').addClass('hidden');
                } else {
                    $('#mobile-date-today').removeClass('hidden');
                }

                // Disable prev button if today
                var yesterday = new Date(today);
                yesterday.setDate(yesterday.getDate() - 1);
                $('#mobile-date-prev').prop('disabled', selected <= today);

                // Disable next button if at max date
                var maxDate = new Date(this.settings.maxReservationDate);
                maxDate.setHours(0, 0, 0, 0);
                $('#mobile-date-next').prop('disabled', selected >= maxDate);

                // Update detail view date
                $('#detail-date-display').text(
                    selected.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
                );
            },

            loadData() {
                $.get('/api/v1/reservations', { date: this.selectedDate })
                    .done((data) => {
                        this.reservations = data;
                        this.updateVehicleStatuses();
                        this.updateStats();

                        // If in detail view, refresh it
                        if (this.currentVehicleId) {
                            this.renderTimeSlots(this.currentVehicleId);
                        }

                        // Sync with desktop
                        Planning.reservations = data;
                        Planning.renderTable();
                    })
                    .fail((error) => {
                        console.error('Erreur chargement mobile:', error);
                        // Update all vehicle statuses to show error
                        this.vehicles.forEach(function(v) {
                            var $status = $('#mobile-vehicle-status-' + v.id);
                            $status.html('<span style="color:#c0392b;">Erreur de chargement</span>');
                        });
                    });
            },

            updateStats() {
                const todayReservations = this.reservations.length;
                $('#mobile-stat-reservations').text(todayReservations);
            },

            updateVehicleStatuses() {
                this.vehicles.forEach(v => {
                    const vehicleReservations = this.reservations.filter(r => r.vehicle_id === v.id);
                    const availableSlots = 24 - vehicleReservations.length;
                    const $status = $(`#mobile-vehicle-status-${v.id}`);

                    if (availableSlots > 0) {
                        $status.removeClass('indisponible').addClass('disponible');
                        $status.html(`
                            <i class="ki-duotone ki-check-circle"><i class="path1"></i><i class="path2"></i></i>
                            <span>${availableSlots} créneaux dispo</span>
                        `);
                    } else {
                        $status.removeClass('disponible').addClass('indisponible');
                        $status.html(`
                            <i class="ki-duotone ki-cross-circle"><i class="path1"></i><i class="path2"></i></i>
                            <span>Complet</span>
                        `);
                    }
                });
            },

            filterVehicles(query) {
                const q = query.toLowerCase();
                $('.vehicle-card-mobile').each(function() {
                    const name = $(this).data('vehicle-name').toLowerCase();
                    const plate = $(this).data('vehicle-plate').toLowerCase();
                    if (name.includes(q) || plate.includes(q)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            },

            showVehicleDetail(vehicleId) {
                this.currentVehicleId = vehicleId;
                const vehicle = this.vehicles.find(v => v.id === vehicleId);

                // Update header
                $('#mobile-title').html(`
                    <span id="mobile-back-btn" style="cursor:pointer;margin-right:8px;">
                        <i class="ki-duotone ki-arrow-left"><i class="path1"></i><i class="path2"></i></i>
                    </span>
                    ${vehicle.name}
                `);

                // Update detail header
                $('#detail-vehicle-name').text(vehicle.name);
                $('#detail-vehicle-plate').text(vehicle.license_plate);

                // Render time slots
                this.renderTimeSlots(vehicleId);

                // Show detail view
                $('#mobile-vehicles-list').hide();
                $('#mobile-vehicle-detail').show();
                $('.mobile-stats').hide();
                $('.mobile-planning-search').hide();
            },

            showVehiclesList() {
                this.currentVehicleId = null;
                $('#mobile-title').text('Planning Véhicules');
                $('#mobile-vehicles-list').show();
                $('#mobile-vehicle-detail').hide();
                $('.mobile-stats').show();
                $('.mobile-planning-search').show();
            },

            renderTimeSlots(vehicleId) {
                let html = '';
                const today = new Date().toISOString().split('T')[0];
                const currentHour = new Date().getHours();
                const isToday = this.selectedDate === today;
                const isPastDate = this.selectedDate < today;
                const isFutureDate = this.selectedDate > this.settings.maxReservationDate;

                // Use settings for hour range
                const startHour = this.settings.openingHour;
                const endHour = this.settings.closingHour;

                for (let hour = startHour; hour <= endHour; hour++) {
                    const timeStr = String(hour).padStart(2, '0') + ':00';
                    const hourStr = String(hour).padStart(2, '0') + ':00:00';
                    const isPastHour = isPastDate || isFutureDate || (isToday && hour <= currentHour);
                    const reservation = this.reservations.find(
                        r => r.vehicle_id === vehicleId && r.start_time <= hourStr && r.end_time > hourStr
                    );

                    if (reservation) {
                        const isMine = reservation.user_id === this.currentUserId;
                        const slotClass = isMine ? 'mine' : 'reserve';

                        html += `
                            <div class="time-slot-mobile ${slotClass}"
                                 data-reservation='${JSON.stringify(reservation).replace(/'/g, "&#39;")}'
                                 onclick="MobilePlanning.showSlotDetails(this)">
                                <div class="time-slot-mobile-time">${timeStr}</div>
                                <div class="time-slot-mobile-info">
                                    <div class="time-slot-mobile-driver">${reservation.user_name}</div>
                                    ${reservation.destination ? `<div class="time-slot-mobile-destination"><i class="ki-duotone ki-geolocation"><i class="path1"></i><i class="path2"></i></i> ${reservation.destination}</div>` : ''}
                                </div>
                                ${isMine && reservation.can_cancel ? `
                                    <div class="time-slot-mobile-actions">
                                        <span class="text-danger small">Tap pour annuler</span>
                                    </div>
                                ` : ''}
                            </div>
                        `;
                    } else if (isPastHour) {
                        // Créneau passé - désactivé
                        html += `
                            <div class="time-slot-mobile" style="opacity: 0.5; cursor: not-allowed; border-left: 4px solid #ccc;">
                                <div class="time-slot-mobile-time">${timeStr}</div>
                                <div class="time-slot-mobile-info">
                                    <div class="time-slot-mobile-status" style="color: #999;">Passé</div>
                                </div>
                            </div>
                        `;
                    } else {
                        html += `
                            <div class="time-slot-mobile disponible"
                                 data-hour="${hour}"
                                 data-vehicle-id="${vehicleId}"
                                 onclick="MobilePlanning.openReservation(${vehicleId}, ${hour})">
                                <div class="time-slot-mobile-time">${timeStr}</div>
                                <div class="time-slot-mobile-info">
                                    <div class="time-slot-mobile-status">Disponible</div>
                                </div>
                                <div class="time-slot-mobile-actions">
                                    <span class="text-success small">Tap pour réserver</span>
                                </div>
                            </div>
                        `;
                    }
                }

                $('#mobile-time-slots').html(html);
            },

            showSlotDetails(el) {
                var r = JSON.parse($(el).attr('data-reservation'));
                var isMine = r.user_id === this.currentUserId;
                var self = this;

                if (isMine && r.can_cancel) {
                    Swal.fire({
                        title: 'Annuler cette réservation ?',
                        text: r.vehicle_name + ' — ' + r.start_time.substring(0, 5) + ' à ' + r.end_time.substring(0, 5),
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#e74c3c',
                        confirmButtonText: 'Oui, annuler',
                        cancelButtonText: 'Non'
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            $.ajax({ url: '/api/v1/reservations/' + r.id, method: 'DELETE' })
                                .done(function() { self.loadData(); })
                                .fail(function(xhr) {
                                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Erreur lors de l\'annulation';
                                    Swal.fire('Erreur', msg, 'error');
                                });
                        }
                    });
                } else if (!isMine) {
                    // Show read-only details
                    $('#detail-user').text(r.user_name);
                    $('#detail-vehicle').text(r.vehicle_name);
                    $('#detail-start').text(r.start_time.substring(0, 5));
                    $('#detail-end').text(r.end_time.substring(0, 5));

                    if (r.destination) {
                        $('#detail-destination').text(r.destination);
                        $('#detail-destination-container').show();
                    } else {
                        $('#detail-destination-container').hide();
                    }

                    if (r.notes) {
                        $('#detail-notes').text(r.notes);
                        $('#detail-notes-container').show();
                    } else {
                        $('#detail-notes-container').hide();
                    }

                    $('#detailsModal').modal('show');
                }
            },

            openReservation(vehicleId, hour) {
                if (this.isSubmitting) return;
                this.isSubmitting = true;
                var self = this;
                var data = {
                    vehicle_id: vehicleId,
                    reservation_date: this.selectedDate,
                    start_time: String(hour).padStart(2, '0') + ':00',
                    end_time: String(hour + 1).padStart(2, '0') + ':00'
                };
                $.ajax({ url: '/api/v1/reservations', method: 'POST', data: data })
                    .done(function() { self.loadData(); })
                    .fail(function(xhr) {
                        var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Erreur lors de la réservation';
                        Swal.fire('Erreur', msg, 'error');
                    })
                    .always(function() { self.isSubmitting = false; });
            },

            startPolling() {
                this.pollingInterval = setInterval(() => this.loadData(), 15000);
            },

            stopPolling() {
                if (this.pollingInterval) clearInterval(this.pollingInterval);
            }
        };

        // Initialize mobile planning
        $(document).ready(() => MobilePlanning.init());
        $(window).on('beforeunload', () => MobilePlanning.stopPolling());

        // ==================== REAL-TIME BROADCASTING ====================
        // Connect to vehicle-planning channel for live updates
        if (typeof Echo !== 'undefined') {
            function subscribeToChannel(date) {
                return Echo.channel('vehicle-planning.' + date)
                    .listen('ReservationCreated', (e) => {
                        console.log('ReservationCreated:', e);
                        // Reload data for both desktop and mobile
                        Planning.loadData();
                        MobilePlanning.loadData();

                        // Show notification
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'info',
                            title: `${e.user_name} a reserve ${e.vehicle_name}`,
                            showConfirmButton: false,
                            timer: 3000
                        });
                    })
                    .listen('ReservationUpdated', (e) => {
                        console.log('ReservationUpdated:', e);
                        Planning.loadData();
                        MobilePlanning.loadData();
                    })
                    .listen('ReservationCancelled', (e) => {
                        console.log('ReservationCancelled:', e);
                        Planning.loadData();
                        MobilePlanning.loadData();

                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'info',
                            title: 'Une reservation a ete annulee',
                            showConfirmButton: false,
                            timer: 3000
                        });
                    });
            }

            // Subscribe to current date channel
            let currentChannel = subscribeToChannel('{{ now()->toDateString() }}');

            // Re-subscribe when date changes
            $(document).on('change', '#planning-date, #mobile-date-input', function() {
                const newDate = $(this).val();
                // Leave old channel and join new one
                Echo.leave('vehicle-planning.' + Planning.selectedDate);
                currentChannel = subscribeToChannel(newDate);
            });
        }
    </script>
</body>
</html>





