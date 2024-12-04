<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\PaymentForTenant;
use App\Models\User;
use App\Notifications\PaymentDueNotification;
use Carbon\Carbon;
class SendPaymentDueNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-payment-due-notifications';

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
        $threeDaysFromNow = Carbon::now()->addDays(12);
        $tenantsDue = PaymentForTenant::where('due_date', '<=', $threeDaysFromNow)
            ->where('payment_status', 'unpaid')
            ->with('tenant') // Ensure the tenant relationship exists
            ->get();
           

        if ($tenantsDue->isEmpty()) {
            $this->info('No tenants with payment due dates found.');
            return;
        }

        // Prepare the tenant data for notification
        $tenantsData = $tenantsDue->map(function ($payment) {
            return [
                'name' => $payment->tenant->name,
                'room_number' => $payment->tenant->room_number,
                'due_date' => $payment->due_date,
            ];
        })->toArray();

        // Notify all users responsible for reminders
        $users = User::role('admin')->get(); // Adjust role if necessary
        foreach ($users as $user) {
            $user->notify(new PaymentDueNotification($tenantsData));
        }

        $this->info('Payment due notifications sent successfully.');
    }
    }
