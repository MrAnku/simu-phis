<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TPRM Scoring Report</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Add favicon -->
    <link rel="icon" type="image/png" href="{{ asset('images/simu-icon.png') }}">
    <!-- Load ApexCharts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <!-- Load html2pdf -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.2/html2pdf.bundle.min.js"></script>

    <style>
        body {
            padding: 0px 350px;
            font-family: system-ui;
        }

        .table-dark {
            --bs-table-color: #fff;
            --bs-table-bg: #1c2e6d !important;
            --bs-table-bg: linear-gradient(to right, #111c43, #2b47a9) !important;

        }

        .reporting {
            font-size: 36px;
            font-weight: 600;
            color: #595cff;
            margin-left: 20px;
            font-family: system-ui;
        }

        .of {
            font-size: 35px;
            font-weight: 600;
            color: #ff7b4a;
            margin-left: 7px;
            font-family: system-ui;
        }

        .phising {
            font-size: 35px;
            font-weight: 600;
            color: #00c3ff;
            margin-left: 7px;
            font-family: system-ui;
        }

        .flex-box-design {
            display: flex;
            margin-bottom: 5px;
        }

        .container_pdf_body {
            /* background: linear-gradient(to bottom, #bef7ff, #cbccff); */
            /* margin: auto; */
            padding: 20px;
            /* border: 2px solid #333; */
            /* background-color: #f9f9f9; */
        }

        h2 {
            color: #333;
        }

        p {
            font-size: 16px;
            color: #555;
        }

        button {
            margin-top: 20px;
            padding: 10px 20px;
            font-size: 16px;
            background-color: #28a745;
            color: white;
            border: none;
            cursor: pointer;
        }

        button:hover {
            background-color: #218838;
        }

        .padding-border-design-0 {
            padding: 1px;
            background-color: transparent;
            width: 17%;
        }

        .padding-border-design {
            padding: 2px;
            background-color: #595cff;
            width: 50%;
            border-radius: 28px;
            margin-top: 8px;
        }

        .blue-background {
            background: #252e4b;
            padding: 10px 30px;
            display: flex;
            margin-top: 15px;
        }

        .blue-background_table {
            background: #252e4b;
            padding: 10px 7px;
            display: flex;
            margin-top: 15px;
        }

        .details {
            font-size: 19px;
            font-weight: 600;
            color: #fff;
            margin-left: 20px;
            font-family: system-ui;
        }

        .user {
            font-size: 15px;
            font-weight: 600;
            color: #ff7b4a;
            margin-left: 2px;
            font-family: system-ui;
        }

        .image-presentation {
            width: 30px;
        }

        .flex-box {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .width-graph {
            width: 60px;
        }

        .flex-box_2 {
            display: flex;
            /* justify-content: center; */
            align-items: center;
            margin: 20px 0px;
        }

        .reporting_2 {
            font-size: 28px;
            font-weight: 600;
            color: #595cff;
            margin-left: 20px;
            font-family: system-ui;
        }

        .of_2 {
            font-size: 18px;
            font-weight: 600;
            color: #ff7b4a;
            margin-left: 5px;
            font-family: system-ui;
        }

        .total_user {
            font-size: 18px;
            font-weight: 600;
            color: #606781;
        }

        .total_user_count {
            font-size: 18px;
            font-weight: 600;
            color: #1c274c;
            margin-right: 15px;
        }

        .background-head {
            background: #252e4b;
            padding: 20px 40px;
        }

        .total_user_count_grade {
            font-size: 26px;
            font-weight: 600;
            color: #1c274c;
            border: 2px solid #ff7b4a;
            padding: 8px;
            border-radius: 37px;
            text-align: center;
        }

        #chart {
            /* display: none; */
            width: fit-content
        }

        #chart_2 {
            width: fit-content
        }
    </style>
</head>

<body>

    <button id="downloadPdf">Download as PDF</button>


    @php
        $training_campaign_details = session('training_campaign_details', []);
    @endphp

    {{-- <div>{{ $training_campaign_details['training_assigned'] ?? 2 }}</div> --}}


    <div id="content" class="container_pdf_body">
        <div class="background-head">


            <div class="flex-box ">
                <div>
                    <span class="of">{{ $label }}</span>
                    <span class="of">Report</span>
                    {{-- <span class="phising">Simulation</span> --}}
                </div>
            </div>
            <div style="color: white; font-weight:600; margin-left: 10px;text-align: center">TPRM Grade Report &
                Analytics According to Data</div>
        </div>

        <!-- Chart Container -->




        <div style="display: flex; ">


            <div style="width: 60%; margin-left: auto;margin-right: 40px;">
                <div class="blue-background">

                    <div>

                        <span class="details">
                            Interaction</span>
                        <span class="user">Details</span>
                    </div>
                </div>
                <div style="background: white; padding: 20px; overflow-wrap: anywhere;">
                    <div class="container">

                        <div class="row">
                            <div class="col-sm-6">
                                <div style="padding: 0px 45px">
                                    <div class="total_user_count_grade">


                                        <div class="total_user_count_grade">{{ $Grade }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div>
                                    <p
                                        style="font-weight: 600;color: #ff7b4a;     font-weight: 600;
    color: #ff7b4a;
    line-height: 18px;
    padding: 0px !important;
    margin-bottom: 5px;">
                                        {{ $info_title }}</p>
                                    <span>
                                        <p style="color: #535a71; font-size: 12px;">{{ $info_deatails }}</p>


                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-2">
                            <div class="col-sm-6">
                                <div
                                    style="padding: 5px 0px;  font-weight: 600; display: flex; justify-content: space-between;">

                                    <div style="display:flex;">
                                        <div><img style="width: 20px" src="{{ asset('images/checkmark.png') }}"
                                                alt=""></div>
                                        <div style="margin-left: 8px;">

                                            Total Employee
                                        </div>
                                    </div>
                                    <div>{{ $Total_data }}</div>



                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div style=" padding: 5px 0px; font-weight: 600; display: flex;">

                                    <div style="display:flex;">
                                        <div><img style="width: 20px" src="{{ asset('images/checkmark.png') }}"
                                                alt=""></div>
                                        <div style="margin-left: 8px;">

                                            Emp Compromised
                                        </div>
                                    </div>
                                    <div style="margin-left: 28px;">{{ $Total_emp_compromised }}</div>



                                </div>
                            </div>
                            <div class="col-sm-8 ">
                                <div
                                    style=" padding: 5px 0px;  font-weight: 600; display: flex; justify-content: space-between;">

                                    <div style="display:flex;">
                                        <div><img style="width: 20px" src="{{ asset('images/checkmark.png') }}"
                                                alt=""></div>
                                        <div style="margin-left: 8px;">

                                            Emp Not Compromised
                                        </div>
                                    </div>
                                    <div>{{ $Total_emp_compromised_not }}</div>



                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <div style="width: 40%">
                @if ($ArrayCount['array_count'] !== 0)
                    <div id="chart" style="background: white; margin-top: 16px;"></div>
                @else
                    <div style="background: white; margin-top: 16px; padding: 23px; width: 360px; text-align: center;">
                        <img style="width: 200px; text-align: center;" src="{{ asset('images/error.png') }}"
                            alt="">
                        <h5 style="color: #606781; padding-top: 20px;">Oops! No Interaction Found</h5>
                    </div>
                @endif

            </div>
        </div>
        {{-- next  --}}
        <div id="yourDivId">
            <div class="flex-box_2">
                <div>
                    <img class="image-presentation" src="{{ asset('images/presentation.png') }}" alt="">
                </div>
                <div>
                    <span class="reporting_2">Training</span>
                    <span class="of_2">Campaings Reports</span>
                </div>
            </div>

            {{-- blue  --}}

            <div class="blue-background">
                <div>
                    <img class="image-presentation" src="http://127.0.0.1:8000/images/presentation.png" alt="">
                </div>
                <div>
                    <span class="details">Graph</span>
                    <span class="user">Details</span>
                </div>
            </div>


            <div style="display: flex;">
                <div style="width: 40%;">

                    <div id="chart_2" style="background: #fff; margin-top: 15px; padding: 45px;"></div>

                </div>
                <div style="width: 40%; margin-left: auto;;margin-right: 40px;">
                    <div class="blue-background">
                        <div>
                            <img class="image-presentation" src="http://127.0.0.1:8000/images/presentation.png"
                                alt="">
                        </div>
                        <div>
                            <span class="details">Graph</span>
                            <span class="user">Details</span>
                        </div>
                    </div>
                    <div style="background: white; padding: 20px; margin-top: 15px;">

                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex">
                                <img style="width: 30px" src="http://127.0.0.1:8000/images/chart.png" alt="">
                                <div style="margin-left: 20px" class="total_user">
                                    Analysis Data
                                </div>
                            </div>
                            {{-- <div class="total_user_count">100</div> --}}
                        </div>

                        @foreach ($training_campaign_details as $key => $value)
                            <div
                                style="
        display: flex;
        justify-content: space-between;
        margin-top: 20px;
    ">

                                <div class="total_user_count">{{ $value }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>


        {{-- <div class="blue-background_table">

            <div>

                <span class="details">
                    Interaction</span>
                <span class="user">Details</span>
            </div>
        </div> --}}


        {{-- <table style="margin-top: 100px; background: #fff"  class="table table-bordered border-dark"> --}}
        <table style="margin-top: 20px; background: #fff" class="table">

            <thead style=" background: #252e4b;" class="table-dark">
                <tr>
                    <th style="font-size: 12px" scope="col">Campaign Name</th>
                    <th style="font-size: 12px" scope="col">Email Address

                    </th>
                    <th style="font-size: 12px" scope="col">Link Clicked</th>
                    <th style="font-size: 12px" scope="col">Emp Compromised</th>
                    <th style="font-size: 12px" scope="col">Training Assigned</th>

                </tr>
            </thead>
            <tbody id="ReportsIndividual">

            </tbody>
        </table>




    </div>


    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // let campaign_details = session('campaign_details', []);
        // console.log('campaign_details', campaign_details);
        let ArrayData = {!! json_encode($Arraydetails) !!};
        // let ArrayData_2 = [{
        //         "labels": "Emails Delivered"
        //     },
        //     {
        //         "labels": "Emails Viewed"
        //     },
        //     {
        //         "labels": "Email Reported"
        //     },
        //     {
        //         "labels": "Emp Compromised"
        //     }
        // ];
        let ArrayData_2 = {!! json_encode($ArrayData_labels) !!}; // Convert PHP array to JavaScript object1
        console.log('ArrayData_2', ArrayData_2);
        var options = {
            series: Object.values(ArrayData), // Extract numerical values
            chart: {
                width: 380,
                type: 'polarArea'
            },
            labels: ArrayData_2.map(item => item.labels), // Extract labels
            fill: {
                opacity: 1
            },
            stroke: {
                width: 1,
                colors: undefined
            },
            yaxis: {
                show: false
            },
            legend: {
                position: 'bottom'
            },
            plotOptions: {
                polarArea: {
                    rings: {
                        strokeWidth: 0
                    },
                    spokes: {
                        strokeWidth: 0
                    }
                }
            },
            colors: ["#252e4b", '#ff7b4a', '#252e4bc9'], // Red, Yellow, Green
            theme: {
                monochrome: {
                    enabled: false // Disable monochrome mode to use custom colors
                }
            }
        };

        var chart = new ApexCharts(document.querySelector("#chart"), options);
        chart.render();

        // chart 2 
        let training_campaign_details = @json($training_campaign_details);
        console.log('training_campaign_details', training_campaign_details);
        if (training_campaign_details.length === 0) {
            console.log("bun")
            $('#yourDivId').hide(); // Hides the div
            var options = {
                series: [{{ session('training_campaign_details.training_assigned', 0) }},
                    {{ session('training_campaign_details.training_completed', 0) }},
                    {{ session('training_campaign_details.total_training', 0) }}
                ],
                chart: {
                    type: 'polarArea',
                },
                stroke: {
                    colors: ['#fff']
                },
                fill: {
                    opacity: 0.8
                },
                responsive: [{
                    breakpoint: 480,
                    options: {
                        chart: {
                            width: 200
                        },
                        legend: {
                            position: 'bottom'
                        }
                    }
                }]
            };

            var chart = new ApexCharts(document.querySelector("#chart_2"), options);
            chart.render();
        }


        // PDF Export Function
        document.getElementById('downloadPdf').addEventListener('click', function() {

            const element = document.getElementById('content'); // Select the content to convert
            html2pdf()
                .from(element)
                .set({
                    margin: 0,
                    filename: 'document.pdf',
                    image: {
                        type: 'jpeg',
                        quality: 0.98
                    },
                    html2canvas: {
                        scale: 2
                    },
                    jsPDF: {
                        unit: 'mm',
                        format: 'a4',
                        orientation: 'portrait'
                    }
                })
                .save();
        });
        var campaignData = @json($data);
        console.log('campaignData', campaignData);

        $(document).ready(function() {
            if (campaignData.length > 0) {
                let mailPending = `
  <span style="
    color: white;
    background: #ffc107;
    font-size: 12px;
    padding: 3px 10px;
    border-radius: 5px;
    font-weight: 600;
  ">
    Pending
  </span>
`;
                let mailSent = `
  <span style="
   color: #28a745;
    border: 1px solid #28a745;
    font-size: 12px;
    padding: 3px 10px;
    border-radius: 5px;
    font-weight: 600;
  ">
    Success
  </span>
`;
                let yesBatch = `
  <span style="
     color: #198754;
    border: 1px solid #198754;
    font-size: 12px;
    padding: 3px 10px;
    border-radius: 5px;
    font-weight: 600;
  ">
    Yes
  </span>
`;
                let noBatch = `
  <span style="
        color: #ff7b4a;
    border: 1px solid #ff7b4a;
  
    font-size: 12px;
    padding: 3px 10px;
    border-radius: 5px;
    font-weight: 600;
  ">
    No
  </span>
`;
                let rowHtml = '';
                campaignData.forEach((camp) => {

                    let isLinkClicked = camp.payload_clicked == 0 ? noBatch : yesBatch;
                    let isEmpCompromised = camp.emp_compromised == 0 ? noBatch : yesBatch;
                    let isTrainingAssigned = camp.training_assigned == 0 ? noBatch : yesBatch;

                    rowHtml += `
                    <tr>
                        <td>${camp.campaign_name}</td>
                        <td>${camp.user_email}</td>
                        <td>${isLinkClicked}</td>
                        <td>${isEmpCompromised}</td>
                        <td>${isTrainingAssigned}</td>
                        
                    </tr>
                `;
                });

                $("#ReportsIndividual").html(rowHtml);
            }
        });
    </script>


</body>

</html>
