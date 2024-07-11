<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr" data-nav-layout="vertical"
    data-theme-mode="light" data-header-styles="light" data-menu-styles="dark" data-toggled="close">

<head>

    <!-- Meta Data -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title> @yield('title') </title>
    <!-- Favicon -->
    @include('layouts.css')

    @stack('newcss')

</head>

<body>

    <!-- Start Switcher -->
    @include('layouts.switcher')
    <!-- End Switcher -->

    <!-- Loader -->
    <div id="loader">
        <img src="{{asset('assets')}}/images/media/loader.svg" alt="">
    </div>
    <!-- Loader -->

    <div class="page">
        <!-- app-header -->
        @include('admin.partials.header')
        <!-- /app-header -->

        <!-- Start::app-sidebar -->

        <style>
            .app-sidebar .main-sidebar-header .header-logo img {
                height: 3rem;
            }
        </style>

        @include('admin.partials.sidebar')

        <!-- End::app-sidebar -->

        <!-- Start::app-content -->
        @yield('main-content')
        <!-- End::app-content -->


        <!-- Footer Start -->
        @include('admin.partials.footer')
        <!-- Footer End -->

    </div>


    <!-- Scroll To Top -->
    <div class="scrollToTop">
        <span class="arrow"><i class="ri-arrow-up-s-fill fs-20"></i></span>
    </div>
    <div id="responsive-overlay"></div>
    <!-- Scroll To Top -->

    @include('layouts.scripts')

    @stack('newscripts')

</body>

</html>
