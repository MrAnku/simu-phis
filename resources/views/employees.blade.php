@extends('layouts.app')

@section('title', 'Campaigns - Phishing awareness training program')

@section('main-content')

    <div class="main-content app-content">
        <div class="container-fluid mt-4">

            <div class="row my-3">
                <div class="col-xxl-4 col-xl-12">
                    <div class="card custom-card hrm-main-card primary">
                        <div class="card-body">
                            <div class="d-flex align-items-top">

                                <div class="flex-fill">
                                    <span class="fw-semibold text-muted d-block mb-2">Across all employee groups
                                        (Total)</span>
                                    <h5 class="fw-semibold mb-2">{{ $totalEmps }} Employees</h5>

                                </div>
                                <div class="me-3">
                                    <span class="avatar bg-primary">
                                        <i class="ri-team-line fs-18"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-4 col-xl-12">
                    <div class="card custom-card hrm-main-card secondary">
                        <div class="card-body">
                            <div class="d-flex align-items-top">

                                <div class="flex-fill">
                                    <span class="fw-semibold text-muted d-block mb-2">{{ $verifiedDomains->count() }} Used
                                        (500 Domains Max.)</span>
                                    <h5 class="fw-semibold mb-2">{{ $verifiedDomains->count() }} Domains Verified</h5>

                                </div>
                                <div class="me-3">
                                    <span class="avatar bg-success">
                                        <i class="bx bx-check-shield fs-18"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-4 col-xl-12">
                    <div class="card custom-card hrm-main-card warning">
                        <div class="card-body">
                            <div class="d-flex align-items-top">

                                <div class="flex-fill">
                                    <span class="fw-semibold text-muted d-block mb-2">Verification of ownership</span>
                                    <h5 class="fw-semibold mb-2">{{ $notVerifiedDomains->count() }} Domains Pending</h5>

                                </div>
                                <div class="me-3">
                                    <span class="avatar bg-warning">
                                        <i class="bx bxs-key fs-18"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <div>
                    <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal"
                        data-bs-target="#newEmpGroupModal">New Employee Group</button>
                </div>
                <div>
                    <button type="button" class="btn btn-secondary mb-3" data-bs-toggle="modal"
                        data-bs-target="#domainVerificationModal">Domain Verification</button>
                    <button type="button" class="btn btn-dark mb-3" data-bs-toggle="modal"
                        data-bs-target="#newCampModal">Directory Sync</button>
                </div>
            </div>

            <div class="row">
                <div class="col-xl-12">
                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">
                                Manage Employees
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="allGroupsTable" class="table table-bordered text-nowrap w-100">
                                    <thead>
                                        <tr>
                                            <th>Sl</th>
                                            <th>Employee Group Name</th>
                                            <th>Employee Count</th>
                                            <th>Group Unique Id</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($groups as $group)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td> <!-- Serial number -->
                                                <td>
                                                    <a href="#" class="text-primary"
                                                        onclick="viewUsersByGroup('{{ $group->group_id }}')"
                                                        data-bs-target="#viewUsers" data-bs-toggle="modal">
                                                        {{ $group->group_name }}
                                                    </a>
                                                </td>
                                                <td>{{ $group->users_count }}</td>
                                                <td><span class="badge bg-info">{{ $group->group_id }}</span></td>
                                                <td>
                                                    <span class="text-secondary mx-1"
                                                        onclick="viewUsersByGroup('{{ $group->group_id }}')" role="button"
                                                        data-bs-target="#addUserModal" data-bs-toggle="modal">
                                                        <i class="bx bx-plus fs-4"></i>
                                                    </span>
                                                    <span class="text-danger ms-1"
                                                        onclick="deleteGroup('{{ $group->group_id }}')" role="button">
                                                        <i class="bx bx-trash fs-4"></i>
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td class="text-center" colspan="5">No records found</td>
                                            </tr>
                                        @endforelse

                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- -------------------Modals------------------------ --}}

     <!-- new Employee group modal -->
     <div class="modal fade" id="newEmpGroupModal" tabindex="-1" aria-labelledby="exampleModalLgLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Add Employee Group</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{route('employee.newgroup')}}" method="post">
                        @csrf
                        <div class="row align-items-end">

                            <div class="col-lg-6">
                                <label for="input-label" class="form-label">Employee Group Name</label>
                                <input type="text" class="form-control" name="usrGroupName">
                            </div>
                            <div class="col-lg-6">
                                <button type="submit" class="btn btn-primary mt-3 btn-wave waves-effect waves-light">Add Employee Group</button>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <!-- verified domains modal -->
    <div class="modal fade" id="domainVerificationModal" tabindex="-1" aria-labelledby="exampleModalLgLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Domain Verification</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <button type="button" id="newDomainVerificationModalBtn"
                        class="btn btn-primary mb-2 btn-wave waves-effect waves-light">Verify a new domain</button>
                    <div class="table-responsive">
                        <table id="domainVerificationTable" class="table table-bordered text-nowrap w-100">
                            <thead>
                                <tr>
                                    <th>Domain Name</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="allDomains">
                                @forelse ($allDomains as $domain)
                                    <tr>
                                        <td>{{ $domain->domain }}</td>
                                        <td>
                                            @if ($domain->verified == 1)
                                                <span class="badge bg-success">Verified</span>
                                            @else
                                                <span class="badge bg-warning">Pending</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span role="button" onclick="deleteDomain(`{{ $domain->domain }}`)"><i
                                                    class="bx bx-x fs-25"></i></span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-center" colspan="5">No records found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>

     <!-- new domain verification modal -->
     <div class="modal" id="newDomainVerificationModal" tabindex="-1" aria-labelledby="exampleModalLgLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Verify Domain</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="" id="sendOtpForm" method="post">

                        <p class="text-muted">Domain verification is performed through challenge-response authentication of the provided email address.(e.g. verifying support@mybusiness.com will enable mybusiness.com.)</p>
                        <div>
                            <label for="input-label" class="form-label">Email Address<sup class="text-danger">*</sup></label>
                            <input type="text" class="form-control" name="verificationEmail">
                        </div>
                        <button type="submit" id="sendOtpBtn" class="btn btn-primary my-3 btn-wave waves-effect waves-light">Send Verification Email</button>
                        <button class="btn btn-primary my-3 btn-loader d-none" id="otpSpinner">
                            <span class="me-2">Please wait...</span>
                            <span class="loading"><i class="ri-loader-2-fill fs-16"></i></span>
                        </button>
                        <p class="text-muted">Haven't received the verification code? Try generating another verification email.</p>
                    </form>

                    <div id="enterOtpContainer" class="d-none">
                        <form action="" id="otpSubmitForm" method="post">
                            <div class="d-flex align-items-end justify-content-center">
                                <div>
                                    <label for="input-label" class="form-label">Enter OTP</label>
                                    <input type="text" class="form-control" name="emailOTP" placeholder="xxxxxx">
                                </div>
                                <button type="submit" id="otpSubmitBtn" class="btn btn-primary mx-3 btn-wave waves-effect waves-light">Submit</button>
                                <button class="btn btn-primary mx-3 btn-loader d-none" id="otpSubmitSpinner">
                                    <span class="me-2">Please wait...</span>
                                    <span class="loading"><i class="ri-loader-2-fill fs-16"></i></span>
                                </button>
                            </div>
                        </form>
                    </div>


                </div>
            </div>
        </div>
    </div>

     <!-- view users modal -->
     <div class="modal fade" id="viewUsers" tabindex="-1" aria-labelledby="exampleModalLgLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">All Employees
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table id="allUsersByGroupTable" class="employeesTable table table-bordered text-nowrap w-100">
                            <thead>
                                <tr>
                                    <th>Sl</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Company</th>
                                    <th>Job Title</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody class="addedUsers"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

     <!-- add users modal -->
     <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="exampleModalLgLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Add Employee</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="card custom-card">

                        <div class="card-body p-0">
                            <ul class="nav nav-pills nav-style-3 mb-3" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" data-bs-toggle="tab" role="tab" aria-current="page" href="#usingForm" aria-selected="true">Import Using Form</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" role="tab" aria-current="page" href="#usingCsv" aria-selected="true">Import Using CSV</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" role="tab" aria-current="page" href="#services-right" aria-selected="true">Import From Directory</a>
                                </li>
                            </ul>
                            <div class="tab-content">
                                <div class="tab-pane show active text-muted" id="usingForm" role="tabpanel">
                                    <form action="" method="post" id="adduserForm">
                                        <div class="row">

                                            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                                                <label for="input-label" class="form-label">Name<sup class="text-danger">*</sup></label>
                                                <input type="text" class="form-control" name="usrName" required>
                                                <input type="hidden" name="groupid" class="groupid">
                                            </div>
                                            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                                                <label for="input-label" class="form-label">Email<sup class="text-danger">*</sup></label>
                                                <input type="text" class="form-control" name="usrEmail">
                                            </div>
                                            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                                                <label for="input-label" class="form-label">Company</label>
                                                <input type="text" class="form-control" name="usrCompany">
                                            </div>
                                            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                                                <label for="input-label" class="form-label">Job Title</label>
                                                <input type="text" class="form-control" name="usrJobTitle">
                                            </div>
                                            <div class="text-end">
                                                <button type="submit" name="addUsr" class="btn btn-primary mt-3 btn-wave waves-effect waves-light">Add Employee</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <div class="tab-pane text-muted" id="usingCsv" role="tabpanel">
                                    <form action="" method="post" enctype="multipart/form-data">
                                        <div class="row align-items-center">
                                            <div class="col-lg-9">
                                                <div class="mb-3">
                                                    <label for="formFile" class="form-label">Select csv file to import</label>
                                                    <input class="form-control" type="file" id="formFile" name="usrCsv" accept=".csv">
                                                    <input type="hidden" name="groupid" class="groupid">
                                                </div>
                                                <div>
                                                    <a href="./storage/uploads/example.csv" class="mt-2 text-primary">Download Sample</a>
                                                </div>
                                            </div>
                                            <div class="col-lg-3">
                                                <div class="mb-3">
                                                    <button type="submit" name="importCsv" id="importBtn" class="btn btn-primary mt-3 btn-wave waves-effect waves-light">Import</button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>

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
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody class="addedUsers"></tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>


    {{-- -------------------Modals------------------------ --}}


    @push('newcss')

    <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.3.0/css/responsive.bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.bootstrap5.min.css">

    @endpush

    @push('newscripts')

    <script src="assets/js/employees.js"></script>

     <!-- Datatables Cdn -->
     <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
     <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>
     <script src="https://cdn.datatables.net/responsive/2.3.0/js/dataTables.responsive.min.js"></script>
     <script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
     <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.print.min.js"></script>
     <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.6/pdfmake.min.js"></script>
     <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
     <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>
     <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

    <script>
        $('#allGroupsTable').DataTable({
            language: {
                searchPlaceholder: 'Search...',
                sSearch: '',
            },
            "pageLength": 10,
            // scrollX: true
        });

        $('#domainVerificationTable').DataTable({
            language: {
                searchPlaceholder: 'Search...',
                sSearch: '',
            },
            "pageLength": 10,
            // scrollX: true
        });
    </script>
    @endpush

@endsection
