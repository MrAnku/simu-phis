@extends('layouts.app')

@section('title', 'Sender Profiles - Phishing awareness training program')

@section('main-content')

    <div class="main-content app-content">
        <div class="container-fluid py-3">

            <div class="d-flex justify-content-between">
                <div>
                    <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal"
                        data-bs-target="#newSenderProfileModal">{{ __('New Sender Profile') }}</button>
                </div>
            </div>


            <div class="row">
                <div class="col-xl-12">
                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">
                                {{ __('Manage Profiles') }}
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="allProfiles" class="table table-bordered text-nowrap w-100">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Profile Name') }}</th>
                                            <th>{{ __('Display Name & Address') }}</th>
                                            <th>{{ __('Profile Type') }}</th>
                                            <th>{{ __('Actions') }}</th>
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
                                                        {{ $companyName . ' Managed' }}
                                                    @else
                                                        {{ 'Custom' }}
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($senderProfile->company_id !== 'default')
                                                        <span class="text-secondary mx-1"
                                                            onclick="fetchSenderProfileData(`{{ $senderProfile->id }}`)"
                                                            role="button" data-bs-target="#editSenderProfileModal"
                                                            data-bs-toggle="modal"><i class="bx bx-pencil fs-4"></i>
                                                        </span>

                                                        <span class="text-danger mx-1"
                                                            onclick="deleteSenderProfile(`{{ $senderProfile->id }}`)"
                                                            role="button"><i class="bx bx-trash fs-4"></i></span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <div class="col-lg-6">
                                                {{ __('No records found') }}
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
                    <h6 class="modal-title">{{ __('Add New Profile') }}</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('senderprofile.add') }}" method="POST">
                        @csrf
                        <div class="my-1">
                            <label for="input-label" class="form-label">{{ __('Profile Name') }}<sup class="text-danger">*</sup></label>
                            <input type="text" class="form-control" name="pName"
                                placeholder="{{ __('Lookalike domain Facebook') }}" required>
                        </div>
                        <div class="my-1">
                            <label for="input-label" class="form-label">{{ __('From Address Name') }}<sup
                                    class="text-danger">*</sup></label>
                            <input type="text" class="form-control" name="from_name" placeholder="{{ __('john doe') }}" required>
                        </div>
                        <div class="my-1">
                            <label for="input-label" class="form-label">{{ __('From Address Email') }}<sup
                                    class="text-danger">*</sup></label>
                            <input type="email" class="form-control" name="from_email" placeholder="john@domain.com"
                                required>
                        </div>
                        <div class="my-1">
                            <label for="input-label" class="form-label">{{ __('SMTP Host') }}<sup class="text-danger">*</sup></label>
                            <input type="text" class="form-control" name="smtp_host" placeholder="mail.domain.com"
                                required>
                        </div>
                        <div class="my-1">
                            <label for="input-label" class="form-label">{{ __('SMTP Username') }}<sup
                                    class="text-danger">*</sup></label>
                            <input type="email" class="form-control" name="smtp_username" placeholder="user@domain.com"
                                required>
                        </div>
                        <div class="my-1">
                            <label for="input-label" class="form-label">{{ __('SMTP Password') }}<sup
                                    class="text-danger">*</sup></label>
                            <input type="password" class="form-control" name="smtp_password" placeholder="{{ __('Password') }}"
                                required>
                        </div>
                        <div class="my-1">
                            <button type="submit" class="btn btn-primary mt-3 btn-wave waves-effect waves-light">{{ __('Add Profile') }}</button>
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
                    <h6 class="modal-title">{{ __('Edit Sender Profile') }}</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{route('senderprofile.update')}}" method="post">
                        @csrf
                        <div class="my-1">
                            <label for="input-label" class="form-label">{{ __('Profile Name') }}<sup
                                    class="text-danger">*</sup></label>
                            <input type="text" class="form-control" name="pName"
                                placeholder="Lookalike domain Facebook" required>
                                <input type="hidden" name="profile_id">
                        </div>
                        <div class="my-1">
                            <label for="input-label" class="form-label">{{ __('From Address Name') }}<sup
                                    class="text-danger">*</sup></label>
                            <input type="text" class="form-control" name="from_name" placeholder="john doe" required>
                        </div>
                        <div class="my-1">
                            <label for="input-label" class="form-label">{{ __('From Address Email') }}<sup
                                    class="text-danger">*</sup></label>
                            <input type="email" class="form-control" name="from_email" placeholder="john@domain.com"
                                required>
                        </div>
                        <div class="my-1">
                            <label for="input-label" class="form-label">{{ __('SMTP Host') }}<sup class="text-danger">*</sup></label>
                            <input type="text" class="form-control" name="smtp_host" placeholder="mail.domain.com"
                                required>
                        </div>
                        <div class="my-1">
                            <label for="input-label" class="form-label">{{ __('SMTP Username') }}<sup
                                    class="text-danger">*</sup></label>
                            <input type="email" class="form-control" name="smtp_username"
                                placeholder="user@domain.com" required>
                        </div>
                        <div class="my-1">
                            <label for="input-label" class="form-label">{{ __('SMTP Password') }}<sup
                                    class="text-danger">*</sup></label>
                            <input type="password" class="form-control" name="smtp_password" placeholder="Password"
                                required>
                        </div>
                        <div class="my-1">
                            <button type="submit" class="btn btn-primary mt-3 btn-wave waves-effect waves-light">{{ __('Update Profile') }}</button>
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
                    <strong class="me-auto">{{ __('Success') }}</strong>
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
                    <strong class="me-auto">{{ __('Error') }}</strong>
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
                        <strong class="me-auto">{{ __('Error') }}</strong>
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
                    lengthMenu: "{{ __('Show') }} _MENU_ {{ __('entries') }}",
                    info: "{{ __('Showing') }} _START_ {{ __('to') }} _END_ {{ __('of') }} _TOTAL_ {{ __('entries') }}",
                    infoEmpty: "{{ __('Showing 0 to 0 of 0 entries') }}",
                    infoFiltered: "({{ __('filtered from') }} _MAX_ {{ __('total entries') }})",
                    searchPlaceholder: "{{ __('Search...') }}",
                    sSearch: '',
                    paginate: {
                        next: "{{ __('Next') }}",
                        previous: "{{ __('Previous') }}"
                    },
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
                            url: '/delete-sender-profile',
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
                    url: `/get-sender-profile/${id}`,
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
