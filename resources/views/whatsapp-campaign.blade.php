@extends('layouts.app')

@section('title', __('WhatsApp Campaign') . ' - ' . __('Phishing awareness training program'))

@section('main-content')

    <div class="main-content app-content">
        <div class="container-fluid mt-4">

            <div class="d-flex justify-content-between" style="gap: 10px;">
                <div class="d-flex" style="gap: 10px;">
                    <div>
                        <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal"
                            data-bs-target="#newWhatsappCampaignModal">{{ __('New Whatsapp Campaign') }}</button>
                    </div>
                    <div>
                        <button type="button" class="btn btn-secondary mb-3" data-bs-toggle="modal"
                            data-bs-target="#templatesModal">{{ __('Available Templates') }}</button>
                    </div>
                </div>

                <div>
                    <button class="btn btn-teal-light btn-border-start mx-2 mb-3"
                        onclick="syncTemps(this)">{{ __('Sync Templates') }}</button>

                    <button class="btn btn-purple-light btn-border-start mb-3" data-bs-toggle="modal"
                        data-bs-target="#newtemplatesModal">{{ __('Request New Template') }}</button>
                    <button class="btn btn-secondary-light btn-border-start ms-2 mb-3" data-bs-toggle="modal"
                        data-bs-target="#updateConfigModal">{{ __('Update Config') }}</button>
                </div>


            </div>


            <div class="row">
                <div class="col-xl-12">
                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">
                                {{ __('All WhatsApp Campaigns') }}
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Campaign Name') }}</th>
                                            <th>{{ __('Campaign Type') }}</th>
                                            <th>{{ __('Training') }}</th>
                                            <th>{{ __('Template Name') }}</th>
                                            <th>{{ __('Employee Group') }}</th>
                                            <th>{{ __('Launch Date') }}</th>
                                            <th>{{ __('Action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($campaigns as $campaign)
                                            <tr>
                                                <td>
                                                    <a href="#" class="text-primary"
                                                        onclick="fetchCampaignDetails('{{ $campaign->camp_id }}')"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#campaignReportModal">{{ $campaign->camp_name }}</a>

                                                </td>
                                                <td class="fst-italic">{{ $campaign->camp_type }}</td>
                                                <td>
                                                    @if ($campaign->trainingData?->name !== null)
                                                        {{ $campaign->trainingData->name }}
                                                    @else
                                                        <span
                                                            class="text-muted"><small>{{ __('Simulated without training') }}</small></span>
                                                    @endif

                                                </td>
                                                <td><span class="badge bg-info">{{ $campaign->template_name }}</span></td>
                                                <td>{{ $campaign->user_group_name ?? 'N/A' }}</td>
                                                <td>
                                                    {{ \Carbon\Carbon::parse($campaign->created_at)->format('d-M-Y') }}

                                                </td>
                                                <td>
                                                    <button
                                                        class="btn btn-icon btn-danger-transparent rounded-pill btn-wave"
                                                        onclick="deleteCamp(`{{ $campaign->camp_id }}`)">
                                                        <i class="ri-delete-bin-line"></i>
                                                    </button>


                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center">{{ __('No records found') }}</td>
                                            </tr>
                                        @endforelse



                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-4">
                                {{ $campaigns->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- -------------------Modals------------------------ --}}

    <!-- new template modal -->
    <div class="modal fade" id="newtemplatesModal" tabindex="-1" aria-labelledby="exampleModalLgLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">{{ __('Request New Template') }}</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('whatsapp.newTemplate') }}" method="post">
                        @csrf

                        <div class="mb-3">
                            <label for="input-label" class="form-label">{{ __('Template name') }}<sup
                                    class="text-danger">*</sup></label>
                            <input type="text" name="temp_name" class="form-control" id="temp_name"
                                placeholder="{{ __('Enter a unique name for your template i.e. alert_for_renewal') }}"
                                required>

                        </div>
                        <div class="mb-3">
                            <label for="temp_body" class="form-label">{{ __('Template Body') }}<sup
                                    class="text-danger">*</sup></label>
                            <textarea class="form-control" name="temp_body" id="text-area" rows="5" style="height: 106px;"
                                placeholder="{{ __('Hi') }} @{{ var }} {{ __('.....your content......') }} @{{ var }}  .... {{ __('Please click the link below to get started') }}  @{{ var }}"
                                required></textarea>


                        </div>
                        <div class="mb-3">
                            <ul>
                                <li>
                                    <small>
                                        {{ __('Add') }} <span class="text-secondary">@{{ var }}</span>
                                        {{ __('for variable.') }} {{ __('For example Hello') }} <span
                                            class="text-secondary">@{{ var }}</span>
                                        {{ __('Thank you for choosing our services.') }}
                                    </small>
                                </li>
                                <li>
                                    <small>
                                        {{ __('Please add minimum 3 and maximum 4 variables in which the') }} <span
                                            class="text-secondary">{{ __('first and last variable will be reserved') }}</span>
                                        {{ __('for Employee name and campaign url.') }}
                                    </small>
                                </li>
                            </ul>


                        </div>

                        <div class="mb-3">
                            <button type="submit"
                                class="btn btn-primary mt-3 btn-wave waves-effect waves-light">{{ __('Request Template') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <!-- new whatsapp campaign add -->
    <div class="modal fade" id="newWhatsappCampaignModal" tabindex="-1" aria-labelledby="exampleModalLgLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">{{ __('Create Campaign') }}</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="input-label" class="form-label">{{ __('Campaign name') }}<sup
                                class="text-danger">*</sup></label>
                        <input type="text" class="form-control" id="camp_name"
                            placeholder="{{ __('Campaign name') }}" required>

                    </div>
                    <div class="mb-3">
                        <label for="whatsapp-template" class="form-label">{{ __('Template') }}<sup
                                class="text-danger">*</sup></label>
                        <select class="form-select" aria-label="Default select example" name="whatsapp_template"
                            id="whatsapp_template" required>
                            <option value="">{{ __('Choose Template') }}</option>
                            @foreach ($templates as $template)
                                <option value="{{ $template['name'] }}" data-cat="{{ $template['category'] }}"
                                    data-lang="{{ $template['language'] }}"
                                    data-msg="{{ json_encode($template['components']) }}">
                                    {{ $template['name'] }} -
                                    {{ $template['status'] }}
                                </option>
                                {{-- @empty
                                <option value="">{{ __('No templates available') }}</option> --}}
                            @endforeach

                        </select>

                    </div>

                    <div class="mb-3 row" id="template_info" style="display: none;">
                        <div class="col-lg-6">
                            <label for="template_category" class="form-label">{{ __('Category') }}</label>
                            <input type="text" class="form-control" id="template_category" disabled>
                        </div>
                        <div class="col-lg-6">
                            <label for="template_lang" class="form-label">{{ __('Language') }}</label>
                            <input type="text" class="form-control" id="template_lang" disabled>
                        </div>
                        <div class="col-lg-12 my-3">
                            <div class="chat-container">
                                {{-- <div class="chat-bubble sender">
                                        <p>Hello! How are you?</p>
                                        <span class="timestamp">10:30 AM</span>
                                    </div> --}}
                                <div class="chat-bubble receiver">
                                    <strong id="msg-header"></strong>
                                    <p id="msg-body"></p>
                                    <p class="timestamp text-start" id="msg-footer"></p>
                                    <span class="timestamp">10:32 AM</span>
                                </div>
                            </div>

                            <div class="row variableInputs" id="variableInputs">


                            </div>
                        </div>
                    </div>
                    {{-- <div class="mb-3">
                        <label for="input-label" class="form-label">Employee Group<sup
                                class="text-danger">*</sup></label>
                        <div class="d-flex">
                            <select class="form-select" aria-label="Default select example" id="usrGroup">
                                @forelse ($all_users as $user)
                                    <option value="{{ $user->group_id }}">{{ $user->group_name }}</option>
                                @empty
                                    <option value="">No Employees Group Available</option>
                                @endforelse

                            </select>
                        </div>

                    </div> --}}
                    <div class="mb-3">

                        <div class="row">
                            <div class="col-lg-6">
                                <label for="input-label" class="form-label">{{ __('Campaign Type') }}<sup
                                        class="text-danger">*</sup></label>
                                <div class="d-flex">

                                    {{-- <input type="text" class="form-control mx-1" name="subdomain" placeholder="Sub-domain"> --}}
                                    <select class="form-select" aria-label="Default select example" id="campType">
                                        <option value="Phishing">{{ __('Phishing') }}</option>
                                        <option value="Phishing and Training">{{ __('Phishing with Training') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <label for="input-label" class="form-label">{{ __('Employee Type') }}<sup
                                        class="text-danger">*</sup></label>
                                <div class="d-flex">

                                    {{-- <input type="text" class="form-control mx-1" name="subdomain" placeholder="Sub-domain"> --}}
                                    <select id="groupType" class="form-select" aria-label="Default select example">
                                        <option value="Normal" selected>{{ __('Normal Employee') }}</option>
                                        <option value="Bluecollar">{{ __('Bluecollar Employee') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mt-3 col-lg-6">
                                <label for="input-label" class="form-label">{{ __('Groups') }}<sup
                                        class="text-danger">*</sup></label>
                                <div class="d-flex">

                                    {{-- <input type="text" class="form-control mx-1" name="subdomain" placeholder="Sub-domain"> --}}
                                    <select id="fetchGroup" class="form-select" disabled>
                                        <option value="">{{ __('Select a group') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mt-3 col-lg-6">
                                <label for="input-label" class="form-label">{{ __('Select Training') }}<sup
                                        class="text-danger">*</sup></label>
                                <div class="d-flex">

                                    {{-- <input type="text" class="form-control mx-1" name="subdomain" placeholder="Sub-domain"> --}}
                                    <select class="form-select" aria-label="Default select example" id="training"
                                        disabled>
                                        @forelse ($trainings as $training)
                                            <option value="{{ $training->id }}">{{ $training->name }}</option>
                                        @empty
                                            <option value="">{{ __('No Trainings Available') }}</option>
                                        @endforelse

                                    </select>
                                </div>
                            </div>


                            <div class="mb-3 mt-3 col-lg-6">
                                <label for="input-label" class="form-label">{{ __('Training Type') }}<sup
                                        class="text-danger">*</sup></label>
                                <div class="d-flex">

                                    {{-- <input type="text" class="form-control mx-1" name="subdomain" placeholder="Sub-domain"> --}}
                                    <select class="form-select" aria-label="Default select example" id="training_type"
                                        disabled>
                                        <option value="static_training">{{ __('Static Training') }}</option>
                                        <option value="ai_training">{{ __('AI Training') }}</option>

                                    </select>
                                </div>

                            </div>
                        </div>

                    </div>
                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary mt-3 btn-wave waves-effect waves-light"
                            onclick="submitCampaign();">{{ __('Create Campaign') }}</button>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- whatsapp campaign report modal -->
    <div class="modal fade" id="campaignReportModal" tabindex="-1" aria-labelledby="exampleModalLgLabel"
        aria-modal="true" role="dialog">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">{{ __('Campaign Report') }}</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th scope="col">{{ __('Employee Name') }}</th>
                                    <th scope="col">{{ __('WhatsApp No.') }}</th>
                                    <th scope="col">{{ __('Template Name') }}</th>
                                    <th scope="col">{{ __('Status') }}</th>
                                    <th scope="col">{{ __('Link Clicked') }}</th>
                                    <th scope="col">{{ __('Employee Compromised') }}</th>
                                    <th scope="col">{{ __('Training Assigned') }}</th>
                                    <th scope="col">{{ __('Date') }}</th>
                                </tr>
                            </thead>
                            <tbody id="camp_users">

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- All templates modal -->
    <div class="modal fade" id="templatesModal" tabindex="-1" aria-labelledby="exampleModalLgLabel" aria-modal="true"
        role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">{{ __('WhatsApp Templates') }}</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">


                    <div class="table-responsive" id="temp_table">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th scope="col">{{ __('Name') }}</th>
                                    <th scope="col">{{ __('Status') }}</th>
                                    <th scope="col">{{ __('Components') }}</th>
                                </tr>
                            </thead>
                            <tbody id="temps">
                                @forelse($templates as $template)
                                    <tr>
                                        <td>{{ $template['name'] }}</td>
                                        <td>
                                            <span
                                                class="badge bg-{{ $template['status'] == 'APPROVED' ? 'success' : 'warning' }}-transparent">{{ $template['status'] }}</span>
                                        </td>
                                        <td>
                                            @foreach ($template['components'] as $component)
                                                @if ($component['type'] === 'HEADER' && $component['format'] === 'TEXT')
                                                    <strong>{{ $component['text'] }}</strong>
                                                    <br>
                                                @elseif($component['type'] === 'BODY')
                                                    {{ $component['text'] }}
                                                    <br>
                                                @elseif($component['type'] === 'FOOTER')
                                                    <strong>{{ $component['text'] }}</strong>
                                                    <br>
                                                @endif
                                            @endforeach
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center">{{ __('No templates available') }}</td>
                                    </tr>
                                @endforelse

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- All templates modal -->
    <div class="modal fade" id="updateConfigModal" tabindex="-1" aria-labelledby="exampleModalLgLabel"
        aria-modal="true" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">{{ __('WhatsApp Templates') }}</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <form action="{{ route('whatsapp.updateConfig') }}" method="post">
                        @csrf
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" name="from_phone_id" id="phone_id"
                                value="{{ $config->from_phone_id }}" placeholder="Phone Number ID">
                            <label for="phone_id">{{ __('From Phone Number ID') }}</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="access_token" name="access_token"
                                value="{{ $config->access_token }}" placeholder="Access Token">
                            <label for="access_token">{{ __('Access Token') }}</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="business_id" name="business_id"
                                value="{{ $config->business_id }}" placeholder="Business ID">
                            <label for="business_id">{{ __('Business ID') }}</label>
                        </div>
                        <div>
                            <button type="submit" class="btn w-100 btn-primary btn-wave">{{ __('Update') }}</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>




    {{-- -------------------Modals------------------------ --}}


    {{-- ------------------------------Toasts---------------------- --}}

    <x-toast />



    @push('newcss')
        <style>
            .chat-container {
                width: 100%;
                max-width: 600px;
                display: flex;
                flex-direction: column;
            }

            .chat-bubble {
                max-width: 80%;
                padding: 10px 15px;
                margin: 10px 0;
                border-radius: 20px;
                position: relative;
                font-size: 16px;
                line-height: 1.4;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }

            .sender {
                background-color: #dcf8c6;
                align-self: flex-start;
                border-top-left-radius: 0;
            }

            .receiver {
                background-color: #dcf8c6;
                align-self: flex-end;
                border-top-right-radius: 0;
            }

            .timestamp {
                display: block;
                font-size: 12px;
                color: #888;
                margin-top: 5px;
                text-align: right;
            }

            .chat-bubble::before {
                content: "";
                position: absolute;
                width: 0;
                height: 0;
            }

            .sender::before {
                border: 10px solid transparent;
                border-top-color: #dcf8c6;
                border-bottom: 0;
                border-right: 0;
                position: absolute;
                top: 0;
                left: -10px;
                margin-top: 10px;
            }

            .receiver::before {
                border: 10px solid transparent;
                border-top-color: #ffffff;
                border-bottom: 0;
                border-left: 0;
                position: absolute;
                top: 0;
                right: -10px;
                margin-top: 10px;
            }
        </style>
    @endpush

    @push('newscripts')
        <script>
            function findandreplace(element, replacevalue) {
                var msg = $("#msg-body").text();

                // Use a global regular expression to replace all occurrences
                var replacemsg = msg.replace(element.value, element.value);

                // Update the text content of the #msg-body element
                $("#msg-body").text(replacemsg);

            }


            $(document).ready(function() {
                // Call fetchGroups on page load with "Normal" as the default value
                fetchGroups("Normal");

                // Listen for changes in the campType dropdown
                $("#groupType").on("change", function() {
                    fetchGroups($(this).val());
                });
            });

            function fetchGroups(value) {
                console.log("Fetching groups for:", value);

                $.post("/whatsapp-fetch-groups", {
                    data: value
                }, function(res) {
                    let $groupDropdown = $("#fetchGroup");
                    $groupDropdown.empty(); // Clear previous options

                    if (res.length > 0) {
                        res.forEach(group => {
                            $groupDropdown.append(
                                `<option value="${group.group_id}">${group.group_name}</option>`);
                        });
                        $groupDropdown.prop("disabled", false);
                    } else {
                        $groupDropdown.append(`<option value="">No Groups Available</option>`);
                        $groupDropdown.prop("disabled", true);
                    }
                }).fail(function() {
                    alert("Error fetching groups. Please try again.");
                });
            }
            $('#whatsapp_template').change(function() {


                var selectedOption = $(this).find('option:selected');

                // Get the data attributes
                var category = selectedOption.data('cat');
                var language = selectedOption.data('lang');
                var msg = selectedOption.data('msg');
                var headerFound = false;
                var footerFound = false;
                var regex = /\{\{\d+\}\}/g;
                // console.log(msg)
                msg.forEach(e => {
                    if (e.type === 'HEADER' && e.format === 'TEXT') {
                        $("#msg-header").text(e.text);
                        headerFound = true;
                    }

                    if (e.type === 'FOOTER') {
                        $("#msg-footer").text(e.text);
                        footerFound = true;
                    }


                    if (e.type === 'BODY') {
                        var text = e.text;
                        var matches = text.match(regex);
                        // console.log(matches);
                        var inputs = '';

                        if (matches == null) {
                            $("#msg-body").text(e.text);
                            $("#variableInputs").html(inputs);
                        } else {
                            matches.forEach((varib, index) => {
                                if (index === 0) {
                                    var input = `<div class="col-lg-4">
                                        <label class="form-label">{{ __('Variable') }} ${varib}</label>
                                        <input type="text" class="form-control form-control-sm" name="name_variable"
                                            value="{{ __('Employee Name') }}" disabled>
                                            <small class="mb-3">{{ __('This variable is reserved') }}</small>
                                    </div>`;
                                    inputs += input;

                                } else if (index === matches.length - 1) {
                                    var input = `<div class="col-lg-4">
                                        <label class="form-label">{{ __('Variable') }} ${varib}</label>
                                        <input type="text" class="form-control form-control-sm" name="url_variable"
                                            value="{{ __('Campaign URL') }}" disabled>
                                            <small class="mb-3">{{ __('This variable is reserved') }}</small>
                                    </div>`;
                                    inputs += input;
                                } else {
                                    var input = `<div class="col-lg-4">
                                        <label class="form-label">{{ __('Variable') }} ${varib}</label>
                                        <input type="text" class="form-control form-control-sm" name="temp_variable"
                                            placeholder="{{ __('enter value') }}">
                                    </div>`;
                                    inputs += input;
                                }

                            })
                            //  console.log(matches);
                            $("#variableInputs").html(inputs);
                            $("#msg-body").text(e.text);
                        }


                    }
                });

                if (!headerFound) {
                    $("#msg-header").text('');
                }

                if (!footerFound) {
                    $("#msg-footer").text('');
                }

                $("#template_category").val(category);
                $("#template_lang").val(language);

                // Get the selected value
                // var selectedValue = selectedOption.val();

                $("#template_info").show();
            })

            function submitCampaign() {
                var inputs = $("input[name='temp_variable']");
                var componentsArray = [];
                var valuesArray = [];

                if($("#whatsapp_template").val() == "hello_world"){
                    Swal.fire({
                        title: "{{ __('Please select a valid template which has variable') }}",
                        icon: 'error',
                        confirmButtonText: "{{ __('OK') }}"
                    })
                    return;
                }

                var hasEmpty = false;

                inputs.each(function() {
                    if ($(this).val().trim() === "") {
                        hasEmpty = true;
                        return false;
                    }
                });

                if (hasEmpty) {
                    Swal.fire({
                        title: "{{ __('Please fill temporary variables') }}",
                        icon: 'error',
                        confirmButtonText: "{{ __('OK') }}"
                    });
                    return;
                }






                if (inputs.length !== 0) {
                    // Iterate over the inputs and collect their values
                    inputs.each(function() {
                        valuesArray.push({
                            type: "text",
                            text: $(this).val()
                        });
                    });

                    componentsArray = [{
                        type: "body",
                        parameters: valuesArray
                    }]
                }

                // // Create an array to hold the values
                // var valuesArray = [];




                // console.log("user_group", groupType.value);
                var finalBody = {
                    camp_name: camp_name.value,
                    user_group: fetchGroup.value,
                    campType: campType.value,
                    empType: groupType.value,
                    training: training.value,
                    trainingType: training_type.value,
                    token: "0",
                    phone: "0",
                    template_name: $("#whatsapp_template").val(),
                    template_language: $("#template_lang").val(),
                    components: valuesArray
                }
                console.log("final", finalBody);

                if (camp_name.value == '' || fetchGroup.value == '' || campType.value == '' || groupType.value == '' || training
                    .value == '' || training_type.value == '' || $("#whatsapp_template").val() == '') {
                    Swal.fire({
                        title: "{{ __('Please fill required fields') }}",
                        icon: 'error',
                        confirmButtonText: "{{ __('OK') }}"
                    })
                    return;
                }

                // console.log(finalBody);
                $.post({
                    url: '/whatsapp-submit-campaign',
                    data: finalBody,
                    success: function(res) {
                        // console.log("check", res);
                        checkResponse(res)
                    }
                })
            }

            function checkResponse(res) {
                if (res.status == 1) {
                    Swal.fire({
                        title: res.msg,
                        icon: 'success',
                        confirmButtonText: "{{ __('OK') }}"
                    }).then(function() {
                        window.location.href = window.location.href
                    })
                } else {
                    Swal.fire({
                        title: res.msg,
                        icon: 'error',
                        confirmButtonText: "{{ __('OK') }}"
                    }).then(function() {
                        window.location.href = window.location.href
                    })
                }
            }

            function deleteCamp(campid) {

                Swal.fire({
                    title: "{{ __('Are you sure?') }}",
                    text: "{{ __('The campaign will be deleted with their report.') }}",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e6533c',
                    cancelButtonColor: '#d33',
                    confirmButtonText: "{{ __('Delete') }}",
                    cancelButtonText: "{{ __('Cancel') }}"
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post({
                            url: '/whatsapp-delete-campaign',
                            data: {
                                campid: campid
                            },
                            success: function(res) {
                                checkResponse(res);
                            }
                        })
                    }
                })

            }

            function fetchCampaignDetails(campid) {
                $.post({
                    url: '/whatsapp-fetch-campaign',
                    data: {
                        campid: campid
                    },
                    success: function(res) {
                        var row = '';
                        if (res) {
                            res.forEach((e) => {


                                row += `
                                <tr class="">
                                    <td scope="row">${e.user_name}</td>
                                    <td>${e.user_whatsapp}</td>
                                    <td>${e.template_name}</td>
                                    <td>
                                        <span class="badge bg-${e.status == 'pending' ? 'warning' : 'success'}-transparent">${e.status}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-${e.link_clicked == 0 ? 'warning' : 'success'}-transparent">${e.link_clicked == 0 ? 'No' : 'Yes'}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-${e.emp_compromised == 0 ? 'warning' : 'success'}-transparent">${e.emp_compromised == 0 ? 'No' : 'Yes'}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-${e.training_assigned == 0 ? 'warning' : 'success'}-transparent">${e.training_assigned == 0 ? 'No' : 'Yes'}</span>
                                    </td>
                                    <td>${e.created_at}</td>
                                </tr>
                                `;
                            })

                            $("#camp_users").html(row);

                        }
                    }
                })
            }


            function syncTemps(btn) {
                $(btn).html("{{ __('Syncing...') }}").attr('disabled', true);
                $.get({
                    url: '/whatsapp-sync-templates',
                    success: function(res) {
                        if (res.success) {
                            Swal.fire({
                                title: res.success,
                                icon: 'success',
                                confirmButtonText: "{{ __('OK') }}"
                            }).then(function() {
                                window.location.href = window.location.href
                            })

                        } else {
                            console.log(res);
                            Swal.fire({
                                title: res.error,
                                icon: 'error',
                                confirmButtonText: "{{ __('OK') }}"
                            }).then(function() {
                                window.location.href = window.location.href
                            })
                        }
                    }
                })
            }
        </script>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const campType = document.getElementById('campType');
                const training = document.getElementById('training');
                const trainingType = document.getElementById('training_type');

                campType.addEventListener('change', function() {
                    if (campType.value === 'Phishing and Training') {
                        training.disabled = false;
                        trainingType.disabled = false;
                    } else {
                        training.disabled = true;
                        trainingType.disabled = true;

                    }
                });
            });
        </script>
    @endpush

@endsection
