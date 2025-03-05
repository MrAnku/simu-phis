<fieldset class="included" id="pm_step_form" style="display: block; opacity: 1;">
    <div class="text-center py-2">
        <button type="button" class="btn btn-dark label-btn label-end stickyBtn rounded-pill previous" onclick="showPrevious('stepTwo')">
            Previous
            <i class="ri-arrow-left-line label-btn-icon ms-2 rounded-pill"></i>
        </button>
        <button type="button" class="btn btn-info label-btn label-end stickyBtn rounded-pill next" onclick="showNext('stepTwo')">
            Next
            <i class="ri-arrow-right-line label-btn-icon ms-2 rounded-pill"></i>
        </button>
    </div>

    <div class="form-card">

        <div class="d-flex justify-content-between">
            <div>
                <label for="input-label" class="form-label">Email Language</label>
                <x-language-select id="quishing_lang"/>
            </div>

            <div>

                <label for="templateSearch" class="form-label">Search</label>
                <div class="d-flex gap-2 align-items-center">
                    <input type="text" class="form-control" id="templateSearch" placeholder="Search template">
                    <div class="spinner-border spinner-border-sm me-4" role="status" id="phishEmailSearchSpinner" style="display: none;">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
    
            </div>
        </div>

        <div class="row" id="phishingEmailsCampModal">
            @forelse($quishingEmails as $email)
                <div class="col-lg-6 email_templates">
                    <div class="card custom-card border my-2">
                        <div class="card-header">
                            <div class="d-flex align-items-center w-100">
                                <div class="">
                                    <div class="fs-15 fw-semibold">
                                        {{ $email->name }} {{ $email->company_id == 'default' ? '(Default)' : '' }}

                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body htmlPhishingGrid" style="background: white;">
                            <iframe class="phishing-iframe" src="{{ Storage::url($email->file) }}"
                                style="width: 100%;
                                height: 300px;
                            "></iframe>
                        </div>
                        <div class="card-footer">
                            <div class="d-flex justify-content-center">
                                <div>
                                    <button type="button"
                                        onclick="showMaterialDetails(this, '{{ $email->name }}', '{{ $email->subject }}', '{{ $email->website }}', '{{ $email->sender_profile }}')"
                                        class="btn btn-outline-primary btn-wave waves-effect waves-light mx-2">
                                        View
                                    </button>
                                </div>
                                <div class="fs-semibold fs-14">
                                    <input type="checkbox" 
                                    name="quish_material"    
                                    class="btn-check"
                                    onclick="selectPhishingMaterial(this)" 
                                    data-name="{{ $email->name }}"
                                    id="pm{{ $email->id }}" 
                                    value="{{ $email->id }}">

                                    <label class="btn btn-outline-primary mb-3" for="pm{{ $email->id }}">Select this attack</label>


                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-lg-12">
                    <div>
                        No quishing emails found.
                    </div>
                </div>
            @endforelse


        </div>
        <div class="d-flex justify-content-center mt-2">
            <button type="button" onclick="loadMoreQuishingEmails(this)"
                class="btn btn-primary btn-sm btn-wave waves-effect waves-light">Show More</button>
        </div>
    </div>
</fieldset>


