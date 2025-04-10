<fieldset class="included" style="display: block; opacity: 1;">
    <div class="text-center py-2">
        <button type="button" class="btn btn-dark label-btn label-end stickyBtn rounded-pill previous" onclick="showPrevious('stepThree')">
            {{ __('Previous') }}
            <i class="ri-arrow-left-line label-btn-icon ms-2 rounded-pill"></i>
        </button>
        <button type="button" class="btn btn-info label-btn label-end stickyBtn rounded-pill next" onclick="showNext('stepThree')">
            {{ __('Next') }}
            <i class="ri-arrow-right-line label-btn-icon ms-2 rounded-pill"></i>
        </button>
    </div>

    <div class="form-card">

        <div class="d-flex gap-2 justify-content-between">
            <div class="d-flex gap-2">
                <div>
                    <div class="input-group input-group-sm mb-3">
    
                        <label for="input-label" class="input-group-text">{{ __('Language:') }}</label>
    
                        <x-language-select id="training_lang" />
                    </div>
                    <div>
                        <div class="input-group input-group-sm mb-3">
                            <span class="input-group-text">{{ __('Days Until Due:') }}</span>
                            <input type="number" data-name="Days Until Due" name="days_until_due" class="form-control" id="days_until_due"
                                value="14">
                        </div>
                    </div>
                </div>
                <div>
                    <div class="input-group input-group-sm mb-3">
                        <label class="input-group-text" for="training_type">{{ __('Training Type:') }}</label>
                        <select class="form-select" id="training_type" data-name="Training Type">
                            <option value="static_training" selected>{{ __('Static Training') }}</option>
                            <option value="ai_training">{{ __('AI Training') }}</option>
                            <option value="gamified">{{ __('Gamified Training') }}</option>
                        </select>
                    </div>
    
                    <div class="input-group input-group-sm mb-3">
                        <label class="input-group-text" for="training_assignment">{{ __('Assignments:') }}</label>
                        <select class="form-select" id="training_assignment" data-name="Training Assignment">
                            <option value="random" selected>{{ __('Intelligently assign one of the selected trainings.') }}</option>
                            <option value="all">{{ __('Assign all of the selected trainings.') }}</option>
                        </select>
                    </div>
    
    
                </div>
    
    
            </div>
    
            <div>
                <div class="input-group input-group-sm mb-3">
                    <span class="input-group-text">{{ __('Search:') }} </span>
                    <input type="text" class="form-control" id="t_moduleSearch" placeholder="{{ __('Search template') }}">
                </div>
    
                <div class="input-group input-group-sm mb-3">
                    <label class="input-group-text" for="training_cat">{{ __('Category:') }}</label>
                    <select class="form-select" id="training_cat" data-name="Training Category">
                        <option value="all" selected>{{ __('All') }}</option>
                        <option value="international">{{ __('International') }}</option>
                        <option value="middle_east">{{ __('Middle East') }}</option>
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
                    <div class="card custom-card border">
                        <div class="card-header">
                            <div class="d-flex align-items-center w-100">
                                <div class="">
                                    <div class="fs-15 fw-semibold">{{ $module->name }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body htmlPhishingGrid">
                            <img class="trainingCoverImg" src="{{ $coverImgPath }}" style="
                            width: 100%;
                        "/>
                        </div>
                        <div class="card-footer">
                            <div class="d-flex justify-content-center">
                                <div class="fs-semibold fs-14">
                                    <input 
                                    type="checkbox" 
                                    name="training_module" 
                                    onclick="selectTrainingModule(this)" 
                                    data-name="{{ $module->name }}"
                                    value="{{ $module->id }}" 
                                    class="btn-check " 
                                    id="training{{ $module->id }}">
                                    
                                    <label class="btn btn-outline-primary mb-3" for="training{{ $module->id }}">{{ __('Select this training') }}</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <p>{{ __('No training modules available.') }}</p>
            @endforelse
    
        </div>
        <div class="d-flex justify-content-center">
            <button type="button" onclick="loadMoreTrainings(this)" class="btn btn-primary btn-sm btn-wave">{{ __('Show More') }}</button>
        </div>
    </div>
</fieldset>


