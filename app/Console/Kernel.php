<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('contracts:send-renewal-emails')->everyThirtyMinutes();
        $schedule->command('payments:send-due-emails')->everyTwoMinutes();
        $schedule->command('app:send-utility-payment-due-emails')->everyTwoMinutes();
    ;
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
