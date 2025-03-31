<div class="card custom-card">

    <div class="card-body p-0">
        <ul class="nav nav-pills nav-style-3 mb-3" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" role="tab" aria-current="page" href="#usingForm"
                    aria-selected="true">Import Using Form</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" role="tab" aria-current="page" href="#usingCsv"
                    aria-selected="true">Import Using CSV</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" role="tab" aria-current="page" href="#active_d"
                    aria-selected="true">Import From Directory</a>
            </li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane show active text-muted" id="usingForm" role="tabpanel">
                <form action="" method="post" id="adduserPlanForm">
                    <div class="row">

                        <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12">
                            <label for="input-label" class="form-label">Name<sup class="text-danger">*</sup></label>
                            <input type="text" class="form-control" name="usrName" required>
                            <input type="hidden" name="groupid" class="groupid">
                        </div>
                        <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12">
                            <label for="input-label" class="form-label">Email<sup class="text-danger">*</sup></label>
                            <input type="text" class="form-control" name="usrEmail">
                        </div>
                        <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12">
                            <label for="input-label" class="form-label">Company</label>
                            <input type="text" class="form-control" name="usrCompany">
                        </div>
                        <div class="mt-3 col-xl-4 col-lg-6 col-md-6 col-sm-12">
                            <label for="input-label" class="form-label">Job Title</label>
                            <input type="text" class="form-control" name="usrJobTitle">
                        </div>
                        <div class="mt-3 col-xl-4 col-lg-6 col-md-6 col-sm-12">
                            <label for="input-label" class="form-label">WhatsApp No.</label>
                            <input type="text" class="form-control" placeholder="919876543210" name="usrWhatsapp"
                                id="usrWhatsapp">
                        </div>
                        <div class="mt-3 col-xl-4 col-lg-6 col-md-6 col-sm-12">
                            <div class="text-start mt-2">
                                <button type="submit" name="addUsr"
                                    class="btn btn-primary mt-3 btn-wave waves-effect waves-light">Add
                                    Employee</button>
                            </div>
                        </div>

                    </div>
                </form>
            </div>
            <div class="tab-pane text-muted" id="usingCsv" role="tabpanel">
                <form action="{{ route('employee.importCsv') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="row align-items-center">
                        <div class="col-lg-9">
                            <div class="mb-3">
                                <label for="formFile" class="form-label">Select csv file to
                                    import</label>
                                <input class="form-control" type="file" id="formFile" name="usrCsv" accept=".csv">
                                <input type="hidden" name="groupid" class="groupid">
                            </div>
                            <div>
                                <a href="./storage/uploads/example.csv" class="mt-2 text-primary">Download Sample</a>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="mb-3">
                                <button type="submit" name="importCsv" id="importBtn"
                                    class="btn btn-primary mt-3 btn-wave waves-effect waves-light">Import</button>
                            </div>
                        </div>
                    </div>
                </form>

            </div>
            <div class="tab-pane" id="active_d" role="tabpanel">

                <div class="d-flex gap-3 justify-content-between">
                    <div class="d-flex gap-3 align-items-center">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="ad_provider" id="ldap_radio"
                                value="ldap" checked="">
                            <label class="form-check-label" for="ldap_radio">
                                LDAP AD
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="ad_provider" id="outlook_radio"
                                value="outlook">
                            <label class="form-check-label" for="outlook_radio">
                                Outlook/Azure
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="ad_provider" id="google_radio"
                                value="google">
                            <label class="form-check-label" for="google_radio">
                                Google Workspace
                            </label>
                        </div>
                    </div>

                    <div>

                        <button type="button" id="sync_ad_btn" class="btn btn-success rounded-pill btn-wave">Sync
                            Directory</button>
                    </div>
                </div>

                <div id="syncRecords" class="d-none">
                    <div class="mt-3" id="syncUserForms">



                    </div>

                    {{-- <div class="text-end mt-4">
                        <button type="button" class="btn btn-secondary btn-wave">Save All</button>
                    </div> --}}
                </div>

                <div id="outlookContainer" style="display: none;">
                    <div class="mt-3 px-4 gap-3 d-flex justify-content-center align-items-center">
                        <select class="form-control" name="outlookGroups" id="outlookGroups" style="width: 300px;">

                        </select>
                        <button type="button" class="btn btn-secondary btn-sm rounded-pill btn-wave"
                            onclick="fetchOutlookEmployees(this)">Sync Employees</button>

                    </div>

                    <div class="mt-3" id="outlookEmps" style="display: none;">
                        <table class="table table-bordered table-striped table-responsive">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Company</th>
                                    <th>Job Title</th>
                                    <th>WhatsApp</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>

                            </tbody>
                        </table>

                        <div class="mt-3 d-flex justify-content-center">
                            <button type="button" class="btn btn-success btn-sm rounded-pill btn-wave"
                                onclick="saveOutlookSyncedEmployees(this)">Save Employees</button>
                        </div>


                    </div>
                </div>


            </div>
        </div>
    </div>
</div>
<div class="table-responsive">
    <table id="addedUsersTable" class="employeesTable table table-bordered text-nowrap w-100">
        <thead>
            <tr>
                <th>Sl</th>
                <th>Name</th>
                <th>Email</th>
                <th>Company</th>
                <th>Job Title</th>
                <th>WhatsApp</th>
            </tr>
        </thead>
        <tbody class="addedPlanUsers"></tbody>
    </table>
</div>
