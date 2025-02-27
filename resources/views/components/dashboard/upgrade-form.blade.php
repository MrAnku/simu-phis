<form action="{{ route('reqNewLimit') }}" method="post">
    @csrf

    <div class="mb-3">
        <label for="input-label" class="form-label">Current Limit</label>
        <input type="text" name="old_limit" class="form-control"
            value="{{ $package['alloted_emp'] }}" disabled>
        <input type="hidden" name="usage" value="{{ $package['used_percent'] }}">

    </div>

    <div class="mb-3">
        <label for="input-label" class="form-label">New Limit<sup class="text-danger">*</sup></label>
        <input type="number" name="new_limit" class="form-control" min="10" max="5000"
            placeholder="Enter new limit value ex. 1000">

    </div>
    <div class="mb-3">
        <label for="input-label" class="form-label">Additional Information</label>
        <textarea class="form-control" name="add_info" id="" rows="5"></textarea>

    </div>

    <div class="mb-3">
        <button type="submit" class="btn btn-primary mt-3 btn-wave waves-effect waves-light">
            Submit Request
        </button>
    </div>
</form>