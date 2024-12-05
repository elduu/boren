<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PaymentForTenant;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Contract;
use App\Notifications\ContractRenewalNotification;
use Carbon\Carbon;
class SendContractRenewalNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-contract-renewal-notifications';

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
        $thirtyDaysFromNow = Carbon::now()->addDays(30);
        $thirtyDaysFromNow = Carbon::now()->addDays(30);
         $tenantsDue = Contract::where('due_date', '<=', $thirtyDaysFromNow)
          ->where('status', 'active') 
          ->with('tenant')
          ->get(); // Ensure the tenant relationship exists ->get();
        if ($tenantsDue->isEmpty()) {
            $this->info('No tenants with contract renewal  dates found.');
            return;
        }

        // Prepare the tenant data for notification
        $tenantsData = $tenantsDue->map(function ($contract) {
            return [
                'name' => $contract->tenant->name,
                'room_number' => $contract->tenant->room_number,
                'due_date' => $contract->due_date,
            ];
        })->toArray();

        // Notify all users responsible for reminders
        $users = User::role('admin')->get(); // Adjust role if necessary
        foreach ($users as $user) {
            $user->notify(new ContractRenewalNotification($tenantsData));
        }

        $this->info('Payment due notifications sent successfully.');
    }
    }

