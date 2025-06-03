@php
if(session('locale')){
    App::setLocale(session('locale'));    
}
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr" data-nav-layout="vertical"
data-theme-mode="light" data-header-styles="light" data-menu-styles="dark" data-toggled="close">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>
        Learning | {{env('APP_NAME')}}
    </title>
    <link rel="icon" href="/assets/images/simu-icon.png" type="image/x-icon" />

    <!-- CSS files -->
    <link href="/dist/css/tabler.min.css?1685973381" rel="stylesheet" />
    <link href="/dist/css/tabler-flags.min.css?1685973381" rel="stylesheet" />
    <link href="/dist/css/tabler-payments.min.css?1685973381" rel="stylesheet" />
    <link href="/dist/css/tabler-vendors.min.css?1685973381" rel="stylesheet" />
    <link href="/dist/css/demo.min.css?1685973381" rel="stylesheet" />

    <!-- CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">



    <style>
        @import url("https://rsms.me/inter/inter.css");

        :root {
            --tblr-font-sans-serif: "Inter Var", -apple-system, BlinkMacSystemFont,
                San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif;
        }

        body {
            font-feature-settings: "cv03", "cv04", "cv11";
        }
    </style>
</head>

<body class="layout-boxed">
    <script src="/dist/js/demo-theme.min.js?1685973381"></script>
    <div class="page">
        <!-- Navbar -->
        <header class="navbar navbar-expand-md d-print-none">
            <div class="container-xl">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu"
                    aria-controls="navbar-menu" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <h1 class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
                    <a href="#">
                        <img src="/assets/images/simu-logo-dark.png" alt="{{env('APP_NAME')}}" class="navbar-brand-image"
                            style="width: 181px; height: auto;" />
                    </a>
                </h1>
                <div>
                    <select class="form-control" id="languageSelect" data-trigger>
                        <option {{ app()->getLocale() == 'en' ? 'selected' : '' }} value="en">{{ __('English (En)') }}</option>
                        <option {{ app()->getLocale() == 'ar' ? 'selected' : '' }} value="ar">{{ __('عربي (AR)') }}</option>
                        <option {{ app()->getLocale() == 'ru' ? 'selected' : '' }} value="ru">{{ __('Русский (RU)') }}</option>
                    </select>
                </div>
            </div>
        </header>
        <div class="page-wrapper">
            <!-- Page header -->
            <div class="page-header d-print-none">
                <div class="container-xl">
                    <div class="row g-2 align-items-center">
                        <div class="col">
                            <!-- Page pre-title -->

                            <h2 class="page-title">
                                {{-- Learner Dashboard: {{ session('learner')->login_username }} --}}
                            </h2>
                        </div>
                        <!-- Page title actions -->
                        <div class="col-auto ms-auto d-print-none">
                            <div class="btn-list">
                                {{-- <span class="d-none d-sm-inline">
                                    <a href="{{ route('learner.logout') }}" class="btn"> Log Out </a>
                                </span> --}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Page body -->
            <div class="page-body">
                <div class="container-xl">
                    <div class="row row-deck row-cards">
                        <div class="col-12">
                            <div class="row row-cards">
                                <div class="col-sm-6 col-lg-3">
                                    <div class="card card-sm">
                                        <div class="card-body">
                                            <div class="row align-items-center">
                                                <div class="col-auto">
                                                    <span
                                                        class="bg-primary text-white avatar"><!-- Download SVG icon from http://tabler-icons.io/i/currency-dollar -->
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="icon icon-tabler icon-tabler-align-box-bottom-center"
                                                            width="24" height="24" viewBox="0 0 24 24"
                                                            stroke-width="1.5" stroke="currentColor" fill="none"
                                                            stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                            <path
                                                                d="M3 3m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" />
                                                            <path d="M9 15v2" />
                                                            <path d="M12 11v6" />
                                                            <path d="M15 13v4" />
                                                        </svg>
                                                    </span>
                                                </div>
                                                <div class="col">
                                                    <div class="font-weight-medium">
                                                        {{ __('Average Score') }}: <span dir="ltr">{{ intval($averageScore) }}%</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-3">
                                    <div class="card card-sm">
                                        <div class="card-body">
                                            <div class="row align-items-center">
                                                <div class="col-auto">
                                                    <span
                                                        class="bg-green text-white avatar"><!-- Download SVG icon from http://tabler-icons.io/i/shopping-cart -->
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="icon icon-tabler icon-tabler-file-description"
                                                            width="24" height="24" viewBox="0 0 24 24"
                                                            stroke-width="1.5" stroke="currentColor" fill="none"
                                                            stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                            <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                                                            <path
                                                                d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
                                                            <path d="M9 17h6" />
                                                            <path d="M9 13h6" />
                                                        </svg>
                                                    </span>
                                                </div>
                                                <div class="col">
                                                    <div class="font-weight-medium">
                                                        {{ __('Assigned Training') }}:
                                                        {{ count($assignedTrainingCount) }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-3">
                                    <div class="card card-sm">
                                        <div class="card-body">
                                            <div class="row align-items-center">
                                                <div class="col-auto">
                                                    <span
                                                        class="bg-twitter text-white avatar"><!-- Download SVG icon from http://tabler-icons.io/i/brand-twitter -->
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="icon icon-tabler icon-tabler-triangle-square-circle"
                                                            width="24" height="24" viewBox="0 0 24 24"
                                                            stroke-width="1.5" stroke="currentColor" fill="none"
                                                            stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                            <path d="M12 3l-4 7h8z" />
                                                            <path d="M17 17m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" />
                                                            <path
                                                                d="M4 14m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" />
                                                        </svg>
                                                    </span>
                                                </div>
                                                <div class="col">
                                                    <div class="font-weight-medium">
                                                        {{ __('Completed Training') }}:
                                                        {{ count($completedTrainingCount) }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-3">
                                    <div class="card card-sm">
                                        <div class="card-body">
                                            <div class="row align-items-center">
                                                <div class="col-auto">
                                                    <span
                                                        class="bg-facebook text-white avatar"><!-- Download SVG icon from http://tabler-icons.io/i/brand-facebook -->
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="icon icon-tabler icon-tabler-certificate"
                                                            width="24" height="24" viewBox="0 0 24 24"
                                                            stroke-width="1.5" stroke="currentColor" fill="none"
                                                            stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                            <path d="M15 15m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" />
                                                            <path d="M13 17.5v4.5l2 -1.5l2 1.5v-4.5" />
                                                            <path
                                                                d="M10 19h-5a2 2 0 0 1 -2 -2v-10c0 -1.1 .9 -2 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -1 1.73" />
                                                            <path d="M6 9l12 0" />
                                                            <path d="M6 12l3 0" />
                                                            <path d="M6 15l2 0" />
                                                        </svg>
                                                    </span>
                                                </div>
                                                <div class="col">
                                                    <div class="font-weight-medium">{{ __('Badge Score') }}: 0</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-3">
                                    <div class="card card-sm">
                                        <div class="card-body">
                                            <div class="row align-items-center">
                                                <div class="col-auto">
                                                    <span class="bg-facebook text-white avatar">
                                                        <!-- Facebook icon or any other icon -->
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="icon icon-tabler icon-tabler-certificate"
                                                            width="24" height="24" viewBox="0 0 24 24"
                                                            stroke-width="1.5" stroke="currentColor" fill="none"
                                                            stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                            <path d="M15 15m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" />
                                                            <path d="M13 17.5v4.5l2 -1.5l2 1.5v-4.5" />
                                                            <path
                                                                d="M10 19h-5a2 2 0 0 1 -2 -2v-10c0 -1.1 .9 -2 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -1 1.73" />
                                                            <path d="M6 9l12 0" />
                                                            <path d="M6 12l3 0" />
                                                            <path d="M6 15l2 0" />
                                                        </svg>
                                                    </span>
                                                </div>
                                                <div class="col">
                                                    <div class="font-weight-medium">{{ __('No. of Certificates') }}:
                                                        {{ $totalCertificates }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card card-md">
                                <div class="card-body p-3">
                                    <div class="row align-items-center">
                                        <div class="col-10">
                                            <h3 class="h1">{{ __('Badges Achieved') }}</h3>
                                            <div class="markdown text-secondary">
                                                <a href="#" target="_blank" rel="noopener">{{ __('Looking to earn more badges?') }}</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">{{ __('Assigned Trainings') }}</h3>
                                </div>
                                <div class="card-table table-responsive">
                                    <table class="table table-vcenter">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Training Module') }}</th>
                                                <th>{{ __('Estimated Time') }}</th>
                                                <th>{{ __('Passing Score') }}</th>
                                                <th>{{ __('Personal Best') }}</th>
                                            </tr>
                                        </thead>

                                        @forelse ($assignedTrainingCount as $training)
                                            <tr>
                                                @if ($training->training_type != 'games')
                                                    <td class="text-secondary">
                                                        <a
                                                            href="
                                                    @if ($training->training_type == 'static_training') {{ route('learner.start.training', [
                                                        'training_id' => encrypt($training->training),
                                                        'training_lang' => $training->training_lang,
                                                        'id' => base64_encode($training->id),
                                                    ]) }}

                                                    @elseif($training->training_type == 'ai_training')
                                                            {{ route('learner.start.ai.training', [
                                                                'topic' => encrypt($training->trainingData->name),
                                                                'language' => $training->training_lang,
                                                                'id' => base64_encode($training->id),
                                                            ]) }}
                                                    @else
                                                            {{ route('learn.gamified.training', [
                                                                'training_id' => encrypt($training->training),
                                                                'id' => base64_encode($training->id),
                                                                'lang' => $training->training_lang,
                                                            ]) }} @endif">
                                                            {{ $training->trainingData->name }}
                                                        </a>
                                                        {{-- <a
                                                        href="{{ route('learn.testquiz', [
                                                            'id' => base64_encode($training->id),
                                                        ]) }}">{{ $training->trainingData->name }}</a> --}}
                                                    </td>
                                                    <td class="text-secondary">
                                                        {{ $training->trainingData->estimated_time }} {{ __('Minutes') }}
                                                    </td>
                                                    <td class="text-secondary">>=
                                                        {{ $training->trainingData->passing_score }}</td>
                                                    @if ($training->personal_best < 30)
                                                        <td class="text-danger"><b>{{ $training->personal_best }}%</b>
                                                        </td>
                                                    @else
                                                        <td class="text-success">
                                                            <b>{{ $training->personal_best }}%</b>
                                                        </td>
                                                    @endif
                                                @endif

                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center">{{ __('No new training has been assigned!') }}
                                                </td>
                                            </tr>
                                        @endforelse



                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">{{ __('Assigned Games') }}</h3>
                                </div>
                                <div class="card-table table-responsive">
                                    <table class="table table-vcenter">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Game Name') }}</th>
                                                <th>{{ __('Time Spent on Game Play') }}</th>
                                                <th>{{ __('Score') }}</th>
                                            </tr>
                                        </thead>

                                        @forelse ($assignedTrainingCount as $training)
                                            <tr>
                                                @if ($training->training_type == 'games')
                                                    <td class="text-secondary">
                                                        <a href="{{ env('TRAINING_GAME_URL') }}/{{ $training->trainingGame->slug }}/?id={{ base64_encode($training->id) }}"
                                                            target="_blank">
                                                            {{ $training->trainingGame->name }}
                                                        </a>
                                                    </td>
                                                    <td class="text-secondary">
                                                        {{ sprintf('%02d:%02d', floor($training->game_time / 60), $training->game_time % 60) }}
                                                    </td>

                                                    @if ($training->personal_best < 30)
                                                        <td class="text-danger"><b>{{ $training->personal_best }}%</b>
                                                        </td>
                                                    @else
                                                        <td class="text-success">
                                                            <b>{{ $training->personal_best }}%</b>
                                                        </td>
                                                    @endif
                                                @endif

                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center">{{ __('No new game has been assigned!') }}
                                                </td>
                                            </tr>
                                        @endforelse
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">{{ __('Completed Training') }}</h3>
                                </div>
                                <div class="card-table table-responsive">
                                    <table class="table table-vcenter">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Training Module') }}</th>
                                                <th>{{ __('Personal Best') }}</th>
                                                <th>{{ __('Completion Date') }}</th>
                                                <th>{{ __('Download Certificate') }}</th>
                                            </tr>
                                        </thead>
                                        @forelse ($completedTrainingCount as $training)
                                            <tr>
                                                <td class="text-secondary">
                                                    <a href="#">
                                                        {{ $training->trainingData->name }}
                                                    </a>
                                                </td>
                                                <td class="text-success"><b>{{ $training->personal_best }}%</b></td>
                                                <td class="text-secondary">{{ $training->completion_date }}
                                                </td>
                                                <td>
                                                    <form action="/download-certificate" method="POST">
                                                        @csrf
                                                        <input type="hidden" name="training_module"
                                                            value="{{ $training->trainingData->name }}">
                                                        <input type="hidden" name="training_id"
                                                            value="{{ $training->training }}">
                                                        <input type="hidden" name="completion_date"
                                                            value="{{ $training->completion_date }}">
                                                        <input type="hidden" name="username"
                                                            value="{{ session('learner')->login_username }}">
                                                        <button type="submit"
                                                            class="btn btn-primary btn-sm">{{ __('Download') }}</button>
                                                    </form>
                                                </td>

                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center">{{ __('No new training has been assigned!') }}</td>
                                            </tr>
                                        @endforelse


                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <footer class="footer footer-transparent d-print-none">
                <div class="container-xl">
                    <div class="row text-center align-items-center justify-content-center">
                        <div class="col-12 col-lg-auto mt-3 mt-lg-0">
                            <ul class="list-inline list-inline-dots mb-0">
                                <li class="list-inline-item">
                                    {{ __('Copyright') }} &copy; {{ date('Y') }}
                                    <a href="." class="link-secondary">{{ env('APP_NAME') }}</a>. {{ __('All rights reserved.') }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- Tabler Core -->
    <script src="/dist/js/tabler.min.js?1685973381" defer></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


    <script>
        $(document).ready(function() {
            $('#languageSelect').change(function() {

                const lang = $(this).val();
                const optionText = $(this).find('option:selected').text();
                confirmLanguage(optionText, lang);
                console.log(lang);
            });
        });

        function confirmLanguage(lang, langCode) {
            Swal.fire({
                title: "{{ __('Are you sure?') }}",
                text: `{{ __('This training will be changed to :lang language!', ['lang' => '${lang}']) }}`,
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "{{ __('Yes, Change Language!') }}",
                cancelButtonText: "{{ __('Cancel') }}"
            }).then((result) => {
                if (result.isConfirmed) {
                    changeLanguage(langCode);
                    console.log("changee");

                }
            });
        }

        function changeLanguage() {
            const locale = document.getElementById('languageSelect').value;
            console.log("locale is : ", locale);
            
            window.location.href = '/lang/' + locale;
        }
    </script>



</body>

</html>
