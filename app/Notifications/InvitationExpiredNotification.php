<?php

namespace App\Notifications;

use App\Models\SignatureInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvitationExpiredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $invitation;

    public function __construct(SignatureInvitation $invitation)
    {
        $this->invitation = $invitation;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $signature = $this->invitation->bailMobiliteSignature;
        $bailMobilite = $signature->bailMobilite;
        $party = $this->invitation->signatureParty;

        return (new MailMessage)
            ->subject('⚠️ Invitation de signature expirée - ' . $bailMobilite->address)
            ->greeting('Bonjour,')
            ->line('Une invitation de signature a expiré sans avoir été complétée :')
            ->line('**Locataire:** ' . $bailMobilite->tenant_name)
            ->line('**Adresse:** ' . $bailMobilite->address)
            ->line('**Partie concernée:** ' . $party->name . ' (' . $this->getRoleDisplayName($party->role) . ')')
            ->line('**Email de la partie:** ' . $party->email)
            ->line('**Date d\'expiration:** ' . $this->invitation->expires_at->format('d/m/Y à H:i'))
            ->line('**Statut actuel du workflow:** ' . $this->getWorkflowStatus())
            ->action('Gérer le workflow', route('signatures.workflow.status', $signature->id))
            ->line('Veuillez prendre les mesures nécessaires pour relancer la signature ou assigner une autre partie.')
            ->salutation('Cordialement, \nL\'équipe Bail Mobilité');
    }

    protected function getRoleDisplayName(string $role): string
    {
        $roles = [
            'landlord' => 'Propriétaire/Bailleur',
            'agent' => 'Mandataire/Agent',
            'witness' => 'Témoin',
            'guarantor' => 'Garant',
            'co_tenant' => 'Co-locataire',
            'legal_representative' => 'Représentant légal'
        ];

        return $roles[$role] ?? ucfirst(str_replace('_', ' ', $role));
    }

    protected function getWorkflowStatus(): string
    {
        $signature = $this->invitation->bailMobiliteSignature;
        $percentage = $signature->getCompletionPercentage();
        
        return $percentage . '% complété (' . $signature->signature_status . ')';
    }

    public function toArray($notifiable)
    {
        $signature = $this->invitation->bailMobiliteSignature;
        $party = $this->invitation->signatureParty;

        return [
            'type' => 'invitation_expired',
            'invitation_id' => $this->invitation->id,
            'signature_id' => $signature->id,
            'bail_mobilite_id' => $signature->bail_mobilite_id,
            'party_name' => $party->name,
            'party_email' => $party->email,
            'party_role' => $party->role,
            'expired_at' => $this->invitation->expires_at->toISOString(),
            'workflow_status' => $signature->signature_status,
            'completion_percentage' => $signature->getCompletionPercentage()
        ];
    }
}