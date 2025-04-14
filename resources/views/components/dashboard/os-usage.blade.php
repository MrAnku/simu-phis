<div class="card custom-card">
    <div class="card-header  justify-content-between">
        <div class="card-title">
            {{ __('OS Usage') }}
        </div>
    </div>
    <div class="card-body">
        <div class="simplebar-content" style="padding: 0px;">
            <ul class="list-unstyled mb-0 notification-container">
                @php
                    $usageCountsSorted = collect($usageCounts)->sortDesc()->toArray();
                @endphp

                @foreach ($usageCountsSorted as $os => $count)
                    <li>
                        <div class="card custom-card un-read">
                            <div class="card-body p-3">
                                <a href="javascript:void(0);">
                                    <div class="d-flex align-items-center mt-0 flex-wrap">
                                        <div class="lh-1">
                                            <span class="avatar avatar-me me-3 avatar-rounded bg-{{ $os == 'windows' ? 'secondary' : ($os == 'mac' ? 'dark' : 'success') }}-transparent">
                                                <i class='bx bxl-{{ $os == 'mac' ? 'apple' : $os }} fs-23 text-{{ $os == 'windows' ? 'secondary' : ($os == 'mac' ? 'dark' : 'success') }}'></i>
                                            </span>
                                        </div>
                                        <div class="flex-fill">
                                            <div class="d-flex align-items-center">
                                                <div class="mt-sm-0 mt-2">
                                                    <p class="mb-0 fs-14 fw-semibold">{{ ucfirst(__($os)) }}</p>
                                                </div>
                                                <div class="ms-auto">
                                                    <span class="float-end fs-22 badge bg-light text-muted">{{ $count }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
