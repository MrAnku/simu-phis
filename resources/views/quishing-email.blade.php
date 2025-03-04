@extends('layouts.app')

@section('title', 'Quishing Emails - Phishing awareness training program')

@section('main-content')

    <div class="main-content app-content">
        <div class="container-fluid mt-4">

            <div class="d-flex justify-content-between">
                <div>
                    <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal"
                        data-bs-target="#newPhishingmailModal">New Quishing Template</button>

                    {{-- <button class="btn btn-secondary label-btn mb-3 mx-2" data-bs-toggle="modal"
                        data-bs-target="#generatePhishMailModal">
                        <i class="ri-magic-line label-btn-icon me-2"></i>
                        Generate With AI
                    </button> --}}
                </div>

                {{-- <div class="row">
                    <div class="col-auto">
                        <label for="" class="col-form-label">Filter</label>
                    </div>
                    <div class="col-auto">
                        <select class="form-select" aria-label="Default select example" id="filterDiff">
                            <option value="" selected>Difficulty</option>
                            <option value="easy">Easy</option>
                            <option value="medium">Medium</option>
                            <option value="hard">Hard</option>
                        </select>
                    </div>

                    <div class="col-auto">
                        <a href="#" id="clearFilter" style="display: none;">Clear Filter</a>
                    </div>

                </div> --}}

            </div>


            <div class="row">
                <div class="col-xl-12">
                    <div class="card custom-card">
                        <div class="card-header d-flex justify-content-between">
                            <div class="card-title">
                                Manage Quishing Emails
                            </div>
                            <div>
                                <div class="input-group mb-3">

                                    <form method="GET" action="{{ route('quishing.emails') }}" class="d-flex gap-2">
                                        <input type="text" class="form-control" name="search"
                                            placeholder="Search Template..." aria-label="Example text with button addon"
                                            aria-describedby="button-addon1" value="{{ request('search') }}">
                                        <button class="btn btn-icon btn-primary-transparent rounded-pill btn-wave"
                                            type="submit">
                                            <i class="ri-search-line"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>


                        </div>
                        <div class="card-body all-email-templates">

                            <div class="row">
                                @forelse ($quishingEmails as $pemail)
                                    <div class="col-lg-6 email_templates" data-diff="{{ $pemail->difficulty ?? '' }}">
                                        <div class="card custom-card border">
                                            <div class="card-header">
                                                <div class="d-flex align-items-center w-100">

                                                    <div class="">
                                                        <div class="fs-15 fw-semibold">{{ $pemail->name }}</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card-body htmlPhishingGrid" id="qmailBody{{ $pemail->id }}">

                                                @if ($pemail->difficulty == 'easy')
                                                    <span class="badge bg-outline-success difficulty">Easy</span>
                                                @elseif ($pemail->difficulty == 'medium')
                                                    <span class="badge bg-outline-warning difficulty">Medium</span>
                                                @elseif ($pemail->difficulty == 'hard')
                                                    <span class="badge bg-outline-danger difficulty">Hard</span>
                                                @else
                                                    <span class="badge bg-outline-secondary difficulty">Unknown</span>
                                                @endif


                                                <iframe class="phishing-iframe" src="{{ Storage::url($pemail->file) }}"
                                                    style="
                                                    width: 100%;
                                                    height: 300px;
                                                "></iframe>
                                            </div>
                                            <div class="card-footer">
                                                <div class="d-flex justify-content-center">
                                                    <button type="button"
                                                        onclick="viewInTemplate(
                                                        `{{ $pemail->senderProfile->profile_name ?? 'N/A' }}`,
                                                        `{{ $pemail->senderProfile->from_email ?? 'N/A' }}`,
                                                        `{{ $pemail->email_subject }}`,`qmailBody{{ $pemail->id }}`
                                                        )"
                                                        data-bs-toggle="modal" data-bs-target="#viewPhishingmailModal"
                                                        class="btn mx-1 btn-outline-primary btn-wave waves-effect waves-light">View</button>

                                                    @if ($pemail->company_id !== 'default')
                                                        <button type="button"
                                                            onclick="editETemplate(`{{ base64_encode($pemail->id) }}`, `{{ $pemail->website }}`, `{{ $pemail->difficulty }}`, `{{ $pemail->sender_profile }}`)"
                                                            data-bs-toggle="modal" data-bs-target="#editEtemplateModal"
                                                            class="btn mx-1 btn-outline-primary btn-wave waves-effect waves-light">Edit</button>

                                                        <button type="button"
                                                            onclick="deleteETemplate(`{{ base64_encode($pemail->id) }}`)"
                                                            class="btn mx-1 btn-outline-danger btn-wave waves-effect waves-light">Delete</button>
                                                    @endif



                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-lg-12">
                                        <p class="text-muted text-center">No records found</p>
                                    </div>
                                @endforelse




                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                {{ $quishingEmails->links() }}
            </div>

        </div>
    </div>

    {{-- -------------------Modals------------------------ --}}

    {{-- view mailbody modal  --}}
    <x-modal id="viewPhishingmailModal" size="modal-xl" heading="Email Preview">
        <x-quish-email.view-template />
    </x-modal>


    <!-- new phishing email template modal -->
    <x-modal id="newPhishingmailModal" heading="Add Quishing Template">
        <x-quish-email.new-temp-form :senderProfiles="$senderProfiles" :phishingWebsites="$phishingWebsites" />
    </x-modal>



    <!-- edit phishing email template modal -->
    <x-modal id="editEtemplateModal" heading="Edit Quishing Template">
        <x-quish-email.edit-template :senderProfiles="$senderProfiles" :phishingWebsites="$phishingWebsites" />
    </x-modal>

    {{-- generate phishing email with ai modal --}}
    <x-modal id="generatePhishMailModal" size="modal-lg" heading="Generate Email Template">
        hello
    </x-modal>

    {{-- save generate phishing email template modal --}}
    {{-- <x-modal id="savePhishMailModal" heading="Save Generated Email Template">
        hello
    </x-modal> --}}




    {{-- ------------------------------Toasts---------------------- --}}

    <x-toast />



    @push('newcss')
        <style>
            .difficulty {
                position: absolute;
                right: 20px;
                top: 20px;
            }
        </style>
    @endpush

    @push('newscripts')
        <script>
            function viewInTemplate(from_name, from_email, email_subject, mail_body) {
                $("#displayMailBodyContent").html($(`#${mail_body}`).html());
                $("#displayMailSubject").html(email_subject);
                $("#displayFromName").html(from_name);
                $("#displayFromEmail").html(from_email);
            }

            function deleteETemplate(id) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "If this template is used in any campaign, it will be removed from the campaign as well.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post({
                            url: '/quishing-emails/delete-temp',
                            data: {
                                id: id,
                            },
                            success: function(res) {
                                if (res.success) {
                                    Swal.fire(
                                        'Deleted!',
                                        'Template has been deleted.',
                                        'success'
                                    )
                                    setTimeout(() => {
                                        location.reload();
                                    }, 1000);
                                } else {
                                    Swal.fire(
                                        'Error!',
                                        res.error,
                                        'error'
                                    )
                                }
                            }
                        })
                    }
                });
            }

            function editETemplate(id, website, diff, sp) {

                $("#editEtemp").val(id);
                $("#updateEAssoWebsite").val(website);
                $("#difficulty").val(diff);
                $("#updateESenderProfile").val(sp);
            }
        </script>
    @endpush

@endsection
