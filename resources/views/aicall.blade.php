@extends('layouts.app')

@section('title', 'AI Calling - Phishing awareness training program')

@section('main-content')

    <div class="main-content app-content">
        <div class="container-fluid py-3">


            @if ($company && $company->status == 0)
                <div class="row">
                    <div class="col-xl-12">
                        <div class="card custom-card">
                            <div class="card-header">
                                <div class="card-title">{{ __('AI Calling') }}</div>
                            </div>
                            <div class="card-body">
                                <h5 class="text-warning">
                                    {{ __('Your request is in pending state. We will update you soon.') }}
                                </h5>


                            </div>
                        </div>
                    </div>


                </div>
            @elseif ($company && $company->status == 1)
                <div class="d-flex justify-content-between">
                    <div>
                        <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal"
                            data-bs-target="#newCampaignCallModal">{{ __('New Call Campaign') }}</button>
                    </div>
                    <div>
                        <button type="button" class="btn btn-secondary mb-3" data-bs-toggle="modal"
                            data-bs-target="#newAiAgentModal">{{ __('Request New Agent') }}</button>
                    </div>

                </div>
                <div class="row">


                    <div class="col-xl-12">

                        <div class="card custom-card">
                            <div class="card-header">
                                <div class="card-title">{{ __('Campaigns') }}</div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table text-nowrap">
                                        <thead>
                                            <tr>
                                                <th scope="col">{{ __('Campaign Name') }}</th>
                                                <th scope="col">{{ __('Employee Group') }}</th>
                                                <th scope="col">{{ __('AI Agent') }}</th>
                                                <th scope="col">{{ __('Training') }}</th>
                                                <th scope="col">{{ __('Phone No.') }}</th>
                                                <th scope="col">{{ __('Status') }}</th>
                                                <th scope="col">{{ __('Action') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>

                                            @forelse ($campaigns as $campaign)
                                                <tr>
                                                    <th scope="row">
                                                        {{ $campaign->campaign_name ?? '' }}
                                                    </th>
                                                    <td>
                                                        {{ $campaign->emp_grp_name ?? '' }}
                                                    </td>
                                                    <td>
                                                        {{ $campaign->ai_agent_name ?? '' }}
                                                        <div>
                                                            <span class="fs-11 text-muted">
                                                                {{ $campaign->ai_agent ?? '' }}
                                                            </span>
                                                        </div>

                                                    </td>
                                                    <td>
                                                        {{ $campaign->trainingName->name ?? __('Only Phishing') }}


                                                    </td>
                                                    <td>
                                                        <span class="badge bg-primary">
                                                            {{ $campaign->phone_no ?? '' }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @if ($campaign->status == 'pending')
                                                            <span class="badge bg-warning">
                                                                {{ __('Pending') }}
                                                            </span>
                                                        @elseif($campaign->status == 'completed')
                                                            <span class="badge bg-success">
                                                                {{ __('Completed') }}
                                                            </span>
                                                        @endif

                                                    </td>
                                                    <td>
                                                        <div class="mb-md-0 mb-2">

                                                            <button
                                                                class="btn btn-icon btn-success-transparent rounded-pill btn-wave"
                                                                data-bs-toggle="modal" data-bs-target="#viewCampaignModal"
                                                                onclick="viewCamp(`{{ base64_encode($campaign->id) }}`)">
                                                                <i class="ri-eye-line"></i>
                                                            </button>
                                                            <button
                                                                class="btn btn-icon btn-danger-transparent rounded-pill btn-wave me-5"
                                                                onclick="deleteCamp(`{{ base64_encode($campaign->id) }}`)">
                                                                <i class="ri-delete-bin-line"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty

                                                <tr>
                                                    <td colspan="6" class="text-center">{{ __('No data found') }}</td>
                                                </tr>
                                            @endforelse




                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-4">
                                    {{ $campaigns->links() }}
                                </div>
                            </div>
                        </div>


                    </div>
                </div>
            @else
                <div class="row">
                    <div class="col-xl-12">
                        <div class="card custom-card">
                            <div class="card-header">
                                <div class="card-title">{{ __('AI Calling') }}</div>
                            </div>
                            <div class="card-body">
                                <h5 class="text-warning">
                                    {{ __('AI Calling feature is not enabled in your account. Please contact your service provider to enable this feature.') }}
                                </h5>


                                <div class="mt-3">
                                    <h5 class="text-lg-start fw-semibold mb-1">{{ __('What is AI Calling?') }}</h5>
                                    <p class=" text-muted">
                                        {{ __('AI calling feature refers to the use of artificial intelligence to automate phone calls, either by generating human-like voice responses or conducting conversations with users. These systems can handle tasks like customer service, appointment scheduling, or even interactive voice response (IVR) systems, simulating real human interaction.') }}
                                    </p>
                                    <p class=" text-muted">
                                        {{ __('In phishing, AI calling can be misused to carry out voice phishing (vishing) attacks. Fraudsters can use AI-generated calls to impersonate trusted entities (e.g., banks, government agencies) and deceive victims into providing sensitive information such as passwords, credit card details, or personal identification numbers, without the need for a human operator. The realism and scale of AI-powered calls make these attacks more convincing and harder to detect.') }}
                                    </p>
                                </div>
                                <div class="mt-3">

                                    <button type="button" data-bs-toggle="modal" data-bs-target="#exampleModalScrollable2"
                                        class="btn btn-primary btn-wave">{{ __('Request for AI Calling Feature') }}</button>
                                </div>
                            </div>
                        </div>
                    </div>


                </div>
            @endif





        </div>
    </div>

    {{-- -----------------------------offcanvas------------------- --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="callDetailOffCanvas"
        aria-labelledby="callDetailOffCanvasLabel1">
        <div class="offcanvas-header border-bottom border-block-end-dashed">
            <div class="d-flex justify-content-between w-100">
                <h5 class="offcanvas-title" id="callDetailOffCanvasLabel1">{{ __('Call Detail') }}
                </h5>
                @if (app()->getLocale() !== 'en')
                    <button id="translateBtn" onclick="translateCallDetail(this, '{{app()->getLocale()}}')" class="btn btn-sm btn-primary me-3">Translate in
                        {{ strtoupper(app()->getLocale()) }}</button>
                @endif

            </div>

            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-3">
            <div class="card-body" id="placeholder">
                <div class="h5 card-title placeholder-glow">
                    <span class="placeholder col-6"></span>
                </div>
                <p class="card-text placeholder-glow">
                    <span class="placeholder col-7"></span>
                    <span class="placeholder col-4"></span>
                    <span class="placeholder col-6"></span>
                    <span class="placeholder col-4"></span>
                </p>
                <div class="h5 card-title placeholder-glow">
                    <span class="placeholder col-6"></span>
                </div>
                <p class="card-text placeholder-glow">
                    <span class="placeholder col-7"></span>
                    <span class="placeholder col-4"></span>
                    <span class="placeholder col-4"></span>
                    <span class="placeholder col-6"></span>
                </p>
                <div class="h5 card-title placeholder-glow">
                    <span class="placeholder col-6"></span>
                </div>
                <p class="card-text placeholder-glow">
                    <span class="placeholder col-4"></span>
                    <span class="placeholder col-7"></span>
                    <span class="placeholder col-4"></span>
                    <span class="placeholder col-6"></span>
                </p>
                <p class="card-text placeholder-glow">
                    <span class="placeholder col-7"></span>
                    <span class="placeholder col-4"></span>
                    <span class="placeholder col-4"></span>
                    <span class="placeholder col-6"></span>
                </p>
                <div class="h5 card-title placeholder-glow">
                    <span class="placeholder col-6"></span>
                </div>
                <p class="card-text placeholder-glow">
                    <span class="placeholder col-4"></span>
                    <span class="placeholder col-7"></span>
                    <span class="placeholder col-4"></span>
                    <span class="placeholder col-6"></span>
                </p>
                <p class="card-text placeholder-glow">
                    <span class="placeholder col-7"></span>
                    <span class="placeholder col-4"></span>
                    <span class="placeholder col-4"></span>
                    <span class="placeholder col-6"></span>
                </p>
                <div class="h5 card-title placeholder-glow">
                    <span class="placeholder col-6"></span>
                </div>
                <p class="card-text placeholder-glow">
                    <span class="placeholder col-4"></span>
                    <span class="placeholder col-7"></span>
                    <span class="placeholder col-4"></span>
                    <span class="placeholder col-6"></span>
                </p>
                <p class="card-text placeholder-glow">
                    <span class="placeholder col-7"></span>
                    <span class="placeholder col-4"></span>
                    <span class="placeholder col-4"></span>
                    <span class="placeholder col-6"></span>
                </p>
                <div class="h5 card-title placeholder-glow">
                    <span class="placeholder col-6"></span>
                </div>
                <p class="card-text placeholder-glow">
                    <span class="placeholder col-4"></span>
                    <span class="placeholder col-7"></span>
                    <span class="placeholder col-4"></span>
                    <span class="placeholder col-6"></span>
                </p>
            </div>

            <div id="call_detail">

            </div>
        </div>
    </div>

    {{-- -------------------Modals------------------------ --}}

    {{-- Request AI Calling modal --}}

    <div class="modal fade" id="exampleModalScrollable2" tabindex="-1" aria-labelledby="exampleModalScrollable2"
        data-bs-keyboard="false" aria-hidden="true">
        <!-- Scrollable modal -->
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title" id="staticBackdropLabel2">{{ __('Request for AI Calling') }}
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('ai.calling.sub.req') }}" method="post">
                    @csrf
                    <div class="modal-body">
                        <p>
                            {{ __('We are pleased to confirm that we agree to provide the AI calling feature as requested. Our team is ready to move forward with the AI Calling functionality.') }}
                        </p>
                        <input class="form-check-input" name="terms" type="checkbox" value="" id="checkebox-md"
                            required>
                        <label class="form-check-label" for="checkebox-md">
                            {{ __('I agree and comply the terms & conditions of this feature.') }}
                        </label>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">{{ __('Send Request') }}</button>
                    </div>
                </form>

            </div>
        </div>
    </div>


    {{-- New Campaign Modal --}}

    <div class="modal fade" id="newCampaignCallModal" tabindex="-1" aria-labelledby="exampleModalScrollable2"
        data-bs-keyboard="false" aria-hidden="true">
        <!-- Scrollable modal -->
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title" id="staticBackdropLabel2">{{ __('Create Call Campaign') }}
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                @csrf
                <div class="modal-body">
                    <div class="card custom-card">
                        <div class="card-body p-0 product-checkout">
                            <ul class="nav nav-tabs tab-style-2 d-sm-flex d-block border-bottom border-block-end-dashed justify-content-center"
                                id="myTab1" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="campaign-tab" data-bs-toggle="tab"
                                        data-bs-target="#campaign_detail" type="button" role="tab"
                                        aria-controls="order-tab" aria-selected="true"><i
                                            class="ri-mail-send-line me-2 align-middle"></i>{{ __('Campaign Detail') }}</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="phishing-tab" data-bs-toggle="tab"
                                        data-bs-target="#phishing" type="button" role="tab"
                                        aria-controls="confirmed-tab" aria-selected="false"><i
                                            class="ri-presentation-line me-2 align-middle"></i>{{ __('Phishing & Training') }}</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="phone-tab" data-bs-toggle="tab" data-bs-target="#phone"
                                        type="button" role="tab" aria-controls="shipped-tab"
                                        aria-selected="false"><i
                                            class="ri-phone-line me-2 align-middle"></i>{{ __('AI Agent & Phone') }}</button>
                                </li>

                            </ul>
                            <form id="campaignDetail" action="{{ route('ai.call.create.campaign') }}" method="post">
                                @csrf
                                <div class="tab-content" id="myTabContent">

                                    <div class="tab-pane fade show active border-0 p-0" id="campaign_detail"
                                        role="tabpanel" aria-labelledby="order-tab-pane" tabindex="0">
                                        <div class="p-4">

                                            <div class="row">
                                                <div class="col-lg-6">
                                                    <div class="input-group mb-3">
                                                        <span class="input-group-text"
                                                            id="basic-addon1">{{ __('Campaign Name') }}</span>
                                                        <input type="text" class="form-control" id="campaignName"
                                                            name="camp_name"
                                                            placeholder="{{ __('Enter Campaign name') }}">
                                                    </div>
                                                </div>
                                                <div class="col-lg-6">
                                                    <div class="input-group mb-3">
                                                        <label class="input-group-text"
                                                            for="emp_group">{{ __('Employee Group') }}</label>
                                                        <select class="form-select" id="emp_group" name="emp_group">
                                                            <option value="" selected>{{ __('Choose...') }}</option>
                                                            @forelse ($empGroups as $empGroup)
                                                                <option value="{{ $empGroup->group_id }}">
                                                                    {{ $empGroup->group_name }}</option>
                                                            @empty
                                                            @endforelse

                                                        </select>
                                                    </div>
                                                </div>
                                            </div>



                                        </div>
                                        <div
                                            class="px-4 py-3 border-top border-block-start-dashed d-sm-flex justify-content-end">
                                            <button type="button" class="btn btn-success-light"
                                                id="phishing-trigger">{{ __('Next') }}
                                                </i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade border-0 p-0" id="phishing" role="tabpanel"
                                        aria-labelledby="confirm-tab-pane" tabindex="0">
                                        <div class="p-4">
                                            <div class="d-flex justify-content-center">
                                                <div class="custom-toggle-switch d-flex align-items-center mb-4">
                                                    <input id="phishing_only" name="phishing_only" type="checkbox"
                                                        value="false">
                                                    <label for="phishing_only" class="label-primary"></label><span
                                                        class="ms-3">{{ __('Phishing Only') }}</span>
                                                </div>
                                            </div>
                                            <div class="text-center" id="notemsg" style="display: none;">
                                                <p class="mb-0">
                                                    <em>{{ __('This Campaign will be triggered without training.') }}</em>
                                                </p>
                                            </div>
                                            <div id="training_grids">
                                                <div class="d-flex justify-content-between pb-1">
                                                    <div class="d-flex gap-2">
                                                        <div>
                                                            <label for="input-label"
                                                                class="form-label">{{ __('Language') }}</label>

                                                            <select class="form-select" name="training_lang"
                                                                id="training_lang">
                                                                <option value="sq">{{ __('Albanian') }}</option>
                                                                <option value="ar">{{ __('Arabic') }}</option>
                                                                <option value="az">{{ __('Azerbaijani') }}</option>
                                                                <option value="bn">{{ __('Bengali') }}</option>
                                                                <option value="bg">{{ __('Bulgarian') }}</option>
                                                                <option value="ca">{{ __('Catalan') }}</option>
                                                                <option value="zh">{{ __('Chinese') }}</option>
                                                                <option value="zt">{{ __('Chinese (traditional)') }}
                                                                </option>
                                                                <option value="cs">{{ __('Czech') }}</option>
                                                                <option value="da">{{ __('Danish') }}</option>
                                                                <option value="nl">{{ __('Dutch') }}</option>
                                                                <option value="en" selected="">{{ __('English') }}
                                                                </option>
                                                                <option value="eo">{{ __('Esperanto') }}</option>
                                                                <option value="et">{{ __('Estonian') }}</option>
                                                                <option value="fi">{{ __('Finnish') }}</option>
                                                                <option value="fr">{{ __('French') }}</option>
                                                                <option value="de">{{ __('German') }}</option>
                                                                <option value="el">{{ __('Greek') }}</option>
                                                                <option value="he">{{ __('Hebrew') }}</option>
                                                                <option value="hi">{{ __('Hindi') }}</option>
                                                                <option value="hu">{{ __('Hungarian') }}</option>
                                                                <option value="id">{{ __('Indonesian') }}</option>
                                                                <option value="ga">{{ __('Irish') }}</option>
                                                                <option value="it">{{ __('Italian') }}</option>
                                                                <option value="ja">{{ __('Japanese') }}</option>
                                                                <option value="ko">{{ __('Korean') }}</option>
                                                                <option value="lv">{{ __('Latvian') }}</option>
                                                                <option value="lt">{{ __('Lithuanian') }}</option>
                                                                <option value="ms">{{ __('Malay') }}</option>
                                                                <option value="nb">{{ __('Norwegian') }}</option>
                                                                <option value="fa">{{ __('Persian') }}</option>
                                                                <option value="pl">{{ __('Polish') }}</option>
                                                                <option value="pt">{{ __('Portuguese') }}</option>
                                                                <option value="ro">{{ __('Romanian') }}</option>
                                                                <option value="ru">{{ __('Russian') }}</option>
                                                                <option value="sk">{{ __('Slovak') }}</option>
                                                                <option value="sl">{{ __('Slovenian') }}</option>
                                                                <option value="es">{{ __('Spanish') }}</option>
                                                                <option value="sv">{{ __('Swedish') }}</option>
                                                                <option value="tl">{{ __('Tagalog') }}</option>
                                                                <option value="th">{{ __('Thai') }}</option>
                                                                <option value="tr">{{ __('Turkish') }}</option>
                                                                <option value="uk">{{ __('Ukranian') }}</option>
                                                                <option value="ur">{{ __('Urdu') }}</option>
                                                            </select>
                                                        </div>
                                                        <div>
                                                            <label for="input-label"
                                                                class="form-label">{{ __('Training Type') }}</label>

                                                            <select class="form-select" name="training_type"
                                                                id="training_type">

                                                                <option value="static_training">
                                                                    {{ __('Static Training') }}</option>
                                                                <option value="ai_training">{{ __('AI Training') }}
                                                                </option>
                                                            </select>
                                                        </div>

                                                    </div>

                                                    <div>

                                                        <label for="t_moduleSearch"
                                                            class="form-label">{{ __('Search') }}</label>
                                                        <input type="text" class="form-control" id="t_moduleSearch"
                                                            placeholder="{{ __('Search template') }}">

                                                    </div>
                                                </div>
                                                <div class="row"
                                                    style="max-height: 300px;overflow-y: scroll;scrollbar-width: thin;">
                                                    @forelse ($trainings as $training)
                                                        @php
                                                            $coverImgPath = asset(
                                                                'storage/uploads/trainingModule/' .
                                                                    $training->cover_image,
                                                            );
                                                        @endphp
                                                        <div class="col-lg-6 t_modules">
                                                            <div class="card custom-card">
                                                                <div class="card-header">
                                                                    <div class="d-flex align-items-center w-100">
                                                                        <div class="">
                                                                            <div class="fs-15 fw-semibold">
                                                                                {{ $training->name }}
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="card-body htmlPhishingGrid">
                                                                    <img class="trainingCoverImg"
                                                                        src="{{ $coverImgPath }}" />
                                                                </div>
                                                                <div class="card-footer">
                                                                    <div class="d-flex justify-content-center">
                                                                        <div class="fs-semibold fs-14">
                                                                            <input type="radio" name="training_module"
                                                                                data-trainingName="{{ $training->name }}"
                                                                                value="{{ $training->id }}"
                                                                                class="btn-check"
                                                                                id="training{{ $training->id }}">
                                                                            <label class="btn btn-sm btn-outline-primary"
                                                                                for="training{{ $training->id }}">{{ __('Select this training') }}</label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @empty
                                                        <p>{{ __('No training modules available.') }}</p>
                                                    @endforelse
                                                </div>
                                            </div>

                                        </div>
                                        <input type="hidden" name="emp_group_name" id="emp_group_name">
                                        <input type="hidden" name="ai_agent_name" id="ai_agent_name">
                                        <div
                                            class="px-4 py-3 border-top border-block-start-dashed d-sm-flex justify-content-between">
                                            <button type="button" class="btn btn-danger-light m-1"
                                                id="back-to-campaign">{{ __('Back') }}</button>
                                            <button type="button" class="btn btn-success-light m-1"
                                                id="phone-trigger">{{ __('Next') }}</button>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade border-0 p-0" id="phone" role="tabpanel"
                                        aria-labelledby="shipped-tab-pane" tabindex="0">
                                        <div class="p-4">
                                            <div class="row">
                                                <div class="col-lg-6">
                                                    <div class="input-group mb-3">
                                                        <label class="input-group-text"
                                                            for="ai_agents">{{ __('AI Agent') }}</label>
                                                        <select class="form-select" id="ai_agent" name="ai_agent">
                                                            <option value="" selected>{{ __('Choose...') }}
                                                            </option>
                                                            @forelse ($agents as $agent)
                                                                <option value="{{ $agent['agent_id'] }}">
                                                                    {{ $agent['agent_name'] }}</option>
                                                            @empty
                                                            @endforelse


                                                        </select>
                                                    </div>


                                                </div>
                                                <div class="col-lg-6">
                                                    <div class="input-group mb-3">
                                                        <label class="input-group-text"
                                                            for="ai_phones">{{ __('Phone Number') }}</label>
                                                        <select class="form-select" id="ai_phones" name="ai_phone">
                                                            <option value="" selected>{{ __('Choose...') }}
                                                            </option>
                                                            @forelse ($phone_numbers as $phone_number)
                                                                <option value="{{ $phone_number['phone_number'] }}">
                                                                    {{ $phone_number['phone_number'] }}</option>
                                                            @empty
                                                            @endforelse
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div
                                            class="px-4 py-3 border-top border-block-start-dashed d-sm-flex justify-content-between">
                                            <button type="button" class="btn btn-danger-light m-1"
                                                id="back-to-phishing">{{ __('Back') }}</button>
                                            <button type="button" class="btn btn-success-light m-1"
                                                id="submit_campaign">{{ __('Save Campaign') }}</button>
                                        </div>
                                    </div>


                                </div>
                            </form>
                        </div>
                    </div>


                </div>



            </div>
        </div>
    </div>


    <div class="modal fade" id="newAiAgentModal" tabindex="-1" aria-labelledby="exampleModalScrollable2"
        data-bs-keyboard="false" aria-hidden="true">
        <!-- Scrollable modal -->
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">
                        {{ __('Request New AI Agent') }}
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>


                <div class="modal-body">

                    <div class="card-body">
                        <form method="post" enctype="multipart/form-data"
                            action="{{ route('ai.calling.agent.req') }}">
                            @csrf
                            <div class="mb-3">
                                <label for="agent-name" class="form-label fs-14 text-dark">{{ __('Enter agent name') }}
                                    <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="agent-name" name="agent_name"
                                    placeholder="{{ __('Enter agent name') }}">
                            </div>
                            <div class="mb-3">
                                <label for="agent-name" class="form-label fs-14 text-dark">{{ __('Language') }} <span
                                        class="text-danger">*</span></label>
                                <select name="language" id="language" class="form-select">
                                    <option value="English (UK)">{{ __('English (UK)') }}</option>
                                    <option value="Multilingual (English and Spanish)">
                                        {{ __('Multilingual (English and Spanish)') }}</option>
                                    <option value="Spanish (Latin America)">{{ __('Spanish (Latin America)') }}</option>
                                    <option value="Spanish (Spain)">{{ __('Spanish (Spain)') }}</option>
                                    <option value="English (India)">{{ __('English (India)') }}</option>
                                    <option value="French">{{ __('French') }}</option>
                                    <option value="Chinese">{{ __('Chinese') }}</option>
                                    <option value="German">{{ __('German') }}</option>
                                    <option value="Hindi">{{ __('Hindi') }}</option>
                                    <option value="Japanese">{{ __('Japanese') }}</option>
                                    <option value="Portuguese (Portugal)">{{ __('Portuguese (Portugal)') }}</option>
                                    <option value="Portuguese (Brazil)">{{ __('Portuguese (Brazil)') }}</option>
                                    <option value="Russian">{{ __('Russian') }}</option>
                                    <option value="Italian">{{ __('Italian') }}</option>
                                    <option value="Korean">{{ __('Korean') }}</option>
                                    <option value="Dutch">{{ __('Dutch') }}</option>
                                    <option value="Polish">{{ __('Polish') }}</option>
                                    <option value="Turkish">{{ __('Turkish') }}</option>
                                    <option value="Vietnamese">{{ __('Vietnamese') }}</option>
                                    <option value="Romanian">{{ __('Romanian') }}</option>

                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="agent-prompt" class="form-label fs-14 text-dark">{{ __('Enter prompt') }}
                                    <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="agent_prompt" id="agent-prompt" rows="5"
                                    placeholder="{{ __('Enter the prompt/instruction for the AI agent to interact with or ask something from your employees.') }}"></textarea>
                                <small class="text-muted mt-2">{{ __('Kindly type your prompt in english.') }}</small>
                            </div>
                            <div class="mb-3 d-flex justify-content-center">
                                <div class="form-check form-check-md form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="enable-deepfake">
                                    <label class="form-check-label"
                                        for="enable-deepfake">{{ __('Enable Deepfake (optional)') }}</label>
                                </div>
                                <div>
                                    <small class="text-muted"></small>
                                </div>
                            </div>
                            <div class="mb-3" style="display: none;" id="deepfake-audio">
                                <label for="formFileSm"
                                    class="form-label">{{ __('Select your voice audio (.mp3|.aac|.wav)') }}</label>
                                <input class="form-control form-control-sm" id="formFileSm" name="deepfake_audio"
                                    type="file">
                            </div>
                            <div class="mb-3 text-end">
                                <button type="submit" class="btn btn-primary btn-wave">{{ __('Submit') }}</button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- campaign view modal --}}

    <div class="modal fade" id="viewCampaignModal" tabindex="-1" aria-labelledby="exampleModalScrollable2"
        data-bs-keyboard="false" aria-hidden="true">
        <!-- Scrollable modal -->
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title" id="staticBackdropLabel2">{{ __('Campaign Detail') }}
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table text-nowrap">
                            <thead>
                                <tr>
                                    <th scope="col">{{ __('Campaign Name') }}</th>
                                    <th scope="col">{{ __('Employee Group') }}</th>
                                    <th scope="col">{{ __('AI Agent') }}</th>
                                    <th scope="col">{{ __('Training') }}</th>
                                    <th scope="col">{{ __('Phone No.') }}</th>
                                    <th scope="col">{{ __('Status') }}</th>

                                </tr>
                            </thead>
                            <tbody id="campdetail">

                            </tbody>
                        </table>
                    </div>

                    <h5 class="mt-3">{{ __('Target Employees') }}</h5>
                    <div class="table-responsive">
                        <table class="table text-nowrap">
                            <thead>
                                <tr>
                                    <th scope="col">{{ __('Employee') }}</th>
                                    <th scope="col">{{ __('Mobile No.') }}</th>
                                    <th scope="col">{{ __('Call ID') }}</th>
                                    <th scope="col">{{ __('Training Assigned') }}</th>
                                    <th scope="col">{{ __('Status') }}</th>
                                    <th scope="col">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody id="campemp">

                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>



    {{-- -------------------Modals------------------------ --}}


    {{-- ------------------------------Toasts---------------------- --}}

    <x-toast />

    {{-- ------------------------------Toasts---------------------- --}}


    @push('newcss')
        <style>
            .htmlPhishingGrid {
                overflow: scroll;
                border: 1px solid #8080804a;
                border-radius: 6px;
                max-height: 300px;
                padding: 10px !important;
            }

            .htmlPhishingGrid img {
                width: 100%;
            }
        </style>
    @endpush

    @push('newscripts')
        <script>
            $('#emp_group').change(function() {
                var emp_group = $('#emp_group option:selected').text().trim();
                // console.log(emp_group);
                $('#emp_group_name').val(emp_group);
            })

            $('#ai_agent').change(function() {
                var ai_agent = $('#ai_agent option:selected').text().trim();
                // console.log(emp_group);
                $('#ai_agent_name').val(ai_agent);
            })

            function deleteCamp(id) {
                Swal.fire({
                    title: "{{ __('Are you sure?') }}",
                    text: "{{ __('Are you sure that you want to delete this Campaign?') }}",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e6533c',
                    cancelButtonColor: '#d33',
                    confirmButtonText: "{{ __('Delete') }}",
                    cancelButtonText: "{{ __('Cancel') }}"
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post({
                            url: '/ai-calling/delete-campaign',
                            data: {
                                camp: id
                            },
                            success: function(res) {

                                // console.log(res)
                                window.location.href = window.location.href;
                            }
                        })
                    }
                })
            }

            function viewCamp(id) {
                $.get({
                    url: '/ai-calling/view-campaign/' + id,
                    success: function(response) {
                        console.log(response)


                        var camprow = `<tr>
                                    <th scope="row">${response.campaign_name}</th>
                                    <td>${response.emp_grp_name}</td>
                                    <td>${response.ai_agent_name}</td>
                                    <td id="assigned_training_name">${response.training_name?.name ?? '<em>{{ __('Only Phishing') }}</em>'}</td>
                                    <td>
                                        <span class="badge bg-outline-primary">${response.phone_no}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-${(response.status === 'pending') ? 'warning' : 'success'}">${(capitalizeFirstLetter(response.status))}</span>
                                    </td>
                                </tr>`;

                        $('#campdetail').html(camprow);


                        if (response.individual_camps.length !== 0) {

                            var row = "";

                            response.individual_camps.forEach(element => {
                                row += ` <tr>
                                    <th scope="row">${element.employee_name}</th>
                                    <td>${element.to_mobile}</td>
                                    <td>
                                        <p class="font-monospace mb-0">${element.call_id}</p>
                                    </td>
                                    <td>
                                        <span class="badge bg-outline-${element.training_assigned == 1 ? 'success' : 'danger'}">${element.training_assigned == 1 ? "{{ __('Yes') }}" : "{{ __('No') }}"}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-${(element.status === 'waiting' || element.status === 'pending') ? 'warning' : 'success'}">${capitalizeFirstLetter(element.status)}</span>
                                    </td>
                                    <td>
                                        <button class="btn btn-icon btn-success-transparent rounded-pill btn-wave"
                                            data-bs-toggle="offcanvas" data-bs-target="#callDetailOffCanvas" onclick="fetchCallDetail('${element.call_id}', '${element.training_assigned == 1 ? element.training : 'null'}')">
                                            <i class="ri-eye-line"></i>
                                        </button>
                                    </td>
                                </tr>`;



                            });

                            $('#campemp').html(row);



                        }

                    }
                })
            }

            function fetchCallDetail(callid, training) {
                // console.log(callid)
                if ($("#translateBtn").length) {
                    $("#translateBtn").show();
                }
                $("#placeholder").show();
                $.get({
                    url: "/ai-calling/fetch-call-report/" + callid,
                    success: function(res) {
                        let fell_in_simulation = false;
                        if (res.transcript_with_tool_calls) {

                            res.transcript_with_tool_calls.forEach(element => {
                                if (element.role == 'tool_call_invocation') {
                                    const func = JSON.parse(element.arguments);
                                    if (func.fell_for_simulation) {
                                        fell_in_simulation = true;
                                    }
                                }
                            })
                        }

                        var conversation = '';
                        if (res.transcript_object) {
                            res.transcript_object.forEach(element => {
                                conversation += `<div class="d-flex justify-content-between mb-2 border-bottom">
                                                    <div>
                                                        ${element.role == 'agent' ? '<strong>Agent</strong>' : '<strong>User</strong>'}: ${element.content}
                                                    </div>
                                                   
                                                </div>`;
                            })
                        } else {
                            conversation = '<p class="text-muted">No transcription available.</p>'
                        }

                        var detail = `<div>
                <h6>${formatedDate(res.start_timestamp)} ${res.call_type}</h6>
                <div>
                    <ul class="list-unstyled">
                        <li>Agent ID: ${res.agent_id}</li>
                        <li>Call ID: ${res.call_id}</li>
                        <li>Call Status: ${res.call_status == 'error' ? '<span class="badge bg-outline-danger">Error</span>' : '<span class="badge bg-outline-success">Ended</span>'}</li>
                        <li>Duration: ${getduration(res.duration_ms) }</li>
                    </ul>

                </div>
                <div>

                    ${(res.call_status == 'ended' && !fell_in_simulation) ? `<audio id="recording_audio" controls='' class='h-11 w-[258px]'
                                                                                                                        src='${res.recording_url}'>Your
                                                                                                                        browser does not support the audio element.</audio>` : ''}
                
                    
                </div>
                <hr>
                <div>
                    <p class="fw-bold mb-0">Conversation Analysis</p>
                    <p class="text-muted mt-0">Present</p>
                </div>
                <div>
                    <table class="w-100">
                        <tbody>
                            <tr>
                                <td>
                                    <i class="ri-phone-line fs-16"></i>
                                    Disconnection Reason
                                </td>
                                <td>${res.disconnection_reason} </td>
                            </tr>
                            <tr>
                                <td>
                                    <i class="ri-presentation-line fs-16"></i>
                                    Training Assigned
                                </td>
                                <td>
                                    <span class="badge bg-${training !== 'null' ? 'success' : 'danger'}">${training !== 'null' ? $('#assigned_training_name').text() : 'No'}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <hr>
                <div class="mt-3">
                    <p class="fw-bold mb-1">Summary</p>
                </div>
                <div>
                    <p class="text-muted">
                        ${res.call_analysis?.call_summary}
                    </p>
                </div>
                <hr>
                <div class="mt-3">
                    <p class="fw-bold mb-3">Transcription</p>
                </div>
                <div>
                   ${conversation}
                </div>

            </div>`;

                        $("#placeholder").hide();
                        $("#call_detail").html(detail);

                    }
                })
            }

            function formatedDate(timestamp) {

                const date = new Date(timestamp);

                // Format the date as MM/DD/YYYY HH:MM
                const formattedDate = date.toLocaleString("en-US", {
                    month: "2-digit",
                    day: "2-digit",
                    year: "numeric",
                    hour: "2-digit",
                    minute: "2-digit",
                    hour12: false // Use 24-hour format
                });

                return formattedDate; // Output: "10/19/2024, 20:26"

            }

            function getduration(milliseconds) {
                const seconds = milliseconds / 1000;

                if (seconds < 60) {
                    return `${Math.round(seconds)} seconds`;
                } else {
                    const minutes = seconds / 60;
                    return `${Math.round(minutes)} minutes`;
                }
            }

            function capitalizeFirstLetter(val) {
                return String(val).charAt(0).toUpperCase() + String(val).slice(1);
            }
        </script>

        <script>
            document.getElementById("phishing-trigger").onclick = () => {
                const campaignName = document.getElementById("campaignName").value;
                const emp_group = document.getElementById("emp_group").value;
                if (campaignName == '') {
                    alert("Please Enter Campaign Name");
                    return;
                }
                if (!emp_group) {
                    alert("Please Select Employee Group");
                    return;
                }

                document.getElementById("phishing-tab").click()
            }

            document.getElementById("phone-trigger").onclick = () => {

                const phishingOnly = document.getElementById("phishing_only").value;

                if (phishingOnly === "false") {

                    // Assuming your radio buttons have the name attribute "myRadioGroup"
                    const radios = document.querySelectorAll('input[name="training_module"]');
                    let isChecked = false;

                    radios.forEach(radio => {
                        if (radio.checked) {
                            isChecked = true;
                        }
                    });

                    if (!isChecked) {
                        alert("Please select training.");
                        return;
                    }


                }




                document.getElementById("phone-tab").click()
            }

            document.getElementById("back-to-campaign").onclick = () => {
                document.getElementById("campaign-tab").click()
            }

            document.getElementById("back-to-phishing").onclick = () => {
                document.getElementById("phishing-tab").click()
            }


            document.getElementById("submit_campaign").onclick = () => {
                const ai_agent = document.getElementById("ai_agent").value;
                const ai_phones = document.getElementById("ai_phones").value;
                if (!ai_agent) {
                    alert("Please Select AI Agent");
                    return;
                }

                if (!ai_phones) {
                    alert("Please Select Phone Number");
                    return;
                }

                const campaignForm = document.getElementById("campaignDetail");
                campaignForm.submit();
            }
        </script>

        <script>
            // Event listener for input field change
            $('#t_moduleSearch').on('input', function() {
                var searchValue = $(this).val().toLowerCase(); // Get the search value and convert it to lowercase

                // Loop through each template card
                $('.t_modules').each(function() {
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

            document.getElementById("phishing_only").addEventListener("change", function() {
                const trainingGrids = document.getElementById("training_grids");
                const notemsg = document.getElementById("notemsg");
                if (this.checked) {
                    trainingGrids.style.display = "none"; // Hide the div
                    notemsg.style.display = "block"; // Hide the div
                    this.value = "true";
                } else {
                    trainingGrids.style.display = "block"; // Show the div
                    notemsg.style.display = "none";
                    this.value = "false";
                }
            });

            const offcanvasElement = document.getElementById('callDetailOffCanvas');

            offcanvasElement.addEventListener('hidden.bs.offcanvas', function() {
                // Your function to call when the offcanvas is closed
                stopAudio();
            });

            function stopAudio() {
                const audioElement = document.getElementById('recording_audio');
                audioElement.src = "";
            }
        </script>

        <script>
            $("#enable-deepfake").change(function() {
                if (this.checked) {
                    $("#deepfake-audio").slideDown();
                } else {
                    $("#deepfake-audio").slideUp();
                }
            });
        </script>
        <script>
            function translateCallDetail(btn, lang) {
                $(btn).text('Translating...').addClass('disabled');
                const html = $('#call_detail').html();
                $.post({
                    url: '/ai-calling/translate-call-detail',
                    data: {
                        html,
                        lang
                    },
                    success: function(res) {

                        if(res.status == 1){
                            $("#call_detail").html(res.html);
                            $(btn).text('Translate').removeClass('disabled');
                            $(btn).hide();
                        }else{
                            Swal.fire({
                                icon: 'error',
                                title: "{{ __('Error') }}",
                                text: "{{ __('Something went wrong:') }}",
                            })
                            $(btn).text('Translate').removeClass('disabled');
                        }
                    }
                })
            }
        </script>
    @endpush

@endsection
