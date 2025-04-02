<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Welcome to Simuphish</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            padding: 20px 0;
        }
        .header img {
            width: 150px;
        }
        .content {
            padding: 20px;
            line-height: 1.6;
        }
        .footer {
            text-align: center;
            padding: 20px;
            font-size: 12px;
            color: #777777;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            margin: 20px 0;
            background-color: #007bff;
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ asset('assets/images/simu-logo-dark.png') }}" alt="simUphish Logo">
        </div>
        <div class="content">
            <h1>Welcome to simUphish!</h1>
            <p>Dear {{ $deal->first_name }},</p>
            <p>We are thrilled to have you on board. Your account has been successfully created. Below are your login details:</p>
            <p><strong>Email:</strong> {{ $deal->email }}</p>
            <p>Please click the following link to set your password: <a href="{{$pass_create_link}}" target="_blank">Create your password</a></p>
            
            <p>Please keep this information safe and do not share it with anyone.</p>
            <p>To get started, click the button below to log in to your account:</p>
            <a href="{{ url('/login') }}" class="button">Log In</a>
            <p>If you have any questions or need assistance, feel free to contact our support team.</p>
            <p>Best regards,</p>
            <p>The {{env('APP_NAME')}} Team</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{env('APP_NAME')}}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>