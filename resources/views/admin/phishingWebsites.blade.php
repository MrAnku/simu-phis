@extends('admin.layouts.app')

@section('title', 'Phishing Websites | simUphish')

@section('main-content')

    <div class="main-content app-content">
        <div class="container-fluid mt-4">

            <div class="d-flex justify-content-between">
                <div>
                    <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal"
                        data-bs-target="#newWebsiteModal">New Website</button>
                </div>

            </div>


            <div class="row">
                <div class="col-xl-12">
                    <div class="card custom-card">
                        <div class="card-header d-flex justify-content-between">
                            <div class="card-title">
                                Manage Phishing Websites
                            </div>

                            <div
                                style="
                            display: flex;
                            align-items: center;
                            gap: 10px;
                        ">

                                <input type="text" class="form-control" id="templateSearch" placeholder="Search website">
                                <i class='bx bx-search fs-23'></i>
                            </div>
                        </div>
                        <div class="card-body">

                            <div class="row">
                                @forelse ($phishingWebsites as $phishingWebsite)
                                    <div class="col-lg-6 website_templates">
                                        <div class="card custom-card">
                                            <div class="card-header">
                                                <div class="d-flex align-items-center w-100">

                                                    <div class="">
                                                        <div class="fs-15 fw-semibold">{{ $phishingWebsite->name }}</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card-body htmlPhishingGrid">
                                                <iframe class="phishing-iframe"
                                                    src="{{ Storage::url('uploads/phishingMaterial/phishing_websites/' . $phishingWebsite->file) }}"></iframe>
                                            </div>
                                            <div class="card-footer">
                                                <div class="d-flex justify-content-center">
                                                    <a href="http://{{Str::random(6)}}.{{ $phishingWebsite->domain }}/{{ Str::random(10) }}?v=r&c={{Str::random(10)}}&p={{$phishingWebsite->id}}&l={{Str::slug($phishingWebsite->name)}}"
                                                        target="_blank"
                                                        class="btn mx-1 btn-outline-primary btn-wave waves-effect waves-light">View</a>

                                                    <button type="button"
                                                        onclick="deleteWebsite(`{{ $phishingWebsite->id }}`, `{{ $phishingWebsite->file }}`)"
                                                        target="_blank"
                                                        class="btn mx-1 btn-outline-danger btn-wave waves-effect waves-light">Delete</button>



                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-lg-6">
                                        No records found
                                    </div>
                                @endforelse




                            </div>

                           
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- -------------------Modals------------------------ --}}

    <!-- new website add -->
    <div class="modal fade" id="newWebsiteModal" tabindex="-1" aria-labelledby="exampleModalLgLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Add Website</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('admin.phishing.website.add') }}" method="post" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label for="input-label" class="form-label">Website name<sup class="text-danger">*</sup></label>
                            <input type="text" class="form-control" name="webName" placeholder="Template name" required>

                        </div>
                        <div class="mb-3">
                            <label for="formFile" class="form-label">Website File<sup class="text-danger">*</sup></label>
                            <input class="form-control" type="file" name="webFile" accept=".html" required>

                        </div>
                        <div class="mb-3">
                            <label for="input-label" class="form-label">Website Domain<sup
                                    class="text-danger">*</sup></label>
                            <div class="d-flex">

                                <select class="form-select" aria-label="Default select example" name="domain">
                                    <option value="app-notifications.net">app-notifications.net
                                    </option>
                                </select>
                            </div>

                        </div>
                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary mt-3 btn-wave waves-effect waves-light">Add
                                Website</button>
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
        <style>
            .htmlPhishingGrid {
                overflow: scroll;
                border: 1px solid #8080804a;
                border-radius: 6px;
                max-height: 300px;
                /* filter: brightness(0.9); */

            }

            .phishing-iframe {
                width: 100%;
                height: 300px;
                margin: 0;
                padding: 0;
            }

            #displayMailBodyContent iframe,
            .htmlPhishingGrid iframe {
                width: 100%;
                height: 100vh;
                border: none;
            }
        </style>
    @endpush

    @push('newscripts')
        <script>
            function deleteWebsite(webId, filename) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "Are you sure you want to delete this website?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e6533c',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Delete'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post({
                            url: 'admin/delete-website',
                            data: {
                                websiteid: webId,
                                filename: filename
                            },
                            success: function(res) {
                                // console.log(res)
                                window.location.href = window.location.href;
                            }
                        })
                    }
                })


            }

            // Event listener for input field change
            $('#templateSearch').on('input', function() {
                var searchValue = $(this).val().toLowerCase(); // Get the search value and convert it to lowercase

                // Loop through each template card
                $('.website_templates').each(function() {
                    var templateName = $(this).find('.fw-semibold').text()
                        .toLowerCase(); // Get the template name and convert it to lowercase

                    // If the template name contains the search value, show the card; otherwise, hide it
                    if (templateName.includes(searchValue)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });
        </script>
    @endpush

@endsection
