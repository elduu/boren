<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;

class PaymentDueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $tenants;

    /**
     * Create a new notification instance.
     *
     * @param array $tenants
     */
    public function __construct($tenants)
    {
        $this->tenants = $tenants;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the database representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toDatabase($notifiable)
    {
        return [
            'message' => 'The following tenants have upcoming payment due dates:',
            'tenants' => $this->tenants,
        ];
    }
    public function toArray($notifiable)
    {
        return [
            'message' => 'The following tenants have upcoming payment due dates:',
            'tenants' => $this->tenants,
        ];
    }
    
}
