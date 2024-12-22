@extends('admin.layouts.app')

@section('title', 'All Logs | simUphish')

@section('main-content')

    <div class="main-content app-content">
        <div class="container-fluid ">



            <div class="row">
                <div class="col-xl-12">
                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">
                                All Logs
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table text-nowrap" id="file-export">
                                    <thead>
                                        <tr>
                                            <th scope="col">Role</th>
                                            <th scope="col" class="break-words">Role ID</th>
                                            <th scope="col" class="details-column break-words">Details</th>
                                            <th scope="col">IP Address</th>
                                            <th scope="col" class="user-agent-column break-words">User Agent</th>
                                            <th scope="col">Log date</th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                        @forelse ($allLogs as $log)
                                            <tr>
                                                <th scope="row"><small>{{ $log->role }}</small></th>
                                                <td><small>{{ $log->role_id }}</small></td>
                                                <td class="w-50"><small>{{ $log->msg }}</small></td>
                                                <td><span class="badge bg-primary-transparent">{{ $log->ip_address }}</span>
                                                </td>
                                                <td class="text-break mb-0"><small>{{ $log->user_agent }}</small></td>
                                                <td><small>{{ $log->created_at }}</small></td>
                                            </tr>
                                        @empty
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




    {{-- -------------------Modals------------------------ --}}



    {{-- ------------------------------Toasts---------------------- --}}

    <x-toast />

    {{-- ------------------------------Toasts---------------------- --}}

    @push('newcss')
        <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css">
        <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.3.0/css/responsive.bootstrap.min.css">
        <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.bootstrap5.min.css">

        <style>
            table {
                width: 100%;
                /* Ensure the table takes the full width */
                table-layout: fixed;
                /* Enables fixed column widths */
            }

            table th,
            table td {
                word-wrap: break-word;
                overflow-wrap: break-word;
                /* Modern alternative for word-wrap */
                white-space: normal;
                /* Allow text to wrap onto the next line */
            }

            table th.details-column,
            table td.details-column {
                width: 40%;
                /* Increase the width of the Details column */
            }

            table th.user-agent-column,
            table td.user-agent-column {
                width: 20%;
                /* Decrease the width of the User Agent column */
            }

            .break-words {
                word-wrap: break-word;
                overflow-wrap: break-word;
                white-space: normal;
            }
        </style>
    @endpush

    @push('newscripts')
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
            $('#logtable').DataTable({
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
