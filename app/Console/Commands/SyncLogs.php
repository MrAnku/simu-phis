<?php

namespace App\Console\Commands;

use App\Models\Users;
use App\Models\Company;
use App\Models\SiemLog;
use App\Models\UsersGroup;
use Illuminate\Console\Command;
use App\Services\EmployeeService;
use App\Services\OutlookAdService;
use Illuminate\Support\Facades\Http;

class SyncLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-logs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // $this->syncLogs();
        $this->handleAutoSyncEmployees();
    }

    private function syncLogs()
    {
        $companies = Company::where('approved', 1)
            ->where('service_status', 1)
            ->where('role', null)
            ->get();
        if ($companies->isEmpty()) {
            return;
        }
        foreach ($companies as $company) {
            setCompanyTimezone($company->company_id);
            $siemConfig = $company->siemConfig;
            if (!$siemConfig) {
                continue;
            }
            $logs = SiemLog::where('company_id', $company->company_id)
                ->where('synced_at', null)
                ->get();

            if ($logs->isEmpty()) {
                continue;
            }
            $logsArray = [];
            foreach ($logs as $log) {
                $logsArray[] = [
                    'event' => $log->log_msg,
                    'sourcetype' => '_json',
                    'index' => 'main',
                ];
            }
            if ($siemConfig->provider_name == 'splunk') {
                $this->sendLogsToSplunk($siemConfig, $logsArray);
                $logs->each(function ($log) {
                    $log->update(['synced_at' => now()]);
                });
            }
            if ($siemConfig->provider_name == 'webhook') {
                $this->sendLogsToWebhook($siemConfig, $logsArray);
                $logs->each(function ($log) {
                    $log->update(['synced_at' => now()]);
                });
            }
        }
    }

    private function sendLogsToSplunk($siemConfig, $logsArray)
    {
        $url = $siemConfig->url;
        $token = $siemConfig->token;

        $headers = [
            'Authorization' =>  'Splunk ' . $token,
            'Content-Type' => 'application/json'
        ];

        try {
            $response = Http::withHeaders($headers)
                ->withoutVerifying()
                ->post($url, $logsArray);

            if ($response->failed()) {
                echo 'Failed to send logs to Splunk: ' . json_encode($response->body());
            } else {
                echo 'Logs sent to Splunk successfully.';
            }
        } catch (\Exception $e) {
            echo 'Error: ' . $e->getMessage();
        }
    }

    private function sendLogsToWebhook($siemConfig, $logsArray)
    {
        $url = $siemConfig->url;

        if ($siemConfig->token == null) {
            $headers = [
                'Content-Type' => 'application/json'
            ];
        } else {
            $headers = [
                'Authorization' =>  'Bearer ' . $siemConfig->token,
                'Content-Type' => 'application/json'
            ];
        }


        try {
            $response = Http::withHeaders($headers)->withoutVerifying()->post($url, $logsArray);

            if ($response->failed()) {
                echo 'Failed to send logs to Webhook: ' . json_encode($response->body());
            } else {
                echo 'Logs sent to Webhook successfully.';
            }
        } catch (\Exception $e) {
            echo 'Error: ' . $e->getMessage();
        }
    }

    private function handleAutoSyncEmployees()
    {
        try {
            $companies = Company::where('approved', 1)
                ->where('service_status', 1)
                ->where('role', null)
                ->get();

            if ($companies->isEmpty()) {
                return;
            }

            foreach ($companies as $company) {
                setCompanyTimezone($company->company_id);

                // check auto sync setup enabled or not
                $autoSyncProviders = $company->autoSyncProviders;
                if ($autoSyncProviders->isEmpty()) {
                    continue;
                }
                foreach ($autoSyncProviders as $provider) {
                    if ($provider->provider == 'outlook') {
                        $this->syncOutlookEmployees($company, $provider);
                    }
                }
            }
        } catch (\Exception $e) {
            echo 'Error: ' . $e->getMessage();
        }
    }

    private function syncOutlookEmployees($company, $provider)
    {
        $newAdService = new OutlookAdService($company->company_id);
        // check if company has outlook ad config
        if (!$newAdService->hasToken()) {
            return;
        }

        // check if token is valid
        if (!$newAdService->isTokenValid()) {
            // if not then get the new token using refresh token
            $tokenRegenerated = $newAdService->refreshAccessToken();
            if (!$tokenRegenerated) {
                return;
            }
        }

        //check if local group exists or not
        $localGroupExists = UsersGroup::where('group_id', $provider->local_group_id)
            ->where('company_id', $company->company_id)
            ->exists();

        if (!$localGroupExists) {
            return;
        }

        // check last synced at is null or < freq days
        if ($provider->last_synced_at === null || $provider->last_synced_at < now()->subDays($provider->sync_freq_days)) {
            $employees = $newAdService->fetchGroupMembers($provider->provider_group_id);

            if (empty($employees)) {
                return;
            }

            $existingEmails = Users::where('company_id', $company->company_id)
                ->pluck('user_email')
                ->map(fn($email) => strtolower($email))
                ->flip()
                ->toArray();

            $newEmployees = collect($employees)
                ->filter(fn($emp) => isset($emp['mail']) && !isset($existingEmails[strtolower($emp['mail'])]))
                ->values(); // reindex

            $empService = new EmployeeService($company->company_id);

            $limit = $provider->sync_employee_limit;

            // print_r($newEmployees);
            // return;

            for ($i = 0; $i < $limit; $i++) {
                $employee = $newEmployees[$i];
                $addedEmp = $empService->addEmployee(
                    $employee['displayName'] ?? 'Unknown',
                    $employee['mail'] ?? 'Unknown',
                    null,
                    null,
                    null,
                    false,
                    true
                );
                $addedInGroup = $empService->addEmployeeInGroup($provider->local_group_id, $addedEmp['user_id']);
            }

            $provider->last_synced_at = now();
            $provider->save();
        }
    }
}
