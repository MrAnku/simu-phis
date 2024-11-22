@extends('layouts.app')

@section('title', 'Reporting - Phishing awareness training program')

@section('main-content')


    <body>


        <div class="page">

            <!-- Start::app-content -->
            <div class="main-content app-content">
                <div class="container-fluid mt-2">
                    <div class="row my-3">
                        <div class="card custom-card">
                            <div class="card-body p-0 product-checkout">
                                <ul class="nav nav-tabs tab-style-2 d-sm-flex d-block border-bottom border-block-end-dashed"
                                    id="myTab1" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="order-tab" data-bs-toggle="tab"
                                            data-bs-target="#order-tab-pane" type="button" role="tab"
                                            aria-controls="order-tab" aria-selected="true"><i
                                                class="ri-mail-line me-2 align-middle"></i>Emails Campaign Report</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="confirmed-tab" data-bs-toggle="tab"
                                            data-bs-target="#confirm-tab-pane" type="button" role="tab"
                                            aria-controls="confirmed-tab" aria-selected="false"><i
                                                class="ri-whatsapp-line me-2 align-middle"></i>WhatsApp Campaign
                                            Report</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="shipped-tab" data-bs-toggle="tab"
                                            data-bs-target="#shipped-tab-pane" type="button" role="tab"
                                            aria-controls="shipped-tab" aria-selected="false"><i
                                                class="ri-robot-line me-2 align-middle"></i>AI Campaign Report</button>
                                    </li>

                                </ul>
                                <div class="tab-content" id="myTabContent">
                                    <div class="tab-pane fade show active border-0 p-0" id="order-tab-pane" role="tabpanel"
                                        aria-labelledby="order-tab-pane" tabindex="0">
                                        <div class="p-4">
                                            {{-- <div class="main-content app-content">
                                                <div class="container-fluid mt-4"> --}}

                                            <div class="row my-3">
                                                <div class="col-xxl-4 col-xl-12">
                                                    <div class="card custom-card">
                                                        <div class="card-body">
                                                            <div class="row">
                                                                <div
                                                                    class="col-xxl-3 col-xl-2 col-lg-3 col-md-3 col-sm-4 col-4 d-flex align-items-center justify-content-center ecommerce-icon px-0">
                                                                    <span class="rounded p-3 bg-primary-transparent">
                                                                        <i class="bx bx-mail-send fs-4"></i>
                                                                    </span>
                                                                </div>
                                                                <div
                                                                    class="col-xxl-9 col-xl-10 col-lg-9 col-md-9 col-sm-8 col-8 px-0">
                                                                    <div class="mb-2">Phishing Emails Delivered</div>
                                                                    <div class="text-muted mb-1 fs-12">
                                                                        <span
                                                                            class="text-dark fw-semibold fs-20 lh-1 vertical-bottom">
                                                                            {{ $emails_delivered }} </span>
                                                                    </div>

                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-xxl-4 col-xl-12">
                                                    <div class="card custom-card">
                                                        <div class="card-body">
                                                            <div class="row">
                                                                <div
                                                                    class="col-xxl-3 col-xl-2 col-lg-3 col-md-3 col-sm-4 col-4 d-flex align-items-center justify-content-center ecommerce-icon px-0">
                                                                    <span class="rounded p-3 bg-secondary-transparent">
                                                                        <i class="bx bx-mail-send fs-4"></i>
                                                                    </span>
                                                                </div>
                                                                <div
                                                                    class="col-xxl-9 col-xl-10 col-lg-9 col-md-9 col-sm-8 col-8 px-0">
                                                                    <div class="mb-2">Active &amp; Recurring Campaigns
                                                                    </div>
                                                                    <div class="text-muted mb-1 fs-12">
                                                                        <span
                                                                            class="text-dark fw-semibold fs-20 lh-1 vertical-bottom">
                                                                            {{ count($camps) }} </span>
                                                                    </div>

                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-xxl-4 col-xl-12">
                                                    <div class="card custom-card">
                                                        <div class="card-body">
                                                            <div class="row">
                                                                <div
                                                                    class="col-xxl-3 col-xl-2 col-lg-3 col-md-3 col-sm-4 col-4 d-flex align-items-center justify-content-center ecommerce-icon px-0">
                                                                    <span class="rounded p-3 bg-warning-transparent">
                                                                        <i class="bx bx-award fs-4"></i>
                                                                    </span>
                                                                </div>
                                                                <div
                                                                    class="col-xxl-9 col-xl-10 col-lg-9 col-md-9 col-sm-8 col-8 px-0">
                                                                    <div class="mb-2">Training Assigned</div>
                                                                    <div class="text-muted mb-1 fs-12">
                                                                        <span
                                                                            class="text-dark fw-semibold fs-20 lh-1 vertical-bottom">
                                                                            {{ $training_assigned }} </span>
                                                                    </div>

                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="card custom-card">
                                                        <div class="card-header">
                                                            <div class="card-title">Employees Interaction</div>
                                                        </div>
                                                        <div class="card-body">
                                                            <div id="dashed-chart"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-xl-12">
                                                    <div class="card custom-card">
                                                        <div class="card-header">
                                                            <div class="card-title">
                                                                Campaign Reports
                                                            </div>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="table-responsive">
                                                                <table id="datatable-basic"
                                                                    class="table table-bordered text-nowrap w-100">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>Sl</th>
                                                                            <th>Campaign Name</th>
                                                                            <th>Status</th>
                                                                            <th>Scheduled Date</th>
                                                                            <th>Emails Delivered</th>
                                                                            <th>Emails Viewed</th>
                                                                            <th>Training Assigned</th>
                                                                            <th>Training Completed</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        @forelse ($camps as $camp)
                                                                            <tr>
                                                                                <td>{{ $loop->iteration }}</td>
                                                                                <td>
                                                                                    <a href="#" class="text-primary"
                                                                                        onclick="fetchCampaignDetails(`{{ $camp->campaign_id }}`)"
                                                                                        data-bs-toggle="modal"
                                                                                        data-bs-target="#campaignReportModal">{{ $camp->campaign_name }}</a>
                                                                                </td>
                                                                                <td>
                                                                                    @if ($camp->status == 'running' || $camp->status == 'completed')
                                                                                        <span
                                                                                            class="badge bg-success">{{ ucfirst($camp->status) }}</span>
                                                                                    @else
                                                                                        <span
                                                                                            class="badge bg-warning">{{ ucfirst($camp->status) }}</span>
                                                                                    @endif
                                                                                </td>
                                                                                <td>{{ $camp->scheduled_date }}</td>
                                                                                <td>
                                                                                    <div class="checkboxesIcon">
                                                                                        @if ($camp->emails_delivered == 0)
                                                                                            <span>{{ $camp->emails_delivered }}</span>
                                                                                            <i
                                                                                                class="bx bx-check-circle mx-2 fs-25 text-danger"></i>
                                                                                        @else
                                                                                            <span>{{ $camp->emails_delivered }}</span>
                                                                                            <i
                                                                                                class="bx bx-check-circle mx-2 fs-25 text-success"></i>
                                                                                        @endif

                                                                                    </div>

                                                                                </td>
                                                                                <td>
                                                                                    <div class="checkboxesIcon">
                                                                                        @if ($camp->emails_viewed == 0)
                                                                                            <span>{{ $camp->emails_viewed }}</span>
                                                                                            <i
                                                                                                class="bx bx-check-circle mx-2 fs-25 text-danger"></i>
                                                                                        @else
                                                                                            <span>{{ $camp->emails_viewed }}</span>
                                                                                            <i
                                                                                                class="bx bx-check-circle mx-2 fs-25 text-success"></i>
                                                                                        @endif

                                                                                    </div>
                                                                                </td>
                                                                                <td>
                                                                                    <div class="checkboxesIcon">
                                                                                        @if ($camp->training_assigned == 0)
                                                                                            <span>{{ $camp->training_assigned }}</span>
                                                                                            <i
                                                                                                class="bx bx-check-circle mx-2 fs-25 text-danger"></i>
                                                                                        @else
                                                                                            <span>{{ $camp->training_assigned }}</span>
                                                                                            <i
                                                                                                class="bx bx-check-circle mx-2 fs-25 text-success"></i>
                                                                                        @endif

                                                                                    </div>

                                                                                </td>
                                                                                <td>
                                                                                    <div class="checkboxesIcon">
                                                                                        @if ($camp->training_completed == 0)
                                                                                            <span>{{ $camp->training_completed }}</span>
                                                                                            <i
                                                                                                class="bx bx-check-circle mx-2 fs-25 text-danger"></i>
                                                                                        @else
                                                                                            <span>{{ $camp->training_completed }}</span>
                                                                                            <i
                                                                                                class="bx bx-check-circle mx-2 fs-25 text-success"></i>
                                                                                        @endif

                                                                                    </div>
                                                                                </td>
                                                                            </tr>
                                                                        @empty
                                                                            <tr>
                                                                                <td class="text-center" colspan="8">No
                                                                                    records found</td>
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
                                    <div class="tab-pane fade border-0 p-0" id="confirm-tab-pane" role="tabpanel"
                                        aria-labelledby="confirm-tab-pane" tabindex="0">
                                        <div class="p-4">
                                            <div class="row my-3">
                                                <div class="col-xxl-4 col-xl-12">
                                                    <div class="card custom-card">
                                                        <div class="card-body">
                                                            <div class="row">
                                                                <div
                                                                    class="col-xxl-3 col-xl-2 col-lg-3 col-md-3 col-sm-4 col-4 d-flex align-items-center justify-content-center ecommerce-icon px-0">
                                                                    <span class="rounded p-3 bg-primary-transparent">

                                                                        <i class='bx bxl-whatsapp fs-4'></i>
                                                                    </span>
                                                                </div>
                                                                <div
                                                                    class="col-xxl-9 col-xl-10 col-lg-9 col-md-9 col-sm-8 col-8 px-0">
                                                                    <div class="mb-2">WhatsApp Messages Delivered</div>
                                                                    <div class="text-muted mb-1 fs-12">
                                                                        <span
                                                                            class="text-dark fw-semibold fs-20 lh-1 vertical-bottom">
                                                                            {{ $msg_delivered }} </span>
                                                                    </div>

                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-xxl-4 col-xl-12">
                                                    <div class="card custom-card">
                                                        <div class="card-body">
                                                            <div class="row">
                                                                <div
                                                                    class="col-xxl-3 col-xl-2 col-lg-3 col-md-3 col-sm-4 col-4 d-flex align-items-center justify-content-center ecommerce-icon px-0">
                                                                    <span class="rounded p-3 bg-secondary-transparent">

                                                                        <i class='bx bxl-whatsapp fs-4'></i>
                                                                    </span>
                                                                </div>
                                                                <div
                                                                    class="col-xxl-9 col-xl-10 col-lg-9 col-md-9 col-sm-8 col-8 px-0">
                                                                    <div class="mb-2">Active &amp; Recurring Campaigns
                                                                    </div>
                                                                    <div class="text-muted mb-1 fs-12">
                                                                        <span
                                                                            class="text-dark fw-semibold fs-20 lh-1 vertical-bottom">
                                                                            {{ count($wcamps) }} </span>
                                                                    </div>

                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-xxl-4 col-xl-12">
                                                    <div class="card custom-card">
                                                        <div class="card-body">
                                                            <div class="row">
                                                                <div
                                                                    class="col-xxl-3 col-xl-2 col-lg-3 col-md-3 col-sm-4 col-4 d-flex align-items-center justify-content-center ecommerce-icon px-0">
                                                                    <span class="rounded p-3 bg-warning-transparent">
                                                                        <i class="bx bx-award fs-4"></i>
                                                                    </span>
                                                                </div>
                                                                <div
                                                                    class="col-xxl-9 col-xl-10 col-lg-9 col-md-9 col-sm-8 col-8 px-0">
                                                                    <div class="mb-2">Training Assigned</div>
                                                                    <div class="text-muted mb-1 fs-12">
                                                                        <span
                                                                            class="text-dark fw-semibold fs-20 lh-1 vertical-bottom">
                                                                            {{ $wtraining_assigned }} </span>
                                                                    </div>

                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="card custom-card">
                                                        <div class="card-header">
                                                            <div class="card-title">Employees Interaction</div>
                                                        </div>
                                                        <div class="card-body">
                                                            <div id="wdashed-chart"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>


                                            <div class="row">
                                                <div class="col-xl-12">
                                                    <div class="card custom-card">
                                                        <div class="card-header">
                                                            <div class="card-title">
                                                                WhatsApp Campaign Reports
                                                            </div>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="table-responsive">
                                                                <table id="datatable-basic"
                                                                    class="table table-bordered text-nowrap w-100">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>Sl</th>
                                                                            <th>Campaign Name</th>
                                                                            {{-- <th>Status</th> --}}
                                                                            <th>Created Date</th>
                                                                            <th>Link Clicked</th>
                                                                            <th>Compromised</th>
                                                                            <th>Training Assigned</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        @forelse ($whatsapp_campaigns as $whatsapp_campaign)
                                                                            <tr>
                                                                                <td>{{ $loop->iteration }}</td>
                                                                                <td>
                                                                                    <a href="#" class="text-primary"
                                                                                        onclick="whatsappfetchCampaignDetails(`{{ $whatsapp_campaign->camp_id }}`)"
                                                                                        data-bs-toggle="modal"
                                                                                        data-bs-target="#whatsappcampaignReportModal">{{ $whatsapp_campaign->camp_name }}</a>
                                                                                </td>

                                                                                <td>{{ \Carbon\Carbon::parse($whatsapp_campaign->created_at)->format('d-m-Y h:i A') }}
                                                                                </td>
                                                                                <td>
                                                                                    <div class="checkboxesIcon">

                                                                                        <span>{{ $whatsapp_campaign->targetUsers->where('link_clicked', '1')->count() }}</span>
                                                                                        <i
                                                                                            class="bx bx-check-circle mx-2 fs-25 {{ $whatsapp_campaign->targetUsers->where('link_clicked', '1')->count() > 0 ? 'text-success' : 'text-danger' }}"></i>


                                                                                    </div>

                                                                                </td>
                                                                                <td>
                                                                                    <div class="checkboxesIcon">
                                                                                        <span>{{ $whatsapp_campaign->targetUsers->where('emp_compromised', '1')->count() }}</span>
                                                                                        <i
                                                                                            class="bx bx-check-circle mx-2 fs-25 {{ $whatsapp_campaign->targetUsers->where('emp_compromised', '1')->count() > 0 ? 'text-success' : 'text-danger' }}"></i>

                                                                                    </div>
                                                                                </td>
                                                                                <td>
                                                                                    <div class="checkboxesIcon">
                                                                                        <span>{{ $whatsapp_campaign->targetUsers->where('training_assigned', '1')->count() }}</span>
                                                                                        <i
                                                                                            class="bx bx-check-circle mx-2 fs-25 {{ $whatsapp_campaign->targetUsers->where('training_assigned', '1')->count() > 0 ? 'text-success' : 'text-danger' }}"></i>

                                                                                    </div>

                                                                                </td>

                                                                            </tr>
                                                                        @empty
                                                                            <tr>
                                                                                <td class="text-center" colspan="8">No
                                                                                    records found</td>
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
                                    <div class="tab-pane fade border-0 p-0" id="shipped-tab-pane" role="tabpanel"
                                        aria-labelledby="shipped-tab-pane" tabindex="0">
                                        <div class="p-4">
                                            <div class="row my-3">
                                                <div class="col-xxl-3 col-xl-12">
                                                    <div class="card custom-card">
                                                        <div class="card-body">
                                                            <div class="row">
                                                                <div
                                                                    class="col-xxl-3 col-xl-2 col-lg-3 col-md-3 col-sm-4 col-4 d-flex align-items-center justify-content-center ecommerce-icon px-0">
                                                                    <span class="rounded p-3 bg-primary-transparent">
                                                                        <i class='bx bx-phone-outgoing fs-4'></i>

                                                                    </span>
                                                                </div>
                                                                <div
                                                                    class="col-xxl-9 col-xl-10 col-lg-9 col-md-9 col-sm-8 col-8 px-0">
                                                                    <div class="mb-2">Phone Calls Delivered</div>
                                                                    <div class="text-muted mb-1 fs-12">
                                                                        <span
                                                                            class="text-dark fw-semibold fs-20 lh-1 vertical-bottom">
                                                                            {{ $ai_calls_individual->where('status', 'waiting')->count() }} </span>
                                                                    </div>

                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-xxl-3 col-xl-12">
                                                    <div class="card custom-card">
                                                        <div class="card-body">
                                                            <div class="row">
                                                                <div
                                                                    class="col-xxl-3 col-xl-2 col-lg-3 col-md-3 col-sm-4 col-4 d-flex align-items-center justify-content-center ecommerce-icon px-0">
                                                                    <span class="rounded p-3 bg-secondary-transparent">
                                                                        
                                                                        <i class='bx bx-phone-call fs-4'></i>
                                                                    </span>
                                                                </div>
                                                                <div
                                                                    class="col-xxl-9 col-xl-10 col-lg-9 col-md-9 col-sm-8 col-8 px-0">
                                                                    <div class="mb-2">
                                                                        Pending Calls
                                                                    </div>
                                                                    <div class="text-muted mb-1 fs-12">
                                                                        <span class="text-dark fw-semibold fs-20 lh-1 vertical-bottom">
                                                                            {{ $ai_calls_individual->where('status', 'pending')->count() }}

                                                                            
                                                                        </span>
                                                                    </div>

                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-xxl-3 col-xl-12">
                                                    <div class="card custom-card">
                                                        <div class="card-body">
                                                            <div class="row">
                                                                <div
                                                                    class="col-xxl-3 col-xl-2 col-lg-3 col-md-3 col-sm-4 col-4 d-flex align-items-center justify-content-center ecommerce-icon px-0">
                                                                    <span class="rounded p-3 bg-success-transparent">
                                                                        
                                                                        <i class='bx bxs-phone-call fs-4' ></i>
                                                                    </span>
                                                                </div>
                                                                <div
                                                                    class="col-xxl-9 col-xl-10 col-lg-9 col-md-9 col-sm-8 col-8 px-0">
                                                                    <div class="mb-2">
                                                                        Call Answered
                                                                    </div>
                                                                    <div class="text-muted mb-1 fs-12">
                                                                        <span class="text-dark fw-semibold fs-20 lh-1 vertical-bottom">
                                                                            {{ $ai_calls_individual->where('call_end_response', '!=', null)->count() }}
                                                                        </span>
                                                                    </div>

                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-xxl-3 col-xl-12">
                                                    <div class="card custom-card">
                                                        <div class="card-body">
                                                            <div class="row">
                                                                <div
                                                                    class="col-xxl-3 col-xl-2 col-lg-3 col-md-3 col-sm-4 col-4 d-flex align-items-center justify-content-center ecommerce-icon px-0">
                                                                    <span class="rounded p-3 bg-warning-transparent">
                                                                        <i class="bx bx-award fs-4"></i>
                                                                    </span>
                                                                </div>
                                                                <div
                                                                    class="col-xxl-9 col-xl-10 col-lg-9 col-md-9 col-sm-8 col-8 px-0">
                                                                    <div class="mb-2">Training Assigned</div>
                                                                    <div class="text-muted mb-1 fs-12">
                                                                        <span
                                                                            class="text-dark fw-semibold fs-20 lh-1 vertical-bottom">
                                                                            {{ $ai_calls_individual->sum('training_assigned') }} </span>
                                                                    </div>

                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="card custom-card">
                                                        <div class="card-header">
                                                            <div class="card-title">Employees Interaction</div>
                                                        </div>
                                                        <div class="card-body">
                                                            <div id="cdashed-chart"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div> --}}

                                            <div class="row">
                                                <div class="col-xl-12">
                                                    <div class="card custom-card">
                                                        <div class="card-header">
                                                            <div class="card-title">
                                                                Call Campaign Reports
                                                            </div>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="table-responsive">
                                                                <table id="datatable-basic"
                                                                    class="table table-bordered text-nowrap w-100">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>Sl</th>
                                                                            <th>Campaign Name</th>
                                                                            <th>Status</th>
                                                                            <th>Created Date</th>
                                                                            <th>Employee Group</th>
                                                                            <th>AI Agent</th>
                                                                            <th>Training </th>
                                                                            <th>Training Assigned</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        @forelse ($ccamps as $ccamp)
                                                                            <tr>
                                                                                <td>{{ $loop->iteration }}</td>
                                                                                <td>
                                                                                    <a href="#" class="text-primary"
                                                                                        onclick="aicallingfetchCampaignDetails(`{{ $ccamp->campaign_id }}`)"
                                                                                        data-bs-toggle="modal"
                                                                                        data-bs-target="#AiCallingcampaignReportModal">{{ $ccamp->campaign_name }}</a>
                                                                                </td>
                                                                                <td>
                                                                                    @if ($ccamp->status == 'running' || $ccamp->status == 'completed')
                                                                                        <span
                                                                                            class="badge bg-success">{{ ucfirst($ccamp->status) }}</span>
                                                                                    @else
                                                                                        <span
                                                                                            class="badge bg-warning">{{ ucfirst($ccamp->status) }}</span>
                                                                                    @endif
                                                                                </td>
                                                                                <td>{{ $ccamp->created_at }}</td>
                                                                                <td>
                                                                                    {{ $ccamp->emp_grp_name }}
                                                                                </td>
                                                                                <td>

                                                                                    {{ $ccamp->ai_agent_name }}
                                                                                </td>
                                                                                <td>
                                                                                    {{ $ccamp->trainingName->name ?? 'Only Phishing' }}
                                                                                    {{-- {{ $campaign->trainingName->name ?? 'Only Phishing' }} --}}
                                                                                </td>
                                                                                <td>
                                                                                    <div class="checkboxesIcon">
                                                                                        @if ($ccamp->individualCamps->where('training_assigned', '1')->count() == 0)
                                                                                            <span>{{ $ccamp->individualCamps->where('training_assigned', '1')->count() }}</span>
                                                                                            <i
                                                                                                class="bx bx-check-circle mx-2 fs-25 text-danger"></i>
                                                                                        @else
                                                                                            <span>{{ $ccamp->individualCamps->where('training_assigned', '1')->count() }}</span>
                                                                                            <i
                                                                                                class="bx bx-check-circle mx-2 fs-25 text-success"></i>
                                                                                        @endif

                                                                                    </div>
                                                                                </td>
                                                                            </tr>
                                                                        @empty
                                                                            <tr>
                                                                                <td class="text-center" colspan="8">No
                                                                                    records found</td>
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

                                </div>
                            </div>
                        </div>
                    </div>


                </div>
                <!--End::row-1 -->
            </div>

        </div>
        </div>
        <!-- End::app-content -->

        {{-- @SPK@include('partials/headersearch_modal.html')
        @SPK@include('partials/footer.html') --}}

        </div>

        {{-- @SPK@include('partials/commonjs.html')

        @SPK@include('partials/custom_switcherjs.html') --}}



    </body>

    </html>





    {{-- -------------------Modals------------------------ --}}

    <!-- campaign report modal -->
    <div class="modal fade" id="campaignReportModal" tabindex="-1" aria-labelledby="exampleModalLgLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Campaign Report</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="card custom-card">

                        <div class="card-body">
                            <ul class="nav nav-pills nav-style-3 mb-3" role="tablist">
                                <li class="nav-item" role="presentation" id="phishing_tab">
                                    <a class="nav-link active" data-bs-toggle="tab" role="tab" aria-current="page"
                                        href="#phishing_campaign" aria-selected="true">Phishing
                                        Campaign</a>
                                </li>
                                <li class="nav-item" role="presentation" id="training_tab">
                                    <a class="nav-link" data-bs-toggle="tab" role="tab" aria-current="page"
                                        href="#training_campaign" aria-selected="false" tabindex="-1">Training
                                        Campaign</a>
                                </li>
                            </ul>
                            <div class="tab-content">
                                <div class="tab-pane show active text-muted" id="phishing_campaign" role="tabpanel">
                                    <div class="table-responsive">
                                        <table class="table text-nowrap table-striped">
                                            <thead>
                                                <tr>
                                                    <th scope="col">Campaign name</th>
                                                    <th scope="col">Status</th>
                                                    <th scope="col">Employees</th>
                                                    <th scope="col">Emails Delivered</th>
                                                    <th scope="col">Emails Viewed</th>
                                                    <th scope="col">Payloads Clicked</th>
                                                    <th scope="col">Employees Compromised</th>
                                                    <th scope="col">Emails Reported</th>
                                                </tr>
                                            </thead>
                                            <tbody id="campReportStatus">
                                            </tbody>
                                        </table>
                                    </div>

                                    <hr>

                                    <div class="card custom-card">
                                        <div class="card-header">
                                            <div class="card-title">Phishing Campaign Statistics</div>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table id="file-export" class="table table-bordered text-nowrap w-100">
                                                    <thead>
                                                        <tr>
                                                            <th>Employee Name</th>
                                                            <th>Email Address</th>
                                                            <th>Email Delivery</th>
                                                            <th>Email Viewed</th>
                                                            <th>Payload Clicked</th>
                                                            <th>Employee Compromised</th>
                                                            <th>Email Reported</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="campReportsIndividual">

                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane text-muted" id="training_campaign" role="tabpanel">
                                    <div class="table-responsive">
                                        <table class="table text-nowrap table-striped">
                                            <thead>
                                                <tr>
                                                    <th scope="col">Campaign name</th>
                                                    <th scope="col">Status</th>
                                                    <th scope="col">Employees</th>
                                                    <th scope="col">Trainings Assigned</th>
                                                    <th scope="col">Trainings Completed</th>
                                                </tr>
                                            </thead>
                                            <tbody id="trainingReportStatus">
                                            </tbody>
                                        </table>
                                    </div>

                                    <hr>

                                    <div class="card custom-card">
                                        <div class="card-header">
                                            <div class="card-title">Training Campaign Statistics</div>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table id="file-export2" class="table table-bordered text-nowrap w-100">
                                                    <thead>
                                                        <tr>
                                                            <th>Email Address</th>
                                                            <th>Training Module</th>
                                                            <th>Date Assigned</th>
                                                            <th>Score</th>
                                                            <th>Passing Score</th>
                                                            <th>Status</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="trainingReportsIndividual">

                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- whatsapp report modal -->
    <div class="modal fade" id="whatsappcampaignReportModal" tabindex="-1" aria-labelledby="exampleModalLgLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">WhatsApp Campaign Report</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="card custom-card">
                        <div class="card-body">
                            <!-- Tabs -->
                            <ul class="nav nav-pills nav-style-3 mb-3" role="tablist">
                                <li class="nav-item" role="presentation" id="phishing_tab">
                                    <a class="nav-link active" data-bs-toggle="tab" role="tab" aria-current="page"
                                        href="#whatsappphishing_campaign" aria-selected="true">Phishing Campaign</a>
                                </li>
                                <li class="nav-item" role="presentation" id="whatsapptraining_tab">
                                    <a class="nav-link" data-bs-toggle="tab" role="tab"
                                        href="#whatsapptraining_campaign" aria-selected="false" tabindex="-1">Training
                                        Campaign</a>
                                </li>
                            </ul>
                            <!-- Tab Content -->
                            <div class="tab-content">
                                <!-- Phishing Campaign Tab -->
                                <div class="tab-pane show active text-muted" id="whatsappphishing_campaign"
                                    role="tabpanel">
                                    <div class="table-responsive">
                                        <table class="table text-nowrap">
                                            <thead>
                                                <tr>
                                                    <th scope="col">Campaign Name</th>
                                                    <th scope="col">Campaign Type</th>
                                                    <th scope="col">Training</th>
                                                    <th scope="col">Template Name</th>
                                                    <th scope="col">Employees Group</th>
                                                    <th scope="col">Launch Date</th>
                                                </tr>
                                            </thead>
                                            <tbody id="whatsappcampReportStatus">
                                                <!-- Dynamic Content -->
                                            </tbody>
                                        </table>
                                    </div>
                                    <hr>
                                    <div class="card custom-card">
                                        <div class="card-header">
                                            <div class="card-title">Phishing Campaign Statistics</div>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table id="file-export" class="table table-bordered text-nowrap w-100">
                                                    <thead>
                                                        <tr>
                                                            <th>Employee Name</th>
                                                            <th>WhatsApp Number</th>
                                                            <th>Message Delivered</th>
                                                            <th>Link Clicked</th>
                                                            <th>Employee Compromised</th>
                                                            <th>Training Assigned</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="whatsappcampReportsIndividual">
                                                        <!-- Dynamic Content -->
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Training Campaign Tab -->
                                <div class="tab-pane text-muted" id="whatsapptraining_campaign" role="tabpanel">
                                    <div class="table-responsive">
                                        <table class="table text-nowrap table-striped">
                                            <thead>
                                                <tr>
                                                    <th scope="col">Campaign Name</th>
                                                    <th scope="col">Status</th>
                                                    <th scope="col">Employees</th>
                                                    <th scope="col">Trainings Assigned</th>
                                                    <th scope="col">Trainings Completed</th>
                                                </tr>
                                            </thead>
                                            <tbody id="whatsapptrainingReportStatus">
                                                <!-- Dynamic Content -->
                                            </tbody>
                                        </table>
                                    </div>
                                    <hr>
                                    <div class="card custom-card">
                                        <div class="card-header">
                                            <div class="card-title">Training Campaign Statistics</div>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table id="file-export2" class="table table-bordered text-nowrap w-100">
                                                    <thead>
                                                        <tr>
                                                            <th>Email Address</th>
                                                            <th>Training Module</th>
                                                            <th>Date Assigned</th>
                                                            <th>Score</th>
                                                            <th>Passing Score</th>
                                                            <th>Status</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="whatsapptrainingReportsIndividual">
                                                        <!-- Dynamic Content -->
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div> <!-- End of Tab Content -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- AI Calling Campaign Report Modal --}}
    <div class="modal fade" id="AiCallingcampaignReportModal" tabindex="-1" aria-labelledby="exampleModalLgLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Ai Calling Campaign Report</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="card custom-card">

                        <div class="card-body">
                            <ul class="nav nav-pills nav-style-3 mb-3" role="tablist">
                                <li class="nav-item" role="presentation" id="ai_phishing_tab">
                                    <a class="nav-link active" data-bs-toggle="tab" role="tab" aria-current="page"
                                        href="#aicallingphishing_campaign" aria-selected="true">Phishing
                                        Campaign</a>
                                </li>
                                <li class="nav-item" role="presentation" id="ai_training_tab">
                                    <a class="nav-link" data-bs-toggle="tab" role="tab" aria-current="page"
                                        href="#aicallingtraining_campaign" aria-selected="false" tabindex="-1">Training
                                        Campaign</a>
                                </li>
                            </ul>
                            <div class="tab-content">
                                <div class="tab-pane show active text-muted" id="aicallingphishing_campaign"
                                    role="tabpanel">
                                    <div class="table-responsive">
                                        <table class="table text-nowrap">
                                            <thead>
                                                <tr>
                                                    <th scope="col">Campaign name</th>
                                                    <th scope="col">Status</th>
                                                    <th scope="col">Delivered At</th>
                                                    <th scope="col">Ai Agent</th>
                                                    <th scope="col">Ai Agent Name</th>
                                                    <th scope="col">Ai Phone Number</th>

                                                </tr>
                                            </thead>
                                            <tbody id="aicallingcampReportStatus">
                                            </tbody>
                                        </table>
                                    </div>

                                    <hr>

                                    <div class="card custom-card">
                                        <div class="card-header">
                                            <div class="card-title">Phishing Campaign Statistics</div>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table id="file-export" class="table table-bordered text-nowrap w-100">
                                                    <thead>
                                                        <tr>
                                                            <th>Employee Name</th>
                                                            <th>Phone Number </th>

                                                            <th>Created At</th>
                                                            <th>Employee Email </th>
                                                            <th>Status</th>
                                                            <th>Training Assigned</th>

                                                        </tr>
                                                    </thead>
                                                    <tbody id="aicallingcampReportsIndividual">

                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane text-muted" id="aicallingtraining_campaign" role="tabpanel">
                                    <div class="table-responsive">
                                        <table class="table text-nowrap">
                                            <thead>
                                                <tr>
                                                    <th scope="col">Campaign name</th>
                                                    <th scope="col">Status</th>
                                                    <th scope="col">Trainings Assigned</th>
                                                    <th scope="col">Trainings Completed</th>
                                                </tr>
                                            </thead>
                                            <tbody id="aicallingtrainingReportStatus">
                                            </tbody>
                                        </table>
                                    </div>

                                    <hr>

                                    <div class="card custom-card">
                                        <div class="card-header">
                                            <div class="card-title">Training Campaign Statistics</div>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table id="file-export2" class="table table-bordered text-nowrap w-100">
                                                    <thead>
                                                        <tr>
                                                            <th>Email Address</th>
                                                            <th>Training Module</th>
                                                            <th>Date Assigned</th>
                                                            <th>Score</th>
                                                            <th>Passing Score</th>
                                                            <th>Status</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="aicallingtrainingReportsIndividual">

                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- -------------------Modals------------------------ --}}


    @push('newcss')
        <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css">
        <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.3.0/css/responsive.bootstrap.min.css">
        <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.bootstrap5.min.css">

        <style>
            .checkboxesIcon {
                display: flex;
                align-items: center;
            }
        </style>


        <link rel="stylesheet" href="assets/libs/apexcharts/apexcharts.css">
    @endpush



    @push('newscripts')
        <!-- Datatables Cdn -->
        <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>
        <script src="https://cdn.datatables.net/responsive/2.3.0/js/dataTables.responsive.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.print.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.6/pdfmake.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

        <script>
            function fetchCampaignDetails(campid) {
                // console.log(campid)
                $.post({
                    url: '/reporting/fetch-campaign-report',
                    data: {
                        campaignId: campid
                    },
                    success: function(response) {

                        if (response.campaign_type === "Phishing") {
                            fetchCampReportByUsers()

                            $("#training_tab").hide();
                            $("#phishing_tab").show();
                        }
                        if (response.campaign_type === "Training") {
                            fetchCampTrainingDetails()
                            fetchCampTrainingDetailsIndividual()

                            $("#phishing_tab").hide();
                            $("#training_tab").show();
                            $("#phishing_campaign").removeClass("active show");

                            $("#training_tab a").addClass("active")
                            $("#training_campaign").addClass("active show")
                        }
                        if (response.campaign_type === "Phishing & Training") {
                            fetchCampReportByUsers()
                            fetchCampTrainingDetails()
                            fetchCampTrainingDetailsIndividual()

                            $("#training_tab").show();
                            $("#phishing_tab").show();
                            $("#phishing_campaign").addClass("active show");
                        }

                        let isDelivered = response.emails_delivered > 0 ?
                            '<i class="bx bx-check-circle text-success fs-25"></i>' :
                            '<i class="bx bx-check-circle text-danger fs-25"></i>';
                        let isViewed = response.emails_viewed > 0 ?
                            '<i class="bx bx-check-circle text-success fs-25"></i>' :
                            '<i class="bx bx-check-circle text-danger fs-25"></i>';
                        let isPayLoadClicked = response.payloads_clicked > 0 ?
                            '<i class="bx bx-check-circle text-success fs-25"></i>' :
                            '<i class="bx bx-check-circle text-danger fs-25"></i>';
                        let isEmpCompromised = response.emp_compromised > 0 ?
                            '<i class="bx bx-check-circle text-success fs-25"></i>' :
                            '<i class="bx bx-check-circle text-danger fs-25"></i>';
                        let isEmailReported = response.email_reported > 0 ?
                            '<i class="bx bx-check-circle text-success fs-25"></i>' :
                            '<i class="bx bx-check-circle text-danger fs-25"></i>';

                        let status = '';
                        if (response.status === 'completed') {
                            status = '<span class="badge bg-success">Completed</span>';
                        } else if (response.status === 'pending') {
                            status = '<span class="badge bg-warning">Pending</span>';
                        } else {
                            status = '<span class="badge bg-success">Running</span>';
                        }

                        let rowHtml = `
            <tr>
                <th scope="row">${response.campaign_name}</th>
                <td>${status}</td>
                <td>${response.no_of_users}</td>
                <td>
                    <div class="d-flex align-items-center">
                        <span class="mx-1">${response.emails_delivered}</span>
                        ${isDelivered}
                    </div>
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <span class="mx-1">${response.emails_viewed}</span>
                        ${isViewed}
                    </div>
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <span class="mx-1">${response.payloads_clicked}</span>
                        ${isPayLoadClicked}
                    </div>
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <span class="mx-1">${response.emp_compromised}</span>
                        ${isEmpCompromised}
                    </div>
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <span class="mx-1">${response.email_reported}</span>
                        ${isEmailReported}
                    </div>
                </td>
            </tr>
        `;

                        $("#campReportStatus").html(rowHtml);

                        // Example of showing/hiding a section based on a condition
                        // if (response.emails_delivered > 0) {
                        //     $("#someSection").show();
                        // } else {
                        //     $("#someSection").hide();
                        // }
                    }
                });






                function fetchCampReportByUsers() {
                    $.post({
                        url: '/fetch-camp-report-by-users',
                        data: {
                            fetchCampReportByUsers: '1',
                            campaignId: campid
                        },
                        success: function(res) {
                            //console.log(res)
                            $("#campReportsIndividual").html(res.html)

                            if (!$.fn.DataTable.isDataTable('#file-export')) {

                                $('#file-export').DataTable({
                                    dom: 'Bfrtip',
                                    buttons: [
                                        'copy', 'csv', 'excel', 'pdf', 'print'
                                    ],
                                    language: {
                                        searchPlaceholder: 'Search...',
                                        sSearch: '',
                                    },
                                });
                            }


                        }
                    })
                }

                function fetchCampTrainingDetails() {
                    $.post({
                        url: '/fetch-camp-training-details',
                        data: {
                            fetchCampTrainingDetails: '1',
                            campaignId: campid
                        },
                        success: function(res) {
                            //console.log(res)
                            $("#trainingReportStatus").html(res.html)


                        }
                    })
                }

                function fetchCampTrainingDetailsIndividual() {
                    $.post({
                        url: '/fetch-camp-training-details-individual',
                        data: {
                            fetchCampTrainingDetailsIndividual: '1',
                            campaignId: campid
                        },
                        success: function(res) {
                            //console.log(res)
                            $("#trainingReportsIndividual").html(res.html)


                        }
                    })
                }


            }



            $('#datatable-basic').DataTable({
                language: {
                    searchPlaceholder: 'Search...',
                    sSearch: '',
                },
                "pageLength": 10,
                // scrollX: true
            });
        </script>

        <script>
            $.get({
                url: '/reporting/get-chart-data',
                success: function(res) {
                    //console.log(res)
                    var chartData = res;

                    /* dashed chart */
                    var options = {
                        series: [{
                                name: "Mail Open",
                                data: chartData.mail_open
                            },
                            {
                                name: "Payload Clicked",
                                data: chartData.payload_clicked
                            },
                            {
                                name: 'Employee Compromised',
                                data: chartData.employee_compromised
                            },
                            {
                                name: 'Email Reported',
                                data: chartData.email_reported
                            }

                        ],
                        chart: {
                            height: 320,
                            type: 'line',
                            zoom: {
                                enabled: false
                            },
                        },
                        dataLabels: {
                            enabled: false
                        },
                        stroke: {
                            width: [3, 4, 3],
                            curve: 'straight',
                            dashArray: [0, 8, 5]
                        },
                        colors: ["#845adf", "#23b7e5", "#f5b849", "#f55679"],
                        title: {
                            text: 'Employees interation of last 12 days',
                            align: 'left',
                            style: {
                                fontSize: '13px',
                                fontWeight: 'bold',
                                color: '#8c9097'
                            },
                        },
                        legend: {
                            tooltipHoverFormatter: function(val, opts) {
                                return val + ' - ' + opts.w.globals.series[opts.seriesIndex][opts
                                    .dataPointIndex
                                ] + ''
                            }
                        },
                        markers: {
                            size: 0,
                            hover: {
                                sizeOffset: 6
                            }
                        },
                        xaxis: {
                            categories: chartData.dates,
                            labels: {
                                show: true,
                                style: {
                                    colors: "#8c9097",
                                    fontSize: '11px',
                                    fontWeight: 600,
                                    cssClass: 'apexcharts-xaxis-label',
                                },
                            }
                        },
                        yaxis: {
                            labels: {
                                show: true,
                                style: {
                                    colors: "#8c9097",
                                    fontSize: '11px',
                                    fontWeight: 600,
                                    cssClass: 'apexcharts-xaxis-label',
                                },
                            }
                        },
                        tooltip: {
                            y: [{
                                    title: {
                                        formatter: function(val) {
                                            return val
                                        }
                                    }
                                },
                                {
                                    title: {
                                        formatter: function(val) {
                                            return val
                                        }
                                    }
                                },
                                {
                                    title: {
                                        formatter: function(val) {
                                            return val;
                                        }
                                    }
                                },
                                {
                                    title: {
                                        formatter: function(val) {
                                            return val;
                                        }
                                    }
                                }
                            ]
                        },
                        grid: {
                            borderColor: '#f1f1f1',
                        }
                    };
                    var chart = new ApexCharts(document.querySelector("#dashed-chart"), options);
                    chart.render();




                }
            })


            $.get({
                url: '/reporting/wget-chart-data',
                success: function(res) {
                    //console.log(res)
                    var chartData = res;

                    /* dashed chart */
                    var options = {
                        series: [{
                                name: "Link Clicked",
                                data: chartData.mail_open
                            },
                            {
                                name: "Payload Clicked",
                                data: chartData.payload_clicked
                            },
                            {
                                name: 'Employee Compromised',
                                data: chartData.employee_compromised
                            },
                            {
                                name: 'Status Send',
                                data: chartData.email_reported
                            }

                        ],
                        chart: {
                            height: 320,
                            type: 'line',
                            zoom: {
                                enabled: false
                            },
                        },
                        dataLabels: {
                            enabled: false
                        },
                        stroke: {
                            width: [3, 4, 3],
                            curve: 'straight',
                            dashArray: [0, 8, 5]
                        },
                        colors: ["#845adf", "#23b7e5", "#f5b849", "#f55679"],
                        title: {
                            text: 'Employees interation of last 12 days',
                            align: 'left',
                            style: {
                                fontSize: '13px',
                                fontWeight: 'bold',
                                color: '#8c9097'
                            },
                        },
                        legend: {
                            tooltipHoverFormatter: function(val, opts) {
                                return val + ' - ' + opts.w.globals.series[opts.seriesIndex][opts
                                    .dataPointIndex
                                ] + ''
                            }
                        },
                        markers: {
                            size: 0,
                            hover: {
                                sizeOffset: 6
                            }
                        },
                        xaxis: {
                            categories: chartData.dates,
                            labels: {
                                show: true,
                                style: {
                                    colors: "#8c9097",
                                    fontSize: '11px',
                                    fontWeight: 600,
                                    cssClass: 'apexcharts-xaxis-label',
                                },
                            }
                        },
                        yaxis: {
                            labels: {
                                show: true,
                                style: {
                                    colors: "#8c9097",
                                    fontSize: '11px',
                                    fontWeight: 600,
                                    cssClass: 'apexcharts-xaxis-label',
                                },
                            }
                        },
                        tooltip: {
                            y: [{
                                    title: {
                                        formatter: function(val) {
                                            return val
                                        }
                                    }
                                },
                                {
                                    title: {
                                        formatter: function(val) {
                                            return val
                                        }
                                    }
                                },
                                {
                                    title: {
                                        formatter: function(val) {
                                            return val;
                                        }
                                    }
                                },
                                {
                                    title: {
                                        formatter: function(val) {
                                            return val;
                                        }
                                    }
                                }
                            ]
                        },
                        grid: {
                            borderColor: '#f1f1f1',
                        }
                    };
                    var chart = new ApexCharts(document.querySelector("#wdashed-chart"), options);
                    chart.render();




                }
            })

            $.get({
                url: '/reporting/cget-chart-data',
                success: function(res) {
                    //console.log(res)
                    var chartData = res;

                    /* dashed chart */
                    var options = {
                        series: [{
                                name: "Mail Open",
                                data: chartData.mail_open
                            },
                            {
                                name: "Payload Clicked",
                                data: chartData.payload_clicked
                            },
                            {
                                name: 'Employee Compromised',
                                data: chartData.employee_compromised
                            },
                            {
                                name: 'Email Reported',
                                data: chartData.email_reported
                            }

                        ],
                        chart: {
                            height: 320,
                            type: 'line',
                            zoom: {
                                enabled: false
                            },
                        },
                        dataLabels: {
                            enabled: false
                        },
                        stroke: {
                            width: [3, 4, 3],
                            curve: 'straight',
                            dashArray: [0, 8, 5]
                        },
                        colors: ["#845adf", "#23b7e5", "#f5b849", "#f55679"],
                        title: {
                            text: 'Employees interation of last 12 days',
                            align: 'left',
                            style: {
                                fontSize: '13px',
                                fontWeight: 'bold',
                                color: '#8c9097'
                            },
                        },
                        legend: {
                            tooltipHoverFormatter: function(val, opts) {
                                return val + ' - ' + opts.w.globals.series[opts.seriesIndex][opts
                                    .dataPointIndex
                                ] + ''
                            }
                        },
                        markers: {
                            size: 0,
                            hover: {
                                sizeOffset: 6
                            }
                        },
                        xaxis: {
                            categories: chartData.dates,
                            labels: {
                                show: true,
                                style: {
                                    colors: "#8c9097",
                                    fontSize: '11px',
                                    fontWeight: 600,
                                    cssClass: 'apexcharts-xaxis-label',
                                },
                            }
                        },
                        yaxis: {
                            labels: {
                                show: true,
                                style: {
                                    colors: "#8c9097",
                                    fontSize: '11px',
                                    fontWeight: 600,
                                    cssClass: 'apexcharts-xaxis-label',
                                },
                            }
                        },
                        tooltip: {
                            y: [{
                                    title: {
                                        formatter: function(val) {
                                            return val
                                        }
                                    }
                                },
                                {
                                    title: {
                                        formatter: function(val) {
                                            return val
                                        }
                                    }
                                },
                                {
                                    title: {
                                        formatter: function(val) {
                                            return val;
                                        }
                                    }
                                },
                                {
                                    title: {
                                        formatter: function(val) {
                                            return val;
                                        }
                                    }
                                }
                            ]
                        },
                        grid: {
                            borderColor: '#f1f1f1',
                        }
                    };
                    var chart = new ApexCharts(document.querySelector("#cdashed-chart"), options);
                    chart.render();




                }
            })






            function getLastNDays(n) {
                const days = [];
                const today = new Date();

                for (let i = 0; i < n; i++) {
                    const date = new Date();
                    date.setDate(today.getDate() - i);
                    const day = String(date.getDate()).padStart(2, '0');
                    const month = date.toLocaleString('default', {
                        month: 'short'
                    });
                    days.push(`${day} ${month}`);
                }

                return days.reverse();
            }

            // const categories = getLastNDays(10);
            // console.log(categories);
        </script>

        <script>
            function whatsappfetchCampaignDetails(campid) {
                // console.log('Sending campaignId:', campid); // Log campaignId to confirm this is triggered
                $.post({
                    url: '/reporting/whatsappfetch-campaign-report',
                    data: {
                        campaignId: campid
                    },
                    success: function(response) {
                        // console.log("Success callback triggered!"); // Confirm callback is executed
                         console.log(response); // Log the full response to verify structure

                        if (response && response.camp_type) {
                            if (response.camp_type === "Phishing") {
                                whatsappfetchCampReportByUsers(campid); // Call function with campaignId
                                $("#whatsapptraining_tab").hide();
                                $("#phishing_tab").show();
                            }
                            if (response.camp_type === "Training") {
                                whatsappfetchCampTrainingDetails(campid);
                                whatsappfetchCampTrainingDetailsIndividual(campid); // Call function with campaignId
                                $("#phishing_tab").hide();
                                $("#whatsapptraining_tab").show();
                                $("#phishing_campaign").removeClass("active show");
                                $("#whatsapptraining_tab a").addClass("active");
                                $("#training_campaign").addClass("active show");
                            }
                            if (response.camp_type === "Phishing and Training") {
                                whatsappfetchCampReportByUsers(campid);
                                whatsappfetchCampTrainingDetails(campid);
                                whatsappfetchCampTrainingDetailsIndividual(campid);
                                $("#whatsapptraining_tab").show();
                                $("#phishing_tab").show();
                                $("#phishing_campaign").addClass("active show");
                            }
                        } else {
                            console.error('Unexpected response structure:', response);
                        }

                        

                        let rowHtml = `
                    <tr>
                        <th scope="row">${response.camp_name}</th>
                        <td>${response.camp_type}</td>
                        <td>
                            <span class="badge bg-primary-transparent">
                                ${response.training_data?.name ?? "Only Phishing"}
                            </span>                            
                        </td>
                        <td>
                            <span class="badge bg-success-transparent">
                                ${response.template_name}
                            </span>
                        </td>
                        <td>${response.user_group_name}</td>
                        <td>${response.created_at}</td>
                    </tr>
                `;
                        $("#whatsappcampReportStatus").html(rowHtml);
                    },
                    error: function(xhr, status, error) {
                        console.error("Error:", status, error);
                        console.error("Response Text:", xhr.responseText);
                    }
                });
            }

            function whatsappfetchCampReportByUsers(campid) {
                $.post({
                    url: '/whatsappfetch-camp-report-by-users',
                    data: {
                        fetchCampReportByUsers: '1',
                        campaignId: campid
                    },
                    success: function(res) {
                        $("#whatsappcampReportsIndividual").html(res.html);

                        if (!$.fn.DataTable.isDataTable('#file-export')) {
                            $('#file-export').DataTable({
                                dom: 'Bfrtip',
                                buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
                                language: {
                                    searchPlaceholder: 'Search...',
                                    sSearch: '',
                                },
                            });
                        }
                    }
                });
            }

            function whatsappfetchCampTrainingDetails(campid) {
                console.log("Sending campaignId for training details:", campid);

                $.post({
                    url: '/whatsappfetch-camp-training-details',
                    data: {
                        fetchCampTrainingDetails: '1',
                        campaignId: campid
                    },
                    success: function(res) {

                        // Injecting the received HTML into the DOM
                        $("#whatsapptrainingReportStatus").html(res.html);

                        // Optional: Log the DOM element to ensure it's updated

                    },
                    error: function(xhr, status, error) {
                        console.error("Error occurred:", error);
                        console.error("XHR object:", xhr);
                        console.error("Status:", status);
                    }
                });
            }


            function whatsappfetchCampTrainingDetailsIndividual(campid) {
                $.post({
                    url: '/whatsappfetch-camp-training-details-individual',
                    data: {
                        fetchCampTrainingDetailsIndividual: '1',
                        campaignId: campid
                    },
                    success: function(res) {
                        $("#whatsapptrainingReportsIndividual").html(res.html);
                    }
                });
            }

            // $('#datatable-basic').DataTable({
            //     language: {
            //         searchPlaceholder: 'Search...',
            //         sSearch: '',
            //     },
            //     "pageLength": 10,
            // });
        </script>
        <script>
            function aicallingfetchCampaignDetails(campid) {
                // console.log(campid)
                $.post({
                    url: '/reporting/aicallingfetch-campaign-report',
                    data: {
                        campaignId: campid
                    },
                    success: function(response) {

                        if (response.campaign_type === "Phishing") {
                            fetchCampReportByUsers()

                            $("#ai_training_tab").hide();
                            $("#ai_phishing_tab").show();
                        }
                        if (response.campaign_type === "Training") {
                            fetchCampTrainingDetails()
                            fetchCampTrainingDetailsIndividual()

                            $("#ai_phishing_tab").hide();
                            $("#ai_training_tab").show();
                            $("#ai_phishing_campaign").removeClass("active show");

                            $("#ai_training_tab a").addClass("active")
                            $("#ai_training_campaign").addClass("active show")
                        }
                        if (response.campaign_type === "Phishing & Training") {
                            aicallingfetchCampReportByUsers()
                            aicallingfetchCampTrainingDetails()
                            aicallingfetchCampTrainingDetailsIndividual()

                            $("#training_tab").show();
                            $("#phishing_tab").show();
                            $("#phishing_campaign").addClass("active show");
                        }

                        let isDelivered = response.emails_delivered > 0 ?
                            '<i class="bx bx-check-circle text-success fs-25"></i>' :
                            '<i class="bx bx-check-circle text-danger fs-25"></i>';
                        let isViewed = response.emails_viewed > 0 ?
                            '<i class="bx bx-check-circle text-success fs-25"></i>' :
                            '<i class="bx bx-check-circle text-danger fs-25"></i>';
                        let isPayLoadClicked = response.payloads_clicked > 0 ?
                            '<i class="bx bx-check-circle text-success fs-25"></i>' :
                            '<i class="bx bx-check-circle text-danger fs-25"></i>';
                        let isEmpCompromised = response.emp_compromised > 0 ?
                            '<i class="bx bx-check-circle text-success fs-25"></i>' :
                            '<i class="bx bx-check-circle text-danger fs-25"></i>';
                        let isEmailReported = response.email_reported > 0 ?
                            '<i class="bx bx-check-circle text-success fs-25"></i>' :
                            '<i class="bx bx-check-circle text-danger fs-25"></i>';

                        let status = '';
                        if (response.status === 'completed') {
                            status = '<span class="badge bg-success">Completed</span>';
                        } else if (response.status === 'pending') {
                            status = '<span class="badge bg-warning">Pending</span>';
                        } else {
                            status = '<span class="badge bg-success">Running</span>';
                        }

                        let rowHtml = `
            <tr>
                <th scope="row">${response.campaign_name}</th>
                <td>${status}</td>
                <td>
                    
                      ${response.created_at}
                        
                    
                </td>
                <td>
                    
                        ${response.ai_agent}
                        
                  
                </td>
                <td>
                   
                       ${response.ai_agent_name}
                        
                    
                </td>
                <td>
                    
                        ${response.phone_no}
                       
                    
                </td>
                
            </tr>
            `;

                        $("#aicallingcampReportStatus").html(rowHtml);

                        // Example of showing/hiding a section based on a condition
                        // if (response.emails_delivered > 0) {
                        // $("#someSection").show();
                        // } else {
                        // $("#someSection").hide();
                        // }
                    }
                });






                function aicallingfetchCampReportByUsers() {
                    $.post({
                        url: '/aicallingfetch-camp-report-by-users',
                        data: {
                            fetchCampReportByUsers: '1',
                            campaignId: campid
                        },
                        success: function(res) {
                            //console.log(res)
                            $("#aicallingcampReportsIndividual").html(res.html)

                            if (!$.fn.DataTable.isDataTable('#file-export')) {

                                $('#file-export').DataTable({
                                    dom: 'Bfrtip',
                                    buttons: [
                                        'copy', 'csv', 'excel', 'pdf', 'print'
                                    ],
                                    language: {
                                        searchPlaceholder: 'Search...',
                                        sSearch: '',
                                    },
                                });
                            }


                        }
                    })
                }

                function aicallingfetchCampTrainingDetails() {
                    $.post({
                        url: '/aicallingfetch-camp-training-details',
                        data: {
                            fetchCampTrainingDetails: '1',
                            campaignId: campid
                        },
                        success: function(res) {
                            //console.log(res)
                            $("#aicallingtrainingReportStatus").html(res.html)


                        }
                    })
                }

                function aicallingfetchCampTrainingDetailsIndividual() {
                    $.post({
                        url: '/aicallingfetch-camp-training-details-individual',
                        data: {
                            fetchCampTrainingDetailsIndividual: '1',
                            campaignId: campid
                        },
                        success: function(res) {
                            //console.log(res)
                            $("#aicallingtrainingReportsIndividual").html(res.html)


                        }
                    })
                }


            }



            // $('#datatable-basic').DataTable({
            //     language: {
            //         searchPlaceholder: 'Search...',
            //         sSearch: '',
            //     },
            //     "pageLength": 10,
            //     // scrollX: true
            // });
        </script>
        <!-- Internal Checkout JS -->
        <script src="../assets/js/checkout.js"></script>

        <!-- Custom JS -->
        <script src="../assets/js/custom.js"></script>
    @endpush





@endsection
