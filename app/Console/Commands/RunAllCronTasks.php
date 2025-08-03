<?php

namespace App\Console\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class RunAllCronTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:run-all-cron-tasks';

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
        $commands = [
            'app:email-breach-check',
            'app:process-ai-campaigns',
            'app:process-campaigns',
            'app:process-cloning-website',
            'app:process-policy-campaign',
            'app:process-quishing',
            'app:process-smishing',
            'app:process-tprm-campaigns',
            'app:process-whatsapp-campaign',
            'app:send-infographics',
            'app:sync-logs',
            // Add more commands as needed
        ];

        foreach ($commands as $command) {
            $logFileName = Str::slug($command) . '.log'; // Clean file name
            $logPath = "logs/cron/{$logFileName}";
            $timestamp = now()->format('Y-m-d H:i:s');

            try {
                Artisan::call($command);
                $outputText = Artisan::output();

                $logEntry = "===== [{$timestamp}] SUCCESS: {$command} =====\n{$outputText}\n\n";
            } catch (\Exception $e) {
                $logEntry = "===== [{$timestamp}] ERROR: {$command} =====\n";
                $logEntry .= "Message: " . $e->getMessage() . "\n";
                $logEntry .= "File: " . $e->getFile() . "\n";
                $logEntry .= "Line: " . $e->getLine() . "\n\n";
            }

            // Append log entry to the command's log file
            Storage::disk('local')->append($logPath, $logEntry);

            // Optional: Console output
            $this->info("Finished: {$command}");
        }
    }
}
