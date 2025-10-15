<?php

use Illuminate\Support\Facades\Schedule;
// Commands that run every minute
$everyMinuteCommands = [
    'app:email-breach-check',
    'app:process-ai-campaigns',
    'app:process-campaigns',
    'app:process-policy-campaign',
    'app:process-quishing',
    'app:process-smishing',
    'app:process-tprm-campaigns',
    'app:process-whatsapp-campaign',
    'app:send-infographics',
    'app:company-management',
    'app:process-triggers',
];

if (! is_dir(storage_path('logs/cron'))) {
    mkdir(storage_path('logs/cron'), 0777, true);
}

foreach ($everyMinuteCommands as $command) {
    $logFile = str_replace(':', '-', $command) . '.log';

    Schedule::command($command)
        ->everyMinute()
        ->runInBackground()
        ->withoutOverlapping()
        ->appendOutputTo(storage_path("logs/cron/{$logFile}"));
}

// Special cases
Schedule::command('app:process-cloning-website')
    ->everyTwoMinutes()
    ->runInBackground()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/cron/process-cloning-website.log'));

Schedule::command('app:sync-logs')
    ->everyThirtyMinutes()
    ->runInBackground()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/cron/sync-logs.log'));

Schedule::command('app:send-overall-report')
    ->daily()
    ->at('09:00')
    ->appendOutputTo(storage_path('logs/cron/overall-report.log'));
