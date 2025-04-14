@extends('layouts.app')

@section('title', 'All Employees - Phishing awareness training program')

@section('main-content')

    <div class="main-content app-content">
        <div class="container-fluid mt-4">

            <div class="row my-3">
                <div class="col-xxl-4 col-xl-12">
                    <div class="card custom-card hrm-main-card primary">
                        <div class="card-body">
                            <div class="d-flex align-items-top">

                                <div class="flex-fill">
                                    <span
                                        class="fw-semibold text-muted d-block mb-2">{{ __('Across all employee groups (Total)') }}</span>
                                    <h5 class="fw-semibold mb-2">{{ $totalEmps }} {{ __('Employees') }}</h5>

                                </div>
                                <div class="me-3">
                                    <span class="avatar bg-primary">
                                        <i class="ri-team-line fs-18"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-4 col-xl-12">
                    <div class="card custom-card hrm-main-card secondary">
                        <div class="card-body">
                            <div class="d-flex align-items-top">

                                <div class="flex-fill">
                                    <span class="fw-semibold text-muted d-block mb-2">{{ $verifiedDomains->count() }}
                                        {{ __('Used (500 Domains Max.)') }}</span>
                                    <h5 class="fw-semibold mb-2">{{ $verifiedDomains->count() }}
                                        {{ __('Domains Verified') }}</h5>

                                </div>
                                <div class="me-3">
                                    <span class="avatar bg-success">
                                        <i class="bx bx-check-shield fs-18"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-4 col-xl-12">
                    <div class="card custom-card hrm-main-card warning">
                        <div class="card-body">
                            <div class="d-flex align-items-top">

                                <div class="flex-fill">
                                    <span
                                        class="fw-semibold text-muted d-block mb-2">{{ __('Verification of ownership') }}</span>
                                    <h5 class="fw-semibold mb-2">{{ $notVerifiedDomains->count() }}
                                        {{ __('Domains Pending') }}</h5>

                                </div>
                                <div class="me-3">
                                    <span class="avatar bg-warning">
                                        <i class="bx bxs-key fs-18"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <div>
                    <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal"
                        data-bs-target="#addUserModalForm">{{ __('Add Employee') }}</button>
                </div>
                {{-- <div>
                    <button type="button" class="btn btn-secondary mb-3" data-bs-toggle="modal"
                        data-bs-target="#domainVerificationModal">Domain Verification</button>
                    <button type="button" class="btn btn-dark mb-3" onclick="checkHasConfig()" data-bs-toggle="modal"
                        data-bs-target="#syncDirectoryModal">Directory Sync</button>
                </div> --}}
            </div>

            <div class="row">
                <div class="col-xl-12">
                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">
                                {{ __('Manage Employees') }}
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="allGroupsTable" class="table table-bordered text-nowrap w-100">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Sl') }}</th>
                                            <th> {{ __('Name') }}</th>
                                            <th>{{ __('Email') }}</th>
                                            <th>{{ __('Company') }}</th>
                                            <th>{{ __('Job Title') }}</th>
                                            <th>{{ __('Actions') }}</th>

                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($allEmployees as $emp)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td> <!-- Serial number -->

                                                <td>
                                                    <a target="blank"
                                                        href="{{ route('employee.detail', base64_encode($emp->id)) }}"
                                                        class="text-primary">
                                                        {{ $emp->user_name }}
                                                    </a>
                                                </td>

                                                <td>{{ $emp->user_email }}</td>
                                                <td>{{ $emp->user_company }}</td>
                                                <td>{{ $emp->user_job_title }}</td>
                                                <td>
                                                    <span class="text-danger ms-1"
                                                        onclick="deletePlanUser('{{ base64_encode($emp->user_email) }}')"
                                                        role="button">
                                                        <i class="bx bx-trash fs-4"></i>
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td class="text-center" colspan="6">{{ __('No records found') }}</td>
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

    <!-- new Employee group modal -->
    <x-modal id="newEmpGroupModal" heading="{{ __('Add Employee Group') }}">
        <x-employees.new-group-form />
    </x-modal>


    <!-- verified domains modal -->
    <x-modal id="domainVerificationModal" heading="{{ __('Domain Verification') }}">
        <x-employees.domain-verification :allDomains="$allDomains" />
    </x-modal>



    <!-- new domain verification modal -->
    <x-modal id="newDomainVerificationModal" heading="{{ __('Verify Domain') }}">
        <x-employees.new-domain-verify />
    </x-modal>


    <!-- view employees modal -->

    <x-modal id="viewUsers" size="modal-xl" heading="{{ __('All Employees') }}">
        <x-employees.view-users />
    </x-modal>


    <!-- add employees modal -->
    <x-modal id="addUserModalForm" size="modal-xl" heading="{{ __('Add Employee') }}">
        <x-employees.add-plan-user />
    </x-modal>



    {{-- Directory sync Modal --}}

    <x-modal id="syncDirectoryModal" size="modal-lg" heading="{{ __('Provider') }}">
        <x-employees.sync-directory :hasOutlookToken="$hasOutlookAdToken" />
    </x-modal>



    {{-- ------------------------------Toasts---------------------- --}}

    <x-toast />



    @push('newcss')
        <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css">
        <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.3.0/css/responsive.bootstrap.min.css">
        <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.bootstrap5.min.css">
    @endpush

    @push('newscripts')
        {{-- All Alert's lang conversion of js file --}}
        <script>
            const alertMsgs = {
                title: "{{ __('Are you sure?') }}",
                deleteGroupText: "{{ __('If this group is assigned with any live campaign then the campaign will be deleted. Are you sure ?') }}",
                deleteUserText: "{{ __('This user will be deleted from Live campaign or scheduled campaign. And if this user has assigned any training then the learning account will be deleted.') }}",
                deleteDomainText: "{{ __('All employees will be deleted from Group whose email associated with this domain.') }}",
                deleteBtnText: "{{ __('Delete') }}",
                cancelBtnText: "{{ __('Cancel') }}",
                noUsersSel: "{{ __('No users selected!') }}",
                deletedUserText: "{{ __('Employee has been deleted successfully.') }}",
                deletedTitle: "{{ __('Deleted!') }}",
                somethingWrong: "{{ __('Something went wrong!') }}",
                error: "{{ __('Error') }}",
                noEmp: "{{ __('No employees available in this group!') }}",
                search: "{{ __('Search...') }}",
                OK: "{{ __('OK') }}"
            };
        </script>
        {{-- All Alert's lang conversion of js file --}}

        <script src="/js/employees.js"></script>

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
            $('#allGroupsTable').DataTable({
                language: {
                    searchPlaceholder: 'Search...',
                    sSearch: '',
                },
                "pageLength": 10,
                // scrollX: true
            });

            $('#domainVerificationTable').DataTable({
                language: {
                    searchPlaceholder: 'Search...',
                    sSearch: '',
                },
                "pageLength": 10,
                // scrollX: true
            });
        </script>

        <script>
            function checkHasConfig() {

                $.get('/employees/check-ldap-ad-config', function(res) {

                    console.log(res)

                    if (res.status === 1) {
                        $("#ldap_host").val(res.data.ldap_host).attr('disabled', true)
                        $("#ldap_dn").val(res.data.ldap_dn).attr('disabled', true)
                        $("#ldap_admin").val(res.data.admin_username).attr('disabled', true)
                        $("#ldap_pass").val(res.data.admin_password).attr('disabled', true)

                        $("#save_ldap_config").hide();
                        $("#edit_ldap_config").show();
                        $("#add_ldap_config").hide();
                    } else {
                        $("#save_ldap_config").hide();
                        $("#edit_ldap_config").hide();
                        $("#add_ldap_config").show();
                    }
                }).fail(function(error) {
                    console.log(error);
                });
            }

            $("#edit_ldap_config").on('click', function(e) {
                e.preventDefault();
                this.style.display = 'none';
                $("#save_ldap_config").show()

                $("#ldap_host").removeAttr('disabled');
                $("#ldap_dn").removeAttr('disabled');
                $("#ldap_admin").removeAttr('disabled');
                $("#ldap_pass").removeAttr('disabled');
            })

            $("#add_ldap_config").on('click', function(e) {
                e.preventDefault();
                this.innerText = 'Please Wait...';


                var host = $("#ldap_host").val();
                var dn = $("#ldap_dn").val();
                var user = $("#ldap_admin").val();
                var pass = $("#ldap_pass").val();

                $.post({
                    url: '/employees/add-ldap-config',
                    data: {
                        host,
                        dn,
                        user,
                        pass
                    },
                    success: function(res) {

                        if (res.status == 1) {
                            alert(res.msg)
                            window.location.href = window.location.href;
                        } else {
                            console.log(res)
                            // alert(res.msg)
                            alert(res.msg)
                        }
                    }
                })



            })

            // $(".saveSyncUser").on('click', function(e){
            //     // e.preventDefault();
            //     var sibs = $(this).parent().siblings();
            //     console.log(sibs);
            // })
            function isValidEmail(email) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailRegex.test(email); // Returns true if valid, false otherwise
            }

            function saveSyncUser(btn) {
                var spinner =
                    '<div class="spinner-border spinner-border-sm me-4" role="status"><span class="visually-hidden">Loading...</span></div>';

                $(btn).html(spinner);

                var sibs = $(btn).parent().siblings(); // Get siblings of the parent element
                var values = {};

                var error = false;

                // Loop through each sibling element
                sibs.each(function(index) {
                    var input = $(this).find('input'); // Find the input inside the sibling
                    if (input.length > 0) {
                        var key = input.data('key');
                        var value = input.val();
                        if (key == 'usrWhatsapp') {
                            value = Number.isInteger(value) ? value : null;
                        }

                        if (key == 'usrEmail') {
                            if (!isValidEmail(value)) {
                                Swal.fire({
                                    title: "Please enter a valid email!",
                                    icon: 'error',
                                    confirmButtonText: "{{ __('OK') }}"
                                })

                                error = true;
                            }

                        }

                        values[key] = value; // Store the input value in the object with a dynamic key
                    }
                });

                if (error) {
                    $(btn).html("Save");
                    return;
                }

                var groupid = $(".groupid").first().val();

                var userdata = {
                    ...values,
                    groupid: groupid
                }

                console.log(userdata)

                $.post({
                    url: '/employees/addUser',
                    data: userdata,
                    success: function(res) {
                        if (res.status == 0) {
                            // alert(resJson.msg);
                            Swal.fire({
                                    title: res.msg,
                                    icon: 'error',
                                    confirmButtonText: "{{ __('OK') }}"
                                })

                            $(btn).html("Save");
                        } else {
                            // var params = new URLSearchParams(formData);
                            // var groupid = params.get('groupid');
                            viewUsersByGroup(groupid);

                            $(btn).html("Saved").removeClass("btn-primary").addClass("btn-success").attr('disabled',
                                true);
                        }

                    }
                })
            }

            $("#sync_ad_btn").on('click', function() {
                var btn = this;
                $(btn).html("Syncing...").attr('disabled', true);
                var provider = $("input[name='ad_provider']:checked").val();

                if (provider == 'ldap') {
                    $.get('/employees/sync-ldap-directory', function(res) {
                        console.log(res)

                        if (res.status === 1) {
                            btn.innerText = 'Sync Directory';
                            pushTableHead()
                            res.data.forEach(element => {
                                generateForm(element);
                            });
                            $("#syncRecords").removeClass('d-none');
                        } else {
                            btn.innerText = 'Sync Directory';
                            alert(res.message);
                        }
                    }).fail(function(error) {
                        console.log(error);
                    });
                } else if (provider == 'outlook') {
                    fetchOutlookGroups(btn);
                    $("#outlookContainer").show();
                } else {
                    Swal.fire({
                                    title: "{{ __('This provider is currently inactive in our system') }}",
                                    icon: 'error',
                                    confirmButtonText: "{{ __('OK') }}"
                                })
                    btn.innerText = "Sync Directory";
                }


            })

            function generateForm(element) {
                var form = `<form action="" method="post">
                                                <div class="table-responsive">
                                                    <table class="table table-primary">
    
                                                        <tbody>
                                                            <tr class="">
                                                                <td scope="row">
                                                                    <input type="text" data-key="usrName" class="form-control"
                                                                        value="${element.username}">
                                                                </td>
                                                                <td><input type="text" data-key="usrEmail" class="form-control"
                                                                        value="${element.email}"></td>
                                                                <td><input type="text" data-key="usrCompany" class="form-control"
                                                                        value="N/A"></td>
                                                                <td><input type="text" data-key="usrJobTitle" class="form-control"
                                                                        value="N/A"></td>
                                                                <td><input type="text" data-key="usrWhatsapp" class="form-control"
                                                                        value="N/A"></td>
                                                                
                                                                        <td>
                                                                            <button type="button" onclick="saveSyncUser(this)" class="btn btn-primary btn-sm btn-wave">Save</button>
                                                                        </td>
                                                            </tr>
                                                            
                                                        </tbody>
                                                    </table>
                                                </div>
    
                                            </form>`;
                $("#syncUserForms").append(form);
            }

            function pushTableHead() {
                var thead = `<table class="table table-primary">
    
                                                        <tbody>
                                                            <tr class="">
                                                                <td scope="row">
                                                                    <input type="text" class="form-control"
                                                                        value="Name" disabled>
                                                                </td>
                                                                <td><input type="text" class="form-control"
                                                                        value="Email" disabled></td>
                                                                <td><input type="text" class="form-control"
                                                                        value="Company" disabled></td>
                                                                <td><input type="text" class="form-control"
                                                                        value="Job Title" disabled></td>
                                                                <td><input type="text" class="form-control"
                                                                        value="WhatsApp" disabled></td>
                                                                
                                                                        <td>
                                                                            Action
                                                                        </td>
                                                            </tr>
                                                            
                                                        </tbody>
                                                    </table>`;
                $("#syncUserForms").html(thead);
            }
        </script>
        <script>
            function initChoices() {
                const multipleCancelButton = new Choices(
                    '#outlookGroups', {
                        // allowHTML: true,
                        removeItemButton: true,
                    }
                );
            }


            function fetchOutlookGroups(btn) {
                // $(btn "span").html("Please Wait...").attr('disabled', true);
                $.ajax({
                    url: "/fetch-outlook-groups",
                    type: "GET",
                    success: function(res) {
                        console.log(res);
                        if (res.status == 0) {
                            Swal.fire({
                                    title: res.msg,
                                    icon: 'error',
                                    confirmButtonText: "{{ __('OK') }}"
                                })
                            return;
                        }
                        let options = "";
                        $.each(res.groups, function(index, option) {
                            options += `
                            <option value="${option.id}">${option.displayName}</option>
                            `;
                        });
                        $("#outlookGroups").html(options);
                        // initChoices();
                        $("#outlookContainer").show();
                        $(btn).html("Sync Directory").attr('disabled', false);
                    }
                });

            }

            function fetchOutlookEmployees(btn) {
                $(btn).html("Syncing...").attr('disabled', true);
                const groupId = $("#outlookGroups").val();
                $.ajax({
                    url: "/fetch-outlook-emps/" + groupId,
                    type: "GET",
                    success: function(response) {
                        if (response.status == 0) {
                            Swal.fire({
                                    title: response.msg,
                                    icon: 'error',
                                    confirmButtonText: "{{ __('OK') }}"
                                } )
                            return;
                        }
                        console.log(response);
                        $("#outlookEmps tbody").html('');
                        let tableRows = "";
                        $.each(response.employees, function(index, employee) {
                            tableRows += `
                            <tr>
                                <td><input type="text" class="form-control name" value="${employee.displayName}"></td>
                                <td><input type="email" class="form-control email" value="${employee.mail ?? employee.userPrincipalName}"></td>
                                <td><input type="text" class="form-control company" value="${employee.company ?? ''}"></td>
                                <td><input type="text" class="form-control job_title" value="${employee.jobTitle ?? ''}"></td>
                                <td><input type="text" class="form-control whatsapp" value="${employee.whatsapp ?? ''}"></td>
                                <td>
                                    <span class="text-danger ms-1" onclick="deleteOutlookEmpRow(this)" role="button">
                                                <i class="bx bx-trash fs-4"></i>
                                            </span>
                                </td>
                            </tr>
                        `;
                        });
                        $("#outlookEmps tbody").html(tableRows);
                        $("#outlookEmps").show();
                        $(btn).html("Sync Employees").attr('disabled', false);
                    }
                });
            }

            function deleteOutlookEmpRow(btn) {
                $(btn).closest('tr').remove();
            }

            function saveOutlookSyncedEmployees(btn) {
                $(btn).html("Saving...").attr('disabled', true);
                const groupId = null;
                const employees = [];
                $("#outlookEmps tbody tr").each(function(index, tr) {
                    const name = $(tr).find('.name').val();
                    const email = $(tr).find('.email').val();
                    const company = $(tr).find('.company').val();
                    const jobTitle = $(tr).find('.job_title').val();
                    const whatsapp = $(tr).find('.whatsapp').val();
                    employees.push({
                        name,
                        email,
                        company,
                        jobTitle,
                        whatsapp
                    });
                });

                $.ajax({
                    url: "/save-outlook-employees",
                    type: "POST",
                    data: {
                        groupId,
                        employees
                    },
                    success: function(response) {
                        // console.log(response);
                        // return;
                        if (response.status == 0) {
                            if (Array.isArray(response.msg)) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Errors',
                                    html: response.msg.map(error => `<p>${error}</p>`).join(''),
                                    confirmButtonText: "{{ __('OK') }}"
                                });
                            } else {
                                Swal.fire({
                                    title: response.msg,
                                    icon: 'error',
                                    confirmButtonText: "{{ __('OK') }}"
                                });
                            }
                            $(btn).html("Save Employees").attr('disabled', false);
                            return;
                        }
                        Swal.fire({
                                    title: response.msg,
                                    icon: 'success',
                                    confirmButtonText: "{{ __('OK') }}"
                                })
                        $(btn).html("Save Employees").attr('disabled', false);
                    }
                });
            }
        </script>
    @endpush

@endsection
