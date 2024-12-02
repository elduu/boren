<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;



class ContractRenewalMail extends Mailable
{
    public $tenant;
    public $contract;

    public function __construct($tenant, $contract)
    {
        $this->tenant = $tenant;
        $this->contract = $contract;
    }

    public function build()
    {
        return $this->view('contract')
            ->subject('Contract Renewal Reminder')
            ->with([
                'tenant' => $this->tenant,
                'contract' => $this->contract,
            ]);
    }
}

