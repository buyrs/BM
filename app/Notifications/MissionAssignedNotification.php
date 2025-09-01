<?php

namespace App\Notifications;

use App\Models\BailMobilite;
use App\Models\Mission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MissionAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Mission $mission;
    protected ?BailMobilite $bailMobilite;

    public function __construct(Mission $mission, ?BailMobilite $bailMobilite = null)
    {
        $this->mission = $mission;
        $this->bailMobilite = $bailMobilite;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $missionType = $this->mission->mission_type ? 
            ($this->mission->mission_type === 'entry' ? 'Entrée' : 'Sortie') : 
            ucfirst($this->mission->type);
            
        $tenantName = $this->bailMobilite ? 
            $this->bailMobilite->tenant_name : 
            ($this->mission->tenant_name ?? 'Locataire');
            
        $assignedBy = $this->mission->opsAssignedBy ? 
            $this->mission->opsAssignedBy->name : 
            'l\'équipe Ops';

        $message = (new MailMessage)
            ->subject('Nouvelle mission assignée - ' . $missionType . ' ' . $tenantName)
            ->greeting('Bonjour ' . $notifiable->name)
            ->line('Une nouvelle mission vous a été assignée par ' . $assignedBy . '.')
            ->line('**Détails de la mission :**')
            ->line('Type : ' . $missionType)
            ->line('Locataire : ' . $tenantName)
            ->line('Adresse : ' . $this->mission->address);

        if ($this->mission->scheduled_at) {
            $message->line('Date et heure : ' . $this->mission->scheduled_at->format('d/m/Y à H:i'));
        }

        if ($this->bailMobilite) {
            $message->line('Téléphone : ' . ($this->bailMobilite->tenant_phone ?? 'Non renseigné'))
                   ->line('Email : ' . ($this->bailMobilite->tenant_email ?? 'Non renseigné'));
                   
            if ($this->bailMobilite->notes) {
                $message->line('Notes : ' . $this->bailMobilite->notes);
            }
        }

        $message->action('Voir la mission', url('/missions/' . $this->mission->id))
                ->line('Merci de confirmer votre disponibilité et de réaliser cette mission dans les délais.');

        return $message;
    }

    public function toArray($notifiable): array
    {
        return [
            'mission_id' => $this->mission->id,
            'mission_type' => $this->mission->mission_type ?? $this->mission->type,
            'address' => $this->mission->address,
            'scheduled_at' => $this->mission->scheduled_at?->toDateTimeString(),
            'tenant_name' => $this->bailMobilite?->tenant_name ?? $this->mission->tenant_name,
            'assigned_by' => $this->mission->opsAssignedBy?->name ?? 'System',
            'bail_mobilite_id' => $this->bailMobilite?->id,
            'message' => 'Nouvelle mission assignée - ' . ($this->mission->mission_type === 'entry' ? 'Entrée' : 'Sortie')
        ];
    }
}