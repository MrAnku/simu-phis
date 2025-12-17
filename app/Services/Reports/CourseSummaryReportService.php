<?php

namespace App\Services\Reports;

use App\Models\TrainingModule;
use App\Models\ScormTraining;
use App\Models\TrainingAssignedUser;
use App\Models\ScormAssignedUser;
use App\Services\CompanyReport;
use Illuminate\Support\Facades\DB;
use App\Services\Reports\MetricCalculator;

class CourseSummaryReportService
{
    public function fetchCourseSummaryReport(string $companyId): array
    {
        $totalCourses = TrainingModule::where('company_id', $companyId)
            ->orWhere('company_id', 'default')
            ->count();

        $totalScormCourses = ScormTraining::where('company_id', $companyId)
            ->count();

        //  Get assigned courses and scorm courses
        $assignedCourses = [
            'assigned_courses' => TrainingAssignedUser::where('company_id', $companyId)
                ->where('completed', 0)
                ->count(),
            'assigned_scorm_courses' => ScormAssignedUser::where('company_id', $companyId)
                ->where('completed', 0)
                ->count()
        ];

        $mostAssignedCoursesData = TrainingAssignedUser::where('company_id', $companyId)
            ->select('training', DB::raw('COUNT(*) as assignment_count'))
            ->groupBy('training')
            ->having('assignment_count', '>', 4)
            ->orderBy('assignment_count', 'desc')
            ->get();
        $mostAssignedCourses = MetricCalculator::formatMostAssignedCourses($mostAssignedCoursesData);


        $courseDetailsData = TrainingAssignedUser::where('company_id', $companyId)
            ->select(
                'training',
                DB::raw('SUM(CASE WHEN completed = 0 THEN 1 ELSE 0 END) as total_assigned'),
                DB::raw('SUM(CASE WHEN training_started = 1 AND completed = 0 THEN 1 ELSE 0 END) as total_in_progress'),
                DB::raw('SUM(CASE WHEN training_started = 0 AND completed = 0 THEN 1 ELSE 0 END) as total_not_started'),
                DB::raw('SUM(CASE WHEN completed = 1 THEN 1 ELSE 0 END) as total_completed'),
                DB::raw('AVG(CASE WHEN personal_best > 0 THEN personal_best ELSE NULL END) as avg_score')
            )
            ->groupBy('training')
            ->get();
        $courseDetails = MetricCalculator::formatCourseDetails($courseDetailsData);

        $totalOverDueCourses = TrainingAssignedUser::where('company_id', $companyId)
            ->where('completed', 0)
            ->where('training_due_date', '<', date('Y-m-d'))
            ->count();

        $totalComplete = TrainingAssignedUser::where('company_id', $companyId)
            ->where('completed', 1)
            ->count();

        $companyReport = new CompanyReport($companyId);
        $completionRate =  $companyReport->trainingCompletionRate();
        $completionTrendOverTime = $companyReport->getTrainingCompletionTrend();

        $topPerformedCoursesData = TrainingAssignedUser::where('company_id', $companyId)
            ->where('completed', 1)
            ->where('personal_best', '>', 90)
            ->select('training', DB::raw('AVG(personal_best) as average_score'))
            ->groupBy('training')
            ->having('average_score', '>=', 90)
            ->orderBy('average_score', 'desc')
            ->get();
        $topPerformedCourses = MetricCalculator::formatPerformedCourses($topPerformedCoursesData);


        $worstPerformedCoursesData = TrainingAssignedUser::where('company_id', $companyId)
            ->where('personal_best', '<', 30)
            ->select('training', DB::raw('AVG(personal_best) as average_score'))
            ->groupBy('training')
            ->having('average_score', '<=', 30)
            ->orderBy('average_score', 'asc')
            ->get();
        $worstPerformedCourses = MetricCalculator::formatPerformedCourses($worstPerformedCoursesData);

        $quicklyCompletedCoursesData = TrainingAssignedUser::where('company_id', $companyId)
            ->where('completed', 1)
            ->select('training', DB::raw('AVG(TIMESTAMPDIFF(SECOND, assigned_date, completion_date)) as avg_completion_time'))
            ->groupBy('training')
            ->orderBy('avg_completion_time', 'asc')
            ->get();
        $quicklyCompletedCourses = MetricCalculator::formatQuicklyCompletedCourses($quicklyCompletedCoursesData);

        $certificatesAwardedData = TrainingAssignedUser::where('company_id', $companyId)
            ->whereNotNull('certificate_id')
            ->whereNotNull('certificate_path')
            ->select('training', DB::raw('COUNT(*) as certificate_count'))
            ->groupBy('training')
            ->orderBy('certificate_count', 'desc')
            ->get();
        $certificatesAwarded = MetricCalculator::formatCertificatesAwarded($certificatesAwardedData);

        $badgesAwardedData = TrainingAssignedUser::where('company_id', $companyId)
            ->whereNotNull('badge')
            ->whereNotIn('badge', ['', '[]', 'null'])
            ->get();
        $badgesAwarded = MetricCalculator::formatBadgesAwarded($badgesAwardedData);

        return [
            'total_courses' => $totalCourses,
            'total_scorm_courses' => $totalScormCourses,
            'assigned_courses' => $assignedCourses,
            'most_assigned_courses' => $mostAssignedCourses,
            'course_details' => $courseDetails,
            'total_overdue_courses' => $totalOverDueCourses,
            'total_completed' => $totalComplete,
            'completion_rate' => $completionRate,
            'completion_trend_over_time' => $completionTrendOverTime,
            'top_performed_courses' => $topPerformedCourses,
            'worst_performed_courses' => $worstPerformedCourses,
            'quickly_completed_courses' => $quicklyCompletedCourses,
            'certificates_awarded' => $certificatesAwarded,
            'badges_awarded' => $badgesAwarded
        ];
    }
}
