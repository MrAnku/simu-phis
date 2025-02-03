<form action="{{ route('addEmailTemplate') }}" method="post" enctype="multipart/form-data">
    @csrf
    <div class="mb-3">
        <label for="input-label" class="form-label">Email Template Name<sup
                class="text-danger">*</sup></label>
        <input type="text" class="form-control" name="eTempName" placeholder="Template name"
            required>

    </div>
    <div class="mb-3">
        <label for="input-label" class="form-label">Email Subject<sup
                class="text-danger">*</sup></label>
        <input type="text" class="form-control" name="eSubject"
            placeholder="i.e. Reset your password" required>

    </div>
    <div class="mb-3">
        <label for="input-label" class="form-label">Difficulty</label>
        <select class="form-select" name="difficulty" aria-label="Default select example">
            <option value="easy" selected>Easy</option>
            <option value="medium">Medium</option>
            <option value="hard">Hard</option>
        </select>
    </div>
    <div class="mb-3">
        <label for="input-label" class="form-label">Associated Website<sup
                class="text-danger">*</sup></label>
        <select class="form-select" name="eAssoWebsite" required>

            @forelse ($phishingWebsites as $phishingWebsite)
                <option value="{{ $phishingWebsite->id }}">{{ $phishingWebsite->name }}</option>
            @empty
                <option value="">Websites not available</option>
            @endforelse

        </select>

    </div>
    <div class="mb-3">
        <label for="input-label" class="form-label">Sender Profile<sup
                class="text-danger">*</sup></label>
        <select class="form-select" name="eSenderProfile" required>

            @forelse ($senderProfiles as $senderProfile)
                <option value="{{ $senderProfile->id }}">{{ $senderProfile->profile_name }}</option>
            @empty
                <option value="">Sender Profile not available</option>
            @endforelse


        </select>

    </div>
    <div class="my-3">
        <label for="formFile" class="form-label">Email Template File<sup
                class="text-danger">*</sup></label>
        <input class="form-control" type="file" name="eMailFile" accept=".html" required>
        <div class="form-text my-3">
            Don't forget to add the shortcodes <code>@{{user_name}}</code>,
            <code>@{{tracker_img}}</code> and <code>@{{website_url}}</code> in the Email
            Template File.
            <br>
            Tutorial Video <a href="https://youtube.com">Watch Now</a>
        </div>
    </div>

    <div class="mb-3">
        <button type="submit" class="btn btn-primary mt-3 btn-wave waves-effect waves-light">Add
            Template</button>
    </div>
</form>