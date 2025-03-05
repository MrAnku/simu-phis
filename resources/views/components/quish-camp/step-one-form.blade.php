<fieldset>
    <div class="form-card">
        <div class="row">
            <div class="col-lg-6">

                <label for="input-label" class="form-label">Campaign Name<sup class="text-danger">*</sup></label>
                <input type="text" class="form-control qcamp" data-name="Campaign Name" id="camp_name"
                    placeholder="Enter a unique campaign name">

            </div>
            <div class="col-lg-6">

                <label for="input-label" class="form-label">Campaign Type</label>
                <select class="form-control qcamp" id="campaign_type" data-name="Campaign Type">
                    <option value="">Choose</option>
                    <option value="quishing">Simulate Quishing</option>
                    {{-- <option value="training">Security Awareness Training</option> --}}
                    <option value="quishing-training">Simulate Quishing &amp; Security Awareness Training</option>
                </select>

            </div>

        </div>

        <div class="row">
            <div class="col-lg-6 mt-3">

                <label for="input-label" class="form-label">Select Employee
                    Group</label>
                <select class="form-control qcamp" id="users_group" data-name="Employee Group">
                    @forelse ($empGroups as $group)
                        <option value="{{ $group->group_id }}">{{ $group->group_name }}</option>
                    @empty
                        <option value="">No group found</option>
                    @endforelse
                </select>

            </div>
        </div>
    </div> 
    <div class="mt-4 text-center">
        <button type="button" class="btn btn-info label-btn label-end stickyBtn rounded-pill next" onclick="showNext('stepOne')">
            Next
            <i class="ri-arrow-right-line label-btn-icon ms-2 rounded-pill"></i>
        </button>
    </div>
    
</fieldset>

