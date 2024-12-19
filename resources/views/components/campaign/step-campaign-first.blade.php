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