<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Reports\BaseReportService;
use Illuminate\Http\Request;
use App\Services\Reports\OverallNormalEmployeeReport;
use Illuminate\Support\Facades\Auth;

class ApiReportController extends Controller
{
    protected $baseReportService;

    public function __construct(BaseReportService $baseReportService)
    {
        $this->baseReportService = $baseReportService;
    }

    public function fetchDivisionUsersReporting(Request $request)
    {
        return  $this->baseReportService->fetchDivisionUsersReporting($request);
    }
    public function fetchAwarenessEduReporting()
    {
        return $this->baseReportService->fetchAwarenessEduReporting();
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
}
