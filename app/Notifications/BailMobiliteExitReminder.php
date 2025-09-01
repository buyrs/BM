<?php

namespace App\Notifications;

use App\Models\BailMobilite;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BailMobiliteExitReminder extends Notification implements ShouldQueue
{
    use Queueable;

    protected BailMobilite $bailMobilite;

    public function __construct(BailMobilite $bailMobilite)
    {
        $this->bailMobilite = $bailMobilite;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $daysRemaining = $this->bailMobilite->getRemainingDays();
        
        return (new MailMessage)
            ->subject('Rappel de fin de Bail Mobilité - ' . $this->bailMobilite->tenant_name)
            ->greeting('Bonjour ' . $notifiable->name)
            ->line('Un Bail Mobilité se termine dans ' . $daysRemaining . ' jours.')
            ->line('**Détails du séjour :**')
            ->line('Locataire : ' . $this->bailMobilite->tenant_name)
            ->line('Adresse : ' . $this->bailMobilite->address)
            ->line('Date de fin : ' . $this->bailMobilite->end_date->format('d/m/Y'))
            ->line('Téléphone : ' . ($this->bailMobilite->tenant_phone ?? 'Non renseigné'))
            ->line('Email : ' . ($this->bailMobilite->tenant_email ?? 'Non renseigné'))
            ->action('Gérer le Bail Mobilité', url('/ops/bail-mobilites/' . $this->bailMobilite->id))
            ->line('Pensez à assigner un checker pour la sortie.')
            ->line('Merci de votre attention !');
    }

    public function toArray($notifiable): array
    {
        return [
            'bail_mobilite_id' => $this->bailMobilite->id,
            'tenant_name' => $this->bailMobilite->tenant_name,
            'address' => $this->bailMobilite->address,
            'end_date' => $this->bailMobilite->end_date->toDateString(),
            'days_remaining' => $this->bailMobilite->getRemainingDays(),
            'message' => 'Bail Mobilité se termine dans ' . $this->bailMobilite->getRemainingDays() . ' jours'
        ];
    }
}