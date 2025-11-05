@extends('layouts.app')

@section('title', 'Tableau de bord')
@section('page-title', 'Tableau de bord')

@push('styles')
<style>
    /* ==================== WELCOME CARD REDESIGN ==================== */
    .welcome-section {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        border-radius: 20px;
        padding: 2rem;
        color: #fff;
        margin-bottom: 1.5rem;
        box-shadow: var(--neu-shadow);
        position: relative;
        overflow: hidden;
    }

    .welcome-section::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 200px;
        height: 200px;
        background: radial-gradient(circle, rgba(212, 168, 75, 0.15) 0%, transparent 70%);
        transform: translate(30%, -30%);
    }

    .welcome-greeting {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
        color: #fff !important;
    }

    .welcome-date {
        color: #D4A84B !important;
        font-size: 0.95rem;
        text-transform: capitalize;
    }

    .welcome-section time {
        color: #fff !important;
    }

    /* ==================== QUICK ACTIONS GRID ==================== */
    .quick-actions-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0.75rem;
        margin-top: 1.5rem;
    }

    @media (max-width: 768px) {
        .quick-actions-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    .quick-action-card {
        background: rgba(255, 255, 255, 0.12);
        border-radius: 14px;
        padding: 1rem;
        text-align: center;
        color: #fff;
        text-decoration: none;
        transition: all 0.25s ease;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .quick-action-card:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: translateY(-2px);
        color: #fff;
        text-decoration: none;
    }

    .quick-action-card:focus-visible {
        outline: 3px solid var(--accent);
        outline-offset: 2px;
    }

    .quick-action-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .quick-action-icon.vehicles { background: rgba(59, 130, 246, 0.25); color: #93c5fd; }
    .quick-action-icon.meals { background: rgba(93, 138, 102, 0.25); color: #7AB785; }
    .quick-action-icon.requests { background: rgba(212, 168, 75, 0.25); color: var(--accent); }
    .quick-action-icon.bulletin { background: rgba(255, 255, 255, 0.15); color: #fff; }

    .quick-action-label {
        font-size: 0.85rem;
        font-weight: 600;
    }

    /* ==================== MEAL DASHBOARD WIDGET ==================== */
    .meal-dashboard-widget {
        background: linear-gradient(135deg, #2C2420 0%, #1a1512 100%);
        border-radius: 20px;
        padding: 1.75rem;
        color: #fff;
        position: relative;
        overflow: hidden;
        box-shadow: var(--neu-shadow);
        margin-bottom: 1.5rem;
    }

    .meal-dashboard-widget::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--accent), var(--primary), var(--accent));
    }

    .meal-widget-icon-large {
        font-size: 5rem;
        position: absolute;
        right: 2rem;
        top: 50%;
        transform: translateY(-50%);
        opacity: 0.08;
        color: var(--accent);
    }

    .meal-widget-content {
        position: relative;
        z-index: 1;
    }

    .meal-widget-title {
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: rgba(255, 255, 255, 0.7);
        margin-bottom: 0.5rem;
    }

    .meal-widget-date {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 1rem;
        color: #fff;
    }

    .meal-widget-stats {
        display: flex;
        align-items: end;
        gap: 2rem;
        margin-bottom: 1rem;
    }

    .meal-stat-main {
        font-size: 2.5rem;
        font-weight: 800;
        color: var(--accent);
        line-height: 1;
    }

    .meal-stat-label {
        font-size: 0.85rem;
        color: rgba(255, 255, 255, 0.7);
    }

    .meal-progress-bar {
        height: 10px;
        background: rgba(255, 255, 255, 0.15);
        border-radius: 6px;
        overflow: hidden;
        margin-bottom: 1rem;
    }

    .meal-progress-fill {
        height: 100%;
        border-radius: 6px;
        transition: width 0.3s ease;
    }

    .meal-widget-actions {
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .meal-btn-register {
        background: linear-gradient(135deg, #5D8A66, #4A7553);
        color: #fff;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 10px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.2s ease;
        text-decoration: none;
    }

    .meal-btn-register:hover {
        background: linear-gradient(135deg, #4A7553, #3D6345);
        transform: translateY(-1px);
        color: #fff;
    }

    .meal-status-registered {
        background: rgba(93, 138, 102, 0.2);
        color: #7AB785;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .meal-deadline-info {
        font-size: 0.85rem;
        color: rgba(255, 255, 255, 0.6);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    /* ==================== STATS CLIQUABLES ==================== */
    .stat-card-clickable {
        background: var(--bg-card);
        border-radius: 16px;
        padding: 1.25rem;
        box-shadow: var(--neu-shadow-sm);
        transition: all 0.25s ease;
        text-decoration: none;
        color: inherit;
        display: flex;
        align-items: center;
        cursor: pointer;
    }

    .stat-card-clickable:hover {
        transform: translateY(-3px);
        box-shadow: var(--neu-shadow);
        text-decoration: none;
        color: inherit;
    }

    .stat-card-clickable:focus-visible {
        outline: 3px solid var(--accent);
        outline-offset: 2px;
    }

    .stat-icon {
        width: 54px;
        height: 54px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .stat-value {
        font-size: 1.75rem;
        font-weight: 800;
        color: #3D3028 !important;
        line-height: 1;
    }

    .stat-label {
        font-size: 0.85rem;
        color: #6B5D52 !important;
        margin-top: 0.25rem;
    }

    /* ==================== FIX LISIBILITÉ TITRES ==================== */
    .card .card-title {
        color: #3D3028 !important;
    }
    .card .card-title i {
        color: #8B2332 !important;
    }
    .reflection-title {
        color: #8B2332 !important;
    }
    .reflection-card.theology .reflection-title {
        color: #9a7a30 !important;
    }
    .activity-mini .fw-semibold {
        color: #3D3028 !important;
    }
    .birthday-person {
        color: #3D3028 !important;
    }
    .birthday-day {
        color: #8B2332 !important;
    }
    h4.text-itcj-red {
        color: #8B2332 !important;
    }

    .stat-arrow {
        margin-left: auto;
        color: var(--text-muted);
        opacity: 0;
        transition: all 0.2s ease;
    }

    .stat-card-clickable:hover .stat-arrow {
        opacity: 1;
        transform: translateX(3px);
    }

    /* ==================== EXISTING STYLES RETAINED ==================== */
    /* Bulletin Preview Card - Tons chauds */
    .bulletin-preview {
        background: linear-gradient(135deg, #2C2420 0%, #1a1512 100%);
        border-radius: 20px;
        overflow: hidden;
        position: relative;
        color: #fff;
        box-shadow: var(--neu-shadow);
    }

    .bulletin-preview-bg {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('{{ asset("assets/media/backgrounds/itcj-bg.jpg") }}') center/cover no-repeat;
        opacity: 0.12;
    }

    .bulletin-preview-content {
        position: relative;
        z-index: 1;
        padding: 1.75rem;
    }

    .bulletin-preview-title {
        font-family: 'Times New Roman', serif;
        font-size: 1.75rem;
        font-weight: bold;
        letter-spacing: 2px;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        color: #fff;
    }

    .bulletin-preview-subtitle {
        color: var(--accent);
        font-style: italic;
        font-size: 0.85rem;
    }

    .bulletin-preview-meta {
        background: rgba(212, 168, 75, 0.15);
        backdrop-filter: blur(10px);
        border-radius: 8px;
        padding: 0.5rem 1rem;
        font-size: 0.85rem;
        display: inline-block;
        border: 1px solid rgba(212, 168, 75, 0.2);
    }

    /* Service Pills */
    .service-pill {
        display: inline-flex;
        align-items: center;
        background: rgba(255,255,255,0.08);
        border-radius: 20px;
        padding: 0.35rem 0.75rem;
        margin: 0.25rem;
        font-size: 0.8rem;
    }

    .service-pill-icon {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 0.5rem;
        font-size: 0.75rem;
    }

    /* Activity Mini Card - Neumorphism */
    .activity-mini {
        background: var(--bg-card);
        border-radius: 12px;
        padding: 1rem;
        margin-bottom: 0.75rem;
        box-shadow: var(--neu-shadow-sm);
        border-left: 3px solid var(--primary);
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .activity-mini:hover {
        transform: translateX(4px);
        box-shadow: var(--neu-shadow);
    }

    .activity-mini:last-child {
        margin-bottom: 0;
    }

    .activity-mini-date {
        font-size: 0.75rem;
        color: var(--primary);
        font-weight: 600;
        text-transform: capitalize;
    }

    /* Reflection Card - Neumorphism */
    .reflection-card {
        background: var(--bg-card);
        border-radius: 14px;
        padding: 1.25rem;
        border-left: 4px solid var(--primary);
        box-shadow: var(--neu-shadow-sm);
    }

    .reflection-card.theology {
        border-left-color: var(--accent);
    }

    .reflection-title {
        font-weight: bold;
        color: var(--primary);
        margin-bottom: 0.5rem;
    }

    .reflection-card.theology .reflection-title {
        color: var(--accent-dark);
    }

    .reflection-excerpt {
        font-style: italic;
        color: var(--text-secondary);
        font-size: 0.9rem;
        line-height: 1.6;
    }

    /* Birthday Card - Tons chauds + Neumorphism */
    .birthday-card {
        background: linear-gradient(135deg, #FDF8F0 0%, #F8F0E3 100%);
        border-radius: 16px;
        padding: 1.25rem;
        border: 2px dashed var(--accent);
        box-shadow: var(--neu-shadow-sm);
    }

    .birthday-icon {
        font-size: 2rem;
        color: var(--accent);
    }

    .birthday-person {
        font-weight: 600;
        color: var(--text-primary);
    }

    .birthday-day {
        font-size: 0.85rem;
        color: var(--primary);
        font-weight: 500;
    }

    /* Empty bulletin state - Neumorphism */
    .no-bulletin {
        background: var(--bg-card);
        border-radius: 20px;
        padding: 3rem 2rem;
        text-align: center;
        box-shadow: var(--neu-shadow);
    }

    .no-bulletin-icon {
        font-size: 4rem;
        color: var(--secondary);
        margin-bottom: 1rem;
    }

    /* No meal widget */
    .no-meal-widget {
        background: var(--bg-card);
        border-radius: 16px;
        padding: 2rem;
        text-align: center;
        box-shadow: var(--neu-shadow-sm);
        margin-bottom: 1.5rem;
    }

    /* ==================== MOBILE OPTIMIZATIONS ==================== */
    @media (max-width: 576px) {
        /* Welcome section mobile */
        .welcome-section {
            padding: 1.25rem;
            border-radius: 16px;
            margin-bottom: 1rem;
        }

        .welcome-section::before {
            width: 120px;
            height: 120px;
        }

        .welcome-greeting {
            font-size: 1.25rem;
        }

        .welcome-date {
            font-size: 0.85rem;
        }

        /* Quick actions mobile - larger touch targets */
        .quick-actions-grid {
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .quick-action-card {
            padding: 0.85rem 0.5rem;
            border-radius: 12px;
        }

        .quick-action-icon {
            width: 44px;
            height: 44px;
            font-size: 1.35rem;
        }

        .quick-action-label {
            font-size: 0.75rem;
        }

        /* Meal widget mobile */
        .meal-dashboard-widget {
            padding: 1.25rem;
            border-radius: 16px;
            margin-bottom: 1rem;
        }

        .meal-widget-icon-large {
            display: none;
        }

        .meal-widget-title {
            font-size: 0.75rem;
        }

        .meal-widget-date {
            font-size: 1.1rem;
            margin-bottom: 0.75rem;
        }

        .meal-widget-stats {
            gap: 1.25rem;
            margin-bottom: 0.75rem;
        }

        .meal-stat-main {
            font-size: 1.75rem;
        }

        .meal-stat-label {
            font-size: 0.75rem;
        }

        .meal-progress-bar {
            height: 8px;
            margin-bottom: 0.75rem;
        }

        .meal-widget-actions {
            flex-direction: column;
            gap: 0.75rem;
        }

        .meal-btn-register {
            width: 100%;
            justify-content: center;
            padding: 0.85rem 1.25rem;
        }

        .meal-status-registered {
            width: 100%;
            justify-content: center;
        }

        .meal-deadline-info {
            font-size: 0.75rem;
            justify-content: center;
            width: 100%;
        }

        /* Stats cards mobile */
        .stat-card-clickable {
            padding: 1rem;
            border-radius: 12px;
        }

        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            font-size: 1.25rem;
        }

        .stat-value {
            font-size: 1.35rem;
        }

        .stat-label {
            font-size: 0.75rem;
        }

        .stat-arrow {
            display: none;
        }

        /* Bulletin preview mobile */
        .bulletin-preview {
            border-radius: 16px;
        }

        .bulletin-preview-content {
            padding: 1.25rem;
        }

        .bulletin-preview-title {
            font-size: 1.25rem;
            letter-spacing: 1px;
        }

        .bulletin-preview-subtitle {
            font-size: 0.75rem;
        }

        .bulletin-preview-meta {
            font-size: 0.75rem;
            padding: 0.4rem 0.75rem;
        }

        /* Activity cards mobile */
        .activity-mini {
            padding: 0.85rem;
            border-radius: 10px;
            margin-bottom: 0.5rem;
        }

        .activity-mini-date {
            font-size: 0.7rem;
        }

        /* Reflection cards mobile */
        .reflection-card {
            padding: 1rem;
            border-radius: 12px;
        }

        .reflection-title {
            font-size: 0.95rem;
        }

        .reflection-excerpt {
            font-size: 0.85rem;
        }

        /* Birthday card mobile */
        .birthday-card {
            padding: 1rem;
            border-radius: 12px;
        }

        .birthday-icon {
            font-size: 1.5rem;
        }

        .birthday-person {
            font-size: 0.9rem;
        }

        .birthday-day {
            font-size: 0.75rem;
        }

        /* Card headers mobile */
        .card-header {
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 0.5rem;
        }

        .card-header .card-title {
            font-size: 1rem;
        }

        /* Service pills mobile */
        .service-pill {
            padding: 0.25rem 0.5rem;
            font-size: 0.7rem;
            margin: 0.15rem;
        }

        .service-pill-icon {
            width: 20px;
            height: 20px;
            margin-right: 0.35rem;
            font-size: 0.65rem;
        }

        /* No meal widget mobile */
        .no-meal-widget {
            padding: 1.5rem 1rem;
            border-radius: 12px;
        }

        /* No bulletin mobile */
        .no-bulletin {
            padding: 2rem 1.25rem;
            border-radius: 16px;
        }

        .no-bulletin-icon {
            font-size: 3rem;
        }
    }

    /* Tablet adjustments */
    @media (min-width: 577px) and (max-width: 768px) {
        .welcome-section {
            padding: 1.5rem;
        }

        .meal-dashboard-widget {
            padding: 1.5rem;
        }

        .meal-widget-date {
            font-size: 1.25rem;
        }

        .meal-stat-main {
            font-size: 2rem;
        }
    }
</style>
@endpush

@section('content')
<!-- ==================== SECTION 1: WELCOME + ACTIONS RAPIDES ==================== -->
<section class="welcome-section" aria-labelledby="welcome-heading">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h2 id="welcome-heading" class="welcome-greeting">Bonjour, {{ auth()->user()->name }} !</h2>
            <p class="welcome-date mb-0">
                <time datetime="{{ now()->format('Y-m-d') }}">{{ now()->locale('fr')->isoFormat('dddd D MMMM YYYY') }}</time>
            </p>
        </div>
        <div class="col-md-6">
            <!-- Actions rapides en grille -->
            <nav class="quick-actions-grid" aria-label="Actions rapides">
                <a href="{{ route('vehicles.planning') }}"
                   class="quick-action-card"
                   data-tooltip="Voir le planning et réserver"
                   aria-label="Réserver un véhicule - Accéder au planning des véhicules">
                    <div class="quick-action-icon vehicles" aria-hidden="true">
                        <i class="ki-duotone ki-car-2"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i><i class="path5"></i></i>
                    </div>
                    <span class="quick-action-label">Véhicule</span>
                </a>

                <a href="{{ route('meals.index') }}"
                   class="quick-action-card"
                   data-tooltip="Gérer mes inscriptions"
                   aria-label="Repas du dimanche - Voir et gérer les inscriptions">
                    <div class="quick-action-icon meals" aria-hidden="true">
                        <i class="ki-duotone ki-gift"><i class="path1"></i><i class="path2"></i></i>
                    </div>
                    <span class="quick-action-label">Repas</span>
                </a>

                <a href="{{ route('community-requests.form.step1') }}"
                   class="quick-action-card"
                   data-tooltip="Réparation ou achat"
                   aria-label="Nouvelle demande - Faire une demande de réparation ou achat">
                    <div class="quick-action-icon requests" aria-hidden="true">
                        <i class="ki-duotone ki-wrench"><i class="path1"></i><i class="path2"></i></i>
                    </div>
                    <span class="quick-action-label">Demande</span>
                </a>

                @if($bulletin)
                <a href="{{ route('bulletin.current') }}"
                   class="quick-action-card"
                   data-tooltip="Lire le bulletin de la semaine"
                   aria-label="Bulletin hebdomadaire - Lire le bulletin de cette semaine">
                    <div class="quick-action-icon bulletin" aria-hidden="true">
                        <i class="ki-duotone ki-document"><i class="path1"></i><i class="path2"></i></i>
                    </div>
                    <span class="quick-action-label">Bulletin</span>
                </a>
                @else
                <a href="{{ route('profile.show') }}"
                   class="quick-action-card"
                   data-tooltip="Voir mon profil"
                   aria-label="Mon profil - Accéder à mes informations personnelles">
                    <div class="quick-action-icon bulletin" aria-hidden="true">
                        <i class="ki-duotone ki-user"><i class="path1"></i><i class="path2"></i></i>
                    </div>
                    <span class="quick-action-label">Profil</span>
                </a>
                @endif
            </nav>
        </div>
    </div>
</section>

<!-- ==================== SECTION 2: WIDGET REPAS DIMANCHE  Et MENU DU JOUR==================== -->
<div class="row g-4 mb-5">

@if($upcomingMeal)
    @php
        $userInscription = $upcomingMeal->inscriptions()->where('user_id', auth()->id())->whereNull('cancelled_at')->first();
        $pct = $upcomingMeal->max_capacity > 0 ? ($upcomingMeal->confirmed_count / $upcomingMeal->max_capacity) * 100 : 0;
        $progressColor = $pct > 90 ? '#ef4444' : ($pct > 70 ? '#D4A84B' : '#5D8A66');
    @endphp
    <section class="meal-dashboard-widget col-lg-6" role="region" aria-labelledby="meal-widget-heading">
        <i class="ki-duotone ki-gift meal-widget-icon-large" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>

        <div class="meal-widget-content">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <p class="meal-widget-title" id="meal-widget-heading">
                        <i class="ki-duotone ki-gift me-1" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
                        Prochain repas du dimanche
                    </p>
                    <h3 class="meal-widget-date">{{ $upcomingMeal->formatted_date }}</h3>

                    <div class="meal-widget-stats">
                        <div>
                            <div class="meal-stat-main">{{ $upcomingMeal->confirmed_count }}/{{ $upcomingMeal->max_capacity }}</div>
                            <div class="meal-stat-label">places réservées</div>
                        </div>
                        <div>
                            <div class="meal-stat-main" style="color: {{ $upcomingMeal->remaining_places < 5 ? '#ef4444' : '#fff' }};">
                                {{ $upcomingMeal->remaining_places }}
                            </div>
                            <div class="meal-stat-label">restantes</div>
                        </div>
                    </div>

                    <div class="meal-progress-bar" role="progressbar"
                         aria-valuenow="{{ round($pct) }}" aria-valuemin="0" aria-valuemax="100"
                         aria-label="Taux de remplissage: {{ round($pct) }}%">
                        <div class="meal-progress-fill" style="width: {{ $pct }}%; background: {{ $progressColor }};"></div>
                    </div>

                    <div class="meal-widget-actions">
                        @if($userInscription)
                            <span class="meal-status-registered" role="status">
                                <i class="ki-duotone ki-check-circle" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
                                Vous êtes inscrit
                            </span>
                            @if($upcomingMeal->canCancel())
                                <a href="{{ route('meals.index') }}" class="btn btn-sm btn-light"
                                   aria-label="Gérer mon inscription au repas">
                                    Gérer
                                </a>
                            @endif
                        @else
                            @if($upcomingMeal->canRegister())
                                <a href="{{ route('meals.show', $upcomingMeal) }}" class="meal-btn-register"
                                   aria-label="S'inscrire au repas du {{ $upcomingMeal->formatted_date }}">
                                    <i class="ki-duotone ki-check" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
                                    S'inscrire maintenant
                                </a>
                            @elseif($upcomingMeal->isFull())
                                <span class="badge" style="background: rgba(255,255,255,0.15); color: rgba(255,255,255,0.7); padding: 0.5rem 1rem;"
                                      role="status">
                                    <i class="ki-duotone ki-information me-1" aria-hidden="true"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                                    Complet
                                </span>
                            @else
                                <span class="badge" style="background: rgba(255,255,255,0.1); color: rgba(255,255,255,0.5); padding: 0.5rem 1rem;"
                                      role="status">
                                    <i class="ki-duotone ki-lock me-1" aria-hidden="true"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                                    Inscriptions fermées
                                </span>
                            @endif
                        @endif

                        <span class="meal-deadline-info">
                            <i class="ki-duotone ki-time" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
                            Jusqu'au {{ $upcomingMeal->registration_deadline ? $upcomingMeal->registration_deadline->locale('fr')->isoFormat('D MMM [à] HH[h]mm') : 'jour du repas' }}
                        </span>
                    </div>
                </div>

                <div class="col-lg-4 text-center d-none d-lg-block">
                    <a href="{{ route('meals.index') }}" class="btn btn-light btn-sm"
                       aria-label="Voir tous les repas programmés">
                        <i class="ki-duotone ki-arrow-right me-1" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
                        Tous les repas
                    </a>
                </div>
            </div>
        </div>
    </section>
@else
    <section class="no-meal-widget col-lg-6" role="region" aria-label="Repas du dimanche">
        <i class="ki-duotone ki-gift fs-3x text-muted mb-3" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
        <h4 class="text-muted mb-1">Aucun repas programmé</h4>
        <p class="text-muted fs-7 mb-0">Il n'y a pas de repas du dimanche prévu pour le moment.</p>
    </section>
@endif
@if ($todaysMenus)
     <div class="col-lg-6">
        <section class="card" aria-labelledby="reservations-heading">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <h3 id="reservations-heading" class="card-title fw-bold mb-0">
                    <i class="ki-duotone ki-car-2 text-itcj-blue me-2" aria-hidden="true"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i><i class="path5"></i></i>
                    Menu du jour
                </h3>
                <a href="{{ route('week-menus.index') }}" class="btn btn-sm btn-light"
                   aria-label="Voir tous le menu de la semaine">
                    Voir pour la semaine
                </a>
            </div>
            <div class="card-body py-3 " >
                {{-- TODO: change icons --}}
                
                    
                @foreach($todaysMenus as $menu)
                    <article class="d-flex  align-items-center p-1 mb-2 rounded" style="background: #f8f9fc;">
                        <span class="badge {{ $menu->period->code === 'midi' ? 'badge-itcj' : 'badge-itcj-gold' }} fs-9"
                              role="status">
                            {{ $menu->period->code === 'midi' ? 'Midi' : 'Soir' }}
                        </span>
                        <div class="symbol symbol-40px me-3" aria-hidden="true">
                            <span class="symbol-label bg-itcj-blue-light">
                                <i class="ki-duotone ki-coffee fs-4 text-itcj-blue"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i><i class="path5"></i></i>
                            </span>
                        </div>
                        <div class="flex-grow-1 ">
                            @if ($menu->available)
                                <div class="fw-bold text-dark fs-7">{{ $menu->dish_name }}</div>
                                <p class="text-muted fs-8" >
                                    {{ $menu->description }}
                                </p>
                            @else
                                <div class="fw-bold text-dark fs-7">Neant</div>
                            
                            @endif
                         
                        </div>
                     
                    </article>
                    
             
                @endforeach
                
            </div>

             @if(auth()->user()->isAdmin())
                 {{-- TODO: center the bouton --}}
                    <div class="card-footer py-3 d-flex align-items-center justify-content-center">
                        <a href="{{ route('week-menus.admin.edit') }}" class="btn btn-itcj">
                            <i class="ki-duotone ki-pencil me-2"><i class="path1"></i><i class="path2"></i></i>
                            Modifier le menu
                        </a>
                
                    </div>
                @endif
        </section>
    </div>
@else
    <div class="col-lg-8">
        <section class="no-bulletin" aria-labelledby="no-bulletin-heading">
            <div class="no-bulletin-icon" aria-hidden="true">
                <i class="ki-duotone ki-document"><i class="path1"></i><i class="path2"></i></i>
            </div>
            <h4 id="no-bulletin-heading" class="text-muted mb-2">Menu Du jour</h4>
            <p class="text-muted mb-4">Aucun menu n'a encore été publié aujourd'hui.</p>
            @if(auth()->user()->isAdmin())
            {{-- TODO: change the route --}}
                <a href="{{ route('bulletin.create') }}" class="btn btn-itcj"
                   aria-label="Publier le menu du jour">
                    <i class="ki-duotone ki-plus me-2" aria-hidden="true"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                    Publier le menu du jour
                </a>
            @endif
        </section>
    </div>
@endif
    
</div>
<!-- ==================== SECTION 3: STATS CLIQUABLES ==================== -->
<section class="row g-4 mb-5" aria-label="Statistiques">
    <div class="col-6 col-xl-3">
        <a href="{{ route('vehicles.planning') }}"
           class="stat-card-clickable"
           data-tooltip="Cliquez pour voir le planning"
           aria-label="{{ $stats['available_vehicles'] ?? 0 }} véhicules disponibles. Cliquez pour accéder au planning.">
            <div class="stat-icon bg-itcj-blue-light text-itcj-blue" aria-hidden="true">
                <i class="ki-duotone ki-car-2 fs-1"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i><i class="path5"></i></i>
            </div>
            <div class="ms-3 flex-grow-1">
                <div class="stat-value">{{ $stats['available_vehicles'] ?? 0 }}</div>
                <div class="stat-label">Véhicules dispo.</div>
            </div>
            <i class="ki-duotone ki-arrow-right stat-arrow" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
        </a>
    </div>

    <div class="col-6 col-xl-3">
        <a href="{{ route('reservations.index') }}"
           class="stat-card-clickable"
           data-tooltip="Voir mes réservations"
           aria-label="{{ $stats['reservations_today'] ?? 0 }} réservations aujourd'hui. Cliquez pour voir vos réservations.">
            <div class="stat-icon bg-itcj-gold-light" style="color: #9a7a30;" aria-hidden="true">
                <i class="ki-duotone ki-calendar fs-1"><i class="path1"></i><i class="path2"></i></i>
            </div>
            <div class="ms-3 flex-grow-1">
                <div class="stat-value">{{ $stats['reservations_today'] ?? 0 }}</div>
                <div class="stat-label">Réserv. aujourd'hui</div>
            </div>
            <i class="ki-duotone ki-arrow-right stat-arrow" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
        </a>
    </div>

    <div class="col-6 col-xl-3">
        <a href="{{ route('meals.index') }}"
           class="stat-card-clickable"
           data-tooltip="Gérer les repas"
           aria-label="@if($upcomingMeal){{ $upcomingMeal->remainingSlots() }} places restantes pour le repas du {{ $upcomingMeal->meal_date->format('d/m') }}.@else Pas de repas prévu.@endif Cliquez pour plus d'infos.">
            <div class="stat-icon" style="background: #e8f5e9; color: #2e7d32;" aria-hidden="true">
                <i class="ki-duotone ki-gift fs-1"><i class="path1"></i><i class="path2"></i></i>
            </div>
            <div class="ms-3 flex-grow-1">
                @if($upcomingMeal)
                    <div class="stat-value">{{ $upcomingMeal->remainingSlots() }}</div>
                    <div class="stat-label">Places repas {{ $upcomingMeal->meal_date->format('d/m') }}</div>
                @else
                    <div class="stat-value">-</div>
                    <div class="stat-label">Pas de repas prévu</div>
                @endif
            </div>
            <i class="ki-duotone ki-arrow-right stat-arrow" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
        </a>
    </div>

    <div class="col-6 col-xl-3">
        <a href="{{ route('community-requests.index') }}"
           class="stat-card-clickable"
           data-tooltip="Voir mes demandes"
           aria-label="{{ $stats['pending_requests'] ?? 0 }} demandes en cours. Cliquez pour voir vos demandes.">
            <div class="stat-icon bg-itcj-red-light text-itcj-red" aria-hidden="true">
                <i class="ki-duotone ki-wrench fs-1"><i class="path1"></i><i class="path2"></i></i>
            </div>
            <div class="ms-3 flex-grow-1">
                <div class="stat-value">{{ $stats['pending_requests'] ?? 0 }}</div>
                <div class="stat-label">Demandes en cours</div>
            </div>
            <i class="ki-duotone ki-arrow-right stat-arrow" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
        </a>
    </div>
</section>

<!-- ==================== SECTION 4: BULLETIN & CONTENU ==================== -->
@if($bulletin)
<!-- Bulletin ATTOUNGBLAN Section -->
<section class="row g-5 mb-5" aria-labelledby="bulletin-heading">
    <div class="col-12">
        <article class="bulletin-preview">
            <div class="bulletin-preview-bg" aria-hidden="true"></div>
            <div class="bulletin-preview-content">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <div class="d-flex align-items-center mb-2 flex-wrap">
                            <h2 id="bulletin-heading" class="bulletin-preview-title mb-0">{{ $bulletin->title }}</h2>
                            <a href="{{ route('bulletin.current') }}" class="btn btn-sm btn-light ms-3 mt-2 mt-md-0"
                               aria-label="Lire le bulletin complet {{ $bulletin->title }}">
                                <i class="ki-duotone ki-eye me-1" aria-hidden="true"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                                Lire le bulletin
                            </a>
                        </div>
                        <p class="bulletin-preview-subtitle mb-3">{{ $bulletin->subtitle ?? "Hebdomadaire d'information de la communauté" }}</p>
                        <div class="bulletin-preview-meta">
                            <span>N°<strong>{{ $bulletin->edition_number }}</strong></span>
                            <span class="mx-2">|</span>
                            <span>{{ $bulletin->week_label }}</span>
                        </div>

                        @if($bulletin->weeklyServices->count() > 0)
                            <div class="mt-3 d-none d-md-block" role="list" aria-label="Services de la semaine">
                                @foreach($bulletin->weeklyServices->take(6) as $service)
                                    <div class="service-pill" role="listitem">
                                        <span class="service-pill-icon" style="background: {{ $service->service_color['bg'] }}; color: {{ $service->service_color['text'] }};" aria-hidden="true">
                                            <i class="ki-duotone {{ $service->service_icon }}"><i class="path1"></i><i class="path2"></i></i>
                                        </span>
                                        <span>{{ $service->service_label }}: {{ Str::limit($service->assigned_display_name, 12) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div class="col-lg-4 text-center text-lg-end mt-4 mt-lg-0 d-none d-lg-block">
                        <img src="{{ asset('assets/media/logos/itcj-logo.jpg') }}" alt="Logo ITCJ Community" class="rounded-3" style="height: 80px; opacity: 0.9;">
                    </div>
                </div>
            </div>
        </article>
    </div>
</section>

<div class="row g-4">
    <!-- Colonne gauche: Activités + Anniversaires -->
    <div class="col-lg-4">
        <section class="card mb-4" aria-labelledby="activities-heading">
            <div class="card-header py-4">
                <h3 id="activities-heading" class="card-title fw-bold mb-0">
                    <i class="ki-duotone ki-calendar text-itcj-blue me-2" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
                    Activités de la semaine
                </h3>
            </div>
            <div class="card-body py-3">
                @forelse($bulletin->weeklyActivities->sortBy('activity_date')->take(5) as $activity)
                    <article class="activity-mini">
                          <span class="badge badge-light-warning me-2">{{ $activity->type_label }}</span>
                        @if($activity->activity_date)
                            <time class="activity-mini-date" datetime="{{ $activity->activity_date->format('Y-m-d') }}">
                                {{ $activity->formatted_date }}
                                @if($activity->formatted_time)
                                    - {{ $activity->formatted_time }}
                                @endif
                            </time>
                        @endif
                        <div class="fw-semibold">{{ $activity->title }}</div>
                        @if($activity->description)
                            <div class="text-muted small">
                                <i class="ki-duotone ki-geolocation fs-8 me-1" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
                                {{ $activity->description }}
                            </div>
                        @endif
                          @if($activity->guests)
                                    <div class="service-pill" role="listitem">
                                        <span class=""  aria-hidden="true">
                                            <i class="ki-duotone ki-people"><i class="path1"></i><i class="path2"></i></i>
                                        </span>
                                        <span>Invites: {{ Str::limit($activity->guests, 20) }}</span>
                                    </div>
                            @endif
                         
                    </article>
                @empty
                    <div class="text-center text-muted py-4">
                        <i class="ki-duotone ki-calendar fs-3x text-gray-300 mb-2" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
                        <p class="mb-0">Aucune activité cette semaine</p>
                    </div>
                @endforelse
            </div>
        </section>

        @if($birthdays->count() > 0)
            <section class="birthday-card" aria-labelledby="birthdays-heading">
                <div class="d-flex align-items-center mb-3">
                    <div class="birthday-icon me-3" aria-hidden="true">
                        <i class="ki-duotone ki-gift"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i></i>
                    </div>
                    <div>
                        <h4 id="birthdays-heading" class="mb-0 text-itcj-red">Anniversaires</h4>
                        <small class="text-muted">Cette semaine</small>
                    </div>
                </div>
                <ul class="list-unstyled mb-0">
                    @foreach($birthdays as $person)
                        <li class="d-flex align-items-center py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                            <div class="symbol symbol-35px me-3" aria-hidden="true">
                                <span class="symbol-label bg-itcj-gold-light text-itcj-gold fw-bold">
                                    {{ strtoupper(substr($person->name, 0, 1)) }}
                                </span>
                            </div>
                            <div>
                                <div class="birthday-person">{{ $person->title_prefix }} {{ $person->name }}</div>
                                <div class="birthday-day">{{ $person->birthday_day }}</div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>

    <!-- Colonne centrale: Spiritualité & Théologie -->
    <div class="col-lg-4">
        <section class="card h-100" aria-labelledby="reflections-heading">
            <div class="card-header py-4">
                <h3 id="reflections-heading" class="card-title fw-bold mb-0">
                    <i class="ki-duotone ki-heart text-itcj-blue me-2" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
                    Réflexions
                </h3>
            </div>
            <div class="card-body">
                @php
                    $spiritualite = $bulletin->spiritualReflections->where('type', 'spiritualite')->first();
                    $theologie = $bulletin->spiritualReflections->where('type', 'theologie')->first();
                @endphp

                @if($spiritualite)
                    <article class="reflection-card mb-4">
                        <div class="d-flex align-items-center mb-2">
                            <span class="badge badge-itcj me-2">Spiritualité</span>
                        </div>
                        <h4 class="reflection-title">{{ $spiritualite->title }}</h4>
                        <blockquote class="reflection-excerpt mb-0">
                            "{{ Str::limit($spiritualite->content, 200) }}"
                        </blockquote>
                    </article>
                @endif

                @if($theologie)
                    <article class="reflection-card theology">
                        <div class="d-flex align-items-center mb-2">
                            <span class="badge badge-itcj-gold me-2">Théologie</span>
                        </div>
                        <h4 class="reflection-title">{{ $theologie->title }}</h4>
                        <blockquote class="reflection-excerpt mb-0">
                            "{{ Str::limit($theologie->content, 200) }}"
                        </blockquote>
                    </article>
                @endif

                @if(!$spiritualite && !$theologie)
                    <div class="text-center text-muted py-5">
                        <i class="ki-duotone ki-book-open fs-3x text-gray-300 mb-2" aria-hidden="true"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i></i>
                        <p class="mb-0">Aucune réflexion cette semaine</p>
                    </div>
                @endif

                <div class="text-center mt-4">
                    <a href="{{ route('bulletin.current') }}" class="btn btn-sm btn-itcj-outline"
                       aria-label="Lire le bulletin complet">
                        <i class="ki-duotone ki-document me-1" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
                        Bulletin complet
                    </a>
                </div>
            </div>
        </section>
    </div>

    <!-- Colonne droite: Réservations -->
    <div class="col-lg-4">
        <section class="card" aria-labelledby="reservations-heading">
            <div class="card-header py-4 d-flex justify-content-between align-items-center">
                <h3 id="reservations-heading" class="card-title fw-bold mb-0">
                    <i class="ki-duotone ki-car-2 text-itcj-blue me-2" aria-hidden="true"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i><i class="path5"></i></i>
                    Mes réservations
                </h3>
                <a href="{{ route('reservations.index') }}" class="btn btn-sm btn-light"
                   aria-label="Voir toutes mes réservations">
                    Voir tout
                </a>
            </div>
            <div class="card-body py-3">
                @forelse($myReservations->take(4) as $reservation)
                    <article class="d-flex align-items-center p-3 mb-2 rounded" style="background: #f8f9fc;">
                        <div class="symbol symbol-40px me-3" aria-hidden="true">
                            <span class="symbol-label bg-itcj-blue-light">
                                <i class="ki-duotone ki-car-2 fs-4 text-itcj-blue"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i><i class="path5"></i></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-bold text-dark fs-7">{{ $reservation->vehicle->name }}</div>
                            <time class="text-muted fs-8" datetime="{{ $reservation->reservation_date->format('Y-m-d') }}">
                                {{ $reservation->reservation_date->locale('fr')->isoFormat('ddd D MMM') }} -
                                {{ \Carbon\Carbon::parse($reservation->start_time)->format('H:i') }}
                            </time>
                        </div>
                        <span class="badge {{ $reservation->status === 'approved' ? 'badge-itcj' : 'badge-itcj-gold' }} fs-9"
                              role="status">
                            {{ $reservation->status === 'approved' ? 'Confirmée' : 'En attente' }}
                        </span>
                    </article>
                @empty
                    <div class="text-center text-muted py-4">
                        <i class="ki-duotone ki-calendar fs-3x text-gray-300 mb-2" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
                        <p class="mb-2">Aucune réservation</p>
                        <a href="{{ route('vehicles.planning') }}" class="btn btn-sm btn-itcj"
                           aria-label="Réserver un véhicule maintenant">
                            <i class="ki-duotone ki-plus me-1" aria-hidden="true"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                            Réserver
                        </a>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</div>
@else
<!-- Pas de bulletin -->
<div class="row">
    <div class="col-lg-8">
        <section class="no-bulletin" aria-labelledby="no-bulletin-heading">
            <div class="no-bulletin-icon" aria-hidden="true">
                <i class="ki-duotone ki-document"><i class="path1"></i><i class="path2"></i></i>
            </div>
            <h4 id="no-bulletin-heading" class="text-muted mb-2">Bulletin Hebdomadaire</h4>
            <p class="text-muted mb-4">Aucun bulletin n'a encore été publié cette semaine.</p>
            @if(auth()->user()->isAdmin())
                <a href="{{ route('bulletin.create') }}" class="btn btn-itcj"
                   aria-label="Créer le premier bulletin de la semaine">
                    <i class="ki-duotone ki-plus me-2" aria-hidden="true"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                    Créer le premier bulletin
                </a>
            @endif
        </section>
    </div>
    <div class="col-lg-4">
        <section class="card" aria-labelledby="reservations-heading-alt">
            <div class="card-header py-4">
                <h3 id="reservations-heading-alt" class="card-title fw-bold mb-0">Mes réservations</h3>
            </div>
            <div class="card-body py-3">
                @forelse($myReservations as $reservation)
                    <article class="d-flex align-items-center p-3 mb-2 rounded" style="background: #f8f9fc;">
                        <div class="symbol symbol-40px me-3" aria-hidden="true">
                            <span class="symbol-label bg-itcj-blue-light">
                                <i class="ki-duotone ki-car-2 fs-4 text-itcj-blue"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i><i class="path5"></i></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-bold text-dark fs-7">{{ $reservation->vehicle->name }}</div>
                            <time class="text-muted fs-8" datetime="{{ $reservation->reservation_date->format('Y-m-d') }}">
                                {{ $reservation->reservation_date->format('d/m') }} -
                                {{ \Carbon\Carbon::parse($reservation->start_time)->format('H:i') }}
                            </time>
                        </div>
                        <span class="badge {{ $reservation->status === 'approved' ? 'badge-itcj' : 'badge-itcj-gold' }} fs-9"
                              role="status">
                            {{ $reservation->status === 'approved' ? 'Confirmée' : 'En attente' }}
                        </span>
                    </article>
                @empty
                    <div class="text-center text-muted py-4">
                        <p class="mb-2">Aucune réservation</p>
                        <a href="{{ route('vehicles.planning') }}" class="btn btn-sm btn-itcj"
                           aria-label="Réserver un véhicule">
                            Réserver
                        </a>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endif
@endsection




