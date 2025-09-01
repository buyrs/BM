<?php

namespace App\Notifications;

use App\Models\BailMobilite;
use App\Models\Mission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChecklistValidationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Mission $mission;
    protected BailMobilite $bailMobilite;

    public function __construct(Mission $mission, BailMobilite $bailMobilite)
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
        $missionType = $this->mission->mission_type === 'entry' ? 'entrée' : 'sortie';
        $checkerName = $this->mission->agent->name ?? 'Checker inconnu';
        
        return (new MailMessage)
            ->subject('Checklist à valider - ' . ucfirst($missionType) . ' ' . $this->bailMobilite->tenant_name)
            ->greeting('Bonjour ' . $notifiable->name)
            ->line('Une checklist de ' . $missionType . ' est en attente de validation.')
            ->line('**Détails de la mission :**')
            ->line('Type : ' . ucfirst($missionType))
            ->line('Checker : ' . $checkerName)
            ->line('Locataire : ' . $this->bailMobilite->tenant_name)
            ->line('Adresse : ' . $this->bailMobilite->address)
            ->line('Date de mission : ' . ($this->mission->scheduled_at ? $this->mission->scheduled_at->format('d/m/Y H:i') : 'Non programmée'))
            ->when($this->mission->mission_type === 'entry', function ($message) {
                return $message->line('⚠️ La validation permettra de passer le BM en statut "En cours"');
            })
            ->when($this->mission->mission_type === 'exit', function ($message) {
                return $message->line('⚠️ La validation permettra de finaliser le BM');
            })
            ->action('Valider la checklist', url('/ops/missions/' . $this->mission->id . '/validate'))
            ->line('Merci de valider rapidement cette checklist.');
    }

    public function toArray($notifiable): array
    {
        return [
            'mission_id' => $this->mission->id,
            'mission_type' => $this->mission->mission_type,
            'bail_mobilite_id' => $this->bailMobilite->id,
            'tenant_name' => $this->bailMobilite->tenant_name,
            'address' => $this->bailMobilite->address,
            'checker_name' => $this->mission->agent->name ?? 'Unknown',
            'message' => 'Checklist de ' . ($this->mission->mission_type === 'entry' ? 'entrée' : 'sortie') . ' à valider'
        ];
    }
}