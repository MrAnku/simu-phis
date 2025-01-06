<div>
    <div class="input-group mb-3">
        <span class="input-group-text" id="basic-addon1">Description</span>
        <input type="text" id="prompt" class="form-control" placeholder="Generate a invoice mail template of company">
        <button class="btn btn-primary" onclick="generateTemplate(this)" type="button" id="button-addon2">Generate
            Template</button>
    </div>
    

    <div id="aiTempContainer" style="display: none;">
        <div>
            <p class="text-muted">Please add the shortcodes <code>@{{user_name}}</code>(optional), <code>@{{website_url}}</code> i.e. href="@{{website_url}}" and <code>@{{tracker_img}}</code> anywhere</p>
        </div>
        <div>
            <textarea id="email-editor"></textarea>
        </div>
        <div class="d-flex justify-content-end mt-3">
            <button class="btn btn-primary" onclick="showAnotherModal(this)" type="button">Save template</button>

            
        </div>
    </div>

</div>
