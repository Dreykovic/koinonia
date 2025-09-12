<div id="kt_app_header" class="app-header" role="banner">
    <div class="app-container container-fluid d-flex align-items-stretch justify-content-between" id="kt_app_header_container">

        <!-- Mobile toggle -->
        <div class="d-flex align-items-center d-lg-none ms-n2 me-2">
            <button type="button"
                    class="btn btn-icon btn-active-color-primary w-35px h-35px"
                    id="kt_app_sidebar_mobile_toggle"
                    aria-label="Ouvrir le menu de navigation"
                    aria-expanded="false"
                    aria-controls="kt_app_sidebar">
                <i class="ki-duotone ki-abstract-14 fs-1" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
            </button>
        </div>

        <!-- Logo mobile -->
        <div class="d-flex align-items-center flex-grow-1 flex-lg-grow-0 d-lg-none">
            <a href="{{ route('dashboard') }}" class="d-flex align-items-center" aria-label="ITCJ Community - Retour à l'accueil">
                <img src="{{ asset('assets/media/logos/itcj-logo.jpg') }}" alt="Logo ITCJ Community" class="h-35px rounded-2">
            </a>
        </div>

        <!-- Header content -->
        <div class="d-flex align-items-center justify-content-between flex-lg-grow-1">
            <!-- Page title -->
            <div class="d-none d-lg-flex align-items-center" id="kt_header_nav">
                <div class="page-title">
                    <h1 class="page-heading text-dark fw-bold fs-4 my-0">@yield('page-title', 'Dashboard')</h1>
                </div>
            </div>

            <!-- Actions rapides + Widget Repas + User menu -->
            <div class="app-navbar flex-shrink-0 d-flex align-items-center gap-2">

                <!-- === ACTIONS RAPIDES === -->
                <nav class="d-none d-md-flex align-items-center gap-2 me-2" aria-label="Actions rapides">
                    <!-- Bouton Réserver véhicule -->
                    <a href="{{ route('vehicles.planning') }}"
                       class="btn btn-icon btn-neu"
                       data-tooltip="Réserver un véhicule"
                       aria-label="Accéder au planning des véhicules pour réserver">
                        <i class="ki-duotone ki-car-2 fs-4" aria-hidden="true">
                            <i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i><i class="path5"></i>
                        </i>
                    </a>

                    <!-- Bouton Nouvelle demande -->
                    <a href="{{ route('community-requests.form.step1') }}"
                       class="btn btn-icon btn-neu"
                       data-tooltip="Nouvelle demande"
                       aria-label="Faire une nouvelle demande de réparation ou achat">
                        <i class="ki-duotone ki-wrench fs-4" aria-hidden="true">
                            <i class="path1"></i><i class="path2"></i>
                        </i>
                    </a>
                </nav>

                <!-- === WIDGET REPAS DIMANCHE (Refait avec tons chauds) === -->
                @php
                    $mealWidget = \App\Http\Controllers\MealController::getUpcomingMealWidget();
                @endphp
                @if($mealWidget && $mealWidget['meal'])
                    @php
                        $meal = $mealWidget['meal'];
                        $isRegistered = $mealWidget['is_registered'];
                        $pct = $meal->max_capacity > 0 ? ($meal->confirmed_count / $meal->max_capacity) * 100 : 0;
                    @endphp
                    <div class="d-none d-md-flex align-items-center" id="meal-widget" role="region" aria-label="Repas du dimanche">

                        <!-- Widget compact avec bouton d'action direct -->
                        <div class="meal-header-widget-warm d-flex align-items-center gap-3 px-4 py-2 rounded-3"
                             style="background: linear-gradient(135deg, #2C2420 0%, #1a1512 100%);
                                    box-shadow: 0 4px 15px rgba(44, 36, 32, 0.3);
                                    border: 1px solid rgba(212, 168, 75, 0.2);">

                            <!-- Icône et infos -->
                            <div class="d-flex align-items-center gap-2 cursor-pointer"
                                 data-kt-menu-trigger="click"
                                 data-kt-menu-attach="parent"
                                 data-kt-menu-placement="bottom-end"
                                 role="button"
                                 aria-expanded="false"
                                 aria-haspopup="true"
                                 aria-label="Voir les détails du repas du {{ $meal->formatted_date }}">
                                <div class="meal-icon-wrapper d-flex align-items-center justify-content-center rounded-circle"
                                     style="width: 38px; height: 38px; background: rgba(212, 168, 75, 0.15);">
                                    <i class="ki-duotone ki-dish fs-3" style="color: var(--accent);" aria-hidden="true">
                                        <i class="path1"></i><i class="path2"></i>
                                    </i>
                                </div>
                                <div class="d-flex flex-column lh-sm">
                                    <span class="text-white fs-8 fw-semibold">{{ $meal->short_date }}</span>
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="fs-9" style="color: rgba(255,255,255,0.7);">{{ $meal->confirmed_count }}/{{ $meal->max_capacity }}</span>
                                        <!-- Mini barre de progression -->
                                        <div class="progress h-4px flex-grow-1" style="width: 40px; background: rgba(255,255,255,0.15);">
                                            <div class="progress-bar" role="progressbar"
                                                 style="width: {{ $pct }}%; background: {{ $pct > 90 ? '#ef4444' : ($pct > 70 ? '#D4A84B' : '#5D8A66') }};"
                                                 aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"
                                                 aria-label="{{ round($pct) }}% des places réservées"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Bouton d'action DIRECT (plus intuitif) -->
                            @if($isRegistered)
                                <span class="badge d-flex align-items-center gap-1 px-3 py-2 rounded-2"
                                      style="background: rgba(93, 138, 102, 0.25); color: #7AB785;"
                                      role="status">
                                    <i class="ki-duotone ki-check-circle fs-7" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
                                    <span class="fs-9 fw-semibold">Inscrit</span>
                                </span>
                            @elseif($meal->canRegister())
                                <a href="{{ route('meals.show', $meal) }}"
                                   class="btn btn-sm d-flex align-items-center gap-1 px-3 py-2"
                                   style="background: linear-gradient(135deg, #5D8A66, #4A7553); color: #fff; border: none; border-radius: 8px;"
                                   data-tooltip="S'inscrire au repas"
                                   aria-label="S'inscrire au repas du {{ $meal->formatted_date }}">
                                    <i class="ki-duotone ki-check fs-7" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
                                    <span class="fs-9 fw-semibold">S'inscrire</span>
                                </a>
                            @elseif($meal->isFull())
                                <span class="badge d-flex align-items-center gap-1 px-3 py-2 rounded-2"
                                      style="background: rgba(255,255,255,0.15); color: rgba(255,255,255,0.6);"
                                      role="status">
                                    <i class="ki-duotone ki-information fs-7" aria-hidden="true"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                                    <span class="fs-9">Complet</span>
                                </span>
                            @else
                                <span class="badge d-flex align-items-center gap-1 px-3 py-2 rounded-2"
                                      style="background: rgba(255,255,255,0.1); color: rgba(255,255,255,0.5);"
                                      role="status">
                                    <i class="ki-duotone ki-lock fs-7" aria-hidden="true"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                                    <span class="fs-9">Fermé</span>
                                </span>
                            @endif

                            <!-- Dropdown menu compact -->
                            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-3 fs-7 w-250px"
                                 data-kt-menu="true"
                                 role="menu"
                                 aria-label="Détails du repas">
                                <!-- En-tête compact -->
                                <div class="px-3 pb-2 mb-2 border-bottom">
                                    <div class="d-flex align-items-center">
                                        <div class="symbol symbol-35px me-2">
                                            <span class="symbol-label" style="background: linear-gradient(135deg, var(--accent-light), var(--accent));">
                                                <i class="ki-duotone ki-dish fs-5 text-white" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
                                            </span>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark fs-7">Repas du Dimanche</div>
                                            <div class="text-muted fs-8">{{ $meal->formatted_date }}</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Statistiques compactes -->
                                <div class="px-3 mb-2">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="text-muted fs-8">Places</span>
                                        <span class="fw-bold fs-7" style="color: var(--text-primary);">{{ $meal->confirmed_count }}/{{ $meal->max_capacity }}</span>
                                    </div>
                                    <div class="progress h-6px mb-2" style="background: rgba(60, 48, 40, 0.1); border-radius: 4px;">
                                        @php
                                            $progressColor = $pct > 90 ? '#ef4444' : ($pct > 70 ? '#D4A84B' : '#5D8A66');
                                        @endphp
                                        <div class="progress-bar" role="progressbar"
                                             style="width: {{ $pct }}%; background: {{ $progressColor }}; border-radius: 4px;"
                                             aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>

                                <div class="separator mb-2"></div>

                                <!-- État et actions compactes -->
                                <div class="px-3">
                                    @if($isRegistered)
                                        <div class="d-flex align-items-center mb-2 p-2 rounded-2" style="background: rgba(93, 138, 102, 0.1);">
                                            <i class="ki-duotone ki-check-circle fs-5 me-2" style="color: #5D8A66;" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
                                            <span class="fw-semibold fs-8" style="color: #5D8A66;">Vous êtes inscrit</span>
                                        </div>
                                        @if($meal->canCancel())
                                            <form action="{{ route('meals.unregister', $meal) }}" method="POST"
                                                  data-confirm="Annuler votre inscription au repas ?"
                                                  data-confirm-title="Annuler l'inscription"
                                                  data-confirm-ok="Oui, annuler">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-light-danger btn-sm w-100 py-2">
                                                    <i class="ki-duotone ki-cross fs-7 me-1" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
                                                    Annuler
                                                </button>
                                            </form>
                                        @endif
                                    @else
                                        @if($meal->canRegister())
                                            <a href="{{ route('meals.show', $meal) }}" class="btn btn-success btn-sm w-100 py-2">
                                                <i class="ki-duotone ki-check fs-7 me-1" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
                                                S'inscrire
                                            </a>
                                            <div class="text-center mt-1">
                                                <span class="text-muted fs-9">{{ $meal->remaining_places }} places restantes</span>
                                            </div>
                                        @elseif($meal->isFull())
                                            <div class="text-center py-2">
                                                <span class="fw-semibold text-muted fs-8">Complet</span>
                                            </div>
                                        @else
                                            <div class="text-center py-2">
                                                <span class="fw-semibold text-muted fs-8">Fermé</span>
                                            </div>
                                        @endif
                                    @endif
                                </div>

                                <div class="separator my-2"></div>

                                <div class="menu-item px-3" role="none">
                                    <a href="{{ route('meals.index') }}" class="menu-link px-3 py-2 fs-8" role="menuitem"
                                       style="color: var(--primary);">
                                        Voir tous les repas
                                        <i class="ki-duotone ki-arrow-right fs-8 ms-1" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- === USER MENU === -->
                <div class="app-navbar-item ms-2" id="kt_header_user_menu_toggle">
                    <button type="button"
                            class="cursor-pointer d-flex align-items-center bg-hover-light rounded p-2 border-0 bg-transparent"
                            data-kt-menu-trigger="click"
                            data-kt-menu-attach="parent"
                            data-kt-menu-placement="bottom-end"
                            aria-expanded="false"
                            aria-haspopup="true"
                            aria-label="Menu utilisateur - {{ auth()->user()->name }}">
                        <div class="symbol symbol-35px">
                            <span class="symbol-label bg-itcj-blue text-white fw-bold fs-6" aria-hidden="true">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </span>
                        </div>
                        <div class="d-none d-md-flex flex-column ms-3 me-2">
                            <span class="fw-bold text-dark fs-7">{{ auth()->user()->name }}</span>
                            <span class="text-muted fs-8">{{ auth()->user()->role_label }}</span>
                        </div>
                        <i class="ki-duotone ki-down fs-6 text-muted d-none d-md-inline" aria-hidden="true"></i>
                    </button>

                    <!-- User Dropdown -->
                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-4 fs-6 w-275px"
                         data-kt-menu="true"
                         role="menu"
                         aria-label="Menu utilisateur">
                        <!-- User info -->
                        <div class="menu-item px-3" role="none">
                            <div class="menu-content d-flex align-items-center px-3">
                                <div class="symbol symbol-50px me-4" aria-hidden="true">
                                    <span class="symbol-label bg-itcj-blue text-white fw-bold fs-3">
                                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                    </span>
                                </div>
                                <div>
                                    <div class="fw-bold fs-5 text-dark">{{ auth()->user()->name }}</div>
                                    <span class="text-muted fs-7">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="separator my-2" role="separator"></div>

                        <div class="menu-item px-5" role="none">
                            <a href="{{ route('profile.show') }}" class="menu-link px-5" role="menuitem">
                                <i class="ki-duotone ki-user fs-5 me-2" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
                                Mon Profil
                            </a>
                        </div>

                        <div class="menu-item px-5" role="none">
                            <a href="{{ route('reservations.index') }}" class="menu-link px-5" role="menuitem">
                                <i class="ki-duotone ki-calendar fs-5 me-2" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
                                Mes Réservations
                            </a>
                        </div>

                        <div class="menu-item px-5" role="none">
                            <a href="{{ route('meals.index') }}" class="menu-link px-5" role="menuitem">
                                <i class="ki-duotone ki-dish fs-5 me-2" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
                                Repas Dimanche
                            </a>
                        </div>

                        <div class="separator my-2" role="separator"></div>

                        <div class="menu-item px-5" role="none">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                        class="menu-link px-5 w-100 text-start border-0 bg-transparent text-danger"
                                        role="menuitem"
                                        aria-label="Se déconnecter de l'application">
                                    <i class="ki-duotone ki-exit-right fs-5 me-2" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
                                    Déconnexion
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
