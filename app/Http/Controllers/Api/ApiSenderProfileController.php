<?php

namespace App\Http\Controllers\Api;

use App\Models\Campaign;
use App\Models\CampaignLive;
use Illuminate\Http\Request;
use App\Models\PhishingEmail;
use App\Models\SenderProfile;
use App\Http\Controllers\Controller;
use App\Models\QuishingCamp;
use App\Models\QuishingLiveCamp;
use App\Models\TprmCampaign;
use App\Models\TprmCampaignLive;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ApiSenderProfileController extends Controller
{
    //old
    public function index()
    {
        try {
            $company_id = Auth::user()->company_id;

            $senderProfiles = SenderProfile::where('company_id', $company_id)
                ->orWhere('company_id', 'default')
                ->get();

            return response()->json([
                'success' => true,
                'message' => __('Sender profiles fetched successfully.'),
                'data' => $senderProfiles
            ], 200); // ✅ 200 OK

        } catch (\Exception $e) {
           
            return response()->json([
                'success' => false,
                'message' => __('An error occurred while fetching sender profiles.'),
                'error' => $e->getMessage()
            ], 500); // ❌ 500 Internal Server Error
        }
    }

    //new
    public function index2(){
        try {
            $company_id = Auth::user()->company_id;

           $default = SenderProfile::where('company_id', 'default')->get();
           $custom = SenderProfile::where('company_id', $company_id)->get();

            return response()->json([
                'success' => true,
                'message' => __('Sender profiles fetched successfully.'),
                'data' => [
                    'default' => $default,
                    'custom' => $custom
                ]
            ], 200); // ✅ 200 OK

        } catch (\Exception $e) {
           
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage(),
            ], 500); // ❌ 500 Internal Server Error
        }
    }

    public function deleteSenderProfile(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required',
            ]);
            $senderProfileId = $request->id;
            $id = base64_decode($senderProfileId);

            $senderProfile = SenderProfile::where('id', $id)
                ->where('company_id', Auth::user()->company_id)
                ->first();

            if (!$senderProfile) {
                return response()->json([
                    'success' => false,
                    'message' => __('Sender profile not found.'),
                ], 404); // ✅ 404 Not Found
            }

            //set null to campaign live and campaign
            Campaign::where('sender_profile', $id)
                ->update(['sender_profile' => null]);
            CampaignLive::where('sender_profile', $id)
                ->update(['sender_profile' => null]);
            QuishingCamp::where('sender_profile', $id)
                ->update(['sender_profile' => null]);
            QuishingLiveCamp::where('sender_profile', $id)
                ->update(['sender_profile' => null]);
            TprmCampaign::where('sender_profile', $id)
                ->update(['sender_profile' => null]);
            TprmCampaignLive::where('sender_profile', $id)
                ->update(['sender_profile' => null]);

            $senderProfile->delete();

            log_action("Sender profile deleted successfully");
            return response()->json([
                'success' => true,
                'message' => __('Sender profile deleted successfully.'),
            ], 200); // ✅ 200 OK

        } catch (\Exception $e) {
            log_action("Exception during sender profile delete: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Error: '). $e->getMessage(),
            ], 500);
        }
    }


    public function addSenderProfile(Request $request)
    {
        try {
            // XSS Check start
            $input = $request->all();

            foreach ($input as $key => $value) {
                if (preg_match('/<[^>]*>|<\?php/', $value)) {
                    return response()->json([
                        'success' => false,
                        'message' => __('Invalid input detected.'),
                    ], 400); // Bad Request
                }
            }

            array_walk_recursive($input, function (&$input) {
                $input = strip_tags($input);
            });
            $request->merge($input);
            // XSS Check end

            // Validation
            $validator = Validator::make($request->all(), [
                'pName' => 'required|string|max:255',
                'from_name' => 'required|string|max:255',
                'from_email' => 'required|email|max:255',
                'smtp_host' => 'required|string|max:255',
                'smtp_username' => 'required|string|max:255',
                'smtp_password' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => __('Error: ') . $validator->errors()->first(),
                    'errors' => $validator->errors(),
                ], 422); // Unprocessable Entity
            }

            $validated = $validator->validated();

            $senderProfile = new SenderProfile();
            $senderProfile->profile_name = $validated['pName'];
            $senderProfile->from_name = $validated['from_name'];
            $senderProfile->from_email = $validated['from_email'];
            $senderProfile->host = $validated['smtp_host'];
            $senderProfile->username = $validated['smtp_username'];
            $senderProfile->password = $validated['smtp_password'];
            $senderProfile->company_id = Auth::user()->company_id;

            if ($senderProfile->save()) {
                log_action("Sender profile added successfully");
                return response()->json([
                    'success' => true,
                    'message' => __('Sender profile added successfully!'),
                    'data' => $senderProfile,
                ], 201); // Created
            }

            log_action("Failed to add sender profile");

            return response()->json([
                'success' => false,
                'message' => __('Failed to add sender profile'),
            ], 500); // Internal Server Error

        } catch (\Exception $e) {
            log_action("Exception while adding sender profile: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('An error occurred while adding the sender profile.'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

   public function saveManualSenderProfile(Request $request)
    {
        try {
            // XSS Check start
            $input = $request->all();

            foreach ($input as $key => $value) {
                if (preg_match('/<[^>]*>|<\?php/', $value)) {
                    return response()->json([
                        'success' => false,
                        'message' => __('Invalid input detected.'),
                    ], 400); // Bad Request
                }
            }

            // Validation
            $request->validate([
                'pName' => 'required|string|max:255',
                'from_name' => 'required|string|max:255',
                'from_email' => 'required|email|max:255',
                'smtp_host' => 'required|string|max:255',
                'smtp_username' => 'required|string|max:255',
                'smtp_password' => 'required|string|max:255',
            ]);


            $senderProfile = new SenderProfile();
            $senderProfile->profile_name = $request->input('pName');
            $senderProfile->from_name = $request->input('from_name');
            $senderProfile->from_email = $request->input('from_email');
            $senderProfile->host = $request->input('smtp_host');
            $senderProfile->username = $request->input('smtp_username');
            $senderProfile->password = $request->input('smtp_password');
            $senderProfile->company_id = Auth::user()->company_id;

            if ($senderProfile->save()) {
                log_action("Manual sender profile added successfully");
                return response()->json([
                    'success' => true,
                    'message' => __('Sender profile added successfully!'),
                    'data' => $senderProfile,
                ], 201); // Created
            }

            log_action("Failed to add manual sender profile");

            return response()->json([
                'success' => false,
                'message' => __('Failed to add sender profile'),
            ], 500); // Internal Server Error

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('Validation Error: ') . $e->validator->errors()->first(),
            ], 422); // Unprocessable Entity
        } catch (\Exception $e) {
            log_action("Exception while adding sender profile: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage(),
            ], 500);
        }
    }

    public function saveManagedSenderProfile(Request $request)
    {
        try {
            // XSS Check start
            $input = $request->all();

            foreach ($input as $key => $value) {
                if (preg_match('/<[^>]*>|<\?php/', $value)) {
                    return response()->json([
                        'success' => false,
                        'message' => __('Invalid input detected.'),
                    ], 400); // Bad Request
                }
            }

            // Validation
            $request->validate([
                'pName' => 'required|string|max:255',
                'from_name' => 'required|string|max:255',
                'from_email' => 'required|email|max:255',
                'domain' => 'required|in:secure-accessmail.com,securitynotice.org'
            ]);

            if($request->domain == 'secure-accessmail.com'){
                $host = env('MAILSENDER_HOST');
                $username = env('MAILSENDER_USERNAME_SECURE_ACCESSMAIL');
                $password = env('MAILSENDER_PASSWORD_SECURE_ACCESSMAIL');
            } elseif($request->domain == 'securitynotice.org') {
                $host = env('MAILSENDER_HOST');
                $username = env('MAILSENDER_USERNAME_SECURITY_NOTICE');
                $password = env('MAILSENDER_PASSWORD_SECURITY_NOTICE');
            }

            $senderProfile = new SenderProfile();
            $senderProfile->profile_name = $request->input('pName');
            $senderProfile->type = 'managed';
            $senderProfile->from_name = $request->input('from_name');
            $senderProfile->from_email = $request->input('from_email');
            $senderProfile->host = $host;
            $senderProfile->username = $username;
            $senderProfile->password = $password;
            $senderProfile->company_id = Auth::user()->company_id;

            if ($senderProfile->save()) {
                log_action("Sender profile added successfully");
                return response()->json([
                    'success' => true,
                    'message' => __('Sender profile added successfully!')
                ], 201); // Created
            }

            log_action("Failed to add managed sender profile");

            return response()->json([
                'success' => false,
                'message' => __('Failed to add managed sender profile'),
            ], 500); // Internal Server Error

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('Validation Error: ') . $e->validator->errors()->first(),
            ], 422); // Unprocessable Entity
        } catch (\Exception $e) {
            log_action("Exception while adding sender profile: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage(),
            ], 500);
        }
    }


    public function getSenderProfile($id)
    {
        try {
            $senderProfile = SenderProfile::find($id);

            if ($senderProfile) {
                return response()->json([
                    'success' => true,
                    'message' => __('Sender profile retrieved successfully'),
                    'data' => $senderProfile
                ], 200); // OK
            } else {
                return response()->json([
                    'success' => false,
                    'message' => __('Sender profile not found'),
                ], 404); // Not Found
            }
        } catch (\Exception $e) {
            log_action("Exception while fetching sender profile: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('An error occurred while fetching the sender profile.'),
                'error' => $e->getMessage()
            ], 500); // Internal Server Error
        }
    }


    public function updateSenderProfile(Request $request)
    {
        try {
            // XSS check start
            $input = $request->all();

            foreach ($input as $key => $value) {
                if (is_string($value) && preg_match('/<[^>]*>|<\?php/', $value)) {
                    return response()->json([
                        'success' => false,
                        'message' => __('Invalid input detected.')
                    ], 400);
                }
            }
            // XSS check end
            $id = $request->route('id');
            if(!$id){
                return response()->json([
                    'success' => false,
                    'message' => __('Sender profile ID is required.'),
                ], 400); // Bad Request
            }
            $id = base64_decode($id);
            $type = $request->input('type');
            if($type === 'managed'){
                $profileName = $request->input('pName');
                $fromName = $request->input('from_name');
                $fromEmail = $request->input('from_email');

                SenderProfile::where('id', $id)
                    ->where('company_id', Auth::user()->company_id)
                    ->update([
                        'profile_name' => $profileName,
                        'from_name' => $fromName,
                        'from_email' => $fromEmail
                    ]);
                return response()->json([
                    'success' => true,
                    'message' => __('Sender Profile Updated Successfully!'),
                ], 200); // ✅ 200 OK
            }
            else {
                $profileName = $request->input('pName');
                $fromName = $request->input('from_name');
                $fromEmail = $request->input('from_email');
                $host = $request->input('smtp_host');
                $username = $request->input('smtp_username');
                $password = $request->input('smtp_password');

                SenderProfile::where('id', $id)
                    ->where('company_id', Auth::user()->company_id)
                    ->update([
                        'profile_name' => $profileName,
                        'from_name' => $fromName,
                        'from_email' => $fromEmail,
                        'host' => $host,
                        'username' => $username,
                        'password' => $password
                    ]);
                return response()->json([
                    'success' => true,
                    'message' => __('Sender Profile Updated Successfully!'),
                ], 200); // ✅ 200 OK
            }

          
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: '),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            log_action("Exception during sender profile update: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('An error occurred while updating the sender profile.'),
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
