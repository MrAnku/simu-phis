<div class="card custom-card">

    <div   class="card-body">
<div style="display: flex; justify-content: space-between">

<div>

        <ul class="nav nav-pills nav-style-3 mb-3" role="tablist">
            <li class="nav-item" role="presentation" id="phishing_tab">
                <a class="nav-link" data-bs-toggle="tab" role="tab" aria-current="page"
                    href="#phishing_campaign" aria-selected="true">Phishing Campaign</a>
            </li>
            <li class="nav-item" role="presentation" id="training_tab">
                <a class="nav-link" data-bs-toggle="tab" role="tab" aria-current="page"
                    href="#training_campaign" aria-selected="false" tabindex="-1">Training
                    Campaign</a>
            </li>
        </ul>
</div>
<div>
    <a href="{{ route('download-pdf') }}">
        <button class="btn btn-primary">View PDF</button>
    </a>
</div>
</div>

        <div class="tab-content">
            <div class="tab-pane text-muted" id="phishing_campaign" role="tabpanel">
                <div class="table-responsive">
                    <table class="table text-nowrap table-striped">
                        <thead>
                            <tr>
                                <th scope="col">Campaign name</th>
                                <th scope="col">Status</th>
                                <th scope="col">Employees</th>
                                <th scope="col">Emails Delivered</th>
                                <th scope="col">Emails Viewed</th>
                                <th scope="col">Payloads Clicked</th>
                                <th scope="col">Employees Compromised</th>
                                <th scope="col">Emails Reported</th>
                            </tr>
                        </thead>
                        <tbody id="campReportStatus">
                        </tbody>
                    </table>
                </div>

                <hr>

                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">Phishing Campaign Statistics</div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="file-export" class="table table-bordered text-nowrap w-100 table-hover">
                                <thead>
                                    <tr>
                                        <th>Employee Name</th>
                                        <th>Email Address</th>
                                        <th>Email Delivery</th>
                                        <th>Email Viewed</th>
                                        <th>Payload Clicked</th>
                                        <th>Employee Compromised</th>
                                        <th>Email Reported</th>
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
                                <th scope="col">Campaign name</th>
                                <th scope="col">Status</th>
                                <th scope="col">Employees</th>
                                <th scope="col">Trainings Assigned</th>
                                <th scope="col">Trainings Type</th>
                                <th scope="col">Trainings Language</th>
                                <th scope="col">Trainings Completed</th>
                            </tr>
                        </thead>
                        <tbody id="trainingReportStatus">
                        </tbody>
                    </table>
                </div>

                <hr>

                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">Training Campaign Statistics</div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="file-export2" class="table table-bordered text-nowrap w-100">
                                <thead>
                                    <tr>
                                        <th>Employee Name</th>
                                        <th>Email Address</th>
                                        <th>Training Module</th>
                                        <th>Date Assigned</th>
                                        <th>Score</th>
                                        <th>Passing Score</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="trainingReportsIndividual">

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>