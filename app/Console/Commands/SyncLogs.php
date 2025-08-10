<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\SiemLog;
use Illuminate\Console\Command;
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
        $this->syncLogs();
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
}
