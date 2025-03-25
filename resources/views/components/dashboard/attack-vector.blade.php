<div class="card custom-card">
    <div class="card-header">
        <div class="card-title">Active Attack Vector</div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-lg-3">
                <a aria-label="anchor" href="{{ route('campaigns') }}"
                    class="btn btn-danger-light border-0 px-4 py-3 lh-1 rounded"> <i
                        class="bi bi-envelope-open fs-22"></i> </a>
                <p class="mb-0 fs-12 text-muted text-truncate text-center">Phishing</p>
            </div>
            <div class="col-lg-3">
                <a aria-label="anchor" href="{{ route('whatsapp.campaign') }}"
                    class="btn btn-success-light border-0 px-4 py-3 lh-1 rounded"> <i
                        class="bi bi-whatsapp fs-22"></i> </a>
                <p class="mb-0 fs-12 text-muted text-truncate text-center">WhatsApp</p>
            </div>
            <div class="col-lg-3 mb-2">
                <a aria-label="anchor" href="{{ route('quishing.index') }}"
                class="btn btn-danger-light border-0 px-4 py-3 lh-1 rounded"> <i class="bx bx-qr fs-22"></i> </a>
                <p class="mb-0 fs-12 text-muted text-truncate text-center">Quishing</p>
            </div>
            @if($activeTprm)
            <div class="col-lg-3 mb-2">
                <a aria-label="anchor" href="{{ route('campaign.tprm') }}"
                class="btn btn-warning-light border-0 px-4 py-3 lh-1 rounded"> <i class="bx bx-shape-circle fs-22"></i> </a>
                <p class="mb-0 fs-12 text-muted text-truncate text-center">TPRM</p>
            </div>
            @endif
            @if($activeAIVishing)
            <div class="col-lg-3 mb-2">
                <a aria-label="anchor" href="{{ route('ai.calling') }}"
                class="btn btn-primary-light border-0 px-4 py-3 lh-1 rounded"> <i class="bx bx-phone-call fs-22"></i> </a>
                <p class="mb-0 fs-12 text-muted text-truncate text-center">AI Vishing</p>
            </div>
            @endif
        </div>
    </div>
</div>