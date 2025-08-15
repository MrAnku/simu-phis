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
        ];

        foreach ($commands as $command) {
            $logFileName = Str::slug($command) . '.log';
            $logPath = storage_path("logs/cron/{$logFileName}");

            // Make sure the logs directory exists
            if (!is_dir(dirname($logPath))) {
                mkdir(dirname($logPath), 0777, true);
            }

            try {
                // Build full artisan command
                $artisanPath = base_path('artisan');
                $cmd = "php {$artisanPath} {$command} >> {$logPath} 2>&1 &";

                // Run command in background
                exec($cmd);

                $this->info("Started {$command} in background, logging to {$logPath}");
            } catch (\Throwable $e) {
                // Log the error if the process fails to start
                $timestamp = now()->format('Y-m-d H:i:s');
                $errorEntry = "===== [{$timestamp}] ERROR STARTING: {$command} =====\n";
                $errorEntry .= "Message: " . $e->getMessage() . "\n";
                $errorEntry .= "File: " . $e->getFile() . "\n";
                $errorEntry .= "Line: " . $e->getLine() . "\n\n";

                Storage::disk('local')->append("logs/cron/errors.log", $errorEntry);

                $this->error("Failed to start {$command}");
            }
        }
    }
}
