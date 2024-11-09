<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate of Completion</title>
    <style>
        /* General Styles */
        body {
            font-family: 'Arial', sans-serif;
            background: #e1f5fe; /* Soft blue gradient background */
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: #333;
        }

        /* Certificate Container */
        .certificate-container {
            background: #ffffff;
            border: 15px solid #007BFF;
            border-radius: 20px;
            padding: 40px;
            width: 90%;
            max-width: 900px;
            text-align: center;
            box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        /* Header Styles */
        h1 {
            color: #007BFF;
            font-size: 48px;
            font-weight: bold;
            margin-bottom: 30px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        /* Certificate Text Styles */
        p {
            font-size: 20px;
            line-height: 1.8;
            margin: 15px 0;
            color: #333;
        }

        .certificate-id {
            font-weight: bold;
            font-size: 22px;
            color: #007BFF;
            margin-top: 20px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        /* Footer Styles */
        .footer {
            margin-top: 40px;
            font-style: italic;
            font-size: 18px;
            color: #777;
        }

        /* Signature Section */
        .signature {
            margin-top: 50px;
            font-size: 24px;
            font-weight: bold;
            color: #007BFF;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Divider */
        .divider {
            margin: 40px 0;
            border-top: 3px solid #007BFF;
            width: 50%;
            margin-left: auto;
            margin-right: auto;
        }

        /* Decorative Elements */
        .decorative {
            position: absolute;
            top: -50px;
            right: -50px;
            width: 150px;
            height: 150px;
            background: rgba(0, 123, 255, 0.15);
            border-radius: 50%;
            box-shadow: 0 0 50px rgba(0, 123, 255, 0.2);
        }

        /* Additional Information Block */
        .certificate-details {
            margin: 30px 0;
            font-size: 22px;
            color: #555;
            line-height: 1.8;
        }

        .certificate-details span {
            font-weight: bold;
            color: #007BFF;
        }

    </style>
</head>
<body>
    <div class="certificate-container">
        <div class="decorative"></div> <!-- Decorative element for a touch of elegance -->

        <h1>Certificate of Completion</h1>

        <div class="certificate-details">
            <p><span>Training Module:</span> {{ $trainingModule }}</p>
            <p><span>Completion Date:</span> {{ $completionDate }}</p>
            <p><span>Username:</span> {{ $username }}</p>
        </div>

        <div class="divider"></div>

        <p class="certificate-id"><strong>Certificate ID:</strong> {{ $certificateId }}</p>

        <div class="footer">
            <p>This certificate confirms the successful completion of the training module.</p>
        </div>

        <div class="signature">
            <p>Authorized Signature</p>
        </div>
    </div>
</body>
</html>
