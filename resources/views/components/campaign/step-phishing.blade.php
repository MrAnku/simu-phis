<div class="form-card">

    <div class="d-flex justify-content-between">
        <div>
            <label for="input-label" class="form-label">Email Language</label>

            <x-language-select id="email_lang" />
        </div>

        <div>

            <label for="templateSearch" class="form-label">Search</label>
            <input type="text" class="form-control" id="templateSearch" placeholder="Search template">

        </div>
    </div>

    <div class="row">
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
                                    data-phishMatName="{{ $email->name }}" 
                                    id="pm{{ $email->id }}" 
                                    value="{{ $email->id }}"
                                >
                                <label class="btn btn-outline-primary mb-3" for="pm{{ $email->id }}">Select this attack</label>

                                
                                {{-- <input type="radio" name="phish_material" data-phishMatName="{{ $email->name }}"
                                    value="{{ $email->id }}" class="btn-check" id="pm{{ $email->id }}">
                                <label class="btn btn-outline-primary mb-3" for="pm{{ $email->id }}">Select this
                                    attack</label> --}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <p>No phishing emails available.</p>
        @endforelse

    </div>
</div>
