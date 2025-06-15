<?php

namespace App\Notifications;

use App\Models\Mission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MissionReminder extends Notification implements ShouldQueue
{
    use Queueable;

    protected $mission;

    public function __construct(Mission $mission)
    {
        $this->mission = $mission;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Mission Reminder: ' . ucfirst($this->mission->type) . ' at ' . $this->mission->address)
            ->greeting('Hello ' . $notifiable->name)
            ->line('This is a reminder about your upcoming mission:')
            ->line('Type: ' . ucfirst($this->mission->type))
            ->line('Address: ' . $this->mission->address)
            ->line('Scheduled for: ' . $this->mission->scheduled_at->format('F j, Y g:i A'))
            ->line('Tenant: ' . $this->mission->tenant_name)
            ->action('View Mission', url('/missions/' . $this->mission->id))
            ->line('Thank you for using our application!');
    }
} 