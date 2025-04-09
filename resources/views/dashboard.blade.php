@extends('layouts.app')

@section('title', $companyName . ' - Phishing awareness training program')

@section('main-content')

    <div class="main-content app-content">
        <div class="container-fluid mt-4">
            <div class="row">
                <div class="col-lg-4">
                    <x-dashboard.hi-card />
                    <x-dashboard.package-card :package="$package" :upgrade="$data" />
                    <x-dashboard.attack-vector :activeAIVishing="$activeAIVishing" :activeTprm="$activeTprm" />
                    <x-dashboard.wa-camp-report />
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
                                                        <p class="mb-0 fs-11 op-7 text-muted fw-semibold">{{ __('ACTIVE CAMPAIGNS') }}
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
                                                        <p class="mb-0 fs-11 op-7 text-muted fw-semibold">{{ __('PHISHING EMAILS') }}
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
                                                        <p class="mb-0 fs-11 op-7 text-muted fw-semibold">{{ __('PHISHING WEBSITES') }}
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
                                                        <p class="mb-0 fs-11 op-7 text-muted fw-semibold">{{ __('TRAINING MODULES') }}
                                                        </p>
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
                                    <div class="card-title">{{ __('Campaign Activity') }}</div>
                                </div>

                                <div class="card-body">
                                    <canvas id="chartjs-line" class="chartjs-chart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">{{ __('Emails and Trainings') }}</div>
                                </div>
                                <div class="card-body">
                                    <canvas id="chartjs-doughnut" class="chartjs-chart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-9">
                            <x-dashboard.sim-statics :sdata="$data" />
                        </div>

                        <div class="col-lg-3">
                            <div class="card custom-card">
                                <div class="card-body p-1">
                                    <div class="d-flex align-items-center flex-wrap">
                                        <div id="analytics-views"></div>
                                        <div class="p-2">
                                            <p class="mb-1 text-muted">{{ __('Payload Clicks') }}</p>
                                            <h5 class="fw-semibold mb-0" id="all-payload-clicks"></h5>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card custom-card">
                                <div class="card-body p-1">
                                    <div class="d-flex align-items-center flex-wrap">
                                        <div id="analytics-views-2"></div>
                                        <div class="p-2">
                                            <p class="mb-1 text-muted">{{ __('Email Reported') }}</p>
                                            <h5 class="fw-semibold mb-0" id="all-email-reported"></h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-6">
                            <x-dashboard.recent-campaigns :recentSixCampaigns="$recentSixCampaigns" />
                        </div>
                        <div class="col-lg-6">
                            <x-dashboard.emp-compromised :campaignsWithReport="$campaignsWithReport" :totalEmpCompromised="$totalEmpCompromised" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6">
                            <x-dashboard.os-usage :usageCounts="$usageCounts['os']" />
                        </div>
                        <div class="col-lg-6">
                            <x-dashboard.browser-usage :usageCounts="$usageCounts['browser']" />
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <x-dashboard.breached-emails :breachedEmails="$breachedEmails" />
                    </div>
                </div>
            </div>
        </div>


    </div>

    {{-- -------------------modals ------------ --}}
    <x-modal id="upgradeModal" heading="Request for employees limit upgrade">
        <x-dashboard.upgrade-form :package="$package" />
    </x-modal>


    {{-- -------------------modals ------------ --}}

    {{-- ------------------------------Toasts---------------------- --}}

    <x-toast />


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

            .simplebar-content li {
                margin-bottom: 6px;
            }
        </style>
    @endpush



    @push('newscripts')
        <!-- Include Axios -->

        <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
        <script src="{{ asset('assets') }}/libs/chart.js/chart.min.js"></script>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Get pie chart data
                axios.get('/get-pie-data')
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
                axios.get('/get-line-chart-data')
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
                                        max: Math.max(...lineChartData.map(item => item.no_of_camps)) < 10 ? 10 : Math.max(...lineChartData.map(item => item.no_of_camps)),
                                    }
                                }
                            }
                        };
                        new Chart(document.getElementById('chartjs-line'), config);
                    })
                    .catch(function(error) {
                        console.error('Error fetching line chart data:', error);
                    });

                axios.get('/get-total-assets')
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

        <script>
            /* Subscription Overview Chart */
            var chartData = @json($data['getLinechart2']);

            var options = {
                series: [{
                        name: "{{ __('WhatsApp Simulation') }}",
                        data: chartData.map(item => item.whatsapp_campaigns)
                    },
                    {
                        name: "{{ __('Phishing Simulation') }}",
                        data: chartData.map(item => item.all_campaigns)
                    }
                ],
                chart: {
                    toolbar: {
                        show: false
                    },
                    height: 285,
                    type: 'line',
                    zoom: {
                        enabled: false
                    },
                    dropShadow: {
                        enabled: true,
                        enabledOnSeries: undefined,
                        top: 5,
                        left: 0,
                        blur: 3,
                        color: '#000',
                        opacity: 0.15
                    },
                },
                grid: {
                    borderColor: '#f1f1f1',
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    width: [2, 2],
                    curve: ['smooth', 'smooth'],
                    lineCap: 'butt',
                    dashArray: [0, 0]
                },
                title: {
                    text: undefined,
                },
                legend: {
                    show: true,
                    position: 'top',
                    horizontalAlign: 'center',
                    fontWeight: 600,
                    fontSize: '11px',
                    tooltipHoverFormatter: function(val, opts) {
                        return val + ' - ' + opts.w.globals.series[opts.seriesIndex][opts.dataPointIndex] + ''
                    },
                    labels: {
                        colors: '#74767c',
                    },
                    markers: {
                        width: 7,
                        height: 7,
                        strokeWidth: 0,
                        radius: 12,
                        offsetX: 0,
                        offsetY: 0
                    },
                },
                markers: {
                    discrete: [{
                            seriesIndex: 0,
                            dataPointIndex: 5,
                            fillColor: '#305cfc',
                            strokeColor: '#fff',
                            size: 4,
                            shape: "circle"
                        },
                        {
                            seriesIndex: 0,
                            dataPointIndex: 11,
                            fillColor: '#305cfc',
                            strokeColor: '#fff',
                            size: 4,
                            shape: "circle"
                        },
                        {
                            seriesIndex: 1,
                            dataPointIndex: 10,
                            fillColor: '#23b7e5',
                            strokeColor: '#fff',
                            size: 4,
                            shape: "circle"
                        }, {
                            seriesIndex: 1,
                            dataPointIndex: 4,
                            fillColor: '#23b7e5',
                            strokeColor: '#fff',
                            size: 4,
                            shape: "circle"
                        }
                    ],
                    hover: {
                        sizeOffset: 6
                    }
                },
                yaxis: {
                    title: {
                        style: {
                            color: '#adb5be',
                            fontSize: '14px',
                            fontFamily: 'poppins, sans-serif',
                            fontWeight: 600,
                            cssClass: 'apexcharts-yaxis-label',
                        },
                    },
                    labels: {
                        formatter: function(y) {
                            return y.toFixed(0) + "";
                        },
                        show: true,
                        style: {
                            colors: "#8c9097",
                            fontSize: '11px',
                            fontWeight: 600,
                            cssClass: 'apexcharts-xaxis-label',
                        },
                    }
                },
                xaxis: {
                    type: 'day',
                    categories: chartData.map(item => item.date),
                    axisBorder: {
                        show: true,
                        color: 'rgba(119, 119, 142, 0.05)',
                        offsetX: 0,
                        offsetY: 0,
                    },
                    axisTicks: {
                        show: true,
                        borderType: 'solid',
                        color: 'rgba(119, 119, 142, 0.05)',
                        width: 6,
                        offsetX: 0,
                        offsetY: 0
                    },
                    labels: {
                        rotate: -90,
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
                        }
                    ]
                },
                colors: ["rgb(132, 90, 223)", "#23b7e5"],
            };
            document.querySelector("#subscriptionOverview").innerHTML = " ";
            var chart1 = new ApexCharts(document.querySelector("#subscriptionOverview"), options);
            chart1.render();

            function subOverview() {
                chart1.updateOptions({
                    colors: ["rgb(" + myVarVal + ")", "#23b7e5"],
                })
            }
            /* Subscription Overview Chart */
        </script>
    @endpush

@endsection
