<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phishing Alert</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(to right, #ff5f6d, #ffc371);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            color: #333;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            max-width: 600px;
            width: 90%;
            text-align: center;
        }
        h1 {
            color: #d9534f;
            margin-bottom: 20px;
        }
        p {
            font-size: 16px;
            margin-bottom: 20px;
        }
        .button-group {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .button-group a {
            background: #d9534f;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            margin: 5px 0;
            transition: background 0.3s;
        }
        .button-group a:hover {
            background: #c9302c;
        }
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            p {
                font-size: 14px;
            }
            .button-group a {
                padding: 8px 15px;
                font-size: 14px;
            }
        }
        @media (max-width: 480px) {
            .container {
                padding: 15px;
            }
            p {
                font-size: 12px;
            }
            .button-group a {
                padding: 6px 10px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>You Were Phished</h1>
        <p>Unfortunately, it appears that you have been a victim of a phishing attempt. Phishing is a type of online scam where attackers try to trick you into providing sensitive information such as usernames, passwords, and credit card details by pretending to be a trustworthy entity.</p>
    </div>
</body>
</html>
