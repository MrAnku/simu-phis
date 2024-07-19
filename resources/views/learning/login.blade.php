<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
  <meta http-equiv="X-UA-Compatible" content="ie=edge" />
  <title>Login | simUphish Learning</title>

  <link rel="icon" href="{{asset('assets')}}/images/simu-icon.png" type="image/x-icon" />

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
          <img src="/assets/images/simu-logo-dark.png" alt="logo" class="navbar-brand-image">
        </a>
      </div>
      <div class="card card-md">
        <div class="card-body">
          <h2 class="h2 text-center mb-4">Welcome Back</h2>
          <form action="{{route('learner.login')}}" method="post" autocomplete="off" novalidate>
            @csrf
            <div class="mb-3">
              <label class="form-label">Email address</label>
              <input type="email" class="form-control" name="email" placeholder="your@email.com" autocomplete="off">
            </div>
            <div class="mb-2">
              <label class="form-label">
                Password

              </label>
              <div class="input-group input-group-flat">
                <input type="password" class="form-control" name="password" placeholder="Your password" autocomplete="off">
                <span class="input-group-text">
                  <a href="#" class="link-secondary" title="Show password" data-bs-toggle="tooltip"><!-- Download SVG icon from http://tabler-icons.io/i/eye -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                      <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                      <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                      <path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" />
                    </svg>
                  </a>
                </span>
              </div>
            </div>

            <div class="form-footer">
              <button type="submit" name="signIn" class="btn btn-primary w-100">Sign in</button>
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