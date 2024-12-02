<?php

use Illuminate\Support\Facades\Route;
use App\Mail\ContractRenewalMail;
use Illuminate\Support\Facades\Mail;
use App\Models\Tenant;
use App\Models\Contract;

use App\Models\PaymentForTenant;
use App\Mail\PaymentDueMail;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    $recipients = ['siyangetahunw@gmail.com', 'yilmaruth494@gmail.com', 'tsiti2755@gmail.com'];
    $tenant = Tenant::first(); // Replace with logic to fetch the correct tenant
    $contract = $tenant->contracts()->latest()->first(); // Replace with logic to fetch the correct contract

    if ($tenant && $contract) {
        Mail::to($tenant->email)->send(new ContractRenewalMail($tenant, $contract));
        return "Email sent successfully.";
    }

    return "Tenant or Contract not found.";
});
Route::get('/send-payment-due-emails', function () {
    $recipients = ['siyangetahunw@gmail.com', 'yilmaruth494@gmail.com', 'tsiti2755@gmail.com']; // Example of multiple recipients

    // Fetch tenants who have a payment due soon (you can modify this logic to fit your needs)
    $tenants = Tenant::has('paymentsForTenant')->get(); // Assuming you have a relationship called 'paymentsForTenant'

    foreach ($tenants as $tenant) {
        // Get the most recent payment entry for each tenant (adjust the query based on your structure)
        $paymentForTenant = $tenant->paymentsForTenant()->latest()->first();

        // Check if the payment due date is today or near (you can modify this condition)
        if ($paymentForTenant && $paymentForTenant->due_date <= now()->addDays(3)) {
            // Send the email for the due payment
            Mail::to($tenant->email)->send(new PaymentDueMail($tenant, $paymentForTenant));

            // Optionally, you can log each email that was sent
          //  Log::info('Payment due email sent to: ' . $tenant->email);
        }
    }

    return "Payment due emails have been sent to applicable tenants.";
});
Route::get('/send-payment-due-emails', function () {
    $recipients = ['siyangetahunw@gmail.com', 'yilmaruth494@gmail.com', 'tsiti2755@gmail.com']; // Example of multiple recipients

    // Fetch tenants who have a payment due soon (you can modify this logic to fit your needs)
    $tenants = Tenant::has('paymentsForTenant')->get(); // Assuming you have a relationship called 'paymentsForTenant'

    foreach ($tenants as $tenant) {
        // Get the most recent payment entry for each tenant (adjust the query based on your structure)
        $paymentForTenant = $tenant->paymentsForTenant()->latest()->first();

        // Check if the payment due date is today or near (you can modify this condition)
        if ($paymentForTenant && $paymentForTenant->due_date <= now()->addDays(3)) {
            // Send the email for the due payment
            Mail::to($tenant->email)->send(new PaymentDueMail($tenant, $paymentForTenant));

            // Optionally, you can log each email that was sent
          //  Log::info('Payment due email sent to: ' . $tenant->email);
        }
    }

    return "Payment due emails have been sent to applicable tenants.";
});



Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');


Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
