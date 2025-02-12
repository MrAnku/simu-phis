<div class="card custom-card shadow">
    <div class="card-header">
        <div class="card-title">
            Employee Info
        </div>
    </div>
    <div class="card-body p-0">
        <div class="d-flex align-items-center border-block-end-dashed p-3 flex-wrap">
            <div class="me-2 lh-1">
                <span class="avatar avatar-md avatar-rounded bg-primary-transparent">
                    {{ strtoupper(substr($employee->user_name, 0, 1)) }}
                </span>
            </div>
            <div class="flex-fill">
                <p class="mb-0 fs-20">{{ $employee->user_name }}</p>
                <p class="mb-0 text-muted fs-12">{{ $employee->user_email }}</p>
            </div>

        </div>
        <div class="p-3 border-bottom border-block-end-dashed">

            <p class="mb-2 text-muted">
            <div class="me-2 lh-1 d-flex align-items-center">
                <div class="me-2">
                    <span class="avatar avatar-xs avatar-rounded">
                        <i class='bx bx-user fs-18 text-muted'></i>
                    </span>
                </div>
                <div>
                    <span class="fw-semibold text-default">Name : </span>
                    {{ $employee->user_name }}
                </div>
            </div>
            </p>
            <p class="mb-2 text-muted">
            <div class="me-2 lh-1 d-flex align-items-center">
                <div class="me-2">
                    <span class="avatar avatar-xs avatar-rounded">
                        <i class='bx bx-envelope fs-18 text-muted'></i>
                    </span>
                </div>
                <div>
                    <span class="fw-semibold text-default">Email : </span>
                    {{ $employee->user_email }}
                </div>
            </div>
            </p>
            <p class="mb-2 text-muted">
            <div class="me-2 lh-1 d-flex align-items-center">
                <div class="me-2">
                    <span class="avatar avatar-xs avatar-rounded">
                        <i class='bx bxl-whatsapp fs-18 text-muted'></i>
                    </span>
                </div>
                <div>
                    <span class="fw-semibold text-default">WhatsApp : </span>{{ $employee->whatsapp ?? '--' }}
                </div>
            </div>
            </p>
            <p class="mb-2 text-muted">
            <div class="me-2 lh-1 d-flex align-items-center">
                <div class="me-2">
                    <span class="avatar avatar-xs avatar-rounded">
                        <i class='bx bx-briefcase fs-18 text-muted'></i>
                    </span>
                </div>
                <div>
                    <span class="fw-semibold text-default">Job Title : </span>{{ $employee->job_title ?? '--' }}
                </div>
            </div>
            </p>

        </div>

    </div>


    <div class="card-footer">
        <div class="row">
            <div class="col-lg-4">
                <div class="card custom-card shadow">
                    <div class="card-body">
                        <div class="d-flex align-items-top">
                            <div class="me-3">
                                <span class="avatar avatar-md p-2 bg-danger">

                                    <i class="ri-cursor-line fs-20"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <div class="d-flex mb-1 align-items-top justify-content-between">
                                    <h5 class="fw-semibold mb-0 lh-1">
                                        {{ $employee->campaigns->sum('payload_clicked') }}
                                    </h5>

                                </div>
                                <p class="mb-0 fs-10 op-7 text-muted fw-semibold">AVERAGE CLICKS</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card custom-card shadow">
                    <div class="card-body">
                        <div class="d-flex align-items-top">
                            <div class="me-3">
                                <span class="avatar avatar-md p-2 bg-secondary">

                                    <i class="ri-mail-send-line fs-20"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <div class="d-flex mb-1 align-items-top justify-content-between">
                                    <h5 class="fw-semibold mb-0 lh-1">
                                        {{ $employee->campaigns->count() }}
                                    </h5>

                                </div>
                                <p class="mb-0 fs-10 op-7 text-muted fw-semibold">TOTAL CAMPAIGNS</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card custom-card shadow">
                    <div class="card-body">
                        <div class="d-flex align-items-top">
                            <div class="me-3">
                                <span class="avatar avatar-md p-2 bg-success">
                                    <i class="ri-presentation-line fs-20"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <div class="d-flex mb-1 align-items-top justify-content-between">
                                    <h5 class="fw-semibold mb-0 lh-1">
                                        {{ $employee->assignedTrainings->count() }}
                                    </h5>

                                </div>
                                <p class="mb-0 fs-10 op-7 text-muted fw-semibold">
                                    TOTAL TRAININGS</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
