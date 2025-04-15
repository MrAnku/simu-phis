@extends('admin.layouts.app')

@section('title', 'New Agent Requests | simUphish')

@section('main-content')

    <div class="main-content app-content">
        <div class="container-fluid">
            <div class="d-flex gap-2 mt-3">
                <div>
                    <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal"
                        data-bs-target="#addNewAgentModal">Add New Agent</button>
                </div>
                <div>
                    <button type="button" class="btn btn-secondary mb-3" data-bs-toggle="modal"
                        data-bs-target="#allAgentsModal">All Agents</button>
                </div>

            </div>

            <div class="row">
                <div class="col-xl-12">
                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">
                                Manage New Agent Requests
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered text-nowrap w-100">
                                    <thead>
                                        <tr>
                                            <th>Sl</th>
                                            <th>Company</th>
                                            <th>Requested Agent Name</th>
                                            <th>Language</th>
                                            <th>Deepfake</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($all_requests as $request)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>
                                                    <div>
                                                        <div class="lh-1">
                                                            <span>{{ $request->company->company_name ?? 'N/A' }}</span>
                                                        </div>
                                                        <div class="lh-1">
                                                            <span
                                                                class="fs-11 text-muted">{{ $request->company->email ?? 'N/A' }}</span>
                                                        </div>
                                                    </div>

                                                </td>

                                                <td>{{ $request->agent_name }}</td>
                                                <td>{{ $request->language ?? '' }}</td>
                                                <td>
                                                    @if ($request->audio_file == null)
                                                        <span class="badge bg-danger-transparent">No</span>
                                                    @else
                                                        <span class="badge bg-success-transparent">Yes</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($request->status == '0')
                                                        <span class="badge bg-warning-transparent">Pending</span>
                                                    @else
                                                        <span class="badge bg-success-transparent">Approved</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <button data-bs-toggle="modal" data-bs-target="#viewPromptModal"
                                                        onclick="viewPrompt(`{{ base64_encode($request->id) }}`)"
                                                        class="btn btn-icon btn-primary-transparent rounded-pill btn-wave waves-effect waves-light">
                                                        <i class="ri-eye-line"></i> </button>


                                                    <button
                                                        onclick="deleteAgentRequest(`{{ base64_encode($request->id) }}`)"
                                                        class="btn btn-icon btn-danger-transparent rounded-pill btn-wave waves-effect waves-light">
                                                        <i class="ri-delete-bin-line"></i>
                                                    </button>





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

    <x-modal id="viewPromptModal" heading="Requested Agent">
        <div>
            <div class="mb-3">
                <label for="agent-name" class="form-label fs-14 text-dark">Agent name</label>
                <input type="text" class="form-control" id="agent-name" disabled>
            </div>
            <div class="mb-3">
                <label for="agent-lang" class="form-label fs-14 text-dark">Language</label>
                <input type="text" class="form-control" id="agent-lang" disabled>
            </div>
            <div class="mb-3">
                <label for="agent-prompt" class="form-label fs-14 text-dark">Prompt</label>
                <textarea class="form-control" id="agent-prompt" rows="6" disabled></textarea>
            </div>
            <div class="mb-3" id="deepfake-audio" style="display: none;">
                <div class="d-flex align-items-center gap-3">
                    <label for="deepfake_audio" class="form-label fs-14 text-dark">Deep fake audio</label>
                    <audio src="" controls=""></audio>
                </div>

            </div>
        </div>
        <div class="my-2">
            <form action="{{ route('admin.aivishing.approveagent') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="title" class="form-label">Agent Id<span class="text-danger">*</span></label>
                    <input type="hidden" id="agent_name" name="agent_name" required>
                    <input type="hidden" id="request_id" name="request_id" required>
                    <input type="text" class="form-control" name="agent_id" placeholder="Enter agent id" required>
                </div>
                <div class="text-end">
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>

            </form>
        </div>
    </x-modal>

    <x-modal id="addNewAgentModal" heading="Add New Agent">
        <form action="{{ route('admin.aivishing.newagent') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="title" class="form-label">Agent Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="agent_name" name="agent_name" placeholder="Enter agent name"
                    required>
            </div>
            <div class="mb-3">
                <label for="title" class="form-label">Agent Id <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="agent_id" placeholder="Enter agent id" required>
            </div>
            <div class="mb-3">
                <small class="text-muted">All companies can access this template by default</small>
            </div>
            <div class="text-end">
                <button type="submit" class="btn btn-primary">Submit</button>
            </div>

        </form>
    </x-modal>

    <x-modal id="allAgentsModal" size="modal-lg" heading="All Agents">
        <div class="table-responsive">
            <table class="table table-bordered text-nowrap w-100" id="allAgentsTable">
                <thead>
                    <tr>
                        <th>Agent Name</th>
                        <th>Agent Id</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($all_agents as $agent)
                        <tr>
                            <td>{{ $agent->agent_name }}</td>
                            <td>
                                <span class="badge bg-primary">
                                    {{ $agent->agent_id }}
                                </span>
                            </td>
                            <td>

                                <button type="submit" onclick="deleteAgent(`{{ base64_encode($agent->agent_id) }}`)"
                                    class="btn btn-icon btn-danger-transparent rounded-pill btn-wave waves-effect waves-light">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center">No Agents Found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-modal>



    {{-- ------------------------------Toasts---------------------- --}}

    <x-toast />

    @push('newcss')
        <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css">
        <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.3.0/css/responsive.bootstrap.min.css">
        <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.bootstrap5.min.css">
    @endpush


    @push('newscripts')
        <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>
        <script>
            $('#allAgentsTable').DataTable({
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
            function viewPrompt(id) {
                // console.log(id);
                $.get({
                    url: '/admin/ai-vishing/req-prompt/' + id,
                    success: function(data) {
                        console.log(data);
                        $('#agent-name').val(data.agent_name);
                        $('#agent-lang').val(data.language);
                        $('#agent-prompt').val(data.prompt);
                        $('#agent_name').val(data.agent_name);
                        $('#request_id').val(data.id);
                        if (data.status == 1) {
                            $('#viewPromptModal button[type="submit"]').hide();
                            $('#viewPromptModal input[name="agent_id"]').val(data.agent.agent_id).prop('disabled',
                                true);
                        }
                        if (data.audio_file) {
                            // console.log("Audio file:", data.audio_file);
                            let audioElement = $('#deepfake-audio audio');
                            audioElement.attr('src', `/storage/deepfake_audio/${data.audio_file}`);
                            audioElement[0].load(); // Ensure the audio updates
                            $('#deepfake-audio').show();
                        } else {
                            // console.log("No audio file found");
                            $('#deepfake-audio').hide();
                        }
                    }
                })
            }


            function deleteAgent(id) {
                Swal.fire({
                    title: "{{ __('Are you sure?') }}",
                    text: "{{ __('If any campaign is using this agent, it will be deleted too!') }}",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: "{{ __('Yes, delete it!') }}",
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post({
                            url: '/admin/ai-vishing/delete-agent',
                            type: 'POST',
                            data: {
                                agent_id: id
                            },
                            success: function(data) {
                                if (data.success) {
                                    Swal.fire({
                                        title: "{{ __('Deleted') }}",
                                        text: "{{ __('Agent has been deleted.') }}"
                                        icon: 'success',
                                        confirmButtonText: "{{ __('OK') }}"
                                    })
                                    setTimeout(() => {
                                        location.reload();
                                    }, 1000);
                                } else {
                                    Swal.fire({
                                        title: "{{ __('Error!') }}",
                                        text: "{{ __('Something went wrong.') }}"
                                        icon: 'error',
                                        confirmButtonText: "{{ __('OK') }}"
                                    })
                                }
                            }
                        })
                    }
                })
            }

            function deleteAgentRequest(id) {
                Swal.fire({
                    title: "{{ __('Are you sure?') }}",
                    text: "{{ __('You want to delete!') }}",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: "{{ __('Yes, delete it!') }}"
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post({
                            url: "/admin/ai-vishing/delete-agent-request",
                            data: {
                                id: id
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        title: "{{ __('Deleted!') }}",
                                        text: "{{ __('Agent has been deleted.') }}",
                                        icon: 'success',
                                        confirmButtonText: "{{ __('OK') }}"
                                    })
                                    location.reload();
                                } else {
                                    Swal.fire({
                                        title: "{{ __('Error!') }}",
                                        text: "{{ __('Something went wrong.') }}",
                                        icon: 'error',
                                        confirmButtonText: "{{ __('OK') }}"
                                    })
                                }
                            }
                        })
                    }
                })
            }
        </script>
    @endpush


@endsection
