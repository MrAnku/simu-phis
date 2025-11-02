<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comic Assignment Notification</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            background-color: #2c3e50;
            padding: 20px;
            text-align: center;
        }
        .header img {
            max-height: 60px;
            max-width: 200px;
        }
        .content {
            padding: 30px;
            color: #333333;
            line-height: 1.6;
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
        }
        .message {
            font-size: 16px;
            margin-bottom: 25px;
        }
        .cta-button {
            display: inline-block;
            background-color: #3498db;
            color: #ffffff;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 20px 0;
        }
        .cta-button:hover {
            background-color: #2980b9;
        }
        .footer {
            background-color: #ecf0f1;
            padding: 20px;
            text-align: center;
            font-size: 14px;
            color: #7f8c8d;
        }
        .company-name {
            font-weight: bold;
            color: #2c3e50;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            @if($logo)
                <img src="{{ $logo }}" alt="{{ $companyName }} Logo">
            @else
                <h1 style="color: #ffffff; margin: 0;">{{ $companyName }}</h1>
            @endif
        </div>
        
        <div class="content">
            <div class="greeting">
                Hello {{ $userName }},
            </div>
            
            <div class="message">
                We hope this email finds you well. A new comic has been assigned to you as part of your ongoing cybersecurity awareness training program.
            </div>
            
            <div class="message">
                This interactive comic is designed to help you recognize and respond to potential security threats in an engaging and educational way.
            </div>
            
            @if($learningPortalUrl)
                <div style="text-align: center;">
                    <a href="{{ $learningPortalUrl }}" class="cta-button">Access Learning Portal</a>
                </div>
            @endif
            
            <div class="message">
                If you have any questions or need assistance accessing the training materials, please don't hesitate to reach out to your administrator.
            </div>
            
            <div class="message">
                Thank you for your commitment to maintaining a secure workplace environment.
            </div>
            
            <div class="message">
                Best regards,<br>
                <span class="company-name">{{ $companyName }}</span> Security Team
            </div>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $companyName }}. All rights reserved.</p>
            <p>This is an automated message. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>