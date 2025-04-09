<div class="card px-0 pt-4 pb-0 mt-3 mb-3">
    <div class="row">
        <div class="col-md-12 mx-0">
            <form id="newCampaignForm" action="" method="post">
                <!-- progressbar -->
                <ul id="progressbar">
                    <li class="active">
                        <i class='bx bx-cog'></i>
                        <strong>{{ __('Initial Setup & Employee Selection') }}</strong>
                    </li>
                    <li id="pm_step">
                        <i class='bx bx-mail-send'></i>
                        <strong>{{ __('Select Phishing Material') }}</strong>
                    </li>
                    <li id="tm_step">
                        <i class='bx bx-mail-send'></i>
                        <strong>{{ __('Select Training Modules') }}</strong>
                    </li>
                    <li>
                        <i class='bx bx-time-five'></i>
                        <strong>{{ __('Set Delivery Schedule') }}</strong>
                    </li>
                    <li>
                        <i class='bx bx-check-square'></i>
                        <strong>{{ __('Review & Submit') }}</strong>
                    </li>
                </ul>
                <!-- fieldsets -->
                <fieldset class="included">
                    <x-campaign.step-campaign-first :usersGroups="$usersGroups" />
                    <x-campaign.next-button label="{{ __('Next') }}" class="next" />
                </fieldset>

                <fieldset class="included" id="pm_step_form">
                    <x-campaign.previous-button label="{{ __('Previous') }}" class="previous" />
                    <x-campaign.next-button label="{{ __('Next') }}" class="next" />
                    <x-campaign.step-phishing :phishingEmails="$phishingEmails" />
                </fieldset>

                <fieldset class="included" id="tm_step_form">
                    <x-campaign.previous-button label="{{ __('Previous') }}" class="previous" />
                    <x-campaign.next-button label="{{ __('Next') }}" class="next" />
                    <x-campaign.step-training :trainingModules="$trainingModules" />
                </fieldset>

                <fieldset class="included">
                    <x-campaign.schedule-campaign />
                    <x-campaign.previous-button label="{{ __('Previous') }}" class="previous" />
                    <x-campaign.next-button label="{{ __('Next') }}" class="next last-step" />
                </fieldset>

                <fieldset class="included">
                    <x-campaign.review-campaign />
                    <x-campaign.previous-button label="{{ __('Previous') }}" class="previous" />
                    <x-campaign.next-button label="{{ __('Submit') }}" id="createCampaign" />
                </fieldset>
            </form>
        </div>
    </div>
</div>