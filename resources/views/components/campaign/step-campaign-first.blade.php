<div class="form-card">
    <div class="row">
        <div class="col-lg-6">

            <label for="input-label" class="form-label">{{ __('Campaign Name') }}<sup
                    class="text-danger">*</sup></label>
            <input type="text" class="form-control required" id="camp_name"
                placeholder="{{ __('Enter a unique campaign name') }}">

        </div>
        <div class="col-lg-6">

            <label for="input-label" class="form-label">{{ __('Campaign Type') }}<sup
                class="text-danger">*</sup></label>
            <select class="form-control required" id="campaign_type">
                <option value="">{{ __('Choose') }}</option>
                <option value="Phishing">{{ __('Simulate Phishing') }}</option>
                <option value="Training">{{ __('Security Awareness Training') }}</option>
                <option value="Phishing & Training">{{ __('Simulate Phishing & Security Awareness Training') }}</option>
            </select>

        </div>

    </div>

    <div class="row">
        <div class="col-lg-6 mt-3">

            <label for="input-label" class="form-label">{{ __('Select Employee Group') }}<sup
                class="text-danger">*</sup></label>
            <select class="form-control required" id="users_group">
                <option value="">{{ __('Choose') }}</option>
                @foreach ($usersGroups as $group)
                    <option value="{{ $group->group_id }}">
                        {{ $group->group_name }}
                    </option>
                @endforeach
            </select>

        </div>
    </div>
</div>