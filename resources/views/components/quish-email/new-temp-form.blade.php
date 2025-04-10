<form action="{{ route('quishing.emails.add') }}" method="post" enctype="multipart/form-data">
    @csrf
    <div class="mb-3">
        <label for="input-label" class="form-label">{{ __('Email Template Name') }}<sup
                class="text-danger">*</sup></label>
        <input type="text" class="form-control" name="template_name" placeholder="{{ __('Template name') }}"
            required>

    </div>
    <div class="mb-3">
        <label for="input-label" class="form-label">{{ __('Email Subject') }}<sup
                class="text-danger">*</sup></label>
        <input type="text" class="form-control" name="template_subject"
            placeholder="{{ __('i.e. Reset your password') }}" required>

    </div>
    <div class="mb-3">
        <label for="input-label" class="form-label">{{ __('Difficulty') }}</label>
        <select class="form-select" name="difficulty" aria-label="Default select example">
            <option value="easy" selected>{{ __('Easy') }}</option>
            <option value="medium">{{ __('Medium') }}</option>
            <option value="hard">{{ __('Hard') }}</option>
        </select>
    </div>
    <div class="mb-3">
        <label for="input-label" class="form-label">{{ __('Associated Website') }}<sup
                class="text-danger">*</sup></label>
        <select class="form-select" name="associated_website" required>

            @forelse ($phishingWebsites as $phishingWebsite)
                <option value="{{ $phishingWebsite->id }}">{{ $phishingWebsite->name }}</option>
            @empty
                <option value="">{{ __('Websites not available') }}</option>
            @endforelse

        </select>

    </div>
    <div class="mb-3">
        <label for="input-label" class="form-label">{{ __('Sender Profile') }}<sup
                class="text-danger">*</sup></label>
        <select class="form-select" name="sender_profile" required>

            @forelse ($senderProfiles as $senderProfile)
                <option value="{{ $senderProfile->id }}">{{ $senderProfile->profile_name }}</option>
            @empty
                <option value="">{{ __('Sender Profile not available') }}</option>
            @endforelse


        </select>

    </div>
    <div class="my-3">
        <label for="formFile" class="form-label">{{ __('Email Template File') }}<sup
                class="text-danger">*</sup></label>
        <input class="form-control" type="file" name="template_file" accept=".html" required>
        <div class="form-text my-3">
            {{ __("Don't forget to add the shortcodes") }} <code>@{{user_name}}</code> {{ __('and') }}
            <code>@{{qr_code}}</code> {{ __('in the Email Template File.') }}
            <br>
            Tutorial Video <a href="https://youtube.com">{{ __('Watch Now') }}</a>
        </div>
    </div>

    <div class="mb-3">
        <button type="submit" class="btn btn-primary mt-3 btn-wave waves-effect waves-light">{{ __('Add Template') }}</button>
    </div>
</form>