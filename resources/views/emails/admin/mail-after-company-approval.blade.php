<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>SimUphish POC Setup</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f4f6f8;
            font-family: 'Segoe UI', sans-serif;
        }

        .email-container {
            max-width: 700px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .header {
            background-color: #1a73e8;
            color: #ffffff;
            padding: 30px 40px;
            text-align: center;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
        }

        .content {
            padding: 30px 40px;
            color: #333333;
            line-height: 1.6;
        }

        .content h2 {
            color: #1a73e8;
            margin-top: 20px;
        }

        .content ul {
            padding-left: 20px;
        }

        .content li {
            margin-bottom: 10px;
        }

        .footer {
            background-color: #f1f3f4;
            padding: 20px 40px;
            font-size: 14px;
            color: #777777;
            text-align: center;
        }

        .highlight {
            background: #f6f8fa;
            border: 1px solid #dbe2e8;
            padding: 10px 15px;
            border-radius: 5px;
            margin: 10px 0;
            font-family: monospace;
        }

        a {
            color: #1a73e8;
            text-decoration: none;
        }
    </style>
</head>

<body>
    {!! $mailBody !!}
</body>

</html>
