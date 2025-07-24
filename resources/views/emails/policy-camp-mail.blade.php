<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>New Policy Assigned</title>
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
            <img src="{{ asset('/assets/images/simu-logo-dark.png') }}" style="width: 200px;" alt="company Logo">
        </div>

        <div class="content">
            <p><strong>{{__('Hello')}} {{ $mailData['user_name'] }},</strong></p>
            <p>{{__('A New Policy has been assigned to you. Kindly Login to the learning portal, read the policy carefully, and take action accordingly.')}}</p>

            <div class="report-box">
                <p><strong>{{__('Policy Assignment Notification')}}</strong></p>
                <p>📄 <span class="label">{{__('Policy Name:')}}</span> {{ $mailData['policy_name'] }}</p>
                <p>📅 <span class="label">{{__('Assigned On:')}}</span> {{ $mailData['assigned_at'] }}</p>
                <a href="{{ env('SIMUPHISH_LEARNING_URL') }}" class="button">{{__('Login to Learning Portal')}}</a>
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
