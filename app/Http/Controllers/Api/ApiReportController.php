<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Reports\DivisionReportService;
use App\Services\Reports\AwarenessReportService;
use App\Services\Reports\TrainingReportService;
use Illuminate\Http\Request;
use App\Services\Reports\OverallNormalEmployeeReport;
use Illuminate\Support\Facades\Auth;

class ApiReportController extends Controller
{
    protected DivisionReportService $divisionService;
    protected AwarenessReportService $awarenessService;
    protected TrainingReportService $trainingService;

    public function __construct(
        DivisionReportService $divisionService,
        AwarenessReportService $awarenessService,
        TrainingReportService $trainingService
    ) {
        $this->divisionService = $divisionService;
        $this->awarenessService = $awarenessService;
        $this->trainingService = $trainingService;
    }

    public function fetchDivisionUsersReporting(Request $request)
    {
        return $this->divisionService->fetchDivisionUsersReporting();
    }
    public function fetchAwarenessEduReporting()
    {
        return $this->awarenessService->fetchAwarenessEduReporting();
    }
    public function fetchUsersReport()
    {
        try {
            $companyId = Auth::user()->company_id;
            $usersReport = new OverallNormalEmployeeReport($companyId);
            $reportData = $usersReport->generateReport();
            return response()->json([
                'success' => true,
                'message' => __('Users report fetched successfully'),
                'data' => $reportData
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }

    public function fetchTrainingReport()
    {
        return $this->trainingService->fetchTrainingReport();
    }
}
