<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SimuPhish New Account Confirmation</title>
    <style>
        /* Styles for better email rendering */
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
            /* Dim grey */
            padding: 20px;
            text-align: center;
        }

        .header img {
            max-width: 250px;
        }

        .header h1 {
            color: #ffffff;
            margin: 0;
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
            <img src="{{ env('CLOUDFRONT_URL') }}/assets/images/simu-logo-dark.png" alt="{{ env('APP_NAME', 'SimuPhish') }} Logo">

        </div>

        <div>
            <h1>Welcome to {{ env('APP_NAME', 'SimuPhish') }}</h1>
        </div>

        <div class="content">
            <p>Congratulations! Your account has been created successfully!</p>
            <p>Currently, You can't access the dashboard and resources because your account approval is pending, You can
                create your password for now. After the approval of your account you can login to your dashboard.</p>
            <div class="credentials">
                <p>Portal: <a href="{{ env('COMPANY_URL', 'https://app.simuphish.com') }}"
                        target="_blank">{{ env('COMPANY_URL', 'https://app.simuphish.com') }}</a></p>
                <p>Username: {{ $company['email'] }}</p>
                <p>Please click the following link to set your password: <a href="{{ $pass_create_link }}"
                        target="_blank">Create your password</a></p>
            </div>

            <p>We're excited to have you on board and look forward to working together to enhance cybersecurity
                awareness!</p>
            <p>Thank You</p>
            <p>Team {{ env('APP_NAME', 'SimuPhish') }}</p>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} {{ env('APP_NAME', 'SimuPhish') }}. All rights reserved.
        </div>
    </div>
</body>

</html>
