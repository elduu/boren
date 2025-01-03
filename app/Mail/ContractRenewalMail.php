<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;
use App\Models\Tenant;
use App\Models\Contract;


class ContractRenewalMail extends Mailable
{
    public $tenant;
    public $contract;
    public $message;
    public $body;

    public function __construct(Tenant $tenant, $contract, $message, $body)
    {
        $this->tenant = $tenant;
        $this->contract = $contract;
        $this->message = (string) $message;
        $this->body = (string)$body;
    }

    public function build()
    {
        // Set the 'from' address here
        return $this->from(env('MAIL_FROM_ADDRESS', 'realestateboren@gmail.com'), env('MAIL_FROM_NAME', 'Boren Realestate')) 
                   // Email subject
                    ->view('contract') // Assuming you have this view file
                    ->with([
                       'tenantName' => $this->tenant->name,
                    'message' =>  $this->message, 
                        'body' => $this->body,
                    ]);
    }
}

