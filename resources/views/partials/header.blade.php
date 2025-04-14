<header class="app-header">

    <!-- Start::main-header-container -->
    <div class="main-header-container container-fluid">

        <!-- Start::header-content-left -->
        <div class="header-content-left">

            <!-- Start::header-element -->
            {{-- <div class="header-element">
                <div class="horizontal-logo">
                    <a href="index.html" class="header-logo">
                        <img src="assets/images/brand-logos/desktop-logo.png" alt="logo" class="desktop-logo">
                        <img src="assets/images/brand-logos/toggle-logo.png" alt="logo" class="toggle-logo">
                        <img src="assets/images/brand-logos/desktop-dark.png" alt="logo" class="desktop-dark">
                        <img src="assets/images/brand-logos/toggle-dark.png" alt="logo" class="toggle-dark">
                        <img src="assets/images/brand-logos/desktop-white.png" alt="logo" class="desktop-white">
                        <img src="assets/images/brand-logos/toggle-white.png" alt="logo" class="toggle-white">
                    </a>
                </div>
            </div> --}}
            <!-- End::header-element -->

            <!-- Start::header-element -->
            <div class="header-element">
                <!-- Start::header-link -->
                <a aria-label="Hide Sidebar"
                    class="sidemenu-toggle header-link animated-arrow hor-toggle horizontal-navtoggle"
                    data-bs-toggle="sidebar" href="javascript:void(0);"><span></span></a>
                <!-- End::header-link -->
            </div>
            <!-- End::header-element -->

        </div>
        <!-- End::header-content-left -->

        <!-- Start::header-content-right -->
        <div class="header-content-right">

            <!-- Start::header-element -->
            <div class="header-element country-selector">
                <!-- Start::header-link -->
                <a href="javascript:void(0);" class="header-link" data-bs-toggle="modal" data-bs-target="#countryModal">
                    {{-- <img src="../assets/images/flags/russia_flag.jpeg" alt="img"
                        class="rounded-circle header-link-icon"> --}}
                    <span class="fw-semibold mb-0 lh-1">{{ strtoupper(Auth::user()->lang) }}</span>
                </a>
            </div>
            <!-- End::header-element -->


            <!-- Start::header-element -->
            <div class="header-element header-theme-mode">
                <!-- Start::header-link|layout-setting -->
                <a href="javascript:void(0);" class="header-link layout-setting">
                    <span class="light-layout">
                        <!-- Start::header-link-icon -->
                        <i class="bx bx-moon header-link-icon"></i>
                        <!-- End::header-link-icon -->
                    </span>
                    <span class="dark-layout">
                        <!-- Start::header-link-icon -->
                        <i class="bx bx-sun header-link-icon"></i>
                        <!-- End::header-link-icon -->
                    </span>
                </a>
                <!-- End::header-link|layout-setting -->
            </div>
            <!-- End::header-element -->


            <!-- Start::header-element -->
            <div class="header-element header-fullscreen">
                <!-- Start::header-link -->
                <a onclick="openFullscreen();" href="javascript:void(0);" class="header-link">
                    <i class="bx bx-fullscreen full-screen-open header-link-icon"></i>
                    <i class="bx bx-exit-fullscreen full-screen-close header-link-icon d-none"></i>
                </a>
                <!-- End::header-link -->
            </div>
            <!-- End::header-element -->

            <!-- Start::header-element -->
            <div class="header-element">
                <!-- Start::header-link|dropdown-toggle -->
                <a href="javascript:void(0);" class="header-link dropdown-toggle" id="mainHeaderProfile"
                    data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                    <div class="d-flex align-items-center">
                        @auth

                            <div class="d-sm-block d-none">
                                <p class="fw-semibold mb-0 lh-1">{{ Auth::user()->full_name }}</p>
                                <span class="op-7 fw-normal d-block fs-11">{{ Auth::user()->email }}</span>
                            </div>
                        @endauth
                    </div>
                </a>
                <!-- End::header-link|dropdown-toggle -->
                <ul class="main-header-dropdown dropdown-menu pt-0 overflow-hidden header-profile-dropdown dropdown-menu-end"
                    aria-labelledby="mainHeaderProfile">
                    <li><a class="dropdown-item d-flex" href="{{ route('settings.index') }}"><i
                                class="ti ti-adjustments-horizontal fs-18 me-2 op-7"></i>{{ __('Settings') }}</a></li>

                    <!-- <li><a class="dropdown-item d-flex" href="chat.html"><i class="ti ti-headset fs-18 me-2 op-7"></i>Support</a></li> -->
                    <li><a class="dropdown-item d-flex" href="{{ route('logout') }}"><i
                                class="ti ti-logout fs-18 me-2 op-7"></i>{{ __('Log Out') }}</a></li>
                </ul>
            </div>
            <!-- End::header-element -->

        </div>
        <!-- End::header-content-right -->

    </div>
    <!-- End::main-header-container -->

</header>

<!--Country Modal Popup-->
<div class="modal fade" id="countryModal" tabindex="-1" aria-labelledby="countryModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body p-4">
                <label class="form-label fs-16">{{ __('Select Language') }}</label>
                <select class="form-control" id="languageSelect" data-trigger>
                    <option {{ Auth::user()->lang == "en" ? 'selected' : '' }} value="en">{{ __('English (En)') }}</option>
                    <option {{ Auth::user()->lang == "ar" ? 'selected' : '' }} value="ar">{{ __('عربي (AR)') }}</option>
                    <option {{ Auth::user()->lang == "ru" ? 'selected' : '' }} value="ru">{{ __('Русский (RU)') }}</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary-light" data-bs-dismiss="modal">{{ __('Close') }}</button>
                <button type="button" class="btn btn-primary" onclick="changeLanguage()">{{ __('Save changes') }}</button>
            </div>
        </div>
    </div>
</div>
<!--Country Modal Popup-->

    
<script>
    function changeLanguage() {
        const locale = document.getElementById('languageSelect').value;
        window.location.href = '/lang/' + locale;
    }
</script>