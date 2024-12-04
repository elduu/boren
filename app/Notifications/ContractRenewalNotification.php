<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;

class ContractRenewalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $tenants;

    public function __construct($tenants)
    {
        $this->tenants = $tenants;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        return ['database']; // Store in notifications table
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable)
    {
        return [
            'message' => 'The following tenants have contracts nearing renewal:',
            'tenants' => $this->tenants, // List of tenants with contract due in 3 days
        ];
    }
    public function toArray($notifiable)
    {
        return [
            'message' => 'The following tenants have contract renewals due soon.',
            'tenants' => $this->tenants,
        ];
    }
}
