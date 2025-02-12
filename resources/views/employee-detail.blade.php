@extends('layouts.app')

@section('title', $employee->user_name . ' - Phishing awareness training program')

@section('main-content')

    <div class="main-content app-content">
        <div class="container-fluid mt-4">

            <div class="card-body">
                <ul class="nav nav-pills justify-content-start nav-style-3" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active" data-bs-toggle="tab" role="tab" aria-current="page" href="#ecamp"
                            aria-selected="true">
                            Email Campaign
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" data-bs-toggle="tab" role="tab" aria-current="page" href="#wcamp"
                            aria-selected="false" tabindex="-1">WhatsApp Campaign</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" data-bs-toggle="tab" role="tab" aria-current="page" href="#aicamp"
                            aria-selected="false" tabindex="-1">AI Vishing</a>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane show active text-muted" id="ecamp" role="tabpanel">
                        <div class="row my-3">
                            <div class="col-md-8">
                                <x-employee.emp-info :employee="$employee" :linkClicks="$employee->campaigns?->sum('payload_clicked') ?? 0" :totalCampaigns="$employee->campaigns?->count() ?? 0"
                                    :totalTrainings="$employee->assignedTrainings?->count() ?? 0" />

                            </div>
                            <div class="col-md-4">
                                <x-employee.emp-security-score cid="email-score" />
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <x-employee.emp-camp-table :employee="$employee" />
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane text-muted" id="wcamp" role="tabpanel">
                        <div class="row my-3">
                            <div class="col-md-8">
                                <x-employee.emp-info :employee="$employee" :linkClicks="$employee->whatsappCamps?->sum('link_clicked') ?? 0" :totalCampaigns="$employee->whatsappCamps?->count() ?? 0"
                                    :totalTrainings="$employee->assignedTrainings?->count() ?? 0" />

                            </div>
                            <div class="col-md-4">
                                <x-employee.emp-security-score cid="wa-score" />
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <x-employee.wa-camp-table :employee="$employee" />
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane text-muted" id="aicamp" role="tabpanel">
                        <div class="row my-3">
                            <div class="col-md-8">
                                {{-- <x-employee.emp-info :employee="$employee" /> --}}
                            </div>
                            <div class="col-md-4">
                                <x-employee.emp-security-score cid="ai-score" />
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <x-employee.ai-camp-table :employee="$employee" />
                            </div>
                        </div>
                    </div>

                </div>
            </div>





        </div>
    </div>





    {{-- ------------------------------Toasts---------------------- --}}

    <x-toast />



    @push('newcss')
    @endpush

    @push('newscripts')
        <script>
            /* sale value chart */
            var eoptions = {
                chart: {
                    height: 229,
                    type: "radialBar",
                },

                series: [
                    {{ intval(($employee->campaigns->where('payload_clicked', 0)->count() / $employee->campaigns->count()) * 100) }}
                ],
                colors: ["rgb(132, 90, 223)"],
                plotOptions: {
                    radialBar: {
                        hollow: {
                            margin: 0,
                            size: "70%",
                            background: "#fff",
                        },
                        track: {
                            dropShadow: {
                                enabled: true,
                                top: 2,
                                left: 0,
                                blur: 2,
                                opacity: 0.15,
                            },
                        },
                        dataLabels: {
                            name: {
                                offsetY: -10,
                                color: "#4b9bfa",
                                fontSize: "16px",
                                show: false,
                            },
                            value: {
                                color: "#4b9bfa",
                                fontSize: "30px",
                                show: true,
                            },
                        },
                    },
                },
                stroke: {
                    lineCap: "round",
                },
                labels: ["Cart"],
            };
            document.querySelector("#email-score").innerHTML = "";
            var chart1 = new ApexCharts(document.querySelector("#email-score"), eoptions);
            chart1.render();

            // function saleValue() {
            //     chart1.updateOptions({
            //         colors: ["rgb(" + myVarVal + ")"],
            //     });
            // }
            /* sale value chart */
        </script>

        <script>
            /* sale value chart */
            var woptions = {
                chart: {
                    height: 229,
                    type: "radialBar",
                },

                series: [
                    {{ intval(($employee->whatsappCamps->where('link_clicked', 0)->count() / $employee->whatsappCamps->count()) * 100) }}
                ],
                colors: ["rgb(132, 90, 223)"],
                plotOptions: {
                    radialBar: {
                        hollow: {
                            margin: 0,
                            size: "70%",
                            background: "#fff",
                        },
                        track: {
                            dropShadow: {
                                enabled: true,
                                top: 2,
                                left: 0,
                                blur: 2,
                                opacity: 0.15,
                            },
                        },
                        dataLabels: {
                            name: {
                                offsetY: -10,
                                color: "#4b9bfa",
                                fontSize: "16px",
                                show: false,
                            },
                            value: {
                                color: "#4b9bfa",
                                fontSize: "30px",
                                show: true,
                            },
                        },
                    },
                },
                stroke: {
                    lineCap: "round",
                },
                labels: ["Cart"],
            };
            document.querySelector("#wa-score").innerHTML = "";
            var chart2 = new ApexCharts(document.querySelector("#wa-score"), woptions);
            chart2.render();

            // function saleValue() {
            //     chart1.updateOptions({
            //         colors: ["rgb(" + myVarVal + ")"],
            //     });
            // }
            /* sale value chart */
        </script>
    @endpush

@endsection
