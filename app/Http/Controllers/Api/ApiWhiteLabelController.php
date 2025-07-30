<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\WhiteLabelledSmtp;
use App\Http\Controllers\Controller;
use App\Models\WhiteLabelledCompany;
use App\Models\WhiteLabelledWhatsappConfig;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Mailer\Transport\Smtp\SmtpTransport;

class ApiWhiteLabelController extends Controller
{
    public function saveWhiteLabel(Request $request)
    {
        try {
            $request->validate([
                'company_name' => 'required|string|max:255',
                'company_email' => 'required|email',
                'domain' => 'required|string',
                'learn_domain' => 'required|string',
                'dark_logo' => 'required|mimes:png',
                'light_logo' => 'required|mimes:png',
                'favicon' => 'required|mimes:png',
                'smtp_host' => 'required|string|max:255',
                'smtp_port' => 'required|integer',
                'smtp_username' => 'required|string|max:255',
                'smtp_password' => 'required|string|max:255',
                'smtp_encryption' => 'required|string',
                'from_address' => 'required',
                'from_name' => 'required|string|max:255',
                'is_default_wa_config' => 'required|boolean',
            ]);

            $whiteLabelExists = WhiteLabelledCompany::where('company_id', Auth::user()->company_id)->exists();
            if ($whiteLabelExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'White Label already added for your company.'
                ], 422);
            }

            $domainExists = WhiteLabelledCompany::where('domain', $request->domain)->exists();
            if ($domainExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Domain already added by another company.'
                ], 422);
            }

            $learnDomainExists = WhiteLabelledCompany::where('learn_domain', $request->learn_domain)->exists();
            if ($learnDomainExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Learn domain already added by another company.'
                ], 422);
            }

            $smtpUsernameExists = WhiteLabelledSmtp::where('smtp_username', $request->smtp_username)->exists();
            if ($smtpUsernameExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'SMTP username already added by another company.'
                ], 422);
            }

            // Check SMTP connection
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
                    'message' => 'Invalid SMTP credentials.'
                ], 422);
            }

            $companyId = Auth::user()->company_id;

            $randomName = generateRandom(10);
            $extension = $request->file('dark_logo')->getClientOriginalExtension();
            $darkLogoFilename = $randomName . '.' . $extension;
            $darkLogoPath = $request->file('dark_logo')->storeAs("whiteLabel/{$companyId}", $darkLogoFilename, 's3');

            $randomName = generateRandom(10);
            $extension = $request->file('light_logo')->getClientOriginalExtension();
            $lightLogoFilename = $randomName . '.' . $extension;

            $lightLogoPath = $request->file('light_logo')->storeAs("whiteLabel/{$companyId}", $lightLogoFilename, 's3');

            $randomName = generateRandom(10);
            $extension = $request->file('favicon')->getClientOriginalExtension();
            $faviconLogoFilename = $randomName . '.' . $extension;

            $faviconLogoPath = $request->file('favicon')->storeAs("whiteLabel/{$companyId}", $faviconLogoFilename, 's3');

            $isCreatedWhitLabel = WhiteLabelledCompany::create([
                'company_id' => Auth::user()->company_id,
                'partner_id' => Auth::user()->partner_id,
                'company_email' => $request->company_email,
                'domain' => $request->domain,
                'learn_domain' => $request->learn_domain,
                'dark_logo' => "/" . $darkLogoPath,
                'light_logo' => "/" . $lightLogoPath,
                'favicon' => "/" . $faviconLogoPath,
                'company_name' => $request->company_name,
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

                if($request->is_default_wa_config == false){
                    $request->validate([
                        'from_phone_id' => 'required|integer',
                        'access_token' => 'required|string',
                        'business_id' => 'required|integer',
                    ]);

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
                'message' => 'Validation error: ' . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
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
            $message->to($credentials['from_address']);
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
