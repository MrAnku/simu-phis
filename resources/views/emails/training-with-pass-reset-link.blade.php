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
            <img src="{{$mailData['logo']}}" alt="Company Logo">
        </div>
        <div class="content">
            <h1>Hi {{$mailData['user_name']}}, You were in attack</h1>
            <p>Training <strong>{{$mailData['training_name']}}</strong> is assigned to you. Kindly login to our Learning Portal to complete your training.</p>
            <p>To create your password, please click the link below:</p>
        </div>
        <div class="footer">
            <a href="{{$mailData['password_create_link']}}" class="button">Start Training Now</a>
        </div>
    </div>
</body>
</html>
