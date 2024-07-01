@extends('layouts.app')

@section('title', 'Reporting - Phishing awareness training program')

@section('main-content')

    <div class="main-content app-content">
        <div class="container-fluid mt-4">

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
                                <div class="col-xxl-9 col-xl-10 col-lg-9 col-md-9 col-sm-8 col-8 px-0">
                                    <div class="mb-2">Phishing Emails Delivered</div>
                                    <div class="text-muted mb-1 fs-12">
                                        <span class="text-dark fw-semibold fs-20 lh-1 vertical-bottom">
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
                                <div class="col-xxl-9 col-xl-10 col-lg-9 col-md-9 col-sm-8 col-8 px-0">
                                    <div class="mb-2">Active &amp; Recurring Campaigns</div>
                                    <div class="text-muted mb-1 fs-12">
                                        <span class="text-dark fw-semibold fs-20 lh-1 vertical-bottom">
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
                                <div class="col-xxl-9 col-xl-10 col-lg-9 col-md-9 col-sm-8 col-8 px-0">
                                    <div class="mb-2">Training Assigned</div>
                                    <div class="text-muted mb-1 fs-12">
                                        <span class="text-dark fw-semibold fs-20 lh-1 vertical-bottom">
                                            {{ $training_assigned }} </span>
                                    </div>

                                </div>
                            </div>
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
                                <table id="datatable-basic" class="table table-bordered text-nowrap w-100">
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
                                                        <span class="badge bg-success">{{ ucfirst($camp->status) }}</span>
                                                    @else
                                                        <span class="badge bg-warning">{{ ucfirst($camp->status) }}</span>
                                                    @endif
                                                </td>
                                                <td>{{ $camp->scheduled_date }}</td>
                                                <td>
                                                    <div class="checkboxesIcon">
                                                        @if ($camp->emails_delivered == 0)
                                                            <span>{{ $camp->emails_delivered }}</span>
                                                            <i class="bx bx-check-circle mx-2 fs-25 text-danger"></i>
                                                        @else
                                                            <span>{{ $camp->emails_delivered }}</span>
                                                            <i class="bx bx-check-circle mx-2 fs-25 text-success"></i>
                                                        @endif

                                                    </div>

                                                </td>
                                                <td>
                                                    <div class="checkboxesIcon">
                                                        @if ($camp->emails_viewed == 0)
                                                            <span>{{ $camp->emails_viewed }}</span>
                                                            <i class="bx bx-check-circle mx-2 fs-25 text-danger"></i>
                                                        @else
                                                            <span>{{ $camp->emails_viewed }}</span>
                                                            <i class="bx bx-check-circle mx-2 fs-25 text-success"></i>
                                                        @endif

                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="checkboxesIcon">
                                                        @if ($camp->training_assigned == 0)
                                                            <span>{{ $camp->training_assigned }}</span>
                                                            <i class="bx bx-check-circle mx-2 fs-25 text-danger"></i>
                                                        @else
                                                            <span>{{ $camp->training_assigned }}</span>
                                                            <i class="bx bx-check-circle mx-2 fs-25 text-success"></i>
                                                        @endif

                                                    </div>

                                                </td>
                                                <td>
                                                    <div class="checkboxesIcon">
                                                        @if ($camp->training_completed == 0)
                                                            <span>{{ $camp->training_completed }}</span>
                                                            <i class="bx bx-check-circle mx-2 fs-25 text-danger"></i>
                                                        @else
                                                            <span>{{ $camp->training_completed }}</span>
                                                            <i class="bx bx-check-circle mx-2 fs-25 text-success"></i>
                                                        @endif

                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td class="text-center" colspan="8">No records found</td>
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

    {{-- -------------------Modals------------------------ --}}

    <!-- campaign report modal -->
    <div class="modal fade" id="campaignReportModal" tabindex="-1" aria-labelledby="exampleModalLgLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl">
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
                                        href="#phishing_campaign" aria-selected="true">Phishing Campaign</a>
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
                    url: '{{ route('campaign.fetchCampaignReport') }}',
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
                        url: '{{ route('campaign.fetchCampReportByUsers') }}',
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
                        url: '{{ route('campaign.fetchCampTrainingDetails') }}',
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
                        url: '{{ route('campaign.fetchCampTrainingDetailsIndividual') }}',
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
    @endpush

@endsection
