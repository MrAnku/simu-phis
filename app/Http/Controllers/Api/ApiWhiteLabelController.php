<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\WhiteLabelledSmtp;
use App\Http\Controllers\Controller;
use App\Models\WhiteLabelledCompany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

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
            ]);

            $whiteLabelExists = WhiteLabelledCompany::where('company_id', Auth::user()->company_id)->exists();
            if($whiteLabelExists){
                   return response()->json([
                    'status' => false,
                    'message' => 'White Label already added for your company.'
                ], 422);
            }

            $domainExists = WhiteLabelledCompany::where('domain', $request->domain)->exists();
            if ($domainExists) {
                return response()->json([
                    'status' => false,
                    'message' => 'Domain already added by another company.'
                ], 422);
            }

            $learnDomainExists = WhiteLabelledCompany::where('learn_domain', $request->learn_domain)->exists();
            if ($learnDomainExists) {
                return response()->json([
                    'status' => false,
                    'message' => 'Learn domain already added by another company.'
                ], 422);
            }

            $smtpUsernameExists = WhiteLabelledSmtp::where('smtp_username', $request->smtp_username)->exists();
            if ($smtpUsernameExists) {
                return response()->json([
                    'status' => false,
                    'message' => 'SMTP username already added by another company.'
                ], 422);
            }

            $companyId = Auth::user()->company_id;

            $darkLogoPath = $request->file('dark_logo')->storeAs("whiteLabel/{$companyId}", $request->file('dark_logo')->getClientOriginalName(), 's3');
            $darkLogoUrl = Storage::disk('s3')->url($darkLogoPath);

            $lightLogoPath = $request->file('light_logo')->storeAs("whiteLabel/{$companyId}", $request->file('light_logo')->getClientOriginalName(), 's3');
            $lightLogoUrl = Storage::disk('s3')->url($lightLogoPath);

            $faviconLogoPath = $request->file('favicon')->storeAs("whiteLabel/{$companyId}", $request->file('favicon')->getClientOriginalName(), 's3');
            $faviconLogoUrl = Storage::disk('s3')->url($faviconLogoPath);

            $isCreatedWhitLabel = WhiteLabelledCompany::create([
                'company_id' => Auth::user()->company_id,
                'partner_id' => Auth::user()->partner_id,
                'company_email' => $request->company_email,
                'domain' => $request->domain,
                'learn_domain' => $request->learn_domain,
                'dark_logo' => $darkLogoUrl,
                'light_logo' => $lightLogoUrl,
                'favicon' => $faviconLogoUrl,
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

                log_action("White label created for company : " . Auth::user()->company_name);
                return response()->json([
                    'status' => 'success',
                    'message' => __('White label created successfully.')
                ], 201);
            }
        } catch (ValidationException $e) {
            // Handle the validation exception
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error: ' . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }
}
