// ==================================================
// Project Name  :  Quizo
// File          :  JS Base
// Version       :  1.0.0
// Author        :  jthemes (https://themeforest.net/user/jthemes)
// ==================================================
$(function () {
    "use strict";


    // ================== CountDown function ================
    $('.countdown_timer').each(function () {
        $('[data-countdown]').each(function () {
            var $this = $(this),
                finalDate = $(this).data('countdown');
            $this.countdown(finalDate, function (event) {
                var $this = $(this).html(event.strftime('' +
                    '<div class="count_number">%S</div>'));
            });
        });
    });

});

let allQuestions = [];
let correctAnswered = 0;
let wrongAnswered = 0;
var currentTab = 0; // Current tab is set to be the first tab (0)
// showTab(currentTab); // Display the current tab
let countDownInterval = null;
let completedVideos = [];


function showTab(n) {

    var x = document.querySelectorAll("#trainingQContainers .multisteps_form_panel");

    x[n].style.display = "block";

    if (n == (x.length - 1)) {
        document.getElementById("nextBtn").innerHTML = "Submit";
        // document.getElementById("nextBtn").setAttribute("onclick", "showScore();");
    } else {

        document.getElementById("nextBtn").innerHTML = "Next Question" + ' <span><i class="fas fa-arrow-right"></i></span>';
    }
    // ... and run a function that displays the correct step indicator:
    // fixStepIndicator(n)
}

function showScore() {
    const scorecard = calculateScore(allQuestions, correctAnswered, wrongAnswered);

    const scoreHtml = `
        <div class="text-center p-4">
            <h2 class="text-primary mb-3">🎉 Your Score 🎉</h2>
            <div class="score-details bg-light p-3 rounded shadow-sm">
                <p class="mb-2"><strong>Total Questions:</strong> ${scorecard.ques}</p>
                <p class="mb-2 text-success"><strong>Correct Answers:</strong> ${scorecard.correct}</p>
                <p class="mb-2 text-danger"><strong>Wrong Answers:</strong> ${scorecard.incorrect}</p>
                <p class="mb-0 text-info"><strong>Score:</strong> ${scorecard.scoreInPercent}%</p>
            </div>
            <button class="btn btn-primary mt-4" onclick="location.reload()">Retry Quiz</button>
        </div>
    `;
    $("#showScoreModal .modal-body").html(scoreHtml);
    $("#showScoreModal").modal("show");


    if (scorecard.scoreInPercent > 0) {
        updateScoreInDb(scorecard.scoreInPercent);
    }
}

function showDescPopup(isCorrect, desc) {
    if (isCorrect) {
        console.log(desc);
        const correctDesc = `<div class="text-center">
                        <h3 class="text-success">Correct Answer!</h3>
                        <p>${desc}</p>
                    </div>`;
        $("#quizPopupModal .modal-body").html(correctDesc);


    } else {
        console.log(desc);
        const wrongDesc = `<div class="text-center">
                        <h3 class="text-danger">Wrong Answer!</h3>
                        <p>${desc}</p>
                    </div>`;
        $("#quizPopupModal .modal-body").html(wrongDesc);
    }

    $("#quizPopupModal").modal("show");
}

function nextPrev(n) {

    var x = document.querySelectorAll("#trainingQContainers .multisteps_form_panel");


    // Get all radio buttons inside the current tab
    var currentTabRadios = x[currentTab].querySelectorAll('input[type="radio"]');

    if (currentTabRadios.length > 0) {
        // Check if any radio button is checked
        var checkedRadio = Array.from(currentTabRadios).find(radio => radio.checked);

        if (!checkedRadio) {
            alert('Please select an option');
            return false;
        }

        if (allQuestions[currentTab].correctOption == checkedRadio.value) {
            correctAnswered++;
            showDescPopup(true, allQuestions[currentTab].ansDesc);
        } else {
            wrongAnswered++;
            showDescPopup(false, allQuestions[currentTab].ansDesc);
        }

        var selectedValue = checkedRadio.value;
        console.log('Selected value:', selectedValue);
    }

    const iframeInsidePage = x[currentTab].querySelector("video");

    if (iframeInsidePage !== null) {
         console.log("Current Tab:", currentTab);
         const exists = completedVideos.some(video => video.videoIndex === currentTab);
         if(!exists){
            alert("Please watch the complete video to proceed");
            return false;
         }

        // Stop video playback
        iframeInsidePage.pause();
        iframeInsidePage.currentTime = 0; // Reset to beginning

        // Remove the source to fully stop loading
        const source = iframeInsidePage.querySelector("source");
        if (source) {
            source.src = "";
        }

        // Reload the video to apply the changes
        iframeInsidePage.load();
    }

    x[currentTab].style.display = "none";
    // Increase or decrease the current tab by 1:
    currentTab = currentTab + n;
    // if you have reached the end of the form... :
    if (currentTab >= x.length) {

        showScore();
        move();
        return false;
    }
    // Otherwise, display the correct tab:
    showTab(currentTab);
    move();
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
    // if (scoreInPercentage > 0) {

    //     updateScoreInDb(scoreInPercentage);
    // }

    const scoreCard = {
        ques: no_of_q_asked,
        correct: corr,
        incorrect: incorr,
        scoreInPercent: scoreInPercentage
    }
    return scoreCard;
}

function validateForm() {
    // This function deals with validation of the form fields
    var x, y, i, valid = true;
    x = document.getElementsByClassName("multisteps_form_panel");
    y = x[currentTab].getElementsByTagName("input");
    // A loop that checks every input field in the current tab:
    for (i = 0; i < y.length; i++) {
        // If a field is empty...
        if (y[i].value == "") {
            // add an "invalid" class to the field:
            y[i].className += " invalid";
            // and set the current valid status to false:
            valid = false;
        }
    }
    // If the valid status is true, mark the step as finished and valid:
    if (valid) {
        document.getElementsByClassName("step")[currentTab].className += " finish";
    }
    return valid; // return the valid status
}

function fixStepIndicator(n) {
    // This function removes the "active" class of all steps...
    var i, x = document.getElementsByClassName("step");
    for (i = 0; i < x.length; i++) {
        x[i].className = x[i].className.replace(" active", "");
    }
    //... and adds the "active" class to the current step:
    x[n].className += " active";
}

function move() {
    // console.log(currentTab);
    // console.log(allQuestions.length);
    // console.log("move function called");
    if (currentTab === 1) {
        var dividedPercentage = Math.floor(100 / allQuestions.length);
        var elem = document.getElementById("myBar");
        // console.log(dividedPercentage);
        elem.style.width = '';
        elem.style.width = `${dividedPercentage}%`;
        elem.innerHTML = `${dividedPercentage}%`;
        elem.setAttribute("aria-valuenow", dividedPercentage);
    } else {
        var dividedPercentage = Math.floor(100 / allQuestions.length);
        var elem = document.getElementById("myBar");

        // Get current width safely (default to 20 if empty)
        var currentWidth = parseInt(elem.style.width) || 20;
        var newWidth = currentWidth + dividedPercentage;

        // Ensure newWidth doesn't exceed 100%
        if (currentTab === allQuestions.length) {
            newWidth = 100;
        }

        elem.style.width = `${newWidth}%`;
        elem.innerHTML = `${newWidth}%`;
        elem.setAttribute("aria-valuenow", newWidth);
    }

}