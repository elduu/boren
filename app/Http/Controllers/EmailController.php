<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\Contract;
use App\Models\PaymentForTenant;
use App\Mail\ContractRenewalMail;
use App\Mail\PaymentDueMail;
use Illuminate\Support\Facades\Mail;

class EmailController extends Controller
{
    // Method to send contract renewal emails
    public function sendContractRenewalEmails()
    {
      
        $tenant = Tenant::first(); // Replace with logic to fetch the correct tenant
        $contract = $tenant->contracts()->latest()->first(); // Replace with logic to fetch the correct contract

        if ($tenant && $contract) {
            Mail::to($tenant->email)->send(new ContractRenewalMail($tenant, $contract));
            return "Email sent successfully.";
        }

        return "Tenant or Contract not found.";
    }

    // Method to send payment due emails
    public function sendPaymentDueEmails()
    {
        $recipients = ['siyangetahunw@gmail.com', 'yilmaruth494@gmail.com', 'tsiti2755@gmail.com']; // Example of multiple recipients

        // Fetch tenants who have a payment due soon
        $tenants = Tenant::has('paymentsForTenant')->get(); 

        foreach ($tenants as $tenant) {
            $paymentForTenant = $tenant->paymentsForTenant()->latest()->first();

            if ($paymentForTenant && $paymentForTenant->due_date <= now()->addDays(3)) {
                Mail::to($tenant->email)->send(new PaymentDueMail($tenant, $paymentForTenant));
            }
        }

        return "Payment due emails have been sent to applicable tenants.";
    }
}