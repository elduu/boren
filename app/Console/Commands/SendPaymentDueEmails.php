<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Models\PaymentForTenant;
use App\Mail\PaymentDueMail;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class SendPaymentDueEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:send-due-emails';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send payment due emails to tenants based on the due date';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        // Get all tenants with payments due within the next 30 days and unpaid
        $tenants = Tenant::whereHas('paymentsForTenants', function ($query) {
            $query->where('due_date', '<=', Carbon::now());
                //  ->where('status', 'unpaid');
        })->get();

        // Loop through tenants and send emails for each payment due
        foreach ($tenants as $tenant) {
            $payments = $tenant->paymentsForTenants()
                               ->where('due_date', '<=', Carbon::now())
                              // ->where('status', 'unpaid')
                               ->get();

            foreach ($payments as $payment) {
                // Send email to the tenant
                Mail::to($tenant->email)->send(new PaymentDueMail($tenant, $payment));

                // Output the email sending status to the console
                $this->info("Payment due email sent to: {$tenant->email} for payment due on {$payment->due_date->format('F j, Y')}");
            }
        }

        $this->info('Payment due emails have been sent successfully!');
    }
}

