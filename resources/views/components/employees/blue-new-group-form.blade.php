<form action="{{ route('bluecollar.employee.newgroup') }}" method="post">
    @csrf
    <div class="row align-items-end">
        <div class="col-lg-6">
            <label for="input-label" class="form-label">{{ __('Employee Group Name') }}</label>
            <input type="text" class="form-control" name="usrGroupName">
        </div>
        <div class="col-lg-6">
            <button type="submit" class="btn btn-primary mt-3 btn-wave waves-effect waves-light">{{ __('Add Employee Group') }}</button>
        </div>
    </div>
</form>
