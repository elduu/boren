<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Models\Contract;
use App\Mail\ContractRenewalMail;
use Carbon\Carbon;

class SendContractRenewalEmails extends Command
{
    protected $signature = 'contracts:send-renewal-emails';
    protected $description = 'Send renewal reminder emails to tenants whose contracts are due soon';

    public function handle()
    {
        $dueDate = Carbon::now()->toDateString(); // Get today's date in 'Y-m-d' format
    
        // Get contracts with a due date matching today and include tenant data
        $contracts = Contract::whereDate('due_date', '=', $dueDate)
            ->with('tenant') // Ensure tenant relationship is loaded
            ->get();
    
        foreach ($contracts as $contract) {
            $tenant = $contract->tenant;
    
            // Skip if tenant doesn't exist or email is missing
            if (!$tenant || !$tenant->email) {
                $this->info("Skipping contract ID {$contract->id}, tenant or email missing.");
                continue;
            }
    
            // Skip if contract status is not 'active'
            if ($contract->status !== 'active') {
                $this->info("Skipping email for tenant {$tenant->email}, contract is inactive.");
                continue;
            }
    
            // Send the email
            Mail::to($tenant->email)->send(new ContractRenewalMail($tenant, $contract));
            $this->info("Sent renewal email to {$tenant->email} for contract ID {$contract->id}");
        }
    
        $this->info('Renewal emails process completed.');
    }
}
