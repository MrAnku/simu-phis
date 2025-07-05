@php
    if (session('locale')) {
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
        Learning | {{ session('companyName') }}
    </title>
    <link rel="icon" href="{{ session('companyFavicon') }}" type="image/x-icon" />

    <!-- CSS files -->
    <link href="/dist/css/tabler.min.css?1685973381" rel="stylesheet" />
    <link href="/dist/css/tabler-flags.min.css?1685973381" rel="stylesheet" />
    <link href="/dist/css/tabler-payments.min.css?1685973381" rel="stylesheet" />
    <link href="/dist/css/tabler-vendors.min.css?1685973381" rel="stylesheet" />
    <link href="/dist/css/demo.min.css?1685973381" rel="stylesheet" />

    <!-- CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">


    <!-- PDF.js library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    </script>




    <style>
        @import url("https://rsms.me/inter/inter.css");

        :root {
            --tblr-font-sans-serif: "Inter Var", -apple-system, BlinkMacSystemFont,
                San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif;
        }

        body {
            font-feature-settings: "cv03", "cv04", "cv11";
        }

        <style>.option-hover:hover {
            background-color: #f1f5ff !important;
            transition: background-color 0.3s ease;
        }

        .form-check-input:checked {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }

        .form-check-input:focus {
            box-shadow: none;
        }

        .quiz-container h6 {
            font-size: 1rem;
        }

        .quiz-container .form-check-label {
            font-size: 0.95rem;
        }

        .quiz-container .form-check {
            cursor: pointer;
        }
    </style>

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
                        <img src="{{ session('companyLogoDark') }}" alt="{{ session('companyName') }}"
                            class="navbar-brand-image" style="width: 181px; height: auto;" />
                    </a>
                </h1>
                <div>
                    <select class="form-control" id="languageSelect" data-trigger>
                        <option {{ app()->getLocale() == 'en' ? 'selected' : '' }} value="en">
                            {{ __('English (En)') }}</option>
                        <option {{ app()->getLocale() == 'ar' ? 'selected' : '' }} value="ar">{{ __('عربي (AR)') }}
                        </option>
                        <option {{ app()->getLocale() == 'ru' ? 'selected' : '' }} value="ru">
                            {{ __('Русский (RU)') }}</option>
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
                                Policy Dashboard: {{ $userEmail }}
                            </h2>
                        </div>
                        <!-- Page title actions -->
                        <div class="col-auto ms-auto d-print-none">
                            <div class="btn-list">
                                <span class="d-none d-sm-inline">
                                    <a href="{{ route('learner.training.dashboard', Session::get('token')) }}"
                                        class="btn"> Training Dashboard </a>
                                </span>
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
                                <div class="col-sm-6 col-lg-4">
                                    <div class="card card-sm">
                                        <div class="card-body">
                                            <div class="row align-items-center">
                                                <div class="col-auto">
                                                    <span class="bg-primary text-white avatar">
                                                        <!-- Download SVG icon from http://tabler-icons.io/i/currency-dollar -->
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
                                                        {{ __('Policies Assigned') }}: <span
                                                            dir="ltr">{{ intval($assignedPolicies->count()) }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-4">
                                    <div class="card card-sm">
                                        <div class="card-body">
                                            <div class="row align-items-center">
                                                <div class="col-auto">
                                                    <span class="bg-green text-white avatar">
                                                        <!-- Download SVG icon from http://tabler-icons.io/i/shopping-cart -->
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
                                                        {{ __('Accepted') }}:
                                                        {{ $assignedPolicies->where('accepted', 1)->count() }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-4">
                                    <div class="card card-sm">
                                        <div class="card-body">
                                            <div class="row align-items-center">
                                                <div class="col-auto">
                                                    <span class="bg-twitter text-white avatar">
                                                        <!-- Download SVG icon from http://tabler-icons.io/i/brand-twitter -->
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
                                                        {{ __('Not Accepted') }}:
                                                        {{ $assignedPolicies->where('accepted', 0)->count() }}
                                                    </div>
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
                                                <a href="#" target="_blank"
                                                    rel="noopener">{{ __('Looking to earn more badges?') }}</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">{{ __('Assigned Policies') }}</h3>
                                </div>
                                <div class="card-table table-responsive">
                                    <table class="table table-vcenter">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Policy Name') }}</th>
                                                <th>{{ __('Policy Description') }}</th>
                                                <th>{{ __('Assigned Date') }}</th>
                                                <th>{{ __('Action') }}</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            @forelse ($assignedPolicies->where('accepted', 0) as $policy)
                                                <tr>
                                                    <td class="text-primary">
                                                        {{ $policy->policyData->policy_name ?? 'Policy not found' }}
                                                    </td>
                                                    <td class="text-secondary">
                                                        {{ $policy->policyData->policy_description ??
                                                            'Description not found' }}
                                                    </td>
                                                    <td class="text-secondary">
                                                        {{ $policy->created_at->format('Y-m-d') ?? 'Date not found' }}
                                                    </td>
                                                    <td class="text-secondary">


                                                        <!-- Button to open modal -->
                                                        <button type="button" class="btn btn-sm btn-primary"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#policyModal{{ $policy->id }}">
                                                            {{ __('View Policy') }}
                                                        </button>

                                                        <!-- Modal -->
                                                        <div class="modal fade" id="policyModal{{ $policy->id }}"
                                                            tabindex="-1" aria-hidden="true">
                                                            <div
                                                                class="modal-dialog modal-fullscreen modal-dialog-centered">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">Policy Document</h5>
                                                                        <button type="button" class="btn-close"
                                                                            data-bs-dismiss="modal"
                                                                            aria-label="Close"></button>
                                                                    </div>

                                                                    <div class="modal-body p-4"
                                                                        style="min-height: 400px;">
                                                                        @php
                                                                            $quiz = json_decode(
                                                                                $policy->policyData->json_quiz ?? '[]',
                                                                            );
                                                                            $hasQuiz =
                                                                                $policy->policyData->has_quiz &&
                                                                                $quiz &&
                                                                                count($quiz) > 0;
                                                                        @endphp

                                                                        <!-- STEP 1: PDF VIEW -->
                                                                        <div id="pdfSection-{{ $policy->id }}">
                                                                            {{-- <div class="border rounded overflow-hidden mb-4"
                                                                                style="height: 80vh;"> --}}





                                                                            {{-- <div class="border rounded overflow-hidden mb-4"
                                                                                    style="">
                                                                                   <canvas  id="pdfViewer-{{ $policy->id }}" style="width: 100%;"></canvas>
                                                                                </div> --}}
                                                                            <div class="border rounded overflow-hidden mb-4"
                                                                                style="height: 80vh;">
                                                                                <iframe
                                                                                    src="{{ env('CLOUDFRONT_URL') . $policy->policyData->policy_file }}"
                                                                                    width="100%" height="100%"
                                                                                    frameborder="0"
                                                                                    style="border: none;"></iframe>
                                                                            </div>








                                                                            {{-- </div> --}}

                                                                            <!-- Accept Section Always Below PDF -->
                                                    <form method="POST" action="">
                                                                                @csrf
                                                            <div class="form-check mt-3">
                                                        <input type="checkbox" class="form-check-input"
                                                            id="agreeCheckbox-{{ $policy->id }}"
                                                                                        onchange="toggleAcceptBtn('{{ $policy->id }}')">
                                                                                    <label class="form-check-label"
                                                                                        for="agreeCheckbox-{{ $policy->id }}">
                                                                                        I have read and accept this
                                                                                        policy
                                                                                    </label>
                                                                                </div>

                                                                                <button type="button"
                                                                                    class="btn btn-success mt-3"
                                                                                    onclick="handleAcceptClick('{{ base64_encode($policy->id) }}', {{ $hasQuiz ? 'true' : 'false' }})"
                                                                                    id="acceptPolicyBtn{{ $policy->id }}"
                                                                                    disabled>
                                                                                    Accept
                                                                                </button>
                                                                            </form>
                                                                        </div>

                                                                        <!-- STEP 2: QUIZ -->

                                                                        @if ($hasQuiz)
                                                                            <div id="quizSection-{{ $policy->id }}"
                                                                                style="display: none;">
                                                                                <div
                                                                                    class="d-flex justify-content-center">
                                                                                    <div class="quiz-container"
                                                                                        style="max-width: 750px; width: 100%;">
                                                                                        <h4
                                                                                            class="mb-4 text-center fw-semibold border-bottom pb-2 text-dark">
                                                                                            📝 Policy Acknowledgement
                                                                                            Quiz</h4>

<form id="quizForm-{{ $policy->id }}">
    @foreach ($quiz as $index => $question)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-start">
                    <div class="me-3 fs-4 text-primary">
                        Q{{ $index + 1 }}.
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="fw-bold text-dark mb-3">
                                                                                                                    {{ $question->question }}
                                                                                                                </h6>

                        <div class="row g-5">
                                                                                                                    @foreach ($question->options as $option)
                                                                                                                        <div class="col-md-6">
                                                                                                                            <div class="form-check p-3 border rounded d-flex align-items-center option-hover">
                                                                                                                                <input class="form-check-input me-2 quiz-radio-{{ $policy->id }}"
                                                                                                                                    type="radio"
                                                                                                                                    name="answers[{{ $index }}]"
                                                                                                                                    value="{{ $option }}"
                                                                                                                                    id="q{{ $index }}_{{ md5($option) }}"
                                                                                                                                    data-question-index="{{ $index }}"
                                                                                                                                    data-question="{{ $question->question }}">
                                                                                                                                <label
                                                                                                                                    class="form-check-label mb-0 text-secondary"
                                                                                                                                    for="q{{ $index }}_{{ md5($option) }}">
                                                                                                                                    {{ $option }}
                                                                                                                                </label>
                                                                                                                            </div>
                                                                                                                        </div>
                                                                                                                    @endforeach
                                                                                                                </div>

                                                                                                            </div>
                                                                                                        </div>
                                                                                                    </div>
                                                                                                </div>
                                                                                            @endforeach
    </form>

    <div class="text-end mt-4">
        <button type="button"
            class="btn btn-primary px-4 py-2"
            onclick="submitQuizAnswers('{{ base64_encode($policy->id) }}')">
            <i class="bi bi-check-circle me-1"></i>
            Submit Quiz & Accept
        </button>
    </div>
</div>
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center">
                                                        {{ __('No Policies Assigned') }}</td>
                                                </tr>
                                            @endforelse
                                        </tbody>

                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">{{ __('Accepted Policies') }}</h3>
                                </div>
                                <div class="card-table table-responsive">
                                    <table class="table table-vcenter">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Policy Name') }}</th>
                                                <th>{{ __('Policy Description') }}</th>
                                                <th>{{ __('File') }}</th>
                                            </tr>
                                        </thead>

                                        @forelse ($assignedPolicies->where('accepted', 1) as $policy)
                                            <tr>
                                                <td class="text-secondary">
                                                    {{ $policy->policyData->policy_name ?? 'Policy not found' }}
                                                </td>
                                                <td class="text-secondary">
                                                    {{ $policy->policyData->policy_description ?? 'Description not found' }}
                                                </td>
                                                <td class="text-secondary">
                                                    <a href="{{ env('CLOUDFRONT_URL') . $policy->policyData->policy_file }}"
                                                        target="_blank">{{ __('View Policy') }}</a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center">
                                                    {{ __('No Policies Accepted') }}</td>
                                            </tr>
                                        @endforelse
                                        </tbody>
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
                                    <a href="." class="link-secondary">{{ session('companyName') }}</a>.
                                    {{ __('All rights reserved.') }}
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
        function acceptPolicy(encodedPolicyId) {
            Swal.fire({
                title: "{{ __('Are you sure?') }}",
                text: "{{ __('Do you want to accept this policy?') }}",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "{{ __('Yes, Accept Policy!') }}",
                cancelButtonText: "{{ __('Cancel') }}"
            }).then((result) => {
                if (result.isConfirmed) {
                    const decodedId = atob(encodedPolicyId);

                    const agreeChecked = $(`#agreeCheckbox-${decodedId}`).is(':checked');
                    if (!agreeChecked) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Oops...',
                            text: 'Please check the agreement box to accept the policy.'
                        });
                        return;
                    }

                    const responses = [];
                    const totalQuestions = $(`#quizForm-${decodedId} .quiz-radio-${decodedId}`).closest('.mb-3')
                        .length;
                    const radios = $(`#quizForm-${decodedId} .quiz-radio-${decodedId}:checked`);

                    radios.each(function() {
                        responses.push({
                            question: $(this).data('question'),
                            answer: $(this).val()
                        });
                    });

                    // Require all quiz questions to be answered
                    if (totalQuestions > 0 && responses.length < totalQuestions) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Incomplete Quiz',
                            text: 'Please answer all quiz questions before accepting the policy.'
                        });
                        return;
                    }

                    // Prepare payload
                    const payload = {
                        id: encodedPolicyId,
                        agreed: true
                    };

                    if (totalQuestions > 0) {
                        payload.responses = responses;
                    }

                    // Send AJAX
                    $.ajax({
                        url: '/accept-policy',
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        contentType: 'application/json',
                        data: JSON.stringify(payload),
                        success: function(res) {
                            if (res.success) {
                                Swal.fire({
                                    title: "{{ __('Success!') }}",
                                    text: "{{ __('Policy accepted successfully.') }}",
                                    icon: "success"
                                }).then(() => location.reload());
                            } else {
                                Swal.fire({
                                    title: "{{ __('Error!') }}",
                                    text: res.message ||
                                        "Something went wrong. Please try again.",
                                    icon: "error"
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                title: "{{ __('Error!') }}",
                                text: "Something went wrong. Please try again.",
                                icon: "error"
                            });
                        }
                    });
                }
            });
        }
    </script>


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

    <script>
        function toggleAcceptBtn(policyId) {
            const checkbox = document.getElementById(`agreeCheckbox-${policyId}`);
            const btn = document.getElementById(`acceptPolicyBtn${policyId}`);
            btn.disabled = !checkbox.checked;
        }

        function handleAcceptClick(encodedPolicyId, hasQuiz) {
            const decodedId = atob(encodedPolicyId);

            // Check if checkbox is checked
            if (!document.getElementById(`agreeCheckbox-${decodedId}`).checked) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Oops...',
                    text: 'Please check the agreement box to accept the policy.'
                });
                return;
            }

            // Show confirmation popup before proceeding
            Swal.fire({
                title: "Are you sure?",
                text: "Do you want to accept this policy?",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, Accept",
                cancelButtonText: "Cancel",
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33"
            }).then((result) => {
                if (result.isConfirmed) {
                    if (hasQuiz) {
                        // Hide PDF section, show quiz
                        document.getElementById(`pdfSection-${decodedId}`).style.display = 'none';
                        document.getElementById(`quizSection-${decodedId}`).style.display = 'block';
                    } else {
                        // Submit directly if no quiz
                        submitPolicyAcceptance(encodedPolicyId, []);
                    }
                }
            });
        }


        function submitQuizAnswers(encodedPolicyId) {
            const decodedId = atob(encodedPolicyId);

            const responses = [];
            const totalQuestions = new Set(
                [...document.querySelectorAll(`.quiz-radio-${decodedId}`)].map(r => r.dataset.questionIndex)
            );
            const answered = new Set(
                [...document.querySelectorAll(`.quiz-radio-${decodedId}:checked`)].map(r => r.dataset.questionIndex)
            );

            if (answered.size < totalQuestions.size) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Incomplete Quiz',
                    text: 'Please answer all quiz questions before submitting.'
                });
                return;
            }

            [...document.querySelectorAll(`.quiz-radio-${decodedId}:checked`)].forEach(el => {
                responses.push({
                    question: el.dataset.question,
                    answer: el.value
                });
            });

            submitPolicyAcceptance(encodedPolicyId, responses);
        }

        function submitPolicyAcceptance(encodedPolicyId, responses) {
            $.ajax({
                url: '/accept-policy',
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                contentType: 'application/json',
                data: JSON.stringify({
                    id: encodedPolicyId,
                    responses: responses,
                    agreed: true
                }),
                success: function(res) {
                    if (res.success) {
                        Swal.fire({
                            title: "Success!",
                            text: "Policy accepted successfully.",
                            icon: "success"
                        }).then(() => location.reload());
                    } else {
                        Swal.fire({
                            title: "Error!",
                            text: res.message || "Something went wrong. Please try again.",
                            icon: "error"
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        title: "Error!",
                        text: "Something went wrong. Please try again.",
                        icon: "error"
                    });
                }
            });
        }
    </script>

    {{-- <script>
        document.addEventListener('DOMContentLoaded', function() {
            // const pdfUrl = "{{ env('CLOUDFRONT_URL') . $policy->policyData->policy_file }}";
            const pdfUrl = "https://mozilla.github.io/pdf.js/web/compressed.tracemonkey-pldi-09.pdf";

            const canvas = document.getElementById("pdfViewer-{{ $policy->id }}");
            const ctx = canvas.getContext("2d");

            console.log("PDF URL:", pdfUrl);
            console.log("Canvas Element:", canvas);
            console.log("Canvas Context:", ctx);

            const loadingTask = pdfjsLib.getDocument(pdfUrl);
            loadingTask.promise.then(function(pdf) {
                pdf.getPage(1).then(function(page) {
                    const viewport = page.getViewport({
                        scale: 1.5
                    });
                    const canvas = document.getElementById("pdfViewer-{{ $policy->id }}");
                    const context = canvas.getContext("2d");

                    canvas.height = viewport.height;
                    canvas.width = viewport.width;

                    const renderContext = {
                        canvasContext: context,
                        viewport: viewport
                    };
                    page.render(renderContext);
                });
            }).catch(function(error) {
                console.error("Error rendering PDF:", error);
            });
        });
    </script> --}}
</body>

</html>
