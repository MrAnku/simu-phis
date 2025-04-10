<fieldset>
    <div class="form-card">
        <div class="row">
            <div class="col-lg-6">

                <label for="input-label" class="form-label">{{ __('Campaign Name') }}<sup class="text-danger">*</sup></label>
                <input type="text" class="form-control qcamp" data-name="Campaign Name" id="camp_name"
                    placeholder="{{ __('Enter a unique campaign name') }}">

            </div>
            <div class="col-lg-6">

                <label for="input-label" class="form-label">{{ __('Campaign Type') }}</label>
                <select class="form-control qcamp" id="campaign_type" data-name="Campaign Type">
                    <option value="">{{ __('Choose') }}</option>
                    <option value="quishing">{{ __('Simulate Quishing') }}</option>
                    {{-- <option value="training">Security Awareness Training</option> --}}
                    <option value="quishing-training">{{ __('Simulate Quishing') }} &amp; {{ __('Security Awareness Training') }}</option>
                </select>

            </div>

        </div>

        <div class="row">
            <div class="col-lg-6 mt-3">

                <label for="input-label" class="form-label"e>{{ __('Select Employe Group') }}</label>
                <select class="form-control qcamp" id="users_group" data-name="Employee Group">
                    @forelse ($empGroups as $group)
                        <option value="{{ $group->group_id }}">{{ $group->group_name }}</option>
                    @empty
                        <option value="">{{ __('No group found') }}</option>
                    @endforelse
                </select>

            </div>
        </div>
    </div> 
    <div class="mt-4 text-center">
        <button type="button" class="btn btn-info label-btn label-end stickyBtn rounded-pill next" onclick="showNext('stepOne')">
            {{ __('Next') }}
            <i class="ri-arrow-right-line label-btn-icon ms-2 rounded-pill"></i>
        </button>
    </div>
    
</fieldset>

