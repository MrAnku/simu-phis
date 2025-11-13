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
    'app:comic-status-checker',
    'app:company-management',
    'app:process-triggers',
    'app:send-overall-report',
    'app:invite-partner'
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

// PPP Calculation Commands - Run separately at end of month
Schedule::command('ppp:calculate-company')
    ->cron('20 23 28-31 * *')
    ->skip(function () {
        return \Carbon\Carbon::now()->addDay()->day !== 1;
    })
    ->runInBackground()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/cron/ppp-calculate-company.log'));

Schedule::command('ppp:calculate-users')
    ->cron('40 23 28-31 * *')
    ->skip(function () {
        return \Carbon\Carbon::now()->addDay()->day !== 1;
    })
    ->runInBackground()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/cron/ppp-calculate-users.log'));
