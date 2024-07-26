@extends('layouts.app')

@section('title', 'WhatsApp Campaign- Phishing awareness training program')

@section('main-content')

    <div class="main-content app-content">
        <div class="container-fluid mt-4">

            <div class="d-flex justify-content-between">
                <div>
                    <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal"
                        data-bs-target="#newWhatsappCampaignModal">New Whatsapp Campaign</button>
                </div>

            </div>


            <div class="row">
                <div class="col-xl-12">
                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">
                                Manage Phishing Websites
                            </div>
                        </div>
                        <div class="card-body">


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
                            <label for="input-label" class="form-label">Campaign name<sup
                                    class="text-danger">*</sup></label>
                            <input type="text" class="form-control" id="camp_name" placeholder="Template name" required>

                        </div>
                        <div class="mb-3">
                            <label for="whatsapp-template" class="form-label">Template</label>
                            <select class="form-select" aria-label="Default select example" name="whatsapp_template"
                                id="whatsapp_template">
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
                                        <p id="msg-body">I'm good, thanks! How about you?</p>
                                        <span class="timestamp">10:32 AM</span>
                                    </div>
                                </div>

                                <div class="row variableInputs" id="variableInputs">

                                  
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="input-label" class="form-label">Employee Group</label>
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
                var regex = /\{\{\d+\}\}/g;
                // console.log(msg)
                msg.forEach(e => {
                    if (e.type === 'BODY') {
                        var text = e.text;
                        var matches = text.match(regex);

                        var inputs = '';
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
                });

                $("#template_category").val(category);
                $("#template_lang").val(language);

                // Get the selected value
                // var selectedValue = selectedOption.val();

                $("#template_info").show();
            })

            function submitCampaign() {
                var inputs = $("input[name='temp_variable']");

                // Create an array to hold the values
                var valuesArray = [];

                // Iterate over the inputs and collect their values
                inputs.each(function() {
                    valuesArray.push({
                        type: "text",
                        text: $(this).val()
                    });
                });

                var componentsArray = [
                    {
                        type: "body",
                        parameters: valuesArray
                    }
                ]

                var finalBody = {
                    camp_name: camp_name.value,
                    user_group: usrGroup.value,
                    token: "0",
                    phone: "0",
                    template_name: $("#whatsapp_template").val(),
                    template_language: $("#template_lang").val(),
                    components: componentsArray
                }

                console.log(finalBody);
                $.post({
                    url: '{{ route('whatsapp.submitCampaign') }}',
                    data: finalBody,
                    success: function(res){
                        console.log(res)
                    }
                })
            }
        </script>
    @endpush

@endsection
