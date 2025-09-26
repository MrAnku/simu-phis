<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>New Suspicious Email Reported</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #ffffff;
            margin: 0;
            padding: 20px;
            color: #333;
        }

        .container {
            max-width: 600px;
            margin: auto;
        }

        .logo {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo img {
            width: 120px;
        }

        .content {
            font-size: 15px;
            margin-bottom: 30px;
        }

        .report-box {
            background-color: #f7f7f8;
            border-radius: 8px;
            padding: 20px;
            margin-top: 15px;
        }

        .report-box p {
            margin: 10px 0;
        }

        .label {
            font-weight: bold;
        }

        a {
            color: #1a73e8;
            text-decoration: none;
        }

        .button {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 18px;
            background-color: #fff;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
        }

        .footer {
            margin-top: 50px;
            font-size: 12px;
            text-align: center;
            color: #888;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="logo">
            <img src="{{ env('CLOUDFRONT_URL') . '/assets/images/simu-logo-dark.png' }}" alt="simUphish Logo">
        </div>

        <div class="content">
            <p><strong>Hello {{ $mailData['company_name'] }},</strong></p>
            <p>A new suspicious email has been reported using the Phish Triage Report Button. Please review this report
                as soon as possible.</p>

            <div class="report-box">
                <p><strong>New Phishing Report Requires Review!</strong></p>
                <p>👤<span class="label">Reported By:</span> <a
                        href="mailto:{{ $mailData['reported_by'] }}">{{ $mailData['reported_by'] }}</a></p>
                <p> 📩 <span class="label">From:</span> <a
                        href="mailto:{{ $mailData['from'] }}">{{ $mailData['from'] }}</a></p>
                <p> 📌 <span class="label">Subject:</span> {{ $mailData['subject'] }}</p>
                <p> 📅 <span class="label">Reported At:</span> {{ $mailData['reported_at'] }}</p>
                <a href="{{ env('NEXT_APP_URL') }}" class="button">Go to Triage Inbox</a>
            </div>
        </div>

        <div class="footer">
            <p>
                <a href="https://simuphish.com/" target="_blank">Copyright © {{ date('Y') }} simUphish | All rights reserved</a>
            </p>
        </div>
    </div>
</body>
</html>