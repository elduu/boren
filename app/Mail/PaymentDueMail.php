<?php

namespace App\Mail;

use App\Models\Tenant;

use App\Models\PaymentForTenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class PaymentDueMail extends Mailable
{
      use Queueable, SerializesModels;

    public $tenant;
    public $paymentForTenant;
    public $message;
    public $body;

    // Constructor accepts tenant, payment, message (subject), and body (email content)
    public function __construct(Tenant $tenant, PaymentForTenant $paymentForTenant, $message, $body)
    {
        $this->tenant = $tenant;
        $this->paymentForTenant = $paymentForTenant;
        $this->message = $message;
        $this->body = $body;
    }

    public function build()
    {
        // Build the email with dynamic subject and body
        return $this->from(env('MAIL_FROM_ADDRESS', 'realestateboren@gmail.com'), env('MAIL_FROM_NAME', 'Boren Realestate')) // Set dynamic subject
                    ->view('payment') // Blade view for the email
                    ->with([
                        'tenantName' => $this->tenant->name,
                        'body' => $this->body, // Pass the body content to the view
                    ]);
    }
}

    

