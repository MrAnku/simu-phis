<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\Auth;
use App\Services\Reports\ResponseBuilder;
use App\Services\Reports\MetricCalculator;
use App\Models\TrainingModule;
use App\Models\TrainingGame;
use App\Models\TrainingAssignedUser;
use App\Models\ScormAssignedUser;

/**
 * Service for generating Training reports.
 */
class TrainingReportService
{
    /**
     * Fetch the advanced training report.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function fetchTrainingReport()
    {
        try {
            $companyId = Auth::user()->company_id;

            // Get all training modules
            $trainingModules = TrainingModule::where(function ($query) use ($companyId) {
                $query->where('company_id', $companyId)
                    ->orWhere('company_id', 'default');
            })
                ->whereHas('trainingAssigned')
                ->get();

            // Get all training games
            $trainingGames = TrainingGame::get();

            $trainings = [];
            $overallStats = MetricCalculator::initialTrainingOverallStats();

            // Process training modules
            foreach ($trainingModules as $trainingModule) {
                $calc = MetricCalculator::calculateTrainingModuleStats($trainingModule, $companyId);

                $trainings[] = $calc['data'];

                MetricCalculator::mergeOverallStats($overallStats, $calc['stats']);

                // Update specific type counts in overall stats
                if ($calc['stats']['type'] === 'gamified') {
                    $overallStats['gamified_training_users'] += $calc['stats']['total'];
                } else {
                    $overallStats['static_training_users'] += $calc['stats']['total'];
                }
            }

            // Process SCORM
            $overallStats['scorm_training_users'] = ScormAssignedUser::where('company_id', $companyId)->count();

            // Process AI Training
            $overallStats['ai_training_users'] = TrainingAssignedUser::where('training_type', 'ai_training')
                ->where('company_id', $companyId)
                ->count();

            // Process Training Games
            $gameTrainings = MetricCalculator::processGames($trainingGames, $companyId, $overallStats);

            // Finalize Rates
            MetricCalculator::finalOverallRates($overallStats);

            // Fetch Recent Activities
            $recentActivities = MetricCalculator::fetchRecentActivities($companyId);

            // Calculate Summary
            $summary = MetricCalculator::calculateSummary($trainings, $gameTrainings);

            return ResponseBuilder::trainingReportSuccess(
                $overallStats,
                $trainings,
                $gameTrainings,
                $recentActivities,
                $summary
            );
        } catch (\Exception $e) {
            return ResponseBuilder::error($e->getMessage());
        }
    }
}
