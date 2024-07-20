@extends('layouts.app')

@section('title', $companyName .' - Phishing awareness training program')

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
                                href="#phish-overview" aria-selected="false" tabindex="-1">Yearly Phishing
                                Overview</a>
                        </li>
                        <!-- <li class="nav-item" role="presentation">
                                        <a class="nav-link" data-bs-toggle="tab" role="tab" aria-current="page" href="#training-overview" aria-selected="false" tabindex="-1">Yearly Training Overview</a>
                                    </li> -->
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane text-muted active show" id="phish-overview" role="tabpanel">
                            <div class="row">
                                <div class="col-xxl-8 col-xl-12">
                                    <div class="card custom-card">

                                        <div class="card-body">
                                            <canvas id="chartjs-line" class="chartjs-chart"></canvas>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xxl-4 col-xl-12">
                                    <div class="card custom-card">
                                        <div class="card-header">
                                            <div class="card-title">Emails and Trainings</div>
                                        </div>
                                        <div class="card-body">
                                            <canvas id="chartjs-doughnut" class="chartjs-chart"></canvas>
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




            <!-- Start::row-1 -->
            <div class="row">
                <div class="col-lg-3">
                    <div class="card custom-card overflow-hidden">
                        <div class="card-body">
                            <div class="d-flex align-items-top justify-content-between">
                                <div>
                                    <span class="avatar avatar-md avatar-rounded bg-primary">
                                        <i class='bx bx-mail-send fs-16'></i>
                                    </span>
                                </div>
                                <div class="flex-fill ms-3">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap">
                                        <div>
                                            <p class="text mb-0">Active Campaigns</p>
                                            <h4 class="fw-semibold mt-1">{{ $data['active_campaigns'] }}</h4>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="card custom-card overflow-hidden">
                        <div class="card-body">
                            <div class="d-flex align-items-top justify-content-between">
                                <div>
                                    <span class="avatar avatar-md avatar-rounded bg-info">
                                        <i class='bx bx-envelope fs-16'></i>
                                    </span>
                                </div>
                                <div class="flex-fill ms-3">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap">
                                        <div>
                                            <p class="text mb-0">Phishing Emails</p>
                                            <h4 class="fw-semibold mt-1">{{ $data['phishing_emails'] }}</h4>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="card custom-card overflow-hidden">
                        <div class="card-body">
                            <div class="d-flex align-items-top justify-content-between">
                                <div>
                                    <span class="avatar avatar-md avatar-rounded bg-success">
                                        <i class='bx bx-globe fs-16'></i>
                                    </span>
                                </div>
                                <div class="flex-fill ms-3">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap">
                                        <div>
                                            <p class="text mb-0">Phishing Websites</p>
                                            <h4 class="fw-semibold mt-1">{{ $data['phishing_websites'] }}</h4>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="card custom-card overflow-hidden">
                        <div class="card-body">
                            <div class="d-flex align-items-top justify-content-between">
                                <div>
                                    <span class="avatar avatar-md avatar-rounded bg-secondary">
                                        <i class='bx bx-book-content fs-16'></i>
                                    </span>
                                </div>
                                <div class="flex-fill ms-3">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap">
                                        <div>
                                            <p class="text mb-0">Training Modules</p>
                                            <h4 class="fw-semibold mt-1">{{ $data['training_modules'] }}</h4>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End::row-1 -->

        </div>
    </div>

    @push('newscripts')
        <!-- Include Axios -->
        <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Get pie chart data
                axios.get('{{ route('get.pie.data') }}')
                    .then(function(response) {
                        var pieData = response.data;
                        new Chart(document.getElementById('chartjs-doughnut'), {
                            type: 'doughnut',
                            data: {
                                labels: [
                                    'Emails Delivered',
                                    'Emails Viewed',
                                    'Training Assigned',
                                    'Training Completed'
                                ],
                                datasets: [{
                                    data: [
                                        pieData.total_emails_delivered,
                                        pieData.total_emails_viewed,
                                        pieData.total_training_assigned,
                                        pieData.total_training_completed
                                    ],
                                    backgroundColor: [
                                        'rgb(255, 99, 132)',
                                        'rgb(0, 153, 51)',
                                        'rgb(0, 204, 102)',
                                        'rgb(0, 191, 255)'
                                    ],
                                    hoverOffset: 4
                                }]
                            }
                        });
                    })
                    .catch(function(error) {
                        console.error('Error fetching pie data:', error);
                    });

                // Get line chart data
                axios.get('{{ route('get.line.chart.data') }}')
                    .then(function(response) {
                        var lineChartData = response.data;
                        var labels = lineChartData.map(function(data) {
                            return data.month;
                        });
                        var data = {
                            labels: labels,
                            datasets: [{
                                label: 'Campaigns ran in last 6 months',
                                backgroundColor: 'rgb(132, 90, 223)',
                                borderColor: 'rgb(132, 90, 223)',
                                data: lineChartData.map(function(data) {
                                    return data.no_of_camps;
                                }),
                            }]
                        };
                        var config = {
                            type: 'line',
                            data: data,
                            options: {}
                        };
                        new Chart(document.getElementById('chartjs-line'), config);
                    })
                    .catch(function(error) {
                        console.error('Error fetching line chart data:', error);
                    });

                    axios.get('{{ route('get.total.assets') }}')
            });
        </script>
    @endpush

@endsection
