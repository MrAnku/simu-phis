@extends('layouts.app')

@section('title', 'Employees - Phishing awareness training program')

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
                    <button type="button" class="btn btn-dark mb-3" onclick="checkHasConfig()" data-bs-toggle="modal"
                        data-bs-target="#syncDirectoryModal">Directory Sync</button>
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
                    <form action="{{ route('employee.newgroup') }}" method="post">
                        @csrf
                        <div class="row align-items-end">

                            <div class="col-lg-6">
                                <label for="input-label" class="form-label">Employee Group Name</label>
                                <input type="text" class="form-control" name="usrGroupName">
                            </div>
                            <div class="col-lg-6">
                                <button type="submit" class="btn btn-primary mt-3 btn-wave waves-effect waves-light">Add
                                    Employee Group</button>
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
    <div class="modal" id="newDomainVerificationModal" tabindex="-1" aria-labelledby="exampleModalLgLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Verify Domain</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="" id="sendOtpForm" method="post">

                        <p class="text-muted">Domain verification is performed through challenge-response authentication of
                            the provided email address.(e.g. verifying support@mybusiness.com will enable mybusiness.com.)
                        </p>
                        <div>
                            <label for="input-label" class="form-label">Email Address<sup
                                    class="text-danger">*</sup></label>
                            <input type="text" class="form-control" name="verificationEmail">
                        </div>
                        <button type="submit" id="sendOtpBtn"
                            class="btn btn-primary my-3 btn-wave waves-effect waves-light">Send Verification Email</button>
                        <button class="btn btn-primary my-3 btn-loader d-none" id="otpSpinner">
                            <span class="me-2">Please wait...</span>
                            <span class="loading"><i class="ri-loader-2-fill fs-16"></i></span>
                        </button>
                        <p class="text-muted">Haven't received the verification code? Try generating another verification
                            email.</p>
                    </form>

                    <div id="enterOtpContainer" class="d-none">
                        <form action="" id="otpSubmitForm" method="post">
                            <div class="d-flex align-items-end justify-content-center">
                                <div>
                                    <label for="input-label" class="form-label">Enter OTP</label>
                                    <input type="text" class="form-control" name="emailOTP" placeholder="xxxxxx">
                                </div>
                                <button type="submit" id="otpSubmitBtn"
                                    class="btn btn-primary mx-3 btn-wave waves-effect waves-light">Submit</button>
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
                                    <th>WhatsApp</th>
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
                                    <a class="nav-link active" data-bs-toggle="tab" role="tab" aria-current="page"
                                        href="#usingForm" aria-selected="true">Import Using Form</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" role="tab" aria-current="page"
                                        href="#usingCsv" aria-selected="true">Import Using CSV</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" role="tab" aria-current="page"
                                        href="#active_d" aria-selected="true">Import From Directory</a>
                                </li>
                            </ul>
                            <div class="tab-content">
                                <div class="tab-pane show active text-muted" id="usingForm" role="tabpanel">
                                    <form action="" method="post" id="adduserForm">
                                        <div class="row">

                                            <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12">
                                                <label for="input-label" class="form-label">Name<sup
                                                        class="text-danger">*</sup></label>
                                                <input type="text" class="form-control" name="usrName" required>
                                                <input type="hidden" name="groupid" class="groupid">
                                            </div>
                                            <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12">
                                                <label for="input-label" class="form-label">Email<sup
                                                        class="text-danger">*</sup></label>
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
                                                <input type="text" class="form-control" placeholder="919876543210"
                                                    name="usrWhatsapp" id="usrWhatsapp">
                                            </div>
                                            <div class="mt-3 col-xl-4 col-lg-6 col-md-6 col-sm-12">
                                                <div class="text-start">
                                                    <button type="submit" name="addUsr"
                                                        class="btn btn-primary mt-3 btn-wave waves-effect waves-light">Add
                                                        Employee</button>
                                                </div>
                                            </div>

                                        </div>
                                    </form>
                                </div>
                                <div class="tab-pane text-muted" id="usingCsv" role="tabpanel">
                                    <form action="{{ route('employee.importCsv') }}" method="post"
                                        enctype="multipart/form-data">
                                        @csrf
                                        <div class="row align-items-center">
                                            <div class="col-lg-9">
                                                <div class="mb-3">
                                                    <label for="formFile" class="form-label">Select csv file to
                                                        import</label>
                                                    <input class="form-control" type="file" id="formFile"
                                                        name="usrCsv" accept=".csv">
                                                    <input type="hidden" name="groupid" class="groupid">
                                                </div>
                                                <div>
                                                    <a href="./storage/uploads/example.csv"
                                                        class="mt-2 text-primary">Download Sample</a>
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
                                                <input class="form-check-input" type="radio" name="ad_provider"
                                                    id="ldap_radio" value="ldap" checked="">
                                                <label class="form-check-label" for="ldap_radio">
                                                    LDAP AD
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="ad_provider"
                                                    id="outlook_radio" value="outlook">
                                                <label class="form-check-label" for="outlook_radio">
                                                    Outlook/Azure
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="ad_provider"
                                                    id="google_radio" value="google">
                                                <label class="form-check-label" for="google_radio">
                                                    Google Workspace
                                                </label>
                                            </div>
                                        </div>

                                        <div>

                                            <button type="button" id="sync_ad_btn"
                                                class="btn btn-success rounded-pill btn-wave">Sync Directory</button>
                                        </div>
                                    </div>

                                    <div id="syncRecords" class="d-none">
                                        <div class="mt-3" id="syncUserForms">



                                        </div>

                                        {{-- <div class="text-end mt-4">
                                            <button type="button" class="btn btn-secondary btn-wave">Save All</button>
                                        </div> --}}
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

    {{-- Directory sync Modal --}}

    <div class="modal fade" id="syncDirectoryModal" tabindex="-1" aria-labelledby="exampleModalLgLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Provider</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <div class="card-body">
                        <ul class="nav nav-tabs tab-style-2 nav-justified mb-3 d-sm-flex d-block" id="myTab1"
                            role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="order-tab" data-bs-toggle="tab"
                                    data-bs-target="#order-tab-pane" type="button" role="tab"
                                    aria-controls="home-tab-pane" aria-selected="true"><i
                                        class="ri-copyleft-line me-1 align-middle"></i>LDAP AD</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="confirmed-tab" data-bs-toggle="tab"
                                    data-bs-target="#confirm-tab-pane" type="button" role="tab"
                                    aria-controls="profile-tab-pane" aria-selected="false"><i
                                        class="ri-windows-line me-1 align-middle"></i>Outlook/Azure</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="shipped-tab" data-bs-toggle="tab"
                                    data-bs-target="#shipped-tab-pane" type="button" role="tab"
                                    aria-controls="contact-tab-pane" aria-selected="false"><i
                                        class="ri-google-line me-1 align-middle"></i>Google Workspace</button>
                            </li>
                        </ul>
                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active" id="order-tab-pane" role="tabpanel"
                                aria-labelledby="home-tab" tabindex="0">

                                <div id="ldapConfig">
                                    <form class="text-center" id="ldapConfigForm" method="post"
                                        action="{{ route('employee.save.ldap.config') }}">
                                        @csrf
                                        <div class="row">
                                            <div class="col-lg-6">
                                                <div class="row mb-3">
                                                    <label for="ldap_host" class="col-sm-2 col-form-label">LDAP
                                                        Host</label>
                                                    <div class="col-sm-10">
                                                        <input type="text" class="form-control" id="ldap_host"
                                                            name="ldap_host">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="row mb-3">
                                                    <label for="ldap_dn" class="col-sm-2 col-form-label">LDAP DN</label>
                                                    <div class="col-sm-10">
                                                        <input type="text" class="form-control" id="ldap_dn"
                                                            name="ldap_dn">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-lg-6">
                                                <div class="row mb-3">
                                                    <label for="ldap_admin" class="col-sm-2 col-form-label">Admin
                                                        Username</label>
                                                    <div class="col-sm-10">
                                                        <input type="text" class="form-control" id="ldap_admin"
                                                            name="ldap_admin">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="row mb-3">
                                                    <label for="ldap_pass" class="col-sm-2 col-form-label">Admin
                                                        Password</label>
                                                    <div class="col-sm-10">
                                                        <input type="password" class="form-control" id="ldap_pass"
                                                            name="ldap_pass">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <button class="btn btn-info label-btn rounded-pill" id="edit_ldap_config">
                                            <i class="ri-edit-line label-btn-icon me-2 rounded-pill"></i>
                                            Edit
                                        </button>

                                        <button id="save_ldap_config" type="submit"
                                            class="btn btn-success label-btn rounded-pill">
                                            <i class="ri-save-3-line label-btn-icon me-2 rounded-pill"></i>
                                            Save
                                        </button>

                                        <button id="add_ldap_config" type="submit"
                                            class="btn btn-success label-btn rounded-pill">
                                            <i class="ri-save-3-line label-btn-icon me-2 rounded-pill"></i>
                                            Add
                                        </button>



                                    </form>
                                </div>
                            </div>
                            <div class="tab-pane fade text-muted" id="confirm-tab-pane" role="tabpanel"
                                aria-labelledby="profile-tab" tabindex="0">
                                <ul class="ps-3 mb-0">
                                    <li>As opposed to using 'Content here, content here', making it look like
                                        readable English. Many desktop publishing packages and web page editors
                                        now use Lorem Ipsum as their default model text, and a search.</li>
                                </ul>
                            </div>
                            <div class="tab-pane fade text-muted" id="shipped-tab-pane" role="tabpanel"
                                aria-labelledby="contact-tab" tabindex="0">
                                <ul class="ps-3 mb-0">
                                    <li>but also the leap into electronic typesetting, remaining essentially
                                        unchanged. It was popularised in the 1960s with the release of Letraset
                                        sheets containing Lorem Ipsum passages, and more recently.</li>
                                </ul>
                            </div>
                            <div class="tab-pane fade text-muted" id="delivered-tab-pane" role="tabpanel"
                                tabindex="0">
                                <ul class="list-unstyled mb-0">
                                    <li>A Latin professor at Hampden-Sydney College in Virginia, looked up one
                                        of the more obscure Latin words, consectetur, from a Lorem Ipsum
                                        passage, and going through the cites of the word in classical
                                        literature.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>


    {{-- -------------------Modals------------------------ --}}

    {{-- ------------------------------Toasts---------------------- --}}

    <div class="toast-container position-fixed top-0 end-0 p-3">
        @if (session('success'))
            <div class="toast colored-toast bg-success-transparent fade show" role="alert" aria-live="assertive"
                aria-atomic="true">
                <div class="toast-header bg-success text-fixed-white">
                    <strong class="me-auto">Success</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    {{ session('success') }}
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="toast colored-toast bg-danger-transparent fade show" role="alert" aria-live="assertive"
                aria-atomic="true">
                <div class="toast-header bg-danger text-fixed-white">
                    <strong class="me-auto">Error</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    {{ session('error') }}
                </div>
            </div>
        @endif

        @if ($errors->any())
            @foreach ($errors->all() as $error)
                <div class="toast colored-toast bg-danger-transparent fade show" role="alert" aria-live="assertive"
                    aria-atomic="true">
                    <div class="toast-header bg-danger text-fixed-white">
                        <strong class="me-auto">Error</strong>
                        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    <div class="toast-body">
                        {{ $error }}
                    </div>
                </div>
            @endforeach
        @endif


    </div>

    {{-- ------------------------------Toasts---------------------- --}}


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

        <script>
            function checkHasConfig() {

                $.get('/employees/check-ldap-ad-config', function(res) {

                    console.log(res)

                    if (res.status === 1) {
                        $("#ldap_host").val(res.data.ldap_host).attr('disabled', true)
                        $("#ldap_dn").val(res.data.ldap_dn).attr('disabled', true)
                        $("#ldap_admin").val(res.data.admin_username).attr('disabled', true)
                        $("#ldap_pass").val(res.data.admin_password).attr('disabled', true)

                        $("#save_ldap_config").hide();
                        $("#edit_ldap_config").show();
                        $("#add_ldap_config").hide();
                    } else {
                        $("#save_ldap_config").hide();
                        $("#edit_ldap_config").hide();
                        $("#add_ldap_config").show();
                    }
                }).fail(function(error) {
                    console.log(error);
                });
            }

            $("#edit_ldap_config").on('click', function(e) {
                e.preventDefault();
                this.style.display = 'none';
                $("#save_ldap_config").show()

                $("#ldap_host").removeAttr('disabled');
                $("#ldap_dn").removeAttr('disabled');
                $("#ldap_admin").removeAttr('disabled');
                $("#ldap_pass").removeAttr('disabled');
            })

            $("#add_ldap_config").on('click', function(e) {
                e.preventDefault();
                this.innerText = 'Please Wait...';


                var host = $("#ldap_host").val();
                var dn = $("#ldap_dn").val();
                var user = $("#ldap_admin").val();
                var pass = $("#ldap_pass").val();

                $.post({
                    url: '/employees/add-ldap-config',
                    data: {
                        host,
                        dn,
                        user,
                        pass
                    },
                    success: function(res) {

                        if (res.status == 1) {
                            alert(res.msg)
                            window.location.href = window.location.href;
                        } else {
                            console.log(res)
                            // alert(res.msg)
                            alert(res.msg)
                        }
                    }
                })



            })

            // $(".saveSyncUser").on('click', function(e){
            //     // e.preventDefault();
            //     var sibs = $(this).parent().siblings();
            //     console.log(sibs);
            // })
            function isValidEmail(email) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailRegex.test(email); // Returns true if valid, false otherwise
            }

            function saveSyncUser(btn) {
                var spinner =
                    '<div class="spinner-border spinner-border-sm me-4" role="status"><span class="visually-hidden">Loading...</span></div>';

                $(btn).html(spinner);

                var sibs = $(btn).parent().siblings(); // Get siblings of the parent element
                var values = {};

                var error = false;

                // Loop through each sibling element
                sibs.each(function(index) {
                    var input = $(this).find('input'); // Find the input inside the sibling
                    if (input.length > 0) {
                        var key = input.data('key');
                        var value = input.val();
                        if (key == 'usrWhatsapp') {
                            value = Number.isInteger(value) ? value : null;
                        }

                        if (key == 'usrEmail') {
                            if (!isValidEmail(value)) {
                                Swal.fire(
                                    "Please enter a valid email!",
                                    '',
                                    'error'
                                )

                                error = true;
                            }

                        }

                        values[key] = value; // Store the input value in the object with a dynamic key
                    }
                });

                if (error) {
                    $(btn).html("Save");
                    return;
                }

                var groupid = $(".groupid").first().val();

                var userdata = {
                    ...values,
                    groupid: groupid
                }

                console.log(userdata)

                $.post({
                    url: '/employees/addUser',
                    data: userdata,
                    success: function(res) {
                        if (res.status == 0) {
                            // alert(resJson.msg);
                            Swal.fire(
                                res.msg,
                                '',
                                'error'
                            )

                            $(btn).html("Save");
                        } else {
                            // var params = new URLSearchParams(formData);
                            // var groupid = params.get('groupid');
                            viewUsersByGroup(groupid);

                            $(btn).html("Saved").removeClass("btn-primary").addClass("btn-success").attr('disabled',
                                true);
                        }

                    }
                })
            }

            $("#sync_ad_btn").on('click', function() {
                var btn = this;
                btn.innerText = "Please Wait...";
                var provider = $("input[name='ad_provider']:checked").val();

                if (provider == 'ldap') {
                    $.get('/employees/sync-ldap-directory', function(res) {
                        console.log(res)

                        if (res.status === 1) {
                            btn.innerText = 'Sync Directory';
                            pushTableHead()
                            res.data.forEach(element => {
                                generateForm(element);
                            });
                            $("#syncRecords").removeClass('d-none');
                        } else {
                            btn.innerText = 'Sync Directory';
                            alert(res.message);
                        }
                    }).fail(function(error) {
                        console.log(error);
                    });
                } else {
                    Swal.fire(
                        "This provider is currently inactive in our system",
                        '',
                        'error'
                    )
                    btn.innerText = "Sync Directory";
                }


            })

            function generateForm(element) {
                var form = `<form action="" method="post">
                                                <div class="table-responsive">
                                                    <table class="table table-primary">
    
                                                        <tbody>
                                                            <tr class="">
                                                                <td scope="row">
                                                                    <input type="text" data-key="usrName" class="form-control"
                                                                        value="${element.username}">
                                                                </td>
                                                                <td><input type="text" data-key="usrEmail" class="form-control"
                                                                        value="${element.email}"></td>
                                                                <td><input type="text" data-key="usrCompany" class="form-control"
                                                                        value="N/A"></td>
                                                                <td><input type="text" data-key="usrJobTitle" class="form-control"
                                                                        value="N/A"></td>
                                                                <td><input type="text" data-key="usrWhatsapp" class="form-control"
                                                                        value="N/A"></td>
                                                                
                                                                        <td>
                                                                            <button type="button" onclick="saveSyncUser(this)" class="btn btn-primary btn-sm btn-wave">Save</button>
                                                                        </td>
                                                            </tr>
                                                            
                                                        </tbody>
                                                    </table>
                                                </div>
    
                                            </form>`;
                $("#syncUserForms").append(form);
            }

            function pushTableHead() {
                var thead = `<table class="table table-primary">
    
                                                        <tbody>
                                                            <tr class="">
                                                                <td scope="row">
                                                                    <input type="text" class="form-control"
                                                                        value="Name" disabled>
                                                                </td>
                                                                <td><input type="text" class="form-control"
                                                                        value="Email" disabled></td>
                                                                <td><input type="text" class="form-control"
                                                                        value="Company" disabled></td>
                                                                <td><input type="text" class="form-control"
                                                                        value="Job Title" disabled></td>
                                                                <td><input type="text" class="form-control"
                                                                        value="WhatsApp" disabled></td>
                                                                
                                                                        <td>
                                                                            Action
                                                                        </td>
                                                            </tr>
                                                            
                                                        </tbody>
                                                    </table>`;
                $("#syncUserForms").html(thead);
            }
        </script>
    @endpush

@endsection
