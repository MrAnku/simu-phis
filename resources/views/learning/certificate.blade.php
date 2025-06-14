<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Certificate</title>

    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: #fdf6f8;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: #333;
        }

        .certificate {
            width: 90%;
            max-width: 900px;
            position: relative;
            margin: 0 auto;
            padding: 60px;
            background: linear-gradient(to bottom right, rgba(255, 255, 255, 0.95), rgba(249, 247, 253, 0.95));
            border: 10px solid #eceff1;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            box-sizing: border-box;
            overflow: hidden;
        }

        .top-decor,
        .bottom-decor {
            position: absolute;
            width: 350px;
            height: 350px;
            background: #6f4ef2;
            border-radius: 50%;
            z-index: 1;
            opacity: 0.7;
            filter: blur(10px);
            pointer-events: none;
            /* No text inside */
            content: "";
        }

        .top-decor {
            top: -120px;
            left: -120px;
        }

        .bottom-decor {
            bottom: -120px;
            right: -120px;
        }

        .certificate-content {
            position: relative;
            z-index: 2;
            text-align: center;
        }
        .certificate h1 {
            font-size: 40px;
            color: #6156dc;
            letter-spacing: 8px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .certificate h2 {
            font-size: 18px;
            font-weight: 500;
            letter-spacing: 2px;
            margin-bottom: 20px;
        }

        .certificate-content .name {
            font-family: cursive !important;
            font-size: 48px;
            font-weight: bold;
            color: #000;
            margin: 20px 0 10px;
        }

        .certificate .desc {
            font-size: 18px;
            margin-bottom: 30px;
            letter-spacing: 1px;
        }

        .certificate .course {
            font-size: 22px;
            font-weight: bold;
            color: #222;
            margin-bottom: 40px;
        }

        .certificate .date {
            font-size: 16px;
            color: #333;
            margin-top: 20px;
             position: absolute;
            bottom: 140px;
            left: 30px;
             max-width: 200px;
            z-index: 3;
        }

        .logo {
            position: absolute;
            top: 40px;
            right: 40px;
        }

        .ribbon {
            position: absolute;
            bottom: 170px;
            left: 40px;
            width: 80px;
            z-index: 3;
        }

        .ribbon img {
            width: 100%;
            height: auto;
        }
    </style>

</head>

<body>
    <div class="certificate">

        <div class="top-decor"></div>
        <div class="bottom-decor"></div>

        <div class="logo">
            <img src="{{ public_path('assets/images/simu-logo-dark.png') }}" style="max-width: 200px;"
                alt="Company Logo">
        </div>

        <div class="certificate-content">
            <h1>CERTIFICATE</h1>
            <h2>OF ACHIEVEMENT</h2>
            <p>Presented To</p>
            <div class="name">{{ ucwords($userName) }}</div>
            <div>{{ $userEmail }}</div>
            <p class="desc">For the successful completion of</p>
            <div class="course">{{ $trainingModule }}</div>
            <div>{{ $certificateId }}</div>
            <div class="ribbon">
                <img src="{{ public_path('assets/images/certificate-badge.png') }}" alt="Badge Logo">
            </div>
               <div class="date">{{ \Carbon\Carbon::parse($completionDate)->format('F j, Y') }}</div>
        </div>
    </div>
</body>

</html>
