@extends('layouts.app')

@section('title', 'Training Modules - Phishing awareness training program')

@section('main-content')

    <div class="main-content app-content">
        <div class="container-fluid mt-4">


            <div class="d-flex justify-content-between">
                <div>
                    <button type="button" class="btn btn-primary mb-3" onclick="addNewTraining()" data-bs-toggle="modal"
                        data-bs-target="#newTrainingModuleModal">{{ __('New Training Module') }}</button>
                    <button class="btn btn-secondary label-btn mb-3 mx-2" data-bs-toggle="modal"
                        data-bs-target="#newGamifiedTrainingModal">
                        <i class="ri-settings-4-line label-btn-icon me-2"></i>
                        {{ __('New Gamified Training') }}
                    </button>
                </div>
                <div
                    style="
                            display: flex;
                            align-items: center;
                            gap: 10px;
                        ">

                    <input type="text" class="form-control" id="t_moduleSearch" placeholder="{{ __('Search training') }}">
                    <i class="bx bx-search fs-23"></i>
                </div>
            </div>


            <div class="row">
                <div class="col-xl-12">
                    <div class="card custom-card">
                        <div class="card-header d-flex justify-content-between">
                            <div class="card-title">
                                {{ __('Manage Training Modules') }}
                            </div>
                            <div class="d-flex gap-2">
                                <div>
                                    <div class="input-group mb-2">
                                        <span class="input-group-text">
                                            {{ __('Training Type') }}
                                        </span>
                                        <select class="form-select" id="training_type_select">
                                            <option value="static_training"
                                                {{ request('type') == 'static_training' ? 'selected' : '' }}>{{ __('Static/ AI Training') }}</option>
                                            <option value="gamified" {{ request('type') == 'gamified' ? 'selected' : '' }}>
                                                {{ __('Gamified Training') }}</option>
                                            <option value="games" {{ request('type') == 'games' ? 'selected' : '' }}>
                                                {{ __('Games') }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    @if (request('type') !== 'games')
                                        <div class="input-group mb-2">
                                            <span class="input-group-text">
                                                {{ __('Category') }}
                                            </span>
                                            <select class="form-select" id="category_select">
                                                <option value="international"
                                                    {{ request('category') == 'international' ? 'selected' : '' }}>
                                                    {{ __('International') }}</option>
                                                <option value="middle_east"
                                                    {{ request('category') == 'middle_east' ? 'selected' : '' }}>
                                                    {{ __('Middle East') }}</option>
                                            </select>
                                        </div>
                                    @endif
                                </div>

                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @forelse ($trainings as $trainingModule)
                                    <div class="col-lg-6 t_modules">
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
                                                    src="{{ request('type') == 'games' ? Storage::url('uploads/trainingGame/' . $trainingModule->cover_image) : Storage::url('uploads/trainingModule/' . $trainingModule->cover_image) }}" />
                                            </div>
                                            <div class="card-footer">
                                                <div class="d-flex justify-content-center">

                                                    <a href="@if (request('type') == 'games') {{ env('TRAINING_GAME_URL') }}/{{ $trainingModule->slug }} @else {{ '/training-preview/' . base64_encode($trainingModule->id) }} @endif"
                                                        target="_blank"
                                                        class="btn mx-1 btn-outline-primary btn-wave waves-effect waves-light">{{ __('View') }}</a>

                                                    @if (request('type') !== 'games' && $trainingModule->company_id !== 'default')
                                                        <button type="button"
                                                            onclick="deleteTrainingModule(`{{ $trainingModule->id }}`, `{{ $trainingModule->cover_image }}`)"
                                                            class="btn mx-1 btn-outline-danger btn-wave waves-effect waves-light">{{ __('Delete') }}</button>

                                                        <button type="button"
                                                            onclick="{{ $trainingModule->training_type == 'gamified' ? 'editGamifiedTrainingModule' : 'editTrainingModule' }}(`{{ $trainingModule->id }}`)"
                                                            class="btn mx-1 btn-outline-primary btn-wave waves-effect waves-light"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="{{ $trainingModule->training_type == 'gamified' ? '#editGamifiedTrainingModuleModal' : '#editTrainingModuleModal' }}">{{ __('Edit') }}</button>
                                                    @endif

                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-lg-6">
                                        {{ __('No records found') }}
                                    </div>
                                @endforelse
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <div>
                {{ $trainings->links() }}
            </div>


        </div>
    </div>

    {{-- -------------------Modals------------------------ --}}

    {{-- add new training  --}}

    <x-modal id="newTrainingModuleModal" size="modal-lg" heading="{{ __('Add Training') }}">
        <x-training_module.new-training-form />
    </x-modal>


    {{-- edit training --}}

    <x-modal id="editTrainingModuleModal" size="modal-lg" heading="{{ __('Edit Training') }}">
        <x-training_module.edit-training-form />
    </x-modal>


    {{-- new gamified training --}}

    <x-modal id="newGamifiedTrainingModal" size="modal-lg" heading="{{ __('Add Gamified Training') }}">
        <x-training_module.new-gamified-training-form />
    </x-modal>

    {{-- edit gamified training --}}

    <x-modal id="editGamifiedTrainingModuleModal" size="modal-lg" heading="{{ __('Edit Gamified Training') }}">
        <x-training_module.edit-gamified-training-form />
    </x-modal>




    {{-- ------------------------------Toasts---------------------- --}}

    <x-toast />


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
            document.querySelectorAll(".mPassingScore").forEach(function(element) {
                element.addEventListener("input", function() {
                    // Get the current value of the input field
                    var currentValue = parseInt(this.value);

                    // If the value is greater than 100, set it to 100
                    if (currentValue > 100) {
                        this.value = 100;
                    }
                });
            });


            document.querySelectorAll(".mCompTime").forEach(function(element) {
                element.addEventListener("input", function() {
                    // Get the current value of the input field
                    var currentValue = parseInt(this.value);

                    // If the value is greater than 60, set it to 60
                    if (currentValue > 60) {
                        this.value = 60;
                    }
                });
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
                        // console.log(formDataArray);
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
                        // console.log(formDataArray);
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
                console.log("jsonTrainingData", jsonTrainingData);
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
                        placeholder="{{ __('Enter as answer option') }}"
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
                        >{{ __('Page') }} <span class="noofpages">${pageNo}</span> - {{ __('Question') }}</span>
                        <div>
                    <button type="button" class="btn btn-sm btn-primary mx-1" onclick="createPageForm('${formType}')">
                      {{ __('Add Question') }}
                    </button>
                    <button type="button" onclick="createStatementPageForm('${formType}')" class="btn btn-sm btn-secondary mx-1">
                      {{ __('Add Statement') }}
                    </button>
                  
                      <button type="button" onclick="removePage('${pageNo}', 'forms')" class="btn btn-sm btn-danger">
                        {{ __('Remove') }}
                      </button>
                      </div>
                    </div>
                    <div class="input-group input-group-sm mb-3">
                      <span class="input-group-text">{{ __('Question') }}</span>
                      <input
                        type="text"
                        class="form-control"
                        name="question"
                        placeholder="{{ __('Enter a question') }}"
                      />
                    </div>
                    <div class="input-group input-group-sm mb-3">
                      <span class="input-group-text">{{ __('Question Type') }}</span>
                      <select
                        class="form-select"
                        aria-label="Default select example"
                        name="qtype"
                        id="questionType${pageNo}"
                        onchange="changeQuestionType(this,'${pageNo}')"
                      >
                        <option value="multipleChoice">{{ __('Multiple Choice') }}</option>
                        <option value="trueFalse">{{ __('True/False') }}</option>
                      </select>
                    </div>
                    <div id="optionContainer${pageNo}">
                      <div class="input-group input-group-sm mb-3">
                      <span class="input-group-text">{{ __('Option 1:') }}</span>
                      <input
                        type="text"
                        class="form-control"
                        name="option1"
                        placeholder="{{ __('Enter as answer option') }}"
                      />
                    </div>
                    <div class="input-group input-group-sm mb-3">
                      <span class="input-group-text">{{ __('Option 2:') }}</span>
                      <input
                        type="text"
                        class="form-control"
                        name="option2"
                        placeholder="{{ __('Enter as answer option') }}"
                      />
                    </div>
                    <div class="input-group input-group-sm mb-3">
                      <span class="input-group-text">{{ __('Option 3:') }}</span>
                      <input
                        type="text"
                        class="form-control"
                        name="option3"
                        placeholder="{{ __('Enter as answer option') }}"
                      />
                    </div>
                    <div class="input-group input-group-sm mb-3">
                      <span class="input-group-text">{{ __('Option 4:') }}</span>
                      <input
                        type="text"
                        class="form-control"
                        name="option4"
                        placeholder="{{ __('Enter as answer option') }}"
                      />
                    </div>
                    <div class="input-group input-group-sm mb-3">
                      <span class="input-group-text">{{ __('Correct Option') }}</span>
                      <select
                        class="form-select"
                        aria-label="Default select example"
                        name="correctOption"
                      >
                        <option value="option1">{{ __('Option 1') }}</option>
                        <option value="option2">{{ __('Option 2') }}</option>
                        <option value="option3">{{ __('Option 3') }}</option>
                        <option value="option4">{{ __('Option 4') }}</option>
                      </select>
                    </div>
    
                      </div>
    
                    <div class="input-group input-group-sm mb-3">
                      <span class="input-group-text">{{ __('Answer Description') }}</span>
                      <input
                        type="text"
                        class="form-control"
                        name="ansDesc"
                        placeholder="{{ __('Enter the content that is displayed under this navbar.') }}"
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
                        >{{ __('Page') }} <span class="noofpages">${pageNo}</span> - {{ __('Statement') }}</span>
    
                        <div>
                        <button type="button" class="btn btn-sm btn-primary mx-1" onclick="createPageForm('${formType}')">
                      {{ __('Add Question') }}
                    </button>
                    <button type="button" onclick="createStatementPageForm('${formType}')" class="btn btn-sm btn-secondary mx-1">
                      {{ __('Add Statement') }}
                    </button>
    
                      <button type="button" onclick="removePage('${pageNo}', '${formType}')" class="btn btn-sm btn-danger">
                        {{ __('Remove') }}
                      </button>
                      </div>
                    </div>
                    <div class="input-group input-group-sm mb-3">
                      <span class="input-group-text">{{ __('Statement Title') }}</span>
                      <input
                        type="text"
                        class="form-control"
                        name="sTitle"
                        placeholder="{{ __('Enter a statement title') }}"
                      />
                      <input type="hidden" name="qtype" value="statement">
                    </div>
                    <div class="input-group input-group-sm mb-3">
                      <span class="input-group-text">{{ __('Statement Content') }}</span>
                      <input
                        type="text"
                        class="form-control"
                        name="sContent"
                        placeholder="{{ __('Enter a statement content') }}"
                      />
                    </div>
                    <div class="input-group input-group-sm mb-3">
                      <span class="input-group-text">{{ __('Additional Content') }}</span>
                      <select
                        class="form-select"
                        aria-label="Default select example"
                        name="embeddedVideo"
                      >
                        <option value="embeddedVideo">{{ __('Embedded Video URL') }}</option>
                      </select>
                    </div>
                    <div id="optionContainer">
                      <div class="input-group input-group-sm mb-3">
                      <span class="input-group-text">{{ __('Embedded Video URL') }}</span>
                      <input
                        type="text"
                        class="form-control"
                        name="videoUrl"
                        placeholder="{{ __('Enter the URL of an Embedded Video (Youtube)') }}"
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
                            url: "/delete-training-module",
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



            }


            //editing training module

            function editTrainingModule(id) {

                trainingModId.value = id

                function createMultipleChoicePageEditForm(obj, index) {

                    let cont = `<form class="p-2 trainingForms my-3" id="formid${index}" style="border: 0.5px solid rgb(223, 223, 223); border-radius: 6px;">
                      <div class="pageQuestion my-3">
                        <div class="d-flex justify-content-between my-2">
                          <span class="badge rounded-pill text-bg-primary my-2">{{ __('Page') }} <span class="noofpages"></span> - {{ __('Question') }}</span>
                          <div>
                    <button type="button" class="btn btn-sm btn-primary mx-1" onclick="createPageForm('editforms')">
                      {{ __('Add Question') }}
                    </button>
                    <button type="button" onclick="createStatementPageForm('editforms')" class="btn btn-sm btn-secondary mx-1">
                      {{ __('Add Statement') }}
                    </button>
                    <button type="button" onclick="removePage('${index}', 'editforms')" class="btn btn-sm btn-danger">
                            {{ __('Remove') }}
                          </button>
                  </div>
                          
                        </div>
                        <div class="input-group input-group-sm mb-3">
                          <span class="input-group-text">Question</span>
                          <input type="text" class="form-control" name="question" value="${obj.question}" placeholder="{{ __('Enter a question') }}">
                        </div>
                        <div class="input-group input-group-sm mb-3">
                          <span class="input-group-text">{{ __('Question Type') }}</span>
                          <select class="form-select" aria-label="Default select example" value="${obj.qtype}" name="qtype" id="questionType${index}" onchange="changeQuestionType(this,'${index}')">
                            <option value="multipleChoice" ${obj.qtype === 'multipleChoice' ? 'selected' : ''}>{{ __('Multiple Choice') }}</option>
                            <option value="trueFalse" ${obj.qtype === 'trueFalse' ? 'selected' : ''}>{{ __('True/False') }}</option>
                          </select>
                        </div>
                        <div id="optionContainer${index}">
                          <div class="input-group input-group-sm mb-3">
                            <span class="input-group-text">{{ __('Option 1:') }}</span>
                            <input type="text" class="form-control" name="option1" value="${obj.option1}" placeholder="{{ __('Enter as answer option') }}">
                          </div>
                          <div class="input-group input-group-sm mb-3">
                            <span class="input-group-text">{{ __('Option 2:') }}</span>
                            <input type="text" class="form-control" name="option2" value="${obj.option2}" placeholder="{{ __('Enter as answer option') }}">
                          </div>
                          <div class="input-group input-group-sm mb-3">
                            <span class="input-group-text">{{ __('Option 3:') }}</span>
                            <input type="text" class="form-control" name="option3" value="${obj.option3}" placeholder="{{ __('Enter as answer option') }}">
                          </div>
                          <div class="input-group input-group-sm mb-3">
                            <span class="input-group-text">{{ __('Option 4:') }}</span>
                            <input type="text" class="form-control" name="option4" value="${obj.option4}" placeholder="{{ __('Enter as answer option') }}">
                          </div>
                          <div class="input-group input-group-sm mb-3">
                            <span class="input-group-text">{{ __('Correct Option') }}</span>
                            <select class="form-select" aria-label="Default select example" name="correctOption">
                              <option value="option1" ${obj.correctOption === 'option1' ? 'selected' : ''}>{{ __('Option 1') }}</option>
                              <option value="option2" ${obj.correctOption === 'option2' ? 'selected' : ''}>{{ __('Option 2') }}</option>
                              <option value="option3" ${obj.correctOption === 'option3' ? 'selected' : ''}>{{ __('Option 3') }}</option>
                              <option value="option4" ${obj.correctOption === 'option4' ? 'selected' : ''}>{{ __('Option 4') }}</option>
                            </select>
                          </div>
    
                        </div>
    
                        <div class="input-group input-group-sm mb-3">
                          <span class="input-group-text">{{ __('Answer Description') }}</span>
                          <input type="text" class="form-control" name="ansDesc" value="${obj.ansDesc}" placeholder="{{ __('Enter the content that is displayed under this navbar.') }}">
                        </div>
                      </div>
                    </form>`;
                    let editForms = document.getElementById('editforms');
                    editForms.innerHTML += cont;
                    // console.log(obj.qtype);
                    sortNoOfPages('editforms');
                }

                function createStatementPageEditForm(obj, index) {
                    let cont = `<form class="p-2 trainingForms my-3" id="formid${index}" style="border: 0.5px solid rgb(223, 223, 223); border-radius: 6px;">
                      <div class="pageQuestion my-3">
                        <div class="d-flex justify-content-between my-2">
                          <span class="badge rounded-pill text-bg-primary my-2">{{ __('Page') }} <span class="noofpages"></span> - {{ __('Statement') }}</span>
                        <div>
                          <button type="button" class="btn btn-sm btn-primary mx-1" onclick="createPageForm('editforms')">
                      {{ __('Add Question') }}
                    </button>
                    <button type="button" onclick="createStatementPageForm('editforms')" class="btn btn-sm btn-secondary mx-1">
                      {{ __('Add Statement') }}
                    </button>
                          <button type="button" onclick="removePage('${index}', 'editforms')" class="btn btn-sm btn-danger">
                            {{ __('Remove') }}
                          </button>
                          </div>
                        </div>
                        <div class="input-group input-group-sm mb-3">
                          <span class="input-group-text">{{ __('Statement Title') }}</span>
                          <input type="text" class="form-control" name="sTitle" value="${obj.sTitle}" placeholder="{{ __('Enter a statement title') }}">
                          <input type="hidden" name="qtype" value="statement">
                        </div>
                        <div class="input-group input-group-sm mb-3">
                          <span class="input-group-text">{{ __('Statement Content') }}</span>
                          <input type="text" class="form-control" name="sContent" value="${obj.sContent}" placeholder="{{ __('Enter a statement content') }}">
                        </div>
                        <div class="input-group input-group-sm mb-3">
                          <span class="input-group-text">{{ __('Additional Content') }}</span>
                          <select class="form-select" aria-label="Default select example" name="embeddedVideo">
                            <option value="embeddedVideo">{{ __('Embedded Video URL') }}</option>
                          </select>
                        </div>
                        <div id="optionContainer">
                          <div class="input-group input-group-sm mb-3">
                            <span class="input-group-text">{{ __('Embedded Video URL') }}</span>
                            <input type="text" class="form-control" name="videoUrl" value="${obj.videoUrl}" placeholder="{{ __('Enter the URL of an Embedded Video (Youtube)') }}">
                          </div>
                        </div>
                      </div>
                    </form>`;
                    let editForms = document.getElementById('editforms');
                    editForms.innerHTML += cont;
                    // console.log(obj.qtype);
                    sortNoOfPages('editforms');
                }

                function createTrueFalsePageEditForm(obj, index) {
                    let cont = `<form class="p-2 trainingForms my-3" id="formid${index}" style="border: 0.5px solid rgb(223, 223, 223); border-radius: 6px;">
                      <div class="pageQuestion my-3">
                        <div class="d-flex justify-content-between my-2">
                          <span class="badge rounded-pill text-bg-primary my-2">{{ __('Page') }} <span class="noofpages"></span> - {{ __('Question') }}</span>
                        <div>
                          <button type="button" class="btn btn-sm btn-primary mx-1" onclick="createPageForm('editforms')">
                      {{ __('Add Question') }}
                    </button>
                    <button type="button" onclick="createStatementPageForm('editforms')" class="btn btn-sm btn-secondary mx-1">
                      {{ __('Add Statement') }}
                    </button>
                          <button type="button" onclick="removePage('${index}', 'editforms')" class="btn btn-sm btn-danger">
                            {{ __('Remove') }}
                          </button>
                          </div>
                        </div>
                        <div class="input-group input-group-sm mb-3">
                          <span class="input-group-text">Question</span>
                          <input type="text" class="form-control" name="question" value="${obj.question}" placeholder="{{ __('Enter a question') }}">
                        </div>
                        <div class="input-group input-group-sm mb-3">
                          <span class="input-group-text">{{ __('Question Type') }}</span>
                          <select class="form-select" aria-label="Default select example" name="qtype" id="questionType${index}" onchange="changeQuestionType(this,'${index}')">
                            <option value="multipleChoice" ${obj.qtype === 'multipleChoice' ? 'selected' : ''}>{{ __('Multiple Choice') }}</option>
                            <option value="trueFalse" ${obj.qtype === 'trueFalse' ? 'selected' : ''}>{{ __('True/False') }}</option>
                          </select>
                        </div>
                        <div id="optionContainer${index}">
                          <div class="input-group input-group-sm mb-3">
                            <span class="input-group-text">{{ __('Answer') }}</span>
                            <select class="form-select" aria-label="Default select example" name="correctOption">
                              <option value="true" ${obj.correctOption === 'true' ? 'selected' : ''}>{{ __('True') }}</option>
                              <option value="false" ${obj.correctOption === 'false' ? 'selected' : ''}>{{ __('False') }}</option>
                            </select>
                          </div>
                        </div>
    
                        <div class="input-group input-group-sm mb-3">
                          <span class="input-group-text">{{ __('Answer Description') }}</span>
                          <input type="text" class="form-control" name="ansDesc" value="${obj.ansDesc}" placeholder="{{ __('Enter the content that is displayed under this navbar.') }}">
                        </div>
                      </div>
                    </form>`;
                    let editForms = document.getElementById('editforms');
                    editForms.innerHTML += cont;
                    // console.log(obj.qtype);
                    sortNoOfPages('editforms');
                }


                $.get({
                    url: `/get-training-module/${id}`,
                    success: function(resJson) {
                        // console.log(resJson);

                        document.getElementById('editforms').innerHTML = '';
                        // const resJson = JSON.parse(res);
                        editModuleName.value = resJson.name;
                        $('#editModuleForm input.mPassingScore').val(resJson.passing_score);
                        // editmPassingScore.value = resJson.passing_score;
                        editCategory.value = resJson.category;
                        // editLang.value = resJson.module_language;
                        // editCoverImageFile.value = resJson.cover_image;
                        $('#editModuleForm input.mCompTime').val(resJson.estimated_time);
                        // editMCompTime.value = resJson.estimated_time;

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
        <script>
            $('#t_moduleSearch').on('input', function() {
                var searchValue = $(this).val().toLowerCase(); // Get the search value and convert it to lowercase

                // Loop through each template card
                $('.t_modules').each(function() {
                    var templateName = $(this).find('.fw-semibold').text()
                        .toLowerCase(); // Get the template name and convert it to lowercase

                    // If the template name contains the search value, show the card; otherwise, hide it
                    if (templateName.includes(searchValue)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });
        </script>

        <script>
            $('#newGamifiedTrainingModal').on('hidden.bs.modal', function() {
                // Pause the video
                const video = $('#newGamifiedTrainingModal video').get(0); // Get the video element
                if (video) {
                    video.pause(); // Pause the video
                }
            });

            $('#editGamifiedTrainingModuleModal').on('hidden.bs.modal', function() {
                // Pause the video
                const video = $('#editGamifiedTrainingModuleModal video').get(0); // Get the video element
                if (video) {
                    video.pause(); // Pause the video
                }
            });

            function fetchVideoUrl() {
                const videourl = document.getElementById('gamified_training_video_url').value;
                const urlPattern = /^(https?:\/\/)?(?!.*(youtube\.com|youtu\.?be)).*\.mp4$/;
                if (!urlPattern.test(videourl)) {
                    alert('Please enter a valid video URL.');
                    return;
                }
                $("#game_training_video_preview source").attr("src", videourl);
                $("#gameTrainingVideoPlayer")[0].load();
                $("#game_training_video_preview").show();
                $("#gamified_questions_container").show();
                // Proceed with further processing if the URL is valid
            }

            function addMoreGamifiedTrainingQues(btn) {

                const questionFields = $(btn).parent().parent().clone();
                questionFields.find('input').val('');
                $(btn).parent().parent().after(questionFields);

                const container = $(btn).parent().parent().parent();
                checkIfItsLastQuestion(container);
            }

            function deleteGamifiedTrainingQues(btn) {
                const container = $(btn).parent().parent().parent();
                $(btn).parent().parent().remove();
                checkIfItsLastQuestion(container);
            }

            function checkIfItsLastQuestion(container) {
                const deleteBtns = $(container).find('.deleteQuesBtn');
                // console.log(deleteBtns.length);
                if (deleteBtns.length == 1) {
                    deleteBtns.hide();
                } else {
                    deleteBtns.show();
                }
            }

            function saveGamifiedQues() {
                let error = false;
                const questions = [];
                const questionContainers = $('#gamified_questions_container .gamified_training_question');
                questionContainers.each(function() {
                    const time = $(this).find('.time').val();
                    const timeParts = time.split(':');
                    const timeInSeconds = parseInt(timeParts[0], 10) * 60 + parseInt(timeParts[1], 10);
                    const question = $(this).find('.question').val();
                    const option1 = $(this).find('.option1').val();
                    const option2 = $(this).find('.option2').val();
                    const option3 = $(this).find('.option3').val();
                    const option4 = $(this).find('.option4').val();
                    const answer = parseInt($(this).find('.answer').val(), 10);
                    if (question.trim() === '' || option1.trim() === '' || option2.trim() === '' || option3.trim() ===
                        '' || option4.trim() === '' || isNaN(timeInSeconds)) {
                        error = true;
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Please fill all the fields in the question.',
                        });
                        return;
                    }
                    questions.push({
                        time: timeInSeconds,
                        question,
                        options: [option1, option2, option3, option4],
                        answer
                    });
                });

                if (error) {
                    console.log('something went wrong!')
                    return;
                }
                const videoUrl = document.getElementById('gamified_training_video_url').value;
                if (!videoUrl) {

                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Please enter a valid video URL.',
                    });
                    return;
                }


                const final_gamified_training = {
                    videoUrl,
                    questions
                }
                save_gamified_training_to_db(final_gamified_training);
            }

            function save_gamified_training_to_db(final_gamified_training) {
                const requiredInputs = $('#gamified_training_form input.required');
                let allFilled = true;
                requiredInputs.each(function() {
                    if (!$(this).val()) {
                        allFilled = false;
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: `Please fill ${$(this).data('name')}.`,
                        });
                        return;
                    }


                });
                if (!allFilled) {
                    return;
                }

                const hiddenInput = document.getElementById('gamifiedJsonData');
                hiddenInput.value = JSON.stringify(final_gamified_training);

                // Submit the form programmatically
                const addGamifiedTrainingSubmitBtn = document.getElementById('addGamifiedTrainingSubmitBtn');
                addGamifiedTrainingSubmitBtn.click(); // This will trigger form submission


            }



            function isTimeValid(input) {
                const videoDuration = document.getElementById('gameTrainingVideoPlayer').duration;

                if (input.value) {
                    const timePattern = /^([0-5]?[0-9]):([0-5][0-9])$/;
                    if (!timePattern.test(input.value)) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Please enter a valid time in MM:SS format.',
                        });
                        input.value = '';
                        input.classList.add('is-invalid');
                        return;
                    }

                    const timeParts = input.value.split(':');
                    const inputTimeInSeconds = parseInt(timeParts[0], 10) * 60 + parseInt(timeParts[1], 10);

                    if (inputTimeInSeconds > videoDuration) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Input time cannot be greater than video duration.',
                        });
                        input.value = '';
                        input.classList.add('is-invalid');
                    } else {
                        input.classList.remove('is-invalid');
                    }
                }
            }

            //////editing gamified training module/////

            function editGamifiedTrainingModule(id) {

                $.get({
                    url: `/get-training-module/${id}`,
                    success: function(resJson) {

                        $('#editGamified_training_form input[name="module_name"]').val(resJson.name);
                        $('#editGamified_training_form input[name="passing_score"]').val(resJson.passing_score);
                        $('#editGamified_training_form select[name="category"]').val(resJson.category);
                        $('#editGamified_training_form input[name="completion_time"]').val(resJson.estimated_time);
                        $('#gamifiedTrainingId').val(resJson.id);

                        createGamifiedQuestions(resJson.json_quiz);

                    }
                })

            }

            function createGamifiedQuestions(ques) {
                const questions = JSON.parse(ques);
                const questionContainer = $('#edit_gamified_questions_container');
                questionContainer.html('');
                $('#edit_game_training_video_preview source').attr('src', questions.videoUrl);
                $('#edit_gamified_training_video_url').val(questions.videoUrl);
                $('#editGameTrainingVideoPlayer')[0].load();
                $('#edit_game_training_video_preview').show();
                $('#edit_gamified_questions_container').show();
                questions.questions.forEach((question, index) => {
                    const questionHtml = `
                <div class="gamified_training_question border px-3 my-3">
                  <div class="d-flex gap-2 justify-content-end my-3">
                      <button type="button" class="btn btn-primary btn-sm btn-wave"
                          onclick="addMoreGamifiedTrainingQues(this)">Add More</button>
                      <button type="button" class="btn btn-danger deleteQuesBtn btn-sm btn-wave"
                          onclick="deleteGamifiedTrainingQues(this)" style="display: none;">Delete</button>
                  </div>

                  <div class="input-group input-group-sm mb-3">
                    <span class="input-group-text">Time</span>
                    <input type="text" class="form-control time" value="${String(Math.floor(question.time / 60)).padStart(2, '0')}:${String(question.time % 60).padStart(2, '0')}" placeholder="Enter time in MM:SS format" onblur="isTimeValid(this)">
                  </div>
                  <div class="input-group input-group-sm mb-3">
                    <span class="input-group-text">Question</span>
                    <input type="text" class="form-control question" value="${question.question}" placeholder="Enter the question">
                  </div>
                  <div class="input-group input-group-sm mb-3">
                    <span class="input-group-text">Option 1</span>
                    <input type="text" class="form-control option1" value="${question.options[0]}" placeholder="Enter option 1">
                  </div>
                  <div class="input-group input-group-sm mb-3">
                    <span class="input-group-text">Option 2</span>
                    <input type="text" class="form-control option2" value="${question.options[1]}" placeholder="Enter option 2">
                  </div>
                  <div class="input-group input-group-sm mb-3">
                    <span class="input-group-text">Option 3</span>
                    <input type="text" class="form-control option3" value="${question.options[2]}" placeholder="Enter option 3">
                  </div>
                  <div class="input-group input-group-sm mb-3">
                    <span class="input-group-text">Option 4</span>
                    <input type="text" class="form-control option4" value="${question.options[3]}" placeholder="Enter option 4">
                  </div>
                  <div class="input-group input-group-sm mb-3">
                    <span class="input-group-text">Answer</span>
                    <select class="form-select answer" aria-label="Default select example">
                      <option value="0" ${question.answer === 0 ? 'selected' : ''}>Option 1</option>
                      <option value="1" ${question.answer === 1 ? 'selected' : ''}>Option 2</option>
                      <option value="2" ${question.answer === 2 ? 'selected' : ''}>Option 3</option>
                      <option value="3" ${question.answer === 3 ? 'selected' : ''}>Option 4</option>
                    </select>
                  </div>
                  
                </div>
                `;
                    questionContainer.append(questionHtml);
                });

            }

            function update_gamified_training_to_db(final_gamified_training) {
                const requiredInputs = $('#editGamified_training_form input.required');
                let allFilled = true;
                requiredInputs.each(function() {
                        if (!$(this).val()) {
                            allFilled = false;
                            Swal.fire({
                                icon: 'error',
                                title: 'Oops...',
                                text: `Please fill ${$(this).data('name')}.`,
                            });
                            return;
                        }

                    }

                );
                if (!allFilled) {
                    return;
                }

                const hiddenInput = document.getElementById('edit_gamifiedJsonData');
                hiddenInput.value = JSON.stringify(final_gamified_training);

                // Submit the form programmatically
                const editGamifiedTrainingSubmitBtn = document.getElementById('editGamifiedTrainingSubmitBtn');
                editGamifiedTrainingSubmitBtn.click(); // This will trigger form submission

            }

            function updateGamifiedQues() {
                let error = false;
                const questions = [];
                const questionContainers = $('#edit_gamified_questions_container .gamified_training_question');
                questionContainers.each(function() {
                    const time = $(this).find('.time').val();
                    const timeParts = time.split(':');
                    const timeInSeconds = parseInt(timeParts[0], 10) * 60 + parseInt(timeParts[1], 10);
                    const question = $(this).find('.question').val();
                    const option1 = $(this).find('.option1').val();
                    const option2 = $(this).find('.option2').val();
                    const option3 = $(this).find('.option3').val();
                    const option4 = $(this).find('.option4').val();
                    const answer = parseInt($(this).find('.answer').val(), 10);
                    if (question.trim() === '' || option1.trim() === '' || option2.trim() === '' || option3
                        .trim() ===
                        '' || option4.trim() === '' || isNaN(timeInSeconds)) {
                        error = true;
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Please fill all the fields in the question.',
                        });
                        return;
                    }
                    questions.push({
                        time: timeInSeconds,
                        question,
                        options: [option1, option2, option3, option4],
                        answer
                    });
                });

                if (error) {
                    console.log('something went wrong!')
                    return;
                }
                const videoUrl = document.getElementById('edit_gamified_training_video_url').value;
                if (!videoUrl) {

                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Please enter a valid video URL.',
                    });
                    return;
                }

                const final_gamified_training = {
                    videoUrl,
                    questions
                }
                console.log(final_gamified_training);
                update_gamified_training_to_db(final_gamified_training);

            }
        </script>

        <script>
            $('#training_type_select').on('change', function() {
                const selectedType = $(this).val();
                let selectedCategory = $('#category_select').val();
                if (typeof selectedCategory === 'undefined') {
                    selectedCategory = 'international';
                }
                console.log("category is : ", selectedCategory);

                if (selectedType == 'games') {
                    window.location.href = `/training-modules?type=${selectedType}`;
                } else {
                    window.location.href = `/training-modules?type=${selectedType}&category=${selectedCategory}`;
                }
            });

            $('#category_select').on('change', function() {
                const selectedCategory = $(this).val();

                let selectedTrainingType = $("#training_type_select").val();


                window.location.href = `/training-modules?category=${selectedCategory}&type=${selectedTrainingType}`;
            });
        </script>
    @endpush

@endsection
