<div class="form-card">

    <div class="d-flex gap-2 justify-content-between">
        <div class="d-flex gap-2">
            <div>
                <div class="input-group input-group-sm mb-3">

                    <label for="input-label" class="input-group-text">Language:</label>

                    <x-language-select id="training_lang" />
                </div>
                <div>
                    <div class="input-group input-group-sm mb-3">
                        <span class="input-group-text">Days Until Due:</span>
                        <input type="number" name="days_until_due" class="form-control" id="days_until_due"
                            value="14">
                    </div>
                </div>
            </div>
            <div>
                <div class="input-group input-group-sm mb-3">
                    <label class="input-group-text" for="training_type">Training Type:</label>
                    <select class="form-select" id="training_type">
                        <option value="static_training" selected>Static Training</option>
                        <option value="ai_training">AI Training</option>
                        <option value="gamified">Gamified Training</option>
                        <option value="games">Games</option>
                    </select>
                </div>

                <div class="input-group input-group-sm mb-3">
                    <label class="input-group-text" for="training_assignment">Assignments:</label>
                    <select class="form-select" id="training_assignment">
                        <option value="random" selected>Intelligently assign one of the selected trainings.</option>
                        <option value="all">Assign all of the selected trainings.</option>
                    </select>
                </div>


            </div>


        </div>

        <div>
            <div class="input-group input-group-sm mb-3">
                <span class="input-group-text">Search: </span>
                <input type="text" class="form-control" id="t_moduleSearch" placeholder="Search template">
            </div>

            <div class="input-group input-group-sm mb-3" id="training_cat_container">
                <label class="input-group-text" for="training_cat">Category:</label>
                <select class="form-select" id="training_cat">
                    <option value="all" selected>All</option>
                    <option value="international">International</option>
                    <option value="middle_east">Middle East</option>
                </select>
            </div>
        </div>
    </div>

    <div class="row" id="trainingModulesCampModal">
        @forelse ($trainingModules as $module)
            @php
                $coverImgPath = asset('storage/uploads/trainingModule/' . $module->cover_image);
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
                    <div class="card-body htmlPhishingGrid" style="width: 100%; height: 300px;">
                        <img class="trainingCoverImg" src="{{ $coverImgPath }}" />
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-center">
                            <div class="fs-semibold fs-14">
                                <input type="checkbox" name="training_module" onclick="selectTrainingModule(this)" data-trainingName="{{ $module->name }}"
                                    value="{{ $module->id }}" class="btn-check" id="training{{ $module->id }}">
                                <label class="btn btn-outline-primary mb-3" for="training{{ $module->id }}">Select
                                    this
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
    <div class="d-flex justify-content-center">
        <button type="button" onclick="loadMoreTrainings(this)" class="btn btn-primary btn-sm btn-wave">Show More</button>
    </div>
</div>
