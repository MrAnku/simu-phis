<div class="card-body">
    <ul class="nav nav-tabs tab-style-2 nav-justified mb-3 d-sm-flex d-block" id="myTab1"
        role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="order-tab" data-bs-toggle="tab"
                data-bs-target="#order-tab-pane" type="button" role="tab"
                aria-controls="home-tab-pane" aria-selected="true"><i
                    class="ri-copyleft-line me-1 align-middle"></i>LDAP AD</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="confirmed-tab" data-bs-toggle="tab"
                data-bs-target="#confirm-tab-pane" type="button" role="tab"
                aria-controls="profile-tab-pane" aria-selected="false"><i
                    class="ri-windows-line me-1 align-middle"></i>Outlook/Azure</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="shipped-tab" data-bs-toggle="tab"
                data-bs-target="#shipped-tab-pane" type="button" role="tab"
                aria-controls="contact-tab-pane" aria-selected="false"><i
                    class="ri-google-line me-1 align-middle"></i>Google Workspace</button>
        </li>
    </ul>
    <div class="tab-content" id="myTabContent">
        <div class="tab-pane fade show active" id="order-tab-pane" role="tabpanel"
            aria-labelledby="home-tab" tabindex="0">

            <div id="ldapConfig">
                <form class="text-center" id="ldapConfigForm" method="post"
                    action="{{ route('employee.save.ldap.config') }}">
                    @csrf
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="row mb-3">
                                <label for="ldap_host" class="col-sm-2 col-form-label">LDAP
                                    Host</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" id="ldap_host"
                                        name="ldap_host">
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="row mb-3">
                                <label for="ldap_dn" class="col-sm-2 col-form-label">LDAP DN</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" id="ldap_dn"
                                        name="ldap_dn">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-6">
                            <div class="row mb-3">
                                <label for="ldap_admin" class="col-sm-2 col-form-label">Admin
                                    Username</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" id="ldap_admin"
                                        name="ldap_admin">
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="row mb-3">
                                <label for="ldap_pass" class="col-sm-2 col-form-label">Admin
                                    Password</label>
                                <div class="col-sm-10">
                                    <input type="password" class="form-control" id="ldap_pass"
                                        name="ldap_pass">
                                </div>
                            </div>
                        </div>
                    </div>

                    <button class="btn btn-info label-btn rounded-pill" id="edit_ldap_config">
                        <i class="ri-edit-line label-btn-icon me-2 rounded-pill"></i>
                        Edit
                    </button>

                    <button id="save_ldap_config" type="submit"
                        class="btn btn-success label-btn rounded-pill">
                        <i class="ri-save-3-line label-btn-icon me-2 rounded-pill"></i>
                        Save
                    </button>

                    <button id="add_ldap_config" type="submit"
                        class="btn btn-success label-btn rounded-pill">
                        <i class="ri-save-3-line label-btn-icon me-2 rounded-pill"></i>
                        Add
                    </button>



                </form>
            </div>
        </div>
        <div class="tab-pane fade text-muted" id="confirm-tab-pane" role="tabpanel"
            aria-labelledby="profile-tab" tabindex="0">
            <ul class="ps-3 mb-0">
                <li>As opposed to using 'Content here, content here', making it look like
                    readable English. Many desktop publishing packages and web page editors
                    now use Lorem Ipsum as their default model text, and a search.</li>
            </ul>
        </div>
        <div class="tab-pane fade text-muted" id="shipped-tab-pane" role="tabpanel"
            aria-labelledby="contact-tab" tabindex="0">
            <ul class="ps-3 mb-0">
                <li>but also the leap into electronic typesetting, remaining essentially
                    unchanged. It was popularised in the 1960s with the release of Letraset
                    sheets containing Lorem Ipsum passages, and more recently.</li>
            </ul>
        </div>
        <div class="tab-pane fade text-muted" id="delivered-tab-pane" role="tabpanel"
            tabindex="0">
            <ul class="list-unstyled mb-0">
                <li>A Latin professor at Hampden-Sydney College in Virginia, looked up one
                    of the more obscure Latin words, consectetur, from a Lorem Ipsum
                    passage, and going through the cites of the word in classical
                    literature.</li>
            </ul>
        </div>
    </div>
</div>