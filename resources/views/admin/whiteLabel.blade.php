@extends('admin.layouts.app')

@section('title', 'Partners | simUphish')

@section('main-content')

    <div class="main-content app-content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-xl-12">
                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">
                                Manage Whitelabel Requests
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="datatable-basic" class="table table-bordered text-nowrap w-100">
                                    <thead>
                                        <tr>
                                            <th>Partner Email</th>
                                            <th>Domain</th>
                                            <th>Assets</th>
                                            <th>Company Name</th>
                                            <th>Status</th>
                                            <th>Requested Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($allpartners as $partner)
                                            <tr>
                                                <td>{{ $partner->partner_email }}</td>
                                                <td>{{ $partner->domain }}</td>
                                                <td>
                                                    <a href="#" class="text-primary"
                                                        onclick="fetchAssets('{{ $partner->dark_logo }}', '{{ $partner->light_logo }}', '{{ $partner->favicon }}')"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#assetsModal">Logos/Favicon</a>
                                                </td>
                                                <td>{{ $partner->company_name }}</td>
                                                <td>{!! $partner->approved_by_admin == 1
                                                    ? '<span class="badge bg-success">Approved</span>'
                                                    : '<span class="badge bg-warning">Pending</span>' !!}</td>
                                                <td>{{ $partner->date }}</td>
                                                <td>
                                                    @if ($partner->approved_by_admin == 1)
                                                        <button type="button" title="Stop white labeling for this partner"
                                                            onclick="stopWL(this, '{{ $partner->id }}')"
                                                            class="btn btn-danger btn-sm btn-wave waves-effect waves-light">Stop</button>
                                                    @else
                                                        <button type="button" title="Approve Request"
                                                            onclick="approveWLReq(this, '{{ $partner->id }}')"
                                                            class="btn btn-success btn-sm btn-wave waves-effect waves-light">
                                                            <i class="bx bx-check fs-15 mr-3"></i>Approve
                                                        </button>
                                                        <button type="button"
                                                            title="Reject whitelabel request for this partner"
                                                            onclick="rejectWL(this, '{{ $partner->id }}')"
                                                            class="btn btn-danger btn-sm btn-wave waves-effect waves-light">Reject</button>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center">No records found</td>
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

    {{-- --------------------------Modals------------------------ --}}

    <div class="modal fade" id="assetsModal" tabindex="-1" aria-labelledby="exampleModalLgLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">White label Logos/Favicon</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <div class="row py-4">
                        <div class="col-lg-4">
                            <h6 class="text-center">Dark Logo</h6>
                            <div id="dark_logo" class="text-center">
                                <img src="" alt="dark_logo" width="200">
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <h6 class="text-center">Light Logo</h6>
                            <div id="light_logo" class="text-center">
                                <img src="" alt="light_logo" width="200">
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <h6 class="text-center">Favicon</h6>
                            <div id="favicon" class="text-center">
                                <img src="" alt="favicon" width="200">
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

            function fetchAssets(darklogo, lightlogo, favicon) {
                $("#dark_logo img").attr('src', `{{ asset('storage') }}/uploads/whitelabeled/${darklogo}`);
                $("#light_logo img").attr('src', `{{ asset('storage') }}/uploads/whitelabeled/${lightlogo}`);
                $("#favicon img").attr('src', `{{ asset('storage') }}/uploads/whitelabeled/${favicon}`);
            }

            
            function approveWLReq(btn, id) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "Please add the requested domain to simUphish App directory and check if this domain has propagated to simUphish IP",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e6533c',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Approve'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $(btn).html("Please Wait...")
                        $.post({
                            url: '/admin/approve-whitelabel',
                            data: {
                                "rowId": id
                            },
                            success: function(res) {
                                $(btn).html("Approve")

                                checkResponse(res);

                            }
                        })
                    }
                })

                
            }

            function stopWL(btn, id) {

                Swal.fire({
                    title: 'Are you sure?',
                    text: "The whitelabeling for this partner will be stopped on this partner domain.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e6533c',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Stop'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $(btn).html("Please Wait...")
                        $.post({
                            url: '/admin/stop-whitelabel',
                            data: {
                                "rowId": id
                            },
                            success: function(res) {
                                $(btn).html("Approve")

                                checkResponse(res);

                            }
                        })
                    }
                })


             
            }

            function rejectWL(btn, id) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "Are you sure to reject this whitelabelling request?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e6533c',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Reject'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $(btn).html("Please Wait...")
                        $.post({
                            url: '/admin/reject-whitelabel',
                            data: {
                                "rowId": id
                            },
                            success: function(res) {
                                $(btn).html("Reject")

                                checkResponse(res);

                            }
                        })
                    }
                })

              
            }
        </script>
    @endpush

@endsection
