<div class="card custom-card">
    <div class="card-header justify-content-between">
        <div class="card-title">
            {{ __('Employee compromised') }}
        </div>

    </div>
    <div class="card-body">
        <div class="d-flex align-items-center mb-3">
            <h4 class="fw-bold mb-0">{{ $totalEmpCompromised ?? 0 }}</h4>
            <div class="ms-2">

                <span class="text-muted ms-1">Employees compromised</span>
            </div>
        </div>

        <ul class="list-unstyled mb-0 pt-2 crm-deals-status">
            @forelse ($campaignsWithReport as $r)
                <li class="primary">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>{{ $r->campaign_name }}</div>
                        <div class="fs-12 text-muted">{{ $r->emp_compromised }}</div>
                    </div>
                </li>
            @empty
            @endforelse

        </ul>
    </div>
</div>