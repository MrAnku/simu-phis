<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Reports\DivisionReportService;
use App\Services\Reports\AwarenessReportService;
use App\Services\Reports\TrainingReportService;
use Illuminate\Http\Request;
use App\Services\Reports\OverallNormalEmployeeReport;
use Illuminate\Support\Facades\Auth;
use App\Services\Reports\CourseSummaryReportService;
use App\Services\Reports\PoliciesReportService;
use App\Services\Reports\GamesReportService;
use App\Services\Reports\ResponseBuilder;

class ApiReportController extends Controller
{
    protected DivisionReportService $divisionService;
    protected AwarenessReportService $awarenessService;
    protected TrainingReportService $trainingService;
    protected CourseSummaryReportService $courseSummaryService;
    protected PoliciesReportService $policiesService;
    protected GamesReportService $gamesService;

    public function __construct(
        DivisionReportService $divisionService,
        AwarenessReportService $awarenessService,
        TrainingReportService $trainingService,
        CourseSummaryReportService $courseSummaryService,
        PoliciesReportService $policiesService,
        GamesReportService $gamesService
    ) {
        $this->divisionService = $divisionService;
        $this->awarenessService = $awarenessService;
        $this->trainingService = $trainingService;
        $this->courseSummaryService = $courseSummaryService;
        $this->policiesService = $policiesService;
        $this->gamesService = $gamesService;
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

    public function fetchCourseSummaryReport()
    {
        try {
            $companyId = Auth::user()->company_id;
            $data = $this->courseSummaryService->fetchCourseSummaryReport($companyId);
            return ResponseBuilder::courseSummarySuccess($data);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }
    public function fetchPoliciesReporting()
    {

        try {
            $companyId = Auth::user()->company_id;
            $data = $this->policiesService->fetchPoliciesReport($companyId);
            return ResponseBuilder::policiesReportSuccess($data);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }
    public function fetchGamesReport()
    {
        try {
            $companyId = Auth::user()->company_id;
            $data = $this->gamesService->fetchGamesReport($companyId);
            return ResponseBuilder::gamesReportSuccess($data);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }
}
