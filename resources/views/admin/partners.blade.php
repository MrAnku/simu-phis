@extends('admin.layouts.app')

@section('title', 'Partners | simUphish')

@section('main-content')

    <div class="main-content app-content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between mt-3">
                <div>
                    <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal"
                        data-bs-target="#newPartnerAddModal">New Partner</button>
                </div>
                <div>
                    <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal"
                        data-bs-target="#partnerNoticeModal">Partner Notice</button>
                </div>
            </div>

            <div class="row">
                <div class="col-xl-12">
                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">
                                Manage Partners
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="datatable-basic" class="table table-bordered text-nowrap w-100">
                                    <thead>
                                        <tr>
                                            <th>Sl</th>
                                            <th>Full Name</th>
                                            <th>Email</th>
                                            <th>Company</th>
                                            <th>Additional info</th>
                                            <th>Status</th>
                                            <th>Services</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if ($all_partners->count() > 0)
                                            @foreach ($all_partners as $partner)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>{{ $partner->full_name }}</td>
                                                    <td>{{ $partner->email }}</td>
                                                    <td>{{ $partner->company }}</td>
                                                    <td>{{ $partner->additional_info }}</td>
                                                    <td>
                                                        @if ($partner->approved == 1)
                                                            <span class="badge bg-success">Approved</span>
                                                        @else
                                                            <span class="badge bg-warning">Pending</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if ($partner->service_status == 1)
                                                            <span class="badge bg-success">Active</span>
                                                        @else
                                                            <span class="badge bg-warning">Inactive</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if ($partner->approved == 1)
                                                            @if ($partner->service_status == 1)
                                                                <button type="button" title="Hold Services"
                                                                    onclick="holdService(this, '{{ $partner->id }}')"
                                                                    class="btn btn-warning btn-sm btn-wave waves-effect waves-light">Hold</button>
                                                                <button type="button"
                                                                    onclick="deletePartner(this, '{{ $partner->id }}')"
                                                                    class="btn btn-danger btn-sm btn-wave waves-effect waves-light">Delete</button>
                                                            @else
                                                                <button type="button" title="Start Services"
                                                                    onclick="startService(this, '{{ $partner->id }}')"
                                                                    class="btn btn-success btn-sm btn-wave waves-effect waves-light">Start</button>
                                                                <button type="button" title="Delete Partner"
                                                                    onclick="deletePartner(this, '{{ $partner->id }}')"
                                                                    class="btn btn-danger btn-sm btn-wave waves-effect waves-light">Delete</button>
                                                            @endif
                                                        @else
                                                            <button type="button"
                                                                onclick="confirmApprove(this, '{{ $partner->id }}')"
                                                                class="btn btn-success btn-sm btn-wave waves-effect waves-light">Approve</button>
                                                            <button type="button"
                                                                onclick="rejectApproval(this, '{{ $partner->id }}')"
                                                                class="btn btn-danger btn-sm btn-wave waves-effect waves-light">Reject</button>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="8" class="text-center">No records found</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- --------------------------Modals------------------------ --}}

    <div class="modal fade" id="newPartnerAddModal" tabindex="-1" aria-labelledby="exampleModalLgLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Add New Partner</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('admin.createPartner') }}" method="post">
                        @csrf
                        <div class="row gy-3 mt-3">
                            <div class="col-xl-12 mt-0">
                                <label class="form-label text-default">Full Name<sup class="text-danger">*</sup></label>
                                <input type="text" class="form-control form-control-lg" name="full_name"
                                    placeholder="Full name" required="">
                            </div>
                            <div class="col-xl-12">
                                <label class="form-label text-default">Email Address<sup class="text-danger">*</sup></label>
                                <input type="email" class="form-control form-control-lg" name="email"
                                    placeholder="Enter your email" required="">
                            </div>
                            <div class="col-xl-12">
                                <label for="company" class="form-label text-default">Company Name<sup
                                        class="text-danger">*</sup></label>
                                <div class="input-group">
                                    <input type="text" class="form-control form-control-lg" name="company_name"
                                        id="company" placeholder="Company" required="">

                                </div>
                            </div>
                            <div class="col-xl-12">
                                <label for="input-label" class="form-label">Services<sup class="text-danger">*</sup></label>
                                <select class="form-select" name="service_status" required>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>

                            </div>

                            <div class="col-xl-12 mb-3">
                                <label for="text-area" class="form-label">Additional Information</label>
                                <textarea class="form-control" id="text-area" rows="1" name="add_info" style="height: 89px;"
                                    placeholder="Hey there we are a managed service provider"></textarea>


                            </div>
                            <div class="col-xl-12 d-grid mt-2">
                                <button type="submit" class="btn btn-lg btn-primary">Add Partner</button>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <!-- partner notice modal -->
    <div class="modal fade" id="partnerNoticeModal" tabindex="-1" aria-labelledby="exampleModalLgLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Partner Notice</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="card-body">
                        <ul class="nav nav-tabs tab-style-1 d-sm-flex d-block" role="tablist">
                            <li class="nav-item" role="presentation">
                                <a class="nav-link active" data-bs-toggle="tab" data-bs-target="#newnotice"
                                    aria-current="page" href="#newnotice" aria-selected="true" role="tab">New
                                    Notice</a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" data-bs-toggle="tab" data-bs-target="#sentNotice" href="#sentNotice"
                                    aria-selected="false" role="tab" tabindex="-1">Sent</a>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane active show" id="newnotice" role="tabpanel">
                                <form action="{{route('admin.addNotice')}}" method="post">
                                    @csrf
                                    <div class="row mb-3">
                                        <label for="inputEmail3" class="col-sm-6 col-form-label">Select Partner</label>
                                        <div class="col-sm-6">
                                            <select class="form-select" name="partner">
                                                <option selected="">Choose Partner</option>

                                                @if ($all_partners->count() > 0)

                                                    @foreach ($all_partners as $partner)
                                                        <option value="{{ $partner->partner_id }}">
                                                            {{ $partner->full_name }} ({{ $partner->email }})</option>
                                                    @endforeach
                                                @endif

                                            </select>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <label for="inputEmail3" class="col-sm-6 col-form-label">Notice Title</label>
                                        <div class="col-sm-6">
                                            <input type="text" class="form-control" name="title" required>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label for="inputEmail3" class="col-sm-6 col-form-label">Notice Message</label>
                                        <div class="col-sm-6">
                                            <input type="text" class="form-control" name="msg" required>
                                        </div>
                                    </div>
                                    <div class="text-end">

                                        <button
                                            class="btn btn-secondary btn-sm shadow-sm btn-wave waves-effect waves-light"
                                            type="submit" name="add_notice">Send</button>
                                    </div>
                                </form>
                            </div>
                            <div class="tab-pane" id="sentNotice" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table text-nowrap">
                                        <thead>
                                            <tr>
                                                <th>Partner Name</th>
                                                <th>Partner Email</th>
                                                <th>Title</th>
                                                <th>Message</th>
                                                <th>Date</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @if ($notices->count() > 0)
                                                @foreach ($notices as $notice)
                                                    <tr>
                                                        <td>{{ $notice->partner->full_name }}</td>
                                                        <td>{{ $notice->partner->email }}</td>
                                                        <td>{{ $notice->notice_title }}</td>
                                                        <td>
                                                            <a tabindex="0" class="btn btn-outline-primary btn-sm"
                                                                role="button" data-bs-toggle="popover"
                                                                data-bs-placement="top"
                                                                data-bs-content="{{ $notice->notice_msg }}"
                                                                data-bs-original-title="Notice Message">{{ Str::limit($notice->notice_msg, 8) }}</a>
                                                        </td>
                                                        <td>{{ $notice->date }}</td>
                                                        <td>
                                                            <button
                                                                class="btn btn-danger btn-sm shadow-sm btn-wave waves-effect waves-light"
                                                                title="The partner will see the default message"
                                                                onclick="deleteNotice(`{{ $notice->id }}`)">Delete</button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @else
                                                <tr>
                                                    <td colspan="6" class="text-center">No records found</td>
                                                </tr>
                                            @endif




                                        </tbody>
                                    </table>
                                </div>

                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- --------------------------Modals------------------------ --}}


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

    @push('newscripts')
        <script>
            function checkResponse(res) {
                if (res.status == 1) {
                    Swal.fire(
                        res.msg,
                        '',
                        'success'
                    ).then(function() {
                        window.location.href = window.location.href
                    })
                } else {
                    Swal.fire(
                        'Something went wrong...',
                        '',
                        'error'
                    ).then(function() {
                        window.location.href = window.location.href
                    })
                }
            }

            function confirmApprove(btn, id) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "After confirmation the login credential will be sent to the partner email.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e6533c',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Approve'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $(btn).html("Please Wait...")
                        $.post({
                            url: '/admin/approve-partner',
                            data: {
                                "partnerId": id
                            },
                            success: function(res) {
                                $(btn).html("Approve")

                                checkResponse(res);

                            }
                        })
                    }
                })


            }

            function holdService(btn, id) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "All services including adding company for this partner will be hold.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e6533c',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Hold'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $(btn).html("Please Wait...")
                        $.post({
                            url: '/admin/hold-service',
                            data: {
                                "partnerId": id
                            },
                            success: function(res) {
                                $(btn).html("Hold")

                                checkResponse(res);

                            }
                        })
                    }
                })

            }

            function startService(btn, id) {

                Swal.fire({
                    title: 'Are you sure?',
                    text: "All services including adding company will be started for this partner.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e6533c',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Start'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $(btn).html("Please Wait...")
                        $.post({
                            url: '/admin/start-service',
                            data: {
                                "partnerId": id
                            },
                            success: function(res) {
                                $(btn).html("Start")

                                checkResponse(res);

                            }
                        })
                    }
                })


            }

            function rejectApproval(btn, id) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "The partner request will be deleted",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e6533c',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Reject'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $(btn).html("Please Wait...")
                        $.post({
                            url: '/admin/reject-approval',
                            data: {
                                "partnerId": id
                            },
                            success: function(res) {
                                $(btn).html("Reject")

                                checkResponse(res);

                            }
                        })
                    }
                })



            }

            function deletePartner(btn, id) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "The partner and the companies under this partner will be deleted.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e6533c',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Delete'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $(btn).html("Please Wait...")
                        $.post({
                            url: '/admin/delete-partner',
                            data: {
                                "partnerId": id
                            },
                            success: function(res) {
                                $(btn).html("Delete")

                                checkResponse(res);

                            }
                        })
                    }
                })


            }

            function deleteNotice(id) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e6533c',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Delete'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post({
                            url: '/admin/delete-notice',
                            data: {
                                "noticeid": id
                            },
                            success: function(res) {
                                checkResponse(res);

                            }
                        })
                    }
                })


            }
        </script>
    @endpush

@endsection
