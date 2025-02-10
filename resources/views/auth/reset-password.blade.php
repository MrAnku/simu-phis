<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" data-vertical-style="overlay" data-theme-mode="light"
    data-header-styles="light" data-menu-styles="light" data-toggled="close">

<head>

    <!-- Meta Data -->
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title> simUphish - Reset Password </title>

    <!-- Favicon -->
    <link rel="icon" href="/assets/images/simu-icon.png" type="image/x-icon">


    <!-- Main Theme Js -->
    <script src="{{ asset('assets') }}/js/authentication-main.js"></script>

    <!-- Bootstrap Css -->
    <link id="style" href="{{ asset('assets') }}/libs/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- Style Css -->
    <link href="{{ asset('assets') }}/css/styles.min.css" rel="stylesheet">

    <!-- Icons Css -->
    <link href="{{ asset('assets') }}/css/icons.min.css" rel="stylesheet">

    <style>
        .authentication .desktop-dark,
        .authentication .desktop-logo {
            height: 6rem;
        }
    </style>
</head>

<body>



    <div class="container-lg">
        <div class="row justify-content-center align-items-center authentication authentication-basic h-100">
            <div class="col-xxl-4 col-xl-5 col-lg-5 col-md-6 col-sm-8 col-12">
                <div class="my-5 d-flex justify-content-center">
                    <a href="#">
                        <img src="/{{$companyLogoDark}}" alt="logo" class="desktop-logo">
                        <img src="/{{$companyLogoLight}}" alt="logo" class="desktop-dark">
                    </a>
                </div>
                <div class="card custom-card">
                    <form action="{{ route('password.store') }}" method="POST" id="newPassForm"
                        onsubmit="return validatePassword()">

                        @csrf

                        <!-- Password Reset Token -->
                        <input type="hidden" name="token" value="{{ $request->route('token') }}">
                        <input type="hidden" name="email" value="{{ old('email', $request->email) }}">


                        <div class="card-body p-5">
                            <p class="h5 fw-semibold mb-2 text-center">Reset Password</p>
                            <div class="row gy-3">

                                <div class="col-xl-12">
                                    <label for="reset-newpassword" class="form-label text-default">New Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control form-control-lg" name="password"
                                            id="reset-newpassword" placeholder="new password">
                                        <button class="btn btn-light" type="button"
                                            onclick="createpassword('reset-newpassword',this)" id="button-addon21"><i
                                                class="ri-eye-off-line align-middle"></i></button>
                                    </div>
                                </div>
                                <div class="col-xl-12 mb-2">
                                    <label for="reset-confirmpassword" class="form-label text-default">Confirm
                                        Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control form-control-lg"
                                            name="password_confirmation" id="reset-confirmpassword"
                                            placeholder="confirm password">
                                        <button class="btn btn-light" type="button"
                                            onclick="createpassword('reset-confirmpassword',this)"
                                            id="button-addon22"><i class="ri-eye-off-line align-middle"></i></button>
                                    </div>
                                </div>
                                <div class="col-xl-12 d-grid mt-2">
                                    <button type="submit" class="btn btn-lg btn-primary">Create</button>
                                    <button class="btn btn-primary btn-loader" id="spinner" disabled>
                                        <span class="me-2">Please Wait</span>
                                        <span class="loading"><i class="ri-loader-2-fill fs-16"></i></span>
                                    </button>
                                </div>
                            </div>
                            <div class="text-center">
                                <p class="fs-12 text-muted mt-3">Login to your account <a href="{{route('login')}}"
                                        class="text-primary">Log In</a></p>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="toast-container position-fixed top-0 end-0 p-3">
            @foreach ($errors->all() as $error)
                <div id="dangerToast" class="toast colored-toast bg-danger-transparent fade show" role="alert"
                    aria-live="assertive" aria-atomic="true">
                    <div class="toast-header bg-danger text-fixed-white">

                        <strong class="me-auto">Error</strong>
                        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    <div class="toast-body">
                        {{ $error }}
                    </div>
                </div>
            @endforeach
        </div>
    @endif







    <!-- Bootstrap JS -->
    <script src="{{ asset('assets') }}/libs/bootstrap/js/bootstrap.bundle.min.js"></script>

    <script src="https://code.jquery.com/jquery-3.6.1.min.js"
        integrity="sha256-o88AwQnZB+VDvE9tvIXrMQaPlFFSUTR+nldQm1LuPXQ=" crossorigin="anonymous"></script>

    <script>
        $("#spinner").hide()
    </script>

    <script>
        // Function to check if the password is strong
        function checkPasswordStrength(password) {
            // A strong password should be at least 8 characters long and contain at least one lowercase letter, one uppercase letter, one digit, and one special character
            var strongRegex = new RegExp("^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*])(?=.{8,})");
            return strongRegex.test(password);
        }

        // Function to validate password fields
        function validatePassword() {
            var password = document.getElementById("reset-newpassword").value;
            var confirmPassword = document.getElementById("reset-confirmpassword").value;

            // Check if passwords match
            if (password !== confirmPassword) {
                alert("Passwords do not match!");
                return false;
            }

            // Check if password is strong
            if (!checkPasswordStrength(password)) {
                alert(
                    "Password is not strong enough! Please use at least 8 characters including one uppercase letter, one lowercase letter, one digit, and one special character."
                );
                return false;
            }

            return true;
        }
    </script>

    <!-- Show Password JS -->
    <script src="{{ asset('assets') }}/js/show-password.js"></script>

</body>

</html>

{{-- <x-guest-layout>
    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $request->email)"
                required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required
                autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password"
                name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('Reset Password') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout> --}}
