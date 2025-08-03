<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>License Expiry Notification</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f6f6f6; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);}
        .logo { text-align: center; margin-bottom: 30px; }
        .logo img { max-width: 150px; height: auto; }
        h1 { color: #333; text-align: center; }
        .details { margin: 20px 0; }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: #3869d4;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
        }
        .footer { margin-top: 30px; color: #888; font-size: 14px; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <img src="{{ env('CLOUDFRONT_URL').  '/assets/images/simu-logo-dark.png' }}" alt="{{ config('app.name') }} Logo">
        </div>
        <h1>License Expiry Notification</h1>
        <p>Dear {{$company->email}},</p>
        <p>We wanted to inform you that the license for your company has expired.</p>
        <div class="details">
            <strong>License Details:</strong>
            <ul>
                <li>Company Name: {{ $company->company_name ?? 'N/A' }}</li>
                <li>License Expiry Date: {{ $company->license?->expiry ?? 'N/A' }}</li>
            </ul>
        </div>
        <p>
            Please renew your license to continue uninterrupted access to our services.
        </p>
       
        <p>
            If you have any questions, feel free to contact our support team.
        </p>
        <div class="footer">
            Thanks,<br>
            {{ config('app.name') }}
        </div>
    </div>
</body>
</html>