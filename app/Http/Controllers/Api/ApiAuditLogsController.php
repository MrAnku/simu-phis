<?php

namespace App\Http\Controllers\Api;

use App\Models\Users;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use App\Models\BlueCollarEmployee;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ApiAuditLogsController extends Controller
{

    public function fetchAuditLogs(Request $request)
    {
        try {
            $query = AuditLog::where('company_id', Auth::user()->company_id);

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
            } elseif ($request->filled('start_date')) {
                $query->where('created_at', '>=', $request->start_date . ' 00:00:00');
            } elseif ($request->filled('end_date')) {
                $query->where('created_at', '<=', $request->end_date . ' 23:59:59');
            }

            $logs = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $logs,
                'message' => __('Audit logs fetched successfully.')
            ], 200);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'success' => false,
                'message' => __('An error occurred: ') . $e->getMessage()
            ], 500);
        }
    }

    public function fetchAuditActions()
    {
        try {
            $actions = [
                'EMAIL_CAMPAIGN_SIMULATED',
                'QUISHING_CAMPAIGN_SIMULATED',
                'WHATSAPP_CAMPAIGN_SIMULATED',
                'AI_CAMPAIGN_SIMULATED',
                'POLICY_CAMPAIGN_SIMULATED',
                'INFOGRAPHICS_CAMPAIGN_SIMULATED',
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
                'message' => __('Audit actions fetched successfully.')
            ], 200);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'success' => false,
                'message' => __('An error occurred: ') . $e->getMessage()
            ], 500);
        }
    }

    public function searchUsers(Request $request)
    {
        try {
            $search = $request->query('q');

            $normalUsers = $this->getNormalUsers($search);

            $blueCollarUsers = $this->getBlueCollarUsers($search);

            return response()->json([
                'success' => true,
                'data' => [
                    'normal_users' => $normalUsers,
                    'blue_collar_users' => $blueCollarUsers
                ],
                'message' => __('Users fetched successfully.')
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('An error occurred: ') . $e->getMessage()
            ], 500);
        }
    }

    private function getNormalUsers($search)
    {
        // Normal users (email)
        $normalUsersQuery = Users::select('user_email')
            ->whereNotNull('user_email');

        if ($search) {
            $normalUsersQuery->where('user_email', 'like', "%{$search}%");
        }

        $normalUsers = $normalUsersQuery->distinct()
            ->limit(10)
            ->get()
            ->map(function ($user) {
                return [
                    'user_email' => $user->user_email
                ];
            });

        return $normalUsers;
    }

    private function getBlueCollarUsers($search)
    {
        $blueCollarQuery = BlueCollarEmployee::select('whatsapp')
            ->whereNotNull('whatsapp');

        if ($search) {
            $blueCollarQuery->where('whatsapp', 'like', "%{$search}%");
        }

        $blueCollarUsers = $blueCollarQuery->distinct()
            ->limit(10)
            ->get()
            ->map(function ($user) {
                return [
                    'whatsapp' => $user->whatsapp
                ];
            });

        return $blueCollarUsers;
    }
}
