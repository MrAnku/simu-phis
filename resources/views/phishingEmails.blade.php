@extends('layouts.app')

@section('title', 'Phishing Emails - Phishing awareness training program')

@section('main-content')

    <div class="main-content app-content">
        <div class="container-fluid mt-4">

            <div class="d-flex justify-content-between">
                <div>
                    <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal"
                        data-bs-target="#newPhishingmailModal">New Email Template</button>
                </div>

            </div>


            <div class="row">
                <div class="col-xl-12">
                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">
                                Manage Phishing Emails
                            </div>
                        </div>
                        <div class="card-body">

                            <div class="row">
                                @forelse ($phishingEmails as $pemail)
                                    <div class="col-lg-6">
                                        <div class="card custom-card">
                                            <div class="card-header">
                                                <div class="d-flex align-items-center w-100">

                                                    <div class="">
                                                        <div class="fs-15 fw-semibold"></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card-body htmlPhishingGrid" id="mailBody{{ $pemail->id }}">
                                                <iframe class="phishing-iframe"
                                                    src="{{ Storage::url($pemail->mailBodyFilePath) }}"></iframe>
                                            </div>
                                            <div class="card-footer">
                                                <div class="d-flex justify-content-center">
                                                    <button type="button"
                                                        {{-- onclick="viewInTemplate(
                                                        `{{ $pemail->sender_p->profile_name }}`,
                                                        `{{ $pemail->sender_profile->from_email }}`,
                                                        `{{ $pemail->email_subject }}`,`mailBody{{ $pemail->id }}`
                                                        )" --}}
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#viewPhishingmailModal"
                                                        class="btn mx-1 btn-outline-primary btn-wave waves-effect waves-light">View</button>


                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                @endforelse




                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- -------------------Modals------------------------ --}}

    <!-- view mailbody modal -->
    <div class="modal fade" id="viewPhishingmailModal" tabindex="-1" aria-labelledby="exampleModalLgLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Email Preview
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="container-fluid">
                        <div class="main-mail-container p-2 gap-2 d-flex">
                            <div class="mail-navigation border" style="display: block;">

                                <div>
                                    <ul class="list-unstyled mail-main-nav" id="mail-main-nav" data-simplebar="init">
                                        <div class="simplebar-wrapper" style="margin: -16px;">
                                            <div class="simplebar-height-auto-observer-wrapper">
                                                <div class="simplebar-height-auto-observer"></div>
                                            </div>
                                            <div class="simplebar-mask">
                                                <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                                                    <div class="simplebar-content-wrapper" tabindex="0" role="region"
                                                        aria-label="scrollable content"
                                                        style="height: auto; overflow: hidden scroll;">
                                                        <div class="simplebar-content" style="padding: 16px;">
                                                            <li class="px-0 pt-0">
                                                                <span class="fs-11 text-muted op-7 fw-semibold">MAILS</span>
                                                            </li>
                                                            <li class="active mail-type">
                                                                <a href="javascript:void(0);">
                                                                    <div class="d-flex align-items-center">
                                                                        <span class="me-2 lh-1">
                                                                            <i
                                                                                class="ri-inbox-archive-line align-middle fs-14"></i>
                                                                        </span>
                                                                        <span class="flex-fill text-nowrap">
                                                                            All Mails
                                                                        </span>
                                                                        <span
                                                                            class="badge bg-success-transparent rounded-pill">12,456</span>
                                                                    </div>
                                                                </a>
                                                            </li>
                                                            <li class="mail-type">
                                                                <a href="javascript:void(0);">
                                                                    <div class="d-flex align-items-center">
                                                                        <span class="me-2 lh-1">
                                                                            <i
                                                                                class="ri-inbox-archive-line align-middle fs-14"></i>
                                                                        </span>
                                                                        <span class="flex-fill text-nowrap">
                                                                            Inbox
                                                                        </span>
                                                                        <span
                                                                            class="badge bg-primary-transparent rounded-circle">8</span>
                                                                    </div>
                                                                </a>
                                                            </li>
                                                            <li class="mail-type">
                                                                <a href="javascript:void(0);">
                                                                    <div class="d-flex align-items-center">
                                                                        <span class="me-2 lh-1">
                                                                            <i
                                                                                class="ri-send-plane-2-line align-middle fs-14"></i>
                                                                        </span>
                                                                        <span class="flex-fill text-nowrap">
                                                                            Sent
                                                                        </span>
                                                                    </div>
                                                                </a>
                                                            </li>
                                                            <li class="mail-type">
                                                                <a href="javascript:void(0);">
                                                                    <div class="d-flex align-items-center">
                                                                        <span class="me-2 lh-1">
                                                                            <i class="ri-draft-line align-middle fs-14"></i>
                                                                        </span>
                                                                        <span class="flex-fill text-nowrap">
                                                                            Drafts
                                                                        </span>
                                                                    </div>
                                                                </a>
                                                            </li>
                                                            <li class="mail-type">
                                                                <a href="javascript:void(0);">
                                                                    <div class="d-flex align-items-center">
                                                                        <span class="me-2 lh-1">
                                                                            <i
                                                                                class="ri-spam-2-line align-middle fs-14"></i>
                                                                        </span>
                                                                        <span class="flex-fill text-nowrap">
                                                                            Spam
                                                                        </span>
                                                                        <span
                                                                            class="badge bg-danger-transparent rounded-circle">4</span>
                                                                    </div>
                                                                </a>
                                                            </li>
                                                            <li class="mail-type">
                                                                <a href="javascript:void(0);">
                                                                    <div class="d-flex align-items-center">
                                                                        <span class="me-2 lh-1">
                                                                            <i
                                                                                class="ri-bookmark-line align-middle fs-14"></i>
                                                                        </span>
                                                                        <span class="flex-fill text-nowrap">
                                                                            Important
                                                                        </span>
                                                                    </div>
                                                                </a>
                                                            </li>
                                                            <li class="mail-type">
                                                                <a href="javascript:void(0);">
                                                                    <div class="d-flex align-items-center">
                                                                        <span class="me-2 lh-1">
                                                                            <i
                                                                                class="ri-delete-bin-line align-middle fs-14"></i>
                                                                        </span>
                                                                        <span class="flex-fill text-nowrap">
                                                                            Trash
                                                                        </span>
                                                                    </div>
                                                                </a>
                                                            </li>
                                                            <li class="mail-type">
                                                                <a href="javascript:void(0);">
                                                                    <div class="d-flex align-items-center">
                                                                        <span class="me-2 lh-1">
                                                                            <i
                                                                                class="ri-archive-line align-middle fs-14"></i>
                                                                        </span>
                                                                        <span class="flex-fill text-nowrap">
                                                                            Archive
                                                                        </span>
                                                                    </div>
                                                                </a>
                                                            </li>
                                                            <li class="mail-type">
                                                                <a href="javascript:void(0);">
                                                                    <div class="d-flex align-items-center">
                                                                        <span class="me-2 lh-1">
                                                                            <i class="ri-star-line align-middle fs-14"></i>
                                                                        </span>
                                                                        <span class="flex-fill text-nowrap">
                                                                            Starred
                                                                        </span>
                                                                        <span
                                                                            class="badge bg-warning-transparent rounded-circle">12</span>
                                                                    </div>
                                                                </a>
                                                            </li>
                                                            <li class="px-0">
                                                                <span
                                                                    class="fs-11 text-muted op-7 fw-semibold">SETTINGS</span>
                                                            </li>
                                                            <li>
                                                                <a href="javascript:void(0);">
                                                                    <div class="d-flex align-items-center">
                                                                        <span class="me-2 lh-1">
                                                                            <i
                                                                                class="ri-settings-3-line align-middle fs-14"></i>
                                                                        </span>
                                                                        <span class="flex-fill text-nowrap">
                                                                            Settings
                                                                        </span>
                                                                    </div>
                                                                </a>
                                                            </li>
                                                            <li class="px-0">
                                                                <span
                                                                    class="fs-11 text-muted op-7 fw-semibold">LABELS</span>
                                                            </li>
                                                            <li>
                                                                <a href="javascript:void(0);">
                                                                    <div class="d-flex align-items-center">
                                                                        <span class="me-2 lh-1">
                                                                            <i
                                                                                class="ri-price-tag-line align-middle fs-14 fw-semibold text-secondary"></i>
                                                                        </span>
                                                                        <span class="flex-fill text-nowrap">
                                                                            Mail
                                                                        </span>
                                                                    </div>
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a href="javascript:void(0);">
                                                                    <div class="d-flex align-items-center">
                                                                        <span class="me-2 lh-1">
                                                                            <i
                                                                                class="ri-price-tag-line align-middle fs-14 fw-semibold text-danger"></i>
                                                                        </span>
                                                                        <span class="flex-fill text-nowrap">
                                                                            Home
                                                                        </span>
                                                                    </div>
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a href="javascript:void(0);">
                                                                    <div class="d-flex align-items-center">
                                                                        <span class="me-2 lh-1">
                                                                            <i
                                                                                class="ri-price-tag-line align-middle fs-14 fw-semibold text-success"></i>
                                                                        </span>
                                                                        <span class="flex-fill text-nowrap">
                                                                            Work
                                                                        </span>
                                                                    </div>
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a href="javascript:void(0);">
                                                                    <div class="d-flex align-items-center">
                                                                        <span class="me-2 lh-1">
                                                                            <i
                                                                                class="ri-price-tag-line align-middle fs-14 fw-semibold text-dark"></i>
                                                                        </span>
                                                                        <span class="flex-fill text-nowrap">
                                                                            Friends
                                                                        </span>
                                                                    </div>
                                                                </a>
                                                            </li>
                                                            <li class="px-0">
                                                                <span class="fs-11 text-muted op-7 fw-semibold">ONLINE
                                                                    USERS</span>
                                                            </li>
                                                            <li>
                                                                <a href="javascript:void(0);">
                                                                    <div class="d-flex align-items-top lh-1">
                                                                        <div class="me-2">
                                                                            <span
                                                                                class="avatar avatar-sm online avatar-rounded">
                                                                                <img src="../assets/images/faces/4.jpg"
                                                                                    alt="">
                                                                            </span>
                                                                        </div>
                                                                        <div>
                                                                            <p class="text-default fw-semibold mb-1">
                                                                                Angelica</p>
                                                                            <p class="fs-12 text-muted mb-0">Hello this is
                                                                                angelica.</p>
                                                                        </div>
                                                                    </div>
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a href="javascript:void(0);">
                                                                    <div class="d-flex align-items-top lh-1">
                                                                        <div class="me-2">
                                                                            <span
                                                                                class="avatar avatar-sm online avatar-rounded">
                                                                                <img src="../assets/images/faces/6.jpg"
                                                                                    alt="">
                                                                            </span>
                                                                        </div>
                                                                        <div>
                                                                            <p class="text-default fw-semibold mb-1">Rexha
                                                                            </p>
                                                                            <p class="fs-12 text-muted mb-0">Thanks for
                                                                                sharing file 😀.</p>
                                                                        </div>
                                                                    </div>
                                                                </a>
                                                            </li>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="simplebar-placeholder" style="width: auto; height: 755px;"></div>
                                        </div>
                                        <div class="simplebar-track simplebar-horizontal" style="visibility: hidden;">
                                            <div class="simplebar-scrollbar" style="width: 0px; display: none;"></div>
                                        </div>
                                        <div class="simplebar-track simplebar-vertical" style="visibility: visible;">
                                            <div class="simplebar-scrollbar"
                                                style="height: 68px; transform: translate3d(0px, 0px, 0px); display: block;">
                                            </div>
                                        </div>
                                    </ul>
                                </div>
                            </div>
                            <div class="mails-information border" style="display: block;">
                                <div class="mail-info-header d-flex flex-wrap gap-2 align-items-center">
                                    <div class="me-1">
                                        <span class="avatar avatar-md online me-2 avatar-rounded mail-msg-avatar">
                                            <img src="https://i.pinimg.com/736x/0d/64/98/0d64989794b1a4c9d89bff571d3d5842.jpg"
                                                alt="">
                                        </span>
                                    </div>
                                    <div class="flex-fill">
                                        <h6 class="mb-0 fw-semibold" id="displayFromName">Michael Jeremy</h6>
                                        <span class="text-muted fs-12"
                                            id="displayFromEmail">michaeljeremy2194@gmail.com</span>
                                    </div>
                                    <div class="mail-action-icons">
                                        <button aria-label="button" type="button" class="btn btn-icon btn-light"
                                            data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Starred">
                                            <i class="ri-star-line"></i>
                                        </button>
                                        <button aria-label="button" type="button" class="btn btn-icon btn-light ms-1"
                                            data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Archive">
                                            <i class="ri-inbox-archive-line"></i>
                                        </button>
                                        <button aria-label="button" type="button" class="btn btn-icon btn-light ms-1"
                                            data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Report spam">
                                            <i class="ri-spam-2-line"></i>
                                        </button>
                                        <button aria-label="button" type="button" class="btn btn-icon btn-light ms-1"
                                            data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Delete">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                        <button aria-label="button" type="button" class="btn btn-icon btn-light ms-1"
                                            data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Reply">
                                            <i class="ri-reply-line"></i>
                                        </button>
                                    </div>
                                    <div class="responsive-mail-action-icons">
                                        <div class="dropdown">
                                            <button aria-label="button" type="button"
                                                class="btn btn-icon btn-light btn-wave waves-light waves-effect"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="ti ti-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="javascript:void(0);"><i
                                                            class="ri-star-line me-1 align-middle d-inline-block"></i>Starred</a>
                                                </li>
                                                <li><a class="dropdown-item" href="javascript:void(0);"><i
                                                            class="ri-inbox-archive-line me-1 align-middle d-inline-block"></i>Archive</a>
                                                </li>
                                                <li><a class="dropdown-item" href="javascript:void(0);"><i
                                                            class="ri-spam-2-line me-1 align-middle d-inline-block"></i>Report
                                                        Spam</a></li>
                                                <li><a class="dropdown-item" href="javascript:void(0);"><i
                                                            class="ri-delete-bin-line me-1 align-middle d-inline-block"></i>Delete</a>
                                                </li>
                                                <li><a class="dropdown-item" href="javascript:void(0);"><i
                                                            class="ri-reply-line me-1 align-middle d-inline-block"></i>Reply</a>
                                                </li>
                                            </ul>
                                        </div>
                                        <button aria-label="button" type="button"
                                            class="btn btn-icon btn-light ms-1 close-button">
                                            <i class="ri-close-line"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="mail-info-body p-4" id="mail-info-body" data-simplebar="init">
                                    <div class="simplebar-wrapper" style="margin: -24px;">
                                        <div class="simplebar-height-auto-observer-wrapper">
                                            <div class="simplebar-height-auto-observer"></div>
                                        </div>
                                        <div class="simplebar-mask">
                                            <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                                                <div class="simplebar-content-wrapper" tabindex="0" role="region"
                                                    aria-label="scrollable content"
                                                    style="height: auto; overflow: hidden scroll;">
                                                    <div class="simplebar-content" style="padding: 24px;">
                                                        <div
                                                            class="d-sm-flex d-block align-items-center justify-content-between mb-4">
                                                            <div>
                                                                <p class="fs-20 fw-semibold mb-0" id="displayMailSubject">
                                                                    History of planets are discovered yesterday.</p>
                                                            </div>
                                                            <div class="float-end">
                                                                <span
                                                                    class="me-2 fs-12 text-muted">Oct-22-2022,03:05PM</span>
                                                            </div>
                                                        </div>
                                                        <div class="main-mail-content mb-4" id="displayMailBodyContent">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="simplebar-placeholder" style="width: auto; height: 883px;"></div>
                                    </div>
                                    <div class="simplebar-track simplebar-horizontal" style="visibility: hidden;">
                                        <div class="simplebar-scrollbar" style="width: 0px; display: none;"></div>
                                    </div>
                                    <div class="simplebar-track simplebar-vertical" style="visibility: visible;">
                                        <div class="simplebar-scrollbar"
                                            style="height: 64px; transform: translate3d(0px, 0px, 0px); display: block;">
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
    <style>
        .htmlPhishingGrid {
            overflow: scroll;
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
    </style>
    @endpush

    @push('newscripts')
    @endpush

@endsection
