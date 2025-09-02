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
                        <a class="menu-link py-3"
                           href="{{ route('home') }}"
                           role="menuitem">
                            <span class="menu-icon" aria-hidden="true">
                                <i class="ki-duotone ki-document fs-2"><i class="path1"></i><i class="path2"></i></i>
                            </span>
                            <span class="menu-title">Bulletin Hebdomadaire</span>
                        </a>
                    </li>

                    <li class="menu-item mb-1" role="none">
                        <a class="menu-link py-3"
                           href="{{ route('dashboard') }}"
                           role="menuitem">
                            <span class="menu-icon" aria-hidden="true">
                                <i class="ki-duotone ki-home-2 fs-2"><i class="path1"></i><i class="path2"></i></i>
                            </span>
                            <span class="menu-title">Tableau de bord</span>
                        </a>
                    </li>
                </ul>

            </nav>
        </div>
    </div>

    <!-- Footer Section -->
    <div class="app-sidebar-footer flex-column-auto" id="kt_app_sidebar_footer">
        <a href="{{ route('profile.show') }}"
           class="btn btn-flex flex-center btn-itcj-gold w-100 py-3"
           aria-label="Accéder à mon profil utilisateur">
            <i class="ki-duotone ki-user fs-2 me-2" aria-hidden="true"><i class="path1"></i><i class="path2"></i></i>
            <span class="btn-label">Mon Profil</span>
        </a>
    </div>
</aside>
