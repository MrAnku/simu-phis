<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Phishing Awareness Infographic</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f6f8fa;
            margin: 0;
            padding: 0;
        }
        .email-container {
            background: #fff;
            max-width: 600px;
            margin: 40px auto;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            padding: 32px;
        }
        .logo {
            width: 120px;
            margin-bottom: 16px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #2d3e50;
            margin-bottom: 24px;
        }
        .infographic {
            width: 100%;
            max-width: 520px;
            border-radius: 6px;
            margin: 24px 0;
        }
        .footer {
            font-size: 13px;
            color: #888;
            margin-top: 32px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <img src="{{ $mailData['logo'] }}" alt="Company Logo" class="logo">
       
        <img src="{{ $mailData['infographic'] }}" alt="Phishing Awareness Infographic" class="infographic">
      
        <div class="footer">
            &copy; {{ date('Y') }} {{ $mailData['company_name'] }}. All rights reserved.
        </div>
    </div>
</body>
</html>