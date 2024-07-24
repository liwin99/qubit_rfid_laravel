<?php

namespace App\Console;

use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('app:test')
            ->timezone('Asia/Kuala_Lumpur')
            ->everyMinute();

        $schedule->command('cron:clear-log')
            ->timezone('Asia/Kuala_Lumpur')
            ->dailyAt('00:00');

        $schedule->command('cron:process-worker-time-log')
            ->timezone('Asia/Kuala_Lumpur')
            ->hourlyAt('30');

        $now = Carbon::now('Asia/Kuala_Lumpur')->toDateString();
        $schedule->command('cron:process-worker-time-log ' . $now)
            ->timezone('Asia/Kuala_Lumpur')
            ->hourlyAt(0);
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
