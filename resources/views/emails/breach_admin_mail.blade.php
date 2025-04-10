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
        <div class="content">
            <h1>Hello, {{ $company->full_name }}</h1>

            <p>A data breach has been detected that may have affected one of your employee associated with the account
                {{ $employee->user_email }}. This breach may have compromised sensitive data. Below are the details of
                the breach:</p>

            <h2>Breach Details:</h2>
            @foreach ($breachData as $breach)
                <p><strong>Platform:</strong> {{ $breach['Title'] }}</p>
                <p><strong>Breach Date:</strong>
                    {{ \Carbon\Carbon::parse($breach['BreachDate'])->toFormattedDateString() }}</p>
                <p><strong>Description:</strong> {!! $breach['Description'] !!}</p>
            @endforeach

            <h2>Employee Affected:</h2>
            <p><strong>Name:</strong> {{ $employee->user_name }}</p>
            <p><strong>Email:</strong> {{ $employee->user_email }}</p>

            <h3>Immediate Actions to Take:</h3>
            <ul>
                <li>Notify the affected employee about the breach and advise them to secure their account.</li>
                <li>Reset the employee's password and enable multi-factor authentication (MFA) if available.</li>
                <li>Monitor any suspicious activity related to this breach.</li>
                <li>Review the security protocols and consider further steps to prevent future breaches.</li>
            </ul>

            <h3>Security Recommendations:</h3>
            <p>Consider taking the following actions:</p>
            <ol>
                <li>Ensure that all employees are using strong, unique passwords.</li>
                <li>Review and enforce the use of multi-factor authentication (MFA) for all critical accounts.</li>
                <li>Investigate whether any other accounts have been compromised.</li>
                <li>Contact the affected service provider (if applicable) to determine if further action is required.
                </li>
            </ol>

            <p>Please act quickly to minimize the impact of this breach and ensure that the employee's account is
                secured.</p>

            <p>If you need any assistance or have further questions, feel free to reach out to the security team.</p>
            <p>Thank you for your attention to this matter.</p>
        </div>
    </div>
</body>

</html>
