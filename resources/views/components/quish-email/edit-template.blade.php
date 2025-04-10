<form action="{{ route('quishing.emails.update') }}" method="post">
    @csrf
    <div class="mb-3">
        <label for="input-label" class="form-label">{{ __('Associate Website') }}<sup
                class="text-danger">*</sup></label>
        <select class="form-select" name="website" id="updateEAssoWebsite" required>
            <option value="">{{ __('Choose') }}</option>

            @forelse ($phishingWebsites as $phishingWebsite)
                <option value="{{ $phishingWebsite->id }}">{{ $phishingWebsite->name }}</option>
            @empty
                <option value="">{{ __('Websites not available') }}</option>
            @endforelse
        </select>
        <input type="hidden" name="template_id" id="editEtemp">

    </div>

    <div class="mb-3">
        <label for="input-label" class="form-label">{{ __('Difficulty') }}</label>
        <select class="form-select" name="difficulty" id="difficulty"
            aria-label="Default select example">
            <option value="easy" selected>{{ __('Easy') }}</option>
            <option value="medium">{{ __('Medium') }}</option>
            <option value="hard">{{ __('Hard') }}</option>
        </select>
    </div>

    <div class="mb-3">
        <label for="input-label" class="form-label">{{ __('Sender Profile') }}<sup
                class="text-danger">*</sup></label>
        <select class="form-select" name="sender_profile" id="updateESenderProfile" required>
            <option value="0">{{ __('Choose') }}</option>
            @forelse ($senderProfiles as $senderProfile)
                <option value="{{ $senderProfile->id }}">{{ $senderProfile->profile_name }}</option>
            @empty
                <option value="">{{ __('Sender Profile not available') }}</option>
            @endforelse
        </select>

    </div>
    <div class="mb-3">
        <button type="submit" class="btn btn-primary mt-3 btn-wave waves-effect waves-light">{{ __('Update Template') }}</button>
    </div>
</form>