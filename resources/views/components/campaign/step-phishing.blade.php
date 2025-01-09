<div class="form-card">

    <div class="d-flex justify-content-between">
        <div>
            <label for="input-label" class="form-label">Email Language</label>

            <x-language-select id="email_lang" />
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
        @forelse ($phishingEmails as $email)
            @php
                $isDefault = $email->company_id == 'default' ? '(Default)' : '';
                $template = asset('storage') . '/' . $email->mailBodyFilePath;
            @endphp
            <div class="col-lg-6 email_templates">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="d-flex align-items-center w-100">
                            <div class="">
                                <div class="fs-15 fw-semibold">{{ $email->name }}
                                    {{ $isDefault }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body htmlPhishingGrid" style="background: white;">
                        <iframe class="phishing-iframe" src="{{ $template }}"></iframe>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-center">
                            <div>
                                <button type="button"
                                    onclick="showMaterialDetails(this, '{{ $email->name }}', '{{ $email->email_subject }}', '{{ $email->website }}', '{{ $email->senderProfile }}')"
                                    class="btn btn-outline-primary btn-wave waves-effect waves-light mx-2">
                                    View
                                </button>
                            </div>
                            <div class="fs-semibold fs-14">
                                <input 
                                    type="checkbox" 
                                    name="phish_material" 
                                    class="btn-check"
                                    onclick="selectPhishingMaterial(this)" 
                                    data-phishMatName="{{ $email->name }}" 
                                    id="pm{{ $email->id }}" 
                                    value="{{ $email->id }}"
                                >
                                <label class="btn btn-outline-primary mb-3" for="pm{{ $email->id }}">Select this attack</label>

                                
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <p>No phishing emails available.</p>
        @endforelse

    </div>
    <div class="d-flex justify-content-center">
        <button type="button" onclick="loadMorePhishingEmails(this)" class="btn btn-primary btn-sm btn-wave">Show More</button>
    </div>
</div>
