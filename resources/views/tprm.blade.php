@extends('layouts.app')

@section('title', 'TPRM Campaign - Phishing awareness training program')

@section('main-content')

    <div class="main-content app-content">
        <div class="container-fluid mt-4">
            <div class="d-flex justify-content-between">
                <button type="button" class="btn btn-primary mb-3 me-2" data-bs-toggle="modal" data-bs-target="#newCampModal">
                    New Campaign
                </button>

                <div>
                    <button type="button" class="btn btn-secondary mb-3" data-bs-toggle="modal"
                        data-bs-target="#domainVerificationModal">Domain Verification</button>

                </div>
            </div>
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
                                            <th>Domain</th>
                                            <th>Phishing Material</th>
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
                                                <td>{{ $campaign->users_group_name }}</td>
                                                <!-- <td>{!! $campaign->status_button !!}</td> -->
                                                <td>
                                                    <span class="badge rounded-pill bg-primary">
                                                        {{ $campaign->campaign_type }}</span>


                                                </td>
                                                <!-- <td class="text-center">
                                                        <div>
                                                            
                                                                @if ($campaign->launch_type == 'schLater')
    <small class="text-danger">
                                                                    Not scheduled
                                                                </small>
@else
    <small>
                                                                    {{ $campaign->launch_type }}
                                                                </small>
    @endif
                                                                
                                                            
                                                        </div>
                                                        
                                                        {{ e($campaign->launch_time) }}
                                                        
                                                        <div>
                                                            <small>
                                                                @if ($campaign->email_freq == 'one')
    Once
@else
    {{ $campaign->email_freq }}
    @endif
                                                                
                                                            </small>
                                                        </div>
                                                    </td> -->
                                                <td>

                                                    <button
                                                        class="btn btn-icon btn-danger-transparent rounded-pill btn-wave waves-effect waves-light"
                                                        title="Delete"
                                                        onclick="deletecampaign('{{ e($campaign->campaign_id) }}')">
                                                        <i class="ri-delete-bin-line"></i>
                                                    </button>


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
                                            <strong>Initial Setup & Domain Selection</strong>
                                        </li>
                                        <li id="pm_step">
                                            <i class='bx bx-mail-send'></i>
                                            <strong>Select Phishing Material</strong>
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

                                                <div class="col-lg-6 ">

                                                    <label for="input-label" class="form-label">Domain</label>
                                                    <select class="form-control required" id="users_group">
                                                        @foreach ($usersGroups as $group)
                                                            <option value="{{ $group->group_id }}">
                                                                {{ $group->group_name }}
                                                            </option>
                                                        @endforeach
                                                    </select>

                                                </div>

                                            </div>

                                            <div class="row">
                                                <div class="col-lg-6">
                                                    <input type="hidden" id="campaign_type" name="campaign_type"
                                                        value="Phishing">
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
                                            class="btn btn-info label-btn stickyBtn label-end last-step rounded-pill next">
                                            Next
                                            <i class="ri-arrow-right-line label-btn-icon  ms-2 rounded-pill"></i>
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



                                    <fieldset>
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
                                                        Date<i class='bx bx-info-circle p-2' data-bs-toggle="tooltip"
                                                            data-bs-placement="top"
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
                                            class="btn btn-info label-btn  label-end rounded-pill next">
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
    <div class="modal fade" id="reschedulemodal" tabindex="-1" aria-labelledby="exampleModalLgLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Re-Schedule Campaign
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('reschedule.campaign') }}" method="post" id="rescheduleForm">
                        @csrf
                        <p class="text-center">Schedule Type</p>
                        <div class="form-card">
                            <div class="d-flex justify-content-center">
                                <div class="checkb mx-1">

                                    <input type="radio" class="btn-check" name="rschType" data-val="Imediately"
                                        value="immediately" id="rimediateBtn" checked>
                                    <label class="btn btn-outline-dark mb-3" data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        data-bs-original-title="Campaign will begin delivering emails within 1-3 minutes of submission."
                                        id="rimediateLabelBtn" for="rimediateBtn">Deliver Immediately </label>
                                </div>
                                <div class="checkb mx-1">

                                    <input type="radio" class="btn-check" name="rschType" data-val="Setup Schedule"
                                        value="scheduled" id="rScheduleBtn">
                                    <label class="btn btn-outline-dark mb-3" data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        data-bs-original-title="Campaign will deliver emails using a defined schedule over a period of hours and days (e.g. 9am-5pm Monday-Friday)."
                                        id="rscheduleLabelBtn" for="rScheduleBtn">Setup Schedule</label>
                                </div>



                            </div>
                            <div id="rdvSchedule2" class="d-none">
                                <label class="text-left control-label col-form-label font-italic mt-3 pt-0"><b>Note:</b>We
                                    will capture employee interactions as long as a campaign remains active (isn't updated
                                    or deleted). </label>
                                <div class="row mb-3">
                                    <label for="inputEmail3" class="col-sm-4 col-form-label">Schedule Date<i
                                            class='bx bx-info-circle p-2' data-bs-toggle="tooltip"
                                            data-bs-placement="top"
                                            data-bs-original-title="Select schedule date for started shooting this campaign"></i>
                                    </label>
                                    <div class="col-sm-8">
                                        <div class="form-group">
                                            <div class="input-group">

                                                <input type="text" class="form-control flatpickr-input active"
                                                    name="rsc_launch_time" id="rschBetRange" placeholder="YYYY-MM-DD"
                                                    required readonly="readonly">
                                                <div class="input-group-text text-muted"> <i class="ri-calendar-line"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label for="inputEmail3" class="col-sm-4 col-form-label">Schedule (Between Times) <i
                                            class='bx bx-info-circle p-2' data-bs-toggle="tooltip"
                                            data-bs-placement="top"
                                            data-bs-original-title="We recommend scheduling campaigns between business hours to get the most ineraction (e.g. 9am - 5pm)"></i></label>
                                    <div class="col-sm-8">
                                        <div class="form-group d-flex">
                                            <input type="time" id="rschTimeStart" name="startTime"
                                                class="form-control" value="09:00" step="60">
                                            <label class="col-md-1 m-t-15" style="text-align:center"> To </label>
                                            <input type="time" id="rschTimeEnd" name="endTime" class="form-control"
                                                value="17:00" step="60">
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label for="inputEmail3" class="col-sm-4 col-form-label">Schedule (Time Zone) <i
                                            class='bx bx-info-circle p-2' data-bs-toggle="tooltip"
                                            data-bs-placement="top"
                                            data-bs-original-title="Select the timezone that best aligns with your business hours."></i></label>
                                    <div class="col-sm-8">
                                        <div class="form-group d-flex">
                                            <select class="select2 form-control custom-select select2-hidden-accessible"
                                                style="width: 100%" id="rschTimeZone" name="rschTimeZone">
                                                <option value="Australia/Canberra" data-select2-id="390">(GMT+10:00)
                                                    Canberra, Melbourne, Sydney</option>
                                                <option value="Etc/GMT+12">(GMT-12:00) International Date Line West
                                                </option>
                                                <option value="Pacific/Midway">(GMT-11:00) Midway Island, Samoa</option>
                                                <option value="Pacific/Honolulu">(GMT-10:00) Hawaii</option>
                                                <option value="US/Alaska">(GMT-09:00) Alaska</option>
                                                <option value="America/Los_Angeles">(GMT-08:00) Pacific Time (US &amp;
                                                    Canada)</option>
                                                <option value="America/Tijuana">(GMT-08:00) Tijuana, Baja California
                                                </option>
                                                <option value="US/Arizona">(GMT-07:00) Arizona</option>
                                                <option value="America/Chihuahua">(GMT-07:00) Chihuahua, La Paz, Mazatlan
                                                </option>
                                                <option value="US/Mountain">(GMT-07:00) Mountain Time (US &amp; Canada)
                                                </option>
                                                <option value="America/Managua">(GMT-06:00) Central America</option>
                                                <option value="US/Central">(GMT-06:00) Central Time (US &amp; Canada)
                                                </option>
                                                <option value="America/Mexico_City">(GMT-06:00) Guadalajara, Mexico City,
                                                    Monterrey</option>
                                                <option value="Canada/Saskatchewan">(GMT-06:00) Saskatchewan</option>
                                                <option value="America/Bogota">(GMT-05:00) Bogota, Lima, Quito, Rio Branco
                                                </option>
                                                <option value="US/Eastern">(GMT-05:00) Eastern Time (US &amp; Canada)
                                                </option>
                                                <option value="US/East-Indiana">(GMT-05:00) Indiana (East)</option>
                                                <option value="Canada/Atlantic">(GMT-04:00) Atlantic Time (Canada)</option>
                                                <option value="America/Caracas">(GMT-04:00) Caracas, La Paz</option>
                                                <option value="America/Manaus">(GMT-04:00) Manaus</option>
                                                <option value="America/Santiago">(GMT-04:00) Santiago</option>
                                                <option value="Canada/Newfoundland">(GMT-03:30) Newfoundland</option>
                                                <option value="America/Sao_Paulo">(GMT-03:00) Brasilia</option>
                                                <option value="America/Argentina/Buenos_Aires">(GMT-03:00) Buenos Aires,
                                                    Georgetown</option>
                                                <option value="America/Godthab">(GMT-03:00) Greenland</option>
                                                <option value="America/Montevideo">(GMT-03:00) Montevideo</option>
                                                <option value="America/Noronha">(GMT-02:00) Mid-Atlantic</option>
                                                <option value="Atlantic/Cape_Verde">(GMT-01:00) Cape Verde Is.</option>
                                                <option value="Atlantic/Azores">(GMT-01:00) Azores</option>
                                                <option value="Africa/Casablanca">(GMT+00:00) Casablanca, Monrovia,
                                                    Reykjavik</option>
                                                <option value="Etc/Greenwich" data-select2-id="418">(GMT+00:00) Greenwich
                                                    Mean Time : Dublin, Edinburgh, Lisbon, London</option>
                                                <option value="Europe/Amsterdam">(GMT+01:00) Amsterdam, Berlin, Bern, Rome,
                                                    Stockholm, Vienna</option>
                                                <option value="Europe/Belgrade">(GMT+01:00) Belgrade, Bratislava, Budapest,
                                                    Ljubljana, Prague</option>
                                                <option value="Europe/Brussels">(GMT+01:00) Brussels, Copenhagen, Madrid,
                                                    Paris</option>
                                                <option value="Europe/Sarajevo">(GMT+01:00) Sarajevo, Skopje, Warsaw,
                                                    Zagreb</option>
                                                <option value="Africa/Lagos">(GMT+01:00) West Central Africa</option>
                                                <option value="Asia/Amman">(GMT+02:00) Amman</option>
                                                <option value="Europe/Athens">(GMT+02:00) Athens, Bucharest, Istanbul
                                                </option>
                                                <option value="Asia/Beirut">(GMT+02:00) Beirut</option>
                                                <option value="Africa/Cairo">(GMT+02:00) Cairo</option>
                                                <option value="Africa/Harare">(GMT+02:00) Harare, Pretoria</option>
                                                <option value="Europe/Helsinki">(GMT+02:00) Helsinki, Kyiv, Riga, Sofia,
                                                    Tallinn, Vilnius</option>
                                                <option value="Asia/Jerusalem">(GMT+02:00) Jerusalem</option>
                                                <option value="Europe/Minsk">(GMT+02:00) Minsk</option>
                                                <option value="Africa/Windhoek">(GMT+02:00) Windhoek</option>
                                                <option value="Asia/Kuwait">(GMT+03:00) Kuwait, Riyadh, Baghdad</option>
                                                <option value="Europe/Moscow">(GMT+03:00) Moscow, St. Petersburg, Volgograd
                                                </option>
                                                <option value="Africa/Nairobi">(GMT+03:00) Nairobi</option>
                                                <option value="Asia/Tbilisi">(GMT+03:00) Tbilisi</option>
                                                <option value="Asia/Tehran">(GMT+03:30) Tehran</option>
                                                <option value="Asia/Muscat">(GMT+04:00) Abu Dhabi, Muscat</option>
                                                <option value="Asia/Baku">(GMT+04:00) Baku</option>
                                                <option value="Asia/Yerevan">(GMT+04:00) Yerevan</option>
                                                <option value="Asia/Kabul">(GMT+04:30) Kabul</option>
                                                <option value="Asia/Yekaterinburg">(GMT+05:00) Yekaterinburg</option>
                                                <option value="Asia/Karachi">(GMT+05:00) Islamabad, Karachi, Tashkent
                                                </option>
                                                <option value="Asia/Calcutta">(GMT+05:30) Chennai, Kolkata, Mumbai, New
                                                    Delhi</option>
                                                <option value="Asia/Calcutta">(GMT+05:30) Sri Jayawardenapura</option>
                                                <option value="Asia/Katmandu">(GMT+05:45) Kathmandu</option>
                                                <option value="Asia/Almaty">(GMT+06:00) Almaty, Novosibirsk</option>
                                                <option value="Asia/Dhaka">(GMT+06:00) Astana, Dhaka</option>
                                                <option value="Asia/Rangoon">(GMT+06:30) Yangon (Rangoon)</option>
                                                <option value="Asia/Bangkok">(GMT+07:00) Bangkok, Hanoi, Jakarta</option>
                                                <option value="Asia/Krasnoyarsk">(GMT+07:00) Krasnoyarsk</option>
                                                <option value="Asia/Hong_Kong">(GMT+08:00) Beijing, Chongqing, Hong Kong,
                                                    Urumqi</option>
                                                <option value="Asia/Kuala_Lumpur">(GMT+08:00) Kuala Lumpur, Singapore
                                                </option>
                                                <option value="Asia/Irkutsk">(GMT+08:00) Irkutsk, Ulaan Bataar</option>
                                                <option value="Australia/Perth">(GMT+08:00) Perth</option>
                                                <option value="Asia/Taipei">(GMT+08:00) Taipei</option>
                                                <option value="Asia/Tokyo">(GMT+09:00) Osaka, Sapporo, Tokyo</option>
                                                <option value="Asia/Seoul">(GMT+09:00) Seoul</option>
                                                <option value="Asia/Yakutsk">(GMT+09:00) Yakutsk</option>
                                                <option value="Australia/Adelaide">(GMT+09:30) Adelaide</option>
                                                <option value="Australia/Darwin">(GMT+09:30) Darwin</option>
                                                <option value="Australia/Brisbane">(GMT+10:00) Brisbane</option>
                                                <option value="Australia/Canberra">(GMT+10:00) Canberra, Melbourne, Sydney
                                                </option>
                                                <option value="Australia/Hobart">(GMT+10:00) Hobart</option>
                                                <option value="Pacific/Guam">(GMT+10:00) Guam, Port Moresby</option>
                                                <option value="Asia/Vladivostok">(GMT+10:00) Vladivostok</option>
                                                <option value="Asia/Magadan">(GMT+11:00) Magadan, Solomon Is., New
                                                    Caledonia</option>
                                                <option value="Pacific/Auckland">(GMT+12:00) Auckland, Wellington</option>
                                                <option value="Pacific/Fiji">(GMT+12:00) Fiji, Kamchatka, Marshall Is.
                                                </option>
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

                                        <input type="radio" class="btn-check" name="emailFreq" data-val="One-off"
                                            value="one" id="rfoneoff" checked>
                                        <label class="btn btn-outline-dark mb-3" for="rfoneoff">One-off</label>
                                    </div>
                                    <div class="checkb mx-1">

                                        <input type="radio" class="btn-check" name="emailFreq" data-val="Monthly"
                                            value="monthly" id="rfmonthly">
                                        <label class="btn btn-outline-dark mb-3" for="rfmonthly">Monthly</label>
                                    </div>

                                    <div class="checkb mx-1">

                                        <input type="radio" class="btn-check" name="emailFreq" data-val="Weekly"
                                            value="weekly" id="rfweekly">
                                        <label class="btn btn-outline-dark mb-3" for="rfweekly">Weekly</label>
                                    </div>
                                    <div class="checkb mx-1">

                                        <input type="radio" class="btn-check" name="emailFreq" data-val="Quaterly"
                                            value="quaterly" id="rfquaterly">
                                        <label class="btn btn-outline-dark mb-3" for="rfquaterly">Quaterly</label>
                                    </div>
                                    <div id="rexp_after" class="d-none">
                                        <div class="input-group">
                                            <div class="input-group-text text-muted"> Expire After</div>
                                            <input type="text" class="form-control flatpickr-input active"
                                                name="rexpire_after" id="rexpire_after" placeholder="Choose date"
                                                readonly="readonly">
                                            <div class="input-group-text text-muted"> <i class="ri-calendar-line"></i>
                                            </div>
                                        </div>
                                    </div>



                                </div>
                            </div>

                            <div class="text-center">
                                <input type="hidden" name="campid" id="recampid">
                                <button type="submit" id="rescheduleBtn"
                                    class="btn btn-primary btn-wave waves-effect waves-light">Re-schedule</button>
                            </div>


                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- <--Domain verification modal-->
    <div class="modal fade" id="domainVerificationModal" tabindex="-1" aria-labelledby="exampleModalLgLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Domain Verification</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <button type="button" id="newDomainVerificationModalBtn" class="btn btn-primary mb-2"
                        data-bs-toggle="modal" data-bs-target="#domainVerificationModal">
                        Add Domain For Verification
                    </button>
                    <div class="table-responsive">
                        <table id="domainVerificationTable" class="table table-bordered text-nowrap w-100">
                            <thead>
                                <tr>
                                    <th>Domain Name</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="allDomains">
                                @forelse ($allDomains as $domain)
                                    <tr>
                                        <td onclick="openDomainModal('{{ $domain->domain }}')"
                                            style="cursor: pointer;">{{ $domain->domain }}</td>
                                        <td>
                                            @if ($domain->verified == 1)
                                                <span class="badge bg-success">Verified</span>
                                            @else
                                                <span class="badge bg-warning">Pending</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($domain->verified == 1)
                                                <button type="button" class="btn btn-outline-info btn-sm ms-2"
                                                    onclick="fetchEmail(this, '{{ $domain->domain }}')">Fetch Email
                                                </button>
                                            @endif
                                            <span role="button" onclick="deleteDomain(`{{ $domain->domain }}`)">
                                                <i class="bx bx-x fs-25"></i>
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-center" colspan="5">No records found</td>
                                    </tr>
                                @endforelse
                            </tbody>

                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Fetch Email Modal -->
    <div class="modal fade" id="fetchEmailModal" tabindex="-1" aria-labelledby="fetchEmailModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="fetchEmailModalLabel">Email for Domain</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Domain:</strong> <span id="modalDomain"></span></p>
                    <div>
                        <input type="checkbox" id="selectAll" onclick="toggleSelectAll(this)"> Select All
                    </div>
                    <div id="modalEmail"></div> <!-- Container for multiple emails -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" onclick="saveEmail()">Save Email</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal emaildatabydomainparticualrecord -->
    <div class="modal fade" id="domainModal" tabindex="-1" aria-labelledby="domainModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content" style="border-radius: 8px; box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.2);">
                <div class="modal-header"
                    style="background-color: #007bff; color: white; border-bottom: none; padding: 1rem;">
                    <h5 class="modal-title" id="domainModalLabel" style="font-weight: 600;">Domain Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body" style="padding: 1.5rem; background-color: #f8f9fa;">
                    <p id="domainName" style="font-size: 1.1rem; font-weight: 500; color: #333; margin-bottom: 1rem;">
                    </p>
                    <hr style="border: 0; border-top: 1px solid #ddd; margin: 0.5rem 0 1rem;">
                    <p style="color: #6c757d; font-size: 0.9rem; margin-bottom: 0.5rem;">Associated emails:</p>
                    <ul id="emailList" style="list-style-type: none; padding: 0; margin: 0;">
                        <!-- Dynamically added email items will appear here -->
                    </ul>
                </div>
                <div class="modal-footer" style="background-color: #e9ecef; padding: 1rem; border-top: none;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                        style="border-radius: 20px; padding: 0.5rem 1.5rem;">Close</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Inline CSS for additional spacing */
        #emailList li {
            padding: 0.5rem;
            background-color: #fff;
            border-radius: 4px;
            margin-bottom: 1rem;
            /* Adds space between each email ID */
            color: #007bff;
        }
    </style>


    <script>
        // Function to open the domain email verification modal 
        function openDomainModal(domain) {
            console.log("Domain clicked: " + domain); // Debugging line

            // Set the domain name in the modal
            document.getElementById('domainName').innerText = domain;

            // Fetch emails associated with the domain
            fetch(`/tprmcampaigns/emails/${domain}`)
                .then(response => response.json())
                .then(emails => {
                    // Select the email list container
                    var emailList = document.getElementById('emailList');
                    emailList.innerHTML = ''; // Clear previous items

                    // Check if there are emails
                    if (emails.length > 0) {
                        emails.forEach(function(email) {
                            var li = document.createElement('li'); // Create a new list item
                            li.innerText = email; // Set the email as the list item text
                            emailList.appendChild(li); // Append the list item to the ul
                        });
                    } else {
                        // Handle the case when no emails are found
                        var li = document.createElement('li');
                        li.innerText = 'No emails found for this domain.'; // Message when no emails found
                        emailList.appendChild(li);
                    }

                    // Create and show the modal
                    var modal = new bootstrap.Modal(document.getElementById('domainModal'));
                    modal.show(); // Show the modal
                })
                .catch(error => {
                    console.error('Error fetching emails:', error);
                    alert('Error fetching emails. Please try again.'); // Error handling
                });
        }

        function fetchEmail(btn, domain) {
            // Fetch email for the given domain via AJAX
            const spinner = `<div class="spinner-border spinner-border-sm me-4" role="status">
    <span class="visually-hidden">Loading...</span>
</div>`;
            btn.innerHTML = spinner;
            $.ajax({
                url: '/tprmcampaigns/fetchEmail',
                type: 'POST',
                data: {
                    domain: domain,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    // Debugging: Check the structure of the response
                    console.log(response);
                    btn.innerHTML = `Fetch Email`;


                    // Check if the response contains emails
                    if (!response.emails || response.emails.length === 0) {
                        // document.getElementById('modalEmail').innerHTML = '<p>No emails found for this domain.</p>';
                        alert("No Emails found for this email.");
                        return; // Exit if no emails are found
                    }

                    // Set the domain in the modal
                    document.getElementById('modalDomain').innerText = response.domain;

                    // Clear existing emails
                    const modalEmailDiv = document.getElementById('modalEmail');
                    modalEmailDiv.innerHTML = '';

                    // Populate the modal with emails
                    response.emails.forEach(email => {
                        const emailItem = document.createElement('div');
                        emailItem.innerHTML = `
                    <input type="checkbox" class="emailCheckbox" value="${email.email}"> <!-- Ensure this matches your response -->
                    ${email.email} <!-- Use the correct property for displaying the email -->
                `;
                        modalEmailDiv.appendChild(emailItem);
                    });

                    // Open the modal
                    var fetchEmailModal = new bootstrap.Modal(document.getElementById('fetchEmailModal'), {
                        keyboard: false
                    });
                    fetchEmailModal.show();
                },
                error: function(error) {
                    console.log('Error fetching email:', error);
                }
            });
        }

        function toggleSelectAll(selectAllCheckbox) {
            const checkboxes = document.querySelectorAll('.emailCheckbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
        }

        function saveEmail() {
            const selectedEmails = Array.from(document.querySelectorAll('.emailCheckbox:checked')).map(checkbox => checkbox
                .value);
            const domain = document.getElementById('modalDomain').innerText; // Get the domain from the modal

            // Debugging: Log selected emails and domain
            // console.log('Selected Emails:', selectedEmails);
            // console.log('Domain:', domain);

            if (selectedEmails.length === 0) {
                alert('Please select at least one email to save.');
                return;
            }

            // Save selected emails and domain via AJAX
            $.ajax({
                url: '/tprmcampaigns/tprmnewGroup',
                type: 'POST',
                data: {
                    emails: selectedEmails, // Array of selected emails
                    domainName: domain, // Domain name (use 'domainName')
                    _token: '{{ csrf_token() }}' // CSRF token for Laravel
                },
                success: function(response) {
                    // alert(response.message);
                    $('#fetchEmailModal').modal('hide');
                },
                error: function(error) {
                    console.log('Error saving email:', error);
                    // Debugging: Log the error response
                    console.log(error.responseText);
                }
            });
        }


        let domains = [];

        // Function to add a domain
        function addDomain() {
            const domainInput = document.getElementById("domainEmailInput");
            const domain = domainInput.value.trim();

            if (domain && !domains.includes(domain)) {
                domains.push(domain);
                updateDomainList();
                domainInput.value = ""; // Clear input after adding
            } else if (domains.includes(domain)) {
                alert("This domain is already added.");
            } else {
                alert("Please enter a valid domain.");
            }
        }

        // Function to update the displayed list of domains
        function updateDomainList() {
            const domainList = document.getElementById("domainList");
            domainList.innerHTML = domains
                .map((domain, index) => `<li class="list-group-item d-flex justify-content-between">
                               ${domain}
                               <button class="btn btn-sm btn-danger" onclick="removeDomain(${index})">Remove</button>
                             </li>`)
                .join("");
        }

        // Function to remove a domain from the list
        function removeDomain(index) {
            domains.splice(index, 1);
            updateDomainList();
        }

        // Function to submit domains for verification
        function submitDomains() {
            if (domains.length > 0) {
                console.log("Submitting domains for verification:", domains);

                // Show spinner and hide the submit button
                document.getElementById("sendOtpBtn").classList.add("d-none");
                document.getElementById("submitSpinner").classList.remove("d-none");

                // Simulate API call to backend
                fetch("/submit-domains", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content'), // Ensure this meta tag exists
                        },
                        body: JSON.stringify({
                            domains
                        })
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error("Network response was not ok");
                        }
                        return response.json();
                    })
                    .then(data => {
                        // Hide spinner
                        document.getElementById("submitSpinner").classList.add("d-none");
                        document.getElementById("sendOtpBtn").classList.remove("d-none");

                        // Show success or failure message based on backend response
                        if (data.status === 1) { // Change to check for status
                            alert("Domains successfully requested for verification!");
                            domains = []; // Clear domains list after successful submission
                            updateDomainList();
                            // Optionally close the modal if needed
                            $('#newDomainVerificationModal').modal('hide'); // Use jQuery for Bootstrap modal
                        } else {
                            alert(`Unable to submit domains for verification: ${data.msg}`); // Show specific message
                        }
                    })
                    .catch(error => {
                        console.error("Error submitting domains:", error);
                        alert("An error occurred while submitting domains. Please try again.");

                        // Reset the buttons in case of error
                        document.getElementById("submitSpinner").classList.add("d-none");
                        document.getElementById("sendOtpBtn").classList.remove("d-none");
                    });
            } else {
                alert("Please add at least one domain.");
            }
        }
    </script>


    </div>
    </div>
    </div>
    </div>

    <!-- new domain verification modal -->
    <!-- Modal Structure for Multiple Domain Verification -->
    <!-- Modal Structure for Multiple Domain Verification -->
    <div class="modal" id="newDomainVerificationModal" tabindex="-1" aria-labelledby="exampleModalLgLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Verify Domains</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">
                        Domain verification is performed through challenge-response authentication of the provided email
                        addresses.
                        (e.g., verifying support@mybusiness.com will enable mybusiness.com.)
                    </p>

                    <!-- Form to Add Domains -->
                    <form id="domainForm" method="post" onsubmit="return false;">
                        <div class="mb-3">
                            <label for="domainEmailInput" class="form-label">Domain<sup
                                    class="text-danger">*</sup></label>
                            <input type="text" class="form-control" id="domainEmailInput"
                                placeholder="Enter email address" />
                        </div>
                        <button type="button" class="btn btn-secondary" onclick="addDomain()">Add Domain</button>
                    </form>

                    <!-- List of Added Domains -->
                    <div class="mt-3">
                        <h6>Domains to Verify:</h6>
                        <ul id="domainList" class="list-group">
                            <!-- Dynamically added domains will appear here -->
                        </ul>
                    </div>

                    <!-- Submit Button and Spinner -->
                    <button type="button" id="sendOtpBtn" class="btn btn-primary my-3"
                        onclick="submitDomains()">Submit Domains for Verification</button>
                    <button class="btn btn-primary my-3 d-none" id="submitSpinner">
                        <span class="me-2">Please wait...</span>
                        <span class="loading"><i class="ri-loader-2-fill fs-16"></i></span>
                    </button>
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
                                    <a class="nav-link active" data-bs-toggle="tab" role="tab"
                                        aria-current="page" href="#phishing_campaign" aria-selected="true">Phishing
                                        Campaign</a>
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
    {{-- --------------------- modals ---------------------- --}}


    @push('newcss')
        <link rel="stylesheet" href="assets/css/campaigns.css">
        <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css">
        <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.3.0/css/responsive.bootstrap.min.css">
        <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.bootstrap5.min.css">
    @endpush

    @push('newscripts')
        <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
        <script src="assets/js/tprmdomain.js"></script>
        <!-- <script src="assets/js/tprmcampaigns.js"></script> -->

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




        <script>
            $('#allGroupsTable').DataTable({
                language: {
                    searchPlaceholder: 'Search...',
                    sSearch: '',
                },
                "pageLength": 10,
                // scrollX: true
            });

            $('#domainVerificationTable').DataTable({
                language: {
                    searchPlaceholder: 'Search...',
                    sSearch: '',
                },
                "pageLength": 10,
                // scrollX: true
            });

            function showMaterialDetails(btn, name, subject, website, senderProfile) {

                $("#viewMaterialModal").modal('show');
                $("#newCampModal").hide();

                var htmlmaterial = $(btn).parent().parent().parent().prev().html();
                $("#vphishEmail").val(name);
                $("#vSub").val(subject);

                $.post({
                    url: '/tprmcampaigns/fetch-phish-data',
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
                    url: '/treporting/fetch-campaign-report',
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


                    }
                });

                function fetchCampReportByUsers() {
                    $.post({
                        url: '/tfetch-camp-report-by-users',
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
                        url: '/fetch-camp-training-details',
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
                        url: "/fetch-camp-training-details-individual",
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
                        res.msg,
                        '',
                        'error'
                    ).then(function() {
                        window.location.href = window.location.href
                    })
                }
            }


            function relaunch_camp(campid) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "The previous statistics and reports of this campaign will be erased.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e6533c',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Re-Launch'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post({
                            url: '/tprmcampaigns/relaunch',
                            data: {
                                campid: campid
                            },
                            success: function(res) {

                                // console.log(res)
                                window.location.href = window.location.href;
                            }
                        })
                    }
                })

                // if (confirm("Are you sure you want to Re-Launch this Campaign")) {
                //     $.post({
                //         url: 'campaigns.php?relaunch_camp=1',
                //         data: {
                //             campid: campid
                //         },
                //         success: function (response) {
                //             // console.log(response);
                //             // window.location.reload()
                //             window.location.href = window.location.href;
                //         }
                //     })
                // } else {
                //     return false;
                // }
            }

            function reschedulecampid(id) {
                $("#recampid").val(id);
            }

            function deletecampaign(campid) {

                Swal.fire({
                    title: 'Are you sure?',
                    text: "Are you sure that you want to delete this Campaign?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e6533c',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Delete'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post({
                            url: '/tprmcampaigns/delete',
                            data: {
                                campid: campid
                            },
                            success: function(res) {

                                // console.log(res)
                                window.location.href = window.location.href;
                            }
                        })
                    }
                })

                // if (confirm("Are you sure that you want to delete?")) {
                //     $.get({
                //         url: 'campaigns.php?deletecamp=1&campid=' + campid,
                //         success: function (res) {
                //             // alert(res);
                //             // window.location.reload();
                //             window.location.href = window.location.href;
                //         }
                //     })
                // } else {
                //     return;
                // }
            }

            //campaign type toggling
            $("#campaign_type").on('change', function() {

                var type = $(this).val();
                if (type == 'Phishing') {

                    $("#pm_step").show()
                    $("#tm_step").hide()

                    $("#pm_step_form").addClass('included');
                    $("#pm_step_form input[type='radio']").addClass('required');

                    $("#tm_step_form").removeClass('included');
                    $("#tm_step_form input[type='radio']").removeClass('required');

                } else if (type == 'Training') {

                    $("#pm_step").hide()
                    $("#tm_step").show()

                    $("#pm_step_form").removeClass('included');

                    $("#tm_step_form").addClass('included');

                } else if (type == 'Phishing & Training') {

                    $("#pm_step").show()
                    $("#tm_step").show()

                    $("#pm_step_form").addClass('included');
                    $("#tm_step_form").addClass('included');

                }
            })



            $(document).ready(function() {

                var current_fs, next_fs, previous_fs; //fieldsets
                var opacity;

                $(".next").click(function() {

                    current_fs = $(this).parent();
                    next_fs = $(this).parent().next();

                    if (!next_fs.hasClass('included')) {
                        next_fs = next_fs.next();
                    }

                    var allFilled = true;
                    current_fs.find('.required').each(function() {
                        if ($(this).val() === '') {
                            allFilled = false;
                            return false; // Exit the loop early if any field is empty
                        }

                    });

                    if (current_fs.attr('id') === 'pm_step_form') {
                        // Check if at least one radio button is checked
                        var radioChecked = false;
                        current_fs.find('input[type="radio"][name="phish_material"]').each(function() {
                            if ($(this).is(':checked')) {
                                radioChecked = true;
                                return false; // Exit the loop early if any radio button is checked
                            }
                        });

                        if (!radioChecked) {
                            alert('Please select phishing material');
                            return; // Stop further execution
                        }
                    }

                    if (current_fs.attr('id') === 'tm_step_form') {
                        // Check if at least one radio button is checked
                        var radioChecked = false;
                        current_fs.find('input[type="radio"][name="training_module"]').each(function() {
                            if ($(this).is(':checked')) {
                                radioChecked = true;
                                return false; // Exit the loop early if any radio button is checked
                            }
                        });

                        if (!radioChecked) {
                            alert('Please select training module');
                            return; // Stop further execution
                        }
                    }




                    if (allFilled) {
                        //Add Class Active
                        $("#progressbar li").eq($("fieldset").index(next_fs)).addClass("active");

                        //show the next fieldset
                        next_fs.show();
                        //hide the current fieldset with style
                        current_fs.animate({
                            opacity: 0
                        }, {
                            step: function(now) {
                                // for making fielset appear animation
                                opacity = 1 - now;

                                current_fs.css({
                                    'display': 'none',
                                    'position': 'relative'
                                });
                                next_fs.css({
                                    'opacity': opacity
                                });
                            },
                            duration: 600
                        });

                        if ($(this).hasClass('last-step')) {
                            // The targeted element has class "last-step"
                            reviewFormData();
                        }
                    } else {
                        // Alert or inform the user that some required fields are empty
                        alert('Please fill all required fields!');
                    }




                });

                $(".previous").click(function() {

                    current_fs = $(this).parent();
                    previous_fs = $(this).parent().prev();

                    if (!previous_fs.hasClass('included')) {
                        previous_fs = previous_fs.prev();
                    }

                    //Remove class active
                    $("#progressbar li").eq($("fieldset").index(current_fs)).removeClass("active");

                    //show the previous fieldset
                    previous_fs.show();

                    //hide the current fieldset with style
                    current_fs.animate({
                        opacity: 0
                    }, {
                        step: function(now) {
                            // for making fielset appear animation
                            opacity = 1 - now;

                            current_fs.css({
                                'display': 'none',
                                'position': 'relative'
                            });
                            previous_fs.css({
                                'opacity': opacity
                            });
                        },
                        duration: 600
                    });
                });

                $('.radio-group .radio').click(function() {
                    $(this).parent().find('.radio').removeClass('selected');
                    $(this).addClass('selected');
                });

                $(".submit").click(function() {
                    return false;
                })

                var dataToBeSaved = {};

                function reviewFormData() {

                    function launch_time() {
                        var currentDate = new Date();
                        var formattedDate = formatDate(currentDate);
                        return formattedDate;
                    }
                    var training_module = '';
                    var trainingLang = '';
                    var phishing_email = '';
                    var phishing_lang = '';

                    if (campaign_type.value == "Phishing") {
                        phishing_email = $("input[name='phish_material']:checked").val();
                        phishing_lang = email_lang.value;
                    }

                    if (campaign_type.value == "Training") {
                        training_module = $("input[name='training_module']:checked").val();
                        trainingLang = training_lang.value;
                    }
                    if (campaign_type.value == "Phishing & Training") {
                        phishing_email = $("input[name='phish_material']:checked").val();
                        phishing_lang = email_lang.value;
                        training_module = $("input[name='training_module']:checked").val();
                        trainingLang = training_lang.value;
                    }
                    var formData = {

                        camp_name: camp_name.value,
                        campaign_type: campaign_type.value,
                        users_group: users_group.value,
                        email_lang: phishing_lang,
                        phish_material: phishing_email,
                        trainingLang: trainingLang,
                        training_mod: training_module,
                        schType: $("input[name='schType']:checked").val(),
                        schBetRange: schBetRange.value,
                        schTimeStart: schTimeStart.value,
                        schTimeEnd: schTimeEnd.value,
                        schTimeZone: schTimeZone.value,
                        emailFreq: $("input[name='emailFreq']:checked").val(),
                        expire_after: expire_after.value,

                        launch_time: launch_time()

                    };

                    revCampName.value = formData.camp_name ?? '--';
                    revCampType.value = $("#campaign_type option:selected").text() ?? '--';
                    revEmpGroup.value = $("#users_group option:selected").text() ?? '--';
                    revEmailLang.value = $("#email_lang option:selected").text() ?? '--';
                    revPhishmat.value = $("input[name='phish_material']:checked").data('phishmatname') ?? '--';
                    revTrainingLang.value = $("#training_lang option:selected").text() ?? '--';
                    revTrainingMod.value = $("input[name='training_module']:checked").data('trainingname') ?? '--';
                    revCampDelivery.value = $("input[name='schType']:checked").data('val') ?? '--';
                    revBtwDays.value = formData.schBetRange ?? '--';
                    revSchTimeStart.value = formData.schTimeStart ?? '--';
                    revSchTimeEnd.value = formData.schTimeEnd ?? '--';
                    revSchTimeZone.value = formData.schTimeZone ?? '--';
                    revEmailFreq.value = $("input[name='emailFreq']:checked").data('val') ?? '--';
                    revExpAfter.value = formData.expire_after ?? '--';

                    if (formData.campaign_type == 'Phishing') {
                        $("#revTrainingLang").parent().parent().hide();
                        $("#revTrainingMod").parent().parent().hide();

                        $("#revPhishmat").parent().parent().show();
                        $("#revEmailLang").parent().parent().show();
                    }

                    if (formData.campaign_type == 'Training') {
                        $("#revPhishmat").parent().parent().hide();
                        $("#revEmailLang").parent().parent().hide();

                        $("#revTrainingLang").parent().parent().show();
                        $("#revTrainingMod").parent().parent().show();
                    }

                    if (formData.schType == 'immediately') {
                        $("#revBtwDays").parent().parent().hide();
                        $("#revSchTimeZone").parent().parent().hide();
                        $("#revBtwTime").hide();


                    }

                    if (formData.schType == 'scheduled') {
                        $("#revBtwDays").parent().parent().show();
                        $("#revSchTimeZone").parent().parent().show();
                        $("#revBtwTime").show();


                    }

                    // Output JSON
                    console.log(formData);

                    dataToBeSaved = formData;
                }

                // document.getElementById('createCampaign').addEventListener('click', function(e) {
                //     e.preventDefault();

                //     // Log your data to be saved
                //     console.log(dataToBeSaved);

                //     // Fetch the CSRF token from the meta tag
                //     const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                //     // Make the POST request with Axios
                //     axios.post('/campaigns/create', dataToBeSaved, {
                //         headers: {
                //             'X-CSRF-TOKEN': token
                //         }
                //     })
                //     .then(function (response) {
                //         console.log(response.data);
                //         window.location.href = window.location.href;
                //     })
                //     .catch(function (error) {
                //         console.error('Error:', error.response.data);
                //     });
                // });

                $('#createCampaign').click(function(e) {
                    e.preventDefault();

                    console.log(dataToBeSaved);
                    $.post({
                        url: '/tprmcampaigns/create',
                        data: dataToBeSaved,
                        success: function(res) {
                            checkResponse(res);
                            // console.log(res);
                        }
                    })
                })



            });

            function formatDate(date) {
                var month = (date.getMonth() + 1).toString().padStart(2, '0'); // Get month with leading zero
                var day = date.getDate().toString().padStart(2, '0'); // Get day with leading zero
                var year = date.getFullYear(); // Get full year
                var hours = date.getHours().toString().padStart(2, '0'); // Get hours with leading zero
                var minutes = date.getMinutes().toString().padStart(2, '0'); // Get minutes with leading zero
                return month + '/' + day + '/' + year + ' ' + hours + ':' + minutes;
            }

            // Example usage
            // var currentDate = new Date();
            // var formattedDate = formatDate(currentDate);

            //handling imediate and schedule btn
            $("#imediateLabelBtn").click(function() {
                $("#dvSchedule2").addClass("d-none");
                $("#email_frequency").removeClass("d-none");
                // var currentDate = new Date();
                // var formattedDate = formatDate(currentDate);
                // $("#launch_time").val(formattedDate);
            })
            $("#scheduleLabelBtn").click(function() {
                $("#dvSchedule2").removeClass("d-none");
                $("#email_frequency").removeClass("d-none");
            })

            $("#scheduleLLabelBtn").click(function() {
                $("#dvSchedule2").addClass("d-none");
                $("#email_frequency").addClass("d-none")
            })

            $('label[for="foneoff"]').click(function() {
                $("#exp_after").addClass("d-none");
            })

            $('label[for="fmonthly"]').click(function() {
                $("#exp_after").removeClass("d-none");
            })
            $('label[for="fweekly"]').click(function() {
                $("#exp_after").removeClass("d-none");
            })
            $('label[for="fquaterly"]').click(function() {
                $("#exp_after").removeClass("d-none");
            })

            //handling imediate and schedule btn
            $("#rimediateLabelBtn").click(function() {
                $("#rdvSchedule2").addClass("d-none");
                $("#remail_frequency").removeClass("d-none");
                // var currentDate = new Date();
                // var formattedDate = formatDate(currentDate);
                // $("#launch_time").val(formattedDate);
            })
            $("#rscheduleLabelBtn").click(function() {
                $("#rdvSchedule2").removeClass("d-none");
                $("#remail_frequency").removeClass("d-none");
            })

            $("#rscheduleLLabelBtn").click(function() {
                $("#rdvSchedule2").addClass("d-none");
                $("#remail_frequency").addClass("d-none")
            })

            $('label[for="rfoneoff"]').click(function() {
                $("#rexp_after").addClass("d-none");
            })

            $('label[for="rfmonthly"]').click(function() {
                $("#rexp_after").removeClass("d-none");
            })
            $('label[for="rfweekly"]').click(function() {
                $("#rexp_after").removeClass("d-none");
            })
            $('label[for="rfquaterly"]').click(function() {
                $("#rexp_after").removeClass("d-none");
            })

            // $('#rescheduleBtn').on('click', function (e) {
            //     e.preventDefault()
            //     var formData = $('#rescheduleForm').serializeArray();
            //     var data = {};
            //     $.each(formData, function () {
            //         if (data[this.name]) {
            //             if (!data[this.name].push) {
            //                 data[this.name] = [data[this.name]];
            //             }
            //             data[this.name].push(this.value || '');
            //         } else {
            //             data[this.name] = this.value || '';
            //         }
            //     });
            // Display the collected data in the console (for demonstration purposes)
            // console.log(data);

            // $.post({
            //     url: 'campaigns.php?reschduleCamp=1',
            //     data: data,
            //     success: function (response) {
            //         //  console.log(response);
            //         // window.location.reload()
            //         window.location.href = window.location.href;
            //     }
            // })

            // Further processing of data can be done here, e.g., sending it to the server via AJAX
            // });



            // Event listener for input field change
            $('#templateSearch').on('input', function() {
                var searchValue = $(this).val().toLowerCase(); // Get the search value and convert it to lowercase

                // Loop through each template card
                $('.email_templates').each(function() {
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

            // Event listener for input field change
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







            $('#datatable-basic').DataTable({
                language: {
                    searchPlaceholder: 'Search...',
                    sSearch: '',
                },
                "pageLength": 10,
                // scrollX: true
            });
        </script>
    @endpush

@endsection
