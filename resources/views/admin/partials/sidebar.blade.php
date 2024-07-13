<aside class="app-sidebar sticky" id="sidebar">

    <!-- Start::main-sidebar-header -->
    <div class="main-sidebar-header">
        <a href="./" class="header-logo">
            <img src="{{asset('assets')}}/images/simu-logo.png" alt="logo" class="desktop-logo">
            <img src="{{asset('assets')}}/images/simu-logo.png" alt="logo" class="toggle-logo">
            <img src="{{asset('assets')}}/images/simu-logo.png" alt="logo" class="desktop-dark">
            <img src="{{asset('assets')}}/images/simu-icon.png" alt="logo" class="toggle-dark">
            <img src="{{asset('assets')}}/images/simu-logo.png" alt="logo" class="desktop-white">
            <img src="{{asset('assets')}}/images/simu-logo.png" alt="logo" class="toggle-white">
        </a>
    </div>
    <!-- End::main-sidebar-header -->

    <!-- Start::main-sidebar -->
    <div class="main-sidebar" id="sidebar-scroll" data-simplebar="init"><div class="simplebar-wrapper" style="margin: -8px 0px -80px;"><div class="simplebar-height-auto-observer-wrapper"><div class="simplebar-height-auto-observer"></div></div><div class="simplebar-mask"><div class="simplebar-offset" style="right: 0px; bottom: 0px;"><div class="simplebar-content-wrapper" tabindex="0" role="region" aria-label="scrollable content" style="height: 100%; overflow: hidden scroll;"><div class="simplebar-content" style="padding: 8px 0px 80px;">

        <!-- Start::nav -->
        <nav class="main-menu-container nav nav-pills flex-column sub-open active">
            <div class="slide-left active d-none" id="slide-left">
                <svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24" viewBox="0 0 24 24"> <path d="M13.293 6.293 7.586 12l5.707 5.707 1.414-1.414L10.414 12l4.293-4.293z"></path> </svg>
            </div>
            <ul class="main-menu active" style="margin-left: 0px; margin-right: 0px;">

                <li class="slide {{ Request::is('admin/dashboard') ? 'active' : '' }}">
                    <a href="{{route('admin.dashboard')}}" class="side-menu__item {{ Request::is('admin/dashboard') ? 'active' : '' }}">
                        <i class="bx bx-home side-menu__icon"></i>
                        <span class="side-menu__label">Dashboard</span>
                    </a>
                </li>

                <li class="slide {{ Request::is('admin/partners') ? 'active' : '' }}">
                    <a href="{{route('admin.partners')}}" class="side-menu__item {{ Request::is('admin/partners') ? 'active' : '' }}">
                        <i class="bx bx-group side-menu__icon"></i>
                        <span class="side-menu__label">Partners</span>
                    </a>
                </li>

                <li class="slide {{ Request::is('admin/companies') ? 'active' : '' }}">
                    <a href="{{route('admin.companies')}}" class="side-menu__item {{ Request::is('admin/companies') ? 'active' : '' }}">
                        <i class="bx bx-buildings side-menu__icon"></i>
                        <span class="side-menu__label">Companies</span>
                    </a>
                </li>
                <li class="slide {{ Request::is('admin/whitelabel-req') ? 'active' : '' }}">
                    <a href="{{route('admin.whitelabel')}}" class="side-menu__item {{ Request::is('admin/whitelabel-req') ? 'active' : '' }}">
                        <i class="bx bx-leaf side-menu__icon"></i>
                        <span class="side-menu__label">Whitelabel Requests</span>
                    </a>
                </li>
                <!-- <li class="slide">
                    <a href="employees.php" class="side-menu__item">
                        <i class="bx bx-group side-menu__icon"></i>
                        <span class="side-menu__label">Employees</span>
                    </a>
                </li>
                <li class="slide">
                    <a href="campaignReports.php" class="side-menu__item">
                        <i class="bx bx-spreadsheet side-menu__icon"></i>
                        <span class="side-menu__label">Reporting</span>
                    </a>
                </li> -->
                <li class="slide has-sub {{ Request::is(['admin/phishing-emails', 'admin/phishing-websites', 'admin/sender-profiles']) ? 'open' : '' }}">
                    <a href="javascript:void(0);" class="side-menu__item {{ Request::is(['admin/phishing-emails', 'admin/phishing-websites', 'admin/sender-profiles']) ? 'active' : '' }}">
                        <i class="bx bx-laptop side-menu__icon"></i>
                        <span class="side-menu__label">Phishing Material</span>
                        <i class="fe fe-chevron-right side-menu__angle"></i>
                    </a>
                    <ul class="slide-menu child1 {{ Request::is(['admin/phishing-emails', 'admin/phishing-websites', 'admin/sender-profiles']) ? 'active' : '' }}"
                        style="position: relative; left: 0px; top: 0px; margin: 0px; transform: translate3d(119.2px, 287.2px, 0px);"
                        data-popper-placement="bottom">
                        <li class="slide side-menu__label1">
                            <a href="javascript:void(0)">Phishing Material</a>
                        </li>
                        <li class="slide {{ Request::is('admin/phishing-emails') ? 'active' : '' }}">
                            <a href="{{route('admin.phishingEmails')}}" class="side-menu__item {{ Request::is('admin/phishing-emails') ? 'active' : '' }}">Phishing Emails</a>
                        </li>
                        <li class="slide {{ Request::is('admin/phishing-websites') ? 'active' : '' }}">
                            <a href="{{route('phishing.websites')}}" class="side-menu__item {{ Request::is('admin/phishing-websites') ? 'active' : '' }}">Phishing
                                Websites</a>
                        </li>
                        <li class="slide {{ Request::is('admin/sender-profiles') ? 'active' : '' }}">
                            <a href="{{route('senderprofile.index')}}" class="side-menu__item {{ Request::is('admin/sender-profiles') ? 'active' : '' }}">Sender Profiles</a>
                        </li>
                    </ul>
                </li>
                <li class="slide {{ Request::is('training-modules') ? 'active' : '' }}">
                    <a href="{{route('trainingmodule.index')}}" class="side-menu__item {{ Request::is('training-modules') ? 'active' : '' }}">
                        <i class="bx bx-windows side-menu__icon"></i>
                        <span class="side-menu__label">Training Modules</span>
                    </a>
                </li>

                
            </ul>
            <div class="slide-right d-none" id="slide-right"><svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24" viewBox="0 0 24 24"> <path d="M10.707 17.707 16.414 12l-5.707-5.707-1.414 1.414L13.586 12l-4.293 4.293z"></path> </svg></div>
        </nav>
        <!-- End::nav -->

    </div></div></div></div><div class="simplebar-placeholder" style="width: auto; height: 366px;"></div></div><div class="simplebar-track simplebar-horizontal" style="visibility: hidden;"><div class="simplebar-scrollbar" style="width: 0px; display: none;"></div></div><div class="simplebar-track simplebar-vertical" style="visibility: visible;"><div class="simplebar-scrollbar" style="height: 118px; display: block; transform: translate3d(0px, 0px, 0px);"></div></div></div>
    <!-- End::main-sidebar -->

</aside>