<div class="card custom-card">
    <div class="card-header">
        <div class="card-title">Package</div>
    </div>
    <div class="card-body">

        <div class="d-flex align-items-center justify-content-between mb-0">
            <div>

                <p class="mb-0 fs-25 fw-semibold">{{ $package['total_emp'] }} of
                    {{ $package['alloted_emp'] }} <span
                        class="text-muted fs-11">{{ round($package['used_percent'], 2) }}% of total
                        used</span>
                </p>
                <span class="text-muted fs-12">Employees</span>
            </div>
            <div>
                <span class="avatar bg-warning">
                    <i class="ri-team-line fs-18"></i>
                </span>
            </div>
        </div>
        <div class="d-flex align-items-center my-2">
            <div class="flex-fill">
                <div class="progress progress-xs">
                    <div class="progress-bar bg-indigo" role="progressbar"
                        style="width: {{ $package['used_percent'] }}%"
                        aria-valuenow="{{ $package['used_percent'] }}" aria-valuemin="0"
                        aria-valuemax="100"></div>
                </div>
                <div class="text-end">

                    {{-- @if ($package['used_percent'] >= 80) --}}

                    @if ($upgrade['upgrade_req'])
                        <button
                            class="btn btn-warning btn-sm btn-wave waves-effect waves-light mt-3">Upgrade
                            request is pending</button>
                    @else
                        <a href="#" data-bs-toggle="modal" data-bs-target="#upgradeModal"
                            class="btn btn-success btn-sm btn-wave waves-effect waves-light mt-3">Upgrade</a>
                    @endif



                    {{-- @endif --}}
                </div>
            </div>


        </div>
    </div>
</div>