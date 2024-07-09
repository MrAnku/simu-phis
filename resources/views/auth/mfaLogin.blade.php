<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" data-vertical-style="overlay" data-theme-mode="light"
    data-header-styles="light" data-menu-styles="light" data-toggled="close">

<head>

    <!-- Meta Data -->
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title> Enter Code - simUphish </title>
    
    <!-- Favicon -->
    <link rel="icon" href="/assets/images/brand-logos/favicon.ico" type="image/x-icon">

    <!-- Main Theme Js -->
    <script src="/assets/js/authentication-main.js"></script>

    <!-- Bootstrap Css -->
    <link id="style" href="/assets/libs/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- Style Css -->
    <link href="/assets/css/styles.min.css" rel="stylesheet">

    <!-- Icons Css -->
    <link href="/assets/css/icons.min.css" rel="stylesheet">

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
            <div class="col-lg-5">
                <div class="my-5 d-flex justify-content-center">
                    <a href="index.html">
                        <img src="{{ asset('assets/images/simu-logo-dark.png') }}" alt="logo" class="desktop-logo"
                            width="200">
                        <img src="{{ asset('assets/images/simu-logo.png') }}" alt="logo" class="desktop-dark">
                    </a>
                </div>
                <div class="card custom-card">
                    <div class="card-body p-5">
                        <p class="h5 fw-semibold mb-2 text-center">Verify Your Authenticator Code</p>
                        <p class="mb-4 text-muted op-7 fw-normal text-center">Enter the 6 digit Google Authenticator
                            Code</p>
                        <form action="{{ route('mfa.verify') }}" method="post" id="otp_form">
                            @csrf
                            <div class="row gy-3">
                                <div class="col-xl-12 mb-2">
                                    <div class="row">
                                        <div class="col-2">
                                            <input type="text" class="form-control form-control-lg text-center"
                                                id="one" maxlength="1" onkeyup="clickEvent(this,'two')">
                                        </div>
                                        <div class="col-2">
                                            <input type="text" class="form-control form-control-lg text-center"
                                                id="two" maxlength="1" onkeyup="clickEvent(this,'three')">
                                        </div>
                                        <div class="col-2">
                                            <input type="text" class="form-control form-control-lg text-center"
                                                id="three" maxlength="1" onkeyup="clickEvent(this,'four')">
                                        </div>
                                        <div class="col-2">
                                            <input type="text" onkeyup="clickEvent(this,'five')"
                                                class="form-control form-control-lg text-center" id="four"
                                                maxlength="1">
                                        </div>
                                        <div class="col-2">
                                            <input type="text" onkeyup="clickEvent(this,'six')"
                                                class="form-control form-control-lg text-center" id="five"
                                                maxlength="1">
                                        </div>
                                        <div class="col-2">
                                            <input type="text" class="form-control form-control-lg text-center"
                                                id="six" maxlength="1">
                                        </div>
                                        <input type="hidden" name="otp" id="otp_field">
                                    </div>

                                </div>
                                <div class="col-xl-12 d-grid mt-2">
                                    <button type="submit" id="submitBtn" class="btn btn-lg btn-primary">Verify</button>
                                </div>
                            </div>
                        </form>


                    </div>
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
    <script src="/assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Internal Two Step Verification JS -->
    <script src="/assets/js/two-step-verification.js"></script>

    <script>
        document.getElementById('submitBtn').addEventListener('click', function(event) {
    // event.preventDefault(); 

    const inputs = document.querySelectorAll('.form-control.form-control-lg.text-center');
    let otpValue = '';
    inputs.forEach(input => {
        otpValue += input.value;
    });
    document.getElementById('otp_field').value = otpValue;

    // Optionally, you can submit the form programmatically after updating the hidden input
    // this.submit();
    document.getElementById('otp_form').submit();
});

       
    </script>

</body>

</html>
