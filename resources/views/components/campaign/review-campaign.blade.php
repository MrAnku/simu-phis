<div class="form-card row">
    <div class="col-lg-6 mb-3">
        <div>
            <label for="input-label" class="form-label">{{ __('Campaign Name') }}</label>
            <input type="text" class="form-control" id="revCampName" disabled
                readonly>
        </div>
    </div>
    <div class="col-lg-6 mb-3">
        <div>
            <label for="input-label" class="form-label">{{ __('Campaign Type') }}</label>
            <input type="text" class="form-control" id="revCampType" disabled
                readonly>
        </div>
    </div>
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
            <label for="input-label" class="form-label">{{ __('Days Until Due') }}</label>
            <input type="text" class="form-control" id="revDays_until_due"
                disabled readonly>
        </div>
    </div>
    <div class="col-lg-4 mb-3">
        <div>
            <label for="input-label" class="form-label">{{ __('Training Type') }}</label>
            <input type="text" class="form-control" id="revTrainingType"
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
            <label for="input-label" class="form-label">{{ __('Schedule Date') }}</label>
            <input type="text" class="form-control" id="revBtwDays" disabled
                readonly>
        </div>
    </div>
    <div class="col-lg-6 mb-3" id="revBtwTime">
        <div>
            <label for="input-label" class="form-label">{{ __('Schedule Between Times') }}</label>
            <div>
                <div class="form-group d-flex">
                    <input type="time" id="revSchTimeStart" name="appt"
                        class="form-control" value="09:00" step="60"
                        disabled readonly>
                    <label class="col-md-1 m-t-15" style="text-align:center"> {{ __('To') }}
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
    <div class="col-lg-4 mb-3">
        <div>
            <label for="input-label" class="form-label">{{ __('Email Frequency') }}</label>
            <input type="text" class="form-control" id="revEmailFreq" disabled
                readonly>
        </div>
    </div>
    <div class="col-lg-4 mb-3">
        <div>
            <label for="input-label" class="form-label">{{ __('Expire After') }}</label>
            <input type="text" class="form-control" id="revExpAfter" disabled
                readonly>
        </div>
    </div>


</div>