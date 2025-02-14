<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HTML to PDF with Graph</title>
    
<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

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
    font-size: 46px;
    font-weight: 600;
    color: #595cff;
    margin-left: 20px;
    font-family: system-ui;
}
.of {
    font-size: 35px;
    font-weight: 600;
    color: #ff7b4a;
    margin-left: 20px;
    font-family: system-ui;
}
.phising {
    font-size: 35px;
    font-weight: 600;
    color: #00c3ff;
    margin-left: 20px;
    font-family: system-ui;
}
.flex-box-design {
    display: flex
;
    margin-bottom: 5px;
}
        .container_pdf_body {
          background: linear-gradient(to bottom, #bef7ff, #cbccff);
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
    background: linear-gradient(to right, #111c43, #2b47a9);
    padding: 10px 20px;
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
    /* justify-content: center; */
    align-items: center;
}
.width-graph {
    width: 60px;
}
.flex-box_2 {
    display: flex
;
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
}
#chart{
/* display: none; */
width: fit-content
}
#chart_2{
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
       <div class="flex-box">
				<div><img class="width-graph" src="{{ asset('images/graph.png') }}" alt=""></div>
				<div>
					<span class="reporting">Reporting</span> <span class="of">Of</span>
					<span class="phising">Phishing</span>
				</div>
			</div>
<div class="flex-box-design">
				<div class="padding-border-design-0"></div>

				<div class="padding-border-design"></div>
			</div>

        <!-- Chart Container -->
<div class="flex-box_2">
							<div>
								<img class="image-presentation" src="{{ asset('images/presentation.png') }}" alt="">
							</div>
							<div>
								<span class="reporting_2">Phishing</span>
								<span class="of_2">Campaings Reports</span>
							</div>
						</div>
<div class="blue-background">
							<div>
								<img class="image-presentation" src="{{ asset('images/presentation.png') }}" alt="">
							</div>
							<div>
								<span class="details">Graph</span>
								<span class="user">Details</span>
							</div>
						</div>



<div style="display: flex;">
<div style="width: 40%" >
<div id="chart" style="background: white; margin-top: 16px;"></div>
</div>
<div style="width: 40%; margin-left: auto;margin-right: 40px;">
<div class="blue-background">
							<div>
								<img class="image-presentation" src="{{ asset('images/presentation.png') }}" alt="">
							</div>
							<div>
								<span class="details">Graph</span>
								<span class="user">Details</span>
							</div>
						</div>
<div style="background: white; padding: 20px; margin-top: 15px;">

<div style="display: flex; justify-content: space-between; align-items: center;">
											<div style="display: flex">
												<img style="width: 30px" src="{{ asset('images/chart.png') }}" alt="">
												<div style="margin-left: 20px" class="total_user">
														Analysis Data
												</div>
											</div>
											{{-- <div class="total_user_count">100</div> --}}
										</div>




@php
    $campaign_details = session('campaign_details', []);
@endphp


@foreach($campaign_details as $key => $value)
    <div style="
        display: flex;
        justify-content: space-between;
        margin-top: 20px;
    ">
        <div style="display: flex">
            <img style="width: 20px" src="{{ asset('images/graph_2.png') }}" alt="">
            <div style="margin-left: 20px" class="total_user">
                {{ ucfirst(str_replace('_', ' ', $key)) }} <!-- Converts "email_reported" to "Email Reported" -->
            </div>
        </div>
        <div class="total_user_count">{{ $value }}</div>
    </div>
@endforeach




</div>
</div>


</div>
{{-- next  --}}
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
								<img class="image-presentation" src="http://127.0.0.1:8000/images/presentation.png" alt="">
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

@foreach($training_campaign_details as $key => $value)
    <div style="
        display: flex;
        justify-content: space-between;
        margin-top: 20px;
    ">
        <div style="display: flex">
            <img style="width: 20px" src="{{ asset('images/graph_2.png') }}" alt="">
            <div style="margin-left: 20px" class="total_user">
                {{ ucfirst(str_replace('_', ' ', $key)) }} <!-- Converts "email_reported" to "Email Reported" -->
            </div>
        </div>
        <div class="total_user_count">{{ $value }}</div>
    </div>
@endforeach





</div>
</div>
</div>
<div class="flex-box_2">
							<div>
								<img class="image-presentation" src="http://127.0.0.1:8000/images/presentation.png" alt="">
							</div>
							<div>
								<span class="reporting_2">Phishing</span>
								<span class="of_2">Datatable</span>
							</div>
						</div>


{{-- <table style="margin-top: 100px; background: #fff"  class="table table-bordered border-dark"> --}}
<table style="margin-top: 100px; background: #fff"  class="table">

  <thead style=" background: linear-gradient(to right, #111c43, #2b47a9);"  class="table-dark">
    <tr>
      <th style="font-size: 12px"  scope="col">Employee Name</th>
      <th style="font-size: 12px"  scope="col">Email <div>Address
</div>  </th>
      <th style="font-size: 12px"  scope="col">Email Delivery</th>
      <th style="font-size: 12px"  scope="col">Email Viewed</th>
      <th style="font-size: 12px"  scope="col">Payload Clicked</th>
      <th style="font-size: 12px"  scope="col">Employee Compromised</th>
      <th style="font-size: 12px"  scope="col">Email Reported</th>
    </tr>
  </thead>
  <tbody id="ReportsIndividual">
    
  </tbody>
</table>


{{-- <div class="table-responsive">
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
</table>
<tbody id="ReportsIndividual"></tbody>
</div> --}}

</div>
        

    </div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
   var options = {
    series: [
        {{ session('campaign_details.emails_delivered', 0) }},
        {{ session('campaign_details.emails_viewed', 0) }},
        {{ session('campaign_details.email_reported', 0) }},
        {{ session('campaign_details.emp_compromised', 0) }}
    ],
    chart: {
        width: 380,
        type: 'polarArea'
    },
    labels: ['Emails Delivered', 'Emails Viewed', 'Email Reported', 'Emp Compromised'],
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
    theme: {
        monochrome: {
            enabled: true,
            shadeTo: 'light',
            shadeIntensity: 0.6
        }
    }
};

var chart = new ApexCharts(document.querySelector("#chart"), options);
chart.render();




// chart 2 
     var options = {
          series: [{{ session('training_campaign_details.training_assigned', 0) }},
        {{ session('training_campaign_details.training_completed', 0) }},
        {{ session('training_campaign_details.total_training', 0) }}],
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
      

        // PDF Export Function
        document.getElementById('downloadPdf').addEventListener('click', function () {

            const element = document.getElementById('content'); // Select the content to convert
            html2pdf()
                .from(element)
                .set({
                    margin: 0,
                    filename: 'document.pdf',
                    image: { type: 'jpeg', quality: 0.98 },
                    html2canvas: { scale: 2 },
                    jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
                })
                .save();
        });
 var campaignData = @json($camp_live);
    console.log('campaignData', campaignData);

    $(document).ready(function () {
        if (campaignData.length > 0) {
            let mailPending  = `
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
            let mailSent  = `
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
            let yesBatch =  `
  <span style="
    color: white;
    background: #ff3c3c;
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
        color: #ff3c3c;
    border: 1px solid #ff3c3c;
  
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
                let isDelivered = camp.sent == "0" ? mailPending : mailSent;
                let isViewed = camp.mail_open == 0 ? noBatch : yesBatch;
                let isPayLoadClicked = camp.payload_clicked == 0 ? noBatch : yesBatch;
                let isEmpCompromised = camp.emp_compromised == 0 ? noBatch : yesBatch;
                let isEmailReported = camp.email_reported == 0 ? noBatch : yesBatch;

                rowHtml += `
                    <tr>
                        <td>${camp.user_name}</td>
                        <td>${camp.user_email}</td>
                        <td>${isDelivered}</td>
                        <td>${isViewed}</td>
                        <td>${isPayLoadClicked}</td>
                        <td>${isEmpCompromised}</td>
                        <td>${isEmailReported}</td>
                    </tr>
                `;
            });

            $("#ReportsIndividual").html(rowHtml);
        }
    });
    </script>


</body>
</html>
