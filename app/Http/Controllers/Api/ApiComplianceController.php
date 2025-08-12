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
            $months = $request->query('months', 1);
            $startDate = now()->subMonths($months)->startOfMonth();
            $endDate = now();
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
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count() +
                WaCampaign::where('company_id', $companyId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count() +
                QuishingCamp::where('company_id', $companyId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count() +
                AiCallCampaign::where('company_id', $companyId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();
            TprmCampaign::where('company_id', $companyId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();


            return response()->json([
                'success' => true,
                'message' => 'Compliance data retrieved successfully',
                'data' => [
                    'total_employees' => $totalEmployees,
                    'trained_employees' => $trainedEmployees,
                    'trained_employees_percentage' => $trainedEmployeesPercentage,
                    'simulations' => $simulations,
                    'click_rate' => $this->clickRate($startDate, $endDate),
                    'report_rate' => $this->reportRate(),
                    'training_completion' => $this->trainingCompletion($startDate, $endDate),
                    'training_completion_rate' => $this->trainingCompletionRate($startDate, $endDate),
                    'frameworks' => $this->frameworkScore($startDate, $endDate),
                    'simulation_results' => $this->simulationResults($startDate, $endDate),
                ]

            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function clickRate($startDate, $endDate)
    {
        $companyId = Auth::user()->company_id;

        $clicks =
            CampaignLive::where('company_id', $companyId)->where('payload_clicked', 1)->whereBetween('created_at', [$startDate, $endDate])->count() +
            WaLiveCampaign::where('company_id', $companyId)->where('payload_clicked', 1)->whereBetween('created_at', [$startDate, $endDate])->count() +
            QuishingLiveCamp::where('company_id', $companyId)->where('qr_scanned', '1')->whereBetween('created_at', [$startDate, $endDate])->count() +

            TprmCampaignLive::where('company_id', $companyId)->where('payload_clicked', 1)->whereBetween('created_at', [$startDate, $endDate])->count();

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

    private function trainingCompletionRate($startDate, $endDate)
    {
        $companyId = Auth::user()->company_id;
        $assigned = TrainingAssignedUser::where('company_id', $companyId)->whereBetween('created_at', [$startDate, $endDate])->count();
        $completed = TrainingAssignedUser::where('company_id', $companyId)->where('completed', 1)->whereBetween('created_at', [$startDate, $endDate])->count();

        return $assigned > 0 ? round(($completed / $assigned) * 100, 2) : 0.0;
    }


    private function trainingCompletion($startDate, $endDate)
    {
        $companyId = Auth::user()->company_id;

        $assigned = TrainingAssignedUser::where('company_id', $companyId)->whereBetween('created_at', [$startDate, $endDate])->count();
        $completed = TrainingAssignedUser::where('company_id', $companyId)->where('completed', 1)->whereBetween('created_at', [$startDate, $endDate])->count();

        return $assigned > 0 ? round(($completed / $assigned) * 100, 2) : 0.0;
    }

    private function simulationResults($startDate, $endDate)
    {
        $companyId = Auth::user()->company_id;

        // Get all simulation data
        $clicks =
            CampaignLive::where('company_id', $companyId)->whereBetween('created_at', [$startDate, $endDate])->where('payload_clicked', 1)->count() +
            WaLiveCampaign::where('company_id', $companyId)->whereBetween('created_at', [$startDate, $endDate])->where('payload_clicked', 1)->count() +
            QuishingLiveCamp::where('company_id', $companyId)->whereBetween('created_at', [$startDate, $endDate])->where('qr_scanned', '1')->count();

        $reports =
            CampaignLive::where('company_id', $companyId)->whereBetween('created_at', [$startDate, $endDate])->where('email_reported', 1)->count() +
            QuishingLiveCamp::where('company_id', $companyId)->whereBetween('created_at', [$startDate, $endDate])->where('email_reported', '1')->count();

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

    private function frameworkScore($startDate, $endDate)
    {
        $companyId = Auth::user()->company_id;

        $trainingMetrics = $this->getTrainingMetrics($companyId, $startDate, $endDate);
        $simulationMetrics = $this->getSimulationMetrics($companyId, $startDate, $endDate);

        // Framework scoring using the same calculation logic as the detailed methods
        $frameworks = [
            'SOC_2' => $this->calculateComplianceScore($trainingMetrics, $simulationMetrics, 'SOC_2'),
            'ISO_27001' => $this->calculateComplianceScore($trainingMetrics, $simulationMetrics, 'ISO_27001'),
            'HIPAA' => $this->calculateComplianceScore($trainingMetrics, [], 'HIPAA'),
            'GDPR' => $this->calculateComplianceScore($trainingMetrics, [], 'GDPR'),
            'PDPL_SAUDI' => $this->calculateComplianceScore($trainingMetrics, [], 'PDPL_SAUDI'),
            'PDPL_UAE' => $this->calculateComplianceScore($trainingMetrics, [], 'PDPL_UAE'),
            'PDPL_OMAN' => $this->calculateComplianceScore($trainingMetrics, [], 'PDPL_OMAN'),
            'PDPL_JORDAN' => $this->calculateComplianceScore($trainingMetrics, [], 'PDPL_JORDAN'),
            'NIST_SP_800_50' => $this->calculateComplianceScore($trainingMetrics, $simulationMetrics, 'NIST_SP_800_50'),
            'NIST_SP_800_53' => $this->calculateComplianceScore($trainingMetrics, $simulationMetrics, 'NIST_SP_800_53'),
            'PCI_DSS' => $this->calculateComplianceScore($trainingMetrics, $simulationMetrics, 'PCI_DSS'),
            'ISO_20000' => $this->calculateComplianceScore($trainingMetrics, [], 'ISO_20000'),
            'QCSF' => $this->calculateComplianceScore($trainingMetrics, $simulationMetrics, 'QCSF'),
            'OCERT' => $this->calculateComplianceScore($trainingMetrics, $simulationMetrics, 'OCERT')
        ];

        return $frameworks;
    }

    public function generateComplianceReport(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;
            $months = $request->query('months', 1);
            $startDate = now()->subMonths($months)->startOfMonth();
            $endDate = now();

            $frameworks = [
                'SOC_2' => $this->getSoc2Compliance($companyId, $startDate, $endDate),
                'ISO_27001' => $this->getIso27001Compliance($companyId, $startDate, $endDate),
                'HIPAA' => $this->getHipaaCompliance($companyId, $startDate, $endDate),
                'GDPR' => $this->getGdprCompliance($companyId, $startDate, $endDate),
                'PDPL_SAUDI' => $this->getPdplCompliance($companyId, $startDate, $endDate, 'SAUDI'),
                'PDPL_UAE' => $this->getPdplCompliance($companyId, $startDate, $endDate, 'UAE'),
                'PDPL_OMAN' => $this->getPdplCompliance($companyId, $startDate, $endDate, 'OMAN'),
                'PDPL_JORDAN' => $this->getPdplCompliance($companyId, $startDate, $endDate, 'JORDAN'),
                'NIST_SP_800_50' => $this->getNistSp80050Compliance($companyId, $startDate, $endDate),
                'NIST_SP_800_53' => $this->getNistSp80053Compliance($companyId, $startDate, $endDate),
                'PCI_DSS' => $this->getPciDssCompliance($companyId, $startDate, $endDate),
                'ISO_20000' => $this->getIso20000Compliance($companyId, $startDate, $endDate),
                'QCSF' => $this->getQcsfCompliance($companyId, $startDate, $endDate),
                'OCERT' => $this->getOcertCompliance($companyId, $startDate, $endDate),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Compliance framework report retrieved successfully',
                'data' => $frameworks
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function soc2Report(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;
            $months = $request->query('months', 1);
            $startDate = now()->subMonths($months)->startOfMonth();
            $endDate = now();

            $data = $this->getSoc2Compliance($companyId, $startDate, $endDate);

            return response()->json([
                'success' => true,
                'message' => 'SOC 2 compliance report retrieved successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function iso27001Report(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;
            $months = $request->query('months', 1);
            $startDate = now()->subMonths($months)->startOfMonth();
            $endDate = now();

            $data = $this->getIso27001Compliance($companyId, $startDate, $endDate);

            return response()->json([
                'success' => true,
                'message' => 'ISO 27001 compliance report retrieved successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function hipaaReport(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;
            $months = $request->query('months', 1);
            $startDate = now()->subMonths($months)->startOfMonth();
            $endDate = now();

            $data = $this->getHipaaCompliance($companyId, $startDate, $endDate);

            return response()->json([
                'success' => true,
                'message' => 'HIPAA compliance report retrieved successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function gdprReport(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;
            $months = $request->query('months', 1);
            $startDate = now()->subMonths($months)->startOfMonth();
            $endDate = now();

            $data = $this->getGdprCompliance($companyId, $startDate, $endDate);

            return response()->json([
                'success' => true,
                'message' => 'GDPR compliance report retrieved successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function pdplReport(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;
            $months = $request->query('months', 1);
            $startDate = now()->subMonths($months)->startOfMonth();
            $endDate = now();
            $region = strtoupper($request->route('region'));

            $data = $this->getPdplCompliance($companyId, $startDate, $endDate, $region);

            return response()->json([
                'success' => true,
                'message' => 'PDPL compliance report retrieved successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function nistSp80050Report(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;
            $months = $request->query('months', 1);
            $startDate = now()->subMonths($months)->startOfMonth();
            $endDate = now();

            $data = $this->getNistSp80050Compliance($companyId, $startDate, $endDate);

            return response()->json([
                'success' => true,
                'message' => 'NIST SP 800-50 compliance report retrieved successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function nistSp80053Report(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;
            $months = $request->query('months', 1);
            $startDate = now()->subMonths($months)->startOfMonth();
            $endDate = now();

            $data = $this->getNistSp80053Compliance($companyId, $startDate, $endDate);

            return response()->json([
                'success' => true,
                'message' => 'NIST SP 800-53 compliance report retrieved successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function pciDssReport(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;
            $months = $request->query('months', 1);
            $startDate = now()->subMonths($months)->startOfMonth();
            $endDate = now();

            $data = $this->getPciDssCompliance($companyId, $startDate, $endDate);

            return response()->json([
                'success' => true,
                'message' => 'PCI DSS compliance report retrieved successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function iso20000Report(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;
            $months = $request->query('months', 1);
            $startDate = now()->subMonths($months)->startOfMonth();
            $endDate = now();

            $data = $this->getIso20000Compliance($companyId, $startDate, $endDate);

            return response()->json([
                'success' => true,
                'message' => 'ISO 20000 compliance report retrieved successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function qcsfReport(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;
            $months = $request->query('months', 1);
            $startDate = now()->subMonths($months)->startOfMonth();
            $endDate = now();

            $data = $this->getQcsfCompliance($companyId, $startDate, $endDate);

            return response()->json([
                'success' => true,
                'message' => 'QCSF compliance report retrieved successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function ocertReport(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;
            $months = $request->query('months', 1);
            $startDate = now()->subMonths($months)->startOfMonth();
            $endDate = now();

            $data = $this->getOcertCompliance($companyId, $startDate, $endDate);

            return response()->json([
                'success' => true,
                'message' => 'OCERT compliance report retrieved successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function getSoc2Compliance($companyId, $startDate, $endDate)
    {
        $trainingMetrics = $this->getTrainingMetrics($companyId, $startDate, $endDate);
        $simulationMetrics = $this->getSimulationMetrics($companyId, $startDate, $endDate);

        return [
            'framework' => 'SOC 2',
            'controls' => ['CC5.3', 'CC5.4'],
            'coverage' => 'Awareness training, phishing simulation reports',
            'training_completion_rate' => $trainingMetrics['completion_rate'],
            'simulation_count' => $simulationMetrics['total_simulations'],
            'phishing_report_rate' => $simulationMetrics['report_rate'],
            'compliance_score' => $this->calculateComplianceScore($trainingMetrics, $simulationMetrics, 'SOC_2')
        ];
    }

    private function getIso27001Compliance($companyId, $startDate, $endDate)
    {
        $trainingMetrics = $this->getTrainingMetrics($companyId, $startDate, $endDate);
        $simulationMetrics = $this->getSimulationMetrics($companyId, $startDate, $endDate);

        return [
            'framework' => 'ISO 27001',
            'controls' => ['A.7.2.2'],
            'coverage' => 'Regular security awareness training metrics',
            'training_completion_rate' => $trainingMetrics['completion_rate'],
            'simulation_count' => $simulationMetrics['total_simulations'],
            'training_frequency' => $this->getTrainingFrequency($companyId, $startDate, $endDate),
            'compliance_score' => $this->calculateComplianceScore($trainingMetrics, $simulationMetrics, 'ISO_27001')
        ];
    }

    private function getHipaaCompliance($companyId, $startDate, $endDate)
    {
        $trainingMetrics = $this->getTrainingMetrics($companyId, $startDate, $endDate);

        return [
            'framework' => 'HIPAA',
            'controls' => ['§164.308(a)(5)(i)'],
            'coverage' => 'Workforce training on security awareness',
            'workforce_training_rate' => $trainingMetrics['completion_rate'],
            'trained_employees' => $trainingMetrics['trained_employees'],
            'total_employees' => $trainingMetrics['total_employees'],
            'compliance_score' => $this->calculateComplianceScore($trainingMetrics, [], 'HIPAA')
        ];
    }

    private function getGdprCompliance($companyId, $startDate, $endDate)
    {
        $trainingMetrics = $this->getTrainingMetrics($companyId, $startDate, $endDate);

        return [
            'framework' => 'GDPR',
            'controls' => ['Article 39'],
            'coverage' => 'Training to staff handling personal data',
            'staff_training_rate' => $trainingMetrics['completion_rate'],
            'data_protection_training' => $trainingMetrics['trained_employees'],
            'compliance_score' => $this->calculateComplianceScore($trainingMetrics, [], 'GDPR')
        ];
    }

    private function getPdplCompliance($companyId, $startDate, $endDate, $region)
    {
        $trainingMetrics = $this->getTrainingMetrics($companyId, $startDate, $endDate);

        return [
            'framework' => 'PDPL (' . ($region == 'SAUDI' ? 'Saudi Arabia' : $region) . ')',
            'controls' => ['Various awareness clauses'],
            'coverage' => 'Same as GDPR mapping',
            'staff_training_rate' => $trainingMetrics['completion_rate'],
            'data_protection_training' => $trainingMetrics['trained_employees'],
            'compliance_score' => $this->calculateComplianceScore($trainingMetrics, [], 'PDPL_' . $region)
        ];
    }

    private function getNistSp80050Compliance($companyId, $startDate, $endDate)
    {
        $trainingMetrics = $this->getTrainingMetrics($companyId, $startDate, $endDate);
        $simulationMetrics = $this->getSimulationMetrics($companyId, $startDate, $endDate);

        return [
            'framework' => 'NIST SP 800-50',
            'controls' => ['All training program guidelines'],
            'coverage' => 'Regular reporting of simulation and training',
            'training_program_effectiveness' => $trainingMetrics['completion_rate'],
            'simulation_frequency' => $simulationMetrics['total_simulations'],
            'reporting_capability' => 100,
            'compliance_score' => $this->calculateComplianceScore($trainingMetrics, $simulationMetrics, 'NIST_SP_800_50')
        ];
    }

    private function getNistSp80053Compliance($companyId, $startDate, $endDate)
    {
        $trainingMetrics = $this->getTrainingMetrics($companyId, $startDate, $endDate);
        $simulationMetrics = $this->getSimulationMetrics($companyId, $startDate, $endDate);

        return [
            'framework' => 'NIST SP 800-53',
            'controls' => ['AT-2', 'AT-3'],
            'coverage' => 'Phishing training program details',
            'at2_compliance' => $trainingMetrics['completion_rate'],
            'at3_compliance' => $simulationMetrics['total_simulations'] > 0 ? 100 : 0,
            'phishing_training_details' => $simulationMetrics,
            'compliance_score' => $this->calculateComplianceScore($trainingMetrics, $simulationMetrics, 'NIST_SP_800_53')
        ];
    }

    private function getPciDssCompliance($companyId, $startDate, $endDate)
    {
        $trainingMetrics = $this->getTrainingMetrics($companyId, $startDate, $endDate);
        $simulationMetrics = $this->getSimulationMetrics($companyId, $startDate, $endDate);

        return [
            'framework' => 'PCI DSS',
            'controls' => ['12.6'],
            'coverage' => 'Security awareness program',
            'security_awareness_program' => $trainingMetrics['completion_rate'],
            'program_effectiveness' => $simulationMetrics['report_rate'],
            'compliance_score' => $this->calculateComplianceScore($trainingMetrics, $simulationMetrics, 'PCI_DSS')
        ];
    }

    private function getIso20000Compliance($companyId, $startDate, $endDate)
    {
        $trainingMetrics = $this->getTrainingMetrics($companyId, $startDate, $endDate);

        return [
            'framework' => 'ISO 20000',
            'controls' => ['6.6'],
            'coverage' => 'Employee competence and training',
            'employee_competence' => $trainingMetrics['completion_rate'],
            'training_records' => $trainingMetrics['trained_employees'],
            'compliance_score' => $this->calculateComplianceScore($trainingMetrics, [], 'ISO_20000')
        ];
    }

    private function getQcsfCompliance($companyId, $startDate, $endDate)
    {
        $trainingMetrics = $this->getTrainingMetrics($companyId, $startDate, $endDate);
        $simulationMetrics = $this->getSimulationMetrics($companyId, $startDate, $endDate);

        return [
            'framework' => 'QCSF (Qatar)',
            'controls' => ['Awareness clauses'],
            'coverage' => 'Same as PCI DSS/ISO 27001 mapping',
            'awareness_training' => $trainingMetrics['completion_rate'],
            'simulation_program' => $simulationMetrics['total_simulations'],
            'compliance_score' => $this->calculateComplianceScore($trainingMetrics, $simulationMetrics, 'QCSF')
        ];
    }

    private function getOcertCompliance($companyId, $startDate, $endDate)
    {
        $simulationMetrics = $this->getSimulationMetrics($companyId, $startDate, $endDate);

        return [
            'framework' => 'OCERT Framework',
            'controls' => ['Awareness domain'],
            'coverage' => 'Simulation statistics',
            'simulation_statistics' => $simulationMetrics,
            'click_rate' => $simulationMetrics['click_rate'],
            'report_rate' => $simulationMetrics['report_rate'],
            'compliance_score' => $this->calculateComplianceScore([], $simulationMetrics, 'OCERT')
        ];
    }

    private function getTrainingMetrics($companyId, $startDate, $endDate)
    {
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

        $completionRate = $totalEmployees > 0 ? round(($trainedEmployees / $totalEmployees) * 100, 2) : 0;

        return [
            'total_employees' => $totalEmployees,
            'trained_employees' => $trainedEmployees,
            'completion_rate' => $completionRate
        ];
    }

    private function getSimulationMetrics($companyId, $startDate, $endDate)
    {
        $totalSimulations = Campaign::where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count() +
            WaCampaign::where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count() +
            QuishingCamp::where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count() +
            AiCallCampaign::where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count() +
            TprmCampaign::where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $clickRate = $this->getClickRateForPeriod($companyId, $startDate, $endDate);
        $reportRate = $this->getReportRateForPeriod($companyId, $startDate, $endDate);

        return [
            'total_simulations' => $totalSimulations,
            'click_rate' => $clickRate,
            'report_rate' => $reportRate
        ];
    }

    private function getClickRateForPeriod($companyId, $startDate, $endDate)
    {
        $clicks =
            CampaignLive::where('company_id', $companyId)
            ->where('payload_clicked', 1)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count() +
            WaLiveCampaign::where('company_id', $companyId)
            ->where('payload_clicked', 1)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count() +
            QuishingLiveCamp::where('company_id', $companyId)
            ->where('qr_scanned', '1')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count() +
            TprmCampaignLive::where('company_id', $companyId)
            ->where('payload_clicked', 1)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $total =
            CampaignLive::where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count() +
            WaLiveCampaign::where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count() +
            QuishingLiveCamp::where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count() +
            TprmCampaignLive::where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        return $total > 0 ? round(($clicks / $total) * 100, 2) : 0.0;
    }

    private function getReportRateForPeriod($companyId, $startDate, $endDate)
    {
        $reports =
            CampaignLive::where('company_id', $companyId)
            ->where('email_reported', 1)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count() +
            QuishingLiveCamp::where('company_id', $companyId)
            ->where('email_reported', '1')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count() +
            TprmCampaignLive::where('company_id', $companyId)
            ->where('email_reported', 1)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $total =
            CampaignLive::where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count() +
            QuishingLiveCamp::where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count() +
            TprmCampaignLive::where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        return $total > 0 ? round(($reports / $total) * 100, 2) : 0.0;
    }

    private function getTrainingFrequency($companyId, $startDate, $endDate)
    {
        $trainingAssignments = TrainingAssignedUser::where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $monthsDiff = $startDate->diffInMonths($endDate) ?: 1;

        return round($trainingAssignments / $monthsDiff, 2);
    }

    private function calculateComplianceScore($trainingMetrics, $simulationMetrics, $framework)
    {
        $weights = [
            'SOC_2' => ['training' => 0.6, 'simulation' => 0.4],
            'ISO_27001' => ['training' => 0.7, 'simulation' => 0.3],
            'HIPAA' => ['training' => 0.8, 'simulation' => 0.2],
            'GDPR' => ['training' => 0.7, 'simulation' => 0.3],
            'PDPL_SAUDI' => ['training' => 0.7, 'simulation' => 0.3],
            'PDPL_UAE' => ['training' => 0.65, 'simulation' => 0.35],
            'PDPL_OMAN' => ['training' => 0.75, 'simulation' => 0.25],
            'PDPL_JORDAN' => ['training' => 0.7, 'simulation' => 0.3],
            'NIST_SP_800_50' => ['training' => 0.5, 'simulation' => 0.3, 'reporting' => 0.2],
            'NIST_SP_800_53' => ['training' => 0.6, 'simulation' => 0.4],
            'PCI_DSS' => ['training' => 0.7, 'simulation' => 0.3],
            'ISO_20000' => ['training' => 0.8, 'simulation' => 0.2],
            'QCSF' => ['training' => 0.7, 'simulation' => 0.3],
            'OCERT' => ['training' => 0.5, 'simulation' => 0.5],
        ];

        $weight = $weights[$framework] ?? ['training' => 0.5, 'simulation' => 0.5];

        // Use passed training metrics if available, otherwise fall back to overall company metrics
        if (!empty($trainingMetrics) && isset($trainingMetrics['completion_rate'])) {
            $trainingScore = $trainingMetrics['completion_rate'];
        } else {
            $companyId = Auth::user()->company_id;
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

            $trainingScore = $totalEmployees > 0 ? ($trainedEmployees / $totalEmployees) * 100 : 0;
        }

        $simulationScore = isset($simulationMetrics['total_simulations']) && $simulationMetrics['total_simulations'] > 0 ? 100 : 0;
        $reportingScore = 100; // Platform has reporting capability

        $score = ($trainingScore * $weight['training']) + ($simulationScore * $weight['simulation']);
        
        // Add reporting component for NIST SP 800-50
        if ($framework === 'NIST_SP_800_50' && isset($weight['reporting'])) {
            $score += ($reportingScore * $weight['reporting']);
        }

        return min(100, round($score, 2));
    }
}
