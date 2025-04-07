<div class="card custom-card overflow-hidden">
    <div class="card-header justify-content-between">
        <div class="card-title">{{ __('Simulation Statics') }}</div>

    </div>
    <div class="card-body p-0">
        <div class="row border-bottom border-block-end-dashed">
            <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-4 col-sm-4 col-12">
                <div
                    class="p-3 border-sm-end border-inline-end-dashed text-sm-start text-center">
                    <p class="fs-20 fw-semibold mb-0">
                        {{ $sdata['waSimuCount'] + $sdata['phishSimuCount'] }}</p>
                    <p class="mb-0 text-muted">{{ __('Total Simulation') }}</p>
                </div>
            </div>
            <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-4 col-sm-4 col-12">
                <div
                    class="p-3 border-sm-end border-inline-end-dashed text-sm-start text-center">
                    <p class="fs-20 fw-semibold mb-0"><span
                            class="basic-subscription">{{ $sdata['waSimuCount'] }}</span></p>
                    <p class="mb-0 text-muted">{{ __('WhatsApp Simulation') }}</p>
                </div>
            </div>
            <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-4 col-sm-4 col-12">
                <div class="p-3 text-sm-start text-center">
                    <p class="fs-20 fw-semibold mb-0"><span
                            class="pro-subscription">{{ $sdata['phishSimuCount'] }}</span></p>
                    <p class="mb-0 text-muted">{{ __('Phishing Simulation') }}</p>
                </div>
            </div>
        </div>
        <div id="subscriptionOverview" class="px-3 mt-sm-0 mt-3"></div>
    </div>
</div>