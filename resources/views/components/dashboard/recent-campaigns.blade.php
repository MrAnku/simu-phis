<div class="card custom-card">
    <div class="card-header  justify-content-between">
        <div class="card-title">
            {{ __('Recent Campaigns') }}
        </div>

    </div>
    <div class="card-body">
        <ul class="list-unstyled crm-top-deals mb-0">
            @forelse ($recentSixCampaigns as $camp)
                <li>
                    <div class="d-flex align-items-top flex-wrap">
                        <div class="me-2">
                            <span class="avatar avatar-sm avatar-rounded">
                                <img src="https://cdn-icons-png.freepik.com/512/3122/3122573.png"
                                    alt="">
                            </span>
                        </div>
                        <div class="flex-fill">
                            <p class="fw-semibold mb-0">{{ $camp->campaign_name }}</p>
                            <span class="text-muted fs-12">{{ $camp->campaign_type }}</span>
                        </div>
                        <div class="fw-semibold fs-15">{{ $camp->status }}</div>
                    </div>
                </li>
            @empty
            @endforelse

        </ul>
    </div>
</div>