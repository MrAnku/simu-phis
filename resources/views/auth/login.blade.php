<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" data-vertical-style="overlay" data-theme-mode="light"
    data-header-styles="light" data-menu-styles="light" data-toggled="close">

<head>

    <!-- Meta Data -->
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title> {{$companyName}} - Login </title>

    <!-- Favicon -->
    <link rel="icon" href="{{$companyFavicon}}" type="image/x-icon">

    <!-- Main Theme Js -->
    <script src="assets/js/authentication-main.js"></script>

    <!-- Bootstrap Css -->
    <link id="style" href="assets/libs/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- Style Css -->
    <link href="assets/css/styles.min.css" rel="stylesheet">

    <!-- Icons Css -->
    <link href="assets/css/icons.min.css" rel="stylesheet">
    
<link rel="stylesheet" href="assets/libs/sweetalert2/sweetalert2.min.css">

    <style>
        .authentication .desktop-dark,
        .authentication .desktop-logo {
            height: 6rem;
        }
    </style>
</head>

<body>


    <div class="container">
        <div class="row justify-content-center align-items-center authentication authentication-basic h-100">
            <div class="col-xxl-4 col-xl-5 col-lg-5 col-md-6 col-sm-8 col-12">
                <div class="my-5 d-flex justify-content-center" style="margin-bottom: 1rem !important;">
                    <a href="#">
                        <img src="{{$companyLogoDark}}" alt="logo" class="desktop-dark">
                        <img src="{{$companyLogoLight}}" alt="logo" class="desktop-logo">
                    </a>
                </div>
                <div class="card custom-card">
                    <div class="card-body p-5">
                        <p class="h5 fw-semibold mb-2 text-center">Sign In</p>
                        <p class="mb-4 text-muted op-7 fw-normal text-center">Welcome to {{$companyName}}</p>
                        <form action="{{ route('login') }}" method="POST">
                            @csrf

                            <div class="row gy-3">
                                <div class="col-xl-12">
                                    <label for="signin-username" class="form-label text-default">Email</label>
                                    <input type="text"
                                        class="form-control form-control-lg"
                                        name="email" placeholder="john@domain.com">
                                    
                                </div>
                                <div class="col-xl-12 mb-2">
                                    <label for="signin-password" class="form-label text-default d-block">Password<a
                                            data-bs-toggle="modal" data-bs-target="#forgetPassModal" href="#"
                                            class="float-end text-secondary">Forget password ?</a></label>
                                    <div class="input-group">
                                        <input type="password"
                                            class="form-control form-control-lg"
                                            name="password" id="login-password" placeholder="password">
                                        <button class="btn btn-light" type="button"
                                            onclick="createpassword('login-password',this)" id="button-addon2"><i
                                                class="ri-eye-off-line align-middle"></i></button>

                                        
                                    </div>
                                    <div class="mt-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="remember"
                                                value="" id="defaultCheck1">
                                            <label class="form-check-label text-muted fw-normal" for="defaultCheck1">
                                                Remember password ?
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-12 d-grid mt-2">
                                    <button type="submit" name="do_signin"
                                        class="btn btn-lg btn-primary">Login</button>
                                </div>
                            </div>
                        </form>

                        <!-- <div class="text-center">
                            <p class="fs-12 text-muted mt-3">Dont have an account? <a href="register.php" class="text-primary">Sign Up</a></p>
                        </div> -->
                    </div>
                </div>
            </div>



        </div>
    </div>




    <div class="modal fade" id="forgetPassModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title" id="exampleModalLabel1">Forget Password</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div>
                        <label for="input-label" class="form-label">Registered Email</label>
                        <input type="email" id="reg_email" class="form-control"
                            placeholder="Enter your registered email with simUphish.">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="do_verify">Send Verification Email</button>
                    <button class="btn btn-primary btn-loader" id="spinner" disabled>
                        <span class="me-2">Please Wait</span>
                        <span class="loading"><i class="ri-loader-2-fill fs-16"></i></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

     {{-- ------------------------------Toasts---------------------- --}}

     <div class="toast-container position-fixed top-0 end-0 p-3">
        @if (session('success'))
            <div class="toast colored-toast bg-success-transparent fade show" role="alert" aria-live="assertive"
                aria-atomic="true">
                <div class="toast-header bg-success text-fixed-white">
                    <strong class="me-auto">Success</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    {{ session('success') }}
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="toast colored-toast bg-danger-transparent fade show" role="alert" aria-live="assertive"
                aria-atomic="true">
                <div class="toast-header bg-danger text-fixed-white">
                    <strong class="me-auto">Error</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    {{ session('error') }}
                </div>
            </div>
        @endif

        @if ($errors->any())
            @foreach ($errors->all() as $error)
                <div class="toast colored-toast bg-danger-transparent fade show" role="alert" aria-live="assertive"
                    aria-atomic="true">
                    <div class="toast-header bg-danger text-fixed-white">
                        <strong class="me-auto">Error</strong>
                        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    <div class="toast-body">
                        {{ $error }}
                    </div>
                </div>
            @endforeach
        @endif


    </div>

    {{-- ------------------------------Toasts---------------------- --}}


    <!-- Bootstrap JS -->
    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>

    <script src="https://code.jquery.com/jquery-3.6.1.min.js"
        integrity="sha256-o88AwQnZB+VDvE9tvIXrMQaPlFFSUTR+nldQm1LuPXQ=" crossorigin="anonymous"></script>

        
    <script src="assets/libs/sweetalert2/sweetalert2.min.js"></script>

    <script>
        $("#spinner").hide()

        $("#do_verify").click(function() {
            var clickedBtn = $(this);
            clickedBtn.hide()
            $("#spinner").show()
            $.post({
                url: '{{ route('password.email') }}',
                headers: {

                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                contentType: 'application/json',
                data: JSON.stringify({
                    email: reg_email.value
                }),
                success: function(res) {
                    // clickedBtn.show()
                    $("#spinner").hide()
                    Swal.fire(res.status);

                    // console.log(res)
                }
            })
        })
    </script>

    <!-- Show Password JS -->
    <script src="assets/js/show-password.js"></script>

</body>

</html>


{{-- <x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button class="ms-3">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout> --}}
