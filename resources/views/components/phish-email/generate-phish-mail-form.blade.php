<div>
    <div class="input-group mb-3">
        <span class="input-group-text" id="basic-addon1">Description</span>
        <input type="text" id="prompt" class="form-control" placeholder="Generate a invoice mail template of company">
        <button class="btn btn-primary" onclick="generateTemplate(this)" type="button" id="button-addon2">Generate
            Template</button>
    </div>

    <div id="temp-suggestions" style="display: none;">
        <div >
            <p class="text-muted">Suggestions:</p>
        </div>
        <div class="row">
            <div class="col-lg-6">
                <button type="button" class="list-group-item text-muted list-group-item-action mb-3 border rounded py-2" aria-current="true">
                    <span class="avatar avatar-xs me-2 avatar-rounded">
                        <img src="https://cdn-icons-png.flaticon.com/512/4138/4138137.png" alt="img">
                    </span>
                    <em>Generate an email template of dropbox password reset</em>
                </button>
                <button type="button" class="list-group-item text-muted list-group-item-action mb-3 border rounded py-2">
                    <span class="avatar avatar-xs me-2 avatar-rounded">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/25/Microsoft_icon.svg/1024px-Microsoft_icon.svg.png" alt="img">
                    </span>
                    <em>Generate an email template for microsoft account verification</em>
                </button>
                <button type="button" class="list-group-item text-muted list-group-item-action mb-3 border rounded py-2">
                    <span class="avatar avatar-xs me-2 avatar-rounded">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/c/c1/Google_%22G%22_logo.svg/640px-Google_%22G%22_logo.svg.png" alt="img">
                    </span>
                    <em>Generate an email template for google password reset</em>
                </button>
            </div>
            <div class="col-lg-6">
                <button type="button" class="list-group-item text-muted list-group-item-action mb-3 border rounded py-2">
                    <span class="avatar avatar-xs me-2 avatar-rounded">
                        <img src="https://cdn4.iconfinder.com/data/icons/logos-and-brands/512/227_Netflix_logo-512.png" alt="img">
                    </span>
                    <em>Generate an email template for netflix subscription confirmation</em>
                </button>
                <button type="button" class="list-group-item text-muted list-group-item-action mb-3 border rounded py-2">
                    <span class="avatar avatar-xs me-2 avatar-rounded">
                        <img src="https://cdn-icons-png.flaticon.com/512/871/871976.png" alt="img">
                    </span>
                    <em>Generate an email template for event invitation</em>
                </button>
                <button type="button" class="list-group-item text-muted list-group-item-action mb-3 border rounded py-2">
                    <span class="avatar avatar-xs me-2 avatar-rounded">
                        <img src="https://static-00.iconduck.com/assets.00/amazon-icon-2048x1722-myhuicq8.png" alt="img">
                    </span>
                    <em>Generate an email template for amazon order confirmation</em>
                </button>
            </div>
        </div>
       

    </div>


    <div id="aiTempContainer" style="display: none;">
        <div>
            <p class="text-muted">Please add the shortcodes <code>@{{user_name}}</code>(optional),
                <code>@{{website_url}}</code> i.e. href="@{{website_url}}" and
                <code>@{{tracker_img}}</code> anywhere
            </p>
        </div>
        <div>
            <textarea id="email-editor"></textarea>
        </div>
        <div class="d-flex justify-content-end mt-3">
            <button class="btn btn-primary" onclick="showAnotherModal(this)" type="button">Save template</button>


        </div>
    </div>

</div>
