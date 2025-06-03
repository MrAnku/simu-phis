@php
if(session('locale')){
    App::setLocale(session('locale'));    
}
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr" data-nav-layout="vertical"
    data-theme-mode="light" data-header-styles="light" data-menu-styles="dark" data-toggled="close">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ env('APP_NAME') }} - Training</title>
    <!-- FontAwesome-cdn include -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="icon" type="image/png" href="/assets/images/simu-icon.png">
    <!-- Google fonts include -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <!-- Bootstrap-css include -->
    <link rel="stylesheet" href="{{ asset('learner/assets') }}/css/bootstrap.min.css">
    <!-- Animate-css include -->
    <link rel="stylesheet" href="{{ asset('learner/assets') }}/css/animate.min.css">
    <!-- Main-StyleSheet include -->
    <link rel="stylesheet" href="{{ asset('learner/assets') }}/css/style.css">

    @stack('newcss')
</head>

<body>
    <div class="wrapper position-relative">
        <div class="container-fluid">
            <div class="row py-5">
                <div class="col-md-6">
                    <div class="logo_area ps-5">
                        <a href="#">
                            <img src="/assets/images/simu-logo.png" alt="image-not-found" width="200">
                        </a>
                    </div>
                </div>
                <div class="col-md-6">
                    <div>
                        
                    </div>
                    <div class="d-flex align-items-center justify-content-end pe-5">
                        <div class="me-3">
                            <div class="input-group">
                                <span class="input-group-text">
                                    Language
                                </span>
                                <x-language-select id="trainingLang" />
                            </div>

                        </div>

                        <div class="progress rounded-pill">
                            <div id="myBar" class="progress-bar rounded-pill" role="progressbar" style="width: 20%;"
                                aria-valuenow="20" aria-valuemin="0" aria-valuemax="100">0%</div>
                        </div>
    
                        <!-- Progress- -->
                        {{-- <div class="count_progress clip-1">
                            <span class="progress-left">
                                <span class="progress_bar"></span>
                            </span>
                            <span class="progress-right">
                                <span class="progress_bar"></span>
                            </span>
                            <div class="progress-value countdown_timer" data-countdown="2022/10/24">
                            </div>
                        </div> --}}
                        <div class="count_progress">
                            <div class="countdown-container">
                                <div class="countdown-text" id="countdown">--</div>
                            </div>
                        </div>
                        
                    </div>

                    
                </div>
            </div>
        </div>

        @yield('questions')
        <!-- progress-bar -->

        <!-- Right-side-img -->
        <div class="right_bottom_img d-none d-lg-block">
            <img class="position-absolute" src="{{ asset('learner/assets') }}/images/bg_1.png" alt="image-not-found">
        </div>
    </div>
    <!-- jQuery-js include -->
    <script src="{{ asset('learner/assets') }}/js/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap-js include -->
    <script src="{{ asset('learner/assets') }}/js/bootstrap.min.js"></script>
    <!-- jQuery-validate-js include -->
    <script src="{{ asset('learner/assets') }}/js/jquery.validate.min.js"></script>
    <!-- jQuery-countUp-js include -->
    <script src="{{ asset('learner/assets') }}/js/countdown.js"></script>
    <!-- Custom-js include -->
    <script src="{{ asset('learner/assets') }}/js/script.js"></script>

    @stack('newjs')
</body>

</html>
