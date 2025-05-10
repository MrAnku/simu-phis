@extends('layouts.app')

@section('title', __('Smishing Templates') . ' - ' . __('Phishing awareness training program'))

@section('main-content')

    <div class="main-content app-content">
        <div class="container-fluid mt-4">

            <div class="d-flex justify-content-between">
                <div>
                    <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal"
                        data-bs-target="#newSmishingTempModal">{{ __('New Smishing Template') }}</button>


                </div>

            </div>


            <div class="row">
                <div class="col-xl-12">
                    <div class="card custom-card">
                        <div class="card-header d-flex justify-content-between">
                            <div class="card-title">
                                {{ __('Manage Smishing Templates') }}
                            </div>
                            <div>
                                <div class="input-group mb-3">

                                    <form method="GET" action="{{ route('quishing.emails') }}" class="d-flex gap-2">
                                        <input type="text" class="form-control" name="search"
                                            placeholder="{{ __('Search Template...') }}"
                                            aria-label="Example text with button addon" aria-describedby="button-addon1"
                                            value="{{ request('search') }}">
                                        <button class="btn btn-icon btn-primary-transparent rounded-pill btn-wave"
                                            type="submit">
                                            <i class="ri-search-line"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>


                        </div>
                        <div class="card-body all-email-templates">

                            <div class="row">

                                @forelse($templates as $template)
                                    <div class="col-lg-4">
                                        <div class="card custom-card border">
                                            <div class="card-header">
                                                <div class="d-flex align-items-center w-100">

                                                    <div class="">
                                                        <div class="fs-15 fw-semibold">{{ $template->name }}</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card-body sms-preview-col">
                                                <div class="phone-frame">
                                                    <div class="status-bar">{{ now()->format('g:i A') }}</div>
                                                    <div class="sms-header">5858587</div>
                                                    <div class="sms-body">
                                                        {{ $template->message }}
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card-footer">
                                                <div class="d-flex justify-content-center">
                                                    <button type="button" data-bs-toggle="modal"
                                                        onclick="setPreview(this, {{ $template->id }})"
                                                        data-bs-target="#testSmsModal"
                                                        class="btn mx-1 btn-outline-primary btn-wave waves-effect waves-light">{{ __('Send Test SMS') }}</button>

                                                    @if ($template->company_id == Auth::user()->company_id)
                                                        <button type="button" data-bs-toggle="modal"
                                                            data-bs-target="#editEtemplateModal"
                                                            class="btn mx-1 btn-outline-primary btn-wave waves-effect waves-light">{{ __('Edit') }}</button>

                                                        <button type="button"
                                                            class="btn mx-1 btn-outline-danger btn-wave waves-effect waves-light">{{ __('Delete') }}</button>
                                                    @endif


                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                @endforelse






                            </div>
                        </div>
                    </div>
                </div>
            </div>


        </div>
    </div>

    {{-- -------------------Modals------------------------ --}}

    <x-modal id="newSmishingTempModal" heading="{{ __('Add New Smishing Template') }}">

        <form action="{{ route('smishing.temp.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label for="template_name"
                    class="form-label
                    required">{{ __('Template Name') }}</label>
                <input type="text" class="form-control" id="template_name" name="template_name"
                    placeholder="{{ __('Template Name') }}" required>
            </div>
            <div class="mb-3">
                <label for="template_body"
                    class="form-label

                    required">{{ __('Template Body') }}</label>
                <div class="mb-3">
                    <small class="text-muted">{{ __('Please use the shortcodes') }} <span
                            class="text-danger">@{{ user_name }}</span> {{ __('and') }} <span
                            class="text-danger">@{{ redirect_url }}</span> {{ __('in your template body.') }}</small>
                </div>
                <textarea class="form-control" id="template_body" name="template_body" rows="3"
                    placeholder="Hello @{{ user_name }}, your OTP is 543679. Click @{{ redirect_url }} to verify." required></textarea>

            </div>

            <div class="mb-3">
                <label for="template_category"
                    class="form-label
                    required">{{ __('Template Category') }}</label>
                <select class="form-select" id="template_type" name="category" required>
                    <option value="financial">{{ __('Financial') }}</option>
                    <option value="matrimonial">{{ __('Matrimonial') }}</option>
                    <option value="promotional">{{ __('Promotional') }}</option>
                    <option value="educational">{{ __('Educational') }}</option>
                    <option value="healthcare">{{ __('Healthcare') }}</option>
                    <option value="entertainment">{{ __('Entertainment') }}</option>
                    <option value="ecommerce">{{ __('E-commerce') }}</option>
                    <option value="others">{{ __('Others') }}</option>
                </select>
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
            </div>
    </x-modal>


    <x-modal id="testSmsModal" heading="{{ __('Send a test SMS') }}">

        <div id="templatePreview" class="mb-3">

        </div>

        <div>
            <div class="mb-3">
                <label for="mobile_no" class="form-label required">{{ __('Enter recipient mobile no') }}</label>
                <input type="hidden" name="template_text" id="template_text" value="">
                <input type="text" class="form-control" id="target_mobile_no" name="target_mobile_no"
                    placeholder="{{ __('+912873918234') }}" required>
            </div>


            <div class="d-flex justify-content-end">
                <button type="submit" onclick="sendTestMsg(this)" class="btn btn-primary">{{ __('Send') }}</button>
            </div>
        </div>

    </x-modal>


    {{-- ------------------------------Toasts---------------------- --}}

    <x-toast />



    @push('newcss')
        <style>
            .difficulty {
                position: absolute;
                right: 20px;
                top: 20px;
            }

            .phone-frame {
                width: 100%;
                overflow-y: scroll;
                height: 300px;
                border-radius: 30px;
                padding: 20px 20px;
                background-color: #fff;
                margin: auto;
            }

            .status-bar {
                font-size: 12px;
                text-align: center;
                margin-bottom: 10px;
                color: #555;
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
            function setPreview(btn, templateId) {
                const preview = $(btn).parent().parent().prev().html()
                
                const smsBody = $(preview).find('.sms-body').text();

                const updatedSmsBody = smsBody
                .replace(/@{{\s*user_name\s*}}/g, '{{ Auth::user()->full_name }}')
                .replace(/@{{\s*redirect_url\s*}}/g, 'https://website.com/12dhsb2342');
                $('#template_text').val(updatedSmsBody);
                $('#templatePreview').html(`<div class="sms-body" style="max-width: 70%;">${updatedSmsBody}</div>`)
            }

            function sendTestMsg(btn){
                const mobileNo = $('#target_mobile_no').val();
                const templateText = $('#template_text').val().trim();

                if(mobileNo.length < 10){
                    Swal.fire({
                        icon: 'error',
                        title: '{{ __('Error') }}',
                        text: '{{ __('Please enter a valid mobile number') }}',
                    });
                    return;
                }
                $(btn).text('Sending...').addClass('disabled');
                $.ajax({
                    url: "/smishing-template/send-test-sms",
                    type: "POST",
                    data: {
                        mobile_no: mobileNo,
                        template_text: templateText
                    },
                    success: function(response) {
                        if (response.status == 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: '{{ __('Success') }}',
                                text: response.message,
                            });
                            $('#testSmsModal').modal('hide');
                            $(btn).text('Send').removeClass('disabled');
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: '{{ __('Error') }}',
                                text: response.message,
                            });
                            $(btn).text('Send').removeClass('disabled');
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: '{{ __('Error') }}',
                            text: xhr.responseJSON.message,
                        });
                        $(btn).text('Send').removeClass('disabled');
                    }
                });
            }
        </script>
    @endpush

@endsection
