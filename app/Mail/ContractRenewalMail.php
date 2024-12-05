<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;


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
        $contractExpiration = Carbon::parse($this->contract->expiration_date)->format('F j, Y');
        return $this->view('contract')
            ->subject('Contract Renewal Reminder')
            ->with([
                'tenantName' => $this->tenant->name,
               'contractExpiration' =>  $contractExpiration, 
                
            ]); 
    }
}

