<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>
        Learning | {{ $companyName }}
    </title>

    <!-- CSS files -->
    <link href="/dist/css/tabler.min.css?1685973381" rel="stylesheet" />
    <link href="/dist/css/tabler-flags.min.css?1685973381" rel="stylesheet" />
    <link href="/dist/css/tabler-payments.min.css?1685973381" rel="stylesheet" />
    <link href="/dist/css/tabler-vendors.min.css?1685973381" rel="stylesheet" />
    <link href="/dist/css/demo.min.css?1685973381" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.15.10/dist/sweetalert2.min.css" rel="stylesheet">

    <link rel="icon" href="{{ $companyFavicon }}" type="image/x-icon">
    <style>
        @import url("https://rsms.me/inter/inter.css");

        :root {
            --tblr-font-sans-serif: "Inter Var", -apple-system, BlinkMacSystemFont,
                San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif;
        }

        body {
            font-feature-settings: "cv03", "cv04", "cv11";
        }

        .radio-options {
            border: 1px solid #aeaeae;
            padding: 20px;
            margin: 20px 0px;
            border-radius: 55px;

        }

        .radio-options label {
            margin-bottom: 0px;
        }

        .video-container {
            position: relative;
            width: 100%;
            /* Set the width of the container to 100% */
            padding-bottom: 56.25%;
            /* Maintain a 16:9 aspect ratio (height = width * 9/16) */
            overflow: hidden;
        }

        .video-iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            /* Make the iframe span the full width of the container */
            height: 100%;
            /* Make the iframe span the full height of the container */
            border: 0;
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
                    <a href="{{ route('learner.dashboard') }}">
                        <img src="{{ $companyLogoDark }}" alt="" class="navbar-brand-image"
                            style="width: 181px; height: auto;" />
                    </a>
                </h1>
            </div>
        </header>
        <div class="page-wrapper">
            <!-- Page header -->
            <div class="page-header d-print-none">
                <div class="container-xl">
                    <div class="row g-2 align-items-center">
                        <div class="col">
                            <!-- Page pre-title -->
                            <div class="d-flex justify-content-between align-items-center">
                                <h2 class="page-title">
                                    Learner Dashboard: {{ session('learner')->login_username }} </h2>
                                <div>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            Language
                                        </span>
                                        <x-language-select id="trainingLang" />
                                    </div>

                                </div>
                            </div>

                        </div>
                        <!-- Page title actions -->
                        <div class="col-auto ms-auto d-print-none">
                            <div class="btn-list">
                                <span class="d-none d-sm-inline">
                                    <a href="{{ route('learner.dashboard') }}"
                                        class="btn btn-outline-primary my-3 active">Back</a>
                                    <a href="{{ route('learner.logout') }}" class="btn"> Log Out </a>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Page body -->
            <div class="page-body">
                <div class="container-xl">
                    <div id="preloader" style="text-align: center;">

                        <img src="/images/file.gif" alt="" srcset="" width="200">
                        <h3>AI is generating your questions...</h3>
                    </div>
                    <div class="row row-deck row-cards">

                        <div class="col-12">
                            <div class="card align-items-center" id="trainingQContainers">

                            </div>

                        </div>
                    </div>
                    <div class="btns d-flex justify-content-center" id="nextBtnContainer">

                        <button type="button" id="nextButton" class="btn btn-outline-primary my-3 active">Next</button>
                        <a href="{{ route('learner.dashboard') }}" id="dashboardBtn"
                            class="btn btn-outline-primary my-3" style="display: none;">Dashboard</a>
                    </div>
                </div>
            </div>
            <footer class="footer footer-transparent d-print-none">
                <div class="container-xl">
                    <div class="row text-center align-items-center justify-content-center">
                        <div class="col-12 col-lg-auto mt-3 mt-lg-0">
                            <ul class="list-inline list-inline-dots mb-0">
                                <li class="list-inline-item">
                                    Copyright &copy; {{ date('Y') }}
                                    <a href="." class="link-secondary">{{ $companyName }}</a>. All rights
                                    reserved.
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <div class="modal modal-blur fade" id="corrAnsDescModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header" style="border: none;">
                    <!-- <h5 class="modal-title">Modal title</h5> -->
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="iconbox d-flex justify-content-center align-items-center">
                        <img src="/dist/corr_ans_icon.png" alt="" srcset="" style="width: 65px;">
                        <h4 class="text-center">Correct Answer</h4>
                    </div>
                    <div id="corrAnsDesc">
                        <p class="text-center"></p>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="wrongAnsDescModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header" style="border: none;">
                    <!-- <h5 class="modal-title">Modal title</h5> -->
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="iconbox d-flex justify-content-center align-items-center">
                        <img src="/dist/wrong_ans_icon.png" alt="" srcset=""
                            style="width: 47px; margin-right: 19px;">
                        <h4 class="text-center">Wrong Answer</h4>
                    </div>
                    <div id="wrongAnsDesc" class="mt-3">
                        <p class="text-center"></p>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Tabler Core -->
    <script src="/dist/js/tabler.min.js?1685973381" defer></script>
    <script src="https://code.jquery.com/jquery-3.6.1.min.js"
        integrity="sha256-o88AwQnZB+VDvE9tvIXrMQaPlFFSUTR+nldQm1LuPXQ=" crossorigin="anonymous"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.15.10/dist/sweetalert2.all.min.js"></script>
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    </script>



    <script>
        let allQuestions = [];
        let correctAnswered = 0;
        let wrongAnswered = 0;

        function convertToEmbedURL(watchURL) {
            // Replace /watch?v= with /embed/
            if (watchURL.includes('/watch?v=')) {

                return watchURL.replace('/watch?v=', '/embed/');
            } else {
                return watchURL;
            }
        }

        function createMultipleChoicePage(obj) {

            // console.log(obj)
            let optionsHTML = '';
            for (const key in obj) {
                if (key !== 'question' && key !== 'qtype' && key !== 'correctOption' && key !== 'ansDesc') {
                    //optionsHTML += generateRadioOption(key, obj[key]);
                    optionsHTML += `<div class="radio-options">
                                                    <label class="form-check">
                                                        <input class="form-check-input" type="radio" name="answer" value="${key}">
                                                        <span class="form-check-label">${obj[key]}</span>
                                                    </label>
                                                </div>`;
                }
            }

            return `
            <div class="p-3 pageq" style="width: 60%;">
                                    <h3 class="h1 text-center my-4">${obj.question}</h3>
                                    <form action="" method="post">
                                        <div class="mb-3">
                                            <div>

                                                ${optionsHTML}
                                            </div>
                                        </div>
                                    </form>
                                </div>
            `;

        }

        function createTrueFalsePage(obj) {

            return `
            <div class="p-3 pageq" style="width: 60%;">
                                    <h3 class="h1 text-center my-4">${obj.question}</h3>
                                    <form action="" method="post">
                                        <div class="mb-3">
                                            <div>

                                                <div class="radio-options">
                                                    <label class="form-check">
                                                        <input class="form-check-input" type="radio" name="answer" value="true">
                                                        <span class="form-check-label">True</span>
                                                    </label>
                                                </div>

                                                <div class="radio-options">
                                                    <label class="form-check">
                                                        <input class="form-check-input" type="radio" name="answer" value="false">
                                                        <span class="form-check-label">False</span>
                                                    </label>
                                                </div>


                                            </div>
                                        </div>
                                    </form>
                                </div>
            `;

        }

        function createStatementPage(obj) {
            return `
            <div class="p-3 pageq" style="width: 60%;">
                                    <h3 class="h1 text-center my-4">${obj.sTitle}</h3>
                                    <p class="text-center">${obj.sContent}</p>
                                    <div class="video-container">
                                        <iframe class="video-iframe" src="${convertToEmbedURL(obj.videoUrl)}" frameborder="0" allowfullscreen></iframe>
                                    </div>

                                </div>
            `;
        }

        function updateScoreInDb(percent) {
            $.post({
                url: '/update-training-score',
                data: {
                    trainingScore: percent,
                    id: '{{ $id }}'
                },
                success: function(res) {
                    // console.log(res)
                }
            })
        }

        function calculateScore(ques, corr, incorr) {
            let no_of_q_asked = 0;
            ques.forEach(obj => {
                if (obj.qtype === 'multipleChoice' || obj.qtype === 'trueFalse') {
                    no_of_q_asked++;
                }

            })

            // console.log(`total questions asked: ${no_of_q_asked}`);
            // console.log(`correct Answered: ${corr}`);
            // console.log(`Incorrect Answered: ${incorr}`);

            const scoreInPercentage = Math.floor((corr / no_of_q_asked) * 100);

            // alert(`Percentage of correct answers: ${scoreInPercentage}%`);
            if (scoreInPercentage > 0) {

                updateScoreInDb(scoreInPercentage);
            }

            const scoreCard = {
                ques: no_of_q_asked,
                correct: corr,
                incorrect: incorr,
                scoreInPercent: scoreInPercentage
            }
            return scoreCard;
        }

        function makeMultiStep() {
            // Get all page div containers and the next button
            const pages = document.querySelectorAll('.pageq');
            const nextButton = document.getElementById('nextButton');
            let currentPageIndex = 0;

            // Initially hide all pages except the first one
            for (let i = 1; i < pages.length; i++) {
                pages[i].style.display = 'none';
            }

            // Add event listener to the next button
            nextButton.addEventListener('click', function() {
                // Hide the current page
                pages[currentPageIndex].style.display = 'none';

                //checking page has form element
                const formInsidePage = pages[currentPageIndex].querySelector('form');
                if (formInsidePage !== null) {
                    //console.log("this page has form");
                    const selectedAns = formInsidePage.querySelector('input[name="answer"]:checked');

                    if (selectedAns.value !== null) {
                        // console.log(selectedAns.value);
                        if (allQuestions[currentPageIndex].correctOption == selectedAns.value) {

                            correctAnswered++;
                            $("#corrAnsDesc p").text(allQuestions[currentPageIndex].ansDesc);
                            $("#corrAnsDescModal").modal('toggle');

                        } else {
                            wrongAnswered++;
                            $("#wrongAnsDesc p").text(allQuestions[currentPageIndex].ansDesc);
                            $("#wrongAnsDescModal").modal('toggle');
                        }
                    }

                }

                const iframeInsidePage = pages[currentPageIndex].querySelector('iframe');
                if (iframeInsidePage !== null) {
                    // var iframeContentWindow = iframeInsidePage.contentWindow;

                    // // Access the video element inside the iframe
                    // var videoElement = iframeContentWindow.document.querySelector('video');

                    // // Check if video element exists and is playable
                    // if (videoElement && !videoElement.paused) {
                    //     // Pause the video
                    //     videoElement.pause();
                    // }

                    iframeInsidePage.src = '';
                }

                // If we've reached the end, loop back to the first page
                if (currentPageIndex === pages.length - 1) {
                    //currentPageIndex = 0;
                    const dashboardBtn = document.getElementById("dashboardBtn");
                    dashboardBtn.style.display = 'block';
                    nextButton.style.display = 'none';

                    const scorecard = calculateScore(allQuestions, correctAnswered, wrongAnswered);
                    // console.log(scorecard);
                    let scoreTable = `
                    <h3 class="text-center">Score Card</h3>
                    <div class="table-responsive" style="
    width: fit-content;
    margin: auto;
">
                    <table class="table table-vcenter card-table" style="
    width: fit-content;
">
                     
                      <tbody>
                        <tr>
                          <td>Questions Asked</td>
                          <td class="text-secondary">${scorecard.ques}</td>
                        </tr>
                        <tr>
                          <td class="text-success">Correct Answers</td>
                          <td class="text-success">${scorecard.correct}</td>
                        </tr>
                        <tr>
                          <td class="text-danger">Incorrect Answers</td>
                          <td class="text-danger">${scorecard.incorrect}</td>
                        </tr>
                        <tr>
                          <td>Score in Percentage</td>
                          <td class="text-secondary">${scorecard.scoreInPercent}%</td>
                        </tr>
                       
                      </tbody>
                    </table>
                  </div>`;

                    pages[currentPageIndex].innerHTML = scoreTable;
                } else {
                    nextButton.style.display = 'block';
                    // Increment the current page index
                    currentPageIndex++;


                }

                // Show the next page
                pages[currentPageIndex].style.display = 'block';



                // Hide the next button if this is the last page
                // if (currentPageIndex === pages.length - 1) {
                //     nextButton.style.display = 'none';

                // } else {
                //     nextButton.style.display = 'block';
                // }

            });
        }

        function createPages(jsonData) {
            let trainingQContainers = document.getElementById("trainingQContainers");
            trainingQContainers.innerHTML = '';
            let pageData = '';
            jsonData.forEach(obj => {
                if (obj.qtype === "multipleChoice") {
                    pageData += createMultipleChoicePage(obj);
                }
                if (obj.qtype === "trueFalse") {
                    pageData += createTrueFalsePage(obj);
                }
                if (obj.qtype === "statement") {
                    pageData += createStatementPage(obj);
                }
            })

            trainingQContainers.innerHTML = pageData;
            makeMultiStep();

        }

        function translateGenerateQuiz(quiz, lang) {

            $("#preloader").show();
            $("#trainingQContainers").hide();
            $("#nextBtnContainer").removeClass('d-flex').hide();

            $.post({
                url: '/ai-training/translate-quiz',
                data: {
                    quiz: quiz,
                    lang: lang
                },
                success: function(res) {

                    if (res.status === 0) {
                        Swal.fire({
                            title: "{{ __('Error!') }}",
                            text: res.msg,
                            icon: "error",
                            confirmButtonText: "{{ __('Ok') }}"
                        });
                        $("#preloader").hide();
                        return;
                    }

                    console.log(res);
                    // return;
                    var json_quiz = JSON.parse(res.quiz.replace(/\n/g, ''));;
                    createPages(json_quiz);
                    allQuestions = json_quiz;
                    $("#preloader").hide();
                    $("#trainingQContainers").show();
                    $("#nextBtnContainer").addClass('d-flex').show();
                }
            })
        }

        let generatedQuiz = '';

        function loadTrainingContent() {

            $("#preloader").show();
            $("#trainingQContainers").hide();
            $("#nextBtnContainer").removeClass('d-flex').hide();

            $.get({
                url: '/load-ai-training/{{ $topic }}',
                success: function(res) {
                    console.log(res);
                    if (res.status === 0) {
                        Swal.fire({
                            title: "{{ __('Error!') }}",
                            text: res.msg,
                            icon: "error",
                            confirmButtonText: "{{ __('Ok') }}"
                        });
                        $("#preloader").hide();
                        return;
                    }
                    
                    generatedQuiz = res.quiz;
                    const lang = '{{ $language }}';

                    if (lang !== 'en') {
                        translateGenerateQuiz(res.quiz, lang);
                        return;
                    }

                    var json_quiz = res.quiz;
                    createPages(json_quiz);
                    allQuestions = json_quiz;
                    $("#preloader").hide();
                    $("#trainingQContainers").show();
                    $("#nextBtnContainer").addClass('d-flex').show();

                }
            })
        }

        loadTrainingContent();
        $('#trainingLang').val('{{ $language }}');
    </script>

    <script>
        function confirmLanguage(lang, langCode) {
            Swal.fire({
                title: "Are you sure?",
                text: `{{ __('This training will be changed to :lang language!', ['lang' => '${lang}']) }}`,
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "{{ __('Yes, Change Language!') }}"
            }).then((result) => {
                if (result.isConfirmed) {
                    if (langCode !== 'en') {

                        translateGenerateQuiz(generatedQuiz, langCode);
                    } else {
                        createPages(generatedQuiz);
                        allQuestions = generatedQuiz;
                        $("#preloader").hide();
                        $("#trainingQContainers").show();
                        $("#nextBtnContainer").addClass('d-flex').show();
                    }
                }
            });
        }


        $(document).ready(function() {
            $('#trainingLang').change(function() {

                const lang = $(this).val();
                const optionText = $(this).find('option:selected').text();
                confirmLanguage(optionText, lang);
                // console.log(lang);

            });
        });
    </script>
</body>

</html>
