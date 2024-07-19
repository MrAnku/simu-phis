@extends('layouts.app')

@section('title', 'Training Modules - Phishing awareness training program')

@section('main-content')

    <div class="main-content app-content">
        <div class="container-fluid mt-4">


            <div class="d-flex justify-content-between">
                <div>
                    <button type="button" class="btn btn-primary mb-3" onclick="addNewTraining()" data-bs-toggle="modal"
                        data-bs-target="#newTrainingModuleModal">New Training Module</button>
                </div>
            </div>


            <div class="row">
                <div class="col-xl-12">
                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">
                                Manage Training Modules
                            </div>
                        </div>
                        <div class="card-body">

                            <div class="row">
                                @forelse ($trainingModules as $trainingModule)
                                    <div class="col-lg-6">
                                        <div class="card custom-card">
                                            <div class="card-header">
                                                <div class="d-flex align-items-center w-100">

                                                    <div class="">
                                                        <div class="fs-15 fw-semibold">{{ $trainingModule->name }}</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card-body htmlPhishingGrid">
                                                <img class="trainingCoverImg"
                                                    src="{{ Storage::url('uploads/trainingModule/' . $trainingModule->cover_image) }}" />
                                            </div>
                                            <div class="card-footer">
                                                <div class="d-flex justify-content-center">
                                                    <a href="{{route('trainingmodule.preview', base64_encode($trainingModule->id))}}" target="_blank"
                                                        class="btn mx-1 btn-outline-primary btn-wave waves-effect waves-light">View</a>

                                                    @if ($trainingModule->company_id !== 'default')
                                                        <button type="button"
                                                            onclick="deleteTrainingModule(`{{ $trainingModule->id }}`, `{{ $trainingModule->cover_image }}`)"
                                                            class="btn mx-1 btn-outline-danger btn-wave waves-effect waves-light">Delete</button>

                                                        <button type="button"
                                                            onclick="editTrainingModule(`{{ $trainingModule->id }}`)"
                                                            class="btn mx-1 btn-outline-primary btn-wave waves-effect waves-light"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#editTrainingModuleModal">Edit</button>
                                                    @endif



                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-lg-6">
                                        No records found
                                    </div>
                                @endforelse




                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- -------------------Modals------------------------ --}}

    <!-- new training add -->
    <div class="modal fade" id="newTrainingModuleModal" tabindex="-1" aria-labelledby="exampleModalLgLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Add Training</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('trainingmodule.add') }}" id="newModuleForm" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="input-group mb-3">
                                    <span class="input-group-text">Module name</span>
                                    <input type="text" class="form-control" name="moduleName"
                                        placeholder="Enter a unique module name" aria-label="Enter a unique module name"
                                        aria-describedby="basic-addon1" data-name="Module name" required>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="input-group mb-3">
                                    <span class="input-group-text">Passing Score</span>
                                    <input type="number" class="form-control" id="mPassingScore" name="mPassingScore"
                                        min="0" max="100" placeholder="70" aria-label="70"
                                        aria-describedby="basic-addon1" data-name="Passing Score" required>
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            {{-- <div class="col-lg-4">
                                <div class="input-group mb-3">
                                    <span class="input-group-text">Module Language</span>
                                    <select class="form-select" name="mModuleLang" id="inputGroupSelect01">
                                        <option value="en">English</option>
                                        <option value="af">Afrikaans</option>
                                        <option value="sq">Albanian</option>
                                        <option value="am">Amharic</option>
                                        <option value="ar">Arabic</option>
                                        <option value="hy">Armenian</option>
                                        <option value="az">Azerbaijani</option>
                                        <option value="bn">Bengali</option>
                                        <option value="bs">Bosnian</option>
                                        <option value="bg">Bulgarian</option>
                                        <option value="ca">Catalan</option>
                                        <option value="zh">Chinese (Simplified)</option>
                                        <option value="zh-TW">Chinese (Traditional)</option>
                                        <option value="hr">Croatian</option>
                                        <option value="cs">Czech</option>
                                        <option value="da">Danish</option>
                                        <option value="fa-AF">Dari</option>
                                        <option value="nl">Dutch</option>
                                        <option value="et">Estonian</option>
                                        <option value="fa">Farsi (Persian)</option>
                                        <option value="tl">Filipino, Tagalog</option>
                                        <option value="fi">Finnish</option>
                                        <option value="fr">French</option>
                                        <option value="fr-CA">French (Canada)</option>
                                        <option value="ka">Georgian</option>
                                        <option value="de">German</option>
                                        <option value="el">Greek</option>
                                        <option value="gu">Gujarati</option>
                                        <option value="ht">Haitian Creole</option>
                                        <option value="ha">Hausa</option>
                                        <option value="he">Hebrew</option>
                                        <option value="hi">Hindi</option>
                                        <option value="hu">Hungarian</option>
                                        <option value="is">Icelandic</option>
                                        <option value="id">Indonesian</option>
                                        <option value="ga">Irish</option>
                                        <option value="it">Italian</option>
                                        <option value="ja">Japanese</option>
                                        <option value="kn">Kannada</option>
                                        <option value="kk">Kazakh</option>
                                        <option value="ko">Korean</option>
                                        <option value="lv">Latvian</option>
                                        <option value="lt">Lithuanian</option>
                                        <option value="mk">Macedonian</option>
                                        <option value="ms">Malay</option>
                                        <option value="ml">Malayalam</option>
                                        <option value="mt">Maltese</option>
                                        <option value="mr">Marathi</option>
                                        <option value="mn">Mongolian</option>
                                        <option value="no">Norwegian (Bokmål)</option>
                                        <option value="ps">Pashto</option>
                                        <option value="pl">Polish</option>
                                        <option value="pt">Portuguese (Brazil)</option>
                                        <option value="pt-PT">Portuguese (Portugal)</option>
                                        <option value="pa">Punjabi</option>
                                        <option value="ro">Romanian</option>
                                        <option value="ru">Russian</option>
                                        <option value="sr">Serbian</option>
                                        <option value="si">Sinhala</option>
                                        <option value="sk">Slovak</option>
                                        <option value="sl">Slovenian</option>
                                        <option value="so">Somali</option>
                                        <option value="es">Spanish</option>
                                        <option value="es-MX">Spanish (Mexico)</option>
                                        <option value="sw">Swahili</option>
                                        <option value="sv">Swedish</option>
                                        <option value="ta">Tamil</option>
                                        <option value="te">Telugu</option>
                                        <option value="th">Thai</option>
                                        <option value="tr">Turkish</option>
                                        <option value="uk">Ukrainian</option>
                                        <option value="ur">Urdu</option>
                                        <option value="uz">Uzbek</option>
                                        <option value="vi">Vietnamese</option>
                                        <option value="cy">Welsh</option>
                                    </select>
                                </div>
                            </div> --}}
                        </div>

                        <div class="row">
                            <div class="col-lg-6">
                                <div class="input-group mb-3">
                                    <label class="input-group-text" for="coverImageFile">Cover Image</label>
                                    <input type="file" class="form-control" name="mCoverFile" id="coverImageFile">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="input-group mb-3">
                                    <span class="input-group-text">Completion Time(minutes)</span>
                                    <input type="number" class="form-control" id="mCompTime" name="mCompTime"
                                        placeholder="5" aria-label="Username" aria-describedby="basic-addon1"
                                        data-name="Completion Time" required>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="jsonData" id="jsonDataInput" value="">
                        <input type="submit" id="addTrainingsubmitButton" value="Submit" class="d-none">

                    </form>

                    <hr>

                    <div class="questionSection">
                        <div class="my-2">
                            <button type="button" class="btn btn-primary mx-1" onclick="createPageForm('forms')">
                                Add Question
                            </button>
                            <button type="button" onclick="createStatementPageForm('forms')"
                                class="btn btn-secondary mx-1">
                                Add Statement
                            </button>
                        </div>

                        <div id="forms"></div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Close
                    </button>
                    <button type="button" class="btn btn-primary" onclick="checkRequiredInputs()">
                        Submit
                    </button>
                </div>
            </div>
        </div>
    </div>


    <!-- edit training -->
    <div class="modal fade" id="editTrainingModuleModal" tabindex="-1" aria-labelledby="exampleModalLgLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Edit Training</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('trainingmodule.update') }}" id="editModuleForm" method="post"
                        enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="input-group mb-3">
                                    <span class="input-group-text">Module name</span>
                                    <input type="text" class="form-control" name="moduleName" id="editModuleName"
                                        placeholder="Enter a unique module name" aria-label="Enter a unique module name"
                                        aria-describedby="basic-addon1" data-name="Module name" required="">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="input-group mb-3">
                                    <span class="input-group-text">Passing Score</span>
                                    <input type="number" class="form-control" id="editmPassingScore"
                                        name="mPassingScore" min="0" max="100" placeholder="70"
                                        aria-label="70" aria-describedby="basic-addon1" data-name="Passing Score"
                                        required="">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            {{-- <div class="col-lg-4">
                                <div class="input-group mb-3">
                                    <span class="input-group-text">Module Language</span>
                                    <select class="form-select" name="mModuleLang" id="editLang">
                                        <option value="en">English</option>
                                        <option value="af">Afrikaans</option>
                                        <option value="sq">Albanian</option>
                                        <option value="am">Amharic</option>
                                        <option value="ar">Arabic</option>
                                        <option value="hy">Armenian</option>
                                        <option value="az">Azerbaijani</option>
                                        <option value="bn">Bengali</option>
                                        <option value="bs">Bosnian</option>
                                        <option value="bg">Bulgarian</option>
                                        <option value="ca">Catalan</option>
                                        <option value="zh">Chinese (Simplified)</option>
                                        <option value="zh-TW">Chinese (Traditional)</option>
                                        <option value="hr">Croatian</option>
                                        <option value="cs">Czech</option>
                                        <option value="da">Danish</option>
                                        <option value="fa-AF">Dari</option>
                                        <option value="nl">Dutch</option>
                                        <option value="et">Estonian</option>
                                        <option value="fa">Farsi (Persian)</option>
                                        <option value="tl">Filipino, Tagalog</option>
                                        <option value="fi">Finnish</option>
                                        <option value="fr">French</option>
                                        <option value="fr-CA">French (Canada)</option>
                                        <option value="ka">Georgian</option>
                                        <option value="de">German</option>
                                        <option value="el">Greek</option>
                                        <option value="gu">Gujarati</option>
                                        <option value="ht">Haitian Creole</option>
                                        <option value="ha">Hausa</option>
                                        <option value="he">Hebrew</option>
                                        <option value="hi">Hindi</option>
                                        <option value="hu">Hungarian</option>
                                        <option value="is">Icelandic</option>
                                        <option value="id">Indonesian</option>
                                        <option value="ga">Irish</option>
                                        <option value="it">Italian</option>
                                        <option value="ja">Japanese</option>
                                        <option value="kn">Kannada</option>
                                        <option value="kk">Kazakh</option>
                                        <option value="ko">Korean</option>
                                        <option value="lv">Latvian</option>
                                        <option value="lt">Lithuanian</option>
                                        <option value="mk">Macedonian</option>
                                        <option value="ms">Malay</option>
                                        <option value="ml">Malayalam</option>
                                        <option value="mt">Maltese</option>
                                        <option value="mr">Marathi</option>
                                        <option value="mn">Mongolian</option>
                                        <option value="no">Norwegian (Bokmål)</option>
                                        <option value="ps">Pashto</option>
                                        <option value="pl">Polish</option>
                                        <option value="pt">Portuguese (Brazil)</option>
                                        <option value="pt-PT">Portuguese (Portugal)</option>
                                        <option value="pa">Punjabi</option>
                                        <option value="ro">Romanian</option>
                                        <option value="ru">Russian</option>
                                        <option value="sr">Serbian</option>
                                        <option value="si">Sinhala</option>
                                        <option value="sk">Slovak</option>
                                        <option value="sl">Slovenian</option>
                                        <option value="so">Somali</option>
                                        <option value="es">Spanish</option>
                                        <option value="es-MX">Spanish (Mexico)</option>
                                        <option value="sw">Swahili</option>
                                        <option value="sv">Swedish</option>
                                        <option value="ta">Tamil</option>
                                        <option value="te">Telugu</option>
                                        <option value="th">Thai</option>
                                        <option value="tr">Turkish</option>
                                        <option value="uk">Ukrainian</option>
                                        <option value="ur">Urdu</option>
                                        <option value="uz">Uzbek</option>
                                        <option value="vi">Vietnamese</option>
                                        <option value="cy">Welsh</option>
                                    </select>
                                </div>
                            </div> --}}
                        </div>

                        <div class="row">
                            <div class="col-lg-6">
                                <div class="input-group mb-3">
                                    <label class="input-group-text" for="coverImageFile">Cover Image</label>
                                    <input type="file" class="form-control" name="mCoverFile"
                                        id="editCoverImageFile">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="input-group mb-3">
                                    <span class="input-group-text">Completion Time(minutes)</span>
                                    <input type="number" class="form-control" id="editMCompTime" name="mCompTime"
                                        placeholder="5" aria-label="Username" aria-describedby="basic-addon1"
                                        data-name="Completion Time" required="">
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="updatedjsonData" id="updatedJsonDataInput" value="">
                        <input type="hidden" name="trainingModuleid" id="trainingModId" value="">
                        <input type="submit" id="updateTrainingsubmitButton" value="Submit" class="d-none">

                    </form>

                    <hr>

                    <div class="questionSection">
                        <div class="my-2">
                            <button type="button" class="btn btn-primary mx-1" onclick="createPageForm('editforms')">
                                Add Question
                            </button>
                            <button type="button" onclick="createStatementPageForm('editforms')"
                                class="btn btn-secondary mx-1">
                                Add Statement
                            </button>
                        </div>

                        <div id="editforms">




                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Close
                    </button>
                    <button type="button" class="btn btn-primary" onclick="checkEditRequiredInputs()">
                        Submit
                    </button>
                </div>
            </div>
        </div>
    </div>




    {{-- -------------------Modals------------------------ --}}


    {{-- ------------------------------Toasts---------------------- --}}

    <div class="toast-container position-fixed top-0 end-0 p-3">
        @if (session('success'))
            <div class="toast colored-toast bg-success-transparent fade show" role="alert" aria-live="assertive"
                aria-atomic="true">
                <div class="toast-header bg-success text-fixed-white">
                    <strong class="me-auto">Success</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    {{ session('success') }}
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="toast colored-toast bg-danger-transparent fade show" role="alert" aria-live="assertive"
                aria-atomic="true">
                <div class="toast-header bg-danger text-fixed-white">
                    <strong class="me-auto">Error</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    {{ session('error') }}
                </div>
            </div>
        @endif

        @if ($errors->any())
            @foreach ($errors->all() as $error)
                <div class="toast colored-toast bg-danger-transparent fade show" role="alert" aria-live="assertive"
                    aria-atomic="true">
                    <div class="toast-header bg-danger text-fixed-white">
                        <strong class="me-auto">Error</strong>
                        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    <div class="toast-body">
                        {{ $error }}
                    </div>
                </div>
            @endforeach
        @endif


    </div>

    {{-- ------------------------------Toasts---------------------- --}}


    @push('newcss')
        <style>
            .htmlPhishingGrid {
                overflow: hidden;
                border: 1px solid #8080804a;
                border-radius: 6px;
                max-height: 300px;
                /* filter: brightness(0.9); */

            }

            .htmlPhishingGrid iframe {
                width: 100%;
                height: 100vh;
                border: none;
            }

            .trainingCoverImg {
                object-fit: contain;
                width: 100%;
            }
        </style>
    @endpush

    @push('newscripts')
        <script>
            document.getElementById("mPassingScore").addEventListener("input", function() {
                // Get the current value of the input field
                var currentValue = parseInt(this.value);

                // If the value is greater than 100, set it to 100
                if (currentValue > 100) {
                    this.value = 100;
                }
            });

            document.getElementById("editmPassingScore").addEventListener("input", function() {
                // Get the current value of the input field
                var currentValue = parseInt(this.value);

                // If the value is greater than 100, set it to 100
                if (currentValue > 100) {
                    this.value = 100;
                }
            });

            document.getElementById("mCompTime").addEventListener("input", function() {
                // Get the current value of the input field
                var currentValue = parseInt(this.value);

                // If the value is greater than 100, set it to 100
                if (currentValue > 60) {
                    this.value = 60;
                }
            });

            document.getElementById("editMCompTime").addEventListener("input", function() {
                // Get the current value of the input field
                var currentValue = parseInt(this.value);

                // If the value is greater than 100, set it to 100
                if (currentValue > 60) {
                    this.value = 60;
                }
            });

            function checkRequiredInputs() {
                const form = document.getElementById('newModuleForm');
                const inputs = form.querySelectorAll('input[required]');
                let allFilled = true;

                inputs.forEach(input => {
                    if (!input.value.trim()) {
                        allFilled = false;
                        alert(`${input.dataset.name} is required.`);
                    }
                });

                if (allFilled) {

                    const forms = document.querySelectorAll("#forms .trainingForms"); // Select all forms
                    const formDataArray = [];
                    let atLeastOneFormFilled = false; // Flag to track if at least one form is filled

                    forms.forEach(function(form) {
                        let formFilled = true; // Flag to track if all fields in the form are filled
                        const formData = new FormData(form); // Get form data
                        const formDataObject = {}; // Object to store form data

                        // Iterate over form elements
                        form.querySelectorAll('input, select').forEach(function(element) {
                            // Check if input or select has a value
                            if (element.value.trim() === '') {
                                formFilled = false;
                            }
                        });

                        if (formFilled) {
                            // Convert form data to object
                            for (const [key, value] of formData.entries()) {
                                formDataObject[key] = value;
                            }

                            formDataArray.push(formDataObject); // Push form data object to array
                            atLeastOneFormFilled = true; // At least one form is filled
                        }
                    });

                    if (atLeastOneFormFilled) {
                        console.log(formDataArray);
                        saveTrainingDataToDb(formDataArray);
                    } else {
                        alert("Please add atleast one training.");
                    }


                }
            }

            function checkEditRequiredInputs() {
                const form = document.getElementById('editModuleForm');
                const inputs = form.querySelectorAll('input[required]');
                let allFilled = true;

                inputs.forEach(input => {
                    if (!input.value.trim()) {
                        allFilled = false;
                        alert(`${input.dataset.name} is required.`);
                    }
                });

                if (allFilled) {

                    const forms = document.querySelectorAll("#editforms .trainingForms"); // Select all forms
                    const formDataArray = [];
                    let atLeastOneFormFilled = false; // Flag to track if at least one form is filled

                    forms.forEach(function(form) {
                        let formFilled = true; // Flag to track if all fields in the form are filled
                        const formData = new FormData(form); // Get form data
                        const formDataObject = {}; // Object to store form data

                        // Iterate over form elements
                        form.querySelectorAll('input, select').forEach(function(element) {
                            // Check if input or select has a value
                            if (element.value.trim() === '') {
                                formFilled = false;
                            }
                        });

                        if (formFilled) {
                            // Convert form data to object
                            for (const [key, value] of formData.entries()) {
                                formDataObject[key] = value;
                            }

                            formDataArray.push(formDataObject); // Push form data object to array
                            atLeastOneFormFilled = true; // At least one form is filled
                        }
                    });

                    if (atLeastOneFormFilled) {
                        console.log(formDataArray);
                        updateTrainingDataToDb(formDataArray);
                    } else {
                        alert("Please add atleast one training.");
                    }


                }
            }

            function updateTrainingDataToDb(updatedTrainingData) {
                // Set the value of the hidden input field
                const hiddenInput = document.getElementById('updatedJsonDataInput');
                hiddenInput.value = JSON.stringify(updatedTrainingData);

                // Submit the form programmatically
                const updateTrainingsubmitButton = document.getElementById('updateTrainingsubmitButton');
                updateTrainingsubmitButton.click(); // This will trigger form submission

            }

            function saveTrainingDataToDb(jsonTrainingData) {
                // Set the value of the hidden input field
                const hiddenInput = document.getElementById('jsonDataInput');
                hiddenInput.value = JSON.stringify(jsonTrainingData);

                // Submit the form programmatically
                const addTrainingsubmitButton = document.getElementById('addTrainingsubmitButton');
                addTrainingsubmitButton.click(); // This will trigger form submission
            }
        </script>

        <script>
            /////////////////adding question script////////////////////////

            let page = 0;

            function addNewTraining() {
                page = 0;
                document.getElementById("forms").innerHTML = '';
                createPageForm('forms');
            }

            function generateMultipleChoiceInputs(pageno) {
                let optionContainer = document.getElementById(
                    `optionContainer${pageno}`
                );
                let options = "";
                let correctOptions = "";
                let ansDesc = "";
                for (let i = 0; i < 4; i++) {
                    const input = `<div class="input-group input-group-sm mb-3">
                      <span class="input-group-text">Option ${i + 1}:</span>
                      <input
                        type="text"
                        class="form-control"
                        name="option${i + 1}"
                        placeholder="Enter as answer option"
                      />
                    </div>`;
                    options += input;
                }
                // adding correct option dropdown
                correctOptions = `<div class="input-group input-group-sm mb-3">
                      <span class="input-group-text">Correct Option</span>
                      <select
                        class="form-select"
                        aria-label="Default select example"
                        name="correctOption"
                      >
                        <option value="option1">Option 1</option>
                        <option value="option2">Option 2</option>
                        <option value="option3">Option 3</option>
                        <option value="option4">Option 4</option>
                      </select>
                    </div>`;

                optionContainer.innerHTML = options + correctOptions;
            }

            function generateTrueFalseInputs(pageno) {
                let optionContainer = document.getElementById(
                    `optionContainer${pageno}`
                );
                optionContainer.innerHTML = ""; // Clear previous options

                const trueFalseElements = `<div class="input-group input-group-sm mb-3">
                      <span class="input-group-text">Answer</span>
                      <select
                        class="form-select"
                        aria-label="Default select example"
                        name="correctOption"
                      >
                        <option value="true">True</option>
                        <option value="false">False</option>
                      </select>
                    </div>`;
                optionContainer.innerHTML = trueFalseElements;
            }

            function changeQuestionType(e, pageno) {
                const selectedOption = e.value;
                if (selectedOption === "multipleChoice") {
                    generateMultipleChoiceInputs(pageno);
                } else if (selectedOption === "trueFalse") {
                    generateTrueFalseInputs(pageno);
                }
            }



            function createPageForm(formType) {
                page++;
                let pageNo = page;
                const formContent = `
                  <div class="pageQuestion my-3">
                    <div class="d-flex justify-content-between my-2">
                      <span class="badge rounded-pill text-bg-primary my-2"
                        >Page <span class="noofpages">${pageNo}</span> - Question</span>
                        <div>
                    <button type="button" class="btn btn-sm btn-primary mx-1" onclick="createPageForm('${formType}')">
                      Add Question
                    </button>
                    <button type="button" onclick="createStatementPageForm('${formType}')" class="btn btn-sm btn-secondary mx-1">
                      Add Statement
                    </button>
                  
                      <button type="button" onclick="removePage('${pageNo}', 'forms')" class="btn btn-sm btn-danger">
                        Remove
                      </button>
                      </div>
                    </div>
                    <div class="input-group input-group-sm mb-3">
                      <span class="input-group-text">Question</span>
                      <input
                        type="text"
                        class="form-control"
                        name="question"
                        placeholder="Enter a question"
                      />
                    </div>
                    <div class="input-group input-group-sm mb-3">
                      <span class="input-group-text">Question Type</span>
                      <select
                        class="form-select"
                        aria-label="Default select example"
                        name="qtype"
                        id="questionType${pageNo}"
                        onchange="changeQuestionType(this,'${pageNo}')"
                      >
                        <option value="multipleChoice">Multiple Choice</option>
                        <option value="trueFalse">True/False</option>
                      </select>
                    </div>
                    <div id="optionContainer${pageNo}">
                      <div class="input-group input-group-sm mb-3">
                      <span class="input-group-text">Option 1:</span>
                      <input
                        type="text"
                        class="form-control"
                        name="option1"
                        placeholder="Enter as answer option"
                      />
                    </div>
                    <div class="input-group input-group-sm mb-3">
                      <span class="input-group-text">Option 2:</span>
                      <input
                        type="text"
                        class="form-control"
                        name="option2"
                        placeholder="Enter as answer option"
                      />
                    </div>
                    <div class="input-group input-group-sm mb-3">
                      <span class="input-group-text">Option 3:</span>
                      <input
                        type="text"
                        class="form-control"
                        name="option3"
                        placeholder="Enter as answer option"
                      />
                    </div>
                    <div class="input-group input-group-sm mb-3">
                      <span class="input-group-text">Option 4:</span>
                      <input
                        type="text"
                        class="form-control"
                        name="option4"
                        placeholder="Enter as answer option"
                      />
                    </div>
                    <div class="input-group input-group-sm mb-3">
                      <span class="input-group-text">Correct Option</span>
                      <select
                        class="form-select"
                        aria-label="Default select example"
                        name="correctOption"
                      >
                        <option value="option1">Option 1</option>
                        <option value="option2">Option 2</option>
                        <option value="option3">Option 3</option>
                        <option value="option4">Option 4</option>
                      </select>
                    </div>
    
                      </div>
    
                    <div class="input-group input-group-sm mb-3">
                      <span class="input-group-text">Answer Description</span>
                      <input
                        type="text"
                        class="form-control"
                        name="ansDesc"
                        placeholder="Enter the content that is displayed under this navbar."
                      />
                    </div>
                  </div>`;
                // Create a new div element
                let form = document.createElement('form');
                form.classList.add("p-2", "trainingForms", "my-3");
                form.style.border = "0.5px solid rgb(223, 223, 223)";
                form.style.borderRadius = "6px";
                form.id = "formid" + pageNo;
                form.innerHTML = formContent;
                // Append the div element to the forms container
                document.getElementById(formType).appendChild(form);

                sortNoOfPages(formType)

            }


            //////////////// end of ending question page/////////////////////
            //sorting no of pages
            function sortNoOfPages(formType) {
                const allPages = document.querySelectorAll(`#${formType} .noofpages`);
                allPages.forEach((page, index) => {

                    page.innerHTML = `${index+1}`;
                })
            }

            //removing page
            function removePage(pageno, formtype) {
                const pageForm = document.getElementById(`formid${pageno}`)
                pageForm.remove();
                sortNoOfPages(formtype)
            }

            function createStatementPageForm(formType) {
                page++;
                let pageNo = page;
                const formContent = `
                <div class"pageQuestion my-3">
                    <div class="d-flex justify-content-between my-2">
                      <span class="badge rounded-pill text-bg-primary my-2"
                        >Page <span class="noofpages">${pageNo}</span> - Statement</span>
    
                        <div>
                        <button type="button" class="btn btn-sm btn-primary mx-1" onclick="createPageForm('${formType}')">
                      Add Question
                    </button>
                    <button type="button" onclick="createStatementPageForm('${formType}')" class="btn btn-sm btn-secondary mx-1">
                      Add Statement
                    </button>
    
                      <button type="button" onclick="removePage('${pageNo}', '${formType}')" class="btn btn-sm btn-danger">
                        Remove
                      </button>
                      </div>
                    </div>
                    <div class="input-group input-group-sm mb-3">
                      <span class="input-group-text">Statement Title</span>
                      <input
                        type="text"
                        class="form-control"
                        name="sTitle"
                        placeholder="Enter a statement title"
                      />
                      <input type="hidden" name="qtype" value="statement">
                    </div>
                    <div class="input-group input-group-sm mb-3">
                      <span class="input-group-text">Statement Content</span>
                      <input
                        type="text"
                        class="form-control"
                        name="sContent"
                        placeholder="Enter a statement content"
                      />
                    </div>
                    <div class="input-group input-group-sm mb-3">
                      <span class="input-group-text">Additional Content</span>
                      <select
                        class="form-select"
                        aria-label="Default select example"
                        name="embeddedVideo"
                      >
                        <option value="embeddedVideo">Embedded Video URL</option>
                      </select>
                    </div>
                    <div id="optionContainer">
                      <div class="input-group input-group-sm mb-3">
                      <span class="input-group-text">Embedded Video URL</span>
                      <input
                        type="text"
                        class="form-control"
                        name="videoUrl"
                        placeholder="Enter the URL of an Embedded Video (Youtube)"
                      />
                    </div>
                    </div>`;
                // Create a new div element
                let form = document.createElement('form');
                form.classList.add("p-2", "trainingForms", "my-3");
                form.style.border = "0.5px solid rgb(223, 223, 223)";
                form.style.borderRadius = "6px";
                form.id = "formid" + pageNo;
                form.innerHTML = formContent;
                // Append the div element to the forms container
                document.getElementById(formType).appendChild(form);

                sortNoOfPages(formType)

            }

            function deleteTrainingModule(trainingid, filelocation) {

                Swal.fire({
                    title: 'Are you sure?',
                    text: "If this training is associated with any campaign or training assigned employees. Then the campaign and the assigned training will be deleted.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e6533c',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Delete'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post({
                            url: "{{route('trainingmodule.delete')}}",
                            data: {
                                deleteTraining: 1,
                                trainingid: trainingid,
                                cover_image: filelocation
                            },
                            success: function(res) {
                                // console.log(res)
                                window.location.href = window.location.href;
                            }
                        })
                    }
                })


                // if (confirm(
                //         'If this training is associated with any campaign or training assigned employees. Then the campaign and the assigned training will be deleted.'
                //     )) {
                //     $.post({
                //         url: 'trainingModules.php',
                //         data: {
                //             deleteTraining: 1,
                //             trainingid: trainingid,
                //             cover_image: filelocation
                //         },
                //         success: function(res) {
                //             // console.log(res)
                //             window.location.href = window.location.href;
                //         }
                //     })
                // } else {
                //     return false;
                // }
            }


            //editing training module

            function editTrainingModule(id) {

                trainingModId.value = id

                function createMultipleChoicePageEditForm(obj, index) {

                    let cont = `<form class="p-2 trainingForms my-3" id="formid${index}" style="border: 0.5px solid rgb(223, 223, 223); border-radius: 6px;">
                      <div class="pageQuestion my-3">
                        <div class="d-flex justify-content-between my-2">
                          <span class="badge rounded-pill text-bg-primary my-2">Page <span class="noofpages"></span> - Question</span>
                          <div>
                    <button type="button" class="btn btn-sm btn-primary mx-1" onclick="createPageForm('editforms')">
                      Add Question
                    </button>
                    <button type="button" onclick="createStatementPageForm('editforms')" class="btn btn-sm btn-secondary mx-1">
                      Add Statement
                    </button>
                    <button type="button" onclick="removePage('${index}', 'editforms')" class="btn btn-sm btn-danger">
                            Remove
                          </button>
                  </div>
                          
                        </div>
                        <div class="input-group input-group-sm mb-3">
                          <span class="input-group-text">Question</span>
                          <input type="text" class="form-control" name="question" value="${obj.question}" placeholder="Enter a question">
                        </div>
                        <div class="input-group input-group-sm mb-3">
                          <span class="input-group-text">Question Type</span>
                          <select class="form-select" aria-label="Default select example" value="${obj.qtype}" name="qtype" id="questionType${index}" onchange="changeQuestionType(this,'${index}')">
                            <option value="multipleChoice" ${obj.qtype === 'multipleChoice' ? 'selected' : ''}>Multiple Choice</option>
                            <option value="trueFalse" ${obj.qtype === 'trueFalse' ? 'selected' : ''}>True/False</option>
                          </select>
                        </div>
                        <div id="optionContainer${index}">
                          <div class="input-group input-group-sm mb-3">
                            <span class="input-group-text">Option 1:</span>
                            <input type="text" class="form-control" name="option1" value="${obj.option1}" placeholder="Enter as answer option">
                          </div>
                          <div class="input-group input-group-sm mb-3">
                            <span class="input-group-text">Option 2:</span>
                            <input type="text" class="form-control" name="option2" value="${obj.option2}" placeholder="Enter as answer option">
                          </div>
                          <div class="input-group input-group-sm mb-3">
                            <span class="input-group-text">Option 3:</span>
                            <input type="text" class="form-control" name="option3" value="${obj.option3}" placeholder="Enter as answer option">
                          </div>
                          <div class="input-group input-group-sm mb-3">
                            <span class="input-group-text">Option 4:</span>
                            <input type="text" class="form-control" name="option4" value="${obj.option4}" placeholder="Enter as answer option">
                          </div>
                          <div class="input-group input-group-sm mb-3">
                            <span class="input-group-text">Correct Option</span>
                            <select class="form-select" aria-label="Default select example" name="correctOption">
                              <option value="option1" ${obj.correctOption === 'option1' ? 'selected' : ''}>Option 1</option>
                              <option value="option2" ${obj.correctOption === 'option2' ? 'selected' : ''}>Option 2</option>
                              <option value="option3" ${obj.correctOption === 'option3' ? 'selected' : ''}>Option 3</option>
                              <option value="option4" ${obj.correctOption === 'option4' ? 'selected' : ''}>Option 4</option>
                            </select>
                          </div>
    
                        </div>
    
                        <div class="input-group input-group-sm mb-3">
                          <span class="input-group-text">Answer Description</span>
                          <input type="text" class="form-control" name="ansDesc" value="${obj.ansDesc}" placeholder="Enter the content that is displayed under this navbar.">
                        </div>
                      </div>
                    </form>`;
                    let editForms = document.getElementById('editforms');
                    editForms.innerHTML += cont;
                    console.log(obj.qtype);
                    sortNoOfPages('editforms');
                }

                function createStatementPageEditForm(obj, index) {
                    let cont = `<form class="p-2 trainingForms my-3" id="formid${index}" style="border: 0.5px solid rgb(223, 223, 223); border-radius: 6px;">
                      <div class="pageQuestion my-3">
                        <div class="d-flex justify-content-between my-2">
                          <span class="badge rounded-pill text-bg-primary my-2">Page <span class="noofpages"></span> - Statement</span>
                        <div>
                          <button type="button" class="btn btn-sm btn-primary mx-1" onclick="createPageForm('editforms')">
                      Add Question
                    </button>
                    <button type="button" onclick="createStatementPageForm('editforms')" class="btn btn-sm btn-secondary mx-1">
                      Add Statement
                    </button>
                          <button type="button" onclick="removePage('${index}', 'editforms')" class="btn btn-sm btn-danger">
                            Remove
                          </button>
                          </div>
                        </div>
                        <div class="input-group input-group-sm mb-3">
                          <span class="input-group-text">Statement Title</span>
                          <input type="text" class="form-control" name="sTitle" value="${obj.sTitle}" placeholder="Enter a statement title">
                          <input type="hidden" name="qtype" value="statement">
                        </div>
                        <div class="input-group input-group-sm mb-3">
                          <span class="input-group-text">Statement Content</span>
                          <input type="text" class="form-control" name="sContent" value="${obj.sContent}" placeholder="Enter a statement content">
                        </div>
                        <div class="input-group input-group-sm mb-3">
                          <span class="input-group-text">Additional Content</span>
                          <select class="form-select" aria-label="Default select example" name="embeddedVideo">
                            <option value="embeddedVideo">Embedded Video URL</option>
                          </select>
                        </div>
                        <div id="optionContainer">
                          <div class="input-group input-group-sm mb-3">
                            <span class="input-group-text">Embedded Video URL</span>
                            <input type="text" class="form-control" name="videoUrl" value="${obj.videoUrl}" placeholder="Enter the URL of an Embedded Video (Youtube)">
                          </div>
                        </div>
                      </div>
                    </form>`;
                    let editForms = document.getElementById('editforms');
                    editForms.innerHTML += cont;
                    console.log(obj.qtype);
                    sortNoOfPages('editforms');
                }

                function createTrueFalsePageEditForm(obj, index) {
                    let cont = `<form class="p-2 trainingForms my-3" id="formid${index}" style="border: 0.5px solid rgb(223, 223, 223); border-radius: 6px;">
                      <div class="pageQuestion my-3">
                        <div class="d-flex justify-content-between my-2">
                          <span class="badge rounded-pill text-bg-primary my-2">Page <span class="noofpages"></span> - Question</span>
                        <div>
                          <button type="button" class="btn btn-sm btn-primary mx-1" onclick="createPageForm('editforms')">
                      Add Question
                    </button>
                    <button type="button" onclick="createStatementPageForm('editforms')" class="btn btn-sm btn-secondary mx-1">
                      Add Statement
                    </button>
                          <button type="button" onclick="removePage('${index}', 'editforms')" class="btn btn-sm btn-danger">
                            Remove
                          </button>
                          </div>
                        </div>
                        <div class="input-group input-group-sm mb-3">
                          <span class="input-group-text">Question</span>
                          <input type="text" class="form-control" name="question" value="${obj.question}" placeholder="Enter a question">
                        </div>
                        <div class="input-group input-group-sm mb-3">
                          <span class="input-group-text">Question Type</span>
                          <select class="form-select" aria-label="Default select example" name="qtype" id="questionType${index}" onchange="changeQuestionType(this,'${index}')">
                            <option value="multipleChoice" ${obj.qtype === 'multipleChoice' ? 'selected' : ''}>Multiple Choice</option>
                            <option value="trueFalse" ${obj.qtype === 'trueFalse' ? 'selected' : ''}>True/False</option>
                          </select>
                        </div>
                        <div id="optionContainer${index}">
                          <div class="input-group input-group-sm mb-3">
                            <span class="input-group-text">Answer</span>
                            <select class="form-select" aria-label="Default select example" name="correctOption">
                              <option value="true" ${obj.correctOption === 'true' ? 'selected' : ''}>True</option>
                              <option value="false" ${obj.correctOption === 'false' ? 'selected' : ''}>False</option>
                            </select>
                          </div>
                        </div>
    
                        <div class="input-group input-group-sm mb-3">
                          <span class="input-group-text">Answer Description</span>
                          <input type="text" class="form-control" name="ansDesc" value="${obj.ansDesc}" placeholder="Enter the content that is displayed under this navbar.">
                        </div>
                      </div>
                    </form>`;
                    let editForms = document.getElementById('editforms');
                    editForms.innerHTML += cont;
                    console.log(obj.qtype);
                    sortNoOfPages('editforms');
                }


                $.get({
                    url: `/get-training-module/${id}`,
                    success: function(resJson) {
                        console.log(resJson);

                        document.getElementById('editforms').innerHTML = '';
                        // const resJson = JSON.parse(res);
                        editModuleName.value = resJson.name;
                        editmPassingScore.value = resJson.passing_score;
                        // editLang.value = resJson.module_language;
                        // editCoverImageFile.value = resJson.cover_image;
                        editMCompTime.value = resJson.estimated_time;

                        const quizes = JSON.parse(resJson.json_quiz);
                        // console.log(quizes);
                        page = 0;
                        quizes.forEach((obj, index) => {
                            if (obj.qtype == 'statement') {
                                createStatementPageEditForm(obj, index + 1)
                            }
                            if (obj.qtype == 'multipleChoice') {
                                createMultipleChoicePageEditForm(obj, index + 1)
                            }
                            if (obj.qtype == 'trueFalse') {
                                createTrueFalsePageEditForm(obj, index + 1)
                            }
                            page++;
                        })
                    }
                })
            }
        </script>
    @endpush

@endsection
