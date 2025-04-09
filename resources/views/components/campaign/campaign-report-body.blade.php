<div class="card custom-card">

    <div   class="card-body">
<div style="display: flex; justify-content: space-between">

<div>

        <ul class="nav nav-pills nav-style-3 mb-3" role="tablist">
            <li class="nav-item" role="presentation" id="phishing_tab">
                <a class="nav-link" data-bs-toggle="tab" role="tab" aria-current="page"
                    href="#phishing_campaign" aria-selected="true">{{ __('Phishing Campaign') }}</a>
            </li>
            <li class="nav-item" role="presentation" id="training_tab">
                <a class="nav-link" data-bs-toggle="tab" role="tab" aria-current="page"
                    href="#training_campaign" aria-selected="false" tabindex="-1">{{ __('Training Campaign') }}</a>
            </li>
            <li class="nav-item" role="presentation" id="game_tab">
                <a class="nav-link" data-bs-toggle="tab" role="tab" aria-current="page"
                    href="#game_training" aria-selected="false" tabindex="-1">{{ __('Game Progress') }}</a>
            </li>
        </ul>
</div>
<div>
    {{-- <a href="{{ route('download-pdf') }}">
        <button class="btn btn-primary">View PDF</button>
    </a> --}}
</div>
</div>

        <div class="tab-content">
            <div class="tab-pane text-muted" id="phishing_campaign" role="tabpanel">
                <div class="table-responsive">
                    <table class="table text-nowrap table-striped">
                        <thead>
                            <tr>
                                <th scope="col">{{ __('Campaign name') }}</th>
                                <th scope="col">{{ __('Status') }}</th>
                                <th scope="col">{{ __('Employees') }}</th>
                                <th scope="col">{{ __('Emails Delivered') }}</th>
                                <th scope="col">{{ __('Emails Viewed') }}</th>
                                <th scope="col">{{ __('Payloads Clicked') }}</th>
                                <th scope="col">{{ __('Employees Compromised') }}</th>
                                <th scope="col">{{ __('Emails Reported') }}</th>
                            </tr>
                        </thead>
                        <tbody id="campReportStatus">
                        </tbody>
                    </table>
                </div>

                <hr>

                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">{{ __('Phishing Campaign Statistics') }}</div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="file-export" class="table table-bordered text-nowrap w-100 table-hover">
                                <thead>
                                    <tr>
                                        <th>{{ __('Employee Name') }}</th>
                                        <th>{{ __('Email Address') }}</th>
                                        <th>{{ __('Email Delivery') }}</th>
                                        <th>{{ __('Email Viewed') }}</th>
                                        <th>{{ __('Payload Clicked') }}</th>
                                        <th>{{ __('Employee Compromised') }}</th>
                                        <th>{{ __('Email Reported') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="campReportsIndividual">

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="tab-pane text-muted" id="training_campaign" role="tabpanel">
                <div class="table-responsive">
                    <table class="table text-nowrap table-striped">
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
                        <tbody id="trainingReportStatus">
                        </tbody>
                    </table>
                </div>

                <hr>

                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">{{ __('Training Campaign Statistics') }}</div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="file-export2" class="table table-bordered text-nowrap w-100">
                                <thead>
                                    <tr>
                                        <th>{{ __('Employee Name') }}</th>
                                        <th>{{ __('Email Address') }}</th>
                                        <th>{{ __('Training Module') }}</th>
                                        <th>{{ __('Date Assigned') }}</th>
                                        <th>{{ __('Score') }}</th>
                                        <th>{{ __('Passing Score') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="trainingReportsIndividual">

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="tab-pane text-muted" id="game_training" role="tabpanel">
                <div class="table-responsive">
                    <table class="table text-nowrap table-striped">
                        <thead>
                            <tr>
                                <th scope="col">{{ __('Campaign name') }}</th>
                                <th scope="col">{{ __('Total Employees Played') }}</th>
                                <th scope="col">{{ __('Total Assigned') }}</th>
                                <th scope="col">{{ __('Game Completed') }}</th>
                            </tr>
                        </thead>
                        <tbody id="gameReportStatus">
                        </tbody>
                    </table>
                </div>

                <hr>

                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">{{ __('Game Statistics') }}</div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="file-export3" class="table table-bordered text-nowrap w-100">
                                <thead>
                                    <tr>
                                        <th>{{ __('Employee Name') }}</th>
                                        <th>{{ __('Email Address') }}</th>
                                        <th>{{ __('Game') }}</th>
                                        <th>{{ __('Date Assigned') }}</th>
                                        <th>{{ __('Score') }}</th>
                                        <th>{{ __('Play Time') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="gameReportsIndividual">

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>