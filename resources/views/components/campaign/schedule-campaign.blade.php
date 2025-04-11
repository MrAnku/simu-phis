<div class="form-card">
    <div class="d-flex">
        <div class="checkb mx-1">

            <input type="radio" class="btn-check" name="schType" data-val="Immediately" value="immediately" id="imediateBtn"
                checked>
            <label class="btn btn-outline-dark mb-3" data-bs-toggle="tooltip" data-bs-placement="top"
                data-bs-original-title="{{ __('Campaign will begin delivering emails within 1-3 minutes of submission.') }}"
                id="imediateLabelBtn" for="imediateBtn">{{ __('Deliver Immediately') }}
            </label>
        </div>
        <div class="checkb mx-1">

            <input type="radio" class="btn-check" name="schType" data-val="Setup Schedule" value="scheduled"
                id="ScheduleBtn">
            <label class="btn btn-outline-dark mb-3" data-bs-toggle="tooltip" data-bs-placement="top"
                data-bs-original-title="{{ __('Campaign will deliver emails using a defined schedule over a period of hours and days (e.g. 9am-5pm Monday-Friday).') }}"
                id="scheduleLabelBtn" for="ScheduleBtn">{{ __('Setup Schedule') }}</label>
        </div>

        <div class="checkb mx-1">

            <input type="radio" class="btn-check" name="schType" data-val="Schedule Later" value="schLater"
                id="ScheduleLBtn">
            <label class="btn btn-outline-dark mb-3" data-bs-toggle="tooltip" data-bs-placement="top"
                data-bs-original-title="{{ __('Campaign will not deliver emails until an update to the schedule is made at a later date.') }}"
                id="scheduleLLabelBtn" for="ScheduleLBtn">{{ __('Schedule Later') }}
                </i></label>
        </div>

        <!-- <div class="input-group d-none" id="dateTimeSelector">
                                                <div class="input-group-text text-muted"> <i class="ri-calendar-line"></i> </div>
                                                <input type="text" class="form-control datetime required" id="launch_time" name="launch_time" placeholder="Choose date with time">
                                            </div> -->

    </div>
    <div id="dvSchedule2" class="d-none">
        <label class="text-left control-label col-form-label font-italic mt-3 pt-0"><b>{{ __('Note:') }}</b>{{ __("We will capture employee interactions as long as a campaign remains active (isn't updated or deleted).") }} </label>
        <div class="row mb-3">
            <label for="inputEmail3" class="col-sm-4 col-form-label">{{ __('Schedule Date') }}<i class='bx bx-info-circle p-2'
                    data-bs-toggle="tooltip" data-bs-placement="top"
                    data-bs-original-title="{{ __('Select a particular date for shooting this campaign') }}"></i>
            </label>
            <div class="col-sm-8">
                <div class="form-group">
                    <div class="input-group">

                        <input type="text" class="form-control flatpickr-input active" id="schBetRange"
                            placeholder="YYYY-MM-DD" readonly="readonly">
                        <div class="input-group-text text-muted"> <i class="ri-calendar-line"></i> </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <label for="inputEmail3" class="col-sm-4 col-form-label">{{ __('Schedule (Between Times)') }} <i class='bx bx-info-circle p-2' data-bs-toggle="tooltip" data-bs-placement="top"
                    data-bs-original-title="{{ __('We recommend scheduling campaigns between business hours to get the most ineraction (e.g. 9am - 5pm)') }}"></i></label>
            <div class="col-sm-8">
                <div class="form-group d-flex">
                    <input type="time" id="schTimeStart" name="appt" class="form-control" value="09:00"
                        step="60">
                    <label class="col-md-1 m-t-15" style="text-align:center"> {{ __('To') }}
                    </label>
                    <input type="time" id="schTimeEnd" name="appt" class="form-control" value="17:00"
                        step="60">
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <label for="inputEmail3" class="col-sm-4 col-form-label">{{ __('Schedule (Time Zone)') }} <i class='bx bx-info-circle p-2' data-bs-toggle="tooltip" data-bs-placement="top"
                    data-bs-original-title="{{ __('Select the timezone that best aligns with your business hours.') }}"></i></label>
            <div class="col-sm-8">
                <div class="form-group d-flex">

                    <x-timezone-select id="schTimeZone" />
                </div>
            </div>
        </div>


    </div>

    <div id="email_frequency">


        <div class="d-flex">

            <div class="checkb mx-1">

                <input type="radio" class="btn-check" name="emailFreq" data-val="One-off" value="one"
                    id="foneoff" checked>
                <label class="btn btn-outline-dark mb-3" for="foneoff">{{ __('One-off') }}</label>
            </div>
            <div class="checkb mx-1">

                <input type="radio" class="btn-check" name="emailFreq" data-val="Monthly" value="monthly"
                    id="fmonthly">
                <label class="btn btn-outline-dark mb-3" for="fmonthly">{{ __('Monthly') }}</label>
            </div>

            <div class="checkb mx-1">

                <input type="radio" class="btn-check" name="emailFreq" data-val="Weekly" value="weekly"
                    id="fweekly">
                <label class="btn btn-outline-dark mb-3" for="fweekly">{{ __('Weekly') }}</label>
            </div>
            <div class="checkb mx-1">

                <input type="radio" class="btn-check" name="emailFreq" data-val="Quaterly" value="quaterly"
                    id="fquaterly">
                <label class="btn btn-outline-dark mb-3" for="fquaterly">{{ __('Quaterly') }}</label>
            </div>
            <div id="exp_after" class="d-none">
                <div class="input-group">
                    <div class="input-group-text text-muted"> {{ __('Expire After') }}</div>
                    <input type="text" class="form-control flatpickr-input active" id="expire_after"
                        placeholder="{{ __('Choose date') }}" readonly="readonly">
                    <div class="input-group-text text-muted"> <i class="ri-calendar-line"></i> </div>
                </div>
            </div>



        </div>
    </div>


</div>
