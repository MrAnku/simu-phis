@extends('layouts.app')

@section('title', __('Smishing Campaign') . ' - ' . __('Phishing awareness training program'))

@section('main-content')

    <div class="main-content app-content">
        <div class="container-fluid mt-4">

            <div class="row my-3">
                <div class="col-xxl-4 col-xl-12">
                    <div class="card custom-card">
                        <div class="card-body">
                            <div class="row">
                                <div
                                    class="col-xxl-3 col-xl-2 col-lg-3 col-md-3 col-sm-4 col-4 d-flex align-items-center justify-content-center ecommerce-icon px-0">
                                    <span class="rounded p-3 bg-primary-transparent">
                                        <i class="bx bx-mail-send fs-4"></i>
                                    </span>
                                </div>
                                <div class="col-xxl-9 col-xl-10 col-lg-9 col-md-9 col-sm-8 col-8 px-0">
                                    <div class="mb-2">{{ __('Total Templates') }}</div>
                                    <div class="text-muted mb-1 fs-12">
                                        <span class="text-dark fw-semibold fs-20 lh-1 vertical-bottom">
                                            {{ $templates->count() }}
                                        </span>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-4 col-xl-12">
                    <div class="card custom-card">
                        <div class="card-body">
                            <div class="row">
                                <div
                                    class="col-xxl-3 col-xl-2 col-lg-3 col-md-3 col-sm-4 col-4 d-flex align-items-center justify-content-center ecommerce-icon px-0">
                                    <span class="rounded p-3 bg-secondary-transparent">
                                        <i class="bx bx-mail-send fs-4"></i>
                                    </span>
                                </div>
                                <div class="col-xxl-9 col-xl-10 col-lg-9 col-md-9 col-sm-8 col-8 px-0">
                                    <div class="mb-2">{{ __('Total Sent SMS') }}</div>
                                    <div class="text-muted mb-1 fs-12">
                                        <span class="text-dark fw-semibold fs-20 lh-1 vertical-bottom">
                                            {{ $totalSentCampaigns }} {{ __('Delivered') }}
                                        </span>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-4 col-xl-12">
                    <div class="card custom-card">
                        <div class="card-body">
                            <div class="row">
                                <div
                                    class="col-xxl-3 col-xl-2 col-lg-3 col-md-3 col-sm-4 col-4 d-flex align-items-center justify-content-center ecommerce-icon px-0">
                                    <span class="rounded p-3 bg-warning-transparent">
                                        <i class="bx bx-envelope-open fs-4"></i>
                                    </span>
                                </div>
                                <div class="col-xxl-9 col-xl-10 col-lg-9 col-md-9 col-sm-8 col-8 px-0">
                                    <div class="mb-2">{{ __('Compromised') }}</div>
                                    <div class="text-muted mb-1 fs-12">
                                        <span class="text-dark fw-semibold fs-20 lh-1 vertical-bottom">
                                            {{ $totalCompromised }} {{ __('Compromised') }}
                                        </span>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal"
                data-bs-target="#newCampModal">{{ __('New Campaign') }}</button>

            <div class="row">
                <div class="col-xl-12">
                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">
                                {{ __('Manage Campaign') }}
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>{{ __('Campaign Name') }}</th>
                                            <th>{{ __('Campaign Type') }}</th>
                                            <th>{{ __('Status') }}</th>
                                            <th>{{ __('Employees Group') }}</th>
                                            <th>{{ __('Launch Time') }}</th>
                                            <th>{{ __('Action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($campaigns as $camp)
                                            <tr>
                                                <th>{{ $loop->iteration }}</th>
                                                <th>
                                                    <a href="#" class="text-primary"
                                                        onclick="fetchCampaignDetails('{{ $camp->campaign_id }}', {{ $camp->campaign_type == 'smishing' ? 'true' : 'false' }})"
                                                        data-bs-toggle="modal" data-bs-target="#campaignReportModal">
                                                        {{ $camp->campaign_name }}
                                                    </a>

                                                </th>
                                                <th>
                                                    @if ($camp->campaign_type == 'smishing')
                                                        <span
                                                            class="badge bg-secondary-transparent">{{ __('Only Smishing') }}</span>
                                                    @else
                                                        <span
                                                            class="badge bg-secondary-transparent">{{ __('Smishing & Training') }}</span>
                                                    @endif
                                                </th>
                                                <th>
                                                    @if ($camp->status == 'pending')
                                                        <span
                                                            class="badge bg-warning-transparent">{{ __('Pending') }}</span>
                                                    @elseif($camp->status == 'running')
                                                        <span
                                                            class="badge bg-success-transparent">{{ __('Running') }}</span>
                                                    @else
                                                        <span
                                                            class="badge bg-success-transparent">{{ __('Completed') }}</span>
                                                    @endif

                                                <th>
                                                    {{ $camp->userGroupData->group_name ?? '' }}
                                                </th>
                                                <th>{{ $camp->created_at->format('d/m/Y h:i A') }}</th>
                                                <th>
                                                    <button
                                                        onclick="deleteCampaign('{{ base64_encode($camp->campaign_id) }}')"
                                                        title="Delete Campaign"
                                                        class="btn btn-icon btn-danger-transparent rounded-pill btn-wave">
                                                        <i class="ri-delete-bin-line"></i>
                                                    </button>
                                                </th>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center text-muted">
                                                    {{ __('No smishing campaign found') }}
                                                </td>
                                            </tr>
                                        @endforelse

                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-4">
                                {{-- {{ $allCamps->links() }} --}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- --------------------------------------------Toasts---------------------- --}}

    <x-toast />


    {{-- --------------------------------------- modals ---------------------- --}}

    <!-- new campaign modal -->
    <x-modal id="newCampModal" size="modal-xl" heading="{{ __('New Smishing Campaign') }}">
        <x-smishing.new-campaign 
        :templates="$templates" 
        :websites="$phishingWebsites" 
        :trainingModules="$trainingModules" />
    </x-modal>

    <!-- campaign report modal -->
    {{-- <x-modal id="campaignReportModal" size="modal-fullscreen" heading="{{ __('Campaign Report') }}">
        <x-quish-camp.campaign-report-modal />
    </x-modal>

    <!-- view material modal -->
    <x-modal id="viewMaterialModal" size="modal-dialog-centered modal-lg" heading="{{ __('Phishing Material') }}">
        hello3
    </x-modal>


    <!-- re-schedule campaign modal -->
    <x-modal id="reschedulemodal" size="modal-lg" heading="{{ __('Re-Schedule Campaign') }}">
        hello4
    </x-modal>  --}}




    @push('newcss')
        <style>
            .phone-frame {
                width: 100%;
                overflow-y: scroll;
                height: 250px;
                border-radius: 30px;
                padding: 20px 20px;
                background-color: #fff;
                margin: auto;
                text-align: center;
            }

            .sms-header {
                text-align: center;
                font-size: 14px;
                font-weight: bold;
                margin-bottom: 17px;
            }

            .sms-body {
                background-color: #f3f3ff;
                border-radius: 20px;
                padding: 10px 15px;
                font-size: 14px;
                text-align: left;
                max-width: 230px;
                color: #000;
                word-wrap: break-word;
            }

            .sms-preview-col {
                display: flex;
                justify-content: center;
                padding: 20px;
            }
        </style>
    @endpush

    @push('newscripts')
        <script>
            document.getElementById('days_until_due').addEventListener('input', function() {
                if (this.value > 30) {
                    this.value = 30;
                    alert('The number of days until due cannot exceed 30.');
                }
            });
            let campType = 'smishing';

            function showNext(current_page) {
                if (current_page == 'stepOne') {
                    if ($('#camp_name').val() == '' || campType == '' || $('#users_group').val() == '') {
                        alert("{{ __('All fields are required') }}");
                        return;
                    }

                    $('#quish-mat').attr('disabled', false);
                    $('#quish-mat').click();

                    // if (campType == 'smishing') {

                    // }
                    // if (campType == 'smishing-training') {
                    //     $('#training-mod').attr('disabled', false);
                    //     $('#training-mod').click();
                    // }

                }
                if (current_page == 'stepTwo') {
                    $('#web-mat').attr('disabled', false);
                    $('#web-mat').click();
                }

                if (current_page == 'stepWebsite') {
                    if ($('input[name="quish_material"]:checked').length === 0) {
                        alert('Please select at least one smishing material');
                        return;
                    }
                    if (campType == 'smishing-training') {
                        $('#training-mod').attr('disabled', false);
                        $('#training-mod').click();
                    }
                    if (campType == 'smishing') {
                        reviewForm();
                        $('#review-sub-tab').attr('disabled', false);
                        $('#review-sub-tab').click();
                    }
                }

                if (current_page == 'stepThree') {
                    if ($('input[name="training_module"]:checked').length === 0) {
                        alert("{{ __('Please select at least one training module') }}");
                        return;
                    }
                    reviewForm();
                    $('#review-sub-tab').attr('disabled', false);
                    $('#review-sub-tab').click();
                }
            }

            function showPrevious(current_page) {
                let campType = $('#campaign_type').val();
                if (current_page == 'stepTwo') {
                    $('#camp-name').click();
                }
                if (current_page == 'stepThree') {
                    $('#web-mat').attr('disabled', false);
                        $('#web-mat').click();
                }
                if (current_page == 'stepWebsite') {
                    // if (campType == 'smishing-training') {
                        $('#quish-mat').attr('disabled', false);
                        $('#quish-mat').click();
                    // }
                    // if (campType == 'training') {
                    //     $('#camp-name').click();
                    // }
                }
                if (current_page == 'stepFour') {
                    if (campType == 'smishing-training') {
                        $('#training-mod').attr('disabled', false);
                        $('#training-mod').click();
                    }
                    if (campType == 'smishing') {
                        $('#web-mat').attr('disabled', false);
                        $('#web-mat').click();
                    }
                }
            }

            function reviewForm() {
                let phishingMaterials = $('input[name="quish_material"]:checked').map(function() {
                    return $(this).data('name');
                }).get().join(', ');

                let trainingModules = $('input[name="training_module"]:checked').map(function() {
                    return $(this).data('name');
                }).get().join(', ');

                let reviewData = {
                    "{{ __('Campaign Name') }}": $('#camp_name').val(),
                    "{{ __('Campaign Type') }}": $('#campaign_type option:selected').text().trim(),
                    "{{ __('Employee Group') }}": $('#users_group option:selected').text().trim(),
                    "{{ __('Smishing Materials') }}": phishingMaterials,
                    "{{ __('Smishing Language') }}": $('#quishing_lang option:selected').text().trim(),
                    "{{ __('Phishing Website') }}": $('input[name="website"]:checked').data('name'),
                    "{{ __('Training Modules') }}": trainingModules,
                    "{{ __('Training Language') }}": $('#training_lang option:selected').text().trim(),
                    "{{ __('Days Until Due') }}": $('#days_until_due').val(),
                    "{{ __('Training Type') }}": $('#training_type option:selected').text().trim(),
                    "{{ __('Training Assignment') }}": $('#training_assignment option:selected').text().trim(),
                    "{{ __('Training Category') }}": $('#training_cat option:selected').text().trim(),
                }
                console.log(reviewData);

                let html = '';
                if (campType == 'smishing-training') {
                    for (const [key, value] of Object.entries(reviewData)) {
                        html += `<div class="mb-3 col-lg-4">
                                    <label for="input-label" class="form-label">${key}</label>
                                    <input type="text" class="form-control" value="${value}" disabled="true" readonly="">
                                </div>`;
                    }

                    $('#reviewForm').html(html);
                    return;
                }

                for (const [key, value] of Object.entries(reviewData).slice(0, -6)) {
                    html += `<div class="mb-3 col-lg-4">
                            <label for="input-label" class="form-label">${key}</label>
                            <input type="text" class="form-control" value="${value}" disabled="true" readonly="">
                        </div>`;
                }
                $('#reviewForm').html(html);


            }

            $("#campaign_type").change(function() {
                $('#training-mod').attr('disabled', true);
                $('#quish-mat').attr('disabled', true);
                $('#review-sub-tab').attr('disabled', true);

                if ($(this).val() == 'smishing') {
                    $('#quish-mat').parent().show();
                    $('#training-mod').parent().hide();
                    campType = 'smishing';
                }
                if ($(this).val() == 'training') {
                    $('#training-mod').parent().show();
                    $('#quish-mat').parent().hide();
                    campType = 'training';
                }
                if ($(this).val() == 'smishing-training') {
                    $('#training-mod').parent().show();
                    $('#quish-mat').parent().show();
                    campType = 'smishing-training';
                }

            });
        </script>
        <script>
            let selectedPhishingMaterial = [];

            function selectPhishingMaterial(checkbox) {
                var label = document.querySelector(`label[for="${checkbox.id}"]`);

                if (checkbox.checked) {
                    // Add value to the checkedValues array
                    selectedPhishingMaterial.push(checkbox.value);

                    // Change the text inside the label
                    label.textContent = "{{ __('Attack selected') }}";

                    // Add the classes to the label
                    label.classList.add('bg-primary', 'text-white');
                } else {
                    // Remove value from the checkedValues array
                    selectedPhishingMaterial = selectedPhishingMaterial.filter(value => value !== checkbox.value);

                    // Change the text back to the original
                    label.textContent = "Select this attack";

                    // Remove the classes from the label
                    label.classList.remove('bg-primary', 'text-white');

                    checkbox.blur();
                }

                // Log the updated checkedValues array
                console.log(selectedPhishingMaterial);
            }
        </script>
        <script>
            var selectedTrainings = [];

            function selectTrainingModule(checkbox) {
                var label = document.querySelector(`label[for="${checkbox.id}"]`);

                if (checkbox.checked) {
                    // Add value to the checkedValues array
                    selectedTrainings.push(checkbox.value);

                    // Change the text inside the label
                    label.textContent = "{{ __('Training selected') }}";

                    // Add the classes to the label
                    label.classList.add('bg-primary', 'text-white');
                } else {
                    // Remove value from the checkedValues array
                    selectedTrainings = selectedTrainings.filter(value => value !== checkbox.value);

                    // Change the text back to the original
                    label.textContent = "{{ __('Select this training') }}";

                    // Remove the classes from the label
                    label.classList.remove('bg-primary', 'text-white');

                    checkbox.blur();
                }

                // Log the updated checkedValues array
                console.log(selectedTrainings);
            }
            function selectWebsite(input) {
                $(input).next().text("{{ __('Website selected') }}");
            }
            function deselectWebsite(input) {
                $(input).next().text("{{ __('Select') }}");
            }
        </script>
        <script>
            function submitQForm() {
                const campaignData = {
                    campaign_name: $('#camp_name').val(),
                    campaign_type: $('#campaign_type').val(),
                    employee_group: $('#users_group').val(),
                    smishing_materials: selectedPhishingMaterial,
                    smishing_language: $('#quishing_lang').val(),
                    phishing_website: $('input[name="website"]:checked').val(),
                    training_modules: selectedTrainings,
                    training_language: $('#training_lang').val(),
                    days_until_due: $('#days_until_due').val(),
                    training_type: $('#training_type').val(),
                    training_assignment: $('#training_assignment').val(),
                    training_category: $('#training_cat').val(),
                }

                console.log(campaignData);
                return;

                $.post({
                    url: '/quishing/create-campaign',
                    data: campaignData,
                    success: function(res) {
                        if (res.status === 1) {
                            Swal.fire({
                                title: res.msg,
                                icon: 'success',
                                confirmButtonText: "{{ __('OK') }}"
                            }).then(() => {
                                $('#newCampModal').modal('hide');
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: res.msg,
                                icon: 'error',
                                confirmButtonText: "{{ __('OK') }}"
                            })
                        }
                    }
                })

            }
        </script>
        <script>
            function prepareTrainingHtml(data) {
                let html = '';
                data.forEach(training => {
                    html += `<div class="col-lg-6 t_modules">
                <div class="card custom-card border">
                    <div class="card-header">
                        <div class="d-flex align-items-center w-100">
                            <div class="">
                                <div class="fs-15 fw-semibold">${training.name}</div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body htmlPhishingGrid">
                        <img class="trainingCoverImg" src="/storage/uploads/trainingModule/${training.cover_image}" style="width: 100%;"/>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-center">
                            <div class="fs-semibold fs-14">
                                <input type="checkbox" name="training_module" onclick="selectTrainingModule(this)" data-name="${training.name}"
                                    value="${training.id}" class="btn-check" id="training${training.id}">
                                <label class="btn btn-outline-primary mb-3" for="training${training.id}">Select
                                    this
                                    training</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>`;
                });

                return html;
            }

            function fetchTrainingByCategory(cat, type = 'static_training') {
                $.post({
                    url: '/campaigns/fetch-training-by-category',
                    data: {
                        category: cat,
                        type: type
                    },
                    success: function(res) {
                        // console.log(res)
                        if (res.status !== 1) {
                            Swal.fire({
                                title: res.msg,
                                icon: 'error',
                                confirmButtonText: "{{ __('OK') }}"
                            })
                            return;
                        }
                        if (res.data.length === 0) {
                            $('#trainingModulesCampModal').html(
                                '<div class="text-center py-3">No training modules found</div>');
                            return;
                        }
                        const htmlrows = prepareTrainingHtml(res.data);
                        $('#trainingModulesCampModal').html(htmlrows);
                    }
                })
            }

            $("#training_cat").on('change', function() {

                const cat = $(this).val();
                const type = $('#training_type').val();
                if (type == 'gamified') {
                    fetchTrainingByCategory(cat, type);
                } else {
                    fetchTrainingByCategory(cat);
                }

            })

            $("#training_type").on('change', function() {

                const type = $(this).val();
                const category = $('#training_cat').val();
                if (type == 'gamified') {
                    fetchTrainingByCategory(category, type);
                } else {
                    fetchTrainingByCategory(category);
                }

            })
            let training_page = 2;

            function loadMoreTrainings(btn) {
                const category = $('#training_cat').val();
                btn.disabled = true;
                btn.innerText = 'Loading...'
                $.post({
                    url: '/campaigns/show-more-trainings',
                    data: {
                        page: training_page,
                        category: category
                    },
                    success: function(res) {
                        // console.log(res)
                        if (res.status !== 1) {
                            Swal.fire({
                                title: res.msg,
                                icon: 'error',
                                confirmButtonText: "{{ __('OK') }}"
                            })
                            return;
                        }
                        if (res.data.length === 0) {

                            btn.disabled = true;
                            btn.innerText = "{{ __('No more training modules') }}";
                            return;
                        }
                        const htmlrows = prepareTrainingHtml(res.data);
                        $('#trainingModulesCampModal').append(htmlrows);
                        btn.disabled = false;
                        btn.innerText = 'Show More';
                        training_page++;
                    }
                })
            }


            $('#t_moduleSearch').on('input', function() {
                var searchValue = $(this).val().toLowerCase(); // Get the search value and convert it to lowercase

                clearTimeout($.data(this, 'timer'));
                if (searchValue.length > 2) {
                    var wait = setTimeout(function() {
                        // Call the search function here
                        searchTrainingModule(searchValue);
                    }, 2000);
                    $(this).data('timer', wait);
                } else {
                    if (searchedTrainingOnce) {
                        fetchTrainingByCategory('all');
                        searchedTrainingOnce = false;
                    }
                }
            });

            let searchedTrainingOnce = false;

            function searchTrainingModule(searchValue) {
                $('#trainingSearchSpinner').show();
                searchedTrainingOnce = true;
                // Loop through each template card
                $.post({
                    url: '/campaigns/search-training-module',
                    data: {
                        search: searchValue
                    },
                    success: function(res) {
                        if (res.status === 1) {
                            // Clear existing results
                            $('#trainingSearchSpinner').hide();
                            $('#trainingModulesCampModal').empty()
                            // Append new results
                            const htmlrows = prepareTrainingHtml(res.data);
                            $('#trainingModulesCampModal').append(htmlrows);
                        } else {
                            Swal.fire({
                                title: res.msg,
                                icon: 'error',
                                confirmButtonText: "{{ __('OK') }}"
                            });
                        }
                    }
                });
            }


            // Event listener for input field change
            $('#templateSearch').on('input', function() {
                var searchValue = $(this).val().toLowerCase(); 

                clearTimeout($.data(this, 'timer'));
                if (searchValue.length > 2) {
                    var wait = setTimeout(function() {
                        // Call the search function here
                        searchPhishingMaterial(searchValue);
                    }, 2000);
                    $(this).data('timer', wait);
                } else {
                    if (phishing_materials_before_search !== '') {
                        $('#phishingEmailsCampModal').html(phishing_materials_before_search)
                        phishing_materials_before_search = '';
                    }
                }
            });

            let phishing_materials_before_search = ''

            function searchPhishingMaterial(searchValue) {
                $('#phishEmailSearchSpinner').show();
                phishing_materials_before_search = $('#phishingEmailsCampModal').html();
                // Loop through each template card
                $.post({
                    url: '/quishing/search-quishing-material',
                    data: {
                        search: searchValue
                    },
                    success: function(res) {
                        if (res.status === 1) {
                            // Clear existing results
                            $('#phishEmailSearchSpinner').hide();
                            $('#phishingEmailsCampModal').empty()
                            // Append new results
                            const htmlrows = prepareHtml(res.data);
                            $('#phishingEmailsCampModal').append(htmlrows);
                        } else {
                            Swal.fire({
                                title: res.msg,
                                icon: 'error',
                                confirmButtonText: "{{ __('OK') }}"
                            });
                        }
                    }
                });
            }

            function prepareHtml(data) {
                let html = '';
                data.forEach(email => {

                html += `<div class="col-lg-6 email_templates border my-2">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="d-flex align-items-center w-100">
                            <div class="">
                                <div class="fs-15 fw-semibold">${email.name}</div>
                                    ${email.company_id == 'default' ? '(Default)' : ''}</div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body htmlPhishingGrid" style="background: white;">
                        <iframe class="phishing-iframe" src="/${email.file.replace("public", "storage")}" style="width: 100%;
                                height: 300px;
                            "></iframe>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-center">
                            <div>
                                <button type="button"
                                    onclick="showMaterialDetails(this, '${email.name}', '${email.email_subject}', '${email.website}', '${email.sender_profile}')"
                                    class="btn btn-outline-primary btn-wave waves-effect waves-light mx-2">
                                    View
                                </button>
                            </div>
                            <div class="fs-semibold fs-14">
                                <input 
                                    type="checkbox" 
                                    name="quish_material" 
                                    class="btn-check"
                                    onclick="selectPhishingMaterial(this)" 
                                    data-name="${email.name}" 
                                    id="pm${email.id}" 
                                    value="${email.id}"
                                >
                                <label class="btn btn-outline-primary mb-3" for="pm${email.id}">Select this attack</label>

                            </div>
                        </div>
                    </div>
                </div>
            </div>`;

                });

                return html;
            }

            let phishing_emails_page = 2;

            function loadMoreQuishingEmails(btn) {
                btn.disabled = true;
                btn.innerText = "{{ __('Loading...') }}"
                $.post({
                    url: '/quishing/show-more-quishing-emails',
                    data: {
                        page: phishing_emails_page
                    },
                    success: function(res) {
                        // console.log(res)
                        if (res.status !== 1) {
                            Swal.fire({
                                title: res.msg,
                                icon: 'error',
                                confirmButtonText: "{{ __('OK') }}"
                            })
                            return;
                        }
                        if (res.data.length === 0) {

                            btn.disabled = true;
                            btn.innerText = "{{ __('No more phishing materials') }}";
                            return;
                        }
                        const htmlrows = prepareHtml(res.data);
                        $('#phishingEmailsCampModal').append(htmlrows);
                        btn.disabled = false;
                        btn.innerText = 'Show More';
                        phishing_emails_page++;
                    }
                })
            }

            function deleteCampaign(id) {
                Swal.fire({
                    title: "{{ __('Are you sure?') }}",
                    text: "{{ __('You want to delete this campaign!') }}",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: "{{ __('Yes, delete it!') }}",
                    cancelButtonText: "{{ __('Cancel') }}"
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post({
                            url: '/quishing/delete-campaign',
                            data: {
                                campid: id
                            },
                            success: function(res) {
                                if (res.status === 1) {
                                    Swal.fire({
                                        title: res.msg,
                                        icon: 'success',
                                        confirmButtonText: "{{ __('OK') }}"
                                    }).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        title: res.msg,
                                        icon: 'error',
                                        confirmButtonText: "{{ __('OK') }}"
                                    });
                                }
                            }
                        })
                    }
                })
            }
        </script>
        <script>
            $('#campaignReportModal').on('shown.bs.modal', function() {
                $(this).find('.nav-item .nav-link').removeClass('active'); // Remove active class from all tabs
                $(this).find('.nav-item:first-child .nav-link').addClass(
                    'active'); // Set the first tab as active
                $(this).find('.tab-pane').removeClass('show active'); // Remove active state from tab content
                $(this).find('.tab-pane:first-child').addClass('show active'); // Set first tab content as active
            });

            //reporting script
            function fetchCampaignDetails(campId, isQuishing) {
                if (isQuishing) {
                    $(".smishing-training-detail").hide();

                } else {
                    $(".smishing-training-detail").show();
                }

                $.post({
                    url: '/quishing/fetch-campaign-details',
                    data: {
                        campid: campId
                    },
                    success: function(res) {
                        console.log(res);
                        if (res.data) {
                            renderData(res.data);
                            renderLiveData(res.data.camp_live);
                            if (res.data.campaign_type == 'smishing-training') {
                                renderTrainingData(res.data)
                                renderTrainingDataLive(res.data)
                            }
                        } else {
                            Swal.fire({
                                title: res.msg,
                                icon: 'error',
                                confirmButtonText: "{{ __('OK') }}"
                            })
                        }
                    }
                })


            }

            function renderData(data) {

                const campaignDetail = `<tr>
                                    <td>${data.campaign_name}</td>
                                    <td>
                                        <span class="badge bg-secondary-transparent">${data.status.charAt(0).toUpperCase() + data.status.slice(1)}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="mx-1">${data.camp_live.length}</span>
                                            <i class="bx bx-check-circle text-${data.camp_live.length === 0 ? 'danger' : 'success'} fs-25"></i>
                                        </div>
                                        
                                        
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="mx-1">${data.camp_live.filter(item => item.sent === "1").length}</span>
                                            <i class="bx bx-check-circle text-${data.camp_live.filter(item => item.sent === "1").length === 0 ? 'danger' : 'success'} fs-25"></i>
                                        </div>

                                        
                                        
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="mx-1">${data.camp_live.filter(item => item.mail_open === "1").length}</span>
                                            <i class="bx bx-check-circle text-${data.camp_live.filter(item => item.mail_open === "1").length === 0 ? 'danger' : 'success'} fs-25"></i>
                                        </div>
                                        
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="mx-1">${data.camp_live.filter(item => item.qr_scanned === "1").length}</span>
                                            <i class="bx bx-check-circle text-${data.camp_live.filter(item => item.qr_scanned === "1").length === 0 ? 'danger' : 'success'} fs-25"></i>
                                        </div>
                                        
                                        
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="mx-1">${data.camp_live.filter(item => item.compromised === "1").length}</span>
                                            <i class="bx bx-check-circle text-${data.camp_live.filter(item => item.compromised === "1").length === 0 ? 'danger' : 'success'} fs-25"></i>
                                        </div>

                                       
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="mx-1">${data.camp_live.filter(item => item.email_reported === "1").length}</span>
                                            <i class="bx bx-check-circle text-${data.camp_live.filter(item => item.email_reported === "1").length === 0 ? 'danger' : 'success'} fs-25"></i>
                                        </div>
                                    </td>
                                </tr>`;

                $('#qcampdetail').html('');
                $('#qcampdetail').html(campaignDetail);
            }

            function renderLiveData(users) {
                let usersData = '';
                users.forEach(user => {
                    usersData += `<tr>
                                    <td>
                                        ${user.user_name}
                                    </td>
                                    <td>${user.user_email}</td>
                                    <td>
                                        <span class="badge bg-${user.sent == '1' ? 'success' : 'warning'}-transparent">${user.sent == '1' ? 'Sent' : 'Pending'}</span>
                                        
                                    </td>
                                    <td>
                                        <span class="badge bg-${user.mail_open == '1' ? 'success' : 'danger'}-transparent">${user.mail_open == '1' ? 'Yes' : 'No'}</span>
                                        
                                    </td>
                                    <td>
                                        <span class="badge bg-${user.qr_scanned == '1' ? 'success' : 'danger'}-transparent">${user.qr_scanned == '1' ? 'Yes' : 'No'}</span>
                                    </td>
                                    <td>

                                        <span class="badge bg-${user.compromised == '1' ? 'success' : 'danger'}-transparent">${user.compromised == '1' ? 'Yes' : 'No'}</span>
                                    
                                    </td>
                                    <td>
                                        <span class="badge bg-${user.email_reported == '1' ? 'success' : 'danger'}-transparent">${user.email_reported == '1' ? 'Yes' : 'No'}</span>

                                        
                                    </td>
                                </tr>`;


                });

                $('#qcampdetailLive').html('');
                $('#qcampdetailLive').html(usersData);
            }

            function renderTrainingData(data) {
                const campaignDetail = `<tr>
                                    <td>${data.campaign_name}</td>
                                    <td>
                                        <span class="badge bg-secondary-transparent">${data.status.charAt(0).toUpperCase() + data.status.slice(1)}</span>
                                        
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="mx-1">${data.camp_live.length}</span>
                                            <i class="bx bx-check-circle text-${data.camp_live.length === 0 ? 'danger' : 'success'} fs-25"></i>
                                        </div>
                                        
                                        
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="mx-1">${data.camp_live.filter(item => item.training_assigned === "1").length}</span>
                                            <i class="bx bx-check-circle text-${data.camp_live.filter(item => item.training_assigned === "1").length === 0 ? 'danger' : 'success'} fs-25"></i>
                                        </div>

                                        
                                        
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary-transparent">${data.training_type == 'static_training' ? 'Static Training' : (data.training_type == 'gamified' ? 'Gamified Training' : 'AI Training')}</span>
                                        
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary-transparent">${data.training_lang}</span>
                                        
                                        
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="mx-1">${data.trainingData ? data.trainingData.filter(item => item.completed === "1").length : 0}</span>
                                            <i class="bx bx-check-circle text-${data.trainingData ? 'success' : 'danger'} fs-25"></i>
                                        </div>

                                       
                                    </td>
                                    
                                </tr>`;

                $('#qcampTrainingData').html('');
                $('#qcampTrainingData').html(campaignDetail);

            }

            function renderTrainingDataLive(data) {
                if (data.trainingAssigned.length === 0) {
                    $('#qcampTrainingDataLive').html(
                        '<tr><td colspan="7" class="text-center text-muted">No training assigned</td></tr>');
                    return;
                }
                let trainingData = '';
                data.trainingAssigned.forEach(user => {
                    trainingData += `<tr>
                                    <td>${user.user_name}</td>
                                    <td>${user.user_email}</td>
                                    <td>
                                        <span class="badge bg-primary">${user.training_data.name}</span>
                                        
                                    </td>
                                    <td>${user.assigned_date}</td>
                                    <td>${user.personal_best}%</td>
                                    <td>${user.training_data.passing_score}%</td>
                                    <td>
                                        ${new Date(user.training_due_date) > new Date() ? '<span class="badge bg-success-transparent">In Training Period</span>' : '<span class="badge bg-danger-transparent">Overdue</span>'}
                                    </td>
                                </tr>`;
                });
                $('#qcampTrainingDataLive').html('');
                $('#qcampTrainingDataLive').html(trainingData);
            }
        </script>
    @endpush

@endsection
