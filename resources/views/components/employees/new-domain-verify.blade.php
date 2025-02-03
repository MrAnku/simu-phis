<form action="" id="sendOtpForm" method="post">

    <p class="text-muted">Domain verification is performed through challenge-response authentication of
        the provided email address.(e.g. verifying support@mybusiness.com will enable mybusiness.com.)
    </p>
    <div>
        <label for="input-label" class="form-label">Email Address<sup
                class="text-danger">*</sup></label>
        <input type="text" class="form-control" name="verificationEmail">
    </div>
    <button type="submit" id="sendOtpBtn"
        class="btn btn-primary my-3 btn-wave waves-effect waves-light">Send Verification Email</button>
    <button class="btn btn-primary my-3 btn-loader d-none" id="otpSpinner">
        <span class="me-2">Please wait...</span>
        <span class="loading"><i class="ri-loader-2-fill fs-16"></i></span>
    </button>
    <p class="text-muted">Haven't received the verification code? Try generating another verification
        email.</p>
</form>

<div id="enterOtpContainer" class="d-none">
    <form action="" id="otpSubmitForm" method="post">
        <div class="d-flex align-items-end justify-content-center">
            <div>
                <label for="input-label" class="form-label">Enter OTP</label>
                <input type="text" class="form-control" name="emailOTP" placeholder="xxxxxx">
            </div>
            <button type="submit" id="otpSubmitBtn"
                class="btn btn-primary mx-3 btn-wave waves-effect waves-light">Submit</button>
            <button class="btn btn-primary mx-3 btn-loader d-none" id="otpSubmitSpinner">
                <span class="me-2">Please wait...</span>
                <span class="loading"><i class="ri-loader-2-fill fs-16"></i></span>
            </button>
        </div>
    </form>
</div>