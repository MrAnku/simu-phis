@extends('admin.layouts.app')

@section('title', 'New Agent Requests | simUphish')

@section('main-content')

    <div class="main-content app-content">
        <div class="container-fluid">


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
                                <table id="datatable-basic" class="table table-bordered text-nowrap w-100">
                                    <thead>
                                        <tr>
                                            <th>Sl</th>
                                            <th>Company</th>
                                            <th>Requested Agent Name</th>
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
                                                        onclick="viewPrompt(`{{ base64_encode($request->id) }}`)" class="btn btn-icon btn-primary-transparent rounded-pill btn-wave waves-effect waves-light">
                                                        <i class="ri-eye-line"></i> </button>
                                                    @if($request->status == '0')
                                                    <button data-bs-toggle="modal" data-bs-target="#approveAgentModal"
                                                        onclick="approveAgent(`{{ base64_encode($request->id) }}`)"
                                                        class="btn btn-icon btn-secondary-transparent rounded-pill btn-wave waves-effect waves-light">
                                                        <i class="ri-pencil-line"></i> </button>
                                                    @endif
                                                    

                                                    <button
                                                        class="btn btn-icon btn-danger-transparent rounded-pill btn-wave waves-effect waves-light">
                                                        <i class="ri-delete-bin-line"></i> </button>

                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center">No Data Found</td>
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

    <x-modal id="viewPromptModal" heading="Requested Prompt">
        <div>
            <div class="mb-3">
                <label for="agent-name" class="form-label fs-14 text-dark">Agent name</label>
                <input type="text" class="form-control" id="agent-name" placeholder="Enter agent name" disabled>
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
    </x-modal>

    <x-modal id="approveAgentModal" heading="Approve Agent">
        <form action="{{ route('admin.aivishing.approveagent') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="title" class="form-label">Agent Name</label>
                <input type="text" class="form-control" id="agent_name" name="agent_name" placeholder="Enter agent name" required>
                <input type="hidden" name="request_id" id="request_id">
            </div>
            <div class="mb-3">
                <label for="title" class="form-label">Agent Id</label>
                <input type="text" class="form-control" name="agent_id" placeholder="Enter agent id" required>
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
        </form>
    </x-modal>



    {{-- ------------------------------Toasts---------------------- --}}

    <x-toast />


    @push('newscripts')
        <script>
            function viewPrompt(id) {
                // console.log(id);
                $.get({
                    url: '/admin/ai-vishing/req-prompt/' + id,
                    success: function(data) {
                        // console.log(data);
                        $('#agent-name').val(data.agent_name);
                        $('#agent-prompt').val(data.prompt);
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

            function approveAgent(id){
                $.get({
                    url: '/admin/ai-vishing/req-prompt/' + id,
                    success: function(data) {
                        // console.log(data);
                        $('#agent_name').val(data.agent_name);
                        $('#request_id').val(data.id);
                        
                    }
                })
            }
        </script>


    @endsection
