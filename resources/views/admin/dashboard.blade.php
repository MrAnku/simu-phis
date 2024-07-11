@extends('admin.layouts.app')

@section('title', 'Admin | simUphish')

@section('main-content')

    <div class="main-content app-content">
        <div class="container-fluid mt-4">

            <!-- Start::page-header -->


            <!-- End::page-header -->

            <div class="card custom-card">

                <div class="card-body">
                    <ul class="nav nav-pills nav-style-3 mb-3" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link active" data-bs-toggle="tab" role="tab" aria-current="page"
                                href="#phish-overview" aria-selected="false" tabindex="-1">Yearly Phishing Overview</a>
                        </li>
                        <!-- <li class="nav-item" role="presentation">
                                <a class="nav-link" data-bs-toggle="tab" role="tab" aria-current="page" href="#training-overview" aria-selected="false" tabindex="-1">Yearly Training Overview</a>
                            </li> -->
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane text-muted active show" id="phish-overview" role="tabpanel">
                            <div class="row">
                                <div class="col-xxl-6 col-xl-12">
                                    <div class="row">
                                        <div class="col-lg-6 col-sm-6 col-md-6 col-xl-6">
                                            <div class="card custom-card overflow-hidden">
                                                <div class="card-body">
                                                    <div class="d-flex align-items-top justify-content-between">
                                                        <div>
                                                            <span class="avatar avatar-md avatar-rounded bg-primary">
                                                                <i class='bx bx-mail-send fs-16'></i>
                                                            </span>
                                                        </div>
                                                        <div class="flex-fill ms-3">
                                                            <div
                                                                class="d-flex align-items-center justify-content-between flex-wrap">
                                                                <div>
                                                                    <p class="text mb-0">Active Campaigns</p>
                                                                    <h4 class="fw-semibold mt-1">{{$counts->activeCampaigns}}</h4>
                                                                </div>
                                                            </div>

                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-6 col-sm-6 col-md-6 col-xl-6">
                                            <div class="card custom-card overflow-hidden">
                                                <div class="card-body">
                                                    <div class="d-flex align-items-top justify-content-between">
                                                        <div>
                                                            <span class="avatar avatar-md avatar-rounded bg-info">
                                                                <i class='bx bx-envelope fs-16'></i>
                                                            </span>
                                                        </div>
                                                        <div class="flex-fill ms-3">
                                                            <div
                                                                class="d-flex align-items-center justify-content-between flex-wrap">
                                                                <div>
                                                                    <p class="text mb-0">Phishing Emails</p>
                                                                    <h4 class="fw-semibold mt-1">{{$counts->phishingEmails}}</h4>
                                                                </div>
                                                            </div>

                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-6 col-sm-6 col-md-6 col-xl-6">
                                            <div class="card custom-card overflow-hidden">
                                                <div class="card-body">
                                                    <div class="d-flex align-items-top justify-content-between">
                                                        <div>
                                                            <span class="avatar avatar-md avatar-rounded bg-success">
                                                                <i class='bx bx-globe fs-16'></i>
                                                            </span>
                                                        </div>
                                                        <div class="flex-fill ms-3">
                                                            <div
                                                                class="d-flex align-items-center justify-content-between flex-wrap">
                                                                <div>
                                                                    <p class="text mb-0">Phishing Websites</p>
                                                                    <h4 class="fw-semibold mt-1">{{$counts->phishingWebsites}}</h4>
                                                                </div>
                                                            </div>

                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-6 col-sm-6 col-md-6 col-xl-6">
                                            <div class="card custom-card overflow-hidden">
                                                <div class="card-body">
                                                    <div class="d-flex align-items-top justify-content-between">
                                                        <div>
                                                            <span class="avatar avatar-md avatar-rounded bg-warning">
                                                                <i class='bx bx-book-content fs-16'></i>
                                                            </span>
                                                        </div>
                                                        <div class="flex-fill ms-3">
                                                            <div
                                                                class="d-flex align-items-center justify-content-between flex-wrap">
                                                                <div>
                                                                    <p class="text mb-0">Training Modules</p>
                                                                    <h4 class="fw-semibold mt-1">{{$counts->trainingModules}}</h4>
                                                                </div>
                                                            </div>

                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xxl-6 col-xl-12">
                                    <div class="row">
                                        <div class="col-xl-12">
                                            <div class="card custom-card">
                                                <!-- <div class="card-header justify-content-between">
                                                        <div class="card-title">Earnings</div>
                                                        <div class="dropdown">
                                                            <a href="javascript:void(0);" class="p-2 fs-12 text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                                View All<i class="ri-arrow-down-s-line align-middle ms-1 d-inline-block"></i>
                                                            </a>
                                                            <ul class="dropdown-menu" role="menu">
                                                                <li><a class="dropdown-item" href="javascript:void(0);">Download</a></li>
                                                                <li><a class="dropdown-item" href="javascript:void(0);">Import</a></li>
                                                                <li><a class="dropdown-item" href="javascript:void(0);">Export</a></li>
                                                            </ul>
                                                        </div>
                                                    </div> -->
                                                <div class="card-body">
                                                    <!-- <div class="row ps-lg-5 mb-4 pb-4 gy-sm-0 gy-3">
                                                            <div class="col-xl-4 col-lg-4 col-md-4 col-sm-4">
                                                                <div class="mb-1 earning first-half ms-3">First Half</div>
                                                                <div class="mb-0">
                                                                    <span class="mt-1 fs-16 fw-semibold">$51.94k</span>
                                                                    <span class="text-success"><i class="fa fa-caret-up mx-1"></i>
                                                                        <span class="badge bg-success-transparent text-success px-1 py-2 fs-10">+0.9%</span></span>
                                                                </div>
                                                            </div>
                                                            <div class="col-xl-4 col-lg-4 col-md-4 col-sm-4">
                                                                <div class="mb-1 earning top-gross ms-3">Top Gross</div>
                                                                <div class="mb-0">
                                                                    <span class="mt-1 fs-16 fw-semibold">$18.32k</span>
                                                                    <span class="text-success"><i class="fa fa-caret-up mx-1"></i>
                                                                        <span class="badge bg-success-transparent text-success px-1 py-2 fs-10">+0.39%</span></span>
                                                                </div>
                                                            </div>
                                                            <div class="col-xl-4 col-lg-4 col-md-4 col-sm-4">
                                                                <div class="mb-1 earning second-half ms-3">Second Half</div>
                                                                <div class="mb-0">
                                                                    <span class="mt-1 fs-16 fw-semibold">$38k</span>
                                                                    <span class="text-danger"><i class="fa fa-caret-up mx-1"></i>
                                                                        <span class="badge bg-danger-transparent text-danger px-1 py-2 fs-10">-0.15%</span></span>
                                                                </div>
                                                            </div>
                                                        </div> -->
                                                    <div id="earnings" style="min-height: 215px;">

                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                        <div class="tab-pane text-muted" id="training-overview" role="tabpanel">
                            <div class="row">
                                <div class="col-xxl-8 col-xl-12">
                                    <div class="card custom-card">

                                        <div class="card-body">
                                            <!-- <canvas id="chartjs-line" class="chartjs-chart"></canvas> -->
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xxl-4 col-xl-12">

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>



        </div>
    </div>

    @push('newscripts')
        
    @endpush

@endsection
