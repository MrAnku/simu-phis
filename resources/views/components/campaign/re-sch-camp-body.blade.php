<form action="{{ route('reschedule.campaign') }}" method="post" id="rescheduleForm">
    @csrf
    <p class="text-center">{{ __('Schedule Type') }}</p>
    <div class="form-card">
        <div class="d-flex justify-content-center">
            <div class="checkb mx-1">

                <input type="radio" class="btn-check" name="rschType" data-val="Imediately"
                    value="immediately" id="rimediateBtn" checked>
                <label class="btn btn-outline-dark mb-3" data-bs-toggle="tooltip"
                    data-bs-placement="top"
                    data-bs-original-title="{{ __('Campaign will begin delivering emails within 1-3 minutes of submission.') }}"
                    id="rimediateLabelBtn" for="rimediateBtn">{{ __('Deliver Immediately') }} </label>
            </div>
            <div class="checkb mx-1">

                <input type="radio" class="btn-check" name="rschType" data-val="Setup Schedule"
                    value="scheduled" id="rScheduleBtn">
                <label class="btn btn-outline-dark mb-3" data-bs-toggle="tooltip"
                    data-bs-placement="top"
                    data-bs-original-title="{{ __('Campaign will deliver emails using a defined schedule over a period of hours and days (e.g. 9am-5pm Monday-Friday).') }}"
                    id="rscheduleLabelBtn" for="rScheduleBtn">{{ __('Setup Schedule') }}</label>
            </div>



        </div>
        <div id="rdvSchedule2" class="d-none">
            <label class="text-left control-label col-form-label font-italic mt-3 pt-0"><b>{{ __('Note:') }}</b>{{ __("We will capture employee interactions as long as a campaign remains active (isn't updated or deleted).") }} </label>
            <div class="row mb-3">
                <label for="inputEmail3" class="col-sm-4 col-form-label">{{ __('Schedule Date') }}<i
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
                <label for="inputEmail3" class="col-sm-4 col-form-label">{{ __('Schedule (Between Times)') }} <i
                        class='bx bx-info-circle p-2' data-bs-toggle="tooltip"
                        data-bs-placement="top"
                        data-bs-original-title="We recommend scheduling campaigns between business hours to get the most ineraction (e.g. 9am - 5pm)"></i></label>
                <div class="col-sm-8">
                    <div class="form-group d-flex">
                        <input type="time" id="rschTimeStart" name="startTime"
                            class="form-control" value="09:00" step="60">
                        <label class="col-md-1 m-t-15" style="text-align:center"> {{ __('To') }} </label>
                        <input type="time" id="rschTimeEnd" name="endTime" class="form-control"
                            value="17:00" step="60">
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <label for="inputEmail3" class="col-sm-4 col-form-label">{{ __('Schedule (Time Zone)') }} <i
                        class='bx bx-info-circle p-2' data-bs-toggle="tooltip"
                        data-bs-placement="top"
                        data-bs-original-title="Select the timezone that best aligns with your business hours."></i></label>
                <div class="col-sm-8">
                    <div class="form-group d-flex">


                        <x-timezone-select id="rschTimeZone" name="rschTimeZone" />
                    </div>
                </div>
            </div>


        </div>
        <hr style="margin: 4px;">
        <div id="remail_frequency">

            <p class="text-center">{{ __('Email Frequency') }}</p>
            <div class="d-flex justify-content-center">

                <div class="checkb mx-1">

                    <input type="radio" class="btn-check" name="emailFreq" data-val="One-off"
                        value="one" id="rfoneoff" checked>
                    <label class="btn btn-outline-dark mb-3" for="rfoneoff">{{ __('One-off') }}</label>
                </div>
                <div class="checkb mx-1">

                    <input type="radio" class="btn-check" name="emailFreq" data-val="Monthly"
                        value="monthly" id="rfmonthly">
                    <label class="btn btn-outline-dark mb-3" for="rfmonthly">{{ __('Monthly') }}</label>
                </div>

                <div class="checkb mx-1">

                    <input type="radio" class="btn-check" name="emailFreq" data-val="Weekly"
                        value="weekly" id="rfweekly">
                    <label class="btn btn-outline-dark mb-3" for="rfweekly">{{ __('Weekly') }}</label>
                </div>
                <div class="checkb mx-1">

                    <input type="radio" class="btn-check" name="emailFreq" data-val="Quaterly"
                        value="quaterly" id="rfquaterly">
                    <label class="btn btn-outline-dark mb-3" for="rfquaterly">{{ __('Quaterly') }}</label>
                </div>
                <div id="rexp_after" class="d-none">
                    <div class="input-group">
                        <div class="input-group-text text-muted">   </div>
                        <input type="text" class="form-control flatpickr-input active"
                            name="rexpire_after" id="rexpire_after" placeholder="{{ __('Choose date') }}"
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
                class="btn btn-primary btn-wave waves-effect waves-light">{{ __('Re-schedule') }}</button>
        </div>


    </div>
</form>