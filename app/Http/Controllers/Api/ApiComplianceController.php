<?php

namespace App\Http\Controllers\Api;

use App\Models\Users;
use App\Models\Campaign;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\AiCallCampaign;
use App\Models\AiCallCampLive;
use App\Models\CampaignLive;
use App\Models\QuishingCamp;
use App\Models\QuishingLiveCamp;
use App\Models\TprmCampaign;
use App\Models\TprmCampaignLive;
use App\Models\TrainingAssignedUser;
use App\Models\WaCampaign;
use App\Models\WaLiveCampaign;
use Illuminate\Support\Facades\Auth;

class ApiComplianceController extends Controller
{
    public function index(Request $request)
    {
        try {
            $companyId =  Auth::user()->company_id;
            $totalEmployees = Users::where('company_id', $companyId)
                ->get()
                ->unique('user_email')
                ->values()
                ->count();
            $trainedEmployees = TrainingAssignedUser::where('company_id', $companyId)
                ->get()
                ->unique('user_email')
                ->values()
                ->where('completed', 1)
                ->count();
            $trainedEmployeesPercentage = $totalEmployees > 0 ? round(($trainedEmployees / $totalEmployees) * 100, 2) : 0;

            //phishing tests
            $simulations = Campaign::where('company_id', $companyId)
                ->count() +
                WaCampaign::where('company_id', $companyId)
                ->count() +
                QuishingCamp::where('company_id', $companyId)
                ->count() +
                AiCallCampaign::where('company_id', $companyId)
                ->count();
            TprmCampaign::where('company_id', $companyId)
                ->count();


            return response()->json([
                'success' => true,
                'message' => 'Compliance data retrieved successfully',
                'data' => [
                    'total_employees' => $totalEmployees,
                    'trained_employees' => $trainedEmployees,
                    'trained_employees_percentage' => $trainedEmployeesPercentage,
                    'simulations' => $simulations,
                    'click_rate' => $this->clickRate(),
                    'report_rate' => $this->reportRate(),
                    'training_completion' => $this->trainingCompletion(),
                    'frameworks' => $this->frameworkScore(),
                    'simulation_results' => $this->simulationResults(),
                ]

            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function clickRate()
    {
        $companyId = Auth::user()->company_id;

        $clicks =
            CampaignLive::where('company_id', $companyId)->where('payload_clicked', 1)->count() +
            WaLiveCampaign::where('company_id', $companyId)->where('payload_clicked', 1)->count() +
            QuishingLiveCamp::where('company_id', $companyId)->where('qr_scanned', '1')->count() +

            TprmCampaignLive::where('company_id', $companyId)->where('payload_clicked', 1)->count();

        $total =
            CampaignLive::where('company_id', $companyId)->count() +
            WaLiveCampaign::where('company_id', $companyId)->count() +
            QuishingLiveCamp::where('company_id', $companyId)->count() +
            TprmCampaignLive::where('company_id', $companyId)->count();

        return $total > 0 ? round(($clicks / $total) * 100, 2) : 0.0;
    }

    private function reportRate()
    {
        $companyId = Auth::user()->company_id;

        $now = now();
        $currentStart = $now->copy()->subDays(14);
        $previousStart = $now->copy()->subDays(28);
        $previousEnd = $currentStart;

        // Current 14 days
        $currentReports =
            CampaignLive::where('company_id', $companyId)->where('email_reported', 1)->whereBetween('created_at', [$currentStart, $now])->count() +

            QuishingLiveCamp::where('company_id', $companyId)->where('email_reported', 1)->whereBetween('created_at', [$currentStart, $now])->count() +
            TprmCampaignLive::where('company_id', $companyId)->where('email_reported', 1)->whereBetween('created_at', [$currentStart, $now])->count();

        $currentTotal =
            CampaignLive::where('company_id', $companyId)->whereBetween('created_at', [$currentStart, $now])->count() +

            QuishingLiveCamp::where('company_id', $companyId)->whereBetween('created_at', [$currentStart, $now])->count() +
            TprmCampaignLive::where('company_id', $companyId)->whereBetween('created_at', [$currentStart, $now])->count();

        // Previous 14 days
        $previousReports =
            CampaignLive::where('company_id', $companyId)->where('email_reported', 1)->whereBetween('created_at', [$previousStart, $previousEnd])->count() +

            QuishingLiveCamp::where('company_id', $companyId)->where('email_reported', 1)->whereBetween('created_at', [$previousStart, $previousEnd])->count() +
            TprmCampaignLive::where('company_id', $companyId)->where('email_reported', 1)->whereBetween('created_at', [$previousStart, $previousEnd])->count();

        $previousTotal =
            CampaignLive::where('company_id', $companyId)->whereBetween('created_at', [$previousStart, $previousEnd])->count() +

            QuishingLiveCamp::where('company_id', $companyId)->whereBetween('created_at', [$previousStart, $previousEnd])->count() +
            TprmCampaignLive::where('company_id', $companyId)->whereBetween('created_at', [$previousStart, $previousEnd])->count();

        $currentRate = $currentTotal > 0 ? round(($currentReports / $currentTotal) * 100, 2) : 0.0;
        $previousRate = $previousTotal > 0 ? round(($previousReports / $previousTotal) * 100, 2) : 0.0;

        return [
            'rate_percent' => $currentRate,
            'pp' => round($currentRate - $previousRate, 2), // percentage points change
        ];
    }

    private function trainingCompletion()
    {
        $companyId = Auth::user()->company_id;

        $assigned = TrainingAssignedUser::where('company_id', $companyId)->count();
        $completed = TrainingAssignedUser::where('company_id', $companyId)->where('completed', 1)->count();

        return $assigned > 0 ? round(($completed / $assigned) * 100, 2) : 0.0;
    }

    private function simulationResults()
    {
        $companyId = Auth::user()->company_id;

        // Get all simulation data
        $clicks =
            CampaignLive::where('company_id', $companyId)->where('payload_clicked', 1)->count() +
            WaLiveCampaign::where('company_id', $companyId)->where('payload_clicked', 1)->count() +
            QuishingLiveCamp::where('company_id', $companyId)->where('qr_scanned', '1')->count();

        $reports =
            CampaignLive::where('company_id', $companyId)->where('email_reported', 1)->count() +
            QuishingLiveCamp::where('company_id', $companyId)->where('email_reported', '1')->count();

        $total =
            CampaignLive::where('company_id', $companyId)->count() +
            WaLiveCampaign::where('company_id', $companyId)->count() +
            QuishingLiveCamp::where('company_id', $companyId)->count();

        $noAction = $total - ($clicks + $reports);

        return [
            'clicked_link' => $clicks,
            'reported_phishing' => $reports,
            'no_action' => max(0, $noAction),
        ];
    }

    private function frameworkScore()
    {
        $companyId = Auth::user()->company_id;

        // Get total and trained employees
        $totalEmployees = Users::where('company_id', $companyId)
            ->get()
            ->unique('user_email')
            ->values()
            ->count();

        $trainedEmployees = TrainingAssignedUser::where('company_id', $companyId)
            ->where('completed', 1)
            ->get()
            ->unique('user_email')
            ->values()
            ->count();

        // Get simulation data
        $totalSimulations = Campaign::where('company_id', $companyId)->count() +
            WaCampaign::where('company_id', $companyId)->count() +
            QuishingCamp::where('company_id', $companyId)->count() +
            AiCallCampaign::where('company_id', $companyId)->count();

        // Calculate base metrics
        $trainingCoverage = $totalEmployees > 0 ? ($trainedEmployees / $totalEmployees) * 100 : 0;
        $hasActiveSimulations = $totalSimulations > 0 ? 100 : 0;
        $reportingCapability = 100; // Platform has reporting capability

        // Framework scoring (out of 100)
        $frameworks = [
            'SOC_2' => min(100, round(($trainingCoverage * 0.6) + ($hasActiveSimulations * 0.4))),
            'ISO_27001' => min(100, round(($trainingCoverage * 0.7) + ($hasActiveSimulations * 0.3))),
            'HIPAA' => min(100, round(($trainingCoverage * 0.8) + ($hasActiveSimulations * 0.2))),
            'GDPR' => min(100, round(($trainingCoverage * 0.7) + ($hasActiveSimulations * 0.3))),
            'PDPL' => min(100, round(($trainingCoverage * 0.7) + ($hasActiveSimulations * 0.3))),
            'NIST_SP_800_50' => min(100, round(($trainingCoverage * 0.5) + ($hasActiveSimulations * 0.3) + ($reportingCapability * 0.2))),
            'NIST_SP_800_53' => min(100, round(($trainingCoverage * 0.6) + ($hasActiveSimulations * 0.4))),
            'PCI_DSS' => min(100, round(($trainingCoverage * 0.7) + ($hasActiveSimulations * 0.3))),
            'ISO_20000' => min(100, round(($trainingCoverage * 0.8) + ($hasActiveSimulations * 0.2))),
            'QCSF' => min(100, round(($trainingCoverage * 0.7) + ($hasActiveSimulations * 0.3))),
            'OCERT' => min(100, round(($trainingCoverage * 0.5) + ($hasActiveSimulations * 0.5)))
        ];

        return $frameworks;
    }
}
