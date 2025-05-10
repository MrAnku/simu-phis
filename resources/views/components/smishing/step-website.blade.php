<fieldset class="included" style="display: block; opacity: 1;">
    <div class="text-center py-2">
        <button type="button" class="btn btn-dark label-btn label-end stickyBtn rounded-pill previous" onclick="showPrevious('stepTwo')">
            {{ __('Previous') }}
            <i class="ri-arrow-left-line label-btn-icon ms-2 rounded-pill"></i>
        </button>
        <button type="button" class="btn btn-info label-btn label-end stickyBtn rounded-pill next" onclick="showNext('stepWebsite')">
            {{ __('Next') }}
            <i class="ri-arrow-right-line label-btn-icon ms-2 rounded-pill"></i>
        </button>
    </div>

    <div class="form-card">

        <div class="d-flex gap-2 justify-content-between">
           
    
            <div>
                <div class="input-group input-group-sm mb-3">
                    <span class="input-group-text">{{ __('Search:') }} </span>
                    <input type="text" class="form-control" id="t_moduleSearch" placeholder="{{ __('Search website') }}">
                </div>
    
               
            </div>
        </div>
    
        <div class="row" id="trainingModulesCampModal">
            @forelse ($websites as $module)
                @php
                    $template = asset('storage/uploads/phishingMaterial/phishing_websites/' . $module->file);
                @endphp
                <div class="col-lg-6 t_modules">
                    <div class="card custom-card border">
                        <div class="card-header">
                            <div class="d-flex align-items-center w-100">
                                <div class="">
                                    <div class="fs-15 fw-semibold">{{ $module->name }}
                                        {{ $module->company_id == 'default' ? '(Default)' : '' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body htmlPhishingGrid" style="
                        height: 300px;
                    ">
                            <iframe style="
                            width: 100%;
                            height: 100%;
                        " class="phishing-iframe" src="{{ $template }}"></iframe>
                        </div>
                        <div class="card-footer">
                            <div class="d-flex justify-content-center">
                                <div class="fs-semibold fs-14">
                                    <input 
                                    type="radio" 
                                    name="website" 
                                    onclick="selectWebsite(this)"
                                    onblur="deselectWebsite(this)"
                                    data-name="{{ $module->name }}"
                                    value="{{ $module->id }}" 
                                    class="btn-check " 
                                    id="website{{ $module->id }}">
                                    
                                    <label class="btn btn-outline-primary mb-3" for="website{{ $module->id }}">{{ __('Select Website') }}</label>
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


