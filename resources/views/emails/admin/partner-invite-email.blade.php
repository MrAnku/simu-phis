<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{env('APP_NAME')}} Partner Program Invitation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .email-container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
        }
        h2 {
            color: #34495e;
            margin-top: 25px;
        }
        ul {
            padding-left: 20px;
        }
        li {
            margin-bottom: 8px;
        }
        .cta-button {
            display: inline-block;
            background-color: #3498db;
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 20px 0;
            text-align: center;
        }
        .cta-button:hover {
            background-color: #2980b9;
        }
        .footer {
            border-top: 1px solid #eee;
            margin-top: 30px;
            padding-top: 20px;
            font-size: 14px;
            color: #666;
        }
        .disclaimer {
            font-size: 12px;
            color: #888;
            font-style: italic;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <h1>Welcome to {{env('APP_NAME')}} Partner Program</h1>
        
        <p>Hello, {{$partnerEmail}}</p>
        
        <p>We're excited to invite you to join the <strong>{{env('APP_NAME')}} Partner Program</strong>! {{env('APP_NAME')}} is a leading phishing awareness platform that helps organizations strengthen their cybersecurity posture through realistic phishing simulations and comprehensive security awareness training.</p>
        
        <h2>Why Partner with {{env('APP_NAME')}}?</h2>
        
        <ul>
            <li><strong>Industry-leading platform</strong> for phishing simulation and security awareness</li>
            <li><strong>Competitive commission structure</strong> and revenue sharing opportunities</li>
            <li><strong>Comprehensive partner support</strong> and training resources</li>
            <li><strong>Marketing co-op programs</strong> to help grow your business</li>
            <li><strong>Technical integration support</strong> for seamless customer onboarding</li>
        </ul>
        
        <h2>What's Next?</h2>
        
        <p>Click the button below to complete your partner registration and access our partner portal where you can:</p>
        
        <ul>
            <li>Review partnership terms and agreements</li>
            <li>Access marketing materials and resources</li>
            <li>Set up your partner account</li>
            <li>Start referring customers</li>
        </ul>
        
        <div style="text-align: center;">
            <a href="{{ env('PARTNER_PORTAL') . "/join-partner-program?email=" . $partnerEmail }}&token={{ $token }}" class="cta-button">Join Partner Program</a>
        </div>
        
        <h2>Questions?</h2>

        <p>Our partner team is here to help. Contact us at <a href="mailto:support{{"@" . env('APP_NAME')}}.com">support{{"@" . env('APP_NAME')}}.com</a> or call our partner hotline.</p>

        <p>Together, let's make the digital world safer for everyone.</p>
        
        <div class="footer">
            <p><strong>Best regards,</strong><br>
            The {{env('APP_NAME')}} Partner Team</p>
        </div>
        
        <div class="disclaimer">
            <p>This invitation is valid for 2 days. If you have any questions about this invitation, please contact our support team.</p>
        </div>
    </div>
</body>
</html>