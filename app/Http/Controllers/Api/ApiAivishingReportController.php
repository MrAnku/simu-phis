<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Users;
use App\Models\TprmCampaign;
use Illuminate\Http\Request;
use App\Models\PhishingEmail;
use App\Models\UsersGroup;
use App\Models\AiCallCampLive;
use App\Http\Controllers\Controller;
use App\Models\AiCallAgent;
use Illuminate\Support\Facades\Auth;

class ApiAivishingReportController extends Controller
{
    public function aivishingSimulationReport(Request $request)
    {
        $companyId = Auth::user()->company_id;

        if ($request->query('users_group') && $request->query('months')) {

            $group = $request->query('users_group');
            $months = $request->query('months');
            $months = (int)$months;

            $usersArray = UsersGroup::where('group_id', $group)
                ->where('company_id', $companyId)->first()->users;
            $usersArray = json_decode($usersArray, true);

            if (!$usersArray) {
                return response()->json([
                    'success' => false,
                    'message' => 'No users found for the specified group',
                ], 404);
            }

            $startDate = now()->subMonths($months)->startOfMonth();
            $endDate = now();

            $total = AiCallCampLive::where('company_id', $companyId)
                ->whereIn('user_id', $usersArray)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            $callsInQueue = AiCallCampLive::where('company_id', $companyId)
                ->where('call_id', null)
                ->whereIn('user_id', $usersArray)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();
            $callsInProgress = AiCallCampLive::where('company_id', $companyId)
                ->where('status', 'waiting')
                ->whereIn('user_id', $usersArray)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();
            $callsCompleted = AiCallCampLive::where('company_id', $companyId)
                ->where('status', 'completed')
                ->whereIn('user_id', $usersArray)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            $transcriptionsLogged = AiCallCampLive::where('company_id', $companyId)
                ->where('call_report', '!=', null)
                ->whereIn('user_id', $usersArray)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            return response()->json([
                'success' => true,
                'message' => 'Ai vishing report retrieved successfully',
                'data' => [
                    "cards" => [
                        'total' => $total,
                        'calls_in_queue' => $callsInQueue,
                        'calls_in_progress' => $callsInProgress,
                        'calls_completed' => $callsCompleted,
                        'transcriptions_logged' => $transcriptionsLogged,
                    ],
                    "call_events_overtime" => $this->eventsOverTime($usersArray, $months), //done
                    "most_engaged_agent" => $this->mostEngagedAgent($usersArray, $months), //done
                    "grouped_simulation_statistics" => $this->groupedSimulationStatistics($group, $months), //done
                    "employee_simulation_events" => $this->empSimulationEvents($usersArray, $months), //done
                    "call_analytics" => $this->callAnalytics($usersArray, $months), //done
                    "call_response_in_week_days" => $this->callResponseInWeekDays($usersArray, $months), //done
                ]
            ], 200);
        } else {
            $total = AiCallCampLive::where('company_id', $companyId)
                ->count();

            $callsInQueue = AiCallCampLive::where('company_id', $companyId)
                ->where('call_id', null)
                ->count();
            $callsInProgress = AiCallCampLive::where('company_id', $companyId)
                ->where('status', 'waiting')
                ->count();
            $callsCompleted = AiCallCampLive::where('company_id', $companyId)
                ->where('status', 'completed')
                ->count();

            $transcriptionsLogged = AiCallCampLive::where('company_id', $companyId)
                ->where('call_report', '!=', null)
                ->count();

            return response()->json([
                'success' => true,
                'message' => 'Ai vishing report retrieved successfully',
                'data' => [
                    "cards" => [
                        'total' => $total,
                        'calls_in_queue' => $callsInQueue,
                        'calls_in_progress' => $callsInProgress,
                        'calls_completed' => $callsCompleted,
                        'transcriptions_logged' => $transcriptionsLogged,
                    ],
                    "call_events_overtime" => $this->eventsOverTime(), //done
                    "most_engaged_agent" => $this->mostEngagedAgent(), //done
                    "grouped_simulation_statistics" => $this->groupedSimulationStatistics(),
                    "employee_simulation_events" => $this->empSimulationEvents(),
                    "call_analytics" => $this->callAnalytics(),
                    "call_response_in_week_days" => $this->callResponseInWeekDays()
                ]
            ], 200);
        }
    }

    private function ppDifference()
    {
        $companyId = Auth::user()->company_id;
        $now = Carbon::now();
        $currentStart = $now->copy()->subDays(7);  // Last 7 days
        $previousStart = $now->copy()->subDays(14); // Previous 7 days
        $previousEnd = $now->copy()->subDays(7);

        // Current period
        $totalCurrent = AiCallCampLive::where('company_id', $companyId)
            ->whereBetween('created_at', [$currentStart, $now])
            ->count();

        $clickedCurrent = AiCallCampLive::where('company_id', $companyId)
            ->where('payload_clicked', 1)
            ->whereBetween('created_at', [$currentStart, $now])
            ->count();

        $clickRateCurrent = $totalCurrent > 0 ? ($clickedCurrent / $totalCurrent) * 100 : 0;

        // Previous period
        $totalPrevious = AiCallCampLive::where('company_id', $companyId)
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->count();

        $clickedPrevious = AiCallCampLive::where('company_id', $companyId)
            ->where('payload_clicked', 1)
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->count();

        $clickRatePrevious = $totalPrevious > 0 ? ($clickedPrevious / $totalPrevious) * 100 : 0;

        // Percentage Point Difference
        $ppDifference = $clickRateCurrent - $clickRatePrevious;

        // Format result
        $ppFormatted = number_format($ppDifference, 2) . ' pp';
        return $ppFormatted;
    }
    private function mostEngagedAgent($usersArray = null, $months = null)
    {

        $companyId = Auth::user()->company_id;

        $query = AiCallCampLive::where('company_id', $companyId);

        if ($usersArray && $months) {
            $startDate = now()->subMonths($months)->startOfMonth();
            $endDate = now();
            $query = $query->whereIn('user_id', $usersArray)
                ->whereBetween('created_at', [$startDate, $endDate]);
        }

        $agentStats = $query->whereNotNull('agent_id')
            ->selectRaw('agent_id, COUNT(*) as total_calls')
            ->groupBy('agent_id')
            ->orderByDesc('total_calls')
            ->get();

        if ($agentStats->isEmpty()) {
            return null;
        }

        // Return all agents with their call counts and names
        return $agentStats->map(function ($stat) {
            $agent = AiCallAgent::where('agent_id', $stat->agent_id)->first();
            return [
                'agent_id' => $stat->agent_id,
                'agent_name' => $agent ? $agent->agent_name : null,
                'total_calls' => $stat->total_calls
            ];
        })->values();

        // if (!$agentStats) {
        //     return null;
        // }

        // $agent = AiCallAgent::where('agent_id', $agentStats->agent_id)
        //     ->first();

        // return [
        //     'agent_id' => $agentStats->agent_id,
        //     'agent_name' => $agent ? $agent->agent_name : null,
        //     'total_calls' => $agentStats->total_calls
        // ];
    }
    private function eventsOverTime($usersArray = null, $months = null)
    {
        $companyId = Auth::user()->company_id;
        $now = Carbon::now();
        $chartData = [];

        if ($usersArray && $months) {


            for ($i = 0; $i < (int)$months; $i++) {
                $monthDate = $now->copy()->subMonthsNoOverflow($i);
                $monthStart = $monthDate->copy()->startOfMonth();
                $monthEnd = $monthDate->copy()->endOfMonth();

                $total = AiCallCampLive::where('company_id', $companyId)
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->whereIn('user_id', $usersArray)
                    ->count();

                $fellForSimulation = 0;
                $transcriptions = 0;
                $endResponses = AiCallCampLive::where('company_id', $companyId)
                    ->where('call_end_response', '!=', null)
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->whereIn('user_id', $usersArray)
                    ->get();
                if ($endResponses->isNotEmpty()) {
                    foreach ($endResponses as $response) {
                        $responseJson = json_decode($response->call_end_response, true);
                        if (isset($responseJson['args']['fell_for_simulation']) && $responseJson['args']['fell_for_simulation'] === true) {
                            $fellForSimulation++;
                        }

                        if (isset($responseJson['call']['transcript'])) {
                            $transcriptions++;
                        }
                    }
                }


                $inProgress = AiCallCampLive::where('company_id', $companyId)
                    ->where('status', 'waiting')
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->whereIn('user_id', $usersArray)
                    ->count();

                $callIgnored = AiCallCampLive::where('company_id', $companyId)
                    ->where('status', 'waiting')
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->whereIn('user_id', $usersArray)
                    ->whereRaw('TIMESTAMPDIFF(MINUTE, created_at, NOW()) > 30')
                    ->count();

                $fellForSimulationRate = $total > 0 ? round(($fellForSimulation / $total) * 100, 2) : 0;
                $transcriptionsRate = $total > 0 ? round(($transcriptions / $total) * 100, 2) : 0;
                $callIgnoredRate = $total > 0 ? round(($callIgnored / $total) * 100, 2) : 0;
                // Example target rates, adjust as needed
                $targetFellForSimulationRate = 5;
                $targetTranscriptionsRate = 40;
                $targetCallIgnoredRate = 40;


                $chartData[] = [
                    'month' => $monthDate->format('F Y'),
                    'total' => $total,
                    'fellForSimulation' => $fellForSimulation,
                    'fellForSimulationRate' => $fellForSimulationRate,
                    'targetFellForSimulationRate' => $targetFellForSimulationRate,
                    'transcriptions_available' => $transcriptions,
                    'transcriptionsRate' => $transcriptionsRate,
                    'targetTranscriptionsRate' => $targetTranscriptionsRate,
                    'inProgress' => $inProgress,
                    'callIgnoredRate' => $callIgnoredRate,
                    'targetCallIgnoredRate' => $targetCallIgnoredRate,

                ];
            }
        } else {
            for ($i = 0; $i < 5; $i++) {
                $monthDate = $now->copy()->subMonthsNoOverflow($i);
                $monthStart = $monthDate->copy()->startOfMonth();
                $monthEnd = $monthDate->copy()->endOfMonth();

                $total = AiCallCampLive::where('company_id', $companyId)
                    ->count();

                $fellForSimulation = 0;
                $transcriptions = 0;
                $endResponses = AiCallCampLive::where('company_id', $companyId)
                    ->where('call_end_response', '!=', null)
                    ->get();
                if ($endResponses->isNotEmpty()) {
                    foreach ($endResponses as $response) {
                        $responseJson = json_decode($response->call_end_response, true);
                        if (isset($responseJson['args']['fell_for_simulation']) && $responseJson['args']['fell_for_simulation'] === true) {
                            $fellForSimulation++;
                        }

                        if (isset($responseJson['call']['transcript'])) {
                            $transcriptions++;
                        }
                    }
                }


                $inProgress = AiCallCampLive::where('company_id', $companyId)
                    ->where('status', 'waiting')
                    ->count();

                $callIgnored = AiCallCampLive::where('company_id', $companyId)
                    ->where('status', 'waiting')
                    ->whereRaw('TIMESTAMPDIFF(MINUTE, created_at, NOW()) > 30')
                    ->count();

                $fellForSimulationRate = $total > 0 ? round(($fellForSimulation / $total) * 100, 2) : 0;
                $transcriptionsRate = $total > 0 ? round(($transcriptions / $total) * 100, 2) : 0;
                $callIgnoredRate = $total > 0 ? round(($callIgnored / $total) * 100, 2) : 0;
                // Example target rates, adjust as needed
                $targetFellForSimulationRate = 5;
                $targetTranscriptionsRate = 40;
                $targetCallIgnoredRate = 40;


                $chartData[] = [
                    'month' => $monthDate->format('F Y'),
                    'total' => $total,
                    'fellForSimulation' => $fellForSimulation,
                    'fellForSimulationRate' => $fellForSimulationRate,
                    'targetFellForSimulationRate' => $targetFellForSimulationRate,
                    'transcriptions_available' => $transcriptions,
                    'transcriptionsRate' => $transcriptionsRate,
                    'targetTranscriptionsRate' => $targetTranscriptionsRate,
                    'inProgress' => $inProgress,
                    'callIgnoredRate' => $callIgnoredRate,
                    'targetCallIgnoredRate' => $targetCallIgnoredRate,

                ];
            }
        }



        return array_reverse($chartData);
    }

    private function groupedSimulationStatistics($group = null, $months = null)
    {
        $companyId = Auth::user()->company_id;

        if ($group && $months) {
            // Fetch all campaigns for the company
            $groups = UsersGroup::with('aiCampaigns.individualCamps')
                ->where('company_id', $companyId)
                ->where('group_id', $group)
                ->get();
            if ($groups->isEmpty()) {
                return [];
            }
            return $groups->map(function ($group) {
                $total = $group->aiCampaigns->sum(function ($campaign) {
                    return $campaign->individualCamps->count();
                });
                $totalCallSent = $group->aiCampaigns->sum(function ($campaign) {
                    return $campaign->individualCamps->where('call_send_response', '!=', null)->count();
                });

                $fellForSimulation = 0;
                $transcriptions = 0;
                $endResponses = $group->aiCampaigns->filter(function ($campaign) {
                    return $campaign->individualCamps->contains(function ($camp) {
                        return $camp->call_end_response !== null;
                    });
                });
                if ($endResponses->isNotEmpty()) {
                    foreach ($endResponses as $response) {
                        $responseJson = json_decode($response->call_end_response, true);
                        if (isset($responseJson['args']['fell_for_simulation']) && $responseJson['args']['fell_for_simulation'] === true) {
                            $fellForSimulation++;
                        }

                        if (isset($responseJson['call']['transcript'])) {
                            $transcriptions++;
                        }
                    }
                }

                $inProgress = $group->aiCampaigns->sum(function ($campaign) {
                    return $campaign->individualCamps->where('status', 'waiting')->count();
                });
                $callIgnored = $group->aiCampaigns->sum(function ($campaign) {
                    return $campaign->individualCamps->where('status', 'waiting')
                        ->whereRaw('TIMESTAMPDIFF(MINUTE, created_at, NOW()) > 30')
                        ->count();
                });

                // $callIgnored = $group->aiCampaigns->where('status', 'waiting')
                //     ->whereRaw('TIMESTAMPDIFF(MINUTE, created_at, NOW()) > 30')
                //     ->count();

                return [
                    'group_name' => $group->group_name,
                    'total_calls_sent' => $totalCallSent,
                    'total_fell_for_simulation' => $fellForSimulation,
                    'fell_for_simulation_rate' => $total > 0 ? round(($fellForSimulation / $total) * 100, 2) : 0,
                    'transcriptions_available' => $transcriptions,
                    'transcriptions_rate' => $total > 0 ? round(($transcriptions / $total) * 100, 2) : 0,
                    'in_progress' => $inProgress,
                    'in_progress_rate' => $total > 0 ? round(($inProgress / $total) * 100, 2) : 0,
                    'call_ignored' => $callIgnored,
                    'call_ignored_rate' => $total > 0 ? round(($callIgnored / $total) * 100, 2) : 0,
                ];
            });
        } else {
            // Fetch all campaigns for the company
            $groups = UsersGroup::with('aiCampaigns.individualCamps')
                ->where('company_id', $companyId)
                ->get();
            if ($groups->isEmpty()) {
                return [];
            }
            return $groups->map(function ($group) {
                $total = $group->aiCampaigns->sum(function ($campaign) {
                    return $campaign->individualCamps->count();
                });
                $totalCallSent = $group->aiCampaigns->sum(function ($campaign) {
                    return $campaign->individualCamps->where('call_send_response', '!=', null)->count();
                });

                $fellForSimulation = 0;
                $transcriptions = 0;
                $endResponses = $group->aiCampaigns->flatMap(function ($campaign) {
                    return $campaign->individualCamps->filter(function ($camp) {
                        return !is_null($camp->call_end_response);
                    });
                });

                if ($endResponses->isNotEmpty()) {
                    foreach ($endResponses as $response) {
                        $responseJson = json_decode($response->call_end_response, true);
                        if (isset($responseJson['args']['fell_for_simulation']) && $responseJson['args']['fell_for_simulation'] === true) {
                            $fellForSimulation++;
                        }

                        if (isset($responseJson['call']['transcript'])) {
                            $transcriptions++;
                        }
                    }
                }

                $inProgress = $group->aiCampaigns->where('status', 'waiting')
                    ->count();

                $callIgnored = $group->aiCampaigns()->where('status', 'waiting')
                    ->whereRaw('TIMESTAMPDIFF(MINUTE, created_at, NOW()) > 30')
                    ->count();

                return [
                    'group_name' => $group->group_name,
                    'total_calls_sent' => $totalCallSent,
                    'total_fell_for_simulation' => $fellForSimulation,
                    'fell_for_simulation_rate' => $total > 0 ? round(($fellForSimulation / $total) * 100, 2) : 0,
                    'transcriptions_available' => $transcriptions,
                    'transcriptions_rate' => $total > 0 ? round(($transcriptions / $total) * 100, 2) : 0,
                    'in_progress' => $inProgress,
                    'in_progress_rate' => $total > 0 ? round(($inProgress / $total) * 100, 2) : 0,
                    'call_ignored_rate' => $total > 0 ? round(($callIgnored / $total) * 100, 2) : 0,
                ];
            });
        }
    }

    private function callAnalytics($usersArray = null, $months = null)
    {
        $companyId = Auth::user()->company_id;

        if ($usersArray && $months) {

            $startDate = now()->subMonths($months)->startOfMonth();
            $endDate = now();

            $totalCalls = AiCallCampLive::where('company_id', $companyId)
                ->whereIn('user_id', $usersArray)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            $pendingCalls = AiCallCampLive::where('company_id', $companyId)
                ->where('call_send_response', null)
                ->whereIn('user_id', $usersArray)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();
            $completedCalls = AiCallCampLive::where('company_id', $companyId)
                ->where('status', 'completed')
                ->whereIn('user_id', $usersArray)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();
            $waitingCalls = AiCallCampLive::where('company_id', $companyId)
                ->where('status', 'waiting')
                ->whereIn('user_id', $usersArray)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();
            $fellInSimulation = 0;
            $endResponses = AiCallCampLive::where('company_id', $companyId)
                ->where('call_end_response', '!=', null)
                ->whereIn('user_id', $usersArray)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();
            if ($endResponses->isNotEmpty()) {
                foreach ($endResponses as $response) {
                    $responseJson = json_decode($response->call_end_response, true);
                    if (isset($responseJson['args']['fell_for_simulation']) && $responseJson['args']['fell_for_simulation'] === true) {
                        $fellInSimulation++;
                    }
                }
            }

            return [
                'total_calls' => $totalCalls,
                'pending_calls' => $pendingCalls,
                'completed_calls' => $completedCalls,
                'waiting_calls' => $waitingCalls,
                'fell_in_simulation' => $fellInSimulation,

            ];
        } else {

            $totalCalls = AiCallCampLive::where('company_id', $companyId)
                ->count();

            $pendingCalls = AiCallCampLive::where('company_id', $companyId)
                ->where('call_send_response', null)
                ->count();
            $completedCalls = AiCallCampLive::where('company_id', $companyId)
                ->where('status', 'completed')
                ->count();
            $waitingCalls = AiCallCampLive::where('company_id', $companyId)
                ->where('status', 'waiting')
                ->count();
            $fellInSimulation = 0;
            $endResponses = AiCallCampLive::where('company_id', $companyId)
                ->where('call_end_response', '!=', null)
                ->get();
            if ($endResponses->isNotEmpty()) {
                foreach ($endResponses as $response) {
                    $responseJson = json_decode($response->call_end_response, true);
                    if (isset($responseJson['args']['fell_for_simulation']) && $responseJson['args']['fell_for_simulation'] === true) {
                        $fellInSimulation++;
                    }
                }
            }

            return [
                'total_calls' => $totalCalls,
                'pending_calls' => $pendingCalls,
                'completed_calls' => $completedCalls,
                'waiting_calls' => $waitingCalls,
                'fell_in_simulation' => $fellInSimulation,

            ];
        }
    }

    private function callResponseInWeekDays($usersArray = null, $months = null)
    {
        $companyId = Auth::user()->company_id;

        if ($usersArray && $months) {

            $startDate = now()->subMonths($months)->startOfMonth();
            $endDate = now();


            $weekDays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            $total = AiCallCampLive::where('company_id', $companyId)
                ->where('call_end_response', '!=', null)
                ->whereIn('user_id', $usersArray)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            $responseByDay = AiCallCampLive::where('company_id', $companyId)
                ->where('call_end_response', '!=', null)
                ->whereIn('user_id', $usersArray)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('DAYOFWEEK(created_at) as day, COUNT(*) as count')
                ->groupBy('day')
                ->pluck('count', 'day')
                ->toArray();

            $result = [];
            foreach ($weekDays as $i => $dayName) {
                // DAYOFWEEK returns 1 (Sunday) to 7 (Saturday)
                $dayIndex = $i + 1;
                $count = isset($responseByDay[$dayIndex]) ? $responseByDay[$dayIndex] : 0;
                $percent = $total > 0 ? round(($count / $total) * 100, 2) : 0;
                $result[] = [
                    'day' => $dayName,
                    'percentage' => $percent
                ];
            }

            return $result;
        } else {
            $weekDays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            $total = AiCallCampLive::where('company_id', $companyId)
                ->where('call_end_response', '!=', null)
                ->count();

            $responseByDay = AiCallCampLive::where('company_id', $companyId)
                ->where('call_end_response', '!=', null)
                ->selectRaw('DAYOFWEEK(created_at) as day, COUNT(*) as count')
                ->groupBy('day')
                ->pluck('count', 'day')
                ->toArray();

            $result = [];
            foreach ($weekDays as $i => $dayName) {
                // DAYOFWEEK returns 1 (Sunday) to 7 (Saturday)
                $dayIndex = $i + 1;
                $count = isset($responseByDay[$dayIndex]) ? $responseByDay[$dayIndex] : 0;
                $percent = $total > 0 ? round(($count / $total) * 100, 2) : 0;
                $result[] = [
                    'day' => $dayName,
                    'percentage' => $percent
                ];
            }

            return $result;
        }
    }
    private function empSimulationEvents($usersArray = null, $months = null)
    {
        $companyId = Auth::user()->company_id;

        if ($usersArray && $months) {
            $uniqueUsers = Users::where('company_id', $companyId)
                ->whereIn('id', $usersArray)
                ->get();
            if ($uniqueUsers->isEmpty()) {
                return [];
            }

            $campaignStats = [];
            $startDate = now()->subMonths($months)->startOfMonth();
            $endDate = now();

            foreach ($uniqueUsers as $user) {
                $userEmail = $user->user_email;

                $total = AiCallCampLive::where('company_id', $companyId)
                    ->where('employee_email', $userEmail)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count();

                $totalCallSent = AiCallCampLive::where('company_id', $companyId)
                    ->where('employee_email', $userEmail)
                    ->where('call_send_response', '!=', null)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count();

                $callsInQueue = AiCallCampLive::where('company_id', $companyId)
                    ->where('call_id', null)
                    ->where('employee_email', $userEmail)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count();
                $callsInProgress = AiCallCampLive::where('company_id', $companyId)
                    ->where('status', 'waiting')
                    ->where('employee_email', $userEmail)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count();
                $callsCompleted = AiCallCampLive::where('company_id', $companyId)
                    ->where('status', 'completed')
                    ->where('employee_email', $userEmail)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count();

                $transcriptionsLogged = AiCallCampLive::where('company_id', $companyId)
                    ->where('call_report', '!=', null)
                    ->where('employee_email', $userEmail)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count();

                $campaignStats[] = [
                    'user_email' => $userEmail,
                    'total_calls_sent' => $totalCallSent,
                    'calls_in_queue' => $callsInQueue,
                    'calls_in_progress' => $callsInProgress,
                    'calls_completed' => $callsCompleted,
                    'transcriptions_logged' => $transcriptionsLogged,
                    'total_calls' => $total,
                    'calls_in_queue_rate' => $total > 0 ? round(($callsInQueue / $total) * 100, 2) : 0,
                    'calls_in_progress_rate' => $total > 0 ? round(($callsInProgress / $total) * 100, 2) : 0,
                    'calls_completed_rate' => $total > 0 ? round(($callsCompleted / $total) * 100, 2) : 0,
                    'transcriptions_logged_rate' => $total > 0 ? round(($transcriptionsLogged / $total) * 100, 2) : 0,
                ];
            }

            return $campaignStats;
        } else {
            $uniqueUsers = Users::where('company_id', $companyId)
                ->select('user_email')
                ->distinct()
                ->get();

            $campaignStats = [];

            foreach ($uniqueUsers as $user) {
                $userEmail = $user->user_email;

                $total = AiCallCampLive::where('company_id', $companyId)
                    ->where('employee_email', $userEmail)
                    ->count();

                $totalCallSent = AiCallCampLive::where('company_id', $companyId)
                    ->where('employee_email', $userEmail)
                    ->where('call_send_response', '!=', null)
                    ->count();

                $callsInQueue = AiCallCampLive::where('company_id', $companyId)
                    ->where('call_id', null)
                    ->where('employee_email', $userEmail)
                    ->count();
                $callsInProgress = AiCallCampLive::where('company_id', $companyId)
                    ->where('status', 'waiting')
                    ->where('employee_email', $userEmail)
                    ->count();
                $callsCompleted = AiCallCampLive::where('company_id', $companyId)
                    ->where('status', 'completed')
                    ->where('employee_email', $userEmail)
                    ->count();

                $transcriptionsLogged = AiCallCampLive::where('company_id', $companyId)
                    ->where('call_report', '!=', null)
                    ->where('employee_email', $userEmail)
                    ->count();

                $campaignStats[] = [
                    'user_email' => $userEmail,
                    'total_calls_sent' => $totalCallSent,
                    'calls_in_queue' => $callsInQueue,
                    'calls_in_progress' => $callsInProgress,
                    'calls_completed' => $callsCompleted,
                    'transcriptions_logged' => $transcriptionsLogged,
                    'total_calls' => $total,
                    'calls_in_queue_rate' => $total > 0 ? round(($callsInQueue / $total) * 100, 2) : 0,
                    'calls_in_progress_rate' => $total > 0 ? round(($callsInProgress / $total) * 100, 2) : 0,
                    'calls_completed_rate' => $total > 0 ? round(($callsCompleted / $total) * 100, 2) : 0,
                    'transcriptions_logged_rate' => $total > 0 ? round(($transcriptionsLogged / $total) * 100, 2) : 0,
                ];
            }

            return $campaignStats;
        }
    }
    private function emotionalStatistics($group = null, $months = null)
    {

        $companyId = Auth::user()->company_id;

        if ($group && $months) {

            $startDate = now()->subMonths($months)->startOfMonth();
            $endDate = now();

            $campaigns = TprmCampaign::with('campaignActivity')
                ->where('company_id', $companyId)
                ->where('users_group', $group)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();
            if ($campaigns->isEmpty()) {
                return [];
            }
            $total = $campaigns->sum(function ($campaign) {
                return $campaign->campaignActivity->count();
            });
            $genuineEmail = 0;
            $showsInterestInPhishingEmail = 0;
            $looksSuspicious = 0;
            $totallySafe = 0;
            foreach ($campaigns as $campaign) {
                foreach ($campaign->campaignActivity as $activity) {
                    if ($activity->email_sent_at && $activity->email_viewed_at) {
                        $emailSentAt = Carbon::parse($activity->email_sent_at);
                        $emailViewedAt = Carbon::parse($activity->email_viewed_at);

                        $diffMinutes = $emailViewedAt->diffInMinutes($emailSentAt);
                        if ($diffMinutes <= 30 && $diffMinutes > 2) {
                            $genuineEmail++;
                        }
                    }
                }
                foreach ($campaign->campaignActivity as $activity) {
                    if ($activity->email_viewed_at && $activity->payload_clicked_at) {
                        $viewedAt = Carbon::parse($activity->email_viewed_at);
                        $payloadClickedAt = Carbon::parse($activity->payload_clicked_at);

                        $diffMinutes = $payloadClickedAt->diffInSeconds($viewedAt);
                        if ($diffMinutes <= 10 && $diffMinutes > 2) {
                            $showsInterestInPhishingEmail++;
                        }
                        if ($diffMinutes <= 120 && $diffMinutes > 10) {
                            $looksSuspicious++;
                        }
                    }
                }

                foreach ($campaign->campaignActivity as $activity) {
                    if ($activity->payload_clicked_at == null && $activity->compromised_at == null) {
                        $totallySafe++;
                    }
                }
            }

            // Calculate percentages
            $genuineEmailPercent = $total > 0 ? round(($genuineEmail / $total) * 100, 2) : 0;
            $showsInterestInPhishingEmailPercent = $total > 0 ? round(($showsInterestInPhishingEmail / $total) * 100, 2) : 0;
            $looksSuspiciousPercent = $total > 0 ? round(($looksSuspicious / $total) * 100, 2) : 0;
            $totallySafePercent = $total > 0 ? round(($totallySafe / $total) * 100, 2) : 0;
            return [
                'total' => $total,
                'genuineEmail' => $genuineEmail,
                'genuineEmailPercent' => $genuineEmailPercent,
                'showsInterestInPhishingEmail' => $showsInterestInPhishingEmail,
                'showsInterestInPhishingEmailPercent' => $showsInterestInPhishingEmailPercent,
                'looksSuspicious' => $looksSuspicious,
                'looksSuspiciousPercent' => $looksSuspiciousPercent,
                'totallySafe' => $totallySafe,
                'totallySafePercent' => $totallySafePercent
            ];
        } else {
            $campaigns = TprmCampaign::with('campaignActivity')
                ->where('company_id', $companyId)
                ->get();
            if ($campaigns->isEmpty()) {
                return [];
            }
            $total = $campaigns->sum(function ($campaign) {
                return $campaign->campaignActivity->count();
            });
            $genuineEmail = 0;
            $showsInterestInPhishingEmail = 0;
            $looksSuspicious = 0;
            $totallySafe = 0;
            foreach ($campaigns as $campaign) {
                foreach ($campaign->campaignActivity as $activity) {
                    if ($activity->email_sent_at && $activity->email_viewed_at) {
                        $emailSentAt = Carbon::parse($activity->email_sent_at);
                        $emailViewedAt = Carbon::parse($activity->email_viewed_at);

                        $diffMinutes = $emailViewedAt->diffInMinutes($emailSentAt);
                        if ($diffMinutes <= 30 && $diffMinutes > 2) {
                            $genuineEmail++;
                        }
                    }
                }
                foreach ($campaign->campaignActivity as $activity) {
                    if ($activity->email_viewed_at && $activity->payload_clicked_at) {
                        $viewedAt = Carbon::parse($activity->email_viewed_at);
                        $payloadClickedAt = Carbon::parse($activity->payload_clicked_at);

                        $diffMinutes = $payloadClickedAt->diffInSeconds($viewedAt);
                        if ($diffMinutes <= 10 && $diffMinutes > 2) {
                            $showsInterestInPhishingEmail++;
                        }
                        if ($diffMinutes <= 120 && $diffMinutes > 10) {
                            $looksSuspicious++;
                        }
                    }
                }

                foreach ($campaign->campaignActivity as $activity) {
                    if ($activity->payload_clicked_at == null && $activity->compromised_at == null) {
                        $totallySafe++;
                    }
                }
            }

            // Calculate percentages
            $genuineEmailPercent = $total > 0 ? round(($genuineEmail / $total) * 100, 2) : 0;
            $showsInterestInPhishingEmailPercent = $total > 0 ? round(($showsInterestInPhishingEmail / $total) * 100, 2) : 0;
            $looksSuspiciousPercent = $total > 0 ? round(($looksSuspicious / $total) * 100, 2) : 0;
            $totallySafePercent = $total > 0 ? round(($totallySafe / $total) * 100, 2) : 0;
            return [
                'total' => $total,
                'genuineEmail' => $genuineEmail,
                'genuineEmailPercent' => $genuineEmailPercent,
                'showsInterestInPhishingEmail' => $showsInterestInPhishingEmail,
                'showsInterestInPhishingEmailPercent' => $showsInterestInPhishingEmailPercent,
                'looksSuspicious' => $looksSuspicious,
                'looksSuspiciousPercent' => $looksSuspiciousPercent,
                'totallySafe' => $totallySafe,
                'totallySafePercent' => $totallySafePercent
            ];
        }
    }
}
