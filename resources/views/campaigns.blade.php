@extends('layouts.app')

@section('title', __('Campaigns') . ' - ' . __('Phishing awareness training program'))

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
                                    <div class="mb-2">{{ __('Since Last Campaign Delivery') }}</div>
                                    <div class="text-muted mb-1 fs-12">
                                        <span class="text-dark fw-semibold fs-20 lh-1 vertical-bottom">
                                            {{ $daysSinceLastDelivery }} {{ __('Day(s)') }}
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
                                    <div class="mb-2">{{ __('Total Sent Emails') }}</div>
                                    <div class="text-muted mb-1 fs-12">
                                        <span class="text-dark fw-semibold fs-20 lh-1 vertical-bottom">
                                            {{ $all_sent }} {{ __('Delivered') }}
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
                                    <div class="mb-2">{{ __('Mail Opened') }}</div>
                                    <div class="text-muted mb-1 fs-12">
                                        <span class="text-dark fw-semibold fs-20 lh-1 vertical-bottom">
                                            {{ $mail_open }} {{ __('Opened') }}
                                        </span>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal"
                data-bs-target="#newCampModal">{{ __('New Campaign') }}</button>

            <div class="row">
                <div class="col-xl-12">
                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">
                                {{ __('Manage Campaign') }}
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>{{ __('Campaign Name') }}</th>
                                            <th>{{ __('Campaign Type') }}</th>
                                            <th>{{ __('Status') }}</th>
                                            <th>{{ __('Employees Group') }}</th>
                                            <th>{{ __('Launch Time') }}</th>
                                            <th>{{ __('Action') }}</th>
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
                                                        <span class="badge bg-success">{{ __('Completed') }}</span>
                                                    @elseif ($campaign->status == 'pending')
                                                        <span class="badge bg-warning">{{ __('Pending') }}</span>
                                                    @elseif ($campaign->status == 'Not Scheduled')
                                                        <span class="badge bg-warning">{{ __('Not Scheduled') }}</span>
                                                    @else
                                                        <span class="badge bg-success">{{ __('Running') }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    {{ $campaign->usersGroup->group_name }}

                                                </td>
                                                <td>
                                                    <div>

                                                        @if ($campaign->launch_type == 'schLater')
                                                            <small class="text-danger">
                                                                {{ __('Not scheduled') }}
                                                            </small>
                                                        @else
                                                            <span
                                                                class="badge bg-info-transparent">{{ __($campaign->launch_type) }}</span>
                                                        @endif


                                                    </div>

                                                    {{ e($campaign->launch_time) }}

                                                    <div>
                                                        <small class="text-muted">
                                                            @if ($campaign->email_freq == 'one')
                                                                {{ __('Once') }}
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
                                                <td colspan="8" class="text-center">{{ __('No campaigns are running') }}
                                                </td>
                                            </tr>
                                        @endforelse



                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-4">
                                {{ $allCamps->links() }}
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
    <x-modal id="newCampModal" size="modal-xl" heading="{{ __('Add New Campaign') }}">
        <x-campaign.new-campaign-body :usersGroups="$usersGroups" :phishingEmails="$phishingEmails" :trainingModules="$trainingModules" />
    </x-modal>

    <!-- campaign report modal -->
    <x-modal id="campaignReportModal" size="modal-fullscreen" heading="{{ __('Campaign Report') }}">
        <x-campaign.campaign-report-body />
    </x-modal>

    <!-- view material modal -->
    <x-modal id="viewMaterialModal" size="modal-dialog-centered modal-lg" heading="{{ __('Phishing Material') }}">
        <x-campaign.phishing-material-preview />
    </x-modal>


    <!-- re-schedule campaign modal -->
    <x-modal id="reschedulemodal" size="modal-lg" heading="{{ __('Re-Schedule Campaign') }}">
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

        {{-- All Alert's lang conversion of js file --}}
        <script>
            const alertMsgs = {
                title : "{{ __('Are you sure?') }}",
                deleteCampText: "{{ __('Are you sure that you want to delete this Campaign?') }}",
                relaunchCampText: "{{ __('The previous statistics and reports of this campaign will be erased.') }}",
                resendTrainAssignRemText: "{{ __('This will send a training reminder to:') }}",
                compAssignTrainText: "{{ __('This will mark the training as completed') }}",
                deleteBtnText: "{{ __('Delete') }}",
                cancelBtnText: "{{ __('Cancel') }}",
                relaunchBtnText: "{{ __('Re-Launch') }}",
                resendTrainAssignBtnText: "{{ __('Yes, send the reminder email') }}",
                compAssignTainBtnText: "{{ __('Yes, mark as completed') }}",
                removeAssignTrainText: "{{ __('Yes, remove assignment') }}",
                selPhishMat: "{{ __('Please select phishing material') }}",
                daysUntiDue: "{{ __('Please enter days until due') }}",
                daysUntilGreater: "{{ __('Days until due must be greater than 0') }}",
                selTrainMod: "{{ __('Please select training module') }}",
                fillAllReq: "{{ __('Please fill all required fields!') }}",
                attackSel : "{{ __('Attack selected') }}",
                OK: "{{ __('OK') }}",
                trainingSel: "{{ __('Training selected') }}",
                campDel : "{{ __('Campaign deleted successfully') }}"
            };
             const CLOUDFRONT_URL = "{{ env('CLOUDFRONT_URL') }}";
        </script>
        {{-- All Alert's lang conversion of js file --}}

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

            let campaignActivities = [];

            function showPhishingReportIndividual(res) {
                if (res.camp_live.length > 0) {
                    let mailPending = '<span class="badge bg-warning-transparent">Pending</span>';
                    let mailSent = '<span class="badge bg-success-transparent">Success</span>';
                    let yesBatch = '<span class="badge bg-success-transparent">Yes</span>';
                    let noBatch = '<span class="badge bg-danger-transparent">No</span>';
                    campaignActivities = res.campaign_activity;
                    let rowHtml = '';
                    res.camp_live.forEach((camp) => {
                        let isDelivered = camp.sent === "0" ? mailPending : mailSent;
                        let isViewed = camp.mail_open === 0 ? noBatch : yesBatch;
                        let isPayLoadClicked = camp.payload_clicked === 0 ? noBatch : yesBatch;
                        let isEmpCompromised = camp.emp_compromised === 0 ? noBatch : yesBatch;
                        let isEmailReported = camp.email_reported === 0 ? noBatch : yesBatch;

                        rowHtml += `
                            <tr data-bs-toggle="collapse" onclick="fetchActivity(this, ${camp.id}, '${camp.user_email}')" role="button" data-bs-target="#collapseClient${camp.id}">
                                <td>${camp.user_name}</td>
                                <td>${camp.user_email}</td>
                                <td>${isDelivered}</td>
                                <td>${isViewed}</td>
                                <td>${isPayLoadClicked}</td>
                                <td>${isEmpCompromised}</td>
                                <td>${isEmailReported}</td>
                            </tr>
                            <tr >
                                <td colspan="7" class="hiddenRow py-0 px-3">
                                    <div class="accordian-body collapse" id="collapseClient${camp.id}"> 
                                        
                                    </div> 
                                </td>
                            </tr>
                        `;
                    });

                    $("#campReportsIndividual").html(rowHtml);
                    document.querySelectorAll("#campReportsIndividual pre code").forEach((block) => {
                        Prism.highlightElement(block);
                    });
                }
            }

            function fetchActivity(row, camp_live_id, emp_email) {
                const expanded = $(row).attr('aria-expanded');
                if (expanded === 'false') {
                    console.log('not expanded');
                    return;
                }

                if (campaignActivities.length > 0) {
                    const activity = campaignActivities.find(activity => activity.campaign_live_id === camp_live_id);

                    if (activity) {
                        let sentActivity = '';
                        let viewedActivity = '';
                        let clickedActivity = '';
                        let compromisedActivity = '';
                        if (activity.email_sent_at !== null) {
                            sentActivity = `<li class="crm-recent-activity-content">
                                                <div class="d-flex align-items-top">
                                                                <div class="me-3">
                                                                    <span class="avatar avatar-xs bg-secondary-transparent avatar-rounded">
                                                                        <i class="bi bi-circle-fill fs-8"></i>
                                                                    </span>
                                                                </div>
                                                                <div class="crm-timeline-content">
                                                                    <span class="fw-semibold">Email Delivered <i class='bx bx-check-circle fs-18 text-success ms-1'></i></span>
                                                                    <span class="d-block fs-12 text-muted">The campaign email was sent to <span class="badge bg-primary-transparent">${emp_email}</span></span>
                                                                </div>
                                                                <div class="flex-fill text-end">
                                                                    <span class="d-block text-muted fs-11 op-7">${activity.email_sent_at}</span>
                                                                </div>
                                                            </div>
                                                        </li>`;
                        }

                        if (activity.email_viewed_at !== null) {
                            viewedActivity = `<li class="crm-recent-activity-content">
                                                            <div class="d-flex align-items-top">
                                                                <div class="me-3">
                                                                    <span class="avatar avatar-xs bg-secondary-transparent avatar-rounded">
                                                                        <i class="bi bi-circle-fill fs-8"></i>
                                                                    </span>
                                                                </div>
                                                                <div class="crm-timeline-content">
                                                                    <span class="fw-semibold">Email Viewed <i class='bx bx-check-circle fs-18 text-success ms-1'></i></span>
                                                                    <span class="d-block fs-12 text-muted">The campaign email has successfully opened by <span class="badge bg-primary-transparent">${emp_email}</span></span>
                                                                </div>
                                                                <div class="flex-fill text-end">
                                                                    <span class="d-block text-muted fs-11 op-7">${activity.email_viewed_at}</span>
                                                                </div>
                                                            </div>
                                                        </li>`;
                        }
                        if (activity.payload_clicked_at !== null) {
                            clickedActivity = `<li class="crm-recent-activity-content">
                                                            <div class="d-flex align-items-top">
                                                                <div class="me-3">
                                                                    <span class="avatar avatar-xs bg-secondary-transparent avatar-rounded">
                                                                        <i class="bi bi-circle-fill fs-8"></i>
                                                                    </span>
                                                                </div>
                                                                <div class="crm-timeline-content">
                                                                    <span class="fw-semibold">Payload Clicked <i class='bx bx-check-circle fs-18 text-success ms-1'></i></span>
                                                                    <span class="d-block fs-12 text-muted">The Payload has clicked by <span class="badge bg-primary-transparent">${emp_email}</span> and redirected to the phishing website</span>
                                                                </div>
                                                                <div class="flex-fill text-end">
                                                                    <span class="d-block text-muted fs-11 op-7">${activity.payload_clicked_at}</span>
                                                                </div>
                                                            </div>
                                                        </li>`;
                        }
                        if (activity.compromised_at !== null && activity.client_details !== null) {
                            let client_details_table = generateClientDetailsTable(activity.client_details);
                            compromisedActivity = `<li class="crm-recent-activity-content">
                                                            <div class="d-flex align-items-top">
                                                                <div class="me-3">
                                                                    <span class="avatar avatar-xs bg-secondary-transparent avatar-rounded">
                                                                        <i class="bi bi-circle-fill fs-8"></i>
                                                                    </span>
                                                                </div>
                                                                <div class="crm-timeline-content">
                                                                    <span class="fw-semibold">Employee Compromised <i class='bx bx-check-circle fs-18 text-success ms-1'></i></span>
                                                                    <span class="d-block fs-12 text-muted"><span class="badge bg-primary-transparent">${emp_email}</span> has tried to enter the confidential details</span>
                                                                </div>
                                                                <div class="flex-fill text-end">
                                                                    <span class="d-block text-muted fs-11 op-7">${activity.compromised_at}</span>
                                                                </div>
                                                            </div>
                                                            <div class="mt-4">
                                                                ${client_details_table}
                                                            </div>
                                                        </li>`;
                        }
                        let activityHtml = `<div class="py-4">
                                                    <ul class="list-unstyled mb-0 crm-recent-activity">
                                                        ${sentActivity}
                                                        ${viewedActivity}
                                                        ${clickedActivity}
                                                        ${compromisedActivity}
                                                    </ul>
                                                </div>`;

                        $(row).next().find('.accordian-body').html(activityHtml);
                        // Prism.highlightAll();
                    }
                }
            }

            function generateClientDetailsTable(clientDetails) {
                let table = '<table class="table table-bordered table-striped">';
                let tableHeader = '<thead><tr><th>Field</th><th>Value</th></tr></thead>';
                let tableBody = '<tbody>';
                let clientDetailsObj = JSON.parse(clientDetails);
                for (const [key, value] of Object.entries(clientDetailsObj)) {
                    tableBody += `<tr><td>${key}</td><td>${value}</td></tr>`;
                }
                tableBody += '</tbody>';
                table += tableBody + '</table>';
                return table;
            }

            function showPhishingReport(res) {
                let greenCheck = '<i class="bx bx-check-circle text-success fs-25"></i>';
                let redCheck = '<i class="bx bx-check-circle text-danger fs-25"></i>';

                let status = '';
                if (res.status === 'completed') {
                    status = '<span class="badge bg-success">{{ __('Completed') }}</span>';
                } else if (res.status === 'pending') {
                    status = '<span class="badge bg-warning">{{ __('Pending') }}</span>';
                } else {
                    status = '<span class="badge bg-success">{{ __('Running') }}</span>';
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
                    status = '<span class="badge bg-success">{{ __('Completed') }}</span>';
                } else if (res.status === 'pending') {
                    status = '<span class="badge bg-warning">{{ __('Pending') }}</span>';
                } else {
                    status = '<span class="badge bg-success">{{ __('Running') }}</span>';
                }

                let rowHtml = `
                                <tr>
                                    <th scope="row">${res.campaign_name}</th>
                                    <td>${status}</td>
                                    <td>${res.camp_live.length}</td>
                                    <td>${res.camp_report.training_assigned}</td>
                                    <td>${res.training_type == 'static_training' ? 
                                        '<span class="badge bg-info">{{ __('Static Training') }}</span>' : 
                                        '<span class="badge bg-info">{{ __('AI Training') }}</span>'}</td>
                                    <td>${res.camp_report.training_lang}</td>
                                    <td>${res.camp_report.training_completed}</td>
                                </tr>
                            `;

                $("#trainingReportStatus").html(rowHtml);

                showTrainingReportIndividual(res);
            }

            function showGameProgressReport(response) {
                $.post({
                    url: '/campaigns/fetch-game-detail',
                    data: {
                        campaignId: response.campaign_id
                    },
                    success: function(res) {
                        if (res.status === 0) {
                            $("#gameReportStatus").html(
                                '<tr><td colspan="5" class="text-center">{{ __('No data found') }}</td></tr>');
                            return;
                        }
                        console.log(res);
                        // return;
                        let campaign = `
                        <tr>
                            <td>
                                ${res.campaign_detail.campaign_name}
                            </td>    
                            <td>
                                <span class="badge bg-secondary">${res.target_employees.length}</span>
                                
                            </td>    
                            <td>
                                <span class="badge bg-secondary">${res.campaign_detail.total_assigned}</span>
                            </td>    
                            <td>
                                <span class="badge bg-secondary">${res.campaign_detail.game_completed}</span>
                            </td>    
                        </tr>
                        `;
                        $("#gameReportStatus").html(campaign);

                        if (res.target_employees.length > 0) {
                            let targetEmployees = '';
                            res.target_employees.forEach((employee, index) => {
                                targetEmployees += `
                                <tr>
                                    <td>${employee.user_name}</td>
                                    <td>${employee.user_email}</td>
                                    <td>
                                        <span class="badge bg-primary">${employee.training_game?.name}</span>
                                    </td>
                                    <td>${employee.assigned_date}</td>
                                    <td>${employee.personal_best}%</td>
                                    <td>${Math.floor(employee.game_time / 60).toString().padStart(2, '0')}:${(employee.game_time % 60).toString().padStart(2, '0')} mins</td>
                                </tr>
                                `;
                            });
                            $("#gameReportsIndividual").html(targetEmployees);
                        } else {
                            $("#gameReportsIndividual").html(
                                '<tr><td colspan="6" class="text-center">{{ __('No data found') }}</td></tr>');
                        }
                    }
                })
            }

            function fetchCampaignDetails(campid) {
                // console.log(campid)
                $("#training_tab").hide();
                $("#training_tab a").removeClass('active');
                $("#training_campaign").removeClass("active show");

                $("#phishing_tab").hide();
                $("#phishing_tab a").removeClass('active');
                $("#phishing_campaign").removeClass("active show");

                $("#game_tab").hide();
                $("#game_tab a").removeClass('active');
                $("#game_training").removeClass("active show");

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
                            if (response.training_type == 'games') {
                                $("#game_tab").show();
                                $("#game_tab a").addClass('active');
                                $("#game_training").addClass("active show");
                                showGameProgressReport(response);
                            } else {
                                $("#training_tab").show();
                                $("#training_tab a").addClass('active');
                                $("#training_campaign").addClass("active show");

                                showTrainingReport(response);
                            }

                        }
                        if (response.campaign_type === "Phishing & Training") {

                            $("#phishing_tab").show();
                            $("#phishing_tab a").addClass('active');
                            $("#phishing_campaign").addClass("active show");
                            showPhishingReport(response);

                            if (response.training_type == 'games') {
                                $("#game_tab").show();
                                showGameProgressReport(response);
                            } else {
                                $("#training_tab").show();
                                showTrainingReport(response);
                            }

                        }

                        return;

                    }
                });






            }

            $('#datatable-basic').DataTable({
                language: {
                    lengthMenu: "{{ __('Show') }} _MENU_ {{ __('entries') }}",
                    info: "{{ __('Showing') }} _START_ {{ __('to') }} _END_ {{ __('of') }} _TOTAL_ {{ __('entries') }}",
                    infoEmpty: "{{ __('Showing 0 to 0 of 0 entries') }}",
                    infoFiltered: "({{ __('filtered from') }} _MAX_ {{ __('total entries') }})",
                    searchPlaceholder: "{{ __('Search...') }}",
                    sSearch: '',
                    paginate: {
                        next: "{{ __('Next') }}",
                        previous: "{{ __('Previous') }}"
                    },
                },
                "pageLength": 10,
                // scrollX: true
            });

            $('#viewMaterialModal').on('hidden.bs.modal', function() {
                // Your function here
                $("#newCampModal").show();
            });
        </script>

        <script>
            function parseJson() {
                document.querySelectorAll(".jsonViewer").forEach(container => {
                    const jsonData = container.getAttribute("data-json");

                    try {
                        const parsedJson = JSON.parse(jsonData);
                        container.innerHTML =
                            `<pre><code class="language-json">${JSON.stringify(parsedJson, null, 4)}</code></pre>`;
                    } catch (error) {
                        console.error("Invalid JSON:", error);
                        container.innerHTML = "<p style='color: red;'>Invalid JSON</p>";
                    }
                });
            }
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
