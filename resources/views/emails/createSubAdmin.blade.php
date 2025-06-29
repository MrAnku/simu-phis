<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome Sub Admin - simUphish</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            text-align: center;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .header {
            background-color: #f2f2f0;
            padding: 20px;
            text-align: center;
        }

        .header img {
            max-width: 250px;
        }

        .content {
            padding: 20px;
        }

        h1 {
            color: #333;
            text-align: center;
        }

        p {
            color: #666;
        }

        .credentials {
            background: #f9f9f9;
            padding: 10px;
            border-radius: 6px;
            margin-top: 20px;
        }

        .credentials p {
            margin: 0;
            font-weight: bold;
        }

        .footer {
            background-color: #f9f9f9;
            padding: 15px;
            text-align: center;
            font-size: 12px;
            color: #777;
            border-top: 1px solid #eee;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <img src="{{ asset('assets/images/simu-logo-dark.png') }}" alt="{{ $branding['company_name'] }} Logo">
        </div>

        <div>
            <h1>Welcome, Sub Admin!</h1>
        </div>

        <div class="content">
            <p>Hello and welcome to <strong>{{ $branding['company_name'] }}</strong>! We’re excited to have you on board as a Sub Admin.</p>
            <p>To get started, please create your password using the link below. Once your account is approved, you'll be able to access the dashboard and start managing your responsibilities.</p>

            <div class="credentials">
                <p>Portal: <a href="{{ $branding['portal_domain'] }}" target="_blank">{{ $branding['portal_domain'] }}</a></p>
                <p>Username: {{ $company->email }}</p>
                <p><a href="{{ $pass_create_link }}" target="_blank">Click here to create your password</a></p>
            </div>

            <p>If you have any questions, feel free to reach out to our support team.</p>
            <p>Thanks again and welcome aboard!</p>
            <p>— Team {{ $branding['company_name'] }}</p>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} {{ $branding['company_name'] }}. All rights reserved.
        </div>
    </div>
</body>

</html>
