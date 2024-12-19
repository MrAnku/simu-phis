<div class="card-body">
    <ul class="nav nav-tabs mb-3 tab-style-6" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="products-tab" data-bs-toggle="tab"
                data-bs-target="#email-tab-pane" type="button" role="tab"
                aria-controls="email-tab-pane" aria-selected="true"><i
                    class="bx bx-envelope me-1 align-middle d-inline-block"></i>Email</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="sales-tab" data-bs-toggle="tab"
                data-bs-target="#website-tab-pane" type="button" role="tab"
                aria-controls="website-tab-pane" aria-selected="false" tabindex="-1"><i
                    class="bx bx-globe me-1 align-middle d-inline-block"></i>Website</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="profit-tab" data-bs-toggle="tab"
                data-bs-target="#senderp-tab-pane" type="button" role="tab"
                aria-controls="senderp-tab-pane" aria-selected="false" tabindex="-1"><i
                    class="bx bx-envelope me-1 align-middle d-inline-block"></i>Sender Profile</button>
        </li>

    </ul>
    <div class="tab-content" id="myTabContent2">
        <div class="tab-pane fade p-3 border-bottom-0 active show" id="email-tab-pane"
            role="tabpanel" aria-labelledby="products-tab" tabindex="0">

            <div class="row mb-3">
                <label for="vphishEmail" class="col-sm-6 col-form-label">Phishing Email</label>
                <div class="col-sm-6">
                    <input type="email" class="form-control" id="vphishEmail" disabled>
                </div>
            </div>
            <div class="row mb-3">
                <label for="vSub" class="col-sm-6 col-form-label">Email Subject</label>
                <div class="col-sm-6">
                    <input type="email" class="form-control" id="vSub" disabled>
                </div>
            </div>
            <div class="row mb-3">
                <label for="inputEmail3" class="col-sm-6 col-form-label">Employee Requirements</label>
                <div class="col-sm-6">
                    <input type="email" class="form-control" id="inputEmail3"
                        value="Email Address | Name" disabled>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-lg-12" id="phishPrev" style="background: white;">

                </div>
            </div>
        </div>
        <div class="tab-pane fade p-3" id="website-tab-pane" role="tabpanel"
            aria-labelledby="sales-tab" tabindex="0">

            <div class="row mb-3">
                <label for="vphishWeb" class="col-sm-6 col-form-label">Phishing Website</label>
                <div class="col-sm-6">
                    <input type="email" class="form-control" id="vphishWeb" disabled>
                </div>
            </div>
            <div class="row mb-3">
                <label for="vPhishUrl" class="col-sm-6 col-form-label">Website URL</label>
                <div class="col-sm-6">
                    <input type="text" class="form-control" id="vPhishUrl" disabled>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-lg-12" id="websitePrev" style="background: white;">
                    <iframe class="phishing-iframe" src="" style="height: 500px;">

                    </iframe>
                </div>
            </div>

        </div>
        <div class="tab-pane fade p-3" id="senderp-tab-pane" role="tabpanel"
            aria-labelledby="profit-tab" tabindex="0">

            <div class="row mb-3">
                <label for="vsenderProf" class="col-sm-6 col-form-label">Sender Profile</label>
                <div class="col-sm-6">
                    <input type="text" class="form-control" id="vsenderProf" disabled>
                </div>
            </div>
            <div class="row mb-3">
                <label for="vDispName" class="col-sm-6 col-form-label">Display Name & Address</label>
                <div class="col-sm-6">
                    <input type="text" class="form-control" id="vDispName" disabled>
                </div>
            </div>

        </div>
    </div>
</div>