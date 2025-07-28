<?php

namespace App\Http\Controllers\Api;

use App\Models\Users;
use Illuminate\Http\Request;
use App\Models\CustomisedReporting;
use App\Http\Controllers\Controller;
use App\Models\AiCallCampLive;
use App\Models\AssignedPolicy;
use App\Models\CampaignLive;
use App\Models\QuishingLiveCamp;
use App\Models\TrainingAssignedUser;
use App\Models\WaLiveCampaign;
use Illuminate\Support\Facades\Auth;
use PHPOpenSourceSaver\JWTAuth\Claims\Custom;
use Illuminate\Validation\ValidationException;

class CustomisedReportingController extends Controller
{
    public function index(Request $request)
    {
        // Fetch customised reports based on the company ID
        $companyId = Auth::user()->company_id;
        $reports = CustomisedReporting::where('company_id', $companyId)->get();

        return response()->json([
            'success' => true,
            'message' => __('Customised reports fetched successfully'),
            'data' => $reports,
        ]);
    }

    public function addCard(Request $request)
    {
        try {
            $request->validate([
                'report_name' => 'required|string|max:255',
                'report_description' => 'required|string'
            ]);

            CustomisedReporting::create([
                'report_name' => $request->report_name,
                'report_description' => $request->report_description,
                'company_id' => Auth::user()->company_id,
            ]);
            return response()->json([
                'success' => true,
                'message' => __('Widget added successfully')
            ]);
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

    public function addWidgets(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|string',
                'widgets' => 'required|array'
            ]);

            $id = base64_decode($request->id);

            CustomisedReporting::where('id', $id)
                ->where('company_id', Auth::user()->company_id)
                ->update(['widgets' => json_encode($request->widgets)]);
            return response()->json([
                'success' => true,
                'message' => __('Widget added successfully')
            ]);
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

    public function cardData(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;
            $type = $request->query('type');
            $cardData = [];
            if ($type == 'employees') {
                $cardData = Users::where('company_id', $companyId)
                    ->distinct('user_email')
                    ->count('user_email');
            }
            if ($type == 'assigned_trainings') {
                $cardData = TrainingAssignedUser::where('company_id', $companyId)
                    ->count();
            }
            if ($type == 'assigned_policies') {
                $cardData = AssignedPolicy::where('company_id', $companyId)
                    ->count();
            }
            if ($type == 'compromised_employees') {
                $cardData = CampaignLive::where('company_id', $companyId)
                    ->where('emp_compromised', 1)
                    ->count();
                $cardData += QuishingLiveCamp::where('company_id', $companyId)
                    ->where('compromised', '1')
                    ->count();
                $cardData += WaLiveCampaign::where('company_id', $companyId)
                    ->where('compromised', 1)
                    ->count();

                $cardData += AiCallCampLive::where('company_id', $companyId)
                    ->where('compromised', 1)
                    ->count();
            }

            return response()->json([
                'success' => true,
                'message' => __('Card data retrieved successfully'),
                'data' => $cardData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }
}
