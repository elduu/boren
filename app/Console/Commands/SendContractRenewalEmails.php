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
        // Get contracts with a due date that is 3 days from now
        $dueDate = Carbon::now();
        $contracts = Contract::whereDate('due_date', '=', $dueDate)
            ->with('tenant') // Include related tenant data
            ->get();

        foreach ($contracts as $contract) {
            $tenant = $contract->tenant;

            if ($tenant && $tenant->email) {
                Mail::to($tenant->email)->send(new ContractRenewalMail($tenant, $contract));
                $this->info("Sent renewal email to {$tenant->email} for contract ID {$contract->id}");
            }
        }

        $this->info('Renewal emails sent successfully.');
    }
}
