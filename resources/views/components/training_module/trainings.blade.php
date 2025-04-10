<div class="row">
    @forelse ($trainingModules as $trainingModule)
        <div class="col-lg-6 t_modules">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="d-flex align-items-center w-100">

                        <div class="">
                            <div class="fs-15 fw-semibold">{{ $trainingModule->name }}</div>
                        </div>
                    </div>
                </div>
                <div class="card-body htmlPhishingGrid">
                    <img class="trainingCoverImg"
                        src="{{ Storage::url('uploads/trainingModule/' . $trainingModule->cover_image) }}" />
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-center">
                        @if (auth('company')->check())
                            <a href="{{ '/admin/training-preview/' . base64_encode($trainingModule->id) }}"
                                target="_blank"
                                class="btn mx-1 btn-outline-primary btn-wave waves-effect waves-light">View</a>

                            @if ($trainingModule->company_id !== 'default')
                                <button type="button"
                                    onclick="deleteTrainingModule(`{{ $trainingModule->id }}`, `{{ $trainingModule->cover_image }}`)"
                                    class="btn mx-1 btn-outline-danger btn-wave waves-effect waves-light">{{ __('Delete') }}</button>

                                <button type="button"
                                    onclick="{{ $trainingModule->training_type == 'gamified' ? 'editGamifiedTrainingModule' : 'editTrainingModule' }}(`{{ $trainingModule->id }}`)"
                                    class="btn mx-1 btn-outline-primary btn-wave waves-effect waves-light"
                                    data-bs-toggle="modal"
                                    data-bs-target="{{ $trainingModule->training_type == 'gamified' ? '#editGamifiedTrainingModuleModal' : '#editTrainingModuleModal' }}">{{ __('Edit') }}</button>
                            @endif
                        @else
                            <a href="{{ '/admin/training-preview/' . base64_encode($trainingModule->id)  }}"
                                target="_blank"
                                class="btn mx-1 btn-outline-primary btn-wave waves-effect waves-light">{{ __('View') }}</a>
                            <button type="button"
                                onclick="deleteTrainingModule(`{{ $trainingModule->id }}`, `{{ $trainingModule->cover_image }}`)"
                                class="btn mx-1 btn-outline-danger btn-wave waves-effect waves-light">{{ __('Delete') }}</button>

                            <button type="button"
                                onclick="{{ $trainingModule->training_type == 'gamified' ? 'editGamifiedTrainingModule' : 'editTrainingModule' }}(`{{ $trainingModule->id }}`)"
                                class="btn mx-1 btn-outline-primary btn-wave waves-effect waves-light"
                                data-bs-toggle="modal"
                                data-bs-target="{{ $trainingModule->training_type == 'gamified' ? '#editGamifiedTrainingModuleModal' : '#editTrainingModuleModal' }}">{{ __('Edit') }}</button>
                        @endif




                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-lg-6">
            {{ __('No records found') }}
        </div>
    @endforelse




</div>
