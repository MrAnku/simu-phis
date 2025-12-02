<?php

namespace App\Http\Controllers\Api;

use App\Models\Users;
use Illuminate\Http\Request;
use App\Models\CompanyBranding;
use App\Models\WhiteLabelledSmtp;
use App\Http\Controllers\Controller;
use App\Models\WhiteLabelledCompany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Models\WhiteLabelledWhatsappConfig;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Mailer\Transport\Smtp\SmtpTransport;

class ApiWhiteLabelController extends Controller
{
    public function check(Request $request)
    {
        $host = $request->query('host');

        $companyWhitelabeled = WhiteLabelledCompany::where(function ($query) use ($host) {
            $query->where('domain', $host)
                ->orWhere('learn_domain', $host);
        })
            ->where('approved_by_partner', 1)
            ->where('service_status', 1)
            ->first();

        $companyLogoLight = "/assets/images/simu-logo.png";
        $companyLogoDark =  "/assets/images/simu-logo-dark.png";
        $companyFavicon = "/assets/images/simu-icon.png";
        $companyName = env('APP_NAME');
        $companyDomain = env('NEXT_APP_URL');
        $companyLearnDomain = env('SIMUPHISH_LEARNING_URL');
        $completelyWhitelabeled = false;


        if ($companyWhitelabeled) {

            $companyDomain = "https://" . $companyWhitelabeled->domain . "/";
            $companyLearnDomain = "https://" . $companyWhitelabeled->learn_domain . "/";

            //check branding
            $branding = CompanyBranding::where('company_id', $companyWhitelabeled->company_id)->first();
            if ($branding) {
                $companyLogoDark = $branding->dark_logo;
                $companyLogoLight = $branding->light_logo;
                $companyFavicon = $branding->favicon;
                $companyName = $branding->company_name;
                $completelyWhitelabeled = true;
            }
        }
        if (Auth::guard('api')->check()) {
            $authCompanyBranding = CompanyBranding::where('company_id', Auth::guard('api')->user()->company_id)->first();
            if ($authCompanyBranding) {
                $companyLogoDark = $authCompanyBranding->dark_logo;
                $companyLogoLight = $authCompanyBranding->light_logo;
                $companyFavicon = $authCompanyBranding->favicon;
                $companyName = $authCompanyBranding->company_name;
            }
        }

        if ($request->query('learner') && $completelyWhitelabeled == false) {
            $companyId = Users::where('user_email', $request->query('learner'))->value('company_id');
            if ($companyId) {
                $authCompanyBranding = CompanyBranding::where('company_id', $companyId)->first();
                if ($authCompanyBranding) {
                    $companyLogoDark = $authCompanyBranding->dark_logo;
                    $companyLogoLight = $authCompanyBranding->light_logo;
                    $companyFavicon = $authCompanyBranding->favicon;
                    $companyName = $authCompanyBranding->company_name;
                }
            }
        }



        // Share branding information with all views
        return response()->json([
            'companyLogoDark' => env('CLOUDFRONT_URL') . $companyLogoDark,
            'companyLogoLight' => env('CLOUDFRONT_URL') . $companyLogoLight,
            'companyFavicon' => env('CLOUDFRONT_URL') . $companyFavicon,
            'companyName' => $companyName,
            'companyDomain' => $companyDomain,
            'companyLearnDomain' => $companyLearnDomain
        ]);
    }
    
    public function saveWhiteLabel(Request $request)
    {
        try {
            $request->validate([
                // 'company_name' => 'required|string|max:255',
                'company_email' => 'required|email',
                'domain' => 'required|string',
                'learn_domain' => 'required|string',
                // 'dark_logo' => 'required|mimes:png',
                // 'light_logo' => 'required|mimes:png',
                // 'favicon' => 'required|mimes:png',
                'managed_smtp' => 'required|boolean',
                'from_address' => 'required',
                'from_name' => 'required|string|max:255',
                'is_default_wa_config' => 'required|boolean',
            ]);
            if ($request->managed_smtp == false) {
                $request->validate([
                    'smtp_host' => 'required|string|max:255',
                    'smtp_port' => 'required|integer|in:465,587',
                    'smtp_username' => 'required|string|max:255',
                    'smtp_password' => 'required|string|max:255',
                    'smtp_encryption' => 'nullable|string|in:tls,ssl',
                ]);
            } else {
                // Set default SMTP credentials from environment variables
                $request->merge([
                    'smtp_host' => env('MAIL_HOST'),
                    'smtp_port' => env('MAIL_PORT'),
                    'smtp_username' => env('MAIL_USERNAME'),
                    'smtp_password' => env('MAIL_PASSWORD'),
                    'smtp_encryption' => env('MAIL_ENCRYPTION'),
                ]);
            }

            if ($request->is_default_wa_config == false) {
                $request->validate([
                    'from_phone_id' => 'required|integer',
                    'access_token' => 'required|string',
                    'business_id' => 'required|integer',
                ]);
            }

            $whiteLabelExists = WhiteLabelledCompany::where('company_id', Auth::user()->company_id)->exists();
            if ($whiteLabelExists) {
                return response()->json([
                    'success' => false,
                    'message' => __('White Label already added for your company.')
                ], 422);
            }

            $domainExists = WhiteLabelledCompany::where('domain', $request->domain)->exists();
            if ($domainExists) {
                return response()->json([
                    'success' => false,
                    'message' => __('Domain already added by another company.')
                ], 422);
            }

            $learnDomainExists = WhiteLabelledCompany::where('learn_domain', $request->learn_domain)->exists();
            if ($learnDomainExists) {
                return response()->json([
                    'success' => false,
                    'message' => __('Learn domain already added by another company.')
                ], 422);
            }

            $smtpUsernameExists = WhiteLabelledSmtp::where('from_address', $request->from_address)->exists();
            if ($smtpUsernameExists) {
                return response()->json([
                    'success' => false,
                    'message' => __('SMTP from address already added by another company.')
                ], 422);
            }

            // Check SMTP connection is managed_smtp is false
            if ($request->managed_smtp == false) {
                $smtpCredentials = [
                    'smtp_host' => $request->smtp_host,
                    'smtp_port' => $request->smtp_port,
                    'smtp_username' => $request->smtp_username,
                    'smtp_password' => $request->smtp_password,
                    'smtp_encryption' => $request->smtp_encryption,
                    'from_address' => $request->from_address,
                    'from_name' => $request->from_name,
                ];
                if (!$this->checkSmtpConnection($smtpCredentials)) {
                    return response()->json([
                        'success' => false,
                        'message' => __('Invalid SMTP credentials.')
                    ], 422);
                }
            }


            $companyId = Auth::user()->company_id;

            // $randomName = generateRandom(10);
            // $extension = $request->file('dark_logo')->getClientOriginalExtension();
            // $darkLogoFilename = $randomName . '.' . $extension;
            // $darkLogoPath = $request->file('dark_logo')->storeAs("whiteLabel/{$companyId}", $darkLogoFilename, 's3');

            // $randomName = generateRandom(10);
            // $extension = $request->file('light_logo')->getClientOriginalExtension();
            // $lightLogoFilename = $randomName . '.' . $extension;

            // $lightLogoPath = $request->file('light_logo')->storeAs("whiteLabel/{$companyId}", $lightLogoFilename, 's3');

            // $randomName = generateRandom(10);
            // $extension = $request->file('favicon')->getClientOriginalExtension();
            // $faviconLogoFilename = $randomName . '.' . $extension;

            // $faviconLogoPath = $request->file('favicon')->storeAs("whiteLabel/{$companyId}", $faviconLogoFilename, 's3');

            $isCreatedWhitLabel = WhiteLabelledCompany::create([
                'company_id' => Auth::user()->company_id,
                'partner_id' => Auth::user()->partner_id,
                'company_email' => $request->company_email,
                'domain' => $request->domain,
                'learn_domain' => $request->learn_domain,
                // 'dark_logo' => "/" . $darkLogoPath,
                // 'light_logo' => "/" . $lightLogoPath,
                // 'favicon' => "/" . $faviconLogoPath,
                // 'company_name' => $request->company_name,
                'managed_smtp' => $request->managed_smtp,
            ]);

            if ($isCreatedWhitLabel) {
                WhiteLabelledSmtp::create([
                    'smtp_host' => $request->smtp_host,
                    'smtp_port' => $request->smtp_port,
                    'smtp_username' => $request->smtp_username,
                    'smtp_password' => $request->smtp_password,
                    'smtp_encryption' => $request->smtp_encryption,
                    'from_address' => $request->from_address,
                    'from_name' => $request->from_name,
                    'company_id' => Auth::user()->company_id,
                ]);

                if ($request->is_default_wa_config == false) {

                    WhiteLabelledWhatsappConfig::create([
                        'from_phone_id' => $request->from_phone_id,
                        'access_token' => $request->access_token,
                        'business_id' => $request->business_id,
                        'company_id' => Auth::user()->company_id,
                    ]);
                }

                log_action("White label request submitted for the company : " . Auth::user()->company_name);
                return response()->json([
                    'success' => true,
                    'message' => __('Whitelabelling request submitted successfully.')
                ], 201);
            }
        } catch (ValidationException $e) {
            // Handle the validation exception
            return response()->json([
                'success' => false,
                'message' => __('Validation error: ') . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'success' => false,
                'message' => __('An error occurred: ') . $e->getMessage()
            ], 500);
        }
    }

    //private method to test the smtp connection to check the SMTP credentials
    private function checkSmtpConnection($credentials)
    {
        try {
            // Temporarily override mail config
            $backup = config('mail.mailers.smtp');

            config([
                'mail.mailers.smtp.host' => $credentials['smtp_host'],
                'mail.mailers.smtp.port' => $credentials['smtp_port'],
                'mail.mailers.smtp.username' => $credentials['smtp_username'],
                'mail.mailers.smtp.password' => $credentials['smtp_password'],
                'mail.mailers.smtp.encryption' => $credentials['smtp_encryption'],
                'mail.from.address' => $credentials['from_address'],
                'mail.from.name' => $credentials['from_name'],
            ]);

            Mail::raw('This is a test email.', function ($message) use ($credentials) {
                $message->from($credentials['from_address'], $credentials['from_name']);
                $message->to("test@yopmail.com");
                $message->subject('Test Email');
            });

            // Restore original config
            config(['mail.mailers.smtp' => $backup]);

            return true;
        } catch (\Exception $e) {
            // Restore original config in case of error
            if (isset($backup)) {
                config(['mail.mailers.smtp' => $backup]);
            }
            return false;
        }
    }
}
