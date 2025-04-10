@extends('layouts.app')

@section('title', 'TPRM - Phishing awareness training program')

@section('main-content')

    <div class="main-content app-content">
        <div class="container-fluid mt-4">
            <div class="d-flex justify-content-between">
                <div>
                    <button type="button" class="btn btn-primary mb-3 me-2" data-bs-toggle="modal"
                        data-bs-target="#newCampModal">
                        {{ __('New Campaign') }}
                    </button>
                    <button type="button" class="btn btn-success mb-3 me-2" data-bs-toggle="modal"
                        data-bs-target="#newdomainVerificationModal">
                        {{ __('Show/Add Email') }}
                    </button>
                </div>

                <div>
                    @if (!$allCamps->isEmpty())
                        <button type="button" class="btn btn-danger mb-3 me-2" data-bs-toggle="modal"
                            data-bs-target="#domainDownloadModal">
                            {{ __('Download Scoring') }}
                        </button>
                    @endif

                    <button type="button" class="btn btn-secondary mb-3" data-bs-toggle="modal"
                        data-bs-target="#domainVerificationModal">
                        {{ __('Domain Verification') }}
                    </button>
                </div>
            </div>

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
                                            <th>{{ __('Domain') }}</th>
                                            <th>{{ __('Campaign Type') }}</th>
                                            <th>{{ __('Action') }}</th>
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

                                                <td>
                                                    <span
                                                        class="badge bg-secondary-transparent">{{ $campaign->campaign_type }}</span>

                                                </td>

                                                <td>
                                                    <button
                                                        class="btn btn-icon btn-danger-transparent rounded-pill btn-wave"
                                                        title="Delete"
                                                        onclick="deletecampaign('{{ e($campaign->campaign_id) }}')">
                                                        <i class="ri-delete-bin-line"></i>
                                                    </button>


                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center">{{ __('No Campaigns') }}</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-4">
                                {{ $allCamps->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>


        </div>
    </div>

    {{-- ------------------------------Toasts---------------------- --}}

    <x-toast />



    {{-- --------------------- modals ---------------------- --}}

    <!-- new campaign modal -->
    <div class="modal fade" id="newCampModal" tabindex="-1" aria-labelledby="exampleModalLgLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">{{ __('Add New Campaign') }}</h6>
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
                                            <strong>{{ __('Initial Setup & Domain Selection') }}</strong>
                                        </li>
                                        <li id="pm_step">
                                            <i class='bx bx-mail-send'></i>
                                            <strong>{{ __('Select Phishing Material') }}</strong>
                                        </li>


                                        <li>
                                            <i class='bx bx-check-square'></i>
                                            <strong>{{ __('Review & Submit') }}</strong>
                                        </li>
                                    </ul>
                                    <!-- fieldsets -->
                                    <fieldset class="included">
                                        <div class="form-card">
                                            <div class="row">
                                                <div class="col-lg-6">

                                                    <label for="input-label" class="form-label">{{ __('Campaign Name') }}<sup
                                                            class="text-danger">*</sup></label>
                                                    <input type="text" class="form-control required" id="camp_name"
                                                        placeholder="{{ __('Enter a unique campaign name') }}">

                                                </div>

                                                <div class="col-lg-6 ">

                                                    <label for="input-label" class="form-label">{{ __('Domains') }}</label>
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
                                            {{ __('Next') }}
                                            <i class="ri-arrow-right-line label-btn-icon ms-2 rounded-pill"></i>
                                        </button>
                                    </fieldset>

                                    <fieldset class="included" id="pm_step_form">
                                        <button type="button"
                                            class="btn btn-dark label-btn label-end stickyBtn rounded-pill previous">
                                            {{ __('Previous') }}
                                            <i class="ri-arrow-left-line label-btn-icon ms-2 rounded-pill"></i>
                                        </button>

                                        <button type="button"
                                            class="btn btn-info label-btn stickyBtn label-end last-step rounded-pill next">
                                            {{ __('Next') }}
                                            <i class="ri-arrow-right-line label-btn-icon  ms-2 rounded-pill"></i>
                                        </button>
                                        <div class="form-card">

                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <label for="input-label" class="form-label">{{ __('Email Language') }}</label>

                                                    <select class="form-select" id="email_lang">
                                                        <option value="sq">{{ __('Albanian') }}</option>
                                                        <option value="ar">{{ __('Arabic') }}</option>
                                                        <option value="az">{{ __('Azerbaijani') }}</option>
                                                        <option value="bn">{{ __('Bengali') }}</option>
                                                        <option value="bg">{{ __('Bulgarian') }}</option>
                                                        <option value="ca">{{ __('Catalan') }}</option>
                                                        <option value="zh">{{ __('Chinese') }}</option>
                                                        <option value="zt">{{ __('Chinese (traditional)') }}</option>
                                                        <option value="cs">{{ __('Czech') }}</option>
                                                        <option value="da">{{ __('Danish') }}</option>
                                                        <option value="nl">{{ __('Dutch') }}</option>
                                                        <option value="en" selected="">{{ __('English') }}</option>
                                                        <option value="eo">{{ __('Esperanto') }}</option>
                                                        <option value="et">{{ __('Estonian') }}</option>
                                                        <option value="fi">{{ __('Finnish') }}</option>
                                                        <option value="fr">{{ __('French') }}</option>
                                                        <option value="de">{{ __('German') }}</option>
                                                        <option value="el">{{ __('Greek') }}</option>
                                                        <option value="he">{{ __('Hebrew') }}</option>
                                                        <option value="hi">{{ __('Hindi') }}</option>
                                                        <option value="hu">{{ __('Hungarian') }}</option>
                                                        <option value="id">{{ __('Indonesian') }}</option>
                                                        <option value="ga">{{ __('Irish') }}</option>
                                                        <option value="it">{{ __('Italian') }}</option>
                                                        <option value="ja">{{ __('Japanese') }}</option>
                                                        <option value="ko">{{ __('Korean') }}</option>
                                                        <option value="lv">{{ __('Latvian') }}</option>
                                                        <option value="lt">{{ __('Lithuanian') }}</option>
                                                        <option value="ms">{{ __('Malay') }}</option>
                                                        <option value="nb">{{ __('Norwegian') }}</option>
                                                        <option value="fa">{{ __('Persian') }}</option>
                                                        <option value="pl">{{ __('Polish') }}</option>
                                                        <option value="pt">{{ __('Portuguese') }}</option>
                                                        <option value="ro">{{ __('Romanian') }}</option>
                                                        <option value="ru">{{ __('Russian') }}</option>
                                                        <option value="sk">{{ __('Slovak') }}</option>
                                                        <option value="sl">{{ __('Slovenian') }}</option>
                                                        <option value="es">{{ __('Spanish') }}</option>
                                                        <option value="sv">{{ __('Swedish') }}</option>
                                                        <option value="tl">{{ __('Tagalog') }}</option>
                                                        <option value="th">{{ __('Thai') }}</option>
                                                        <option value="tr">{{ __('Turkish') }}</option>
                                                        <option value="uk">{{ __('Ukranian') }}</option>
                                                        <option value="ur">{{ __('Urdu') }}</option>
                                                    </select>
                                                </div>

                                                <div>

                                                    <label for="templateSearch" class="form-label">{{ __('Search') }}</label>
                                                    <input type="text" class="form-control" id="templateSearch"
                                                        placeholder="{{ __('Search template') }}">

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
                                                                            {{ __('View') }}
                                                                        </button>
                                                                    </div>
                                                                    <div class="fs-semibold fs-14">
                                                                        <input type="radio" name="phish_material"
                                                                            data-phishMatName="{{ $email->name }}"
                                                                            value="{{ $email->id }}" class="btn-check"
                                                                            id="pm{{ $email->id }}">
                                                                        <label class="btn btn-outline-primary mb-3"
                                                                            for="pm{{ $email->id }}">{{ __('Select this attack') }}</label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <p>{{ __('No phishing emails available.') }}</p>
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
                                                        id="imediateLabelBtn" for="imediateBtn">{{ __('Deliver Immediately') }}
                                                    </label>
                                                </div>
                                                <div class="checkb mx-1">

                                                    <input type="radio" class="btn-check" name="schType"
                                                        data-val="Setup Schedule" value="scheduled" id="ScheduleBtn">
                                                    <label class="btn btn-outline-dark mb-3" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        data-bs-original-title="Campaign will deliver emails using a defined schedule over a period of hours and days (e.g. 9am-5pm Monday-Friday)."
                                                        id="scheduleLabelBtn" for="ScheduleBtn">{{ __('Setup Schedule') }}</label>
                                                </div>

                                                <div class="checkb mx-1">

                                                    <input type="radio" class="btn-check" name="schType"
                                                        data-val="Schedule Later" value="schLater" id="ScheduleLBtn">
                                                    <label class="btn btn-outline-dark mb-3" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        data-bs-original-title="Campaign will not deliver emails until an update to the schedule is made at a later date."
                                                        id="scheduleLLabelBtn" for="ScheduleLBtn">{{ __('Schedule Later') }}
                                                        </i></label>
                                                </div>

                                                <!-- <div class="input-group d-none" id="dateTimeSelector">
                                                                                                                                                                                                                    <div class="input-group-text text-muted"> <i class="ri-calendar-line"></i> </div>
                                                                                                                                                                                                                    <input type="text" class="form-control datetime required" id="launch_time" name="launch_time" placeholder="Choose date with time">
                                                                                                                                                                                                                </div> -->

                                            </div>
                                            <div id="dvSchedule2" class="d-none">
                                                <label
                                                    class="text-left control-label col-form-label font-italic mt-3 pt-0"><b>{{ __('Note:') }}</b>{{ __("We will capture employee interactions as long as a campaign remains active (isn't updated or deleted).") }}</label>
                                                <div class="row mb-3">
                                                    <label for="inputEmail3" class="col-sm-4 col-form-label">{{ __('Schedule
                                                        Date') }}<i class='bx bx-info-circle p-2' data-bs-toggle="tooltip"
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
                                                    <label for="inputEmail3" class="col-sm-4 col-form-label">{{ __('Schedule (Between Times)') }} <i class='bx bx-info-circle p-2'
                                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                                            data-bs-original-title="We recommend scheduling campaigns between business hours to get the most ineraction (e.g. 9am - 5pm)"></i></label>
                                                    <div class="col-sm-8">
                                                        <div class="form-group d-flex">
                                                            <input type="time" id="schTimeStart" name="appt"
                                                                class="form-control" value="09:00" step="60">
                                                            <label class="col-md-1 m-t-15" style="text-align:center"> {{ __('To') }}
                                                            </label>
                                                            <input type="time" id="schTimeEnd" name="appt"
                                                                class="form-control" value="17:00" step="60">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row mb-3">
                                                    <label for="inputEmail3" class="col-sm-4 col-form-label">{{ __('Schedule (Time Zone)') }} <i class='bx bx-info-circle p-2'
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
                                                            for="foneoff">{{ __('One-off') }}</label>
                                                    </div>
                                                    <div class="checkb mx-1">

                                                        <input type="radio" class="btn-check" name="emailFreq"
                                                            data-val="Monthly" value="monthly" id="fmonthly">
                                                        <label class="btn btn-outline-dark mb-3"
                                                            for="fmonthly">{{ __('Monthly') }}</label>
                                                    </div>

                                                    <div class="checkb mx-1">

                                                        <input type="radio" class="btn-check" name="emailFreq"
                                                            data-val="Weekly" value="weekly" id="fweekly">
                                                        <label class="btn btn-outline-dark mb-3"
                                                            for="fweekly">{{ __('Weeky') }}</label>
                                                    </div>
                                                    <div class="checkb mx-1">

                                                        <input type="radio" class="btn-check" name="emailFreq"
                                                            data-val="Quaterly" value="quaterly" id="fquaterly">
                                                        <label class="btn btn-outline-dark mb-3"
                                                            for="fquaterly">{{ __('Quaterly') }}</label>
                                                    </div>
                                                    <div id="exp_after" class="d-none">
                                                        <div class="input-group">
                                                            <div class="input-group-text text-muted"> {{ __('Expire After') }}</div>
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
                                            {{ __('Previous') }}
                                            <i class="ri-arrow-left-line label-btn-icon ms-2 rounded-pill"></i>
                                        </button>

                                        <button type="button"
                                            class="btn btn-info label-btn  label-end rounded-pill next">
                                            {{ __('Next') }}
                                            <i class="ri-arrow-right-line label-btn-icon ms-2 rounded-pill"></i>
                                        </button>

                                    </fieldset>

                                    <fieldset class="included">
                                        <div class="form-card row">
                                            <div class="col-lg-6 mb-3">
                                                <div>
                                                    <label for="input-label" class="form-label">{{ __('Campaign Name') }}</label>
                                                    <input type="text" class="form-control" id="revCampName" disabled
                                                        readonly>
                                                </div>
                                            </div>
                                            {{-- <div class="col-lg-6 mb-3">
                                                <div>
                                                    <label for="input-label" class="form-label">Campaign Type</label>
                                                    <input type="text" class="form-control" id="revCampType" disabled
                                                        readonly>
                                                </div>
                                            </div> --}}
                                            <div class="col-lg-4 mb-3">
                                                <div>
                                                    <label for="input-label" class="form-label">{{ __('Employee Group') }}</label>
                                                    <input type="text" class="form-control" id="revEmpGroup" disabled
                                                        readonly>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 mb-3">
                                                <div>
                                                    <label for="input-label" class="form-label">{{ __('Email Language') }}</label>
                                                    <input type="text" class="form-control" id="revEmailLang" disabled
                                                        readonly>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 mb-3">
                                                <div>
                                                    <label for="input-label" class="form-label">{{ __('Phishing Material') }}</label>
                                                    <input type="text" class="form-control" id="revPhishmat" disabled
                                                        readonly>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 mb-3">
                                                <div>
                                                    <label for="input-label" class="form-label">{{ __('Training Language') }}</label>
                                                    <input type="text" class="form-control" id="revTrainingLang"
                                                        disabled readonly>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 mb-3">
                                                <div>
                                                    <label for="input-label" class="form-label">{{ __('Training Module') }}</label>
                                                    <input type="text" class="form-control" id="revTrainingMod"
                                                        disabled readonly>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 mb-3">
                                                <div>
                                                    <label for="input-label" class="form-label">{{ __('Campaign Delivery') }}</label>
                                                    <input type="text" class="form-control" id="revCampDelivery"
                                                        disabled readonly>
                                                </div>
                                            </div>
                                            <div class="col-lg-6 mb-3">
                                                <div>
                                                    <label for="input-label" class="form-label">{{ __('chedule Date') }}</label>
                                                    <input type="text" class="form-control" id="revBtwDays" disabled
                                                        readonly>
                                                </div>
                                            </div>
                                            <div class="col-lg-6 mb-3" id="revBtwTime">
                                                <div>
                                                    <label for="input-label" class="form-label">{{ __('chedule Between
                                                        Times') }}</label>
                                                    <div>
                                                        <div class="form-group d-flex">
                                                            <input type="time" id="revSchTimeStart" name="appt"
                                                                class="form-control" value="09:00" step="60"
                                                                disabled readonly>
                                                            <label class="col-md-1 m-t-15" style="text-align:center">{{ __('To')}}
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
                                                    <label for="input-label" class="form-label">{{ __('Schedule Time Zone') }}</label>
                                                    <input type="text" class="form-control" id="revSchTimeZone"
                                                        disabled readonly>
                                                </div>
                                            </div>
                                            {{-- <div class="col-lg-4 mb-3">
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
                                            </div> --}}


                                        </div>

                                        <button type="button"
                                            class="btn btn-dark label-btn label-end rounded-pill previous">
                                            {{ __('Previous') }}
                                            <i class="ri-arrow-left-line label-btn-icon ms-2 rounded-pill"></i>
                                        </button>

                                        <button type="submit" id="createCampaign"
                                            class="btn btn-info label-btn label-end rounded-pill">
                                            {{ __('Submit') }}
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
                    <h6 class="modal-title">{{ __('Campaign Report') }}</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="card custom-card">

                        <div class="card-body">
                            <ul class="nav nav-pills nav-style-3 mb-3" role="tablist">
                                <li class="nav-item" role="presentation" id="phishing_tab">
                                    <a class="nav-link active" data-bs-toggle="tab" role="tab" aria-current="page"
                                        href="#phishing_campaign" aria-selected="true">{{ __('Phishing Campaign') }}</a>
                                </li>
                                <li class="nav-item" role="presentation" id="training_tab">
                                    <a class="nav-link" data-bs-toggle="tab" role="tab" aria-current="page"
                                        href="#training_campaign" aria-selected="false" tabindex="-1">{{ __('Training
                                        Campaign') }}</a>
                                </li>
                            </ul>
                            <div class="tab-content">
                                <div class="tab-pane show active text-muted" id="phishing_campaign" role="tabpanel">
                                    <div class="table-responsive">
                                        <table class="table text-nowrap table-striped">
                                            <thead>
                                                <tr>
                                                    <th scope="col">{{ __('Campaign name') }}</th>
                                                    <th scope="col">{{ __('Status') }}</th>
                                                    <th scope="col">{{ __('Employees') }}</th>
                                                    <th scope="col">{{ __('Emails Delivered') }}</th>
                                                    <th scope="col">{{ __('Emails Viewed') }}</th>
                                                    <th scope="col">{{ __('Payloads Clicked') }}</th>
                                                    <th scope="col">{{ __('Employees Compromised') }}</th>
                                                    <th scope="col">{{ __('Emails Reported') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody id="campReportStatus">
                                            </tbody>
                                        </table>
                                    </div>

                                    <hr>

                                    <div class="card custom-card">
                                        <div class="card-header">
                                            <div class="card-title">{{ __('Phishing Campaign Statistics') }}</div>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table id="file-export" class="table table-bordered text-nowrap w-100">
                                                    <thead>
                                                        <tr>
                                                            <th>{{ __('Employee Name') }}</th>
                                                            <th>{{ __('Email Address') }}</th>
                                                            <th>{{ __('Email Delivery') }}</th>
                                                            <th>{{ __('Email Viewed') }}</th>
                                                            <th>{{ __('Payload Clicked') }}</th>
                                                            <th>{{ __('Employee Compromised') }}</th>
                                                            <th>{{ __('Email Reported') }}</th>
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
                                                    <th scope="col">{{ __('Campaign name') }}</th>
                                                    <th scope="col">{{ __('Status') }}</th>
                                                    <th scope="col">{{ __('Employees') }}</th>
                                                    <th scope="col">{{ __('Trainings Assigned') }}</th>
                                                    <th scope="col">{{ __('Trainings Completed') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody id="trainingReportStatus">
                                            </tbody>
                                        </table>
                                    </div>

                                    <hr>

                                    <div class="card custom-card">
                                        <div class="card-header">
                                            <div class="card-title">{{ __('Training Campaign Statistics') }}</div>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table id="file-export2" class="table table-bordered text-nowrap w-100">
                                                    <thead>
                                                        <tr>
                                                            <th>{{ __('Email Address') }}</th>
                                                            <th>{{ __('Training Module') }}</th>
                                                            <th>{{ __('Date Assigned') }}</th>
                                                            <th>{{ __('Score') }}</th>
                                                            <th>{{ __('Passing Score') }}</th>
                                                            <th>{{ __('Status') }}</th>
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
                    <h6 class="modal-title">{{ __('Phishing Material') }}</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="min-height: 100vh;">
                    <div class="card-body">
                        <ul class="nav nav-tabs mb-3 tab-style-6" id="myTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="products-tab" data-bs-toggle="tab"
                                    data-bs-target="#email-tab-pane" type="button" role="tab"
                                    aria-controls="email-tab-pane" aria-selected="true"><i
                                        class="bx bx-envelope me-1 align-middle d-inline-block"></i>{{ __('Email') }}</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="sales-tab" data-bs-toggle="tab"
                                    data-bs-target="#website-tab-pane" type="button" role="tab"
                                    aria-controls="website-tab-pane" aria-selected="false" tabindex="-1"><i
                                        class="bx bx-globe me-1 align-middle d-inline-block"></i>{{ __('Website') }}</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="profit-tab" data-bs-toggle="tab"
                                    data-bs-target="#senderp-tab-pane" type="button" role="tab"
                                    aria-controls="senderp-tab-pane" aria-selected="false" tabindex="-1"><i
                                        class="bx bx-envelope me-1 align-middle d-inline-block"></i>{{ __('Sender Profile') }}</button>
                            </li>

                        </ul>
                        <div class="tab-content" id="myTabContent2">
                            <div class="tab-pane fade p-3 border-bottom-0 active show" id="email-tab-pane"
                                role="tabpanel" aria-labelledby="products-tab" tabindex="0">

                                <div class="row mb-3">
                                    <label for="vphishEmail" class="col-sm-6 col-form-label">{{ __('Phishing Email') }}</label>
                                    <div class="col-sm-6">
                                        <input type="email" class="form-control" id="vphishEmail" disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="vSub" class="col-sm-6 col-form-label">{{ __('Email Subject') }}</label>
                                    <div class="col-sm-6">
                                        <input type="email" class="form-control" id="vSub" disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="inputEmail3" class="col-sm-6 col-form-label">{{ __('Employee Requirements') }}</label>
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
                                    <label for="vphishWeb" class="col-sm-6 col-form-label">{{ __('Phishing Website') }}</label>
                                    <div class="col-sm-6">
                                        <input type="email" class="form-control" id="vphishWeb" disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="vPhishUrl" class="col-sm-6 col-form-label">{{ __('Website URL') }}</label>
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
                                    <label for="vsenderProf" class="col-sm-6 col-form-label">{{ __('Sender Profile') }}</label>
                                    <div class="col-sm-6">
                                        <input type="text" class="form-control" id="vsenderProf" disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="vDispName" class="col-sm-6 col-form-label">{{ __('Display Name & Address') }}</label>
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

    <!-- <--Domain view/add modal-->
    <div class="modal fade" id="newdomainVerificationModal" tabindex="-1" aria-labelledby="exampleModalLgLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">{{ __('Email Show/Add') }}</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <div class="table-responsive">
                        <table id="domainVerificationTable" class="table table-bordered text-nowrap w-100">
                            <thead>
                                <tr>
                                    <th>{{ __('Domain Name') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody id="allDomains">
                                @forelse ($allDomains as $domain)
                                    <tr>
                                        <td>{{ $domain->domain }}</td>
                                        <td>
                                            @if ($domain->verified == 1)
                                                <span class="badge bg-success">{{ __('Verified') }}</span>
                                            @else
                                                <span class="badge bg-warning">{{ __('Pending') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($domain->verified == 1)
                                                <button type="button" class="btn btn-outline-info btn-sm ms-2"
                                                    onclick="openDomainModal('{{ $domain->domain }}')">{{ __('Show/Add Email') }}</button>
                                            @endif
                                            <!-- <span role="button" onclick="deleteDomain(`{{ $domain->domain }}`)">
                                                                                                                                                <i class="bx bx-x fs-25"></i>
                                                                                                                                            </span> -->
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-center" colspan="5">{{ __('No records found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>

                        </table>
                    </div>
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
                    <h6 class="modal-title">{{ __('Domain Verification') }}</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <button type="button" id="newDomainVerificationModalBtn" class="btn btn-primary mb-2"
                        data-bs-toggle="modal" data-bs-target="#domainVerificationModal">
                        {{ __('Add Domain For Verification') }}
                    </button>
                    <div class="table-responsive">
                        <table id="domainVerificationTable" class="table table-bordered text-nowrap w-100">
                            <thead>
                                <tr>
                                    <th>{{ __('Domain Name') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody id="allDomains">
                                @forelse ($allDomains as $domain)
                                    <tr>
                                        <td>{{ $domain->domain }}</td>
                                        <td>
                                            @if ($domain->verified == 1)
                                                <span class="badge bg-success">{{ __('Verified') }}</span>
                                            @else
                                                <span class="badge bg-warning">{{ __('Pending') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span role="button" onclick="deleteDomain(`{{ $domain->domain }}`)">
                                                <i class="bx bx-x fs-25"></i>
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-center" colspan="5">{{ __('No records found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>

                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- <--Domain Download modal-->
    <div class="modal fade" id="domainDownloadModal" tabindex="-1" aria-labelledby="exampleModalLgLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-body">

                    <div class="table-responsive">
                        <table id="downloadVerifiedDomain" class="table table-bordered text-nowrap w-100">
                            <thead>
                                <tr>
                                    <th>{{ __('Domain Name') }}</th>
                                    {{-- <th>Status</th> --}}
                                    <th>{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody id="allDomains">
                                @forelse ($allDomains as $domain)
                                    <tr>
                                        <td>{{ $domain->domain }}</td>
                                        {{-- <td>
                                            @if ($domain->verified == 1)
                                                <span class="badge bg-success">Verified</span>
                                            @else
                                                <span class="badge bg-warning">Pending</span>
                                            @endif
                                        </td> --}}
                                        <td>
                                            {{-- @if ($domain->verified == 1)
                                                <button type="button" class="btn btn-outline-info btn-sm ms-2"
                                                    onclick="fetchEmail('{{ $domain->domain }}')">Fetch Email</button>
                                            @endif --}}
                                            <span role="button">
                                                <a target="blank"
                                                    href="{{ route('domain-full-report-download-pdf', ['domain' => $domain->domain]) }}">
                                                    <i class="bi bi-arrow-down-circle fs-25"></i>
                                                </a>




                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-center" colspan="5">{{ __('No records found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>

                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Modal for domain details -->
    <div class="modal fade" id="domainModal" tabindex="-1" aria-labelledby="domainModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="domainModalLabel">{{ __('Domain Details') }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <!-- Modal Body -->
                <div class="modal-body">
                    <p id="domainName" class="fs-5 fw-semibold mb-3"></p>
                    <hr>

                    <p class="text-muted fs-6">{{ __('Associated emails:') }}</p>
                    <ul id="emailList" class="list-unstyled">
                        <!-- Dynamically added email items will appear here -->
                    </ul>

                    <!-- Horizontal line to separate the sections -->
                    <hr>

                    <!-- Add email form -->
                    <div class="mt-4">
                        <label for="emailInput" class="form-label">{{ __('Add Email Address') }}</label>
                        <div class="input-group">
                            <input type="email" id="emailInput" class="form-control"
                                placeholder="{{ __('Enter email address') }}">
                            <button type="button" class="btn btn-primary" id="addEmailButton" onclick="addEmail()">{{ __('Add Email') }}</button>
                        </div>
                        <div id="emailWarning" class="text-danger mt-2" style="display: none;">
                            {{ __('Warning: Email domain does not match the provided domain.') }}
                        </div>
                        {{-- <ul id="emailList" class="list-unstyled mt-3">
                            <!-- Added emails will appear here -->
                        </ul> --}}
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="submitDomainData()">{{ __('Save Changes') }}</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
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
        let newlyAddedEmails = []; // Array to track the recently added emails

        // Function to open the domain modal and fetch emails
        function openDomainModal(domain) {
            console.log("Domain clicked: " + domain); // Debugging line
            document.getElementById('domainName').innerText = domain;

            fetch(`/tprmcampaigns/emails/${domain}`)
                .then(response => response.json())
                .then(emails => {
                    const emailList = document.getElementById('emailList');
                    emailList.innerHTML = ''; // Clear previous items

                    if (emails.length > 0) {
                        emails.forEach(function(email) {
                            const li = document.createElement('li');
                            li.innerHTML = `${email}`;
                            emailList.appendChild(li);
                        });
                    } else {
                        const p = document.createElement('p');
                        p.innerText = 'No emails found for this domain.';
                        emailList.appendChild(p);
                    }

                    var modal = new bootstrap.Modal(document.getElementById('domainModal'));
                    modal.show();
                })
                .catch(error => {
                    console.error('Error fetching emails:', error);
                    alert('Error fetching emails. Please try again.');
                });
        }

        function emailLimitExceed() {
            // Select the <ul> element by its ID
            const ulElement = document.getElementById('emailList');

            // Count the number of <li> elements inside the <ul>
            const liCount = ulElement.querySelectorAll('li').length;
            
            if (liCount >= 5) {
                return true;
            }

            return false;
        }

        // Function to add an email
        function addEmail() {

            if (emailLimitExceed()) {
                Swal.fire(
                    'Limit Exceeded',
                    'You can only add 5 emails',
                    'error'
                );
                return;
            }
            const emailInput = document.getElementById('emailInput');
            const email = emailInput.value.trim();
            const domain = document.getElementById('domainName').innerText.trim();
            const emailDomain = email.split('@')[1];

            if (!email) {
                alert('Please enter a valid email.');
                return;
            }

            if (emailDomain !== domain) {
                document.getElementById('emailWarning').style.display = 'block';
                return;
            } else {
                document.getElementById('emailWarning').style.display = 'none';
            }

            // Check if the email is not already added
            if (newlyAddedEmails.includes(email)) {
                alert('This email has already been added.');
                return;
            }

            // Add the email to the list in the DOM
            const emailList = document.getElementById('emailList');
            const li = document.createElement('li');
            li.innerHTML = `${email}`;

            // Only show the "Remove" button for the most recently added email
            li.innerHTML +=
                `<button type="button" class="btn btn-sm btn-danger ms-2" onclick="removeEmail('${email}')">Remove</button>`;
            emailList.appendChild(li);

            // Add the email to the recently added emails array
            newlyAddedEmails.push(email);

            // Hide the "Remove" button for the previous email
            updateRemoveButtons();

            emailInput.value = ''; // Clear the input after adding
        }

        // Function to remove the most recently added email
        function removeEmail(email) {
            // Remove the last email from the array
            newlyAddedEmails.pop();

            // Remove the email from the list in the DOM
            const emailList = document.getElementById('emailList');
            const liList = emailList.getElementsByTagName('li');
            for (let li of liList) {
                if (li.innerText.includes(email)) {
                    li.remove();
                    break; // Remove the first matching email
                }
            }

            // Update the display of "Remove" buttons
            updateRemoveButtons();
        }

        // Function to update "Remove" buttons (only show for the last added email)
        function updateRemoveButtons() {
            const emailList = document.getElementById('emailList');
            const liList = emailList.getElementsByTagName('li');

            // Loop through all email items and update the visibility of the "Remove" button
            for (let i = 0; i < liList.length; i++) {
                const li = liList[i];
                const email = li.innerText.split(' ')[0]; // Extract the email part

                // If the email is the most recent added, show the "Remove" button
                if (newlyAddedEmails.includes(email)) {
                    if (email === newlyAddedEmails[newlyAddedEmails.length - 1]) {
                        const removeButton = li.querySelector('.btn-danger');
                        if (!removeButton) {
                            li.innerHTML +=
                                `<button type="button" class="btn btn-sm btn-danger ms-2" onclick="removeEmail('${email}')">Remove</button>`;
                        }
                    } else {
                        // Hide the "Remove" button for emails that are not the most recently added
                        const removeButton = li.querySelector('.btn-danger');
                        if (removeButton) {
                            removeButton.remove();
                        }
                    }
                }
            }
        }

        // Function to submit the domain data with newly added emails
        function submitDomainData() {
            const domain = document.getElementById('domainName').innerText.trim();

            if (!domain) {
                alert('Domain name is missing.');
                return;
            }

            if (newlyAddedEmails.length === 0) {
                alert('No new emails to save.');
                return;
            }

            const selectedEmails = newlyAddedEmails;

            $.ajax({
                url: '/tprmcampaigns/emailtprmnewGroup',
                type: 'POST',
                data: {
                    emails: selectedEmails,
                    domainName: domain,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    console.log('Response:', response);
                    // Check if the response is HTML, indicating a redirect
                    if (typeof response === 'string' && response.includes('<html>')) {
                        // Assume success since controller redirected
                        Swal.fire(
                            'Emails saved successfully!',
                            'New emails added successfully.',
                            'success'
                        );

                        // Redirect manually after showing success message
                        setTimeout(() => {
                            window.location.href = '/tprmcampaigns';
                        }, 2000);
                    } else {
                        // Handle as JSON response if response contains a status
                        if (response.status === 1) {
                            Swal.fire(
                                'Emails saved successfully!',
                                response.msg,
                                'success'
                            );
                        } else {
                            Swal.fire(
                                'Something went wrong!',
                                response.msg || 'Unknown error occurred.',
                                'error'
                            );
                        }
                        setTimeout(() => {
                            window.location.href = window.location.href;
                        }, 2000);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                    Swal.fire(
                        'Error!',
                        'Could not complete the request.',
                        'error'
                    );
                }
            });
        }




        function fetchEmail(domain) {
            // Fetch email for the given domain via AJAX
            $.ajax({
                url: '/tprmcampaigns/fetchEmail',
                type: 'POST',
                data: {
                    domain: domain,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    // Debugging: Check the structure of the response
                    console.log('AJAX Response:', response);

                    // Check if the response contains emails
                    if (!response.emails || response.emails.length === 0) {
                        document.getElementById('modalEmail').innerHTML =
                            '<p>No emails found for this domain.</p>';
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

            const invalidTagsPattern = /<[^>]*>/;
            const invalidPhpPattern = /^<\?php/;
            const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            const domainPattern = /^(?!:\/\/)([a-zA-Z0-9-]{1,63}\.)+[a-zA-Z]{2,}$/;

            if (domain == '') {
                Swal.fire(
                    'Oops!',
                    'Please Enter a domain',
                    'error'
                );
                return;
            }

            if (invalidPhpPattern.test(domain) || invalidTagsPattern.test(domain)) {
                Swal.fire(
                    'Oops!',
                    'Invalid input detected.',
                    'error'
                );
                return;
            }

            if (emailPattern.test(domain) || !domainPattern.test(domain)) {
                Swal.fire(
                    'Oops!',
                    'Please enter a valid domain',
                    'error'
                );
                return;
            }

            if (domain && !domains.includes(domain)) {
                domains.push(domain);
                updateDomainList();
                domainInput.value = ""; // Clear input after adding
            } else if (domains.includes(domain)) {
                Swal.fire(
                    'Oops!',
                    'This domain is already added.',
                    'error'
                );
            } else {
                Swal.fire(
                    'Oops!',
                    'Something went wrong.',
                    'error'
                );
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
                            Swal.fire(
                                'Request Submitted',
                                `${data.msg}`,
                                'success'
                            ).then((result) => {
                                if (result.isConfirmed) {
                                    location.reload();
                                }
                            });
                            domains = []; // Clear domains list after successful submission
                            updateDomainList();
                            // Optionally close the modal if needed
                            $('#newDomainVerificationModal').modal('hide'); // Use jQuery for Bootstrap modal
                        } else if (data.status === 0) {
                            Swal.fire(
                                'Oops!',
                                `${data.msg}`,
                                'error'
                            );
                            return;

                        } else {
                            Swal.fire(
                                'Oops!',
                                `Unable to submit domains for verification: ${data.msg}`,
                                'error'
                            );
                            return;
                        }
                    })
                    .catch(error => {
                        console.error("Error submitting domains:", error);
                        Swal.fire(
                            'Oops!',
                            'An error occurred while submitting domains. Please try again.',
                            'error'
                        );
                        return;

                        // Reset the buttons in case of error
                        document.getElementById("submitSpinner").classList.add("d-none");
                        document.getElementById("sendOtpBtn").classList.remove("d-none");
                    });
            } else {
                Swal.fire(
                    'Oops!',
                    'Please add at least one domain.',
                    'error'
                );
                return;
            }
        }
    </script>



    <div class="modal" id="newDomainVerificationModal" tabindex="-1" aria-labelledby="exampleModalLgLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">{{ __('Verify Domains') }}</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">
                        {{ __('Domain verification is performed through challenge-response authentication of the provided email addresses. (e.g., verifying support@mybusiness.com will enable mybusiness.com.)') }}
                    </p>

                    <!-- Form to Add Domains -->
                    <form id="domainForm" method="post" onsubmit="return false;">
                        <div class="mb-3">
                            <label for="domainEmailInput" class="form-label">{{ __('Domain') }}<sup
                                    class="text-danger">*</sup></label>
                            <input type="text" class="form-control" id="domainEmailInput"
                                placeholder="i.e. domain.com" />
                        </div>
                        <button type="button" class="btn btn-secondary" onclick="addDomain()">{{ __('Add Domain') }}</button>
                    </form>

                    <!-- List of Added Domains -->
                    <div class="mt-3">
                        <h6>{{ __('Domains to Verify:') }}</h6>
                        <ul id="domainList" class="list-group">
                            <!-- Dynamically added domains will appear here -->
                        </ul>
                    </div>

                    <!-- Submit Button and Spinner -->
                    <button type="button" id="sendOtpBtn" class="btn btn-primary my-3" onclick="submitDomains()">{{ __('Submit Domains for Verification') }}</button>
                    <button class="btn btn-primary my-3 d-none" id="submitSpinner">
                        <span class="me-2">{{ __('Please wait...') }}</span>
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
                    <h6 class="modal-title">{{ __('Campaign Report') }}</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="card custom-card">

                        <div class="card-body">
                            <ul class="nav nav-pills nav-style-3 mb-3" role="tablist">
                                <li class="nav-item" role="presentation" id="phishing_tab">
                                    <a class="nav-link active" data-bs-toggle="tab" role="tab" aria-current="page"
                                        href="#phishing_campaign" aria-selected="true">{{ __('Phishing
                                        Campaign') }}</a>
                                </li>
                                <li class="nav-item" role="presentation" id="training_tab">
                                    <a class="nav-link" data-bs-toggle="tab" role="tab" aria-current="page"
                                        href="#training_campaign" aria-selected="false" tabindex="-1">{{ __('Training
                                        Campaign') }}</a>
                                </li>
                            </ul>
                            <div class="tab-content">
                                <div class="tab-pane show active text-muted" id="phishing_campaign" role="tabpanel">
                                    <div class="table-responsive">
                                        <table class="table text-nowrap table-striped">
                                            <thead>
                                                <tr>
                                                    <th scope="col">{{ __('Campaign name') }}</th>
                                                    <th scope="col">{{ __('Status') }}</th>
                                                    <th scope="col">{{ __('Employees') }}</th>
                                                    <th scope="col">{{ __('Emails Delivered') }}</th>
                                                    <th scope="col">{{ __('Emails Viewed') }}</th>
                                                    <th scope="col">{{ __('Payloads Clicked') }}</th>
                                                    <th scope="col">{{ __('Employees Compromised') }}</th>
                                                    <th scope="col">{{ __('Emails Reported') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody id="campReportStatus">
                                            </tbody>
                                        </table>
                                    </div>

                                    <hr>

                                    <div class="card custom-card">
                                        <div class="card-header">
                                            <div class="card-title">{{ __('Phishing Campaign Statistics') }}</div>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table id="file-export" class="table table-bordered text-nowrap w-100">
                                                    <thead>
                                                        <tr>
                                                            <th>{{ __('Employee Name') }}</th>
                                                            <th>{{ __('Email Address') }}</th>
                                                            <th>{{ __('Email Delivery') }}</th>
                                                            <th>{{ __('Email Viewed') }}</th>
                                                            <th>{{ __('Payload Clicked') }}</th>
                                                            <th>{{ __('Employee Compromised') }}</th>
                                                            <th>{{ __('Email Reported') }}</th>
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
                                                    <th scope="col">{{ __('Campaign name') }}</th>
                                                    <th scope="col">{{ __('Status') }}</th>
                                                    <th scope="col">{{ __('Employees') }}</th>
                                                    <th scope="col">{{ __('Trainings Assigned') }}</th>
                                                    <th scope="col">{{ __('Trainings Completed') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody id="trainingReportStatus">
                                            </tbody>
                                        </table>
                                    </div>

                                    <hr>

                                    <div class="card custom-card">
                                        <div class="card-header">
                                            <div class="card-title">{{ __('Training Campaign Statistics') }}</div>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table id="file-export2" class="table table-bordered text-nowrap w-100">
                                                    <thead>
                                                        <tr>
                                                            <th>{{ __('Email Address') }}</th>
                                                            <th>{{ __('Training Module') }}</th>
                                                            <th>{{ __('Date Assigned') }}</th>
                                                            <th>{{ __('Score') }}</th>
                                                            <th>{{ __('Passing Score') }}</th>
                                                            <th>{{ __('Status') }}</th>
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
        <script src="/js/tprmdomain.js"></script>
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
                    searchPlaceholder: "{{ __('Search...') }}",
                    sSearch: '',
                },
                "pageLength": 10,
                // scrollX: true
            });

            $('#domainVerificationTable').DataTable({
                language: {
                    searchPlaceholder: "{{ __('Search...') }}",
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
                                        searchPlaceholder: "{{ __('Search...') }}",
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
                    searchPlaceholder: "{{ __('Search...') }}",
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
                    // revCampType.value = $("#campaign_type option:selected").text() ?? '--';
                    revEmpGroup.value = $("#users_group option:selected").text().trim() ?? '--';
                    revEmailLang.value = $("#email_lang option:selected").text() ?? '--';
                    revPhishmat.value = $("input[name='phish_material']:checked").data('phishmatname') ?? '--';
                    revTrainingLang.value = $("#training_lang option:selected").text().trim() ?? '--';
                    revTrainingMod.value = $("input[name='training_module']:checked").data('trainingname') ?? '--';
                    revCampDelivery.value = $("input[name='schType']:checked").data('val') ?? '--';
                    revBtwDays.value = formData.schBetRange ?? '--';
                    revSchTimeStart.value = formData.schTimeStart ?? '--';
                    revSchTimeEnd.value = formData.schTimeEnd ?? '--';
                    revSchTimeZone.value = formData.schTimeZone ?? '--';
                    // revEmailFreq.value = $("input[name='emailFreq']:checked").data('val') ?? '--';
                    // revExpAfter.value = formData.expire_after ?? '--';

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
                    searchPlaceholder: "{{ __('Search...') }}",
                    sSearch: '',
                },
                "pageLength": 10,
                // scrollX: true
            });
        </script>
    @endpush

@endsection
