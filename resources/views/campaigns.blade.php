@extends('layouts.app')

@section('title', 'Campaigns - Phishing awareness training program')

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
                                    <div class="mb-2">Since Last Campaign Delivery</div>
                                    <div class="text-muted mb-1 fs-12">
                                        <span class="text-dark fw-semibold fs-20 lh-1 vertical-bottom">
                                            {{ $daysSinceLastDelivery }} Day(s)
                                        </span>
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
                                    <div class="mb-2">Total Sent Emails</div>
                                    <div class="text-muted mb-1 fs-12">
                                        <span class="text-dark fw-semibold fs-20 lh-1 vertical-bottom">
                                            {{ $all_sent }} Delivered
                                        </span>
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
                                        <i class="bx bx-envelope-open fs-4"></i>
                                    </span>
                                </div>
                                <div class="col-xxl-9 col-xl-10 col-lg-9 col-md-9 col-sm-8 col-8 px-0">
                                    <div class="mb-2">Mail Opened</div>
                                    <div class="text-muted mb-1 fs-12">
                                        <span class="text-dark fw-semibold fs-20 lh-1 vertical-bottom">
                                            {{ $mail_open }} Opened
                                        </span>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#newCampModal">New
                Campaign</button>

            <div class="row">
                <div class="col-xl-12">
                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">
                                Manage Campaign
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Campaign Name</th>
                                            <th>Campaign Type</th>
                                            <th>Status</th>
                                            <th>Employees Group</th>
                                            <th>Launch Time</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($allCamps as $index => $campaign)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>
                                                    @if ($campaign->status != 'Not Scheduled')
                                                        <a href="#" class="text-primary"
                                                            onclick="fetchCampaignDetails('{{ e($campaign->campaign_id) }}')"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#campaignReportModal">{{ e($campaign->campaign_name) }}</a>
                                                    @else
                                                        {{ e($campaign->campaign_name) }}
                                                    @endif
                                                </td>
                                                <td>{{ $campaign->campaign_type }}</td>
                                                <td>
                                                    @if ($campaign->status == 'completed')
                                                        <span class="badge bg-success">Completed</span>
                                                    @elseif ($campaign->status == 'pending')
                                                        <span class="badge bg-warning">Pending</span>
                                                    @elseif ($campaign->status == 'Not Scheduled')
                                                        <span class="badge bg-warning">Not Scheduled</span>
                                                    @else
                                                        <span class="badge bg-success">Running</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    {{ $campaign->usersGroup->group_name }}

                                                </td>
                                                <td>
                                                    <div>

                                                        @if ($campaign->launch_type == 'schLater')
                                                            <small class="text-danger">
                                                                Not scheduled
                                                            </small>
                                                        @else
                                                            <span
                                                                class="badge bg-info-transparent">{{ $campaign->launch_type }}</span>
                                                        @endif


                                                    </div>

                                                    {{ e($campaign->launch_time) }}

                                                    <div>
                                                        <small class="text-muted">
                                                            @if ($campaign->email_freq == 'one')
                                                                Once
                                                            @else
                                                                {{ $campaign->email_freq }}
                                                            @endif

                                                        </small>
                                                    </div>
                                                </td>
                                                <td>
                                                    {{-- {!! $campaign->relaunch_btn ?? '' !!} --}}
                                                    @if ($campaign->status == 'pending' || $campaign->status == 'Not Scheduled')
                                                        {{-- reschedule button --}}
                                                        <button
                                                            class="btn btn-icon btn-primary-transparent rounded-pill btn-wave"
                                                            data-bs-toggle="modal" data-bs-target="#reschedulemodal"
                                                            title="{{ $campaign->status == 'Not Scheduled' ? 'Schedule Campaign' : 'Re-Schedule Campaign' }}"
                                                            onclick="reschedulecampid(`{{ $campaign->id }}`)">
                                                            <i class="ri-time-line"></i>
                                                        </button>
                                                    @elseif ($campaign->status == 'completed')
                                                        {{-- relaunch button --}}
                                                        <button
                                                            class="btn btn-icon btn-secondary-transparent rounded-pill btn-wave"
                                                            onclick="relaunch_camp(`{{ $campaign->campaign_id }}`)"
                                                            title="Re-Launch">
                                                            <i class="ri-loop-left-line"></i>
                                                        </button>
                                                    @endif

                                                    <button
                                                        class="btn btn-icon btn-danger-transparent rounded-pill btn-wave"
                                                        title="Delete"
                                                        onclick="deletecampaign('{{ e($campaign->campaign_id) }}')">
                                                        <i class="ri-delete-bin-line"></i>
                                                    </button>

                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center">No campaigns running</td>
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

    {{-- --------------------------------------------Toasts---------------------- --}}

    <x-toast />


    {{-- --------------------------------------- modals ---------------------- --}}

    <!-- new campaign modal -->
    <x-modal id="newCampModal" size="modal-xl" heading="Add New Campaign">
        <x-campaign.new-campaign-body :usersGroups="$usersGroups" :phishingEmails="$phishingEmails" :trainingModules="$trainingModules" />
    </x-modal>

    <!-- campaign report modal -->
    <x-modal id="campaignReportModal" size="modal-fullscreen" heading="Campaign Report">
        <x-campaign.campaign-report-body />
    </x-modal>

    <!-- view material modal -->
    <x-modal id="viewMaterialModal" size="modal-dialog-centered modal-lg" heading="Phishing Material">
        <x-campaign.phishing-material-preview />
    </x-modal>


    <!-- re-schedule campaign modal -->
    <x-modal id="reschedulemodal" size="modal-lg" heading="Re-Schedule Campaign">
        <x-campaign.re-sch-camp-body />
    </x-modal>




    @push('newcss')
        <link rel="stylesheet" href="assets/css/campaigns.css">
    @endpush

    @push('newscripts')
        <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

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


        <script src="{{ asset('js/campaigns.js') }}"></script>

        <script>
            function showMaterialDetails(btn, name, subject, website, senderProfile) {

                $("#viewMaterialModal").modal('show');
                $("#newCampModal").hide();

                var htmlmaterial = $(btn).parent().parent().parent().prev().html();
                $("#vphishEmail").val(name);
                $("#vSub").val(subject);

                $.post({
                    url: '/campaigns/fetch-phish-data',
                    data: {
                        fetchPhisData: 1,
                        website: website,
                        senderProfile: senderProfile
                    },
                    success: function(jsonRes) {
                        $("#websitePrev iframe").attr('src',
                            `https://${jsonRes.website_url}/${jsonRes.website_file}`);
                        $("#vphishWeb").val(jsonRes.website_name);


                        $("#vPhishUrl").val("https://" + jsonRes.website_url);
                        $("#vsenderProf").val(jsonRes.senderProfile);
                        $("#vDispName").val(jsonRes.displayName + "<" + jsonRes.address + ">");

                        // window.location.reload()
                        // window.location.href = window.location.href;
                    }
                })


                $("#phishPrev").html(htmlmaterial)
                // console.log(phishName);
            }

            function showPhishingReportIndividual(res) {
                if (res.camp_live.length > 0) {
                    let mailPending = '<span class="badge bg-warning-transparent">Pending</span>';
                    let mailSent = '<span class="badge bg-success-transparent">Success</span>';
                    let yesBatch = '<span class="badge bg-success-transparent">Yes</span>';
                    let noBatch = '<span class="badge bg-danger-transparent">No</span>';

                    let rowHtml = '';
                    res.camp_live.forEach((camp) => {
                        let isDelivered = camp.sent === "0" ? mailPending : mailSent;
                        let isViewed = camp.mail_open === 0 ? noBatch : yesBatch;
                        let isPayLoadClicked = camp.payload_clicked === 0 ? noBatch : yesBatch;
                        let isEmpCompromised = camp.emp_compromised === 0 ? noBatch : yesBatch;
                        let isEmailReported = camp.email_reported === 0 ? noBatch : yesBatch;

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

                    $("#campReportsIndividual").html(rowHtml);
                }
            }

            function showPhishingReport(res) {
                let greenCheck = '<i class="bx bx-check-circle text-success fs-25"></i>';
                let redCheck = '<i class="bx bx-check-circle text-danger fs-25"></i>';

                let status = '';
                if (res.status === 'completed') {
                    status = '<span class="badge bg-success">Completed</span>';
                } else if (res.status === 'pending') {
                    status = '<span class="badge bg-warning">Pending</span>';
                } else {
                    status = '<span class="badge bg-success">Running</span>';
                }

                let rowHtml = `
                                <tr>
                                    <th scope="row">${res.camp_report.campaign_name}</th>
                                    <td>${status}</td>
                                    <td>${res.camp_live.length}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="mx-1">${res.camp_report.emails_delivered}</span>
                                            ${res.camp_report.emails_delivered > 0 ? greenCheck : redCheck}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="mx-1">${res.camp_report.emails_viewed}</span>
                                            ${res.camp_report.emails_viewed > 0 ? greenCheck : redCheck}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="mx-1">${res.camp_report.payloads_clicked}</span>
                                            ${res.camp_report.payloads_clicked > 0 ? greenCheck : redCheck}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="mx-1">${res.camp_report.emp_compromised}</span>
                                            ${res.camp_report.emp_compromised > 0 ? greenCheck : redCheck}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="mx-1">${res.camp_report.email_reported}</span>
                                            ${res.camp_report.email_reported > 0 ? greenCheck : redCheck}
                                        </div>
                                    </td>
                                </tr>
                            `;

                $("#campReportStatus").html(rowHtml);

                showPhishingReportIndividual(res);
            }

            function showTrainingReportIndividual(res) {

                if (res.training_assigned_users.length > 0) {
                    $.post({
                        url: "/campaigns/fetch-training-individual",
                        data: {
                            fetchCampTrainingDetailsIndividual: '1',
                            campaignId: res.campaign_id
                        },
                        success: function(res) {
                            //console.log(res)
                            $("#trainingReportsIndividual").html(res.html)


                        }
                    })
                }

            }

            function showTrainingReport(res) {
                let greenCheck = '<i class="bx bx-check-circle text-success fs-25"></i>';
                let redCheck = '<i class="bx bx-check-circle text-danger fs-25"></i>';

                let status = '';
                if (res.status === 'completed') {
                    status = '<span class="badge bg-success">Completed</span>';
                } else if (res.status === 'pending') {
                    status = '<span class="badge bg-warning">Pending</span>';
                } else {
                    status = '<span class="badge bg-success">Running</span>';
                }

                let rowHtml = `
                                <tr>
                                    <th scope="row">${res.campaign_name}</th>
                                    <td>${status}</td>
                                    <td>${res.camp_live.length}</td>
                                    <td>${res.camp_report.training_assigned}</td>
                                    <td>${res.training_type == 'static_training' ? 
                                        '<span class="badge bg-info">Static Training</span>' : 
                                        '<span class="badge bg-info">AI Training</span>'}</td>
                                    <td>${res.camp_report.training_lang}</td>
                                    <td>${res.camp_report.training_completed}</td>
                                </tr>
                            `;

                $("#trainingReportStatus").html(rowHtml);

                 showTrainingReportIndividual(res);
            }

            function fetchCampaignDetails(campid) {
                // console.log(campid)
                $("#training_tab").hide();
                $("#training_tab a").removeClass('active');
                $("#training_campaign").removeClass("active show");
                $("#phishing_tab").hide();
                $("#phishing_tab a").removeClass('active');
                $("#phishing_campaign").removeClass("active show");

                $.post({
                    url: '/campaigns/fetch-campaign-detail',
                    data: {
                        campaignId: campid
                    },
                    success: function(response) {

                        console.log(response);



                        if (response.campaign_type === "Phishing") {
                            $("#phishing_tab").show();
                            $("#phishing_tab a").addClass('active');
                            $("#phishing_campaign").addClass("active show");

                            showPhishingReport(response);

                        }
                        if (response.campaign_type === "Training") {
                            $("#training_tab").show();
                            $("#training_tab a").addClass('active');
                            $("#training_campaign").addClass("active show");

                            showTrainingReport(response);
                        }
                        if (response.campaign_type === "Phishing & Training") {
                            $("#phishing_tab").show();
                            $("#phishing_tab a").addClass('active');
                            $("#phishing_campaign").addClass("active show");
                            $("#training_tab").show();

                            showPhishingReport(response);
                            showTrainingReport(response);
                        }

                        return;

                    }
                });






            }

            $('#datatable-basic').DataTable({
                language: {
                    searchPlaceholder: 'Search...',
                    sSearch: '',
                },
                "pageLength": 10,
                // scrollX: true
            });

            $('#viewMaterialModal').on('hidden.bs.modal', function() {
                // Your function here
                $("#newCampModal").show();
            });
        </script>

        <!-- Date & Time Picker JS -->
        <script src="assets/libs/flatpickr/flatpickr.min.js"></script>
        <!-- <script src="assets/js/date&time_pickers.js"></script> -->
        <script>
            flatpickr(".datetime", {
                enableTime: true,
                dateFormat: "m/d/Y H:i",
            });

            flatpickr("#schBetRange", {

                minDate: "today",
                defaultDate: "today",
                dateFormat: "Y-m-d",
            });
            flatpickr("#rschBetRange", {
                minDate: "today",
                defaultDate: "today",
                dateFormat: "Y-m-d",
            });
            flatpickr("#expire_after", {
                dateFormat: "Y-m-d",
                minDate: "today"
            });
            flatpickr("#rexpire_after", {
                dateFormat: "Y-m-d",
                minDate: "today"
            });
        </script>
    @endpush

@endsection
