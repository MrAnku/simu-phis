<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class ApiAuditLogsController extends Controller
{

    public function fetchAuditLogs(Request $request)
    {
        try {
            $query = AuditLog::query();

            // User filter (by email or whatsapp) - apply first if set
            if ($request->filled('user')) {
                $query->where(function ($q) use ($request) {
                    $q->where('user_email', $request->user)
                        ->orWhere('user_whatsapp', $request->user);
                });
            }

            // User type filter
            if ($request->filled('user_type')) {
                $query->where('user_type', $request->user_type);
            }

            // Action type filter
            if ($request->filled('action')) {
                $query->where('action', $request->action);
            }

            // Date range filter
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->whereBetween('created_at', [
                    $request->start_date . ' 00:00:00',
                    $request->end_date . ' 23:59:59'
                ]);
            }

            // Optional: Add pagination
            $logs = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $logs,
                'message' => 'Audit logs fetched successfully.'
            ], 200);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function fetchAuditActions()
    {
        try {
            $actions = [
                'EMAIL_CAMPAIGN_SENT',
                'QUISHING_CAMPAIGN_SENT',
                'WHATSAPP_CAMPAIGN_SENT',
                'AI_CAMPAIGN_SENT',
                'POLICY_CAMPAIGN_SENT',
                'INFOGRAPHICS_CAMPAIGN_SENT',
                'EMPLOYEE_COMPROMISED',
                'SCORM_ASSIGNED',
                'TRAINING_ASSIGNED',
                'POLICY_ASSIGNED',
                'POLICY_ACCEPTED',
                'CERTIFICATE_AWARDED',
                'TRAINING_COMPLETED'
            ];

            return response()->json([
                'success' => true,
                'data' => $actions,
                'message' => 'Audit actions fetched successfully.'
            ], 200);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }
}
