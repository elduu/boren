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
        // $schedule->command('contracts:send-renewal-emails')->everyMinute();
        // $schedule->command('payments:send-due-emails')->everyTwoMinutes();
        // $schedule->command('app:send-utility-payment-due-emails')->everyTwoMinutes();
        $schedule->command('app:send-payment-due-notifications')->daily();
        $schedule->command('app:send-contract-renewal-notifications')->daily();
        app:
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
