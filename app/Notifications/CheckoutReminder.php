<?php

namespace App\Notifications;

use App\Models\Mission;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class CheckoutReminder extends Notification
{
    use Queueable;

    protected $mission;

    public function __construct(Mission $mission)
    {
        $this->mission = $mission;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Checkout Reminder: ' . $this->mission->address)
            ->line('A checkout mission is scheduled in 10 days.')
            ->line('Address: ' . $this->mission->address)
            ->line('Tenant: ' . $this->mission->tenant_name)
            ->line('Scheduled for: ' . $this->mission->scheduled_at->format('Y-m-d H:i'))
            ->action('View Mission', url('/missions/' . $this->mission->id));
    }
} 