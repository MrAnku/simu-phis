<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Successful</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="icon" href="{{ asset('assets') }}/images/simu-icon.png" type="image/x-icon" />
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f4f4f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background: #fff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .container h1 {
            color: #4CAF50;
            margin-bottom: 1rem;
        }
        .container p {
            color: #333;
            margin-bottom: 2rem;
        }
        .container a {
            display: inline-block;
            padding: 0.5rem 1rem;
            color: #fff;
            background-color: #4CAF50;
            border-radius: 4px;
            text-decoration: none;
        }
        .container a:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Password Reset Successful</h1>
        <p>Your password has been successfully reset. You can now log in with your new password.</p>
        <a href="{{env('SIMUPHISH_LEARNING_URL')}}">Go to Login</a>
    </div>
</body>
</html>