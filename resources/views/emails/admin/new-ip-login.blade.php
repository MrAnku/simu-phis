<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>{{ env('APP_NAME') }} Login Alert</title>
</head>

<body style="margin:0; padding:0; font-family:Arial, sans-serif; background-color:#f9f9f9;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
        style="background:#f9f9f9; padding:20px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0"
                    style="background:#ffffff; border-radius:6px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,0.1);">
                    <tr>
                        <td style="background:#222; padding:20px;" align="center">
                            <img src="{{ env('CLOUDFRONT_URL') }}/assets/images/simu-logo.png"
                                alt="{{ env('APP_NAME') }}" style="height:60px;" />
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:30px;">
                            <h2 style="color:#222; margin:0 0 15px;">Your account has been accessed from a new IP
                                address</h2>
                            <p style="font-size:15px; line-height:22px; color:#444; margin:0 0 20px;">
                                Your <strong>{{ env('APP_NAME') }}</strong> account was recently accessed from a new IP
                                address.
                                If this was you, no further action is required.
                            </p>

                            <table width="100%" cellspacing="0" cellpadding="0" border="0"
                                style="background:#f5f5f5; border-radius:4px; margin-bottom:20px;">
                                <tr>
                                    <td style="padding:15px; font-size:14px; color:#333; line-height:20px;">
                                        <strong>Email:</strong> {{ $email }}<br />
                                        <strong>Time:</strong> {{ $time }}<br />
                                        <strong>IP Address:</strong> {{ $ip }}
                                    </td>
                                </tr>
                            </table>

                            <p style="font-size:15px; line-height:22px; color:#444; margin:0 0 15px;">
                                <strong>Not you?</strong> Please reset your password immediately and
                                enable two-factor authentication
                                to secure your account.
                            </p>

                            <p style="font-size:14px; line-height:20px; color:#666; margin:20px 0 0;">
                                Need help? Visit our
                                <a href="mailto:{{ 'support@' . env('APP_NAME') }}.com"
                                    style="color:#0066cc; text-decoration:none;">Support Center</a>.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#f0f0f0; padding:15px; text-align:center; font-size:12px; color:#888;">
                            Copyright © {{date('Y')}} {{ env('APP_NAME') }}. All rights reserved. <br /> <br />
                            UAE<br />
                            <a href="https://{{ env('APP_NAME') }}.com"
                                style="color:#0066cc; text-decoration:none;">www.{{ env('APP_NAME') }}.com</a>


                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
