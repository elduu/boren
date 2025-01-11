<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\Contract;
use App\Models\PaymentForTenant;
use App\Mail\ContractRenewalMail;
use App\Mail\PaymentDueMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;


class EmailController extends Controller
{
    // Method to send contract renewal emails
    public function sendContractRenewalEmails(Request $request)
    {
        // Validate the incoming request
        $validatedData = $request->validate([
            'message' => 'nullable|string', // Email subject
            'body' => 'required|string',    // Email body content
            'tenant_email' => 'required|email', // Tenant email address
        ], [
            'message.required' => 'The message field is required.',
            'message.string' => 'The message must be a valid string.',
            
            'body.required' => 'The body field is required.',
            'body.string' => 'The body must be a valid string.',
            
            'tenant_email.required' => 'The tenant email field is required.',
            'tenant_email.email' => 'The tenant email must be a valid email address.',
        ]);
    
        // Fetch the tenant using the provided email
        $tenant = Tenant::where('email', $validatedData['tenant_email'])->first();
    
        // Ensure the tenant exists
        if (!$tenant) {
            return response()->json(['message' => 'Tenant not found.'], 400);
        }
    
        // Fetch the latest contract for the tenant
        $contract = $tenant->contracts()->latest()->first();
    
        // Ensure a contract exists for the tenant
        if (!$contract) {
            return response()->json(['message' => 'Contract not found for the tenant.'], 400);
        }
    
        // Send the email with dynamic subject (message) and body content
        try {
            Mail::to($tenant->email)
                ->send(new ContractRenewalMail($tenant, $contract, $validatedData['message'] ?? " ", $validatedData['body']));
    
            return response()->json(['message' => 'Contract renewal reminder email sent successfully.'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error sending email: ' . $e->getMessage()], 500);
        }
    }

    

    // Method to send payment due emails
    public function sendPaymentDueEmails(Request $request)
{
    // Validate the incoming request data
    $validatedData = $request->validate([
        'message' => 'nullable|string', // Email subject
        'body' => 'required|string',    // Email body content
        'tenant_email' => 'required|email', // Tenant email address
    ], [
        'message.required' => 'The message field is required.',
        'message.string' => 'The message must be a valid string.',
        
        'body.required' => 'The body field is required.',
        'body.string' => 'The body must be a valid string.',
        
        'tenant_email.required' => 'The tenant email field is required.',
        'tenant_email.email' => 'The tenant email must be a valid email address.',
    ]);
    // Fetch the tenant using the provided email
    $tenant = Tenant::where('email', $validatedData['tenant_email'])->first();

    // Ensure the tenant exists
    if (!$tenant) {
        return response()->json(['message' => 'Tenant not found.'], 400
    );
    }

    // Fetch the latest payment for the tenant
    $paymentForTenant = $tenant->paymentsForTenant()->latest()->first(); // Get the most recent payment

    // Ensure a payment exists for the tenant
    if (!$paymentForTenant) {
        return response()->json(['message' => 'Payment not found for the tenant.'], 400);
    }

    // Send the email with dynamic subject (message) and body content
    try {
        Mail::to($tenant->email)
            ->send(new PaymentDueMail($tenant, $paymentForTenant, $validatedData['message'] ?? " ", $validatedData['body']));

        return response()->json(['message' => 'Payment due reminder email sent successfully.'], 200);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Error sending email: ' . $e->getMessage()], 500);
    }
}
}