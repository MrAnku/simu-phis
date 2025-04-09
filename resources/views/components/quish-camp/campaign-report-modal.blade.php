<div class="card-body">
    <ul class="nav nav-pills justify-content-start nav-style-3 mb-3" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link active" id="quishing-detail" data-bs-toggle="tab" role="tab" aria-current="page" href="#home-right"
                aria-selected="true">{{ __('Quishing') }}</a>
        </li>
        <li class="nav-item quishing-training-detail" role="presentation" style="display: none;">
            <a class="nav-link" data-bs-toggle="tab" role="tab" aria-current="page" href="#about-right"
                aria-selected="false" tabindex="-1">{{ __('Training') }}</a>
        </li>

    </ul>
    <div class="tab-content">
        <div class="tab-pane text-muted active show" id="home-right" role="tabpanel">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        {{ __('Quishing Campaign Details') }}
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table text-nowrap">
                            <thead>
                                <tr>
                                    <th scope="col">{{ __('Campaign name') }}</th>
                                    <th scope="col">{{ __('Status') }}</th>
                                    <th scope="col">{{ __('Employees') }}</th>
                                    <th scope="col">{{ __('Emails Delivered') }}</th>
                                    <th scope="col">{{ __('Emails Viewed') }}</th>
                                    <th scope="col">{{ __('QR Scanned') }}</th>
                                    <th scope="col">{{ __('Employees Compromised') }}</th>
                                    <th scope="col">{{ __('Emails Reported') }}</th>
                                </tr>
                            </thead>
                            <tbody id="qcampdetail">
                                
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        {{ __('Target Employees Interaction') }}
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table text-nowrap">
                            <thead>
                                <tr>
                                    <th>{{ __('Employee Name') }}</th>
                                    <th>{{ __('Email Address') }}</th>
                                    <th>{{ __('Email Delivery') }}</th>
                                    <th>{{ __('Email Viewed') }}</th>
                                    <th>{{ __('QR Scanned') }}</th>
                                    <th>{{ __('Employee Compromised') }}</th>
                                    <th>{{ __('Email Reported') }}</th>
                                </tr>
                            </thead>
                            <tbody id="qcampdetailLive">
                                
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-pane text-muted" id="about-right" role="tabpanel">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        {{ __('Quishing Training Details') }}
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table text-nowrap">
                            <thead>
                                <tr>
                                    <th scope="col">{{ __('Campaign name') }}</th>
                                    <th scope="col">{{ __('Status') }}</th>
                                    <th scope="col">{{ __('Employees') }}</th>
                                    <th scope="col">{{ __('Trainings Assigned') }}</th>
                                    <th scope="col">{{ __('Trainings Type') }}</th>
                                    <th scope="col">{{ __('Trainings Language') }}</th>
                                    <th scope="col">{{ __('Trainings Completed') }}</th>
                                </tr>
                            </thead>
                            <tbody id="qcampTrainingData">
                                <tr>
                                    <th scope="row">{{ __('Mark') }}</th>
                                    <td>21,Dec 2021</td>
                                    <td>+1234-12340</td>
                                    <td>+1234-12340</td>
                                    <td>+1234-12340</td>
                                    <td>+1234-12340</td>
                                    <td>+1234-12340</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        {{ __('Target Employees Progress') }}
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table text-nowrap">
                            <thead>
                                <tr>
                                    <th>{{ __('Employee Name') }}</th>
                                    <th>{{ __('Email Address') }}</th>
                                    <th>{{ __('Training Module') }}</th>
                                    <th>{{ __('Date Assigned') }}</th>
                                    <th>{{ __('Score') }}</th>
                                    <th>{{ __('Passing Score') }}</th>
                                    <th>{{ __('Status') }}</th>
                                </tr>
                            </thead>
                            <tbody id="qcampTrainingDataLive">
                                
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
