@extends('admin.layouts.app')

@section('title', 'WhatsApp | simUphish')

@section('main-content')

    <div class="main-content app-content">
        <div class="container-fluid">


            <div class="row">
                <div class="col-xl-12">
                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">
                                Change Number Requests
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="datatable-basic" class="table table-bordered text-nowrap w-100">
                                    <thead>
                                        <tr>
                                            <th>Sl</th>
                                            <th>Partner Name</th>
                                            <th>Partner Email</th>
                                            <th>Requested WhatsApp No</th>
                                            <th>Request Date</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($requests as $index => $request)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ $request->partnerDetail->full_name }}</td>
                                                <td>{{ $request->partnerDetail->email }}</td>
                                                <td>{{ $request->new_num }}</td>
                                                <td>{{ $request->created_at }}</td>
                                                <td>
                                                    @if ($request->status == 1)
                                                        <span class="badge bg-success">Approved</span>
                                                    @else
                                                        <span class="badge bg-warning">Pending</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($request->status == 0)
                                                        <button type="button" title="Approve Company"
                                                            onclick="approveReq(this, '{{ $request->id }}')"
                                                            class="btn btn-success btn-sm btn-wave waves-effect waves-light">Approve</button>
                                                        <button type="button" title="Reject Approval"
                                                            onclick="rejectReq(this, '{{ $request->id }}')"
                                                            class="btn btn-danger btn-sm btn-wave waves-effect waves-light">Reject</button>
                                                    @else
                                                        <button type="button" title="Delete Company"
                                                            onclick="deleteReq(this, '{{ $request->id }}')"
                                                            class="btn btn-danger btn-sm btn-wave waves-effect waves-light">Delete</button>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center">No records found</td>
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
            function approveReq(btn, id) {

                Swal.fire({
                    title: 'Approve WhatsApp Number Change Request',
                    html: `
        <label for="token">Enter new token</label>
        <input id="token" type="text" class="swal2-input" placeholder="xxxxxxxxx">
    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e6533c',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Approve',
                    preConfirm: () => {
                        const token = document.getElementById('token').value;

                        if (!token) {
                            Swal.showValidationMessage('Please enter the new token');
                            return false; // Prevent submission if validation fails.
                        }

                        return {
                            token
                        }; // Return data to `then` block.
                    }
                }).then((result) => {
                    if (result.isConfirmed) {

                        console.log(result)
                        $(btn).html("Please Wait...");
                        $.post({
                            url: '/admin/whatsapp/approve',
                            data: {
                                id: id,
                                token: result.value.token, // Get token
                            },
                            success: function(res) {
                                $(btn).html("Approve");
                                checkResponse(res);
                            },
                            error: function(err) {
                                console.error(err);
                                Swal.fire('Error', 'Something went wrong!', 'error');
                                $(btn).html("Approve");
                            }
                        });
                    }
                });



            }

            function rejectCompany(btn, id) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "Are you sure for rejecting this company approval.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e6533c',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Reject'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $(btn).html("Please Wait...")
                        $.post({
                            url: '/admin/companies/reject',
                            data: {
                                "companyId": id
                            },
                            success: function(res) {
                                $(btn).html("Reject")

                                checkResponse(res);

                            }
                        })
                    }
                })


            }

            function deleteCompany(btn, id) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "Are you sure to delete this company.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e6533c',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Delete'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $(btn).html("Please Wait...")
                        $.post({
                            url: '/admin/companies/delete',
                            data: {
                                "companyId": id
                            },
                            success: function(res) {
                                $(btn).html("Delete")

                                checkResponse(res);

                            }
                        })
                    }
                })


            }
        </script>

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
        </script>
    @endpush

@endsection
