@extends('layouts.app')

@section('title', 'WhatsApp Campaign- Phishing awareness training program')

@section('main-content')

    <div class="main-content app-content">
        <div class="container-fluid mt-4">

            <div class="d-flex" style="gap: 10px;">
                <div>
                    <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal"
                        data-bs-target="#newWhatsappCampaignModal">New Whatsapp Campaign</button>
                </div>
                <div>
                    <button type="button" class="btn btn-secondary mb-3" onclick="fetchTemplates()" data-bs-toggle="modal"
                        data-bs-target="#templatesModal">Available Templates</button>
                </div>

            </div>


            <div class="row">
                <div class="col-xl-12">
                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">
                                All WhatsApp Campaigns
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Campaign Name</th>
                                            <th>Template Name</th>
                                            <th>Employee Group</th>
                                            <th>Launch Date</th>
                                            <th>Action</th>
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
                                                <td>{{ $campaign->template_name }}</td>
                                                <td>{{ $campaign->user_group_name ?? 'N/A' }}</td>
                                                <td>{{ $campaign->created_at }}</td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-danger"
                                                        onclick="deleteCamp(`{{ $campaign->camp_id }}`)">
                                                        Delete
                                                    </button>

                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center">No records found</td>
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
    </div>

    {{-- -------------------Modals------------------------ --}}

    <!-- new whatsapp campaign add -->
    <div class="modal fade" id="newWhatsappCampaignModal" tabindex="-1" aria-labelledby="exampleModalLgLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Create Campaign</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="input-label" class="form-label">Campaign name<sup class="text-danger">*</sup></label>
                        <input type="text" class="form-control" id="camp_name" placeholder="Template name" required>

                    </div>
                    <div class="mb-3">
                        <label for="whatsapp-template" class="form-label">Template<sup class="text-danger">*</sup></label>
                        <select class="form-select" aria-label="Default select example" name="whatsapp_template"
                            id="whatsapp_template" required>
                            <option value="">Choose Template</option>
                            @forelse ($templates as $template)
                                <option value="{{ $template['name'] }}" data-cat="{{ $template['category'] }}"
                                    data-lang="{{ $template['language'] }}" data-msg="{{ $template['components'] }}">
                                    {{ $template['name'] }} -
                                    {{ $template['status'] }}</option>
                            @empty
                                <option value="">No templates available</option>
                            @endforelse

                        </select>

                    </div>

                    <div class="mb-3 row" id="template_info" style="display: none;">
                        <div class="col-lg-6">
                            <label for="template_category" class="form-label">Category</label>
                            <input type="text" class="form-control" id="template_category" disabled>
                        </div>
                        <div class="col-lg-6">
                            <label for="template_lang" class="form-label">Language</label>
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
                    <div class="mb-3">
                        <label for="input-label" class="form-label">Employee Group<sup class="text-danger">*</sup></label>
                        <div class="d-flex">

                            {{-- <input type="text" class="form-control mx-1" name="subdomain" placeholder="Sub-domain"> --}}
                            <select class="form-select" aria-label="Default select example" id="usrGroup">
                                @forelse ($all_users as $user)
                                    <option value="{{ $user->group_id }}">{{ $user->group_name }}</option>
                                @empty
                                    <option value="">No Employees Group Available</option>
                                @endforelse

                            </select>
                        </div>

                    </div>
                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary mt-3 btn-wave waves-effect waves-light"
                            onclick="submitCampaign();">Add
                            Campaign</button>
                    </div>

                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="campaignReportModal" tabindex="-1" aria-labelledby="exampleModalLgLabel"
        aria-modal="true" role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Campaign Report</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th scope="col">Employee name</th>
                                    <th scope="col">WhatsApp No.</th>
                                    <th scope="col">Template Name</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Date</th>
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

    <div class="modal fade" id="templatesModal" tabindex="-1" aria-labelledby="exampleModalLgLabel" aria-modal="true"
        role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">WhatsApp Templates</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <div class="spinner-border" role="status" id="temp_spinner">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>


                    <div class="table-responsive" id="temp_table">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th scope="col">Name</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Components</th>
                                </tr>
                            </thead>
                            <tbody id="temps">

                            </tbody>
                        </table>
                    </div>
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
            $('#whatsapp_template').change(function() {


                var selectedOption = $(this).find('option:selected');

                // Get the data attributes
                var category = selectedOption.data('cat');
                var language = selectedOption.data('lang');
                var msg = selectedOption.data('msg');
                var headerFound = false;
                var footerFound = false;
                var regex = /\{\{\d+\}\}/g;
                console.log(msg)
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
                            matches.forEach(varib => {
                                var input = `<div class="col-lg-4">
                                        <label class="form-label">Variable ${varib}</label>
                                        <input type="text" class="form-control" name="temp_variable"
                                            placeholder="enter value">
                                    </div>`;
                                inputs += input;
                            })
                            // console.log(matches);
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

                // console.log(inputs.length);
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





                var finalBody = {
                    camp_name: camp_name.value,
                    user_group: usrGroup.value,
                    token: "0",
                    phone: "0",
                    template_name: $("#whatsapp_template").val(),
                    template_language: $("#template_lang").val(),
                    components: componentsArray
                }

                // console.log(finalBody);
                $.post({
                    url: '{{ route('whatsapp.submitCampaign') }}',
                    data: finalBody,
                    success: function(res) {
                        checkResponse(res)
                    }
                })
            }

            function checkResponse(res) {
                if (res.status == 1) {
                    Swal.fire(
                        res.msg,
                        '',
                        'success'
                    ).then(function() {
                        window.location.href = window.location.href
                    })
                } else {
                    Swal.fire(
                        'Something went wrong...',
                        '',
                        'error'
                    ).then(function() {
                        window.location.href = window.location.href
                    })
                }
            }

            function deleteCamp(campid) {

                Swal.fire({
                    title: 'Are you sure?',
                    text: "The campaign will be deleted with their report.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e6533c',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Delete'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post({
                            url: '{{ route('whatsapp.deleteCampaign') }}',
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
                    url: '{{ route('whatsapp.fetchCamp') }}',
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
                                    <td>${e.created_at}</td>
                                </tr>
                                `;
                            })

                            $("#camp_users").html(row);

                        }
                    }
                })
            }

            function fetchTemplates() {
                $("#temp_table").hide();
                $("#temp_spinner").show();
                $.get({
                    url: '{{ route('whatsapp.templates') }}',
                    success: function(res) {

                        var row = '';
                        var header = '';
                        var body = '';
                        var footer = '';
                        var components = '';
                        if (res) {
                            res.templates.forEach((e) => {
                                components = JSON.parse(e.components);
                                components.forEach((e2) => {
                                    if (e2.type === 'HEADER' && e2.format === 'TEXT') {
                                        header = e2.text
                                    }
                                    if (e2.type === 'BODY') {
                                        body = e2.text
                                    }
                                    if (e2.type === 'FOOTER') {
                                        footer = e2.text
                                    }
                                })
                                row += `
                                <tr class="">
                                    <td scope="row">${e.name}</td>
                                    <td>
                                        <span class="badge bg-${e.status == 'APPROVED' ? 'success' : 'warning'}-transparent">${e.status}</span>
                                    </td>
                                    <td>
                                        <strong>${header}</strong>
                                        <br>
                                        ${body}
                                        <br>
                                        <strong>${footer}</strong>
                                    </td>
                                    
                                </tr>
                                `;
                            })

                            $("#temps").html(row);

                            $("#temp_spinner").hide();
                            $("#temp_table").show();

                        }


                        console.log(res);
                    }
                })
            }
        </script>
    @endpush

@endsection
