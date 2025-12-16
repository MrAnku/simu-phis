<?php

namespace App\Services\Reports;

use Illuminate\Http\JsonResponse;

class ResponseBuilder
{
    public static function divisionUsersSuccess(
        int $totalDivisions,
        array $details,
        array $campaigns
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => __('Division users report fetched successfully'),
            'data' => [
                'total_divisions' => $totalDivisions,
                'division_group_details' => $details,
                'campaigns_last_7_days' => $campaigns,
            ]
        ], 200);
    }

    public static function divisionUsersEmpty(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => __('No users found for this company'),
            'data' => [
                'total_divisions' => 0,
                'division_group_details' => [],
                'campaigns_last_7_days' => [],
            ]
        ], 200);
    }


    public static function awarenessSuccess(
        array $trainingStats,
        array $generalStats,
        array $durationStats,
        array $onboardingDetails
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => __('Awareness and Education report fetched successfully'),
            'data' => [
                'training_statistics' => $trainingStats,
                'general_statistics' => $generalStats,
                'education_duration_statistics' => $durationStats,
                'onboardingTrainingDetails' => $onboardingDetails,
            ]
        ], 200);
    }

    public static function awarenessEmpty(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => __('No data found'),
            'data' => [
                'training_statistics' => [],
                'general_statistics' => [],
                'education_duration_statistics' => [],
                'onboardingTrainingDetails' => [],
            ]
        ], 200);
    }
    public static function trainingReportSuccess(
        array $overall,
        array $modules,
        array $games,
        $recent,
        array $summary
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => 'Advanced training report fetched successfully',
            'data' => [
                'overall_statistics' => $overall,
                'training_modules' => $modules,
                'game_trainings' => $games,
                'recent_activities' => $recent,
                'summary' => $summary,
            ]
        ], 200);
    }

    public static function error(string $msg): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $msg,
        ], 500);
    }
}
