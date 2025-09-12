@extends('layouts.app')

@section('title', 'Bulletin Hebdomadaire')
@section('page-title', 'Bulletin Hebdomadaire')

@push('styles')
<style>
    :root {
        --bulletin-blue: #1a365d;
        --bulletin-gold: #b8860b;
        --bulletin-red: #8b2942;
        --bulletin-cream: #faf8f5;
    }

    .bulletin-page {
        background: var(--bulletin-cream);
        min-height: 100vh;
        padding: 2rem 0;
    }

    /* ===== EN-TETE STYLE JOURNAL ===== */
    .bulletin-masthead {
        background: linear-gradient(180deg, var(--bulletin-blue) 0%, #0d1b2a 100%);
        border-radius: 0;
        margin: -2rem -2rem 2rem -2rem;
        padding: 0;
        position: relative;
        overflow: hidden;
    }

    .masthead-decoration {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 6px;
        background: linear-gradient(90deg,
            var(--bulletin-gold) 0%,
            var(--bulletin-gold) 20%,
            var(--bulletin-red) 20%,
            var(--bulletin-red) 40%,
            var(--bulletin-gold) 40%,
            var(--bulletin-gold) 60%,
            var(--bulletin-red) 60%,
            var(--bulletin-red) 80%,
            var(--bulletin-gold) 80%);
    }

    .masthead-inner {
        padding: 2.5rem 3rem;
        text-align: center;
        position: relative;
    }

    .masthead-logo {
        width: 90px;
        height: 90px;
        border-radius: 50%;
        border: 4px solid rgba(255,255,255,0.3);
        margin-bottom: 1rem;
        box-shadow: 0 8px 25px rgba(0,0,0,0.3);
    }

    .masthead-title {
        font-family: 'Georgia', 'Times New Roman', serif;
        font-size: 3rem;
        font-weight: 700;
        color: #fff;
        letter-spacing: 6px;
        text-transform: uppercase;
        margin: 0;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.4);
    }

    .masthead-subtitle {
        color: var(--bulletin-gold);
        font-size: 1rem;
        font-style: italic;
        margin-top: 0.5rem;
        letter-spacing: 2px;
    }

    .masthead-divider {
        width: 200px;
        height: 2px;
        background: var(--bulletin-gold);
        margin: 1.5rem auto;
    }

    .masthead-meta {
        display: flex;
        justify-content: center;
        gap: 3rem;
        flex-wrap: wrap;
        color: rgba(255,255,255,0.9);
        font-size: 0.95rem;
    }

    .masthead-meta-item {
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .masthead-meta-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        opacity: 0.7;
    }

    .masthead-meta-value {
        font-size: 1.3rem;
        font-weight: 700;
        color: var(--bulletin-gold);
    }

    /* ===== LAYOUT 3 COLONNES STYLE JOURNAL ===== */
    .bulletin-content {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 1.5rem;
        margin-top: 2rem;
    }

    @media (max-width: 1200px) {
        .bulletin-content {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (max-width: 768px) {
        .bulletin-content {
            grid-template-columns: 1fr;
        }

        .masthead-title {
            font-size: 2rem;
            letter-spacing: 3px;
        }

        .masthead-inner {
            padding: 1.5rem;
        }
    }

    /* ===== SECTIONS / ARTICLES ===== */
    .bulletin-section {
        background: #fff;
        border-radius: 4px;
        overflow: hidden;
        box-shadow: 0 2px 15px rgba(0,0,0,0.06);
        border: 1px solid #e8e5e0;
    }

    .section-header {
        padding: 1rem 1.25rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 2px;
        font-size: 0.85rem;
        text-align: center;
        border-bottom: 3px solid;
    }

    .section-header.blue {
        background: var(--bulletin-blue);
        color: #fff;
        border-bottom-color: var(--bulletin-gold);
    }

    .section-header.red {
        background: var(--bulletin-red);
        color: #fff;
        border-bottom-color: var(--bulletin-gold);
    }

    .section-header.gold {
        background: linear-gradient(135deg, #9a7a30 0%, var(--bulletin-gold) 100%);
        color: #fff;
        border-bottom-color: var(--bulletin-blue);
    }

    .section-body {
        padding: 1.5rem;
    }

    /* ===== ACTIVITES ===== */
    .activity-intro {
        font-style: italic;
        color: #666;
        font-size: 0.9rem;
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px dashed #ddd;
    }

    .activity-item {
        padding: 0.75rem 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .activity-item:last-child {
        border-bottom: none;
    }

    .activity-date-badge {
        display: inline-block;
        background: linear-gradient(135deg, var(--bulletin-red) 0%, #a62d3f 100%);
        color: #fff;
        padding: 4px 12px;
        border-radius: 3px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: capitalize;
        margin-right: 8px;
    }

    .activity-time-badge {
        display: inline-block;
        background: var(--bulletin-blue);
        color: #fff;
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .activity-title {
        display: block;
        font-weight: 600;
        color: #333;
        margin-top: 6px;
        font-size: 0.95rem;
    }

    .activity-desc {
        font-size: 0.85rem;
        color: #666;
        margin-top: 4px;
        line-height: 1.5;
    }

    .activity-location {
        font-size: 0.8rem;
        color: #888;
        margin-top: 4px;
    }

    .activity-location i {
        color: var(--bulletin-red);
    }

    /* ===== SERVICES DE LA SEMAINE ===== */
    .services-box {
        background: linear-gradient(135deg, #f8f7f4 0%, #f0efe8 100%);
        border: 2px solid #e8e5e0;
        border-radius: 8px;
        padding: 1.25rem;
        margin-top: 1.5rem;
    }

    .services-title {
        text-align: center;
        font-weight: 700;
        color: var(--bulletin-blue);
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 1.5px;
        padding-bottom: 0.75rem;
        margin-bottom: 1rem;
        border-bottom: 2px solid var(--bulletin-gold);
    }

    .service-item {
        display: flex;
        padding: 8px 0;
        border-bottom: 1px dotted #ccc;
    }

    .service-item:last-child {
        border-bottom: none;
    }

    .service-type {
        flex: 0 0 100px;
        font-weight: 700;
        color: var(--bulletin-blue);
        font-size: 0.85rem;
        text-decoration: underline;
        text-decoration-color: var(--bulletin-gold);
    }

    .service-person {
        flex: 1;
        color: #333;
        font-size: 0.9rem;
    }

    .service-desc {
        font-size: 0.8rem;
        color: #888;
        margin-top: 2px;
        padding-left: 100px;
    }

    /* ===== ANNIVERSAIRES ===== */
    .birthday-box {
        background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
        border: 3px dashed var(--bulletin-gold);
        border-radius: 10px;
        padding: 1.25rem;
        margin-top: 1.5rem;
        text-align: center;
    }

    .birthday-title {
        color: var(--bulletin-red);
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 1.5px;
        margin-bottom: 0.75rem;
    }

    .birthday-title i {
        color: var(--bulletin-gold);
    }

    .birthday-item {
        padding: 6px 0;
        font-size: 0.9rem;
    }

    .birthday-day {
        color: var(--bulletin-red);
        font-weight: 700;
    }

    .birthday-name {
        color: #333;
    }

    .birthday-arrows {
        margin-top: 10px;
        color: var(--bulletin-gold);
        font-size: 1.5rem;
        letter-spacing: 8px;
    }

    /* ===== REFLEXIONS SPIRITUELLES ===== */
    .reflection-container {
        text-align: center;
    }

    .reflection-title {
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--bulletin-blue);
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid var(--bulletin-gold);
        display: inline-block;
    }

    .reflection-content {
        font-size: 0.95rem;
        line-height: 1.9;
        color: #444;
        text-align: justify;
        font-style: italic;
    }

    .reflection-content::first-letter {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--bulletin-blue);
        float: left;
        margin-right: 8px;
        margin-top: 4px;
        line-height: 1;
    }

    .reflection-source {
        margin-top: 1.25rem;
        text-align: right;
        font-size: 0.85rem;
        color: #666;
        font-style: italic;
        border-top: 1px solid #eee;
        padding-top: 0.75rem;
    }

    /* ===== THEOLOGIE ===== */
    .theology-container {
        text-align: center;
    }

    .theology-title {
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--bulletin-gold);
        font-style: italic;
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid var(--bulletin-blue);
        display: inline-block;
    }

    .theology-content {
        font-size: 0.95rem;
        line-height: 1.9;
        color: #444;
        text-align: justify;
    }

    .theology-content::first-letter {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--bulletin-gold);
        float: left;
        margin-right: 8px;
        margin-top: 4px;
        line-height: 1;
    }

    /* ===== MESSAGE DU RECTEUR ===== */
    .rector-message {
        background: linear-gradient(135deg, var(--bulletin-blue) 0%, #0d1b2a 100%);
        color: #fff;
        padding: 1.25rem;
        border-radius: 8px;
        text-align: center;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        font-size: 0.9rem;
        margin-top: 1.5rem;
        box-shadow: 0 4px 15px rgba(26, 54, 93, 0.3);
        position: relative;
    }

    .rector-message::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--bulletin-gold);
    }

    /* ===== PIED DE PAGE ===== */
    .bulletin-footer {
        margin-top: 2rem;
        padding-top: 1.5rem;
        border-top: 3px double #ddd;
        text-align: center;
    }

    .footer-ornament {
        color: var(--bulletin-gold);
        font-size: 1.5rem;
        letter-spacing: 15px;
        margin-bottom: 1rem;
    }

    .footer-text {
        font-size: 0.85rem;
        color: #888;
        font-style: italic;
    }

    .footer-actions {
        margin-top: 1.5rem;
        display: flex;
        justify-content: center;
        gap: 1rem;
        flex-wrap: wrap;
    }

    /* ===== EMPTY STATES ===== */
    .empty-content {
        text-align: center;
        padding: 2rem;
        color: #999;
        font-style: italic;
    }

    .empty-content i {
        font-size: 2rem;
        margin-bottom: 0.5rem;
        display: block;
        opacity: 0.5;
    }

    /* ===== ETAT VIDE GLOBAL ===== */
    .empty-bulletin {
        text-align: center;
        padding: 4rem 2rem;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        max-width: 500px;
        margin: 2rem auto;
    }

    .empty-bulletin-icon {
        width: 120px;
        height: 120px;
        background: linear-gradient(135deg, var(--bulletin-blue) 0%, #0d1b2a 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.5rem;
    }

    .empty-bulletin-icon i {
        font-size: 50px;
        color: var(--bulletin-gold);
    }

    .empty-bulletin h4 {
        color: var(--bulletin-blue);
        margin-bottom: 0.5rem;
        font-weight: 700;
    }

    .empty-bulletin p {
        color: #888;
        margin-bottom: 1.5rem;
    }
</style>
@endpush

@section('content')
<div class="bulletin-page">
    @if($bulletin)
        <!-- En-tete du bulletin -->
        <div class="bulletin-masthead">
            <div class="masthead-decoration"></div>
            <div class="masthead-inner">
                <img src="{{ asset('assets/media/logos/itcj-logo.jpg') }}" alt="ITCJ" class="masthead-logo">
                <h1 class="masthead-title">{{ $bulletin->title }}</h1>
                <p class="masthead-subtitle">{{ $bulletin->subtitle ?? "Hebdomadaire d'information de la communaute jesuite de l'ITCJ" }}</p>

                <div class="masthead-divider"></div>

                <div class="masthead-meta">
                    <div class="masthead-meta-item">
                        <span class="masthead-meta-label">Edition</span>
                        <span class="masthead-meta-value">N{{ $bulletin->edition_number }}</span>
                    </div>
                    <div class="masthead-meta-item">
                        <span class="masthead-meta-label">Periode</span>
                        <span class="masthead-meta-value" style="font-size: 1rem;">{{ $bulletin->week_start->locale('fr')->isoFormat('D MMM') }} - {{ $bulletin->week_end->locale('fr')->isoFormat('D MMM YYYY') }}</span>
                    </div>
                    <div class="masthead-meta-item">
                        <span class="masthead-meta-label">Annee</span>
                        <span class="masthead-meta-value">{{ $bulletin->year }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenu du bulletin -->
        <div class="bulletin-content">
            <!-- COLONNE 1: Activites + Services + Anniversaires -->
            <div class="bulletin-section">
                <div class="section-header blue">
                    <i class="ki-duotone ki-calendar fs-5 me-2"><i class="path1"></i><i class="path2"></i></i>
                    Activites & Annonces
                </div>
                <div class="section-body">
                    @if($bulletin->weeklyActivities->count() > 0)
                        <p class="activity-intro">Les activites suivantes auront lieu au cours de cette semaine :</p>
                        @foreach($bulletin->weeklyActivities->sortBy('activity_date') as $activity)
                            <div class="activity-item">
                                @if($activity->activity_date)
                                    <span class="activity-date-badge">{{ $activity->formatted_date }}</span>
                                    @if($activity->activity_time)
                                        <span class="activity-time-badge">{{ $activity->formatted_time }}</span>
                                    @endif
                                @endif
                                <span class="activity-title">{{ $activity->title }}</span>
                                @if($activity->description)
                                    <span class="activity-desc">{{ $activity->description }}</span>
                                @endif
                                @if($activity->location)
                                    <span class="activity-location">
                                        <i class="ki-duotone ki-geolocation fs-8"><i class="path1"></i><i class="path2"></i></i>
                                        {{ $activity->location }}
                                    </span>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <div class="empty-content">
                            <i class="ki-duotone ki-calendar-remove"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i></i>
                            Aucune activite programmee
                        </div>
                    @endif

                    <!-- Services -->
                    @if($bulletin->weeklyServices->count() > 0)
                        <div class="services-box">
                            <div class="services-title">
                                <i class="ki-duotone ki-people fs-6 me-1"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i><i class="path5"></i></i>
                                Services de la Semaine
                            </div>
                            @foreach($bulletin->weeklyServices as $service)
                                <div class="service-item">
                                    <span class="service-type">{{ $service->service_label }}</span>
                                    <span class="service-person">{{ $service->assigned_display_name }}</span>
                                </div>
                                @if($service->description)
                                    <div class="service-desc">{{ $service->description }}</div>
                                @endif
                            @endforeach
                        </div>
                    @endif

                    <!-- Anniversaires -->
                    @if($birthdays->count() > 0)
                        <div class="birthday-box">
                            <div class="birthday-title">
                                <i class="ki-duotone ki-gift fs-5 me-1"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i></i>
                                Joyeux Anniversaire !
                            </div>
                            @foreach($birthdays as $person)
                                <div class="birthday-item">
                                    <span class="birthday-day">{{ $person->birthday_day }}</span> :
                                    <span class="birthday-name">{{ $person->title_prefix }} {{ $person->name }}</span>
                                </div>
                            @endforeach
                            <div class="birthday-arrows">&#10148; &#10148; &#10148; &#10148;</div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- COLONNE 2: Spiritualite -->
            <div class="bulletin-section">
                <div class="section-header red">
                    <i class="ki-duotone ki-heart fs-5 me-2"><i class="path1"></i><i class="path2"></i></i>
                    Spiritualite
                </div>
                <div class="section-body">
                    @php
                        $spiritualite = $bulletin->spiritualReflections->where('type', 'spiritualite')->first();
                    @endphp
                    @if($spiritualite)
                        <div class="reflection-container">
                            <h4 class="reflection-title">{{ $spiritualite->title }}</h4>
                        </div>
                        <div class="reflection-content">
                            {!! nl2br(e($spiritualite->content)) !!}
                        </div>
                        @if($spiritualite->source)
                            <div class="reflection-source">
                                <i class="ki-duotone ki-book fs-7 me-1"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i></i>
                                {{ $spiritualite->source }}
                            </div>
                        @endif
                    @else
                        <div class="empty-content">
                            <i class="ki-duotone ki-heart"><i class="path1"></i><i class="path2"></i></i>
                            Aucune reflexion spirituelle
                        </div>
                    @endif
                </div>
            </div>

            <!-- COLONNE 3: Theologie + Message -->
            <div class="bulletin-section">
                <div class="section-header gold">
                    <i class="ki-duotone ki-book-open fs-5 me-2"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i></i>
                    Theologie
                </div>
                <div class="section-body">
                    @php
                        $theologie = $bulletin->spiritualReflections->where('type', 'theologie')->first();
                    @endphp
                    @if($theologie)
                        <div class="theology-container">
                            <h4 class="theology-title">{{ $theologie->title }}</h4>
                        </div>
                        <div class="theology-content">
                            {!! nl2br(e($theologie->content)) !!}
                        </div>
                        @if($theologie->source)
                            <div class="reflection-source">
                                <i class="ki-duotone ki-book fs-7 me-1"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i></i>
                                {{ $theologie->source }}
                            </div>
                        @endif
                    @else
                        <div class="empty-content">
                            <i class="ki-duotone ki-book-open"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i></i>
                            Aucune reflexion theologique
                        </div>
                    @endif

                    <div class="rector-message">
                        {{ $rectorMessage }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Pied de page -->
        <div class="bulletin-footer">
            <div class="footer-ornament">&#10022; &#10022; &#10022;</div>
            <p class="footer-text">
                Institut de Theologie de la Compagnie de Jesus<br>
                Communaute Jesuite - {{ $bulletin->year }}
            </p>
            @if(auth()->user()->isAdmin())
                <div class="footer-actions">
                    <a href="{{ route('bulletin.edit', $bulletin) }}" class="btn btn-itcj">
                        <i class="ki-duotone ki-pencil me-2"><i class="path1"></i><i class="path2"></i></i>
                        Modifier ce bulletin
                    </a>
                    <a href="{{ route('bulletin.index') }}" class="btn btn-itcj-outline">
                        <i class="ki-duotone ki-folder me-2"><i class="path1"></i><i class="path2"></i></i>
                        Gestion des editions
                    </a>
                </div>
            @endif
        </div>
    @else
        <!-- Aucun bulletin disponible -->
        <div class="empty-bulletin">
            <div class="empty-bulletin-icon">
                <i class="ki-duotone ki-document"><i class="path1"></i><i class="path2"></i></i>
            </div>
            <h4>Aucun bulletin disponible</h4>
            <p>Le bulletin hebdomadaire n'a pas encore ete publie pour cette semaine.</p>
            @if(auth()->user()->isAdmin())
                <a href="{{ route('bulletin.create') }}" class="btn btn-itcj">
                    <i class="ki-duotone ki-plus me-2"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                    Creer un bulletin
                </a>
            @endif
        </div>
    @endif
</div>
@endsection
