<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Training Reminder</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            background: #f6f8fb;
            margin: 0;
            padding: 0;
            font-family: 'Fira Sans', Arial, sans-serif;
            color: #222;
        }

        .container {
            max-width: 480px;
            margin: 40px auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            padding: 32px 24px;
        }

        .logo {
            display: block;
            margin: 0 auto 24px auto;
            max-width: 120px;
        }

        h1 {
            color: #000000;
            font-size: 2rem;
            margin: 0 0 12px 0;
            text-align: center;
        }

        h2 {
            font-size: 1.1rem;
            font-weight: 400;
            margin: 0 0 24px 0;
            text-align: center;
        }

        .btn {
            display: inline-block;
            background: #000000;
            color: #fff !important;
            padding: 12px 28px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 1rem;
            margin: 24px auto 0 auto;
            text-align: center;
        }

        .trainings {
            margin: 32px 0 0 0;
            padding: 0;
            list-style: none;
        }

        .trainings li {
            background: #f1f3fa;
            border-radius: 4px;
            padding: 12px 16px;
            margin-bottom: 10px;
            font-size: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .trainings a {
            color: #000000;
            text-decoration: none;
            font-size: 1.1em;
        }

        .footer {
            text-align: center;
            font-size: 0.95em;
            margin-top: 32px;
        }
    </style>
</head>

<body>
    <div class="container">
        <img src="{{ $mailData['logo'] }}" alt="Company Logo" class="logo">
        <h1>{{ __('Training Reminder!') }}</h1>
        <h2>
            {{ __('Hi :name,', ['name' => $mailData['user_name']]) }}
            <br>
            {{ __('This is a friendly reminder that you have pending training modules that need to be completed.') }}
            <br>
            {{ __('Please log in to your training dashboard to complete the assigned trainings:') }}
        </h2>
        <div style="text-align:center;">
            <a href="{{ $mailData['learning_site'] }}" class="btn" target="_blank">
                {{ __('Continue Training') }}
            </a>
        </div>
        <div>
            <p style="text-align: center; font-size: 1.1em; margin-top: 10px;">
                {{ __('Assigned Trainings:') }}
            </p>
        </div>
        @if (!empty($trainingNames))
            <ul class="trainings">
                @foreach ($trainingNames as $trainingName)
                    <li>
                        <span>{{ $trainingName }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
        <div class="footer">
            <a href="#" style=" color: #000000;  text-decoration: none; " target="_blank">Copyright ©
                {{ date('Y') }} {{ $mailData['company_name'] }} | All rights reserved</a>
        </div>
    </div>
</body>

</html>
