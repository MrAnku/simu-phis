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
use App\Models\TprmCampaignLive;
use App\Models\TrainingAssignedUser;
use App\Models\UsersGroup;
use App\Models\WaLiveCampaign;
use App\Services\CustomisedReport\WidgetsService;
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

    public function reportingById($id)
    {
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

    public function updateReport(Request $request, $id)
    {
        try {
            $request->validate([
                'report_name' => 'required|string|max:255',
                'report_description' => 'required|string'
            ]);
            $id = base64_decode($id);

            CustomisedReporting::where('id', $id)
                ->where('company_id', Auth::user()->company_id)
                ->update([
                    'report_name' => $request->report_name,
                    'report_description' => $request->report_description
                ]);

            return response()->json([
                'success' => true,
                'message' => __('Report updated successfully')
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

    public function deleteReport($id)
    {
        try {
            $id = base64_decode($id);
            CustomisedReporting::where('id', $id)
                ->where('company_id', Auth::user()->company_id)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => __('Report deleted successfully')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
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

    // graph apis

    public function cardData(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;
            $type = $request->query('type');

            $widget = new WidgetsService($companyId);

            return response()->json([
                'success' => true,
                'message' => __('Card data retrieved successfully'),
                'data' => $widget->card($type)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }

    public function lineData(Request $request)
    {
        try {
            $type = $request->query('type', 'interaction');
            $months = $request->query('months', 6);
            $companyId = Auth::user()->company_id;

            $widget = new WidgetsService($companyId);

            return response()->json([
                'success' => true,
                'message' => __('Data retrieved successfully'),
                'data' => $widget->line($type, $months)
            ]);


        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }

    public function barData(Request $request)
    {
        try {
            $type = $request->query('type', 'interaction');
            $months = $request->query('months', 6);
            $companyId = Auth::user()->company_id;

            $widget = new WidgetsService($companyId);

            return response()->json([
                'success' => true,
                'message' => __('Data retrieved successfully'),
                'data' => $widget->line($type, $months)
            ]);


        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }

    public function radialbarData(Request $request)
    {
         try {
            $type = $request->query('type', 'interaction');
            $months = $request->query('months', 6);
            $companyId = Auth::user()->company_id;

            $widget = new WidgetsService($companyId);

            return response()->json([
                'success' => true,
                'message' => __('Data retrieved successfully'),
                'data' => $widget->line($type, $months)
            ]);


        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }

    public function mixedData(Request $request)
    {
        try {
            $type = $request->query('type', 'interaction');
            $months = $request->query('months', 6);
            $companyId = Auth::user()->company_id;

            $widget = new WidgetsService($companyId);

            return response()->json([
                'success' => true,
                'message' => __('Data retrieved successfully'),
                'data' => $widget->line($type, $months)
            ]);


        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }

    
    public function areaData(Request $request)
    {
        try {
            $type = $request->query('type', 'interaction');
            $months = $request->query('months', 6);
            $companyId = Auth::user()->company_id;

            $widget = new WidgetsService($companyId);

            return response()->json([
                'success' => true,
                'message' => __('Data retrieved successfully'),
                'data' => $widget->line($type, $months)
            ]);


        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }

    public function tableData(Request $request)
    {
          try {
            $type = $request->query('type', 'employees');
            $months = $request->query('months', 2);
            $companyId = Auth::user()->company_id;

            $widget = new WidgetsService($companyId);

            return response()->json([
                'success' => true,
                'message' => __('Table data retrieved successfully'),
                'data' => $widget->table($type, $months)
            ]);


        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }

    

    public function bubbleData(Request $request)
    {
         try {
            $type = $request->query('type', 'division_report');
            $months = $request->query('months', 6);
            $companyId = Auth::user()->company_id;

            $widget = new WidgetsService($companyId);

            return response()->json([
                'success' => true,
                'message' => __('Data retrieved successfully'),
                'data' => $widget->bubble($type, $months)
            ]);


        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }

    

    
}
