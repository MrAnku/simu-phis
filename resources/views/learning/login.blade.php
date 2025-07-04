<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>Login | {{session('companyName')}} Learning</title>

    <link rel="icon" href="{{ session('companyFavicon') }}" type="image/x-icon" />

    <!-- CSS files -->
    <link href="/dist/css/tabler.min.css?1685973381" rel="stylesheet" />
    <link href="/dist/css/tabler-flags.min.css?1685973381" rel="stylesheet" />
    <link href="/dist/css/tabler-payments.min.css?1685973381" rel="stylesheet" />
    <link href="/dist/css/tabler-vendors.min.css?1685973381" rel="stylesheet" />
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
    <script src="/dist/js/demo-theme.min.js?1685973381"></script>
    <div class="page page-center">
        <div class="container container-tight py-4">
            <div class="text-center mb-4">
                <a href="." class="navbar-brand navbar-brand-autodark">
                    {{-- <img src="" width="150" alt="Logo" class="navbar-brand-image"> --}}
                    <img src="{{ session('companyLogoDark') }}" alt="logo" class="navbar-brand-image">
                </a>
            </div>
            <div class="card card-md">
                <div class="card-body">
                    @if (isset($msg))
                    <p class="my-2 text-center text-danger">{{$msg}}</p>
                    @endif
                    <h2 class="h2 text-center mb-4">Enter your email to regenerate training session</h2>
                    <form id="newTokenForm">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Email address</label>
                            <input type="email" id="email" class="form-control" name="email"
                                placeholder="your@email.com" autocomplete="off">
                        </div>
                        <div class="mb-3">
                            <p id="responseMessage" class="text-center" style="margin-top: 15px; font-size: 14px; color: green;"></p>
                        </div>
                      

                        <div class="form-footer">
                            <button type="submit" name="signIn" class="btn btn-primary w-100">Regenerate</button>
                        </div>
                    </form>
                </div>
            </div>
           
        </div>
    </div>
    <!-- Libs JS -->
    <!-- Tabler Core -->
    <script src="/dist/js/tabler.min.js?1685973381" defer></script>
    <script>
        document.getElementById('newTokenForm').addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent form from refreshing the page

            const submitButton = event.target.querySelector('button[type="submit"]');
            submitButton.classList.add('disabled');
            submitButton.innerText = 'Please wait...';

            const email = document.getElementById('email').value;
            if(!email) {
                document.getElementById('responseMessage').innerText = 'Please enter your email';
                document.getElementById('responseMessage').style.color = 'red';
                submitButton.innerText = 'Regenerate';
                submitButton.classList.remove('disabled');
                return;
            }

            fetch('/create-new-token', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}' // Laravel CSRF protection
                    },
                    body: JSON.stringify({
                        email: email
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if(data.error){
                        document.getElementById('responseMessage').innerText = data.error;
                        document.getElementById('responseMessage').style.color = 'red';
                        submitButton.innerText = 'Regenerate';
                        submitButton.classList.remove('disabled');
                        return;
                    }
                    document.getElementById('responseMessage').innerText = data.success;
                    submitButton.innerText = 'Session Regenerated';
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('responseMessage').innerText = 'Error While Send Mail';
                    document.getElementById('responseMessage').style.color = 'red';
                    submitButton.innerText = 'Regenerate';
                    submitButton.classList.remove('disabled');
                });
        });
    </script>
</body>

</html>
