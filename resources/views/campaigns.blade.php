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
                                            15 Day(s)
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
                                            2 Delivered
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
                                            0 Opened
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
                                                <td>{{ $campaign->users_group_name }}</td>
                                                <td>{{ e($campaign->launch_time) }}</td>
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
                                                            <option value="{{ $group->group_id }}">{{ $group->group_name }}
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
                                                        $template = asset('storage') ."/". $email->mailBodyFilePath;
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
                                                        (Between Days)<i class='bx bx-info-circle p-2'
                                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                                            data-bs-original-title="We recommend scheduling campaigns between business days to get the most ineraction (e.g. Monday - Friday)"></i>
                                                    </label>
                                                    <div class="col-sm-8">
                                                        <div class="form-group">
                                                            <div class="input-group">

                                                                <input type="text"
                                                                    class="form-control flatpickr-input active"
                                                                    id="schBetRange" value="2024-05-16 to 2024-05-30"
                                                                    placeholder="YYYY-MM-DD to YYYY-MM-DD"
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
                                                                <option value="Asia/Calcutta">(GMT+05:30) Chennai, Kolkata,
                                                                    Mumbai, New Delhi</option>
                                                                <option value="Asia/Calcutta">(GMT+05:30) Sri
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
                                                    <label for="input-label" class="form-label">Schedule Between
                                                        Days</label>
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

    @push('newcss')
        <link rel="stylesheet" href="assets/css/campaigns.css">
    @endpush

    @push('newscripts')

    <script src="assets/js/campaigns.js"></script>

    <!-- Date & Time Picker JS -->
    <script src="assets/libs/flatpickr/flatpickr.min.js"></script>
    <!-- <script src="assets/js/date&time_pickers.js"></script> -->
    <script>
        flatpickr(".datetime", {
            enableTime: true,
            dateFormat: "m/d/Y H:i",
        });

        flatpickr("#schBetRange", {
            mode: "range",
            minDate: "today",
            dateFormat: "Y-m-d",
        });
        flatpickr("#rschBetRange", {
            mode: "range",
            minDate: "today",
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
