<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Certificate</title>

    <style>
        @page {
            size: A4 landscape;
            margin: 0;
        }

        body {
    margin: 0;
    padding: 0;
}
        .certificate {
            display: table;
            table-layout: fixed;
            width: 100%;
            max-width: 1000px;
            height: 700px;
            border: 8px solid red;
            background: #fff;
        }

        .left-section,
        .right-section {
            display: table-cell;
            vertical-align: top;
        }

        .left-section {
            width: 40%;
        }

        .right-section {
            width: 60%;
            padding: 60px 40px;
            text-align: center;
            box-sizing: border-box;
            position: relative;
            border: 2px solid #56dc8c;
        }

        .left-part img {
            width: 60%;
        }


        .logo {
            margin-bottom: 60px;
            text-align: left;
        }

        /* .logo img {
            width: 100px;
        } */

        .title {
            font-size: 36px;
            color: #6156dc;
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .awarded {
            font-size: 18px;
            margin-bottom: 20px;
        }

        .name {
            font-size: 42px;
            font-family: cursive;
            font-weight: bold;
            margin-bottom: 10px;
            color: #000;
        }

        .info {
            font-size: 20px;
            font-weight: 500;
            color: #333;
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

        <div class="left-section">
            <div class="left-part">
                                <img src="{{ asset('assets/images/certificate_left_part.png') }}" alt="SimuPhish Logo">

            </div>
        </div>

        <!-- Right White Section -->
        <div class="right-section">
            <!-- Top Logo -->
            <div class="logo">
                <img src="{{ asset('assets/images/simu-logo-dark.png') }}" alt="SimuPhish Logo">
            </div>

            <!-- Content -->
            <div class="title">Certificate<br>Of Completion</div>
            <div class="awarded">This Certificate has been awarded to</div>
            <div class="name">{{ ucwords($userName) }}</div>
            <br>
            <div class="info">For completing {{ $trainingModule }}</div>

              <div class="ribbon">
                <img src="{{ asset('assets/images/certificate-badge.png') }}" alt="Badge Logo">
            </div>
               <div class="date">{{ \Carbon\Carbon::parse($completionDate)->format('F j, Y') }}</div>


        </div>
    </div>
</body>

</html>
