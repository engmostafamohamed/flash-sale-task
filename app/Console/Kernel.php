<?php
// app/Console/Kernel.php

namespace App\Console;

use App\Jobs\ReleaseExpiredHoldsJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Release expired holds every minute
        $holdService = app()->make(\App\Services\HoldService::class);
        $schedule->job(new ReleaseExpiredHoldsJob($holdService))
            ->everyMinute()
            ->withoutOverlapping()
            ->onOneServer();
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
