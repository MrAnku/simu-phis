@extends('layouts.app')

@section('title', 'Campaigns - Phishing awareness training program')

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
                                    <div class="mb-2">Since Last Campaign Delivery</div>
                                    <div class="text-muted mb-1 fs-12">
                                        <span class="text-dark fw-semibold fs-20 lh-1 vertical-bottom">
                                            {{ $daysSinceLastDelivery }} Day(s)
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
                                    <div class="mb-2">Total Sent Emails</div>
                                    <div class="text-muted mb-1 fs-12">
                                        <span class="text-dark fw-semibold fs-20 lh-1 vertical-bottom">
                                            {{ $all_sent }} Delivered
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
                                    <div class="mb-2">Mail Opened</div>
                                    <div class="text-muted mb-1 fs-12">
                                        <span class="text-dark fw-semibold fs-20 lh-1 vertical-bottom">
                                            {{ $mail_open }} Opened
                                        </span>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#newCampModal">New
                Campaign</button>

            <div class="row">
                <div class="col-xl-12">
                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">
                                Manage Campaign
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Campaign Name</th>
                                            <th>Campaign Type</th>
                                            <th>Status</th>
                                            <th>Employees Group</th>
                                            <th>Launch Time</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($allCamps as $index => $campaign)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>
                                                    @if ($campaign->status != 'Not Scheduled')
                                                        <a href="#" class="text-primary"
                                                            onclick="fetchCampaignDetails('{{ e($campaign->campaign_id) }}')"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#campaignReportModal">{{ e($campaign->campaign_name) }}</a>
                                                    @else
                                                        {{ e($campaign->campaign_name) }}
                                                    @endif
                                                </td>
                                                <td>{{ $campaign->campaign_type }}</td>
                                                <td>{!! $campaign->status_button !!}</td>
                                                <td class="text-center">
                                                    {{ $campaign->users_group_name }}
                                                   
                                                </td>
                                                <td class="text-center">
                                                    <div>
                                                        
                                                            @if ($campaign->launch_type == 'schLater')
                                                            <small class="text-danger">
                                                                Not scheduled
                                                            </small>
                                                            @else
                                                            <small>
                                                                {{$campaign->launch_type}}
                                                            </small>
                                                            @endif
                                                            
                                                        
                                                    </div>
                                                    
                                                    {{ e($campaign->launch_time) }}
                                                    
                                                    <div>
                                                        <small>
                                                            @if ($campaign->email_freq == 'one')
                                                                Once
                                                            @else
                                                                {{$campaign->email_freq}}
                                                            @endif
                                                            
                                                        </small>
                                                    </div>
                                                </td>
                                                <td>
                                                    {!! $campaign->relaunch_btn ?? '' !!}
                                                    {!! $campaign->reschedule_btn ?? '' !!}
                                                    <button
                                                        class="btn btn-icon btn-danger btn-wave waves-effect waves-light"
                                                        title="Delete"
                                                        onclick="deletecampaign('{{ e($campaign->campaign_id) }}')"><i
                                                            class="bx bx-trash"></i></button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center">No campaigns running</td>
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


    {{-- --------------------- modals ---------------------- --}}

    <!-- new campaign modal -->
    <div class="modal fade" id="newCampModal" tabindex="-1" aria-labelledby="exampleModalLgLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Add New Campaign</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="card px-0 pt-4 pb-0 mt-3 mb-3">
                        <div class="row">
                            <div class="col-md-12 mx-0">
                                <form id="newCampaignForm" action="" method="post">
                                    <!-- progressbar -->
                                    <ul id="progressbar">
                                        <li class="active">
                                            <i class='bx bx-cog'></i>
                                            <strong>Initial Setup & Employee Selection</strong>
                                        </li>
                                        <li id="pm_step">
                                            <i class='bx bx-mail-send'></i>
                                            <strong>Select Phishing Material</strong>
                                        </li>
                                        <li id="tm_step">
                                            <i class='bx bx-mail-send'></i>
                                            <strong>Select Training Modules</strong>
                                        </li>
                                        <li>
                                            <i class='bx bx-time-five'></i>
                                            <strong>Set Delivery Schedule</strong>
                                        </li>
                                        <li>
                                            <i class='bx bx-check-square'></i>
                                            <strong>Review & Submit</strong>
                                        </li>
                                    </ul>
                                    <!-- fieldsets -->
                                    <fieldset class="included">
                                        <div class="form-card">
                                            <div class="row">
                                                <div class="col-lg-6">

                                                    <label for="input-label" class="form-label">Campaign Name<sup
                                                            class="text-danger">*</sup></label>
                                                    <input type="text" class="form-control required" id="camp_name"
                                                        placeholder="Enter a unique campaign name">

                                                </div>
                                                <div class="col-lg-6">

                                                    <label for="input-label" class="form-label">Campaign Type</label>
                                                    <select class="form-control required" id="campaign_type">
                                                        <option value="">Choose</option>
                                                        <option value="Phishing">Simulate Phishing</option>
                                                        <option value="Training">Security Awareness Training</option>
                                                        <option value="Phishing & Training">Simulate Phishing & Security
                                                            Awareness Training</option>
                                                    </select>

                                                </div>

                                            </div>

                                            <div class="row">
                                                <div class="col-lg-6 mt-3">

                                                    <label for="input-label" class="form-label">Select Employee
                                                        Group</label>
                                                    <select class="form-control required" id="users_group">
                                                        @foreach ($usersGroups as $group)
                                                            <option value="{{ $group->group_id }}">
                                                                {{ $group->group_name }}
                                                            </option>
                                                        @endforeach
                                                    </select>

                                                </div>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-info label-btn label-end rounded-pill next">
                                            Next
                                            <i class="ri-arrow-right-line label-btn-icon ms-2 rounded-pill"></i>
                                        </button>
                                    </fieldset>

                                    <fieldset class="included" id="pm_step_form">
                                        <button type="button"
                                            class="btn btn-dark label-btn label-end stickyBtn rounded-pill previous">
                                            Previous
                                            <i class="ri-arrow-left-line label-btn-icon ms-2 rounded-pill"></i>
                                        </button>

                                        <button type="button"
                                            class="btn btn-info label-btn stickyBtn label-end rounded-pill next">
                                            Next
                                            <i class="ri-arrow-right-line label-btn-icon ms-2 rounded-pill"></i>
                                        </button>
                                        <div class="form-card">

                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <label for="input-label" class="form-label">Email Language</label>

                                                    <select class="form-select" id="email_lang">
                                                        <option value="sq">Albanian</option>
                                                        <option value="ar">Arabic</option>
                                                        <option value="az">Azerbaijani</option>
                                                        <option value="bn">Bengali</option>
                                                        <option value="bg">Bulgarian</option>
                                                        <option value="ca">Catalan</option>
                                                        <option value="zh">Chinese</option>
                                                        <option value="zt">Chinese (traditional)</option>
                                                        <option value="cs">Czech</option>
                                                        <option value="da">Danish</option>
                                                        <option value="nl">Dutch</option>
                                                        <option value="en" selected="">English</option>
                                                        <option value="eo">Esperanto</option>
                                                        <option value="et">Estonian</option>
                                                        <option value="fi">Finnish</option>
                                                        <option value="fr">French</option>
                                                        <option value="de">German</option>
                                                        <option value="el">Greek</option>
                                                        <option value="he">Hebrew</option>
                                                        <option value="hi">Hindi</option>
                                                        <option value="hu">Hungarian</option>
                                                        <option value="id">Indonesian</option>
                                                        <option value="ga">Irish</option>
                                                        <option value="it">Italian</option>
                                                        <option value="ja">Japanese</option>
                                                        <option value="ko">Korean</option>
                                                        <option value="lv">Latvian</option>
                                                        <option value="lt">Lithuanian</option>
                                                        <option value="ms">Malay</option>
                                                        <option value="nb">Norwegian</option>
                                                        <option value="fa">Persian</option>
                                                        <option value="pl">Polish</option>
                                                        <option value="pt">Portuguese</option>
                                                        <option value="ro">Romanian</option>
                                                        <option value="ru">Russian</option>
                                                        <option value="sk">Slovak</option>
                                                        <option value="sl">Slovenian</option>
                                                        <option value="es">Spanish</option>
                                                        <option value="sv">Swedish</option>
                                                        <option value="tl">Tagalog</option>
                                                        <option value="th">Thai</option>
                                                        <option value="tr">Turkish</option>
                                                        <option value="uk">Ukranian</option>
                                                        <option value="ur">Urdu</option>
                                                    </select>
                                                </div>

                                                <div>

                                                    <label for="templateSearch" class="form-label">Search</label>
                                                    <input type="text" class="form-control" id="templateSearch"
                                                        placeholder="Search template">

                                                </div>
                                            </div>

                                            <div class="row">
                                                @forelse ($phishingEmails as $email)
                                                    @php
                                                        $isDefault = $email->company_id == 'default' ? '(Default)' : '';
                                                        $template = asset('storage') . '/' . $email->mailBodyFilePath;
                                                    @endphp
                                                    <div class="col-lg-6 email_templates">
                                                        <div class="card custom-card">
                                                            <div class="card-header">
                                                                <div class="d-flex align-items-center w-100">
                                                                    <div class="">
                                                                        <div class="fs-15 fw-semibold">{{ $email->name }}
                                                                            {{ $isDefault }}</div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="card-body htmlPhishingGrid"
                                                                style="background: white;">
                                                                <iframe class="phishing-iframe"
                                                                    src="{{ $template }}"></iframe>
                                                            </div>
                                                            <div class="card-footer">
                                                                <div class="d-flex justify-content-center">
                                                                    <div>
                                                                        <button type="button"
                                                                            onclick="showMaterialDetails(this, '{{ $email->name }}', '{{ $email->email_subject }}', '{{ $email->website }}', '{{ $email->senderProfile }}')"
                                                                            class="btn btn-outline-primary btn-wave waves-effect waves-light mx-2">
                                                                            View
                                                                        </button>
                                                                    </div>
                                                                    <div class="fs-semibold fs-14">
                                                                        <input type="radio" name="phish_material"
                                                                            data-phishMatName="{{ $email->name }}"
                                                                            value="{{ $email->id }}" class="btn-check"
                                                                            id="pm{{ $email->id }}">
                                                                        <label class="btn btn-outline-primary mb-3"
                                                                            for="pm{{ $email->id }}">Select this
                                                                            attack</label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <p>No phishing emails available.</p>
                                                @endforelse

                                            </div>
                                        </div>

                                    </fieldset>

                                    <fieldset class="included" id="tm_step_form">
                                        <button type="button"
                                            class="btn btn-dark label-btn label-end stickyBtn rounded-pill previous">
                                            Previous
                                            <i class="ri-arrow-left-line label-btn-icon ms-2 rounded-pill"></i>
                                        </button>

                                        <button type="button"
                                            class="btn btn-info label-btn stickyBtn label-end rounded-pill next">
                                            Next
                                            <i class="ri-arrow-right-line label-btn-icon ms-2 rounded-pill"></i>
                                        </button>
                                        <div class="form-card">

                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <label for="input-label" class="form-label">Language</label>

                                                    <select class="form-select" id="training_lang">
                                                        <option value="sq">Albanian</option>
                                                        <option value="ar">Arabic</option>
                                                        <option value="az">Azerbaijani</option>
                                                        <option value="bn">Bengali</option>
                                                        <option value="bg">Bulgarian</option>
                                                        <option value="ca">Catalan</option>
                                                        <option value="zh">Chinese</option>
                                                        <option value="zt">Chinese (traditional)</option>
                                                        <option value="cs">Czech</option>
                                                        <option value="da">Danish</option>
                                                        <option value="nl">Dutch</option>
                                                        <option value="en" selected="">English</option>
                                                        <option value="eo">Esperanto</option>
                                                        <option value="et">Estonian</option>
                                                        <option value="fi">Finnish</option>
                                                        <option value="fr">French</option>
                                                        <option value="de">German</option>
                                                        <option value="el">Greek</option>
                                                        <option value="he">Hebrew</option>
                                                        <option value="hi">Hindi</option>
                                                        <option value="hu">Hungarian</option>
                                                        <option value="id">Indonesian</option>
                                                        <option value="ga">Irish</option>
                                                        <option value="it">Italian</option>
                                                        <option value="ja">Japanese</option>
                                                        <option value="ko">Korean</option>
                                                        <option value="lv">Latvian</option>
                                                        <option value="lt">Lithuanian</option>
                                                        <option value="ms">Malay</option>
                                                        <option value="nb">Norwegian</option>
                                                        <option value="fa">Persian</option>
                                                        <option value="pl">Polish</option>
                                                        <option value="pt">Portuguese</option>
                                                        <option value="ro">Romanian</option>
                                                        <option value="ru">Russian</option>
                                                        <option value="sk">Slovak</option>
                                                        <option value="sl">Slovenian</option>
                                                        <option value="es">Spanish</option>
                                                        <option value="sv">Swedish</option>
                                                        <option value="tl">Tagalog</option>
                                                        <option value="th">Thai</option>
                                                        <option value="tr">Turkish</option>
                                                        <option value="uk">Ukranian</option>
                                                        <option value="ur">Urdu</option>
                                                    </select>
                                                </div>

                                                <div>

                                                    <label for="t_moduleSearch" class="form-label">Search</label>
                                                    <input type="text" class="form-control" id="t_moduleSearch"
                                                        placeholder="Search template">

                                                </div>
                                            </div>

                                            <div class="row">
                                                @forelse ($trainingModules as $module)
                                                    @php
                                                        $coverImgPath = asset(
                                                            'storage/uploads/trainingModule/' . $module->cover_image,
                                                        );
                                                    @endphp
                                                    <div class="col-lg-6 t_modules">
                                                        <div class="card custom-card">
                                                            <div class="card-header">
                                                                <div class="d-flex align-items-center w-100">
                                                                    <div class="">
                                                                        <div class="fs-15 fw-semibold">{{ $module->name }}
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="card-body htmlPhishingGrid">
                                                                <img class="trainingCoverImg"
                                                                    src="{{ $coverImgPath }}" />
                                                            </div>
                                                            <div class="card-footer">
                                                                <div class="d-flex justify-content-center">
                                                                    <div class="fs-semibold fs-14">
                                                                        <input type="radio" name="training_module"
                                                                            data-trainingName="{{ $module->name }}"
                                                                            value="{{ $module->id }}" class="btn-check"
                                                                            id="training{{ $module->id }}">
                                                                        <label class="btn btn-outline-primary mb-3"
                                                                            for="training{{ $module->id }}">Select this
                                                                            training</label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <p>No training modules available.</p>
                                                @endforelse

                                            </div>
                                        </div>

                                    </fieldset>

                                    <fieldset class="included">
                                        <div class="form-card">
                                            <div class="d-flex">
                                                <div class="checkb mx-1">

                                                    <input type="radio" class="btn-check" name="schType"
                                                        data-val="Immediately" value="immediately" id="imediateBtn"
                                                        checked>
                                                    <label class="btn btn-outline-dark mb-3" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        data-bs-original-title="Campaign will begin delivering emails within 1-3 minutes of submission."
                                                        id="imediateLabelBtn" for="imediateBtn">Deliver Immediately
                                                    </label>
                                                </div>
                                                <div class="checkb mx-1">

                                                    <input type="radio" class="btn-check" name="schType"
                                                        data-val="Setup Schedule" value="scheduled" id="ScheduleBtn">
                                                    <label class="btn btn-outline-dark mb-3" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        data-bs-original-title="Campaign will deliver emails using a defined schedule over a period of hours and days (e.g. 9am-5pm Monday-Friday)."
                                                        id="scheduleLabelBtn" for="ScheduleBtn">Setup Schedule</label>
                                                </div>

                                                <div class="checkb mx-1">

                                                    <input type="radio" class="btn-check" name="schType"
                                                        data-val="Schedule Later" value="schLater" id="ScheduleLBtn">
                                                    <label class="btn btn-outline-dark mb-3" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        data-bs-original-title="Campaign will not deliver emails until an update to the schedule is made at a later date."
                                                        id="scheduleLLabelBtn" for="ScheduleLBtn">Schedule Later
                                                        </i></label>
                                                </div>

                                                <!-- <div class="input-group d-none" id="dateTimeSelector">
                                                                                    <div class="input-group-text text-muted"> <i class="ri-calendar-line"></i> </div>
                                                                                    <input type="text" class="form-control datetime required" id="launch_time" name="launch_time" placeholder="Choose date with time">
                                                                                </div> -->

                                            </div>
                                            <div id="dvSchedule2" class="d-none">
                                                <label
                                                    class="text-left control-label col-form-label font-italic mt-3 pt-0"><b>Note:</b>We
                                                    will capture employee interactions as long as a campaign remains active
                                                    (isn't updated or deleted). </label>
                                                <div class="row mb-3">
                                                    <label for="inputEmail3" class="col-sm-4 col-form-label">Schedule
                                                        Date<i class='bx bx-info-circle p-2'
                                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                                            data-bs-original-title="Select a particular date for shooting this campaign"></i>
                                                    </label>
                                                    <div class="col-sm-8">
                                                        <div class="form-group">
                                                            <div class="input-group">

                                                                <input type="text"
                                                                    class="form-control flatpickr-input active"
                                                                    id="schBetRange" placeholder="YYYY-MM-DD"
                                                                    readonly="readonly">
                                                                <div class="input-group-text text-muted"> <i
                                                                        class="ri-calendar-line"></i> </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row mb-3">
                                                    <label for="inputEmail3" class="col-sm-4 col-form-label">Schedule
                                                        (Between Times) <i class='bx bx-info-circle p-2'
                                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                                            data-bs-original-title="We recommend scheduling campaigns between business hours to get the most ineraction (e.g. 9am - 5pm)"></i></label>
                                                    <div class="col-sm-8">
                                                        <div class="form-group d-flex">
                                                            <input type="time" id="schTimeStart" name="appt"
                                                                class="form-control" value="09:00" step="60">
                                                            <label class="col-md-1 m-t-15" style="text-align:center"> To
                                                            </label>
                                                            <input type="time" id="schTimeEnd" name="appt"
                                                                class="form-control" value="17:00" step="60">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row mb-3">
                                                    <label for="inputEmail3" class="col-sm-4 col-form-label">Schedule
                                                        (Time Zone) <i class='bx bx-info-circle p-2'
                                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                                            data-bs-original-title="Select the timezone that best aligns with your business hours."></i></label>
                                                    <div class="col-sm-8">
                                                        <div class="form-group d-flex">
                                                            <select
                                                                class="select2 form-control custom-select select2-hidden-accessible"
                                                                style="width: 100%" id="schTimeZone" aria-invalid="false"
                                                                data-select2-id="schTimeZone" tabindex="-1"
                                                                aria-hidden="true">
                                                                <option value="Australia/Canberra" data-select2-id="390">
                                                                    (GMT+10:00) Canberra, Melbourne, Sydney</option>
                                                                <option value="Etc/GMT+12">(GMT-12:00) International Date
                                                                    Line West</option>
                                                                <option value="Pacific/Midway">(GMT-11:00) Midway Island,
                                                                    Samoa</option>
                                                                <option value="Pacific/Honolulu">(GMT-10:00) Hawaii
                                                                </option>
                                                                <option value="US/Alaska">(GMT-09:00) Alaska</option>
                                                                <option value="America/Los_Angeles">(GMT-08:00) Pacific
                                                                    Time (US &amp; Canada)</option>
                                                                <option value="America/Tijuana">(GMT-08:00) Tijuana, Baja
                                                                    California</option>
                                                                <option value="US/Arizona">(GMT-07:00) Arizona</option>
                                                                <option value="America/Chihuahua">(GMT-07:00) Chihuahua, La
                                                                    Paz, Mazatlan</option>
                                                                <option value="US/Mountain">(GMT-07:00) Mountain Time (US
                                                                    &amp; Canada)</option>
                                                                <option value="America/Managua">(GMT-06:00) Central America
                                                                </option>
                                                                <option value="US/Central">(GMT-06:00) Central Time (US
                                                                    &amp; Canada)</option>
                                                                <option value="America/Mexico_City">(GMT-06:00)
                                                                    Guadalajara, Mexico City, Monterrey</option>
                                                                <option value="Canada/Saskatchewan">(GMT-06:00)
                                                                    Saskatchewan</option>
                                                                <option value="America/Bogota">(GMT-05:00) Bogota, Lima,
                                                                    Quito, Rio Branco</option>
                                                                <option value="US/Eastern">(GMT-05:00) Eastern Time (US
                                                                    &amp; Canada)</option>
                                                                <option value="US/East-Indiana">(GMT-05:00) Indiana (East)
                                                                </option>
                                                                <option value="Canada/Atlantic">(GMT-04:00) Atlantic Time
                                                                    (Canada)</option>
                                                                <option value="America/Caracas">(GMT-04:00) Caracas, La Paz
                                                                </option>
                                                                <option value="America/Manaus">(GMT-04:00) Manaus</option>
                                                                <option value="America/Santiago">(GMT-04:00) Santiago
                                                                </option>
                                                                <option value="Canada/Newfoundland">(GMT-03:30)
                                                                    Newfoundland</option>
                                                                <option value="America/Sao_Paulo">(GMT-03:00) Brasilia
                                                                </option>
                                                                <option value="America/Argentina/Buenos_Aires">(GMT-03:00)
                                                                    Buenos Aires, Georgetown</option>
                                                                <option value="America/Godthab">(GMT-03:00) Greenland
                                                                </option>
                                                                <option value="America/Montevideo">(GMT-03:00) Montevideo
                                                                </option>
                                                                <option value="America/Noronha">(GMT-02:00) Mid-Atlantic
                                                                </option>
                                                                <option value="Atlantic/Cape_Verde">(GMT-01:00) Cape Verde
                                                                    Is.</option>
                                                                <option value="Atlantic/Azores">(GMT-01:00) Azores</option>
                                                                <option value="Africa/Casablanca">(GMT+00:00) Casablanca,
                                                                    Monrovia, Reykjavik</option>
                                                                <option value="Etc/Greenwich" data-select2-id="418">
                                                                    (GMT+00:00) Greenwich Mean Time : Dublin, Edinburgh,
                                                                    Lisbon, London</option>
                                                                <option value="Europe/Amsterdam">(GMT+01:00) Amsterdam,
                                                                    Berlin, Bern, Rome, Stockholm, Vienna</option>
                                                                <option value="Europe/Belgrade">(GMT+01:00) Belgrade,
                                                                    Bratislava, Budapest, Ljubljana, Prague</option>
                                                                <option value="Europe/Brussels">(GMT+01:00) Brussels,
                                                                    Copenhagen, Madrid, Paris</option>
                                                                <option value="Europe/Sarajevo">(GMT+01:00) Sarajevo,
                                                                    Skopje, Warsaw, Zagreb</option>
                                                                <option value="Africa/Lagos">(GMT+01:00) West Central
                                                                    Africa</option>
                                                                <option value="Asia/Amman">(GMT+02:00) Amman</option>
                                                                <option value="Europe/Athens">(GMT+02:00) Athens,
                                                                    Bucharest, Istanbul</option>
                                                                <option value="Asia/Beirut">(GMT+02:00) Beirut</option>
                                                                <option value="Africa/Cairo">(GMT+02:00) Cairo</option>
                                                                <option value="Africa/Harare">(GMT+02:00) Harare, Pretoria
                                                                </option>
                                                                <option value="Europe/Helsinki">(GMT+02:00) Helsinki, Kyiv,
                                                                    Riga, Sofia, Tallinn, Vilnius</option>
                                                                <option value="Asia/Jerusalem">(GMT+02:00) Jerusalem
                                                                </option>
                                                                <option value="Europe/Minsk">(GMT+02:00) Minsk</option>
                                                                <option value="Africa/Windhoek">(GMT+02:00) Windhoek
                                                                </option>
                                                                <option value="Asia/Kuwait">(GMT+03:00) Kuwait, Riyadh,
                                                                    Baghdad</option>
                                                                <option value="Europe/Moscow">(GMT+03:00) Moscow, St.
                                                                    Petersburg, Volgograd</option>
                                                                <option value="Africa/Nairobi">(GMT+03:00) Nairobi</option>
                                                                <option value="Asia/Tbilisi">(GMT+03:00) Tbilisi</option>
                                                                <option value="Asia/Tehran">(GMT+03:30) Tehran</option>
                                                                <option value="Asia/Muscat">(GMT+04:00) Abu Dhabi, Muscat
                                                                </option>
                                                                <option value="Asia/Baku">(GMT+04:00) Baku</option>
                                                                <option value="Asia/Yerevan">(GMT+04:00) Yerevan</option>
                                                                <option value="Asia/Kabul">(GMT+04:30) Kabul</option>
                                                                <option value="Asia/Yekaterinburg">(GMT+05:00)
                                                                    Yekaterinburg</option>
                                                                <option value="Asia/Karachi">(GMT+05:00) Islamabad,
                                                                    Karachi, Tashkent</option>
                                                                <option value="Asia/Kolkata">(GMT+05:30) Chennai, Kolkata,
                                                                    Mumbai, New Delhi</option>
                                                                <option value="Asia/Kolkata">(GMT+05:30) Sri
                                                                    Jayawardenapura</option>
                                                                <option value="Asia/Katmandu">(GMT+05:45) Kathmandu
                                                                </option>
                                                                <option value="Asia/Almaty">(GMT+06:00) Almaty, Novosibirsk
                                                                </option>
                                                                <option value="Asia/Dhaka">(GMT+06:00) Astana, Dhaka
                                                                </option>
                                                                <option value="Asia/Rangoon">(GMT+06:30) Yangon (Rangoon)
                                                                </option>
                                                                <option value="Asia/Bangkok">(GMT+07:00) Bangkok, Hanoi,
                                                                    Jakarta</option>
                                                                <option value="Asia/Krasnoyarsk">(GMT+07:00) Krasnoyarsk
                                                                </option>
                                                                <option value="Asia/Hong_Kong">(GMT+08:00) Beijing,
                                                                    Chongqing, Hong Kong, Urumqi</option>
                                                                <option value="Asia/Kuala_Lumpur">(GMT+08:00) Kuala Lumpur,
                                                                    Singapore</option>
                                                                <option value="Asia/Irkutsk">(GMT+08:00) Irkutsk, Ulaan
                                                                    Bataar</option>
                                                                <option value="Australia/Perth">(GMT+08:00) Perth</option>
                                                                <option value="Asia/Taipei">(GMT+08:00) Taipei</option>
                                                                <option value="Asia/Tokyo">(GMT+09:00) Osaka, Sapporo,
                                                                    Tokyo</option>
                                                                <option value="Asia/Seoul">(GMT+09:00) Seoul</option>
                                                                <option value="Asia/Yakutsk">(GMT+09:00) Yakutsk</option>
                                                                <option value="Australia/Adelaide">(GMT+09:30) Adelaide
                                                                </option>
                                                                <option value="Australia/Darwin">(GMT+09:30) Darwin
                                                                </option>
                                                                <option value="Australia/Brisbane">(GMT+10:00) Brisbane
                                                                </option>
                                                                <option value="Australia/Canberra">(GMT+10:00) Canberra,
                                                                    Melbourne, Sydney</option>
                                                                <option value="Australia/Hobart">(GMT+10:00) Hobart
                                                                </option>
                                                                <option value="Pacific/Guam">(GMT+10:00) Guam, Port Moresby
                                                                </option>
                                                                <option value="Asia/Vladivostok">(GMT+10:00) Vladivostok
                                                                </option>
                                                                <option value="Asia/Magadan">(GMT+11:00) Magadan, Solomon
                                                                    Is., New Caledonia</option>
                                                                <option value="Pacific/Auckland">(GMT+12:00) Auckland,
                                                                    Wellington</option>
                                                                <option value="Pacific/Fiji">(GMT+12:00) Fiji, Kamchatka,
                                                                    Marshall Is.</option>
                                                                <option value="Pacific/Tongatapu">(GMT+13:00) Nuku'alofa
                                                                </option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>


                                            </div>

                                            <div id="email_frequency">


                                                <div class="d-flex">

                                                    <div class="checkb mx-1">

                                                        <input type="radio" class="btn-check" name="emailFreq"
                                                            data-val="One-off" value="one" id="foneoff" checked>
                                                        <label class="btn btn-outline-dark mb-3"
                                                            for="foneoff">One-off</label>
                                                    </div>
                                                    <div class="checkb mx-1">

                                                        <input type="radio" class="btn-check" name="emailFreq"
                                                            data-val="Monthly" value="monthly" id="fmonthly">
                                                        <label class="btn btn-outline-dark mb-3"
                                                            for="fmonthly">Monthly</label>
                                                    </div>

                                                    <div class="checkb mx-1">

                                                        <input type="radio" class="btn-check" name="emailFreq"
                                                            data-val="Weekly" value="weekly" id="fweekly">
                                                        <label class="btn btn-outline-dark mb-3"
                                                            for="fweekly">Weekly</label>
                                                    </div>
                                                    <div class="checkb mx-1">

                                                        <input type="radio" class="btn-check" name="emailFreq"
                                                            data-val="Quaterly" value="quaterly" id="fquaterly">
                                                        <label class="btn btn-outline-dark mb-3"
                                                            for="fquaterly">Quaterly</label>
                                                    </div>
                                                    <div id="exp_after" class="d-none">
                                                        <div class="input-group">
                                                            <div class="input-group-text text-muted"> Expire After</div>
                                                            <input type="text"
                                                                class="form-control flatpickr-input active"
                                                                id="expire_after" placeholder="Choose date"
                                                                readonly="readonly">
                                                            <div class="input-group-text text-muted"> <i
                                                                    class="ri-calendar-line"></i> </div>
                                                        </div>
                                                    </div>



                                                </div>
                                            </div>


                                        </div>
                                        <button type="button"
                                            class="btn btn-dark label-btn label-end rounded-pill previous">
                                            Previous
                                            <i class="ri-arrow-left-line label-btn-icon ms-2 rounded-pill"></i>
                                        </button>

                                        <button type="button"
                                            class="btn btn-info label-btn last-step label-end rounded-pill next">
                                            Next
                                            <i class="ri-arrow-right-line label-btn-icon ms-2 rounded-pill"></i>
                                        </button>

                                    </fieldset>

                                    <fieldset class="included">
                                        <div class="form-card row">
                                            <div class="col-lg-6 mb-3">
                                                <div>
                                                    <label for="input-label" class="form-label">Campaign Name</label>
                                                    <input type="text" class="form-control" id="revCampName" disabled
                                                        readonly>
                                                </div>
                                            </div>
                                            <div class="col-lg-6 mb-3">
                                                <div>
                                                    <label for="input-label" class="form-label">Campaign Type</label>
                                                    <input type="text" class="form-control" id="revCampType" disabled
                                                        readonly>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 mb-3">
                                                <div>
                                                    <label for="input-label" class="form-label">Employee Group</label>
                                                    <input type="text" class="form-control" id="revEmpGroup" disabled
                                                        readonly>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 mb-3">
                                                <div>
                                                    <label for="input-label" class="form-label">Email Language</label>
                                                    <input type="text" class="form-control" id="revEmailLang" disabled
                                                        readonly>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 mb-3">
                                                <div>
                                                    <label for="input-label" class="form-label">Phishing Material</label>
                                                    <input type="text" class="form-control" id="revPhishmat" disabled
                                                        readonly>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 mb-3">
                                                <div>
                                                    <label for="input-label" class="form-label">Training Language</label>
                                                    <input type="text" class="form-control" id="revTrainingLang"
                                                        disabled readonly>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 mb-3">
                                                <div>
                                                    <label for="input-label" class="form-label">Training Module</label>
                                                    <input type="text" class="form-control" id="revTrainingMod"
                                                        disabled readonly>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 mb-3">
                                                <div>
                                                    <label for="input-label" class="form-label">Campaign Delivery</label>
                                                    <input type="text" class="form-control" id="revCampDelivery"
                                                        disabled readonly>
                                                </div>
                                            </div>
                                            <div class="col-lg-6 mb-3">
                                                <div>
                                                    <label for="input-label" class="form-label">Schedule Date</label>
                                                    <input type="text" class="form-control" id="revBtwDays" disabled
                                                        readonly>
                                                </div>
                                            </div>
                                            <div class="col-lg-6 mb-3" id="revBtwTime">
                                                <div>
                                                    <label for="input-label" class="form-label">Schedule Between
                                                        Times</label>
                                                    <div>
                                                        <div class="form-group d-flex">
                                                            <input type="time" id="revSchTimeStart" name="appt"
                                                                class="form-control" value="09:00" step="60"
                                                                disabled readonly>
                                                            <label class="col-md-1 m-t-15" style="text-align:center"> To
                                                            </label>
                                                            <input type="time" id="revSchTimeEnd" name="appt"
                                                                class="form-control" value="17:00" step="60"
                                                                disabled readonly>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 mb-3">
                                                <div>
                                                    <label for="input-label" class="form-label">Schedule Time Zone</label>
                                                    <input type="text" class="form-control" id="revSchTimeZone"
                                                        disabled readonly>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 mb-3">
                                                <div>
                                                    <label for="input-label" class="form-label">Email Frequency</label>
                                                    <input type="text" class="form-control" id="revEmailFreq" disabled
                                                        readonly>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 mb-3">
                                                <div>
                                                    <label for="input-label" class="form-label">Expire After</label>
                                                    <input type="text" class="form-control" id="revExpAfter" disabled
                                                        readonly>
                                                </div>
                                            </div>


                                        </div>

                                        <button type="button"
                                            class="btn btn-dark label-btn label-end rounded-pill previous">
                                            Previous
                                            <i class="ri-arrow-left-line label-btn-icon ms-2 rounded-pill"></i>
                                        </button>

                                        <button type="submit" id="createCampaign"
                                            class="btn btn-info label-btn label-end rounded-pill">
                                            Submit
                                            <i class="ri-arrow-right-line label-btn-icon ms-2 rounded-pill"></i>
                                        </button>
                                    </fieldset>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- campaign report modal -->
    <div class="modal fade" id="campaignReportModal" tabindex="-1" aria-labelledby="exampleModalLgLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Campaign Report</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="card custom-card">

                        <div class="card-body">
                            <ul class="nav nav-pills nav-style-3 mb-3" role="tablist">
                                <li class="nav-item" role="presentation" id="phishing_tab">
                                    <a class="nav-link active" data-bs-toggle="tab" role="tab" aria-current="page"
                                        href="#phishing_campaign" aria-selected="true">Phishing Campaign</a>
                                </li>
                                <li class="nav-item" role="presentation" id="training_tab">
                                    <a class="nav-link" data-bs-toggle="tab" role="tab" aria-current="page"
                                        href="#training_campaign" aria-selected="false" tabindex="-1">Training
                                        Campaign</a>
                                </li>
                            </ul>
                            <div class="tab-content">
                                <div class="tab-pane show active text-muted" id="phishing_campaign" role="tabpanel">
                                    <div class="table-responsive">
                                        <table class="table text-nowrap table-striped">
                                            <thead>
                                                <tr>
                                                    <th scope="col">Campaign name</th>
                                                    <th scope="col">Status</th>
                                                    <th scope="col">Employees</th>
                                                    <th scope="col">Emails Delivered</th>
                                                    <th scope="col">Emails Viewed</th>
                                                    <th scope="col">Payloads Clicked</th>
                                                    <th scope="col">Employees Compromised</th>
                                                    <th scope="col">Emails Reported</th>
                                                </tr>
                                            </thead>
                                            <tbody id="campReportStatus">
                                            </tbody>
                                        </table>
                                    </div>

                                    <hr>

                                    <div class="card custom-card">
                                        <div class="card-header">
                                            <div class="card-title">Phishing Campaign Statistics</div>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table id="file-export" class="table table-bordered text-nowrap w-100">
                                                    <thead>
                                                        <tr>
                                                            <th>Employee Name</th>
                                                            <th>Email Address</th>
                                                            <th>Email Delivery</th>
                                                            <th>Email Viewed</th>
                                                            <th>Payload Clicked</th>
                                                            <th>Employee Compromised</th>
                                                            <th>Email Reported</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="campReportsIndividual">

                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane text-muted" id="training_campaign" role="tabpanel">
                                    <div class="table-responsive">
                                        <table class="table text-nowrap table-striped">
                                            <thead>
                                                <tr>
                                                    <th scope="col">Campaign name</th>
                                                    <th scope="col">Status</th>
                                                    <th scope="col">Employees</th>
                                                    <th scope="col">Trainings Assigned</th>
                                                    <th scope="col">Trainings Completed</th>
                                                </tr>
                                            </thead>
                                            <tbody id="trainingReportStatus">
                                            </tbody>
                                        </table>
                                    </div>

                                    <hr>

                                    <div class="card custom-card">
                                        <div class="card-header">
                                            <div class="card-title">Training Campaign Statistics</div>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table id="file-export2" class="table table-bordered text-nowrap w-100">
                                                    <thead>
                                                        <tr>
                                                            <th>Email Address</th>
                                                            <th>Training Module</th>
                                                            <th>Date Assigned</th>
                                                            <th>Score</th>
                                                            <th>Passing Score</th>
                                                            <th>Status</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="trainingReportsIndividual">

                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- view material modal -->
    <div class="modal fade" id="viewMaterialModal" tabindex="-1" aria-labelledby="exampleModalLgLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Phishing Material</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="min-height: 100vh;">
                    <div class="card-body">
                        <ul class="nav nav-tabs mb-3 tab-style-6" id="myTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="products-tab" data-bs-toggle="tab"
                                    data-bs-target="#email-tab-pane" type="button" role="tab"
                                    aria-controls="email-tab-pane" aria-selected="true"><i
                                        class="bx bx-envelope me-1 align-middle d-inline-block"></i>Email</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="sales-tab" data-bs-toggle="tab"
                                    data-bs-target="#website-tab-pane" type="button" role="tab"
                                    aria-controls="website-tab-pane" aria-selected="false" tabindex="-1"><i
                                        class="bx bx-globe me-1 align-middle d-inline-block"></i>Website</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="profit-tab" data-bs-toggle="tab"
                                    data-bs-target="#senderp-tab-pane" type="button" role="tab"
                                    aria-controls="senderp-tab-pane" aria-selected="false" tabindex="-1"><i
                                        class="bx bx-envelope me-1 align-middle d-inline-block"></i>Sender Profile</button>
                            </li>

                        </ul>
                        <div class="tab-content" id="myTabContent2">
                            <div class="tab-pane fade p-3 border-bottom-0 active show" id="email-tab-pane"
                                role="tabpanel" aria-labelledby="products-tab" tabindex="0">

                                <div class="row mb-3">
                                    <label for="vphishEmail" class="col-sm-6 col-form-label">Phishing Email</label>
                                    <div class="col-sm-6">
                                        <input type="email" class="form-control" id="vphishEmail" disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="vSub" class="col-sm-6 col-form-label">Email Subject</label>
                                    <div class="col-sm-6">
                                        <input type="email" class="form-control" id="vSub" disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="inputEmail3" class="col-sm-6 col-form-label">Employee Requirements</label>
                                    <div class="col-sm-6">
                                        <input type="email" class="form-control" id="inputEmail3"
                                            value="Email Address | Name" disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-lg-12" id="phishPrev" style="background: white;">

                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade p-3" id="website-tab-pane" role="tabpanel"
                                aria-labelledby="sales-tab" tabindex="0">

                                <div class="row mb-3">
                                    <label for="vphishWeb" class="col-sm-6 col-form-label">Phishing Website</label>
                                    <div class="col-sm-6">
                                        <input type="email" class="form-control" id="vphishWeb" disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="vPhishUrl" class="col-sm-6 col-form-label">Website URL</label>
                                    <div class="col-sm-6">
                                        <input type="text" class="form-control" id="vPhishUrl" disabled>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-lg-12" id="websitePrev" style="background: white;">
                                        <iframe class="phishing-iframe" src="" style="height: 500px;">

                                        </iframe>
                                    </div>
                                </div>

                            </div>
                            <div class="tab-pane fade p-3" id="senderp-tab-pane" role="tabpanel"
                                aria-labelledby="profit-tab" tabindex="0">

                                <div class="row mb-3">
                                    <label for="vsenderProf" class="col-sm-6 col-form-label">Sender Profile</label>
                                    <div class="col-sm-6">
                                        <input type="text" class="form-control" id="vsenderProf" disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="vDispName" class="col-sm-6 col-form-label">Display Name & Address</label>
                                    <div class="col-sm-6">
                                        <input type="text" class="form-control" id="vDispName" disabled>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- re-schedule campaign modal -->
    <div class="modal fade" id="reschedulemodal" tabindex="-1" aria-labelledby="exampleModalLgLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Re-Schedule Campaign
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{route('reschedule.campaign')}}" method="post" id="rescheduleForm">
                        @csrf
                        <p class="text-center">Schedule Type</p>
                        <div class="form-card">
                            <div class="d-flex justify-content-center">
                                <div class="checkb mx-1">

                                    <input type="radio" class="btn-check" name="rschType" data-val="Imediately" value="immediately" id="rimediateBtn" checked>
                                    <label class="btn btn-outline-dark mb-3" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Campaign will begin delivering emails within 1-3 minutes of submission." id="rimediateLabelBtn" for="rimediateBtn">Deliver Immediately </label>
                                </div>
                                <div class="checkb mx-1">

                                    <input type="radio" class="btn-check" name="rschType" data-val="Setup Schedule" value="scheduled" id="rScheduleBtn">
                                    <label class="btn btn-outline-dark mb-3" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Campaign will deliver emails using a defined schedule over a period of hours and days (e.g. 9am-5pm Monday-Friday)." id="rscheduleLabelBtn" for="rScheduleBtn">Setup Schedule</label>
                                </div>



                            </div>
                            <div id="rdvSchedule2" class="d-none">
                                <label class="text-left control-label col-form-label font-italic mt-3 pt-0"><b>Note:</b>We will capture employee interactions as long as a campaign remains active (isn't updated or deleted). </label>
                                <div class="row mb-3">
                                    <label for="inputEmail3" class="col-sm-4 col-form-label">Schedule Date<i class='bx bx-info-circle p-2' data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Select schedule date for started shooting this campaign"></i> </label>
                                    <div class="col-sm-8">
                                        <div class="form-group">
                                            <div class="input-group">

                                                <input type="text" class="form-control flatpickr-input active" name="rsc_launch_time" id="rschBetRange" placeholder="YYYY-MM-DD" required readonly="readonly">
                                                <div class="input-group-text text-muted"> <i class="ri-calendar-line"></i> </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label for="inputEmail3" class="col-sm-4 col-form-label">Schedule (Between Times) <i class='bx bx-info-circle p-2' data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="We recommend scheduling campaigns between business hours to get the most ineraction (e.g. 9am - 5pm)"></i></label>
                                    <div class="col-sm-8">
                                        <div class="form-group d-flex">
                                            <input type="time" id="rschTimeStart" name="startTime" class="form-control" value="09:00" step="60">
                                            <label class="col-md-1 m-t-15" style="text-align:center"> To </label>
                                            <input type="time" id="rschTimeEnd" name="endTime" class="form-control" value="17:00" step="60">
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label for="inputEmail3" class="col-sm-4 col-form-label">Schedule (Time Zone) <i class='bx bx-info-circle p-2' data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Select the timezone that best aligns with your business hours."></i></label>
                                    <div class="col-sm-8">
                                        <div class="form-group d-flex">
                                            <select class="select2 form-control custom-select select2-hidden-accessible" style="width: 100%" id="rschTimeZone" name="rschTimeZone">
                                                <option value="Australia/Canberra" data-select2-id="390">(GMT+10:00) Canberra, Melbourne, Sydney</option>
                                                <option value="Etc/GMT+12">(GMT-12:00) International Date Line West</option>
                                                <option value="Pacific/Midway">(GMT-11:00) Midway Island, Samoa</option>
                                                <option value="Pacific/Honolulu">(GMT-10:00) Hawaii</option>
                                                <option value="US/Alaska">(GMT-09:00) Alaska</option>
                                                <option value="America/Los_Angeles">(GMT-08:00) Pacific Time (US &amp; Canada)</option>
                                                <option value="America/Tijuana">(GMT-08:00) Tijuana, Baja California</option>
                                                <option value="US/Arizona">(GMT-07:00) Arizona</option>
                                                <option value="America/Chihuahua">(GMT-07:00) Chihuahua, La Paz, Mazatlan</option>
                                                <option value="US/Mountain">(GMT-07:00) Mountain Time (US &amp; Canada)</option>
                                                <option value="America/Managua">(GMT-06:00) Central America</option>
                                                <option value="US/Central">(GMT-06:00) Central Time (US &amp; Canada)</option>
                                                <option value="America/Mexico_City">(GMT-06:00) Guadalajara, Mexico City, Monterrey</option>
                                                <option value="Canada/Saskatchewan">(GMT-06:00) Saskatchewan</option>
                                                <option value="America/Bogota">(GMT-05:00) Bogota, Lima, Quito, Rio Branco</option>
                                                <option value="US/Eastern">(GMT-05:00) Eastern Time (US &amp; Canada)</option>
                                                <option value="US/East-Indiana">(GMT-05:00) Indiana (East)</option>
                                                <option value="Canada/Atlantic">(GMT-04:00) Atlantic Time (Canada)</option>
                                                <option value="America/Caracas">(GMT-04:00) Caracas, La Paz</option>
                                                <option value="America/Manaus">(GMT-04:00) Manaus</option>
                                                <option value="America/Santiago">(GMT-04:00) Santiago</option>
                                                <option value="Canada/Newfoundland">(GMT-03:30) Newfoundland</option>
                                                <option value="America/Sao_Paulo">(GMT-03:00) Brasilia</option>
                                                <option value="America/Argentina/Buenos_Aires">(GMT-03:00) Buenos Aires, Georgetown</option>
                                                <option value="America/Godthab">(GMT-03:00) Greenland</option>
                                                <option value="America/Montevideo">(GMT-03:00) Montevideo</option>
                                                <option value="America/Noronha">(GMT-02:00) Mid-Atlantic</option>
                                                <option value="Atlantic/Cape_Verde">(GMT-01:00) Cape Verde Is.</option>
                                                <option value="Atlantic/Azores">(GMT-01:00) Azores</option>
                                                <option value="Africa/Casablanca">(GMT+00:00) Casablanca, Monrovia, Reykjavik</option>
                                                <option value="Etc/Greenwich" data-select2-id="418">(GMT+00:00) Greenwich Mean Time : Dublin, Edinburgh, Lisbon, London</option>
                                                <option value="Europe/Amsterdam">(GMT+01:00) Amsterdam, Berlin, Bern, Rome, Stockholm, Vienna</option>
                                                <option value="Europe/Belgrade">(GMT+01:00) Belgrade, Bratislava, Budapest, Ljubljana, Prague</option>
                                                <option value="Europe/Brussels">(GMT+01:00) Brussels, Copenhagen, Madrid, Paris</option>
                                                <option value="Europe/Sarajevo">(GMT+01:00) Sarajevo, Skopje, Warsaw, Zagreb</option>
                                                <option value="Africa/Lagos">(GMT+01:00) West Central Africa</option>
                                                <option value="Asia/Amman">(GMT+02:00) Amman</option>
                                                <option value="Europe/Athens">(GMT+02:00) Athens, Bucharest, Istanbul</option>
                                                <option value="Asia/Beirut">(GMT+02:00) Beirut</option>
                                                <option value="Africa/Cairo">(GMT+02:00) Cairo</option>
                                                <option value="Africa/Harare">(GMT+02:00) Harare, Pretoria</option>
                                                <option value="Europe/Helsinki">(GMT+02:00) Helsinki, Kyiv, Riga, Sofia, Tallinn, Vilnius</option>
                                                <option value="Asia/Jerusalem">(GMT+02:00) Jerusalem</option>
                                                <option value="Europe/Minsk">(GMT+02:00) Minsk</option>
                                                <option value="Africa/Windhoek">(GMT+02:00) Windhoek</option>
                                                <option value="Asia/Kuwait">(GMT+03:00) Kuwait, Riyadh, Baghdad</option>
                                                <option value="Europe/Moscow">(GMT+03:00) Moscow, St. Petersburg, Volgograd</option>
                                                <option value="Africa/Nairobi">(GMT+03:00) Nairobi</option>
                                                <option value="Asia/Tbilisi">(GMT+03:00) Tbilisi</option>
                                                <option value="Asia/Tehran">(GMT+03:30) Tehran</option>
                                                <option value="Asia/Muscat">(GMT+04:00) Abu Dhabi, Muscat</option>
                                                <option value="Asia/Baku">(GMT+04:00) Baku</option>
                                                <option value="Asia/Yerevan">(GMT+04:00) Yerevan</option>
                                                <option value="Asia/Kabul">(GMT+04:30) Kabul</option>
                                                <option value="Asia/Yekaterinburg">(GMT+05:00) Yekaterinburg</option>
                                                <option value="Asia/Karachi">(GMT+05:00) Islamabad, Karachi, Tashkent</option>
                                                <option value="Asia/Calcutta">(GMT+05:30) Chennai, Kolkata, Mumbai, New Delhi</option>
                                                <option value="Asia/Calcutta">(GMT+05:30) Sri Jayawardenapura</option>
                                                <option value="Asia/Katmandu">(GMT+05:45) Kathmandu</option>
                                                <option value="Asia/Almaty">(GMT+06:00) Almaty, Novosibirsk</option>
                                                <option value="Asia/Dhaka">(GMT+06:00) Astana, Dhaka</option>
                                                <option value="Asia/Rangoon">(GMT+06:30) Yangon (Rangoon)</option>
                                                <option value="Asia/Bangkok">(GMT+07:00) Bangkok, Hanoi, Jakarta</option>
                                                <option value="Asia/Krasnoyarsk">(GMT+07:00) Krasnoyarsk</option>
                                                <option value="Asia/Hong_Kong">(GMT+08:00) Beijing, Chongqing, Hong Kong, Urumqi</option>
                                                <option value="Asia/Kuala_Lumpur">(GMT+08:00) Kuala Lumpur, Singapore</option>
                                                <option value="Asia/Irkutsk">(GMT+08:00) Irkutsk, Ulaan Bataar</option>
                                                <option value="Australia/Perth">(GMT+08:00) Perth</option>
                                                <option value="Asia/Taipei">(GMT+08:00) Taipei</option>
                                                <option value="Asia/Tokyo">(GMT+09:00) Osaka, Sapporo, Tokyo</option>
                                                <option value="Asia/Seoul">(GMT+09:00) Seoul</option>
                                                <option value="Asia/Yakutsk">(GMT+09:00) Yakutsk</option>
                                                <option value="Australia/Adelaide">(GMT+09:30) Adelaide</option>
                                                <option value="Australia/Darwin">(GMT+09:30) Darwin</option>
                                                <option value="Australia/Brisbane">(GMT+10:00) Brisbane</option>
                                                <option value="Australia/Canberra">(GMT+10:00) Canberra, Melbourne, Sydney</option>
                                                <option value="Australia/Hobart">(GMT+10:00) Hobart</option>
                                                <option value="Pacific/Guam">(GMT+10:00) Guam, Port Moresby</option>
                                                <option value="Asia/Vladivostok">(GMT+10:00) Vladivostok</option>
                                                <option value="Asia/Magadan">(GMT+11:00) Magadan, Solomon Is., New Caledonia</option>
                                                <option value="Pacific/Auckland">(GMT+12:00) Auckland, Wellington</option>
                                                <option value="Pacific/Fiji">(GMT+12:00) Fiji, Kamchatka, Marshall Is.</option>
                                                <option value="Pacific/Tongatapu">(GMT+13:00) Nuku'alofa</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>


                            </div>
                            <hr style="margin: 4px;">
                            <div id="remail_frequency">

                                <p class="text-center">Email Frequency</p>
                                <div class="d-flex justify-content-center">

                                    <div class="checkb mx-1">

                                        <input type="radio" class="btn-check" name="emailFreq" data-val="One-off" value="one" id="rfoneoff" checked>
                                        <label class="btn btn-outline-dark mb-3" for="rfoneoff">One-off</label>
                                    </div>
                                    <div class="checkb mx-1">

                                        <input type="radio" class="btn-check" name="emailFreq" data-val="Monthly" value="monthly" id="rfmonthly">
                                        <label class="btn btn-outline-dark mb-3" for="rfmonthly">Monthly</label>
                                    </div>

                                    <div class="checkb mx-1">

                                        <input type="radio" class="btn-check" name="emailFreq" data-val="Weekly" value="weekly" id="rfweekly">
                                        <label class="btn btn-outline-dark mb-3" for="rfweekly">Weekly</label>
                                    </div>
                                    <div class="checkb mx-1">

                                        <input type="radio" class="btn-check" name="emailFreq" data-val="Quaterly" value="quaterly" id="rfquaterly">
                                        <label class="btn btn-outline-dark mb-3" for="rfquaterly">Quaterly</label>
                                    </div>
                                    <div id="rexp_after" class="d-none">
                                        <div class="input-group">
                                            <div class="input-group-text text-muted"> Expire After</div>
                                            <input type="text" class="form-control flatpickr-input active" name="rexpire_after" id="rexpire_after" placeholder="Choose date" readonly="readonly">
                                            <div class="input-group-text text-muted"> <i class="ri-calendar-line"></i> </div>
                                        </div>
                                    </div>



                                </div>
                            </div>

                            <div class="text-center">
                                <input type="hidden" name="campid" id="recampid">
                                <button type="submit" id="rescheduleBtn" class="btn btn-primary btn-wave waves-effect waves-light">Re-schedule</button>
                            </div>


                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>



    {{-- --------------------- modals ---------------------- --}}


    @push('newcss')
        <link rel="stylesheet" href="assets/css/campaigns.css">
    @endpush

    @push('newscripts')
        <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

        <!-- Datatables Cdn -->
        <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>
        <script src="https://cdn.datatables.net/responsive/2.3.0/js/dataTables.responsive.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.print.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.6/pdfmake.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>


        <script src="assets/js/campaigns.js"></script>

        <script>
            function showMaterialDetails(btn, name, subject, website, senderProfile) {

                $("#viewMaterialModal").modal('show');
                $("#newCampModal").hide();

                var htmlmaterial = $(btn).parent().parent().parent().prev().html();
                $("#vphishEmail").val(name);
                $("#vSub").val(subject);

                $.post({
                    url: '{{ route('campaigns.fetch.phish.data') }}',
                    data: {
                        fetchPhisData: 1,
                        website: website,
                        senderProfile: senderProfile
                    },
                    success: function(jsonRes) {
                        $("#websitePrev iframe").attr('src',
                            `https://${jsonRes.website_url}/${jsonRes.website_file}`);
                        $("#vphishWeb").val(jsonRes.website_name);


                        $("#vPhishUrl").val("https://" + jsonRes.website_url);
                        $("#vsenderProf").val(jsonRes.senderProfile);
                        $("#vDispName").val(jsonRes.displayName + "<" + jsonRes.address + ">");

                        // window.location.reload()
                        // window.location.href = window.location.href;
                    }
                })


                $("#phishPrev").html(htmlmaterial)
                // console.log(phishName);
            }

            function fetchCampaignDetails(campid) {
                // console.log(campid)
                $.post({
                    url: '{{ route('campaign.fetchCampaignReport') }}',
                    data: {
                        campaignId: campid
                    },
                    success: function(response) {

                        if (response.campaign_type === "Phishing") {
                            fetchCampReportByUsers()

                            $("#training_tab").hide();
                            $("#phishing_tab").show();
                        }
                        if (response.campaign_type === "Training") {
                            fetchCampTrainingDetails()
                            fetchCampTrainingDetailsIndividual()

                            $("#phishing_tab").hide();
                            $("#training_tab").show();
                            $("#phishing_campaign").removeClass("active show");

                            $("#training_tab a").addClass("active")
                            $("#training_campaign").addClass("active show")
                        }
                        if (response.campaign_type === "Phishing & Training") {
                            fetchCampReportByUsers()
                            fetchCampTrainingDetails()
                            fetchCampTrainingDetailsIndividual()

                            $("#training_tab").show();
                            $("#phishing_tab").show();
                            $("#phishing_campaign").addClass("active show");
                        }

                        let isDelivered = response.emails_delivered > 0 ?
                            '<i class="bx bx-check-circle text-success fs-25"></i>' :
                            '<i class="bx bx-check-circle text-danger fs-25"></i>';
                        let isViewed = response.emails_viewed > 0 ?
                            '<i class="bx bx-check-circle text-success fs-25"></i>' :
                            '<i class="bx bx-check-circle text-danger fs-25"></i>';
                        let isPayLoadClicked = response.payloads_clicked > 0 ?
                            '<i class="bx bx-check-circle text-success fs-25"></i>' :
                            '<i class="bx bx-check-circle text-danger fs-25"></i>';
                        let isEmpCompromised = response.emp_compromised > 0 ?
                            '<i class="bx bx-check-circle text-success fs-25"></i>' :
                            '<i class="bx bx-check-circle text-danger fs-25"></i>';
                        let isEmailReported = response.email_reported > 0 ?
                            '<i class="bx bx-check-circle text-success fs-25"></i>' :
                            '<i class="bx bx-check-circle text-danger fs-25"></i>';

                        let status = '';
                        if (response.status === 'completed') {
                            status = '<span class="badge bg-success">Completed</span>';
                        } else if (response.status === 'pending') {
                            status = '<span class="badge bg-warning">Pending</span>';
                        } else {
                            status = '<span class="badge bg-success">Running</span>';
                        }

                        let rowHtml = `
            <tr>
                <th scope="row">${response.campaign_name}</th>
                <td>${status}</td>
                <td>${response.no_of_users}</td>
                <td>
                    <div class="d-flex align-items-center">
                        <span class="mx-1">${response.emails_delivered}</span>
                        ${isDelivered}
                    </div>
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <span class="mx-1">${response.emails_viewed}</span>
                        ${isViewed}
                    </div>
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <span class="mx-1">${response.payloads_clicked}</span>
                        ${isPayLoadClicked}
                    </div>
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <span class="mx-1">${response.emp_compromised}</span>
                        ${isEmpCompromised}
                    </div>
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <span class="mx-1">${response.email_reported}</span>
                        ${isEmailReported}
                    </div>
                </td>
            </tr>
        `;

                        $("#campReportStatus").html(rowHtml);

                        // Example of showing/hiding a section based on a condition
                        // if (response.emails_delivered > 0) {
                        //     $("#someSection").show();
                        // } else {
                        //     $("#someSection").hide();
                        // }
                    }
                });

                function fetchCampReportByUsers() {
                    $.post({
                        url: '{{ route('campaign.fetchCampReportByUsers') }}',
                        data: {
                            fetchCampReportByUsers: '1',
                            campaignId: campid
                        },
                        success: function(res) {
                            //console.log(res)
                            $("#campReportsIndividual").html(res.html)

                            if (!$.fn.DataTable.isDataTable('#file-export')) {

                                $('#file-export').DataTable({
                                    dom: 'Bfrtip',
                                    buttons: [
                                        'copy', 'csv', 'excel', 'pdf', 'print'
                                    ],
                                    language: {
                                        searchPlaceholder: 'Search...',
                                        sSearch: '',
                                    },
                                });
                            }


                        }
                    })
                }

                function fetchCampTrainingDetails() {
                    $.post({
                        url: '{{ route('campaign.fetchCampTrainingDetails') }}',
                        data: {
                            fetchCampTrainingDetails: '1',
                            campaignId: campid
                        },
                        success: function(res) {
                            //console.log(res)
                            $("#trainingReportStatus").html(res.html)


                        }
                    })
                }

                function fetchCampTrainingDetailsIndividual() {
                    $.post({
                        url: '{{ route('campaign.fetchCampTrainingDetailsIndividual') }}',
                        data: {
                            fetchCampTrainingDetailsIndividual: '1',
                            campaignId: campid
                        },
                        success: function(res) {
                            //console.log(res)
                            $("#trainingReportsIndividual").html(res.html)


                        }
                    })
                }




            }

            $('#datatable-basic').DataTable({
                language: {
                    searchPlaceholder: 'Search...',
                    sSearch: '',
                },
                "pageLength": 10,
                // scrollX: true
            });

            $('#viewMaterialModal').on('hidden.bs.modal', function() {
                // Your function here
                $("#newCampModal").show();
            });
        </script>

        <!-- Date & Time Picker JS -->
        <script src="assets/libs/flatpickr/flatpickr.min.js"></script>
        <!-- <script src="assets/js/date&time_pickers.js"></script> -->
        <script>
            flatpickr(".datetime", {
                enableTime: true,
                dateFormat: "m/d/Y H:i",
            });

            flatpickr("#schBetRange", {
                
                minDate: "today",
                defaultDate: "today",
                dateFormat: "Y-m-d",
            });
            flatpickr("#rschBetRange", {
                minDate: "today",
                defaultDate: "today",
                dateFormat: "Y-m-d",
            });
            flatpickr("#expire_after", {
                dateFormat: "Y-m-d",
                minDate: "today"
            });
            flatpickr("#rexpire_after", {
                dateFormat: "Y-m-d",
                minDate: "today"
            });
        </script>
    @endpush

@endsection
