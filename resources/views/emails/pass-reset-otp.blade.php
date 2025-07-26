<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Password Reset</title>
    <style>
        body {
            background: #f6f8fb;
            margin: 0;
            padding: 0;
            font-family: 'Fira Sans', Arial, sans-serif;
            color: #222;
        }

        .container {
            max-width: 480px;
            margin: 40px auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            padding: 32px 24px;
            text-align: center;
        }

        .header img {
            max-width: 150px;
            margin: 0 auto 20px;
            display: block;
        }

        h1 {
            color: #000000;
            font-size: 2rem;
            margin: 0 0 12px 0;
        }

        p {
            font-size: 1rem;
            line-height: 1.5;
            margin: 12px 0;
        }

        .otp-box {
            font-size: 24px;
            color: #333;
            background: #f2f2f2;
            padding: 12px 24px;
            display: inline-block;
            border-radius: 6px;
            letter-spacing: 4px;
            font-weight: bold;
            margin: 20px 0;
        }

        .footer {
            text-align: center;
            font-size: 0.95em;
            margin-top: 32px;
            color: #777;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <img src="{{ $mailData['company_dark_logo'] }}" alt="{{ $mailData['company_name'] }} Logo">
        </div>

        <h1>Password Reset OTP</h1>
        <p>Hi {{ $mailData['full_name'] }},</p>
        <p>We received a request to reset your password. Use the OTP below to proceed:</p>

        <div class="otp-box">
            {{ $mailData['otp'] }}
        </div>

        <p>This OTP is valid for the next 10 minutes.</p>
        <p>If you did not request a password reset, please ignore this email.</p>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $mailData['company_name'] }}. All rights reserved.</p>
        </div>
    </div>
</body>

</html>
