<div class="col-xxl-4">
    <div class="card custom-card shadow">
        <div class="card-header align-items-center">

            <div class="flex-fill">

                <div class="fw-semibold fs-20 text-center">
                    {{ $employee->userData->user_name ?? '' }}
                </div>
                <span class="text-muted text-center d-block fs-12">{{ $employee->userData->user_email ?? '' }}</span>
            </div>

        </div>

        <div class="card-body">
            <ul class="nav nav-tabs gap-2 mb-3 nav-justified nav-style-1 d-sm-flex d-block" role="tablist">
                @forelse($breachDetail as $key => $value)
                    <li class="nav-item {{ $loop->first ? 'active' : '' }}" role="presentation">
                        <a class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab" role="tab"
                            href="#{{ $value['Name'] }}" aria-selected="false" tabindex="-1">{{ $value['Name'] }}</a>
                    </li>
                @empty
                @endforelse


            </ul>
            <div class="tab-content">
                @forelse($breachDetail as $key => $value)
                    <div class="tab-pane text-muted {{ $loop->first ? 'active show' : '' }}" id="{{ $value['Name'] }}"
                        role="tabpanel">
                        <div>
                            {!! $value['Description'] !!}
                        </div>


                        <div class="card-footer p-0 pt-2 mt-2 d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted fs-11 d-block">Breach Date :</span>
                                <span class="fw-semibold d-block">{{ $value['BreachDate'] }}</span>
                            </div>
                            <div class="text-end">
                                <span class="text-muted fs-11 d-block">Modified Date :</span>
                                <span
                                    class="fw-semibold d-block">{{ \Carbon\Carbon::parse($value['ModifiedDate'])->format('Y-m-d') }}</span>
                            </div>
                        </div>
                    </div>
                @empty
                @endforelse

            </div>
        </div>

        {{-- <div class="card-footer d-flex align-items-center justify-content-between">
            <div>
                <span class="text-muted fs-11 d-block">Assigned Date :</span>
                <span class="fw-semibold d-block">24,May 2023</span>
            </div>
            <div class="text-end">
                <span class="text-muted fs-11 d-block">Due Date :</span>
                <span class="fw-semibold d-block">12,Jul 2023</span>
            </div>
        </div> --}}
    </div>
</div>
