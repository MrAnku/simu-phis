<!DOCTYPE html>
<html>

<head>
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        .email-container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
        }

        .header img {
            width: 100px;
            height: auto;
            text-align: center;
        }

        .content {
            margin-top: 20px;
        }

        .content h1 {
            font-size: 24px;
            color: #333;
            text-align: center;
        }

        .content p {
            font-size: 16px;
            color: #555;
        }

        .content a {
            color: #007bff;
            text-decoration: none;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
        }

        .button {
            display: inline-block;
            padding: 10px 20px;
            font-size: 16px;
            color: #fff;
            background-color: #007bff;
            text-decoration: none;
            border-radius: 5px;
        }
    </style>
</head>

<body>
    <div class="email-container">
        <div class="header" style="text-align:center; margin-bottom:43px;">
            <p>heyyyy</p>
            {{-- <img src="{{ $mailData['logo'] }}" alt="Company Logo"> --}}
        </div>
        <div class="content">
            {{-- <h1>Hi {{ $mailData['user_name'] }}, Your organization has enrolled you in a training session after you
                encountered a simulated phishing exercise.</h1>
            <p>Game training <strong>{{ $mailData['training_name'] }}</strong> is assigned to you. Kindly login to our
                Learning Portal to complete your training.</p> --}}
            {{-- <p><strong>Username:</strong> <a href="mailto:{{$mailData['login_email']}}">{{$mailData['login_email']}}</a></p>
            <p><strong>Password:</strong> {{$mailData['login_pass']}}</p> --}}
        </div>
        <div class="footer">
            {{-- <a href="{{ $mailData['learning_site'] }}" class="button">Start Game</a> --}}
        </div>
    </div>
</body>

</html>
