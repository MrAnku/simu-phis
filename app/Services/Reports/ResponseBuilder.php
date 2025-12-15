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

    public static function error(string $message, int $statusCode = 500): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $statusCode);
    }
}
