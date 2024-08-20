@extends('admin.layouts.app')

@section('title', 'Companies | simUphish')

@section('main-content')

    <div class="main-content app-content">
        <div class="container-fluid">


            <div class="row">
                <div class="col-xl-12">
                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">
                                Company Approval Requests
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="datatable-basic" class="table table-bordered text-nowrap w-100">
                                    <thead>
                                        <tr>
                                            <th>Sl</th>
                                            <th>Company Name</th>
                                            <th>Company Email</th>
                                            <th>Full Name</th>
                                            <th>Employees</th>
                                            <th>Partner</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($all_companies as $index => $company)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ $company->company_name }}</td>
                                                <td>{{ $company->email }}</td>
                                                <td>{{ $company->full_name }}</td>
                                                <td>{{ $company->employees }}</td>
                                                <td>{{ $company->partner->full_name }} ({{ $company->partner->email }})</td>
                                                <td>
                                                    @if ($company->approved == 1)
                                                        <span class="badge bg-success">Approved</span>
                                                    @else
                                                        <span class="badge bg-warning">Pending</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($company->approved == 0)
                                                        <button type="button" title="Approve Company"
                                                            onclick="approveCompany(this, '{{ $company->id }}')"
                                                            class="btn btn-success btn-sm btn-wave waves-effect waves-light">Approve</button>
                                                        <button type="button" title="Reject Approval"
                                                            onclick="rejectCompany(this, '{{ $company->id }}')"
                                                            class="btn btn-danger btn-sm btn-wave waves-effect waves-light">Reject</button>
                                                    @else
                                                        <button type="button" title="Delete Company"
                                                            onclick="deleteCompany(this, '{{ $company->id }}')"
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
            function approveCompany(btn, id) {

                Swal.fire({
                    title: 'Are you sure?',
                    text: "After approval the company will be able to login and shoot campaign.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e6533c',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Approve'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $(btn).html("Please Wait...")
                        $.post({
                            url: '/admin/companies/approve',
                            data: {
                                "companyId": id
                            },
                            success: function(res) {
                                $(btn).html("Approve")

                                checkResponse(res);

                            }
                        })
                    }
                })


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
