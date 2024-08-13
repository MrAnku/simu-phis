@extends('layouts.app')

@section('title', $companyName . ' - Phishing awareness training program')

@section('main-content')

    <div class="main-content app-content">
        <div class="container-fluid mt-4">

            <div class="row">
                <div class="col-lg-4">
                    <div class="card custom-card projects-tracking-card overflow-hidden text-center">
                        <div class="card-body">
                            <img src="../assets/images/media/media-86.svg" alt="" class="mb-1">
                            <div>
                                <span class="fs-15 fw-semibold d-block mt-4">Hi, {{ Auth::user()->full_name }}</span>
                                <span class="d-block fs-11 text-muted mt-2">{{ Auth::user()->company_name }}</span>
                                <a href="{{ route('campaigns') }}"
                                    class="btn btn-primary btn-sm btn-wave waves-effect waves-light mt-3">Start
                                    Simulation</a>
                            </div>
                            <span class="shape-1 text-primary"><i class="ti ti-circle fs-20 fw-bold"></i></span>
                            <span class="shape-2 text-secondary"><i class="ti ti-triangle fs-20 fw-bold"></i></span>
                            <span class="shape-3 text-warning"><i class="ti ti-square fs-20 fw-bold"></i></span>
                            <span class="shape-4 text-info"><i class="ti ti-square-rotated fs-20 fw-bold"></i></span>
                            <span class="shape-5 text-success"><i class="ti ti-pentagon fs-20 fw-bold"></i></span>
                            <span class="shape-6 text-danger"><i class="ti ti-star fs-20 fw-bold"></i></span>
                            <span class="shape-7 text-pink"><i class="ti ti-hexagon fs-20 fw-bold"></i></span>
                            <span class="shape-8 text-teal"><i class="ti ti-octagon fs-20 fw-bold"></i></span>
                            <span class="shape-9 text-primary"><i class="ti ti-circle fs-20 fw-bold"></i></span>
                            <span class="shape-10 text-secondary"><i class="ti ti-triangle fs-20 fw-bold"></i></span>
                            <span class="shape-11 text-warning"><i class="ti ti-square fs-20 fw-bold"></i></span>
                            <span class="shape-12 text-info"><i class="ti ti-square-rotated fs-20 fw-bold"></i></span>
                            <span class="shape-13 text-success"><i class="ti ti-pentagon fs-20 fw-bold"></i></span>
                            <span class="shape-14 text-danger"><i class="ti ti-star fs-20 fw-bold"></i></span>
                            <span class="shape-15 text-pink"><i class="ti ti-hexagon fs-20 fw-bold"></i></span>
                            <span class="shape-16 text-teal"><i class="ti ti-octagon fs-20 fw-bold"></i></span>
                        </div>
                    </div>

                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">Package</div>
                        </div>
                        <div class="card-body">

                            <div class="d-flex align-items-center justify-content-between mb-0">
                                <div>

                                    <p class="mb-0 fs-25 fw-semibold">{{ $package['total_emp'] }} of
                                        {{ $package['alloted_emp'] }} <span
                                            class="text-muted fs-11">{{ $package['used_percent'] }}% of total used</span>
                                    </p>
                                    <span class="text-muted fs-12">Employees</span>
                                </div>
                                <div>
                                    <span class="avatar bg-warning">
                                        <i class="ri-team-line fs-18"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center my-2">
                                <div class="flex-fill">
                                    <div class="progress progress-xs">
                                        <div class="progress-bar bg-indigo" role="progressbar"
                                            style="width: {{ $package['used_percent'] }}%"
                                            aria-valuenow="{{ $package['used_percent'] }}" aria-valuemin="0"
                                            aria-valuemax="100"></div>
                                    </div>
                                    <div class="text-end">

                                        <a href="#"
                                            class="btn btn-success btn-sm btn-wave waves-effect waves-light mt-3">Upgrade</a>
                                    </div>
                                </div>


                            </div>
                        </div>
                    </div>

                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">Active Attack Vector</div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-3">
                                    <a aria-label="anchor" href="{{ route('campaigns') }}"
                                        class="btn btn-danger-light border-0 px-4 py-3 lh-1 rounded"> <i
                                            class="bi bi-envelope-open fs-22"></i> </a>
                                    <p class="mb-0 fs-12 text-muted text-truncate text-center">Phishing</p>
                                </div>
                                <div class="col-lg-3">
                                    <a aria-label="anchor" href="{{ route('whatsapp.campaign') }}"
                                        class="btn btn-success-light border-0 px-4 py-3 lh-1 rounded"> <i
                                            class="bi bi-whatsapp fs-22"></i> </a>
                                    <p class="mb-0 fs-12 text-muted text-truncate text-center">WhatsApp</p>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">WhatsApp Campaign Report</div>
                        </div>
                        <div class="card-body">
                            <div id="bar-group"></div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="row">
                        <div class="col-lg-3">
                            <div class="card custom-card overflow-hidden">
                                <div class="card-body">
                                    <a href="{{ route('campaigns') }}">
                                        <div class="d-flex align-items-top justify-content-between">
                                            <div>
                                                <span class="avatar avatar-md bg-primary">
                                                    <i class='bx bx-mail-send fs-20'></i>
                                                </span>
                                            </div>
                                            <div class="flex-fill ms-3">
                                                <div class="d-flex align-items-center justify-content-between flex-wrap">
                                                    <div>
                                                        <h4 class="fw-semibold mt-1">{{ $data['active_campaigns'] }}</h4>
                                                        <p class="mb-0 fs-11 op-7 text-muted fw-semibold">ACTIVE CAMPAIGNS
                                                        </p>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    </a>

                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="card custom-card overflow-hidden">
                                <div class="card-body">
                                    <a href="{{ route('phishing.emails') }}">
                                        <div class="d-flex align-items-top justify-content-between">
                                            <div>
                                                <span class="avatar avatar-md bg-info">
                                                    <i class='bx bx-envelope fs-20'></i>
                                                </span>
                                            </div>
                                            <div class="flex-fill ms-3">
                                                <div class="d-flex align-items-center justify-content-between flex-wrap">
                                                    <div>
                                                        <h4 class="fw-semibold mt-1">{{ $data['phishing_emails'] }}</h4>
                                                        <p class="mb-0 fs-11 op-7 text-muted fw-semibold">PHISHING EMAILS
                                                        </p>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="card custom-card overflow-hidden">
                                <div class="card-body">
                                    <a href="{{ route('phishing.websites') }}">
                                        <div class="d-flex align-items-top justify-content-between">
                                            <div>
                                                <span class="avatar avatar-md bg-success">
                                                    <i class='bx bx-globe fs-20'></i>
                                                </span>
                                            </div>
                                            <div class="flex-fill ms-3">
                                                <div class="d-flex align-items-center justify-content-between flex-wrap">
                                                    <div>
                                                        <h4 class="fw-semibold mt-1">{{ $data['phishing_websites'] }}</h4>
                                                        <p class="mb-0 fs-11 op-7 text-muted fw-semibold">PHISHING WEBSITES
                                                        </p>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="card custom-card overflow-hidden">
                                <div class="card-body">
                                    <a href="{{ route('trainingmodule.index') }}">
                                    <div class="d-flex align-items-top justify-content-between">
                                        <div>
                                            <span class="avatar avatar-md bg-teal">
                                                <i class='bx bx-book-content fs-20'></i>
                                            </span>
                                        </div>
                                        <div class="flex-fill ms-3">
                                            <div class="d-flex align-items-center justify-content-between flex-wrap">
                                                <div>
                                                    <h4 class="fw-semibold mt-1">{{ $data['training_modules'] }}</h4>
                                                    <p class="mb-0 fs-11 op-7 text-muted fw-semibold">TRAINING MODULES</p>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">Campaign Activity</div>
                                </div>

                                <div class="card-body">
                                    <canvas id="chartjs-line" class="chartjs-chart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
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

                    <div class="row">
                        <div class="col-lg-6">
                            <div class="card custom-card">
                                <div class="card-header  justify-content-between">
                                    <div class="card-title">
                                        Recent Campaigns
                                    </div>

                                </div>
                                <div class="card-body">
                                    <ul class="list-unstyled crm-top-deals mb-0">
                                        @forelse ($recentSixCampaigns as $camp)
                                            <li>
                                                <div class="d-flex align-items-top flex-wrap">
                                                    <div class="me-2">
                                                        <span class="avatar avatar-sm avatar-rounded">
                                                            <img src="https://cdn-icons-png.freepik.com/512/3122/3122573.png"
                                                                alt="">
                                                        </span>
                                                    </div>
                                                    <div class="flex-fill">
                                                        <p class="fw-semibold mb-0">{{ $camp->campaign_name }}</p>
                                                        <span class="text-muted fs-12">{{ $camp->campaign_type }}</span>
                                                    </div>
                                                    <div class="fw-semibold fs-15">{{ $camp->status }}</div>
                                                </div>
                                            </li>
                                        @empty
                                        @endforelse

                                    </ul>
                                </div>
                            </div>

                            <div class="card custom-card">
                                <div class="card-header justify-content-between">
                                    <div class="card-title">
                                        Employee compromised
                                    </div>

                                </div>
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <h4 class="fw-bold mb-0">{{ $totalEmpCompromised ?? 0 }}</h4>
                                        <div class="ms-2">

                                            <span class="text-muted ms-1">Employees compromised</span>
                                        </div>
                                    </div>

                                    <ul class="list-unstyled mb-0 pt-2 crm-deals-status">
                                        @forelse ($campaignsWithReport as $r)
                                            <li class="primary">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <div>{{ $r->campaign_name }}</div>
                                                    <div class="fs-12 text-muted">{{ $r->emp_compromised }}</div>
                                                </div>
                                            </li>
                                        @empty
                                        @endforelse

                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="card custom-card">
                                        <div class="card-body p-1">
                                            <div class="d-flex align-items-center flex-wrap">
                                                <div id="analytics-views"></div>
                                                <div class="p-2">
                                                    <p class="mb-1 text-muted">Payload Clicks</p>
                                                    <h5 class="fw-semibold mb-0" id="all-payload-clicks"></h5>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="card custom-card">
                                        <div class="card-body p-1">
                                            <div class="d-flex align-items-center flex-wrap">
                                                <div id="analytics-views-2"></div>
                                                <div class="p-2">
                                                    <p class="mb-1 text-muted">Email Reported</p>
                                                    <h5 class="fw-semibold mb-0" id="all-email-reported"></h5>
                                                </div>
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
                                    <div class="card-title">
                                        Dark Web Activity (5 Most Recent Breaches)
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table text-nowrap table-striped text-center">
                                            <thead>
                                                <tr>
                                                    <th scope="col">Employee Email</th>
                                                    <th scope="col">Name</th>
                                                    <th scope="col">Breached</th>
                                                </tr>
                                            </thead>
                                            <tbody>

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

    @push('newcss')
        <style>
            .card.custom-card {
                border-radius: .5rem;
                border: 0;
                background-color: var(--custom-white);
                box-shadow: 0 0 11px rgb(10 10 10 / 21%);
                position: relative;
                margin-block-end: 1.5rem;
                width: 100%;
            }
        </style>
    @endpush



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
                        console.log(response.data)
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
                                    return Math.round(data.no_of_camps);
                                }),
                            }]
                        };
                        var config = {
                            type: 'line',
                            data: data,
                            options: {
                                scales: {

                                    y: {
                                        type: 'linear',
                                        min: 0,
                                        max: 10
                                    }
                                }
                            }
                        };
                        new Chart(document.getElementById('chartjs-line'), config);
                    })
                    .catch(function(error) {
                        console.error('Error fetching line chart data:', error);
                    });

                axios.get('{{ route('get.total.assets') }}')
            });
        </script>

        <!-- Apex Charts JS -->
        <script src="assets/libs/apexcharts/apexcharts.min.js"></script>

        {{-- Whatsapp campaign report chart --}}
        <script>
            /* grouped bar chart */
            $(document).ready(function() {
                $.ajax({
                    url: '/whatsappreport-chart-data',
                    method: 'GET',
                    success: function(response) {
                        var options = {
                            series: [{
                                name: 'Link Clicked',
                                data: response.link_clicked
                            }, {
                                name: 'Emp Compromised',
                                data: response.emp_compromised
                            }],
                            chart: {
                                type: 'bar',
                                height: 320
                            },
                            plotOptions: {
                                bar: {
                                    horizontal: true,
                                    dataLabels: {
                                        position: 'top',
                                    },
                                }
                            },
                            grid: {
                                borderColor: '#f2f5f7',
                            },
                            colors: ["#845adf", "#23b7e5"],
                            dataLabels: {
                                enabled: true,
                                offsetX: -6,
                                style: {
                                    fontSize: '10px',
                                    colors: ['#fff']
                                }
                            },
                            stroke: {
                                show: true,
                                width: 1,
                                colors: ['#fff']
                            },
                            tooltip: {
                                shared: true,
                                intersect: false
                            },
                            xaxis: {
                                categories: response.months,
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
                                        cssClass: 'apexcharts-yaxis-label',
                                    },
                                }
                            }
                        };

                        var chart = new ApexCharts(document.querySelector("#bar-group"), options);
                        chart.render();
                    }
                });
            });
        </script>

        <script>
            /* Payload Clicks Chart */
            $(document).ready(function() {
                $.ajax({
                    url: '/dash/get-payload-click-data',
                    method: 'GET',
                    success: function(response) {
                        $("#all-payload-clicks").text(response.payload_clicks);
                        var options = {
                            chart: {
                                height: 120,
                                width: 100,
                                type: "radialBar",
                            },
                            series: [response.percentage],
                            colors: ["#7f4bcc"],
                            plotOptions: {
                                radialBar: {
                                    hollow: {
                                        margin: 0,
                                        size: "50%",
                                        background: "#fff"
                                    },
                                    dataLabels: {
                                        name: {
                                            offsetY: -10,
                                            color: "#010608",
                                            fontSize: "10px",
                                            show: false
                                        },
                                        value: {
                                            offsetY: 5,
                                            color: "#010608",
                                            fontSize: "12px",
                                            show: true,
                                            fontWeight: 800
                                        }
                                    }
                                }
                            },
                            stroke: {
                                lineCap: "round"
                            },
                            labels: ["Payload Clicks"]
                        };

                        document.querySelector("#analytics-views").innerHTML = ""
                        var chart6 = new ApexCharts(document.querySelector("#analytics-views"), options);
                        chart6.render();
                    }
                });
            });
        </script>

        <script>
            /* Email reported Chart */
            $(document).ready(function() {
                $.ajax({
                    url: '/dash/get-emailreported-data',
                    method: 'GET',
                    success: function(response) {
                        $("#all-email-reported").text(response.email_reported);
                        var options = {
                            chart: {
                                height: 120,
                                width: 100,
                                type: "radialBar",
                            },
                            series: [response.percentage],
                            colors: ["#7f4bcc"],
                            plotOptions: {
                                radialBar: {
                                    hollow: {
                                        margin: 0,
                                        size: "50%",
                                        background: "#fff"
                                    },
                                    dataLabels: {
                                        name: {
                                            offsetY: -10,
                                            color: "#010608",
                                            fontSize: "10px",
                                            show: false
                                        },
                                        value: {
                                            offsetY: 5,
                                            color: "#010608",
                                            fontSize: "12px",
                                            show: true,
                                            fontWeight: 800
                                        }
                                    }
                                }
                            },
                            stroke: {
                                lineCap: "round"
                            },
                            labels: ["Email Reported"]
                        };

                        document.querySelector("#analytics-views-2").innerHTML = ""
                        var chart6 = new ApexCharts(document.querySelector("#analytics-views-2"), options);
                        chart6.render();
                    }
                });
            });
        </script>
    @endpush

@endsection
