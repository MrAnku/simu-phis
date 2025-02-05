@extends('admin.layouts.app')

@section('title', 'Encyclopedia | simUphish')

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
                                Manage Encyclopedia
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="datatable-basic" class="table table-bordered text-nowrap w-100">
                                    <thead>
                                        <tr>
                                            <th>Sl</th>
                                            <th>Title</th>
                                            <th>Content</th>
                                            <th>File</th>
                                            <th>Featured</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                       @forelse($data as $key => $item)
                                        <tr>
                                             <td>{{ $key+1 }}</td>
                                             <td>{{ $item->title }}</td>
                                             <td>{{ \Illuminate\Support\Str::words($item->content, 20, '...') }}</td>
                                             <td><a href="{{ $item->file }}" class="text-primary text-decoration-underline" target="_blank">View File</a></td>
                                             <td>{{ $item->featured == 1 ? 'Yes' : 'No' }}</td>
                                             <td>
                                                <button class="btn btn-icon btn-primary-transparent rounded-pill btn-wave waves-effect waves-light" data-bs-toggle="modal"
                                                data-bs-target="#editEncycloModal" onclick="editData({{ $item->id }})"> <i class="ri-pencil-line"></i> </button>

                                                  <button class="btn btn-icon btn-danger-transparent rounded-pill btn-wave waves-effect waves-light" onclick="deleteNotice({{ $item->id }})"> <i class="ri-delete-bin-line"></i> </button>
                                                  
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
        <form action="{{ route('admin.add-encyclo') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label for="title" class="form-label">Title</label>
                <input type="text" class="form-control" id="title" name="title" placeholder="Enter title" required>
            </div>
            <div class="mb-3">
                <label for="content" class="form-label">Content</label>
                <textarea class="form-control" id="content" name="content" rows="7" required></textarea>
            </div>
            <div class="mb-3">
                <label for="file" class="form-label">File</label>
                <input type="file" class="form-control" id="file" name="file" required>
            </div>
            <div class="mb-3">
                <label for="featured" class="form-label">Featured</label>
                <select class="form-select" id="featured" name="featured" required>
                    <option value="0">No</option>
                    <option value="1">Yes</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Add</button>
        </form>
    </x-modal>

    <x-modal id="editEncycloModal" size="modal-lg" heading="Edit Data">
        <form action="{{ route('admin.update-encyclo') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label for="title" class="form-label">Title</label>
                <input type="text" class="form-control" id="editTitle" name="title" placeholder="Enter title" required>
                <input type="hidden" name="encyclo_id" id="encyclo_id">
            </div>
            <div class="mb-3">
                <label for="content" class="form-label">Content</label>
                <textarea class="form-control" id="editContent" name="content" rows="7" required></textarea>
            </div>
            <div class="mb-3">
                <span class="badge bg-primary-transparent" id="oldFileName"></span>
                <span style="cursor: pointer;" onclick="deleteOldFile(this)"><i class='text-danger fs-22 bx bx-x-circle'></i></span>
            </div>
            <div class="mb-3" style="display: none;">
                <label for="file" class="form-label">File</label>
                <input type="file" class="form-control" id="editFile" name="file">
            </div>
            <div class="mb-3">
                <label for="featured" class="form-label">Featured</label>
                <select class="form-select" id="editFeatured" name="featured" required>
                    <option value="0">No</option>
                    <option value="1">Yes</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
        </form>
    </x-modal>



    {{-- ------------------------------Toasts---------------------- --}}

    <x-toast />


    @push('newscripts')
        <script>
            function deleteNotice(id) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You want to delete!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post({
                            url: "/admin/encyclopedia/delete",
                            data: {
                                id: id
                            },
                            success: function(response) {
                                if (response.status == 1) {
                                    Swal.fire(
                                        'Deleted!',
                                        'Data has been deleted.',
                                        'success'
                                    )
                                    location.reload();
                                } else {
                                    Swal.fire(
                                        'Error!',
                                        'Something went wrong.',
                                        'error'
                                    )
                                }
                            }
                        })
                    }
                })
            }

            function editData(id) {
                $.get({
                    url: "/admin/encyclopedia/" + id,
                    success: function(response) {
                        console.log(response);
                        $('#encyclo_id').val(response.id);
                        $('#editTitle').val(response.title);
                        $('#editContent').val(response.content);
                        $('#editFeatured').val(response.featured);
                        $('#oldFileName').html(response.file.split('/').pop());
                    }
                })
            }

            function deleteOldFile(btn) {
                $(btn).parent().hide();
                $(btn).parent().next().show();
            }
        </script>
    @endpush

@endsection
