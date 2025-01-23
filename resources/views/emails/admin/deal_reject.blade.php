<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Deal Rejection Notification</title>
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
            background-color: #007bff;
            color: #ffffff;
            padding: 10px 0;
            text-align: center;
        }
        .content {
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            color: #777777;
            font-size: 12px;
            margin-top: 20px;
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
            <h1>Deal Rejection Notification</h1>
        </div>
        <div class="content">
            <p>Dear Partner,</p>
            <p>We regret to inform you that your recent deal submission has been rejected. Unfortunately, it does not meet our policies and guidelines.</p>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Company Name</th>
                    <th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Company Email</th>
                </tr>
                <tr>
                    <td style="border: 1px solid #dddddd; text-align: left; padding: 8px;">{{ $deal->company }}</td>
                    <td style="border: 1px solid #dddddd; text-align: left; padding: 8px;">{{ $deal->email }}</td>
                </tr>
            </table>
            <p>We encourage you to review our policies and make the necessary adjustments to your submission. If you have any questions or need further assistance, please do not hesitate to contact us.</p>
            <p>Thank you for your understanding.</p>
            <p>Best regards,</p>
            <p>{{env('APP_NAME')}}</p>
        </div>
        <div class="footer">
            <p>&copy; {{date('Y')}} {{env('APP_NAME')}}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>