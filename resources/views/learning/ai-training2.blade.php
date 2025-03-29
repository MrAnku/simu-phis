@extends('layouts.training')

@section('questions')
    <div class="container">
        <div class="row">

            <div id="preloader" class="text-center">
                <img src="{{ asset('learner/assets/images/preloader.svg') }}" alt="preloader" width="200">
                <div class="d-flex justify-content-center align-items-center">
                    <h1 class="bg-white rounded-pill px-4 py-4" style="font-size: 21px; width: fit-content;">AI is generating
                        your questions...</h1>
                </div>

            </div>

            <div id="trainingQContainers">

            </div>

            <!---------- Form Button ---------->
            <div class="form_btn p-0 my-4">
                {{-- <button type="button" class="f_btn prev_btn text-white rounded-pill text-uppercase" id="prevBtn"
                        onclick="nextPrev(-1)"><span><i class="fas fa-arrow-left"></i></span> Last Question</button> --}}
                <button type="button" class="f_btn nextBtn text-white rounded-pill text-uppercase" id="nextBtn"
                    onclick="nextPrev(1)" style="font-size: 15px; display: none;">Next Question</button>
            </div>

        </div>
    </div>

    {{-- <!-- Button trigger modal -->
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#quizPopupModal">
        Launch demo modal
    </button> --}}

    <!-- Modal -->
    <div class="modal fade" id="quizPopupModal" tabindex="-1" aria-labelledby="quizPopupModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="quizPopupModalLabel"> Description</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="correctAnswerContent" style="display: none;">
                        <h3 class="text-success">Correct Answer!</h3>
                        <p>Well done! You have selected the correct answer.</p>
                    </div>
                    <div id="wrongAnswerContent" style="display: none;">
                        <h3 class="text-danger">Wrong Answer!</h3>
                        <p>Oops! That was not the correct answer. Try again!</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>

                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="showScoreModal" tabindex="-1" aria-labelledby="showScoreModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="quizPopupModalLabel"> Your Score</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>

                </div>
            </div>
        </div>
    </div>

    @push('newcss')
        <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.15.10/dist/sweetalert2.min.css" rel="stylesheet">
        <style>
            .multisteps_form_panel .label {
                font-size: 1.5rem;
                font-weight: 600;
                color: #333;
                margin-bottom: 1rem;
            }

            .progress-circle {
                position: relative;
                width: 200px;
                height: 200px;
            }

            .progress-circle svg {
                transform: rotate(-90deg);
            }

            .progress-circle circle {
                fill: none;
                stroke-width: 12;
                stroke-linecap: round;
            }

            .progress-circle .progress-bar {
                stroke: #1a4368;
                transition: stroke-dashoffset 1s linear;
                stroke-dasharray: 283;
                stroke-dashoffset: 283;
            }

            .progress-circle .background {
                stroke: white;
                stroke-width: 12;
                opacity: 1;
            }

            .countdown-text {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                font-size: 21px;
                font-weight: bold;
                color: black;
            }
        </style>
    @endpush

    @push('newjs')
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.15.10/dist/sweetalert2.all.min.js"></script>
        <script>
            function translateGenerateQuiz(quiz, lang) {
                doBeforeLoadingContent();

                $.post({
                    url: '/ai-training/translate-quiz',
                    data: {
                        _token: '{{ csrf_token() }}',
                        quiz: quiz,
                        lang: lang
                    },
                    success: function(res) {

                        if (res.status === 0) {
                            Swal.fire({
                                title: "Error!",
                                text: res.msg,
                                icon: "error",
                                confirmButtonText: "Ok"
                            });
                            // $("#preloader").hide();
                            return;
                        }

                        console.log(res);
                        // return;
                        var json_quiz = JSON.parse(res.quiz.replace(/\n/g, ''));;
                        createPages(json_quiz);
                        allQuestions = json_quiz;
                        showTab(0);

                        startCountdown(parseInt(6));
                        doAfterLoadingContent();

                    }
                })
            }

            let generatedQuiz = '';

            function loadTrainingContent(lang = '{{ $language }}') {
                doBeforeLoadingContent();

                $.get({
                    url: '/load-ai-training/{{ $topic }}',
                    success: function(res) {
                        console.log(res)
                        if (res.status === 0) {
                            Swal.fire({
                                title: "Error!",
                                text: res.msg,
                                icon: "error",
                                confirmButtonText: "Ok"
                            });
                            // $("#preloader").hide();
                            return;
                        }

                        generatedQuiz = res.quiz;
                        if (lang !== 'en') {
                            translateGenerateQuiz(res.quiz, lang);
                            return;
                        }
                        var json_quiz = res.quiz;
                        createPages(json_quiz);
                        allQuestions = json_quiz;
                        showTab(0);

                        startCountdown(parseInt(6));
                        doAfterLoadingContent();
                    }
                })
            }

            loadTrainingContent();
            $('#trainingLang').val('{{ $language }}');

            function doBeforeLoadingContent() {
                $("#preloader").show();
                $("#nextBtn").hide();
                $("#trainingQContainers").hide();
                stopCountdown();
                var elem = document.getElementById("myBar");
                elem.style.width = `20%`;
                elem.innerHTML = `0%`;
                elem.setAttribute("aria-valuenow", "20");
                allQuestions = [];
                correctAnswered = 0;
                wrongAnswered = 0;
                currentTab = 0;
                completedVideos = [];
            }

            function doAfterLoadingContent() {
                $("#preloader").hide();
                $("#nextBtn").show();
                $("#trainingQContainers").show();
            }

            function createPages(jsonData) {
                let trainingQContainers = document.getElementById("trainingQContainers");
                trainingQContainers.innerHTML = '';
                let pageData = '';
                jsonData.forEach((obj, index) => {
                    if (obj.qtype === "multipleChoice") {
                        pageData += createMultipleChoicePage(obj, index);
                    }
                    if (obj.qtype === "trueFalse") {
                        pageData += createTrueFalsePage(obj, index);
                    }
                    if (obj.qtype === "statement") {
                        pageData += createStatementPage(obj);
                    }
                })

                trainingQContainers.innerHTML = pageData;
                // makeMultiStep();
                $(document).on("click", ".options", function() {
                    $(".options").removeClass("active"); // Remove active class from all
                    $(this).addClass("active"); // Add active class to the clicked one
                });

                addVideoEventListeners();
            }

            function convertToEmbedURL(watchURL) {
                // Replace /watch?v= with /embed/
                if (watchURL.includes('/watch?v=')) {

                    return watchURL.replace('/watch?v=', '/embed/');
                } else {
                    return watchURL;
                }
            }

            function createMultipleChoicePage(obj, index) {

                return `<div class="col-lg-7">
                        <div class="multisteps_form_panel">
                            <div class="question_title pb-4">
                                <h1 class="bg-white rounded-pill position-relative" style="font-size: 21px; width: fit-content;">${obj.question}
                                </h1>
                                <!-- Step-Progress-bar area -->
                                <!-- <div class="step_progress_bar position-absolute">
                                    <div class="step position-relative" style="background-color: #f8d5b3;"></div>
                                    <div class="step position-relative" style="background-color: #ffe090;"></div>
                                    <div class="step position-relative" style="background-color: #f2c7db;"></div>
                                    <div class="step position-relative" style="background-color: #bdcaff;"></div>
                                </div> -->
                            </div>
                            <div class="form_items" style="margin: 0px;">
                                <ul class="ms-5 p-0 list-unstyled">
                                    <li>
                                        <label for="opt_${index}_1"
                                            class="options rounded-pill position-relative animate__animated animate__fadeInRight animate_50ms" style="font-size: 18px;">
                                            <input type="radio" id="opt_${index}_1" name="stp_${index}_select_option"
                                                value="option1">
                                            <span class="text-white">A</span>
                                            ${obj.option1}
                                            <span class="pinkLady"></span>
                                        </label>
                                    </li>
                                    <li>
                                        <label for="opt_${index}_2"
                                            class="options rounded-pill position-relative animate__animated animate__fadeInRight animate_100ms" style="font-size: 18px;">
                                            <input type="radio" id="opt_${index}_2" name="stp_${index}_select_option"
                                                value="option2">
                                            <span class="text-white">B</span>
                                            ${obj.option2}
                                            <span class="salomie"></span>
                                        </label>
                                    </li>
                                    <li>
                                        <label for="opt_${index}_3"
                                            class="options rounded-pill position-relative animate__animated animate__fadeInRight animate_150ms" style="font-size: 18px;">
                                            <input type="radio" id="opt_${index}_3" name="stp_${index}_select_option"
                                                value="option3">
                                            <span class="text-white">C</span>
                                            ${obj.option3}
                                            <span class="wePeep"></span>
                                        </label>
                                    </li>
                                    <li>
                                        <label for="opt_${index}_4"
                                            class="options rounded-pill position-relative animate__animated animate__fadeInRight animate_200ms" style="font-size: 18px;">
                                            <input type="radio" id="opt_${index}_4" name="stp_1_select_option"
                                                value="option4">
                                            <span class="text-white">D</span>
                                            ${obj.option4}
                                            <span class="periwinkle"></span>
                                        </label>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>`;

            }

            function createTrueFalsePage(obj, index) {

                return `<div class="col-lg-7">
                        <div class="multisteps_form_panel">
                            <div class="question_title pb-4">
                                <h1 class="bg-white rounded-pill position-relative" style="font-size: 21px; width: fit-content;">${obj.question}
                                </h1>
                                <!-- Step-Progress-bar area -->
                               <!-- <div class="step_progress_bar position-absolute">
                                    <div class="step position-relative" style="background-color: #f8d5b3;"></div>
                                    <div class="step position-relative" style="background-color: #ffe090;"></div>
                                    
                                </div> -->
                            </div>
                            <div class="form_items" style="margin: 0px;">
                                <ul class="ms-5 p-0 list-unstyled">
                                    <li>
                                        <label for="opt_${index}_true"
                                            class="options rounded-pill position-relative animate__animated animate__fadeInRight animate_50ms" style="font-size: 18px;">
                                            <input type="radio" id="opt_${index}_true" name="stp_${index}_select_option"
                                                value="true">
                                            <span class="text-white">A</span>
                                            True
                                            <span class="pinkLady"></span>
                                        </label>
                                    </li>
                                    <li>
                                        <label for="opt_${index}_false"
                                            class="options rounded-pill position-relative animate__animated animate__fadeInRight animate_100ms" style="font-size: 18px;">
                                            <input type="radio" id="opt_${index}_false" name="stp_${index}_select_option"
                                                value="false">
                                            <span class="text-white">B</span>
                                            False
                                            <span class="salomie"></span>
                                        </label>
                                    </li>
                                    
                                </ul>
                            </div>
                        </div>
                    </div>`;

            }

            function addVideoEventListeners() {
                let videos = document.querySelectorAll(".video-iframe"); // Assuming .video-iframe is a <video> tag
                console.log("videos:", videos);

                if (videos.length === 0) {
                    return;
                }

                // Replace video elements to remove old event listeners
                videos.forEach((video, index) => {
                    const newVideo = video.cloneNode(true);
                    video.replaceWith(newVideo);
                });

                // Re-select the videos after replacing them
                videos = document.querySelectorAll(".video-iframe");

                videos.forEach((video, index) => {
                    console.log(`Adding event listeners to video ${index}`);

                    // Hide the seek bar using CSS (ensures user cannot see the timeline)
                    video.setAttribute("controls", ""); // Enable controls but hide seek bar
                    video.style.cssText = `
            position: relative;
            width: 100%;
            height: auto;
        `;

                    // Prevent seeking
                    video.addEventListener("timeupdate", function() {
                        if (video.currentTime > (video.lastTime || 0) + 1) {
                            video.currentTime = video.lastTime || 0; // Prevent skipping
                        }
                        video.lastTime = video.currentTime;
                    });

                    // Disable spacebar & arrow key seeking
                    function preventSeek(e) {
                        if (["ArrowRight", "ArrowLeft", "Space"].includes(e.code)) {
                            e.preventDefault();
                        }
                    }
                    window.removeEventListener("keydown", preventSeek);
                    window.addEventListener("keydown", preventSeek);

                    // Notify parent when video ends
                    video.addEventListener("ended", function() {
                        // window.parent.postMessage({
                        //     videoIndex: index,
                        //     status: "completed"
                        // }, "*");
                        completedVideos.push({
                            videoIndex: index,
                            status: "completed"
                        });
                        console.log(completedVideos);
                    });
                });

                // // Message event listener to track video completion
                // function handleMsgListener(event) {
                //     // if (event.data.status === "completed") {
                //     //     document.getElementById("nextButton").disabled = false;
                //     // }
                //     console.log("video completed:", event.data);
                // }

                // // Remove previous message listener before adding a new one
                // window.removeEventListener("message", handleMsgListener);
                // window.addEventListener("message", handleMsgListener);
            }

            function createStatementPage(obj) {
                return `
                <div class="col-lg-7">
                        <div class="multisteps_form_panel">
                            <div class="question_title pb-4">
                                <h1 class="bg-white rounded-pill position-relative" style="font-size: 21px; width: fit-content;">${obj.sTitle}
                                </h1>
                                <p class="text-white mt-3">${obj.sContent}</p>
                                
                            </div>
                            <div class="form_items" style="margin: 0px;">
                                <video class="video-iframe w-100" style="height: 350px;" controls oncontextmenu="return false" disablePictureInPicture controlsList="nodownload nofullscreen noremoteplayback">
                                    <source src="${convertToEmbedURL(obj.videoUrl)}" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                                
                            </div>

                        </div>
                    </div>`;
            }

            function startCountdown(minutes) {
                let totalTime = minutes * 60;
                let countdownText = document.getElementById("countdown");
                countDownInterval = setInterval(() => {
                    let minutesLeft = Math.floor(totalTime / 60);
                    let secondsLeft = totalTime % 60;
                    countdownText.innerHTML = `${minutesLeft}:${secondsLeft < 10 ? '0' + secondsLeft : secondsLeft}`;

                    if (totalTime <= 0) {
                        clearInterval(countDownInterval);
                    }
                    totalTime--;
                }, 1000);
            }

            function stopCountdown() {
                if (countDownInterval) {
                    clearInterval(countDownInterval);
                    $("#countdown").html("--");
                    console.log("Countdown stopped");
                }

            }

            function updateScoreInDb(percent) {
                console.log("updateScoreInDb", updateScoreInDb);
                $.post({
                    url: '/update-training-score',
                    data: {
                        _token: '{{ csrf_token() }}',
                        trainingScore: percent,
                        id: '{{ $id }}'
                    },
                    success: function(res) {
                        console.log("update score", res);
                    }
                });
            }

            $('#showScoreModal').on('hidden.bs.modal', function() {
                window.location.href = window.location.href;
            });
        </script>
        <script>
            function confirmLanguage(lang, langCode) {
                Swal.fire({
                    title: "Are you sure?",
                    text: `This training will be changed to ${lang} language!`,
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Yes, Change Language!"
                }).then((result) => {
                    if (result.isConfirmed) {
                        if (langCode !== 'en') {

                            translateGenerateQuiz(generatedQuiz, langCode);
                        } else {
                            createPages(generatedQuiz);
                            allQuestions = generatedQuiz;
                            showTab(0);

                            startCountdown(parseInt(6));
                            doAfterLoadingContent();
                        }
                    }
                });
            }


            $(document).ready(function() {
                $('#trainingLang').change(function() {

                    const lang = $(this).val();
                    const optionText = $(this).find('option:selected').text();
                    confirmLanguage(optionText, lang);
                    console.log(lang);


                });
            });
        </script>
    @endpush
@endsection
