<aside id="kt_app_sidebar"
       class="app-sidebar flex-column"
       role="navigation"
       aria-label="Navigation principale"
       data-kt-drawer="true"
       data-kt-drawer-name="app-sidebar"
       data-kt-drawer-activate="{default: true, lg: false}"
       data-kt-drawer-overlay="true"
       data-kt-drawer-width="280px"
       data-kt-drawer-direction="start"
       data-kt-drawer-toggle="#kt_app_sidebar_mobile_toggle">

    <!-- Logo Section -->
    <div class="app-sidebar-logo" id="kt_app_sidebar_logo">
        <a href="{{ route('home') }}"
           class="d-flex align-items-center text-decoration-none"
           aria-label="ITCJ Community - Retour à l'accueil">
            <img src="{{ asset('assets/media/logos/itcj-logo.jpg') }}" alt="Logo ITCJ" class="h-45px rounded-3 shadow-sm">
            <div class="ms-3">
                <span class="fs-4 fw-bold d-block" style="color: var(--text-primary); line-height: 1.2;">ITCJ</span>
                <span class="fs-8 fw-semibold" style="color: var(--primary);">Community</span>
            </div>
        </a>
    </div>

    <!-- Menu Section -->
    <div class="app-sidebar-menu overflow-hidden flex-column-fluid">
        <div id="kt_app_sidebar_menu_wrapper" class="app-sidebar-wrapper hover-scroll-overlay-y my-3"
            data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-height="auto"
            data-kt-scroll-dependencies="#kt_app_sidebar_logo, #kt_app_sidebar_footer"
            data-kt-scroll-wrappers="#kt_app_sidebar_menu" data-kt-scroll-offset="5px"
            data-kt-scroll-save-state="true">

            <nav class="menu menu-column menu-rounded menu-sub-indention px-2"
                 id="#kt_app_sidebar_menu"
                 data-kt-menu="true"
                 data-kt-menu-expand="false"
                 aria-label="Menu de navigation">

                <!-- === SECTION: Principal === -->
                <ul class="list-unstyled mb-0" role="menu" aria-label="Navigation principale">
                    <li class="menu-item mb-1" role="none">
                        <a class="menu-link py-3 {{ request()->routeIs('home', 'bulletin.current') ? 'active' : '' }}"
                           href="{{ route('home') }}"
                           role="menuitem"
                           aria-current="{{ request()->routeIs('home', 'bulletin.current') ? 'page' : 'false' }}"
                           data-tooltip="Voir le bulletin de la semaine">
                            <span class="menu-icon" aria-hidden="true">
                                <i class="ki-duotone ki-document fs-2"><i class="path1"></i><i class="path2"></i></i>
                            </span>
                            <span class="menu-title">Bulletin Hebdomadaire</span>
                        </a>
                    </li>

                    <li class="menu-item mb-1" role="none">
                        <a class="menu-link py-3 {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                           href="{{ route('dashboard') }}"
                           role="menuitem"
                           aria-current="{{ request()->routeIs('dashboard') ? 'page' : 'false' }}"
                           data-tooltip="Accéder à mon tableau de bord">
                            <span class="menu-icon" aria-hidden="true">
                                <i class="ki-duotone ki-home-2 fs-2"><i class="path1"></i><i class="path2"></i></i>
                            </span>
                            <span class="menu-title">Tableau de bord</span>
                        </a>
                    </li>

                    <li class="menu-item mb-1" role="none">
                        <a class="menu-link py-3 {{ request()->routeIs('guide') ? 'active' : '' }}"
                           href="{{ route('guide') }}"
                           role="menuitem"
                           aria-current="{{ request()->routeIs('guide') ? 'page' : 'false' }}"
                           data-tooltip="Consulter le guide d'utilisation">
                            <span class="menu-icon" aria-hidden="true">
                                <i class="ki-duotone ki-information fs-2"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                            </span>
                            <span class="menu-title">Guide</span>
                        </a>
                    </li>
                </ul>

                <!-- === SECTION: Services === -->
                <div class="menu-item pt-4 pb-1" role="presentation">
                    <div class="menu-content">
                        <span class="menu-heading fw-bold text-uppercase fs-8 ls-1" id="services-heading">Services</span>
                    </div>
                </div>

                <ul class="list-unstyled mb-0" role="menu" aria-labelledby="services-heading">
                    <li class="menu-item mb-1" role="none">
                        <a class="menu-link py-3 {{ request()->routeIs('vehicles.planning') ? 'active' : '' }}"
                           href="{{ route('vehicles.planning') }}"
                           role="menuitem"
                           aria-current="{{ request()->routeIs('vehicles.planning') ? 'page' : 'false' }}"
                           data-tooltip="Voir et réserver un véhicule">
                            <span class="menu-icon" aria-hidden="true">
                                <i class="ki-duotone ki-map fs-2"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                            </span>
                            <span class="menu-title">Planning Véhicules</span>
                        </a>
                    </li>

                    <li class="menu-item mb-1" role="none">
                        <a class="menu-link py-3 {{ request()->routeIs('reservations.*') ? 'active' : '' }}"
                           href="{{ route('reservations.index') }}"
                           role="menuitem"
                           aria-current="{{ request()->routeIs('reservations.*') ? 'page' : 'false' }}"
                           data-tooltip="Gérer mes réservations de véhicules">
                            <span class="menu-icon" aria-hidden="true">
                                <i class="ki-duotone ki-car-2 fs-2"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i><i class="path5"></i></i>
                            </span>
                            <span class="menu-title">Mes Réservations</span>
                        </a>
                    </li>
                    {{-- TODO: modifier l'icon --}}
                 <li class="menu-item mb-1" role="none">
                        <a class="menu-link py-3 {{ request()->routeIs('week-menus.index*') ? 'active' : '' }}"
                           href="{{ route('week-menus.index') }}"
                           role="menuitem"
                           aria-current="{{ request()->routeIs('week-menus.index*') ? 'page' : 'false' }}"
                           data-tooltip="Consulter le menu">
                            <span class="menu-icon" aria-hidden="true">
                                <i class="ki-duotone ki-coffee fs-2"><i class="path1"></i><i class="path2"></i></i>
                            </span>
                            <span class="menu-title">Menu de la semaine</span>
                        </a>
                    </li>

                    <li class="menu-item mb-1" role="none">
                        <a class="menu-link py-3 {{ request()->routeIs('meals.*') ? 'active' : '' }}"
                           href="{{ route('meals.index') }}"
                           role="menuitem"
                           aria-current="{{ request()->routeIs('meals.*') ? 'page' : 'false' }}"
                           data-tooltip="S'inscrire aux repas du dimanche">
                            <span class="menu-icon" aria-hidden="true">
                                <i class="ki-duotone ki-gift fs-2"><i class="path1"></i><i class="path2"></i></i>
                            </span>
                            <span class="menu-title">Repas du Dimanche</span>
                        </a>
                    </li>

                    <li class="menu-item mb-1" role="none">
                        <a class="menu-link py-3 {{ request()->routeIs('community-requests.form.*') ? 'active' : '' }}"
                           href="{{ route('community-requests.form.step1') }}"
                           role="menuitem"
                           aria-current="{{ request()->routeIs('community-requests.form.*') ? 'page' : 'false' }}"
                           data-tooltip="Faire une demande de réparation ou achat">
                            <span class="menu-icon" aria-hidden="true">
                                <i class="ki-duotone ki-questionnaire-tablet fs-2"><i class="path1"></i><i class="path2"></i></i>
                            </span>
                            <span class="menu-title">Réparations & Achats</span>
                        </a>
                    </li>

                    <li class="menu-item mb-1" role="none">
                        <a class="menu-link py-3 {{ request()->routeIs('community-requests.index', 'community-requests.show') && !auth()->user()->isAdmin() ? 'active' : '' }}"
                           href="{{ route('community-requests.index') }}"
                           role="menuitem"
                           aria-current="{{ request()->routeIs('community-requests.index', 'community-requests.show') ? 'page' : 'false' }}"
                           data-tooltip="Voir mes demandes de réparation et achat">
                            <span class="menu-icon" aria-hidden="true">
                                <i class="ki-duotone ki-clipboard fs-2"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                            </span>
                            <span class="menu-title">Mes Demandes</span>
                        </a>
                    </li>
                </ul>

                @if(auth()->user()->isAdmin() || auth()->user()->isCommunicateur())
                <!-- === SECTION: Communication === -->
                <div class="menu-item pt-4 pb-1" role="presentation">
                    <div class="menu-content">
                        <span class="menu-heading fw-bold text-uppercase fs-8 ls-1" id="communication-heading">Communication</span>
                    </div>
                </div>

                <ul class="list-unstyled mb-0" role="menu" aria-labelledby="communication-heading">
                    <li class="menu-item mb-1" role="none">
                        <a class="menu-link py-3 {{ request()->routeIs('bulletin.index', 'bulletin.create', 'bulletin.edit') ? 'active' : '' }}"
                           href="{{ route('bulletin.index') }}"
                           role="menuitem"
                           aria-current="{{ request()->routeIs('bulletin.index', 'bulletin.create', 'bulletin.edit') ? 'page' : 'false' }}"
                           data-tooltip="Créer et gérer les bulletins">
                            <span class="menu-icon" aria-hidden="true">
                                <i class="ki-duotone ki-document fs-2"><i class="path1"></i><i class="path2"></i></i>
                            </span>
                            <span class="menu-title">Gestion Bulletin</span>
                        </a>
                    </li>
                </ul>
                @endif

                @if(auth()->user()->isAdmin())
                <!-- === SECTION: Administration === -->
                <div class="menu-item pt-4 pb-1" role="presentation">
                    <div class="menu-content">
                        <span class="menu-heading fw-bold text-uppercase fs-8 ls-1" id="admin-heading">Administration</span>
                    </div>
                </div>

                <ul class="list-unstyled mb-0" role="menu" aria-labelledby="admin-heading">
                    <li class="menu-item mb-1" role="none">
                        <a class="menu-link py-3 {{ request()->routeIs('vehicles.index', 'vehicles.create', 'vehicles.edit') ? 'active' : '' }}"
                           href="{{ route('vehicles.index') }}"
                           role="menuitem"
                           aria-current="{{ request()->routeIs('vehicles.index', 'vehicles.create', 'vehicles.edit') ? 'page' : 'false' }}"
                           data-tooltip="Ajouter et gérer les véhicules">
                            <span class="menu-icon" aria-hidden="true">
                                <i class="ki-duotone ki-setting-3 fs-2"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i><i class="path5"></i></i>
                            </span>
                            <span class="menu-title">Gestion Véhicules</span>
                        </a>
                    </li>
                    <li class="menu-item mb-1" role="none">
                        <a class="menu-link py-3 {{ request()->routeIs('week-menus.admin*') ? 'active' : '' }}"
                           href="{{ route('week-menus.admin.edit') }}"
                           role="menuitem"
                           aria-current="{{ request()->routeIs('week-menus.admin*') ? 'page' : 'false' }}"
                           data-tooltip="Gérer les plats et le cycle des menus">
                            <span class="menu-icon" aria-hidden="true">
                                <i class="ki-duotone ki-coffee fs-2"><i class="path1"></i><i class="path2"></i></i>
                            </span>
                            <span class="menu-title">Gestion Menu</span>
                        </a>
                    </li>
                    <li class="menu-item mb-1" role="none">
                        <a class="menu-link py-3 {{ request()->routeIs('meals.admin.*') ? 'active' : '' }}"
                           href="{{ route('meals.admin.index') }}"
                           role="menuitem"
                           aria-current="{{ request()->routeIs('meals.admin.*') ? 'page' : 'false' }}"
                           data-tooltip="Créer et gérer les repas">
                            <span class="menu-icon" aria-hidden="true">
                                <i class="ki-duotone ki-gift fs-2"><i class="path1"></i><i class="path2"></i></i>
                            </span>
                            <span class="menu-title">Gestion Repas</span>
                        </a>
                    </li>

                    <li class="menu-item mb-1" role="none">
                        <a class="menu-link py-3 {{ request()->routeIs('community-requests.index', 'community-requests.show', 'community-requests.statistics') ? 'active' : '' }}"
                           href="{{ route('community-requests.index') }}"
                           role="menuitem"
                           aria-current="{{ request()->routeIs('community-requests.index', 'community-requests.show', 'community-requests.statistics') ? 'page' : 'false' }}"
                           data-tooltip="Voir et traiter les demandes">
                            <span class="menu-icon" aria-hidden="true">
                                <i class="ki-duotone ki-questionnaire-tablet fs-2"><i class="path1"></i><i class="path2"></i></i>
                            </span>
                            <span class="menu-title">Gestion Demandes</span>
                        </a>
                    </li>

                    <li class="menu-item mb-1" role="none">
                        <a class="menu-link py-3 {{ request()->routeIs('users.*') ? 'active' : '' }}"
                           href="{{ route('users.index') }}"
                           role="menuitem"
                           aria-current="{{ request()->routeIs('users.*') ? 'page' : 'false' }}"
                           data-tooltip="Gérer les membres de la communauté">
                            <span class="menu-icon" aria-hidden="true">
                                <i class="ki-duotone ki-profile-user fs-2"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i></i>
                            </span>
                            <span class="menu-title">Membres</span>
                        </a>
                    </li>

                    @php
                        $pendingAccountRequests = \App\Models\AccountRequest::pending()->count();
                    @endphp
                    <li class="menu-item mb-1" role="none">
                        <a class="menu-link py-3 {{ request()->routeIs('account-requests.*') ? 'active' : '' }}"
                           href="{{ route('account-requests.index') }}"
                           role="menuitem"
                           aria-current="{{ request()->routeIs('account-requests.*') ? 'page' : 'false' }}"
                           aria-label="Demandes d'adhésion{{ $pendingAccountRequests > 0 ? ' - ' . $pendingAccountRequests . ' en attente' : '' }}"
                           data-tooltip="Approuver les nouvelles demandes d'adhésion">
                            <span class="menu-icon" aria-hidden="true">
                                <i class="ki-duotone ki-profile-user fs-2"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i></i>
                            </span>
                            <span class="menu-title">Demandes d'adhésion</span>
                            @if($pendingAccountRequests > 0)
                                <span class="badge badge-circle badge-sm"
                                      style="background: var(--primary); color: #fff;"
                                      aria-hidden="true">{{ $pendingAccountRequests }}</span>
                            @endif
                        </a>
                    </li>
                </ul>

                <!-- === SECTION: Configuration === -->
                <div class="menu-item pt-4 pb-1" role="presentation">
                    <div class="menu-content">
                        <span class="menu-heading fw-bold text-uppercase fs-8 ls-1" id="config-heading">Configuration</span>
                    </div>
                </div>

                <ul class="list-unstyled mb-0" role="menu" aria-labelledby="config-heading">
                    <li class="menu-item mb-1" role="none">
                        <a class="menu-link py-3 {{ request()->routeIs('communities.*') ? 'active' : '' }}"
                           href="{{ route('communities.index') }}"
                           role="menuitem"
                           aria-current="{{ request()->routeIs('communities.*') ? 'page' : 'false' }}"
                           data-tooltip="Configurer les communautés">
                            <span class="menu-icon" aria-hidden="true">
                                <i class="ki-duotone ki-people fs-2"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i><i class="path5"></i></i>
                            </span>
                            <span class="menu-title">Communautés</span>
                        </a>
                    </li>

                    <li class="menu-item mb-1" role="none">
                        <a class="menu-link py-3 {{ request()->routeIs('user-profile-vehicle-types.*') ? 'active' : '' }}"
                           href="{{ route('user-profile-vehicle-types.index') }}"
                           role="menuitem"
                           aria-current="{{ request()->routeIs('user-profile-vehicle-types.*') ? 'page' : 'false' }}"
                           data-tooltip="Gérer les lieux de réparation">
                            <span class="menu-icon" aria-hidden="true">
                                <i class="ki-duotone ki-car fs-2"><i class="path1"></i><i class="path2"></i></i>
                            </span>
                            <span class="menu-title">Acces Vehicule</span>
                        </a>
                    </li>
                    <li class="menu-item mb-1" role="none">
                        <a class="menu-link py-3 {{ request()->routeIs('repair-locations.*') ? 'active' : '' }}"
                           href="{{ route('repair-locations.index') }}"
                           role="menuitem"
                           aria-current="{{ request()->routeIs('repair-locations.*') ? 'page' : 'false' }}"
                           data-tooltip="Gérer les lieux de réparation">
                            <span class="menu-icon" aria-hidden="true">
                                <i class="ki-duotone ki-geolocation fs-2"><i class="path1"></i><i class="path2"></i></i>
                            </span>
                            <span class="menu-title">Lieux Réparation</span>
                        </a>
                    </li>

                    <li class="menu-item mb-1" role="none">
                        <a class="menu-link py-3 {{ request()->routeIs('admin.vehicle-settings.*') ? 'active' : '' }}"
                           href="{{ route('admin.vehicle-settings.edit') }}"
                           role="menuitem"
                           aria-current="{{ request()->routeIs('admin.vehicle-settings.*') ? 'page' : 'false' }}"
                           data-tooltip="Configurer les horaires de reservation">
                            <span class="menu-icon" aria-hidden="true">
                                <i class="ki-duotone ki-time fs-2"><i class="path1"></i><i class="path2"></i></i>
                            </span>
                            <span class="menu-title">Parametres Vehicules</span>
                        </a>
                    </li>

                    @php
                        $unresolvedEmails = \App\Models\FailedEmail::unresolved()->count();
                    @endphp
                    <li class="menu-item mb-1" role="none">
                        <a class="menu-link py-3 {{ request()->routeIs('admin.failed-emails.*') ? 'active' : '' }}"
                           href="{{ route('admin.failed-emails.index') }}"
                           role="menuitem"
                           aria-current="{{ request()->routeIs('admin.failed-emails.*') ? 'page' : 'false' }}"
                           aria-label="Emails échoués{{ $unresolvedEmails > 0 ? ' - ' . $unresolvedEmails . ' non résolus' : '' }}"
                           data-tooltip="Voir les emails qui n'ont pas pu être envoyés">
                            <span class="menu-icon" aria-hidden="true">
                                <i class="ki-duotone ki-sms fs-2"><i class="path1"></i><i class="path2"></i></i>
                            </span>
                            <span class="menu-title">Emails échoués</span>
                            @if($unresolvedEmails > 0)
                                <span class="badge badge-circle badge-sm"
                                      style="background: var(--itcj-red); color: #fff;"
                                      aria-hidden="true">{{ $unresolvedEmails }}</span>
                            @endif
                        </a>
                    </li>
                </ul>
                @endif

            </nav>
        </div>
    </div>

    <!-- Footer Section -->
    <div class="app-sidebar-footer flex-column-auto" id="kt_app_sidebar_footer">
        <a href="{{ route('profile.show') }}"
           class="btn btn-flex flex-center btn-itcj-gold w-100 py-3"
           aria-label="Accéder à mon profil utilisateur"
           data-tooltip="Modifier mes informations personnelles">
            <i class="ki-duotone ki-user fs-2 me-2" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
            <span class="btn-label">Mon Profil</span>
        </a>
    </div>
</aside>













