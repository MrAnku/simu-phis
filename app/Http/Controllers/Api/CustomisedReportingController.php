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

    public function reportingById($id){
        $id = base64_decode($id);
        $companyId = Auth::user()->company_id;
        $report = CustomisedReporting::where('id', $id)
            ->where('company_id', $companyId)
            ->first();  
        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => __('Report not found')
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => __('Report fetched successfully'),
            'data' => $report
        ]);
    }

    public function addReport(Request $request)
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
                $cardData['title'] = __('Employees');
                $cardData['period'] = __('Last 30 Days');
                $cardData['value'] = Users::where('company_id', $companyId)
                    ->distinct('user_email')
                    ->count();
                $totalEmployees = Users::where('company_id', $companyId)
                    ->distinct('user_email')
                    ->count();

                // Example: fraction = percentage of employees added in last 30 days
                $employeesLast30Days = Users::where('company_id', $companyId)
                    ->where('created_at', '>=', now()->subDays(30))
                    ->distinct('user_email')
                    ->count();

                $cardData['fraction'] = $totalEmployees > 0 ? round(($employeesLast30Days / $totalEmployees) * 100, 2) : 0;
                $cardData['pp'] = $employeesLast30Days;
                $cardData['icon'] = 'LucideUsers';
                $cardData['iconColor'] = 'text-blue-500';
            }
            if ($type == 'assigned_trainings') {
                $cardData['title'] = __('Assigned Trainings');
                $cardData['period'] = __('Total');
                $cardData['value'] = TrainingAssignedUser::where('company_id', $companyId)->count();
                $cardData['icon'] = 'LucideBookOpen';
                $cardData['iconColor'] = 'text-green-500';
            }
            if ($type == 'assigned_policies') {
                $cardData['title'] = __('Assigned Policies');
                $cardData['period'] = __('Total');
                $cardData['value'] = AssignedPolicy::where('company_id', $companyId)->count();
                $cardData['icon'] = 'LucideFileText';
                $cardData['iconColor'] = 'text-yellow-500';
            }
            if ($type == 'compromised_employees') {
                $compromisedCount = CampaignLive::where('company_id', $companyId)
                    ->where('emp_compromised', 1)
                    ->count();
                $compromisedCount += QuishingLiveCamp::where('company_id', $companyId)
                    ->where('compromised', '1')
                    ->count();
                $compromisedCount += WaLiveCampaign::where('company_id', $companyId)
                    ->where('compromised', 1)
                    ->count();
                $compromisedCount += AiCallCampLive::where('company_id', $companyId)
                    ->where('compromised', 1)
                    ->count();

                $cardData['title'] = __('Compromised Employees');
                $cardData['period'] = __('Total');
                $cardData['value'] = $compromisedCount;
                $cardData['icon'] = 'LucideAlertTriangle';
                $cardData['iconColor'] = 'text-red-500';
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
