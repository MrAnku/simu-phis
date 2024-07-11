<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>simUphish Partnership Confirmation</title>
            <style>
                /* Styles for better email rendering */
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    margin: 0;
                    padding: 0;
                    background-color: #f4f4f4;
                    text-align: center;
                }
                .container {
                    max-width: 600px;
                    margin: 20px auto;
                    padding: 20px;
                    background: #fff;
                    border-radius: 8px;
                    box-shadow: 0 0 10px rgba(0,0,0,0.1);
                }
                h1 {
                    color: #333;
                }
                p {
                    color: #666;
                }
                .credentials {
                    background: #f9f9f9;
                    padding: 10px;
                    border-radius: 6px;
                    margin-top: 20px;
                }
                .credentials p {
                    margin: 0;
                    font-weight: bold;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>simUphish Partnership Confirmation</h1>
                <p>Congratulations! Your partnership with simUphish has been approved.</p>
                <p>You can now access your partner dashboard and resources by logging in with the following credentials:</p>
                <div class="credentials">
                    <p>Username: {{ $username}}</p>
                    <p>Password: {{$password}}</p>
                </div>
                <p>We're excited to have you on board and look forward to working together to enhance cybersecurity awareness!</p>
            </div>
        </body>
        </html>