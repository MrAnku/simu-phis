<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Reports\BaseReportService;
use Illuminate\Http\Request;

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
}
