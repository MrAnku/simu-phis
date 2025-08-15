<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>License Expiry Notification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f6f6f6;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 40px auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo img {
            max-width: 150px;
            height: auto;
        }

        h1 {
            color: #333;
            text-align: center;
        }

        .details {
            margin: 20px 0;
        }

        .button {
            display: inline-block;
            padding: 12px 24px;
            background: #3869d4;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
        }

        .footer {
            margin-top: 30px;
            color: #888;
            font-size: 14px;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="logo">
            <img src="{{ env('CLOUDFRONT_URL') . '/assets/images/simu-logo-dark.png' }}"
                alt="{{ config('app.name') }} Logo">
        </div>
        <h1>Your simUphish Account Has Expired – Need Any Help?</h1>
        <p>Dear {{ $company->email }},</p>
        <p>We noticed that your simUphish account has recently expired. We wanted to check in and see if you need any
            assistance or have any questions regarding the platform.</p>
        <p>simUphish is designed to help your organization stay ahead of phishing threats through simulated attacks,
            real-time training, and in-depth reporting. If you ran into any technical issues or need help navigating the
            platform, our support team is here to help.</p>

        <p>
            If you're interested in renewing your account or just want to explore more about how simUphish can support
            your phishing awareness efforts, feel free to reply to this email or contact us directly at <a
                href="mailto:contact@simuphish.com" target="_blank">contact@simuphish.com</a>.
        </p>

        <p>
            Looking forward to hearing from you.
        </p>

        <p>Best regards,<br>
            <strong>Customer Support Team</strong><br>
            <strong>simUphish</strong><br>
            <a href="mailto:contact@simuphish.com" target="_blank">contact@simuphish.com</a><br>
            <a href="https://www.simuphish.com" target="_blank">www.simuphish.com</a>
        </p>
        
    </div>
</body>

</html>
