<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Training Completed</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            padding: 32px 24px;
        }
        .logo {
            display: block;
            margin: 0 auto 24px auto;
            max-width: 120px;
        }
        h1 {
            color: #000000;
            font-size: 2rem;
            margin: 0 0 12px 0;
            text-align: center;
        }
        h2 {
            font-size: 1.1rem;
            font-weight: 400;
            margin: 0 0 24px 0;
            text-align: center;
        }
        .info {
            background: #f1f3fa;
            padding: 16px;
            border-radius: 6px;
            margin-bottom: 24px;
        }
        .info p {
            margin: 8px 0;
            font-size: 1rem;
        }
        .btn {
            display: inline-block;
            background: #000000;
            color: #fff !important;
            padding: 12px 28px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 1rem;
            margin: 12px auto 0 auto;
            text-align: center;
        }
        .footer {
            text-align: center;
            font-size: 0.95em;
            margin-top: 32px;
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="{{ $mailData['logo'] }}" alt="Company Logo" class="logo">

        <h1> Congratulations, Training Completed!</h1>

        <h2>
            Hi {{ $mailData['user_name'] }},<br>
            You’ve successfully completed your assigned training. Keep up the great work!
        </h2>

        <div class="info">
            <p><strong>Training Name:</strong> {{ $mailData['training_name'] }}</p>
            <p><strong>Training Score:</strong> {{ $mailData['training_score'] }}%</p>
        </div>
        <div class="footer">
            <a href="#" style="color: #000000; text-decoration: none;" target="_blank">
                © {{ date('Y') }} {{ $mailData['company_name'] }} | All rights reserved
            </a>
        </div>
    </div>
</body>
</html>
