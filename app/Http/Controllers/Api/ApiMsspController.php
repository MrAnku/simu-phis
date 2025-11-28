<?php

namespace App\Http\Controllers\Api;

use App\Models\Company;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Services\AuthService;
use App\Models\CompanyLicense;
use Illuminate\Support\Facades\DB;
use App\Mail\CreateMsspCompanyMail;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class ApiMsspController extends Controller
{
    public function msspCompanies(Request $request)
    {
        if (Auth::user()->mssp_admin == 0) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'You are not authorized to access this resource.',
                ],
                403
            );
        }
        $msspCompanies = Company::where('mssp_id', Auth::user()->mssp_id)
            ->where('mssp_admin', 0)->get();
        return response()->json(
            [
                'success' => true,
                'data' => $msspCompanies,
            ]
        );
    }
    public function createCompany(Request $request)
    {
        try {


            if (Auth::user()->mssp_admin == 0) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'You are not authorized to access this resource.',
                    ],
                    403
                );
            }

            $request->validate([
                'email' => 'required|email|unique:company,email',
                'full_name' => 'required|string|max:255',
                'company_name' => 'required|string|max:255',
                'expiry' => 'required|date',
                'features' => 'required|array',
                'time_zone' => 'required|string',
                'storage_region' => 'required|string',
                'employees' => 'required|integer',
                'tprm_employees' => 'nullable|integer',
                'blue_collar_employees' => 'nullable|integer',
            ]);
            // check if the company has the the enough no of lincenses
            if(!$this->msspCompanyHasLicense($request)){
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'You do not have enough licenses to create a new company.',
                    ],
                    403
                );
            }

            //check if the requested company expiry is not greater than mssp admin expiry
            if ($request->expiry > Auth::user()->license->expiry) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'The requested company expiry cannot be greater than the MSSP admin expiry.',
                    ],
                    403
                );
            }

            $token = Str::random(32);

            // Generating company ID and token
            $company_id = sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff)
            );
            $pass_create_link = env("NEXT_APP_URL") . "/company/create-password/" . $token;

            // Preparing data
            Company::create([
                'company_id' => $company_id,
                'partner_id' => Auth::user()->partner_id,
                'email' => $request->email,
                'full_name' => $request->full_name,
                'company_name' => $request->company_name,
                'employees' => $request->employees,
                'account_type' => 'normal',
                'mssp_id' => Auth::user()->mssp_id,
                'enabled_feature' => json_encode($request->features),
                'pass_create_token' => $token,
                'approved' => 1, // Default to not approved
                'service_status' => 1, // Default to inactive
                'storage_region' => $request->storage_region
            ]);

            CompanyLicense::create([
                'company_id' => $company_id,
                'employees' => $request->employees,
                'tprm_employees' => $request->tprm_employees ?? 0,
                'blue_collar_employees' => $request->blue_collar_employees ?? 0,
                'expiry' => $request->expiry,
            ]);

            // Insert the company settings
            DB::table('company_settings')->insert([
                'company_id' => $company_id,
                'email' => $request->email,
                'country' => $request->storage_region,
                'time_zone' => $request->time_zone,
                'date_format' => 'dd/MM/yyyy',
                'mfa' => '0',
                'mfa_secret' => '',
                'default_phishing_email_lang' => 'en',
                'default_training_lang' => 'en',
                'default_notifications_lang' => 'en',
                'phish_redirect' => 'simuEducation',
                'phish_redirect_url' => '',
                'phish_reporting' => '0',
                'training_assign_remind_freq_days' => '1',
            ]);
            // Insert the company tours
            DB::table('company_tours')->insert([
                'company_id' => $company_id,
                'dashboard' => 0,
                'sidebar' => 0,
                'settings' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            //update mssp company license
            $this->updateMsspCompanyLicense($request);

           
            // Extract data from request to avoid serialization issues
            $companyData = $request->only(['email', 'full_name', 'company_name', 'employees']);
            $companyData['account_type'] = 'normal';

            Mail::to($request->email)->send(new CreateMsspCompanyMail($companyData, $pass_create_link));

            

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Company created successfully.',
                ]
            );
        } catch (ValidationException $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => __('Validation Error: ') . $e->getMessage(),
                ],
                500
            );
        }
    }

    private function msspCompanyHasLicense($request): bool
    {
        // current available employees license
        $availableEmployeeLicense = Auth::user()->license->employees - Auth::user()->license->used_employees;
        // current blue collar employees license
        $availableBlueCollarLicense = Auth::user()->license->blue_collar_employees - Auth::user()->license->used_blue_collar_employees;
        // current tprm employees license
        $availableTprmLicense = Auth::user()->license->tprm_employees - Auth::user()->license->used_tprm_employees;

        if ($request->employees > $availableEmployeeLicense ||
            ($request->blue_collar_employees ?? 0) > $availableBlueCollarLicense ||
            ($request->tprm_employees ?? 0) > $availableTprmLicense) {
            return false;
        }
        return true;
    }

    private function updateMsspCompanyLicense($request): void
    {
        $msspLicense = Auth::user()->license;
        $msspLicense->used_employees += $request->employees;
        $msspLicense->used_blue_collar_employees += $request->blue_collar_employees ?? 0;
        $msspLicense->used_tprm_employees += $request->tprm_employees ?? 0;
        $msspLicense->save();
    }

    public function companyLoginSwitch($email)
    {
        if (Auth::user()->mssp_admin == 0) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'You are not authorized to access this resource.',
                ],
                403
            );
        }

        $company = Company::where('email', $email)
            ->where('mssp_id', Auth::user()->mssp_id)
            ->where('mssp_admin', 0)
            ->first();

        if (!$company) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Company not found or you are not authorized to access this company.',
                ],
                404
            );
        }

        $authService = new AuthService($email);

        return $authService->loginCompany('mssp_switch');
    }
}
