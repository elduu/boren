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
    public $payment;

    /**
     * Create a new message instance.
     *
     * @param Tenant $tenant
     * @param PaymentForTenant $payment
     * @return void
     */
    public function __construct(Tenant $tenant, PaymentForTenant $payment)
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
                        'roomNumber' => $this->tenant->room_number ?? 'N/A',
                        'dueDate' => Carbon::parse($this->payment->due_date)->format('F j, Y'),
                        'amountDue' => $this->payment->amount,
                    ]);
    }

    
}
