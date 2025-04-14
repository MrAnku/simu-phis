@extends('admin.layouts.app')

@section('title', 'Training Game | simUphish')

@section('main-content')

    <div class="main-content app-content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between mt-3">
                <div>
                    <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal"
                        data-bs-target="#addNewData">Add New</button>
                </div>
               
            </div>

            <div class="row">
                <div class="col-xl-12">
                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">
                                Manage Training Game
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="datatable-basic" class="table table-bordered text-nowrap w-100">
                                    <thead>
                                        <tr>
                                            <th>Sl</th>
                                            <th>Game Name</th>
                                            <th>Slug</th>
                                            <th>Company ID</th>
                                            <th>Cover Image</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                       @forelse($data as $key => $item)
                                        <tr>
                                             <td>{{ $key+1 }}</td>
                                             <td>{{ $item->name }}</td>
                                             <td>{{ $item->slug }}</td>
                                             <td>{{ $item->company_id }}</td>
                                             <td>
                                                <a href="{{ $item->cover_image ? Storage::disk('public')->url('uploads/trainingGame/' . $item->cover_image) : Storage::disk('public')->url('uploads/trainingGame/default.jpg') }}" class="text-primary text-decoration-underline" target="_blank">View File</a>
                                            </td>
                                            
                                             <td>
                                                  <button class="btn btn-icon btn-danger-transparent rounded-pill btn-wave waves-effect waves-light" onclick="deleteTrainingGame({{ $item->id }})"> <i class="ri-delete-bin-line"></i> </button>
                                                  
                                             </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="6" class="text-center">No Data Found</td>
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

    <x-modal id="addNewData" size="modal-lg" heading="Add New">
        <form action="{{ route('admin.addTrainingGame') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label for="name" class="form-label">Game Name</label>
                <input type="text" class="form-control" id="name" name="name" placeholder="Enter game name" required>
            </div>
            <div class="mb-3">
                <label for="slug" class="form-label">Slug</label>
                <input type="text" class="form-control" id="slug" name="slug" placeholder="Enter Slug" required>
            </div>
            <div class="mb-3">
                <label for="cover_image" class="form-label">Cover Image</label>
                <input type="file" class="form-control" id="cover_image" name="cover_image">
            </div>
            <button type="submit" class="btn btn-primary">Add</button>
        </form>
    </x-modal>

    {{-- ------------------------------Toasts---------------------- --}}

    <x-toast />


    @push('newscripts')
        <script>
            function deleteTrainingGame(id) {
                Swal.fire({
                    title: "{{ __('Are you sure?') }}",
                    text: "{{ __('You want to delete!') }}",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: "{{ __('Yes, delete it!') }}",
                    cancelButtonText: "{{ __('Cancel') }}"
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post({
                            url: "/admin/training-game/delete",
                            data: {
                                id: id
                            },
                            success: function(response) {
                                if (response.status == 1) {
                                    Swal.fire({
                                    title: "{{ __('Deleted!') }}",
                                    text: "{{ __('Data has been deleted.) }}",
                                    icon: 'success',
                                    confirmButtonText: "{{ __('OK') }}"
                                })
                                    location.reload();
                                } else {
                                    Swal.fire({
                                    title: "{{ __('Error!') }}",
                                    text: "{{ __('Something went wrong!') }}",
                                    icon: 'error',
                                    confirmButtonText: "{{ __('OK') }}"
                                } )
                                }
                            }
                        })
                    }
                })
            }
        </script>
    @endpush

@endsection
