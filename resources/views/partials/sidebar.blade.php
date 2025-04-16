<style>
    .side-menu__label {
        white-space: normal !important;
        word-break: break-word;
        display: inline-block;
        width: 100%;
    }
</style>
<aside class="app-sidebar sticky" id="sidebar">

    <!-- Start::main-sidebar-header -->
    <div class="main-sidebar-header">
        <a href="{{ route('dashboard') }}" class="header-logo">
            <img src="{{ $companyLogoLight }}" alt="logo" class="desktop-logo">
            <img src="{{ $companyLogoLight }}" alt="logo" class="toggle-logo">
            <img src="{{ $companyLogoLight }}" alt="logo" class="desktop-dark">
            <img src="{{ $companyFavicon }}" alt="logo" class="toggle-dark">
            <img src="{{ $companyLogoLight }}" alt="logo" class="desktop-white">
            <img src="{{ $companyLogoLight }}" alt="logo" class="toggle-white">
        </a>
    </div>
    <!-- End::main-sidebar-header -->

    <!-- Start::main-sidebar -->
    <div class="main-sidebar" id="sidebar-scroll" data-simplebar="init">
        <div class="simplebar-wrapper" style="margin: -8px 0px -80px;">
            <div class="simplebar-height-auto-observer-wrapper">
                <div class="simplebar-height-auto-observer"></div>
            </div>
            <div class="simplebar-mask">
                <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                    <div class="simplebar-content-wrapper" tabindex="0" role="region" aria-label="scrollable content"
                        style="height: 100%; overflow: hidden;">
                        <div class="simplebar-content" style="padding: 8px 0px 80px;">

                            <!-- Start::nav -->
                            <nav class="main-menu-container nav nav-pills flex-column sub-open active">
                                <div class="slide-left active d-none" id="slide-left">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24"
                                        viewBox="0 0 24 24">
                                        <path d="M13.293 6.293 7.586 12l5.707 5.707 1.414-1.414L10.414 12l4.293-4.293z">
                                        </path>
                                    </svg>
                                </div>
                                <ul class="main-menu active" style="margin-left: 0px; margin-right: 0px;">


                                    <li class="slide {{ isActiveRoute('dashboard') }}">
                                        <a href="{{ url('/') }}"
                                            class="side-menu__item {{ isActiveRoute('dashboard') }}">
                                            <i class="bx bx-home side-menu__icon"></i>
                                            <span class="side-menu__label">{{ __('Dashboard') }}</span>
                                        </a>
                                    </li>
                                    {{-- Employee dffd --}}
                                    <li
                                        class="slide has-sub {{ Request::is('employees') || Request::is('blue-collar-employees') || Request::is('all-employees') ? 'open' : '' }}">
                                        <a href="javascript:void(0);"
                                            class="side-menu__item {{ Request::is('employees') || Request::is('blue-collar-employees') || Request::is('all-employees') ? 'active' : '' }}">
                                            <i class="bx bx-group side-menu__icon"></i>
                                            <span class="side-menu__label">{{ __('Employees') }}</span>
                                            <i class="fe fe-chevron-right side-menu__angle"></i>
                                        </a>
                                        <ul class="slide-menu child1">
                                            <li class="slide side-menu__label1">
                                                <a href="javascript:void(0)">{{ __('Employees') }}</a>
                                            </li>
                                            <li class="slide {{ Request::is('employees') ? 'active' : '' }}">
                                                <a href="{{ route('employees') }}"
                                                    class="side-menu__item {{ Request::is('employees') ? 'active' : '' }}">
                                                    <i class="bx bx-group side-menu__icon"></i>
                                                    {{-- <i class='bx bx-user'></i> --}}
                                                    <span class="side-menu__label">{{ __('Groups') }}</span>
                                                </a>
                                            </li>
                                            <li class="slide {{ Request::is('all-employees') ? 'active' : '' }}">
                                                <a href="{{ route('all-employees') }}"
                                                    class="side-menu__item {{ Request::is('all-employees') ? 'active' : '' }}">
                                                    <i class="bx bx-user side-menu__icon"></i>
                                                    {{-- <i class='bx bx-user'></i> --}}
                                                    <span class="side-menu__label">{{ __('All Employees') }}</span>
                                                </a>
                                            </li>
                                            <li
                                                class="slide {{ Request::is('blue-collar-employees') ? 'active' : '' }}">
                                                <a href="{{ route('bluecollar.employees') }}"
                                                    class="side-menu__item {{ Request::is('blue-collar-employees') ? 'active' : '' }}">
                                                    <i class="bx bx-phone-call side-menu__icon"></i>
                                                    <span class="side-menu__label">{{ __('BlueCollars') }}</span>
                                                </a>
                                            </li>
                                        </ul>
                                    </li>


                                    <li
                                        class="slide has-sub {{ Request::is('campaigns') || Request::is('whatsapp-campaign') || Request::is('quishing') ? 'open' : '' }}">
                                        <a href="javascript:void(0);"
                                            class="side-menu__item {{ Request::is('campaigns') || Request::is('whatsapp-campaign') || Request::is('quishing') ? 'active' : '' }}">
                                            <i class="bx bx-mail-send side-menu__icon"></i>
                                            <span class="side-menu__label">{{ __('Campaigns') }}</span>
                                            <i class="fe fe-chevron-right side-menu__angle"></i>
                                        </a>
                                        <ul class="slide-menu child1">
                                            <li class="slide side-menu__label1">
                                                <a href="javascript:void(0)">{{ __('Campaigns') }}</a>
                                            </li>

                                            <li class="slide {{ Request::is('campaigns') ? 'active' : '' }}">
                                                <a href="{{ route('campaigns') }}"
                                                    class="side-menu__item {{ Request::is('campaigns') ? 'active' : '' }}">
                                                    <i class="bx bx-envelope side-menu__icon"></i>
                                                    <span class="side-menu__label">{{ __('Email Phishing') }}</span>
                                                </a>
                                            </li>
                                            <li class="slide {{ Request::is('quishing') ? 'active' : '' }}">
                                                <a href="{{ route('quishing.index') }}"
                                                    class="side-menu__item {{ Request::is('quishing') ? 'active' : '' }}">
                                                    <i class="bx bx-qr side-menu__icon"></i>
                                                    <span class="side-menu__label">
                                                        {{ __('Quishing') }}
                                                        <span
                                                            class="badge bg-secondary-transparent ms-2">{{ __('New') }}</span>
                                                    </span>
                                                </a>
                                            </li>
                                            <li class="slide {{ Request::is('whatsapp-campaign') ? 'active' : '' }}">
                                                <a href="{{ route('whatsapp.campaign') }}"
                                                    class="side-menu__item {{ Request::is('whatsapp-campaign') ? 'active' : '' }}">
                                                    <i class="bx bxl-whatsapp side-menu__icon"></i>
                                                    <span class="side-menu__label">{{ __('WA Campaigns') }}</span>
                                                </a>
                                            </li>



                                        </ul>
                                    </li>


                                    <li
                                        class="slide has-sub {{ Request::is('ai-calling') || Request::is('tprm') ? 'open' : '' }}">
                                        <a href="javascript:void(0);"
                                            class="side-menu__item {{ Request::is('ai-calling') || Request::is('tprm') ? 'active' : '' }}">
                                            <i class="bx bx-mail-send side-menu__icon"></i>
                                            <span class="side-menu__label">{{ __('Advanced Simulation') }}</span>
                                            <i class="fe fe-chevron-right side-menu__angle"></i>
                                        </a>
                                        <ul class="slide-menu child1">
                                            <li class="slide side-menu__label1">
                                                <a href="javascript:void(0)">{{ __('Advanced Simulation') }}</a>
                                            </li>


                                            <li class="slide {{ Request::is('ai-calling') ? 'active' : '' }}">
                                                <a href="{{ route('ai.calling') }}"
                                                    class="side-menu__item {{ Request::is('ai-calling') ? 'active' : '' }}">
                                                    <i class="bx bx-phone-call side-menu__icon"></i>
                                                    <span class="side-menu__label">
                                                        {{ __('AI Vishing') }}
                                                        <span
                                                            class="badge bg-secondary-transparent ms-2">{{ __('New') }}</span>
                                                    </span>
                                                </a>
                                            </li>



                                            <li class="slide {{ Request::is('tprm') ? 'active' : '' }}">
                                                <a href="{{ route('campaign.tprm') }}"
                                                    class="side-menu__item {{ Request::is('tprm') ? 'active' : '' }}">
                                                    <i class="bx bx-shape-circle side-menu__icon"></i>
                                                    <span class="side-menu__label">
                                                        {{ __('TPRM') }}

                                                    </span>
                                                </a>
                                            </li>

                                        </ul>
                                    </li>


                                    <li class="slide {{ Request::is('reporting') ? 'active' : '' }}">
                                        <a href="{{ route('campaign.reporting') }}"
                                            class="side-menu__item {{ Request::is('reporting') ? 'active' : '' }}">
                                            <i class="bx bx-spreadsheet side-menu__icon"></i>
                                            <span class="side-menu__label">{{ __('Reporting') }}</span>
                                        </a>
                                    </li>


                                    <li
                                        class="slide has-sub {{ Request::is('phishing-emails') || Request::is('phishing-websites') || Request::is('quishing-emails') || Request::is('sender-profiles') ? 'open' : '' }}">
                                        <a href="javascript:void(0);"
                                            class="side-menu__item {{ Request::is('phishing-emails') || Request::is('phishing-websites') || Request::is('quishing-emails') || Request::is('sender-profiles') ? 'active' : '' }}">


                                            <i class="bx bx-mail-send side-menu__icon"></i>
                                            <span class="side-menu__label">{{ __('Phishing Material') }}</span>
                                            <i class="fe fe-chevron-right side-menu__angle"></i>
                                        </a>
                                        <ul class="slide-menu child1">
                                            <li class="slide side-menu__label1">
                                                <a href="javascript:void(0)">{{ __('Phishing Material') }}</a>
                                            </li>

                                            <li class="slide {{ Request::is('phishing-emails') ? 'active' : '' }}">
                                                <a href="{{ route('phishing.emails') }}"
                                                    class="side-menu__item {{ Request::is('phishing-emails') ? 'active' : '' }}">
                                                    <i class="bx bx-envelope side-menu__icon"></i>
                                                    <span class="side-menu__label">{{ __('Phishing Emails') }}</span>
                                                </a>
                                            </li>
                                            <li class="slide {{ Request::is('quishing-emails') ? 'active' : '' }}">
                                                <a href="{{ route('quishing.emails') }}"
                                                    class="side-menu__item {{ Request::is('quishing-emails') ? 'active' : '' }}">
                                                    <i class="bx bx-qr side-menu__icon"></i>
                                                    <span class="side-menu__label">{{ __('Quishing Emails') }}</span>
                                                </a>
                                            </li>
                                            <li class="slide {{ Request::is('phishing-websites') ? 'active' : '' }}">
                                                <a href="{{ route('phishing.websites') }}"
                                                    class="side-menu__item {{ Request::is('phishing-websites') ? 'active' : '' }}">
                                                    <i class="bx bx-globe side-menu__icon"></i>
                                                    <span
                                                        class="side-menu__label">{{ __('Phishing Websites') }}</span>
                                                </a>
                                            </li>
                                            <li class="slide {{ Request::is('sender-profiles') ? 'active' : '' }}">
                                                <a href="{{ route('senderprofile.index') }}"
                                                    class="side-menu__item {{ Request::is('sender-profiles') ? 'active' : '' }}">
                                                    <i class="bx bx-mail-send side-menu__icon"></i>
                                                    <span class="side-menu__label">{{ __('Sender Profiles') }}</span>

                                                </a>
                                            </li>

                                        </ul>
                                    </li>
                                    <li class="slide {{ Request::is('training-modules') ? 'active' : '' }}">
                                        <a href="{{ route('trainingmodule.index') }}"
                                            class="side-menu__item {{ Request::is('training-modules') ? 'active' : '' }}">
                                            <i class="bx bx-windows side-menu__icon"></i>
                                            <span class="side-menu__label">{{ __('Training Modules') }}</span>
                                        </a>
                                    </li>

                                    <li
                                        class="slide has-sub {{ Request::is('brand-monitoring') || Request::is('human-risk-management') ? 'open' : '' }}">
                                        <a href="javascript:void(0);"
                                            class="side-menu__item {{ Request::is('brand-monitoring') || Request::is('human-risk-management') ? 'active' : '' }}">
                                            <i class="bx bx-mail-send side-menu__icon"></i>
                                            <span class="side-menu__label">{{ __('Threat Monitoring') }}</span>
                                            <i class="fe fe-chevron-right side-menu__angle"></i>
                                        </a>
                                        <ul class="slide-menu child1">
                                            <li class="slide side-menu__label1">
                                                <a href="javascript:void(0)">{{ __('Threat Monitoring') }}</a>
                                            </li>

                                            {{-- <li class="slide {{ Request::is('brand-monitoring') ? 'active' : '' }}">
                                                <a href="{{ route('brand.monitoring') }}"
                                                    class="side-menu__item {{ Request::is('brand-monitoring') ? 'active' : '' }}">
                                                    <i class="bx bx-line-chart side-menu__icon"></i>
                                                    <span class="side-menu__label">{{ __('Brand Monitoring') }}</span>
                                                </a>
                                            </li> --}}

                                            <li
                                                class="slide {{ Request::is('human-risk-management') ? 'active' : '' }}">
                                                <a href="{{ route('human.risk.management') }}"
                                                    class="side-menu__item {{ Request::is('human-risk-management') ? 'active' : '' }}">
                                                    <i class="bx bx-globe side-menu__icon"></i>
                                                    <span
                                                        class="side-menu__label">{{ __('Human Risk Management') }}</span>
                                                </a>
                                            </li>

                                        </ul>
                                    </li>




                                    <li class="slide {{ Request::is('support') ? 'active' : '' }}">
                                        <a href="{{ route('company.support') }}"
                                            class="side-menu__item {{ Request::is('support') ? 'active' : '' }}">
                                            <i class="bx bx-support side-menu__icon"></i>
                                            <span class="side-menu__label">{{ __('Support Ticket') }}</span>
                                        </a>
                                    </li>



                                </ul>
                                <div class="slide-right d-none" id="slide-right"><svg
                                        xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24"
                                        height="24" viewBox="0 0 24 24">
                                        <path
                                            d="M10.707 17.707 16.414 12l-5.707-5.707-1.414 1.414L13.586 12l-4.293 4.293z">
                                        </path>
                                    </svg></div>
                            </nav>
                            <!-- End::nav -->

                        </div>
                    </div>
                </div>
            </div>
            <div class="simplebar-placeholder" style="width: auto; height: 366px;"></div>
        </div>
        <div class="simplebar-track simplebar-horizontal" style="visibility: hidden;">
            <div class="simplebar-scrollbar" style="width: 0px; display: none;"></div>
        </div>
        <div class="simplebar-track simplebar-vertical" style="visibility: hidden;">
            <div class="simplebar-scrollbar" style="height: 0px; display: none;"></div>
        </div>
    </div>
    <!-- End::main-sidebar -->

</aside>
