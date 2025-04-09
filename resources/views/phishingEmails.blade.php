@extends('layouts.app')

@section('title', 'Phishing Emails - Phishing awareness training program')

@section('main-content')

    <div class="main-content app-content">
        <div class="container-fluid mt-4">

            <div class="d-flex justify-content-between">
                <div>
                    <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal"
                        data-bs-target="#newPhishingmailModal">{{ __('New Email Template') }}</button>

                    <button class="btn btn-secondary label-btn mb-3 mx-2" data-bs-toggle="modal"
                        data-bs-target="#generatePhishMailModal">
                        <i class="ri-magic-line label-btn-icon me-2"></i>
                        {{ __('Generate With AI') }}
                    </button>
                </div>

                <div class="row">
                    <div class="col-auto">
                        <label for="" class="col-form-label">{{ __('Filter') }}</label>
                    </div>
                    <div class="col-auto">
                        <select class="form-select" aria-label="Default select example" id="filterDiff">
                            <option value="" selected>{{ __('Difficulty') }}</option>
                            <option value="easy">{{ __('Easy') }}</option>
                            <option value="medium">{{ __('Medium') }}</option>
                            <option value="hard">{{ __('Hard') }}</option>
                        </select>
                    </div>

                    <div class="col-auto">
                        <a href="#" id="clearFilter" style="display: none;">{{ __('Clear Filter') }}</a>
                    </div>

                </div>

            </div>


            <div class="row">
                <div class="col-xl-12">
                    <div class="card custom-card">
                        <div class="card-header d-flex justify-content-between">
                            <div class="card-title">
                                {{ __('Manage Phishing Emails') }}
                            </div>
                            <div>
                                <div class="input-group mb-3">

                                    <form method="GET" action="{{ route('phishingEmails.search') }}" class="d-flex gap-2">
                                        <input type="text" class="form-control" name="search"
                                            placeholder="Search Template..." aria-label="Example text with button addon"
                                            aria-describedby="button-addon1">
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
                                @forelse ($phishingEmails as $pemail)
                                    <div class="col-lg-6 email_templates" data-diff="{{ $pemail->difficulty ?? '' }}">
                                        <div class="card custom-card">
                                            <div class="card-header">
                                                <div class="d-flex align-items-center w-100">

                                                    <div class="">
                                                        <div class="fs-15 fw-semibold">{{ $pemail->name }}</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card-body htmlPhishingGrid" id="mailBody{{ $pemail->id }}">

                                                @if ($pemail->difficulty == 'easy')
                                                    <span class="badge bg-outline-success">{{ __('Easy') }}</span>
                                                @elseif ($pemail->difficulty == 'medium')
                                                    <span class="badge bg-outline-warning">{{ __('Medium') }}</span>
                                                @elseif ($pemail->difficulty == 'hard')
                                                    <span class="badge bg-outline-danger">{{ __('Hard') }}</span>
                                                @else
                                                    <span class="badge bg-outline-secondary">{{ __('Unknown') }}</span>
                                                @endif


                                                <iframe class="phishing-iframe"
                                                    src="{{ Storage::url($pemail->mailBodyFilePath) }}"></iframe>
                                            </div>
                                            <div class="card-footer">
                                                <div class="d-flex justify-content-center">
                                                    <button type="button"
                                                        onclick="viewInTemplate(
                                                        `{{ $pemail->sender_p->profile_name ?? 'N/A' }}`,
                                                        `{{ $pemail->sender_p->from_email ?? 'N/A' }}`,
                                                        `{{ $pemail->email_subject }}`,`mailBody{{ $pemail->id }}`
                                                        )"
                                                        data-bs-toggle="modal" data-bs-target="#viewPhishingmailModal"
                                                        class="btn mx-1 btn-outline-primary btn-wave waves-effect waves-light">{{ __('View') }}</button>

                                                    @if ($pemail->company_id !== 'default')
                                                        <button type="button"
                                                            onclick="editETemplate(`{{ $pemail->id }}`)"
                                                            data-bs-toggle="modal" data-bs-target="#editEtemplateModal"
                                                            class="btn mx-1 btn-outline-primary btn-wave waves-effect waves-light">{{ __('Edit') }}</button>

                                                        <button type="button"
                                                            onclick="deleteETemplate(`{{ $pemail->id }}`, `{{ $pemail->mailBodyFilePath }}`)"
                                                            class="btn mx-1 btn-outline-danger btn-wave waves-effect waves-light">{{ __('Delete') }}</button>
                                                    @endif



                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-lg-6">
                                        {{ __('No records found') }}
                                    </div>
                                @endforelse




                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                {{ $phishingEmails->links() }}
            </div>

        </div>
    </div>

    {{-- -------------------Modals------------------------ --}}

    {{-- view mailbody modal  --}}
    <x-modal id="viewPhishingmailModal" size="modal-xl" heading="Email Preview">
        <x-phish-email.view-phish-mail-modal />
    </x-modal>


    <!-- new phishing email template modal -->
    <x-modal id="newPhishingmailModal" heading="Add Email Template">
        <x-phish-email.new-phish-mail-form :phishingWebsites="$phishingWebsites" :senderProfiles="$senderProfiles" />
    </x-modal>



    <!-- edit phishing email template modal -->
    <x-modal id="editEtemplateModal" heading="Edit Email Template">
        <x-phish-email.edit-phish-mail-form :phishingWebsites="$phishingWebsites" :senderProfiles="$senderProfiles" />
    </x-modal>

    {{-- generate phishing email with ai modal --}}
    <x-modal id="generatePhishMailModal" size="modal-lg" heading="Generate Email Template">
        <x-phish-email.generate-phish-mail-form />
    </x-modal>

    {{-- save generate phishing email template modal --}}
    <x-modal id="savePhishMailModal" heading="Save Generated Email Template">
        <x-phish-email.save-ai-phish-temp-form :phishingWebsites="$phishingWebsites" :senderProfiles="$senderProfiles" />
    </x-modal>




    {{-- ------------------------------Toasts---------------------- --}}

    <x-toast />



    @push('newcss')
        <style>
            .htmlPhishingGrid {
                overflow: scroll;
                scrollbar-width: none;
                border: 1px solid #8080804a;
                border-radius: 6px;
                max-height: 300px;
                /* filter: brightness(0.9); */

            }

            .phishing-iframe {
                width: 100%;
                height: 300px;
                margin: 0;
                padding: 0;
            }



            #displayMailBodyContent iframe,
            .htmlPhishingGrid iframe {
                width: 100%;
                height: 100vh;
                border: none;
            }

            .all-email-templates::-webkit-scrollbar {
                width: 12px;
                height: 12px;
            }

            .all-email-templates::-webkit-scrollbar-track {
                background: #ecf0f1 !important;
                border-radius: 10px;
            }

            .all-email-templates::-webkit-scrollbar-thumb {
                background: #3498db !important;
                border-radius: 10px;
                border: 2px solid transparent;
                /* Adds some space around the thumb */
                background-clip: content-box;
                /* Keeps the thumb within the space */
            }

            .all-email-templates {
                overflow-y: scroll;
            }
        </style>
    @endpush

    @push('newscripts')
        <script src="https://d3p8e1mvy30w84.cloudfront.net/assets/js/tinymce/tinymce.min.js"></script>
        <script>
            tinymce.init({
                selector: '#email-editor',
                license_key: 'gpl',
                promotion: false,
                branding: false,
                plugins: 'preview importcss searchreplace autolink directionality code visualblocks visualchars image link table charmap pagebreak nonbreaking insertdatetime advlist lists wordcount help charmap quickbars emoticons',
                editimage_cors_hosts: ['picsum.photos'],
                menubar: 'file edit view insert format tools table help',
                toolbar: "undo redo | code preview | link image | blocks fontfamily fontsize | bold italic underline strikethrough | align numlist bullist | table | lineheight outdent indent| forecolor backcolor removeformat | ltr rtl",

            });

            document.addEventListener('focusin', (e) => {
                if (e.target.closest(".tox-tinymce, .tox-tinymce-aux, .moxman-window, .tam-assetmanager-root") !==
                    null) {
                    e.stopImmediatePropagation();
                }
            });

            function shortcodeValidation() {
                const editorContent = tinymce.activeEditor.getContent();
                const hasWebsiteUrl = editorContent.includes('@{{website_url}}');
                const hasTrackerImg = editorContent.includes('@{{tracker_img}}');

                return hasWebsiteUrl && hasTrackerImg;
            }

            function showAnotherModal(button) {

                if (!shortcodeValidation()) {
                    alert('Please add all the required shortcodes');
                    return;
                }
                // Hide the current modal
                $('#generatePhishMailModal').modal('hide');

                // Show the another modal
                $('#savePhishMailModal').modal('show');

                // When the another modal is closed, show the current modal again
                $('#savePhishMailModal').on('hidden.bs.modal', function() {
                    $('#generatePhishMailModal').modal('show');
                });
            }



            async function generateTemplate(btn) {
                btn.disabled = true;
                btn.innerHTML = 'Generating...';
                const prompt = document.getElementById('prompt').value;
                const response = await fetch('/generate-template', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        prompt
                    })
                });

                const data = await response.json();
                console.log(data);
                if (data.status === 0) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: data.msg,
                    })
                    return;
                }
                if (data.html) {

                    tinymce.activeEditor.setContent(data.html);
                    btn.disabled = false;
                    btn.innerHTML = 'Generate Template';
                    $('#aiTempContainer').show();
                    $('#temp-suggestions').hide();
                } else {
                    alert('Failed to generate template');
                }
            }

            async function saveTemplate(btn) {
                btn.disabled = true;
                btn.innerHTML = 'Saving...';
                const content = tinymce.activeEditor.getContent();
                const formData = {};
                document.querySelectorAll('#savePhishMailModal form [name]').forEach(input => {
                    formData[input.name] = input.value;
                });
                const response = await fetch('/save-ai-phish-template', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        ...formData,
                        html: content
                    })
                });

                const data = await response.json();
                if (data.status === 1) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: data.msg,
                    });
                    window.location.reload();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: data.msg,
                    });
                }
            }

            $('#generatePhishMailModal').on('shown.bs.modal', function() {
                setTimeout(function() {
                    $('#temp-suggestions').slideDown('slow');
                }, 500);
            });

            $('#generatePhishMailModal').on('hidden.bs.modal', function() {
                $('#temp-suggestions').hide();
            });

            $('#temp-suggestions button').on('click', function() {
                const prompt = $(this).text().trim();
                document.getElementById('prompt').value = prompt;
            });

            $('#prompt').on('input', function() {
                if ($(this).val().trim() === '') {
                    $('#temp-suggestions').show();
                } else {
                    $('#temp-suggestions').hide();
                }
            });
        </script>

        <script>
            function viewInTemplate(from_name, from_email, email_subject, mail_body) {
                $("#displayMailBodyContent").html($(`#${mail_body}`).html());
                $("#displayMailSubject").html(email_subject);
                $("#displayFromName").html(from_name);
                $("#displayFromEmail").html(from_email);
            }

            function deleteETemplate(tempid, filelocation) {

                Swal.fire({
                    title: 'Are you sure?',
                    text: "Deleting this template will delete the campaigns associated with this email template.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e6533c',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Delete'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post({
                            url: "/delete-email-template",
                            data: {
                                tempid: tempid,
                                filelocation: filelocation
                            },
                            success: function(res) {
                                // console.log(res)
                                // window.location.reload();
                                window.location.href = window.location.href;
                            }
                        })
                    }
                })


            }

            function editETemplate(id) {
                editEtemp.value = id;
                $.post({
                    url: `/phishing-email`,
                    data: {
                        'id': id
                    },
                    success: function(res) {
                        if (res.status === 1) {
                            $("#updateEAssoWebsite").val(res.data.website)
                            $("#difficulty").val(res.data.difficulty)
                            $("#updateESenderProfile").val(res.data.senderProfile)
                        }

                        // console.log(JSON.parse(res));
                    }
                })
            }

            // Event listener for input field change
            $('#templateSearch').on('input', function() {
                var searchValue = $(this).val().toLowerCase(); // Get the search value and convert it to lowercase

                // Loop through each template card
                $('.email_templates').each(function() {
                    var templateName = $(this).find('.fw-semibold').text()
                        .toLowerCase(); // Get the template name and convert it to lowercase

                    // If the template name contains the search value, show the card; otherwise, hide it
                    if (templateName.includes(searchValue)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });

            $('#filterDiff').on('change', function() {
                var selectedDifficulty = $(this).val();
                $('#clearFilter').show();

                $('.email_templates').each(function() {
                    var templateDifficulty = $(this).data('diff');

                    if (selectedDifficulty === '' || templateDifficulty === selectedDifficulty) {
                        $(this).show(); // Show the template
                    } else {
                        $(this).hide(); // Hide the template
                    }
                });
            });

            $('#clearFilter').on('click', function() {
                $('#filterDiff').val(''); // Reset the dropdown to default value
                $('.email_templates').show(); // Show all templates

                $(this).hide();
            });
        </script>
    @endpush

@endsection
