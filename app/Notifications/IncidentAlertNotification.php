<?php

namespace App\Notifications;

use App\Models\BailMobilite;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class IncidentAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected BailMobilite $bailMobilite;
    protected ?string $incidentReason;

    public function __construct(BailMobilite $bailMobilite, ?string $incidentReason = null)
    {
        $this->bailMobilite = $bailMobilite;
        $this->incidentReason = $incidentReason;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('🚨 INCIDENT - Bail Mobilité ' . $this->bailMobilite->tenant_name)
            ->greeting('Bonjour ' . $notifiable->name)
            ->line('Un incident a été détecté sur un Bail Mobilité.')
            ->line('**Détails de l\'incident :**')
            ->line('Locataire : ' . $this->bailMobilite->tenant_name)
            ->line('Adresse : ' . $this->bailMobilite->address)
            ->line('Statut actuel : ' . ucfirst($this->bailMobilite->status));

        if ($this->incidentReason) {
            $message->line('Raison : ' . $this->incidentReason);
        }

        $message->line('Date de début : ' . $this->bailMobilite->start_date->format('d/m/Y'))
                ->line('Date de fin : ' . $this->bailMobilite->end_date->format('d/m/Y'))
                ->line('Téléphone : ' . ($this->bailMobilite->tenant_phone ?? 'Non renseigné'))
                ->line('Email : ' . ($this->bailMobilite->tenant_email ?? 'Non renseigné'))
                ->action('Gérer l\'incident', url('/ops/bail-mobilites/' . $this->bailMobilite->id))
                ->line('⚠️ Une action immédiate est requise pour résoudre cet incident.')
                ->line('Merci de traiter ce problème rapidement.');

        return $message;
    }

    public function toArray($notifiable): array
    {
        return [
            'bail_mobilite_id' => $this->bailMobilite->id,
            'tenant_name' => $this->bailMobilite->tenant_name,
            'address' => $this->bailMobilite->address,
            'status' => $this->bailMobilite->status,
            'incident_reason' => $this->incidentReason,
            'message' => 'Incident détecté - ' . $this->bailMobilite->tenant_name,
            'priority' => 'high'
        ];
    }
}