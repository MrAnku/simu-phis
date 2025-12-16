<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use App\Repositories\ReportRepository;
use App\Services\Reports\ResponseBuilder;
use App\Services\Reports\MetricCalculator;

/**
 * Service for generating Awareness and Education reports.
 */
class AwarenessReportService
{
    protected ReportRepository $repository;

    /**
     * @param ReportRepository $repository
     */
    public function __construct(ReportRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Generates the Awareness and Education Report.
     * Aggregates training statistics, license usage, and user-wise details.
     *
     * @return JsonResponse
     */
    public function fetchAwarenessEduReporting(): JsonResponse
    {
        try {

            $companyId = (string) (Auth::user()->company_id ?? '');
            if ($companyId === '') {
                return ResponseBuilder::awarenessEmpty();
            }

            // ===== COUNTS =====
            // Fetch all counts in a single query using conditional aggregation
            $trainingCounts = $this->repository->getTrainingCounts($companyId);

            $totalAssignedUsers = $trainingCounts->total ?? 0;
            $notStarted = $trainingCounts->not_started ?? 0;
            $inProgress = $trainingCounts->in_progress ?? 0;
            $completed = $trainingCounts->completed ?? 0;
            $educatedUsers = $trainingCounts->educated ?? 0;

            // ===== TRAINING METRICS =====
            $trainingStats = MetricCalculator::trainingProgressMetrics(
                $totalAssignedUsers,
                $notStarted,
                $inProgress,
                $completed
            );

            // ===== EDUCATED USERS =====
            // Already calculated above
            // $educatedUsers = ...

            // ===== LICENSE =====
            $license = $this->repository->getCompanyLicense($companyId);

            $totalEmployees = MetricCalculator::totalEmployees($license);

            $generalStats = MetricCalculator::generalStatistics(
                $totalAssignedUsers,
                $educatedUsers,
                $completed,
                $totalEmployees
            );

            // ===== DURATION =====
            $durationStats = MetricCalculator::durationStatistics($companyId);

            // ===== USER-WISE DETAILS =====
            $onboardingDetails = $this->userWiseTrainingDetails($companyId);

            return ResponseBuilder::awarenessSuccess(
                $trainingStats,
                $generalStats,
                $durationStats,
                $onboardingDetails
            );
        } catch (\Throwable $e) {
            return ResponseBuilder::error($e->getMessage());
        }
    }

    /**
     * User-wise training details.
     * Optimized with eager loading to prevent N+1 queries on TrainingModule relationship.
     *
     * @param string $companyId
     * @return array User details with their assigned trainings
     */
    private function userWiseTrainingDetails(string $companyId): array
    {
        // 1. Fetch all training assignments for this company, eager loading the 'trainingData' relationship
        //    to access name, passing_score etc. without extra queries.
        $allTrainings = $this->repository->getAllTrainingAssignments($companyId)
            ->groupBy('user_email');

        // 2. Fetch all users
        $users = $this->repository->getCompanyUsers($companyId);

        $result = [];

        foreach ($users as $user) {
            // Get trainings for this user from the collection in memory
            $trainings = $allTrainings->get($user->user_email, collect());

            $assignedTrainings = [];

            foreach ($trainings as $training) {
                // $trainingData is now eager loaded on $training
                $trainingData = $training->trainingData;

                $assignedTrainings[] = [
                    'training_name' => $trainingData->name ?? '',
                    'training_type' => $training->training_type ?? '',
                    'assigned_date' => $training->assigned_date,
                    'training_started' => $training->training_started ? 'Yes' : 'No',
                    'passing_score' => $trainingData->passing_score ?? 0,
                    'training_due_date' => $training->training_due_date,
                    'is_completed' => $training->completed ? 'Yes' : 'No',
                    'completion_date' => $training->completion_date,
                    'personal_best' => $training->personal_best ?? 0,
                    'certificate_id' => $training->certificate_id ?? '',
                    'last_reminder_date' => $training->last_reminder_date,
                    'status' => $training->completed ? 'Completed' : 'In Progress',
                ];
            }

            $result[] = [
                'user_name' => $user->user_name ?? '',
                'user_email' => $user->user_email ?? '',
                'total_assigned_trainings' => $trainings->count(),
                'assigned_trainings' => $assignedTrainings,
                'total_completed_trainings' => $trainings->where('completed', 1)->count(),
                'total_certificates' => $trainings->whereNotNull('certificate_id')->count(),
                'count_of_outstanding_training' => $trainings->where('personal_best', '>=', 70)->count(),
            ];
        }

        return $result;
    }
}
