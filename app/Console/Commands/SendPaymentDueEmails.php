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
    $dueDate = Carbon::now()->addDays(12);
    // Get all tenants with payments due on or before today
    $tenants = PaymentForTenant::where('due_date', '<=', $dueDate)
        ->where('due_date', '<=', $dueDate)
              ->where('payment_status', 'overdue') // Ensure this matches your payment status column
    ->get();

    if ($tenants->isEmpty()) {
        $this->info('No tenants with payments due.');
        return;
    }
    foreach ($tenants as $payment) {
        $tenant = $payment->tenant; // Assuming `tenant` is the relationship name

        if (!$tenant || !$tenant->email) {
            $this->error("Payment ID {$payment->id} has no associated tenant email.");
            continue;
        }

    // Loop through tenants to send payment due emails


        foreach ($tenants as $payment) {
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

