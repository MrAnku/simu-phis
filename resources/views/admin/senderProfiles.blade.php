@extends('admin.layouts.app')

@section('title', 'Sender Profiles | simUphish')

@section('main-content')

    <div class="main-content app-content">
        <div class="container-fluid py-3">

            <div class="d-flex justify-content-between">
                <div>
                    <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal"
                        data-bs-target="#newSenderProfileModal">New Sender Profile</button>
                </div>
            </div>


            <div class="row">
                <div class="col-xl-12">
                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">
                                Manage Profiles
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="allProfiles" class="table table-bordered text-nowrap w-100">
                                    <thead>
                                        <tr>
                                            <th>Profile Name</th>
                                            <th>Display Name & Address</th>
                                            <th>Profile Type</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                        @forelse ($senderProfiles as $senderProfile)
                                            <tr>
                                                <td>{{ $senderProfile->profile_name ?? 'N/A' }}</td>
                                                <td>
                                                    <strong>{{ $senderProfile->from_name ?? 'N/A' }}</strong> |
                                                    <i>{{ $senderProfile->from_email ?? 'N/A' }}</i>
                                                </td>
                                                <td>
                                                    @if ($senderProfile->company_id == 'default')
                                                        {{ 'simUphish Managed' }}
                                                    @else
                                                        {{ 'By Company' }}
                                                    @endif
                                                </td>
                                                <td>

                                                    <span class="text-secondary mx-1"
                                                        onclick="fetchSenderProfileData(`{{ $senderProfile->id }}`)"
                                                        role="button" data-bs-target="#editSenderProfileModal"
                                                        data-bs-toggle="modal"><i class="bx bx-pencil fs-4"></i>
                                                    </span>

                                                    <span class="text-danger mx-1"
                                                        onclick="deleteSenderProfile(`{{ $senderProfile->id }}`)"
                                                        role="button"><i class="bx bx-trash fs-4"></i></span>

                                                </td>
                                            </tr>
                                        @empty
                                            <div class="col-lg-6">
                                                No records found
                                            </div>
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

    <!-- new Sender Profile modal -->
    <div class="modal fade" id="newSenderProfileModal" tabindex="-1" aria-labelledby="exampleModalLgLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Add New Profile</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('admin.senderprofile.add') }}" method="POST">
                        @csrf
                        <div class="my-1">
                            <label for="input-label" class="form-label">Profile Name<sup class="text-danger">*</sup></label>
                            <input type="text" class="form-control" name="pName"
                                placeholder="Lookalike domain Facebook" required>
                        </div>
                        <div class="my-1">
                            <label for="input-label" class="form-label">From Address Name<sup
                                    class="text-danger">*</sup></label>
                            <input type="text" class="form-control" name="from_name" placeholder="john doe" required>
                        </div>
                        <div class="my-1">
                            <label for="input-label" class="form-label">From Address Email<sup
                                    class="text-danger">*</sup></label>
                            <input type="email" class="form-control" name="from_email" placeholder="john@domain.com"
                                required>
                        </div>
                        <div class="my-1">
                            <label for="input-label" class="form-label">SMTP Host<sup class="text-danger">*</sup></label>
                            <input type="text" class="form-control" name="smtp_host" placeholder="mail.domain.com"
                                required>
                        </div>
                        <div class="my-1">
                            <label for="input-label" class="form-label">SMTP Username<sup
                                    class="text-danger">*</sup></label>
                            <input type="email" class="form-control" name="smtp_username" placeholder="user@domain.com"
                                required>
                        </div>
                        <div class="my-1">
                            <label for="input-label" class="form-label">SMTP Password<sup
                                    class="text-danger">*</sup></label>
                            <input type="password" class="form-control" name="smtp_password" placeholder="Password"
                                required>
                        </div>
                        <div class="my-1">
                            <button type="submit" class="btn btn-primary mt-3 btn-wave waves-effect waves-light">Add
                                Profile</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>


    <!-- edit Sender Profile modal -->
    <div class="modal fade" id="editSenderProfileModal" tabindex="-1" aria-labelledby="exampleModalLgLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Edit Sender Profile</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('admin.senderprofile.update') }}" method="post">
                        @csrf
                        <div class="my-1">
                            <label for="input-label" class="form-label">Profile Name<sup
                                    class="text-danger">*</sup></label>
                            <input type="text" class="form-control" name="pName"
                                placeholder="Lookalike domain Facebook" required>
                            <input type="hidden" name="profile_id">
                        </div>
                        <div class="my-1">
                            <label for="input-label" class="form-label">From Address Name<sup
                                    class="text-danger">*</sup></label>
                            <input type="text" class="form-control" name="from_name" placeholder="john doe" required>
                        </div>
                        <div class="my-1">
                            <label for="input-label" class="form-label">From Address Email<sup
                                    class="text-danger">*</sup></label>
                            <input type="email" class="form-control" name="from_email" placeholder="john@domain.com"
                                required>
                        </div>
                        <div class="my-1">
                            <label for="input-label" class="form-label">SMTP Host<sup class="text-danger">*</sup></label>
                            <input type="text" class="form-control" name="smtp_host" placeholder="mail.domain.com"
                                required>
                        </div>
                        <div class="my-1">
                            <label for="input-label" class="form-label">SMTP Username<sup
                                    class="text-danger">*</sup></label>
                            <input type="email" class="form-control" name="smtp_username"
                                placeholder="user@domain.com" required>
                        </div>
                        <div class="my-1">
                            <label for="input-label" class="form-label">SMTP Password<sup
                                    class="text-danger">*</sup></label>
                            <input type="password" class="form-control" name="smtp_password" placeholder="Password"
                                required>
                        </div>
                        <div class="my-1">
                            <button type="submit" class="btn btn-primary mt-3 btn-wave waves-effect waves-light">Update
                                Profile</button>
                        </div>
                    </form>

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
        <style>
            #allProfiles thead th,
            #allProfiles tbody td {
                text-align: center !important;
            }
        </style>
    @endpush

    @push('newscripts')
        <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>
        <script src="https://cdn.datatables.net/responsive/2.3.0/js/dataTables.responsive.min.js"></script>
        <script>
            $('#allProfiles').DataTable({
                language: {
                    searchPlaceholder: 'Search...',
                    sSearch: '',
                },
                "pageLength": 10,
                // scrollX: true
            });
        </script>

        <script>
            function deleteSenderProfile(id) {
                Swal.fire({
                    title: "{{ __('Are you sure?') }}",
                    text: "{{ __('Are you sure that you want to delete this Sender Profile?') }}",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e6533c',
                    cancelButtonColor: '#d33',
                    confirmButtonText: "{{ __('Delete') }}",
                    cancelButtonText: "{{ __('Cancel') }}"
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post({
                            url: 'admin/delete-sender-profile',
                            data: {
                                deleteSenderProfile: '1',
                                senderProfileId: id
                            },
                            success: function(res) {

                                // console.log(res)
                                window.location.href = window.location.href;
                            }
                        })
                    }
                })
            }

            function fetchSenderProfileData(id) {
                $.get({
                    url: `get-sender-profile/${id}`,
                    success: function(res) {

                        console.log(res)

                        if (res.status === 1) {
                            $('#editSenderProfileModal input[name="profile_id"]').val(id);
                            $('#editSenderProfileModal input[name="pName"]').val(res.data.profile_name);
                            $('#editSenderProfileModal input[name="from_name"]').val(res.data.from_name);
                            $('#editSenderProfileModal input[name="from_email"]').val(res.data.from_email);
                            $('#editSenderProfileModal input[name="smtp_host"]').val(res.data.host);
                            $('#editSenderProfileModal input[name="smtp_username"]').val(res.data.username);
                            $('#editSenderProfileModal input[name="smtp_password"]').val(res.data.password);
                        } else {
                            Swal.fire({
                                title: "{{ __('Something went wrong!') }}",
                                icon: 'error',
                                confirmButtonText: "{{ __('OK') }}"
                            })
                        }
                    }
                })
            }
        </script>
    @endpush

@endsection
