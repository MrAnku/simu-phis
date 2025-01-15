<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gamified Training Preview</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        .video-container,
        .quiz-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            box-sizing: border-box;
        }

        .video-container {
            flex-basis: 60%;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            position: relative;
        }

        video {
            max-width: 90%;
            max-height: 90%;
            border: 5px solid rgba(255, 255, 255, 0.7);
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.3);
        }

        .quiz-container {
            background: #f4f4f4;
            flex-basis: 40%;
            flex-direction: column;
            border-left: 5px solid #ddd;
        }

        .question {
            font-size: 1.4em;
            margin-bottom: 20px;
            color: #333;
            text-align: center;
        }

        .options button {
            display: block;
            margin: 10px auto;
            padding: 12px 20px;
            font-size: 1em;
            cursor: pointer;
            border: none;
            border-radius: 5px;
            background: #007bff;
            color: #fff;
            width: 90%;
            text-align: center;
            transition: all 0.3s ease;
        }

        .options button:hover {
            background: #0056b3;
            transform: scale(1.05);
        }

        /* Modal styling */
        .modal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 300px;
            padding: 20px;
            background: #fff;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.3);
            border-radius: 8px;
            text-align: center;
            z-index: 1000;
        }

        .modal.correct {
            border: 2px solid #28a745;
        }

        .modal.incorrect {
            border: 2px solid #dc3545;
        }

        .modal button {
            margin-top: 10px;
            padding: 8px 16px;
            font-size: 1em;
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .modal .btn {
            margin-top: 10px;
            padding: 8px 16px;
            font-size: 1em;
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .modal button:hover {
            background: #0056b3;
        }

        /* Overlay */
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }
    </style>
</head>

<body>
    <div class="video-container">
        <video id="videoPlayer" controls>
            <source src="" type="video/mp4">
            Your browser does not support the video tag.
        </video>
    </div>
    <div class="quiz-container" id="quizBox" style="display: none;">
        <p class="question"></p>
        <div class="options"></div>
    </div>

    <!-- Modal -->
    <div class="overlay" id="overlay"></div>
    <div class="modal" id="modal">
        <p id="modalMessage"></p>
        <button id="closeModal">OK</button>
    </div>

    <!-- Final Score Modal -->
    <div class="modal" id="scoreModal">
        <h2>Quiz Results</h2>
        <p id="scoreSummary"></p>
        <button id="restartQuiz">Restart</button>
        <a href="{{ route('admin.trainingmodule.index') }}" class="btn">Back</a>
    </div>

    <script>
        

const quesString = @json($training->json_quiz);
        const parsedQ = JSON.parse(quesString);
        const responseVideo = parsedQ.videoUrl;
        document.querySelector("#videoPlayer source").setAttribute("src", responseVideo);
        document.getElementById("videoPlayer").load();
        const questions = parsedQ.questions;

        console.log(questions);
        
       

        const video = document.getElementById("videoPlayer");
        const quizBox = document.getElementById("quizBox");
        const questionElem = quizBox.querySelector(".question");
        const optionsElem = quizBox.querySelector(".options");
        const modal = document.getElementById("modal");
        const modalMessage = document.getElementById("modalMessage");
        const overlay = document.getElementById("overlay");
        const closeModal = document.getElementById("closeModal");
        const scoreModal = document.getElementById("scoreModal");
        const scoreSummary = document.getElementById("scoreSummary");
        const restartQuiz = document.getElementById("restartQuiz");
        let currentQuestionIndex = 0;
        let correctAnswers = 0;
        let totalAnswered = 0;

        video.addEventListener("timeupdate", () => {
            if (
                currentQuestionIndex < questions.length &&
                video.currentTime >= questions[currentQuestionIndex].time
            ) {
                video.pause();
                loadQuestion(questions[currentQuestionIndex]);
            }
        });

        function loadQuestion(question) {
            quizBox.style.display = "flex";
            questionElem.textContent = question.question;
            optionsElem.innerHTML = "";

            question.options.forEach((option, index) => {
                const button = document.createElement("button");
                button.textContent = option;
                button.onclick = () => answerQuestion(index, question.answer);
                optionsElem.appendChild(button);
            });
        }

        function answerQuestion(selectedIndex, correctAnswer) {
            totalAnswered++;
            if (selectedIndex === correctAnswer) {
                correctAnswers++;
                showPopup("Correct! Well done!", "correct");
            } else {
                showPopup("Incorrect. Try to do better next time!", "incorrect");
            }

            currentQuestionIndex++;

            if (currentQuestionIndex < questions.length) {
                quizBox.style.display = "none";
            } else {
                displayScoreSummary();
            }
        }

        function showPopup(message, type) {
            modalMessage.textContent = message;
            modal.className = `modal ${type}`;
            modal.style.display = "block";
            overlay.style.display = "block";
        }

        closeModal.addEventListener("click", () => {
            modal.style.display = "none";
            overlay.style.display = "none";

            if (currentQuestionIndex < questions.length) {
                video.play();
            }
        });

        function displayScoreSummary() {
            const trainingScore = Math.round((correctAnswers / questions.length) * 100);

            const path = window.location.pathname;
            const segments = path.split('/');
            const lastSegment = segments.pop() || segments.pop();

            const trainingid = lastSegment;

            scoreSummary.innerHTML = `
                Total Questions: ${questions.length}<br>
                Total Answered: ${totalAnswered}<br>
                Correct Answers: ${correctAnswers}<br>
                Wrong Answers: ${totalAnswered - correctAnswers}<br>
                Score: ${trainingScore}%
            `;
            scoreModal.style.display = "block";
            overlay.style.display = "block";

        }

        restartQuiz.addEventListener("click", () => {
            currentQuestionIndex = 0;
            correctAnswers = 0;
            totalAnswered = 0;
            scoreModal.style.display = "none";
            overlay.style.display = "none";
            video.currentTime = 0;
            video.play();
        });
    </script>
</body>

</html>
