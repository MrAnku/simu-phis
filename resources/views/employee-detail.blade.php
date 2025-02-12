@extends('layouts.app')

@section('title', $employee->user_name . ' - Phishing awareness training program')

@section('main-content')

    <div class="main-content app-content">
        <div class="container-fluid mt-4">

            <div class="card-body">
                <ul class="nav nav-pills justify-content-start nav-style-3" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active" data-bs-toggle="tab" role="tab" aria-current="page" href="#ecamp" aria-selected="true">
                            Email Campaign
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" data-bs-toggle="tab" role="tab" aria-current="page" href="#wcamp" aria-selected="false" tabindex="-1">WhatsApp Campaign</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" data-bs-toggle="tab" role="tab" aria-current="page" href="#aicamp" aria-selected="false" tabindex="-1">AI Vishing</a>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane show active text-muted" id="ecamp" role="tabpanel">
                        <div class="row my-3">
                            <div class="col-md-8">
                                <x-employee.emp-info :employee="$employee" />
                            </div>
                            <div class="col-md-4">
                                <x-employee.emp-security-score />
                            </div>
                        </div>
            
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <x-employee.emp-camp-table :employee="$employee" />
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane text-muted" id="wcamp" role="tabpanel">
                        How hotel deals can help you live a better life. <b>How celebrity cruises</b>
                        aren't as bad as you think. How cultural solutions can help you predict the
                        future. How to cheat at dog friendly hotels and get away with it. 17 problems
                        with summer activities. How to cheat at travel agents and get away with it. How
                        not knowing family trip ideas makes you a rookie. What everyone is saying about
                        daily deals. How twitter can teach you about carnival cruises. How to start
                        using cultural solutions.
                    </div>
                    <div class="tab-pane text-muted" id="aicamp" role="tabpanel">
                        Unbelievable healthy snack success stories. 12 facts about safe food handling
                        tips that will impress your friends. Restaurant weeks by the numbers. Will
                        mexican food ever rule the world? The 10 best thai restaurant youtube videos.
                        How restaurant weeks can make you sick. The complete beginner's guide to cooking
                        healthy food. Unbelievable food stamp success stories. How whole foods markets
                        are making the world a better place. 16 things that won't happen in dish
                        reviews.
                    </div>
                    <div class="tab-pane text-muted" id="contacts-right" role="tabpanel">
                        Why delicious magazines are killing you. Why our world would end if restaurants
                        disappeared. Why restaurants are on crack about restaurants. How restaurants are
                        making the world a better place. 8 great articles about minute meals. Why our
                        world would end if healthy snacks disappeared. Why the world would end without
                        mexican food. The evolution of chef uniforms. How not knowing food processors
                        makes you a rookie. Why whole foods markets beat peanut butter on pancakes.
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
            var options = {
                chart: {
                    height: 229,
                    type: "radialBar",
                },

                series: [{{ intval($employee->campaigns->where('payload_clicked', 0)->count() / $employee->campaigns->count() * 100) }}],
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
            document.querySelector("#sale-value").innerHTML = "";
            var chart1 = new ApexCharts(document.querySelector("#sale-value"), options);
            chart1.render();

            function saleValue() {
                chart1.updateOptions({
                    colors: ["rgb(" + myVarVal + ")"],
                });
            }
            /* sale value chart */
        </script>
    @endpush

@endsection
