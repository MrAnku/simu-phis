<div class="card-body">
    <ul class="nav nav-pills justify-content-start nav-style-3 mb-3" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link active" id="quishing-detail" data-bs-toggle="tab" role="tab" aria-current="page" href="#home-right"
                aria-selected="true">Quishing</a>
        </li>
        <li class="nav-item quishing-training-detail" role="presentation" style="display: none;">
            <a class="nav-link" data-bs-toggle="tab" role="tab" aria-current="page" href="#about-right"
                aria-selected="false" tabindex="-1">Training</a>
        </li>

    </ul>
    <div class="tab-content">
        <div class="tab-pane text-muted active show" id="home-right" role="tabpanel">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        Quishing Campaign Details
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table text-nowrap">
                            <thead>
                                <tr>
                                    <th scope="col">Campaign name</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Employees</th>
                                    <th scope="col">Emails Delivered</th>
                                    <th scope="col">Emails Viewed</th>
                                    <th scope="col">QR Scanned</th>
                                    <th scope="col">Employees Compromised</th>
                                    <th scope="col">Emails Reported</th>
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
                        Target Employees Interaction
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table text-nowrap">
                            <thead>
                                <tr>
                                    <th>Employee Name</th>
                                    <th>Email Address</th>
                                    <th>Email Delivery</th>
                                    <th>Email Viewed</th>
                                    <th>QR Scanned</th>
                                    <th>Employee Compromised</th>
                                    <th>Email Reported</th>
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
                        Quishing Training Details
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table text-nowrap">
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
                            <tbody id="qcampTrainingData">
                                <tr>
                                    <th scope="row">Mark</th>
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
                        Target Employees Progress
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table text-nowrap">
                            <thead>
                                <tr>
                                    <th>Employee Name</th>
                                    <th>Email Address</th>
                                    <th>Training Module</th>
                                    <th>Date Assigned</th>
                                    <th>Score</th>
                                    <th>Passing Score</th>
                                    <th>Status</th>
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
