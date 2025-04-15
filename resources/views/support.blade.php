@extends('layouts.app')

@section('title', __('Support Ticket') . ' - ' . __('Phishing awareness training program'))

@section('main-content')

    <div class="main-content app-content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between mt-3">
                <div>
                    <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal"
                        data-bs-target="#new_tkt_modal">{{ __('Create New Ticket') }}</button>
                </div>
            </div>
            <div class="main-chart-wrapper p-2 gap-2 d-lg-flex responsive-chat-open">
                <div class="chat-info border">

                    <div class="d-flex align-items-center justify-content-between w-100 p-3 border-bottom">
                        <div>
                            <h5 class="fw-semibold mb-0">{{ __('Support Ticket') }}</h5>
                        </div>

                    </div>
                    <ul class="nav nav-tabs tab-style-2 nav-justified mb-0 border-bottom d-flex" id="myTab1"
                        role="tablist">
                        <li class="nav-item border-end me-0" role="presentation">
                            <button class="nav-link active h-100" id="users-tab" data-bs-toggle="tab"
                                data-bs-target="#users-tab-pane" type="button" role="tab"
                                aria-controls="users-tab-pane" aria-selected="true"><i
                                    class="ri-history-line me-1 align-middle d-inline-block"></i>{{ __('Open') }}</button>
                        </li>
                        <li class="nav-item border-end me-0" role="presentation">
                            <button class="nav-link h-100" id="groups-tab" data-bs-toggle="tab"
                                data-bs-target="#groups-tab-pane" type="button" role="tab"
                                aria-controls="groups-tab-pane" aria-selected="false" tabindex="-1"><i
                                    class="ri-group-2-line me-1 align-middle d-inline-block"></i>{{ __('Closed') }}</button>
                        </li>
                    </ul>
                    <div class="tab-content" id="myTabContent">
                        <div class="tab-pane fade show active border-0 chat-users-tab" id="users-tab-pane" role="tabpanel"
                            aria-labelledby="users-tab" tabindex="0">
                            <ul class="list-unstyled mb-0 mt-2 chat-users-tab" id="chat-msg-scroll" data-simplebar="init">
                                <div class="simplebar-wrapper" style="margin: 0px;">
                                    <div class="simplebar-height-auto-observer-wrapper">
                                        <div class="simplebar-height-auto-observer"></div>
                                    </div>
                                    <div class="simplebar-mask">
                                        <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                                            <div class="simplebar-content-wrapper" tabindex="0" role="region"
                                                aria-label="scrollable content"
                                                style="height: auto; overflow: hidden scroll;">
                                                <div class="simplebar-content" style="padding: 0px;">
                                                    <li class="pb-0">
                                                        <p class="text-muted fs-11 fw-semibold mb-2 op-7">{{ __('Open Tickets') }}</p>
                                                    </li>

                                                    @forelse ($openTickets as $ticket)
                                                        <li class="checkforactive active"
                                                            onclick="loadConversation(`{{ $ticket->cp_tkt_no }}`)">
                                                            <a href="javascript:void(0);">
                                                                <div class="d-flex align-items-top">

                                                                    <div class="flex-fill">
                                                                        <p class="mb-0 fw-semibold">
                                                                            #{{ $ticket->cp_tkt_no }} {{ $ticket->subject }}
                                                                            <span
                                                                                class="float-end text-muted fw-normal fs-11">{{ $ticket->created_at }}</span>
                                                                        </p>
                                                                        <p class="fs-12 mb-0">
                                                                            <span
                                                                                class="chat-msg text-truncate">{{ $ticket->msg }}</span>

                                                                        </p>
                                                                    </div>
                                                                </div>
                                                            </a>
                                                        </li>
                                                    @empty
                                                        <li></li>
                                                    @endforelse

                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="simplebar-placeholder" style="width: auto; height: 696px;"></div>
                                </div>
                                <div class="simplebar-track simplebar-horizontal" style="visibility: hidden;">
                                    <div class="simplebar-scrollbar" style="width: 0px; display: none;"></div>
                                </div>
                                <div class="simplebar-track simplebar-vertical" style="visibility: visible;">
                                    <div class="simplebar-scrollbar"
                                        style="height: 25px; transform: translate3d(0px, 0px, 0px); display: block;"></div>
                                </div>
                            </ul>
                        </div>
                        <div class="tab-pane fade border-0 chat-groups-tab" id="groups-tab-pane" role="tabpanel"
                            aria-labelledby="groups-tab" tabindex="0">
                            <ul class="list-unstyled mb-0 mt-2 ">
                                <li class="pb-0">
                                    <p class="text-muted fs-11 fw-semibold mb-1 op-7">{{ __('Closed Tickets') }}</p>
                                </li>
                                @forelse ($closedTickets as $ticket)
                                    <li class="checkforactive active"
                                        onclick="loadClosedConversation(`{{ $ticket->cp_tkt_no }}`)"
                                        style="background: #f9e9e9;">
                                        <a href="javascript:void(0);">
                                            <div class="d-flex align-items-top">

                                                <div class="flex-fill">
                                                    <p class="mb-0 fw-semibold">
                                                        #{{ $ticket->cp_tkt_no }} {{ $ticket->subject }}
                                                        <span
                                                            class="float-end text-muted fw-normal fs-11">{{ $ticket->created_at }}</span>
                                                    </p>
                                                    <p class="fs-12 mb-0">
                                                        <span class="chat-msg text-truncate">{{ $ticket->msg }}</span>

                                                    </p>
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                @empty
                                    <li></li>
                                @endforelse

                            </ul>

                        </div>
                    </div>
                </div>
                <div class="main-chat-area border">
                    <div class="d-flex align-items-center p-2 border-bottom">

                        <div class="flex-fill">
                            <p class="mb-0 fw-semibold fs-14">
                                <a href="javascript:void(0);"
                                    class="chatnameperson responsive-userinfo-open">{{ __('Conversation') }}</a>
                            </p>

                        </div>

                    </div>
                    <div class="chat-content" data-simplebar="init">
                        <div class="simplebar-wrapper" style="margin: -40px;">
                            <div class="simplebar-height-auto-observer-wrapper">
                                <div class="simplebar-height-auto-observer"></div>
                            </div>
                            <div class="simplebar-mask">
                                <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                                    <div class="simplebar-content-wrapper" tabindex="0" role="region"
                                        aria-label="scrollable content" style="height: auto; overflow: hidden scroll;">
                                        <div class="simplebar-content" style="padding: 40px;">
                                            <ul class="list-unstyled" id="tkt_conv">


                                            </ul>
                                            <div class="text-center" id="support_img">

                                                <img src="assets/images/support.png" alt="support" srcset=""
                                                    width="500" style="opacity: .3;">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="simplebar-placeholder" style="width: auto; height: 924px;"></div>
                        </div>
                        <div class="simplebar-track simplebar-horizontal" style="visibility: hidden;">
                            <div class="simplebar-scrollbar" style="width: 0px; display: none;"></div>
                        </div>
                        <div class="simplebar-track simplebar-vertical" style="visibility: visible;">
                            <div class="simplebar-scrollbar"
                                style="height: 25px; transform: translate3d(0px, 30px, 0px); display: block;"></div>
                        </div>
                    </div>
                    <div class="chat-footer" id="replyInput" style="display: none;">
                        <input class="form-control" placeholder="Type your message here..." type="text">

                        <a aria-label="anchor" class="btn btn-primary btn-icon btn-send" id="replyBtn"
                            href="javascript:void(0)">
                            <i class="ri-send-plane-2-line"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- -------------------Modals------------------------ --}}

    {{-- new ticket modal --}}

    <div class="modal fade" id="new_tkt_modal" tabindex="-1" aria-labelledby="exampleModalLgLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">{{ __('Ticket Information') }}</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('support.createTicket') }}" method="post">
                        @csrf
                        <div class="row">
                            <div class="col-xl-6">
                                <label class="form-label text-default">{{ __('Name') }}<sup class="text-danger">*</sup></label>
                                <input type="text" class="form-control form-control-lg" name="name"
                                    placeholder="{{ __('Full name') }}" required>
                            </div>
                            <div class="col-xl-6">
                                <label class="form-label text-default">{{ __('Email') }}<sup class="text-danger">*</sup></label>
                                <input type="email" class="form-control form-control-lg" name="email"
                                    placeholder="{{ __('Enter your email') }}" required>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-xl-6">
                                <label for="company" class="form-label text-default">{{ __('Subject') }}<sup
                                        class="text-danger">*</sup></label>
                                <div class="input-group">
                                    <input type="text" class="form-control form-control-lg" name="sub"
                                        id="company" placeholder="{{ __('Subject') }}" required>

                                </div>
                            </div>
                            <div class="col-xl-6">
                                <label for="input-label" class="form-label">{{ __('Priority') }}<sup
                                        class="text-danger">*</sup></label>
                                <select class="form-control" name="priority" required>
                                    <option value="High">{{ __('High') }}</option>
                                    <option value="Medium">{{ __('Medium') }}</option>
                                    <option value="Low">{{ __('Low') }}</option>
                                </select>

                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-xl-12">
                                <label for="text-area" class="form-label">{{ __('Message') }}<sup class="text-danger">*</sup></label>
                                <textarea class="form-control" id="text-area" name="msg" placeholder="{{ __('Enter your Message') }}" rows="5"></textarea>
                            </div>
                        </div>


                        <div class="col-xl-12 d-grid mt-2">
                            <button type="submit" class="btn btn-lg btn-primary">{{ __('Create Ticket') }}</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>





    {{-- -------------------Modals------------------------ --}}


    {{-- ------------------------------Toasts---------------------- --}}

    <x-toast />



    @push('newcss')
        <style>
            .selected {
                background-color: #f0f0f0;
                /* Adjust the color to your preference */
            }
        </style>
    @endpush

    @push('newscripts')
        <script>
            $(document).ready(function() {
                $('.checkforactive').on('click', function() {
                    // Remove 'selected' class from all chat items
                    $('.checkforactive').removeClass('selected');

                    // Add 'selected' class to the clicked chat item
                    $(this).addClass('selected');
                });
            });
        </script>

        <script>
            function createChatHtml(res) {
                var chatHtml = '';
                res.forEach((obj) => {
                    if (obj.person == 'partner') {
                        chatHtml += `<li class="chat-item-start">
                                                        <div class="chat-list-inner">
                                                            
                                                            <div class="ms-3">
                                                                <span class="chatting-user-info">
                                                                    <span class="chatnameperson">Partner</span> <span class="msg-sent-time">${obj.date}</span>
                                                                </span>
                                                                <div class="main-chat-msg">
                                                                    <div>
                                                                        <p class="mb-0">${obj.msg}</p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </li>`;
                    }

                    if (obj.person == 'company') {
                        chatHtml += `<li class="chat-item-end">
                                                        <div class="chat-list-inner">
                                                            <div class="me-3">
                                                                <span class="chatting-user-info">
                                                                    <span class="msg-sent-time"><span class="chat-read-mark align-middle d-inline-flex"></i></span>${obj.date}</span> You
                                                                </span>
                                                                <div class="main-chat-msg d-flex justify-content-end">
                                                                    <div>
                                                                        <p class="mb-0">${obj.msg}</p>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                        </div>
                                                    </li>`;
                    }


                })

                $("#tkt_conv").html(chatHtml);
            }

            function loadConversation(id) {
                $("#replyInput").show();
                $("#support_img").hide();

                $.post({
                    url: '/support/load-conversations',
                    data: {
                        loadConv: '1',
                        tkt_id: id
                    },
                    success: function(res) {
                        // var jsonRes = JSON.parse(res);
                        // console.log(res)
                        createChatHtml(res);
                        $("#replyBtn").attr('onclick', `submitReply('${res[0].tkt_id}')`);
                        // window.location.href = window.location.href;
                    }
                })
                // console.log(id)
            }

            function loadClosedConversation(id) {
                $("#replyInput").hide();
                $("#support_img").hide();
                $.post({
                    url: '/support/load-conversations',
                    data: {
                        loadConv: '1',
                        tkt_id: id
                    },
                    success: function(res) {
                        // var jsonRes = JSON.parse(res);
                        // console.log(jsonRes)
                        createChatHtml(res);
                        // window.location.href = window.location.href;
                    }
                })
                console.log(id)
            }

            function submitReply(tkt_id) {

                var msg = $("#replyInput input").val();
                $.post({
                    url: '/support/submit-reply',
                    data: {
                        submitReply: '1',
                        tkt_id: tkt_id,
                        msg: msg
                    },
                    success: function(res) {
                        if(res.status == 0){
                            Swal.fire({
                                icon: 'error',
                                title: "{{ __('Oops...') }}",
                                text: res.msg,
                                confirmButtonText: "{{ __('OK') }}"
                            })
                            return;
                        }
                        // var jsonRes = JSON.parse(res);
                        // console.log(jsonRes)
                        $("#replyInput input").val('');
                        loadConversation(tkt_id);
                        // window.location.href = window.location.href;
                    }
                })


            }
        </script>
    @endpush

@endsection
