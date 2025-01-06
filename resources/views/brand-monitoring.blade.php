@extends('layouts.app')

@section('title', 'Brand Monitoring - Phishing awareness training program')

@section('main-content')

    <div class="main-content app-content">
        <div class="container-fluid py-3">




            <div class="row">
                <div class="col-xl-5">
                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">Scan Domain</div>
                        </div>
                        <div class="card-body">
                            <div>
                                
                                <div id="searchbox">
                                    <input type="hidden" id="sid">
                                    <div class="row">
                                        <div class="col-lg-8">
                                            <input type="text" class="form-control" id="url" placeholder="Enter domain name" autofocus>
                                        </div>
                                        <div class="col-lg-4">
                                            <button id="scan" class="btn btn-primary w-100 btn-wave">Scan</button>
                                        </div>
                                    </div>
                                    
                                    
                                </div>
                                <div class="progress my-2" role="progressbar" aria-valuenow="10" aria-valuemin="0" aria-valuemax="100" style="display: none;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated"
                                    id="progress" style="width: 0%"></div>
                                </div>
                                {{-- <progress  value="0"></progress> --}}
                                <div>
                                    <small id="status"></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-7">
                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">Suspicious Domains Registered</div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">

                                <table id="data" class="table text-nowrap table-bordered"></table>
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
    @endpush

    @push('newscripts')

    <script>
        window.onload = function() {
            if (window.location.hash) {
                $('#sid').val(window.location.hash.substring(1));
                fetchDomains();
            }
        }
        
        last_registered = 0;
        
        function shareResults() {
            navigator.clipboard.writeText(window.location.href.split('#')[0] + '#' + sid);
            $('#status').html('Copied URL to clipboard!');
        }
        
        function fetchDomains() {
            $.getJSON('/scans/' + $('#sid').val() + '/domains', function(data) {
                $('#data').empty();
                $('<tr>').html(
                        '<th>PERMUTATION</th>' +
                        '<th>IP ADDRESS</th>' +
                        '<th>NAME SERVER</th>' +
                        '<th>MAIL SERVER</th>'
                    ).appendTo('#data');
                $.each(data, function(i, item) {
                    $.each(item, function(k, v) {
                        if (v instanceof Array) {
                            if (v[0] == '!ServFail') {
                                data[i][k][0] = '🚫'
                            }
                        }
                    })
                    fuzzer = item['fuzzer'] || ''
                    permutation = item['domain'] || ''
                    ipaddr = [
                        (item['dns_a'] || [''])[0],
                        (item['dns_aaaa'] || [''])[0]
                    ].filter(Boolean).join('</br>');
                    dns_ns = (item['dns_ns'] || [''])[0];
                    dns_mx = (item['dns_mx'] || [''])[0];
                    geoip = item['geoip'] || '';
                    $('<tr>').html(
                        '<td>' + permutation + ' <a href="http://' + permutation + '" id="link">🔗</a><div class="p-0"><small class="text-muted">' + fuzzer + '</small></div></td>' +
                        '<td>' + ipaddr + '<div class="p-0"><small class="text-muted">' + geoip + '</small></div></td>' +
                        '<td>' + dns_ns + '</td>' +
                        '<td>' + dns_mx + '</td>'
                    ).appendTo('#data');
                });
            });
        }
        
        function pollScan() {
            $.getJSON('/scans/' + $('#sid').val(), function(data) {
                $('#status').html('Processed ' + data['complete'] + ' of ' + data['total']);
                // $('#progress').val(data['complete']/data['total']);
                $('#progress').width(data['complete']/data['total'] * 100 + "%");
                if (data['remaining'] > 0) {
                    setTimeout(pollScan, 250);
                } else {
                    sid = $('#sid').val()
                    $('#status').html('Scanned <a href="/scans/' + sid + '/list">' + data['complete'] + '</a> permutations. Found ' + data['registered'] + ' registered: <a href="#" onclick="shareResults()">share it</a> or download as <a href="/scans/' + sid + '/csv">CSV</a> <a href="/scans/' + sid + '/json">JSON</a>');
                    $('#scan').text('Scan').removeClass('btn-danger').addClass('btn-primary');
                    $('#progress').parent().hide();
                }
                if (last_registered < data['registered']) {
                    last_registered = data['registered']
                    fetchDomains();
                }
            })
            .fail(function(){
                $('#status').html('Ups! Something went wrong...');
            });
        }
        
        function actionScan() {
            if (!$('#url').val()) {
                $('#status').html('↖ You need to type in a domain name first');
                return
            }
        
            if ($('#scan').text() == 'Scan') {
                last_registered = 0;
                $('#scan').text('Scanning...');
                $.post({
                    url: '/scans',
                    data: JSON.stringify({'url': $('#url').val()}),
                    contentType: 'application/json',
                    success: function(data) {
                        $('#sid').val(data['id']);
                        $('#url').val(data['domain']);
                        $('#scan').text('Stop').removeClass('btn-primary').addClass('btn-danger');
                        $('#progress').parent().show();
                        pollScan();
                    },
                    error: function(xhr, status, error) {
                        $('#scan').text('Scan').removeClass('btn-danger').addClass('btn-primary');
                        $('#status').html(xhr.responseJSON['message'] || 'Something went wrong');
                    },
                });
            } else {
                $('#scan').text('Stoping...');
                $.post({
                    url: '/scans/' + $('#sid').val() + '/stop',
                    contentType: 'application/json',
                })
                .always(function() {
                    $('#scan').text('Scan').removeClass('btn-danger').addClass('btn-primary');
                });
            }
        }
        
        $('#scan').click(function() {
            actionScan();
        });
        
        $('#url').on('keypress',function(e) {
            if(e.which == 13) {
                actionScan();
            }
        });
        </script>
    @endpush

@endsection
