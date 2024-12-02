<?php

namespace App\Mail;

use App\Models\Tenant;
use App\Models\PaymentForBuyer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UtilityPaymentDueMail extends Mailable
{
    use Queueable, SerializesModels;

    public $tenant;
    public $payment;

    /**
     * Create a new message instance.
     *
     * @param Tenant $tenant
     * @param PaymentForBuyer $payment
     * @return void
     */
    public function __construct(Tenant $tenant, PaymentForBuyer $payment)
    {
        $this->tenant = $tenant;
        $this->payment = $payment;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Payment Due Reminder')
                    ->view('payment')
                    ->with([
                        'tenantName' => $this->tenant->name,
                       'amount' => $this->payment->utility_price,
                      'dueDate' => $this->payment->due_date,
                    ]);
    }
}
