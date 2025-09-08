<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>How's Your Experience Going?</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f6f6f6; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);}
        .logo { text-align: center; margin-bottom: 30px; }
        .logo img { max-width: 150px; height: auto; }
        h2 { color: #333; text-align: center; }
        .details { margin: 20px 0; background: #f8f9fa; padding: 15px; border-radius: 6px; }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: #3869d4;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
        }
        .support-button {
            display: inline-block;
            padding: 12px 24px;
            background: #28a745;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            margin: 0 10px;
        }
        .footer { margin-top: 30px; color: #888; font-size: 14px; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <img src="{{ env('CLOUDFRONT_URL') . '/assets/images/simu-logo-dark.png' }}" alt="{{ config('app.name') }} Logo">
        </div>
        <h2>How's Everything Going?</h2>
        <p>Hi {{ $company->company_name ?? 'there' }},</p>
        <p>
            It's been a few days since you joined {{ config('app.name') }}, and we wanted to check in to see how your experience has been so far.
        </p>
        
        <p>
            We're committed to ensuring you get the most out of our platform. Whether you have questions about features, need help with setup, or want to explore advanced capabilities, we're here to support you every step of the way.
        </p>
        <p>
            <strong>Common areas where we can help:</strong>
        </p>
        <ul>
            <li>Platform configuration and customization</li>
            <li>User training and onboarding</li>
            <li>Best practices and simulation tips</li>
            <li>Integration with your Active Directory</li>
            <li>Any technical questions or concerns</li>
        </ul>
        <p style="text-align: center;">
            <a href="mailto:support@simuphish.com" class="button" style="color: #fff !important;">Get Support</a>
        </p>
        <p style="text-align: center; font-size: 14px; color: #666;">
            <em>No issues? That's great! Feel free to ignore this email.</em>
        </p>
        <div class="footer">
            Best regards,<br>
            The {{ config('app.name') }} Support Team<br>
            <small>We're here to help you succeed!</small>
        </div>
    </div>
</body>
</html>