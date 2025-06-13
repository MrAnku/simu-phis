<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Certificate</title>
  <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: 'Montserrat', sans-serif;
      background: #fdf6f8;
      color: #333;
    }

    .certificate {
      width: 100%;
      max-width: 1200px;
      margin: 0 auto;
      padding: 60px;
      background: linear-gradient(to bottom right, #ffffff, #f9f7fd);
      position: relative;
      border: 10px solid #eceff1;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
      box-sizing: border-box;
    }

    .top-decor, .bottom-decor {
      position: absolute;
      width: 300px;
      height: 300px;
      background: radial-gradient(circle at top left, #6f4ef2 0%, #c167ff 100%);
      border-radius: 50%;
      z-index: 0;
    }

    .top-decor {
      top: -100px;
      left: -100px;
    }

    .bottom-decor {
      bottom: -100px;
      right: -100px;
      background: radial-gradient(circle at bottom right, #6f4ef2 0%, #c167ff 100%);
    }

    .certificate-content {
      position: relative;
      z-index: 1;
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
      font-family: 'Great Vibes', cursive;
      font-size: 48px;
      font-weight: bold;
      color: #000;
      margin: 30px 0 10px;
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
      margin-top: 40px;
    }

    .logo {
      position: absolute;
      top: 40px;
      right: 40px;
      text-align: right;
    }

    .logo h3 {
      margin: 0;
      font-size: 20px;
      color: #6156dc;
      font-weight: 700;
    }

    .logo p {
      margin: 0;
      font-size: 12px;
      color: #777;
      font-weight: 500;
    }

    .ribbon {
      position: absolute;
      left: 40px;
      bottom: 40px;
      width: 80px;
    }

    .ribbon img {
      width: 100%;
    }

  </style>
</head>
<body>
  <div class="certificate">
    <div class="top-decor"></div>
    <div class="bottom-decor"></div>

    <div class="logo">
      <h3>simUphish</h3>
      <p>DEFEND | EDUCATE | EMPOWER</p>
    </div>

    <div class="certificate-content">
      <h1>CERTIFICATE</h1>
      <h2>OF ACHIEVEMENT</h2>
      <p>Presented To</p>
      <div class="name">{{ $username ?? 'Recipient Name' }}</div>
      <p class="desc">For the successful completion of</p>
      <div class="course">{{ $trainingModule ?? 'Phishing Training' }}</div>
      <div class="date">{{ $completionDate ?? 'June 11, 2025' }}</div>
    </div>

     {{-- <i class='bx  bx-medal-star-alt'  ></i>  --}}
     <img src="https://img.icons8.com/emoji/96/000000/reminder-ribbon-emoji.png" alt="Ribbon" />
    <div class="ribbon">
       
      <!-- <img src="https://img.icons8.com/emoji/96/000000/reminder-ribbon-emoji.png" alt="Ribbon" /> -->
    </div>
  </div>
</body>
</html>
