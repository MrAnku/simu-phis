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
    <div class="email-container" style="padding: 20px">
        {{-- <div class="header" style="text-align:center; margin-bottom:43px;">
            <img src="{{$mailData['logo']}}" alt="Company Logo">
        </div> --}}
        <div class="content">
            <h1>Hello, {{ $employee->user_name }}</h1>
            <p>We have detected a potential data breach involving in your email {{ $employee->user_email }}.</p>

            <h2>Breach Details:</h2>
            @foreach($breachData as $breach)
                <p><strong>Platform:</strong> {{ $breach['Title'] }}</p>
                <p><strong>Breach Date:</strong> {{ \Carbon\Carbon::parse($breach['BreachDate'])->toFormattedDateString() }}</p>
                <p><strong>Description:</strong> {!! $breach['Description'] !!}</p>
            @endforeach
    
            <h3>Immediate Action Required:</h3>
            <p>Please take immediate action to secure your account. We recommend the following steps:</p>
            <ol>
                <li>Change your password immediately.</li>
                <li>Enable multi-factor authentication (MFA) if available.</li>
                <li>Monitor your accounts for any suspicious activity.</li>
            </ol>
    
            <p>For more information, please refer to our security protocols or contact the security team if you need assistance.</p>
        </div>
    </div>
</body>
</html>
