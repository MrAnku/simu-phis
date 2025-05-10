<ul class="nav nav-tabs tab-style-2 nav-justified mb-3 d-sm-flex d-block" id="myTab1" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="camp-name" data-bs-toggle="tab" data-bs-target="#camp-name-pane" type="button" role="tab" aria-controls="camp-name-pane" aria-selected="true"><i class="ri-mail-line me-1 align-middle"></i>{{ __('Campaign Name') }}</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="quish-mat" data-bs-toggle="tab" data-bs-target="#quish-mat-pane" type="button" role="tab" aria-controls="quish-mat-pane" aria-selected="false" tabindex="-1" disabled="true"><i class="ri-file-list-line me-1 align-middle"></i>{{ __('Smishing Material') }}</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="web-mat" data-bs-toggle="tab" data-bs-target="#web-mat-pane" type="button" role="tab" aria-controls="web-mat-pane" aria-selected="false" tabindex="-1" disabled="true"><i class="ri-file-list-line me-1 align-middle"></i>{{ __('Phishing Website') }}</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="training-mod" data-bs-toggle="tab" data-bs-target="#training-mod-pane" type="button" role="tab" aria-controls="training-mod-pane" aria-selected="false" tabindex="-1" disabled="true"><i class="ri-presentation-line me-1 align-middle"></i>{{ __('Training Module') }}</button>
    </li>
    
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="review-sub-tab" data-bs-toggle="tab" data-bs-target="#review-sub-pane" type="button" role="tab" aria-selected="false" tabindex="-1" disabled="true"><i class="ri-check-double-line me-1 align-middle"></i>{{ __('Review and Submit') }}</button>
    </li>
</ul>

<div class="tab-content" id="myTabContent1">
    <div class="tab-pane fade show active text-muted" id="camp-name-pane" role="tabpanel" aria-labelledby="camp-name"
        tabindex="0">
        <x-smishing.step-one-form />
    </div>
    <div class="tab-pane fade text-muted" id="quish-mat-pane" role="tabpanel" aria-labelledby="quish-mat"
        tabindex="0">
        <x-smishing.step-two-form :templates="$templates" />
    </div>
    <div class="tab-pane fade text-muted" id="web-mat-pane" role="tabpanel" aria-labelledby="web-mat"
        tabindex="0">
        <x-smishing.step-website :websites="$websites" />
    </div>
    <div class="tab-pane fade text-muted" id="training-mod-pane" role="tabpanel" aria-labelledby="training-mod"
        tabindex="0">
        <x-smishing.step-three-form :trainingModules="$trainingModules" />
    </div>
    
    <div class="tab-pane fade text-muted" id="review-sub-pane" role="tabpanel" aria-labelledby="review-sub"
        tabindex="0">
        <x-smishing.step-four-form />
    </div>
</div>
