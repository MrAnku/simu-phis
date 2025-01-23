@extends('admin.layouts.app')

@section('title', 'Deal Registrations | simUphish')

@section('main-content')

    <div class="main-content app-content">
        <div class="container-fluid">
            

            <div class="row">
                <div class="col-xl-12">
                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">
                                Deal Registrations
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="datatable-basic" class="table table-bordered text-nowrap w-100">
                                    <thead>
                                        <tr>
                                            <th>Sl</th>
                                            <th>Company</th>
                                            <th>Email</th>
                                            <th>Name</th>
                                            <th>Phone</th>
                                            <th>Website</th>
                                            <th>Partner</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($registrations as $reg)

                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $reg->company }}</td>
                                            <td>{{ $reg->email }}</td>
                                            <td>{{ $reg->first_name }} {{ $reg->last_name }}</td>
                                            <td>{{ $reg->phone }}</td>
                                            <td>{{ $reg->website }}</td>
                                            <td>{{ $reg->partner->full_name }}</td>
                                            <td>
                                                @if ($reg->status == 'submitted')
                                                    <span class="badge bg-warning">Pending</span>
                                                @elseif($reg->status == 'approved')
                                                    <span class="badge bg-success">Approved</span>
                                                @else
                                                    <span class="badge bg-danger">Rejected</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($reg->status == 'submitted')
                                                <button class="btn btn-success btn-sm shadow-sm btn-wave waves-effect waves-light"
                                                    onclick="confirmApprove(this, '{{ $reg->id }}')">Approve</button>
                                                <button class="btn btn-danger btn-sm shadow-sm btn-wave waves-effect waves-light"
                                                    onclick="rejectApproval(this, '{{ $reg->id }}')">Reject</button>
                                                @endif

                                                
                                            </td>
                                        </tr>
                                            
                                        @empty
                                        <td>
                                            <td colspan="9" class="text-center">No records found</td>
                                        </td>
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

    


    {{-- ------------------------------Toasts---------------------- --}}

    <x-toast />

    

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
                        res.msg,
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
                    text: "After confirmation the company will be added to the partner's account.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e6533c',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Approve'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $(btn).html("Please Wait...")
                        $.post({
                            url: '/admin/deal-registrations/approve',
                            data: {
                                "id": id
                            },
                            success: function(res) {
                                $(btn).html("Approve")

                                checkResponse(res);

                            }
                        })
                    }
                })


            }

            

          

            function rejectApproval(btn, id) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "This Registration will be rejected.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e6533c',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Reject'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $(btn).html("Please Wait...")
                        $.post({
                            url: '/admin/deal-registrations/reject',
                            data: {
                                "id": id
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
