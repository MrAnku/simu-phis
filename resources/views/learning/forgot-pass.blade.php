<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>Forgot Password | {{ $companyName }} Learning</title>

    <link rel="icon" href="{{ $companyFavicon }}" type="image/x-icon" />

    <!-- CSS files -->
    <link href="./dist/css/tabler.min.css?1685973381" rel="stylesheet" />
    <link href="./dist/css/tabler-flags.min.css?1685973381" rel="stylesheet" />
    <link href="./dist/css/tabler-payments.min.css?1685973381" rel="stylesheet" />
    <link href="./dist/css/tabler-vendors.min.css?1685973381" rel="stylesheet" />
    <style>
        @import url('https://rsms.me/inter/inter.css');

        :root {
            --tblr-font-sans-serif: 'Inter Var', -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif;
        }

        body {
            font-feature-settings: "cv03", "cv04", "cv11";
        }

        .navbar-brand-image {
            height: 5rem !important;
            width: auto;
        }
    </style>
</head>

<body class=" d-flex flex-column">
    <script src="./dist/js/demo-theme.min.js?1685973381"></script>
    <div class="page page-center">
        <div class="container container-tight py-4">
            <div class="text-center mb-4">
                <a href="." class="navbar-brand navbar-brand-autodark">
                    {{-- <img src="" width="150" alt="Logo" class="navbar-brand-image"> --}}
                    <img src="{{ $companyLogoDark }}" alt="logo" class="navbar-brand-image">
                </a>
            </div>
            <div class="card card-md">
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif
                    {{-- <h2 class="h2 text-center mb-4">Forgot Password?</h2> --}}
                    <form action="{{ route('learner.forgot.store') }}" method="post" autocomplete="off" novalidate>
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Email address</label>
                            <input type="email" class="form-control" name="email" placeholder="your@email.com"
                                autocomplete="off">

                        </div>


                        <div class="form-footer">
                            <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
                        </div>
                    </form>
                </div>
            </div>
            <!-- <div class="text-center text-secondary mt-3">
          Don't have account yet? <a href="./sign-up.html" tabindex="-1">Sign up</a>
        </div> -->
        </div>
    </div>
    <!-- Libs JS -->
    <!-- Tabler Core -->
    <script src="./dist/js/tabler.min.js?1685973381" defer></script>
</body>

</html>
