<fieldset class="included" id="pm_step_form" style="display: block; opacity: 1;">
    <div class="text-center py-2 stickyBtn">
        <button type="button" class="btn btn-dark label-btn label-end stickyBtn rounded-pill previous" onclick="showPrevious('stepTwo')">
            {{ __('Previous') }}
            <i class="ri-arrow-left-line label-btn-icon ms-2 rounded-pill"></i>
        </button>
        <button type="button" class="btn btn-info label-btn label-end stickyBtn rounded-pill next" onclick="showNext('stepTwo')">
            {{ __('Next') }}
            <i class="ri-arrow-right-line label-btn-icon ms-2 rounded-pill"></i>
        </button>
    </div>

    <div class="form-card">

        <div class="d-flex justify-content-between">
            <div>
                <label for="input-label" class="form-label">{{ __('Smishing Language') }}</label>
                <x-language-select id="smishing_lang"/>
            </div>

            <div>

                <label for="templateSearch" class="form-label">{{ __('Search') }}</label>
                <div class="d-flex gap-2 align-items-center">
                    <input type="text" class="form-control" id="templateSearch" placeholder="{{ __('Search template') }}">
                    <div class="spinner-border spinner-border-sm me-4" role="status" id="templateSearchSpinner" style="display: none;">
                        <span class="visually-hidden">{{ __('Loading...') }}</span>
                    </div>
                </div>
    
            </div>
        </div>

        <div class="row" id="templateCampModal">
            @forelse($templates as $template)
                <div class="col-lg-6 email_templates">
                    <div class="card custom-card border my-2">
                        <div class="card-header">
                            <div class="d-flex align-items-center w-100">
                                <div class="">
                                    <div class="fs-15 fw-semibold">
                                        {{ $template->name }} {{ $template->company_id == 'default' ? '(Default)' : '' }}

                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body htmlPhishingGrid" style="background: white;">
                            <div class="card-body sms-preview-col">
                                <div class="phone-frame">
                                    <div class="status-bar">{{ now()->format('g:i A') }}</div>
                                    <div class="sms-header">5858587</div>
                                    <div class="sms-body">
                                        {{ $template->message }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="d-flex justify-content-center">
                                
                                <div class="fs-semibold fs-14">
                                    <input type="checkbox" 
                                    name="quish_material"    
                                    class="btn-check"
                                    onclick="selectPhishingMaterial(this)" 
                                    data-name="{{ $template->name }}"
                                    id="pm{{ $template->id }}" 
                                    value="{{ $template->id }}">

                                    <label class="btn btn-outline-primary mb-3" for="pm{{ $template->id }}">{{ __('Select this attack') }}</label>


                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-lg-12">
                    <div>
                        {{ __('No smishing template found.') }}
                    </div>
                </div>
            @endforelse


        </div>
        <div class="d-flex justify-content-center mt-2">
            <button type="button" onclick="loadMoreTemplates(this)"
                class="btn btn-primary btn-sm btn-wave waves-effect waves-light">{{ __('Show More') }}</button>
        </div>
    </div>
</fieldset>


