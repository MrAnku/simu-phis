<div class="form-card">

    <div class="d-flex justify-content-between">
        <div class="d-flex gap-2">
            <div>
                <label for="input-label" class="form-label">Language</label>

                <x-language-select id="training_lang" />
            </div>
            <div>
                <label for="input-label" class="form-label">Training Type</label>

                <select class="form-select" id="training_type">

                    <option value="static_training">Static Training</option>
                    <option value="ai_training">AI Training</option>
                </select>
            </div>

        </div>

        <div>

            <label for="t_moduleSearch" class="form-label">Search</label>
            <input type="text" class="form-control" id="t_moduleSearch"
                placeholder="Search template">

        </div>
    </div>

    <div class="row">
        @forelse ($trainingModules as $module)
            @php
                $coverImgPath = asset(
                    'storage/uploads/trainingModule/' . $module->cover_image,
                );
            @endphp
            <div class="col-lg-6 t_modules">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="d-flex align-items-center w-100">
                            <div class="">
                                <div class="fs-15 fw-semibold">{{ $module->name }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body htmlPhishingGrid">
                        <img class="trainingCoverImg"
                            src="{{ $coverImgPath }}" />
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-center">
                            <div class="fs-semibold fs-14">
                                <input type="radio" name="training_module"
                                    data-trainingName="{{ $module->name }}"
                                    value="{{ $module->id }}" class="btn-check"
                                    id="training{{ $module->id }}">
                                <label class="btn btn-outline-primary mb-3"
                                    for="training{{ $module->id }}">Select this
                                    training</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <p>No training modules available.</p>
        @endforelse

    </div>
</div>