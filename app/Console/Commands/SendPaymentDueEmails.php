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
    // Get all tenants with payments due on or before today
    $tenants = Tenant::whereHas('paymentsForTenant', function ($query) {
        $query->where('due_date', '<=', Carbon::now());
            // Uncomment if you have a 'status' field to filter unpaid payments:
            // ->where('status', 'unpaid');
    })->get();

    if ($tenants->isEmpty()) {
        $this->info('No tenants with payments due.');
        return;
    }

    // Loop through tenants to send payment due emails
    foreach ($tenants as $tenant) {
        $payments = $tenant->paymentsForTenant()
                           ->where('due_date', '<=', Carbon::now())
                           // Uncomment if filtering by 'unpaid' status
                           // ->where('status', 'unpaid')
                           ->get();

        foreach ($payments as $payment) {
            // Ensure `due_date` is a Carbon instance
            $dueDate = $payment->due_date instanceof \Carbon\Carbon
                ? $payment->due_date
                : Carbon::parse($payment->due_date);

            try {
                // Send email to the tenant
                Mail::to($tenant->email)->send(new PaymentDueMail($tenant, $payment));

                // Output the email sending status to the console
                $this->info("Payment due email sent to: {$tenant->email} for payment due on {$dueDate->format('F j, Y')}");
            } catch (\Exception $e) {
                $this->error("Failed to send email to {$tenant->email}: {$e->getMessage()}");
            }
        }
    }

    $this->info('Payment due emails have been sent successfully!');
}
}

