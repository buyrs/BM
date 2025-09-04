<?php

namespace App\Notifications;

use App\Models\SignatureInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EscalationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $invitation;
    protected $escalationSettings;

    public function __construct(SignatureInvitation $invitation, array $escalationSettings)
    {
        $this->invitation = $invitation;
        $this->escalationSettings = $escalationSettings;
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
            ->subject('🚨 Escalation requise - Signature en attente - ' . $bailMobilite->address)
            ->greeting('URGENT - Action requise')
            ->line('Une invitation de signature a expiré et nécessite une intervention immédiate :')
            ->line('**Locataire:** ' . $bailMobilite->tenant_name)
            ->line('**Adresse:** ' . $bailMobilite->address)
            ->line('**Partie en défaut:** ' . $party->name . ' (' . $this->getRoleDisplayName($party->role) . ')')
            ->line('**Contact:** ' . $party->email . ($party->phone ? ' / ' . $party->phone : ''))
            ->line('**Délai d\'expiration:** ' . $this->invitation->expires_at->diffForHumans())
            ->line('**Raison de l\'escalation:** ' . $this->getEscalationReason())
            ->line('**Actions recommandées:** ' . $this->getRecommendedActions())
            ->action('Intervenir maintenant', route('signatures.workflow.status', $signature->id))
            ->line('Ce contrat est bloqué dans le workflow de signature et nécessite une action immédiate.')
            ->salutation('Cordialement, \nSystème d\'escalation Bail Mobilité');
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

    protected function getEscalationReason(): string
    {
        $daysExpired = $this->invitation->expires_at->diffInDays(now());
        
        if ($daysExpired >= 3) {
            return 'Délai critique dépassé (' . $daysExpired . ' jours) - Risque d\'annulation du contrat';
        }
        
        return 'Délai standard dépassé (' . $daysExpired . ' jours) - Relance nécessaire';
    }

    protected function getRecommendedActions(): string
    {
        $actions = [
            'Contacter la partie par téléphone',
            'Envoyer un rappel urgent',
            'Assigner un représentant alternatif',
            'Réinitialiser le workflow si nécessaire'
        ];

        return implode(', ', $actions);
    }

    public function toArray($notifiable)
    {
        $signature = $this->invitation->bailMobiliteSignature;
        $party = $this->invitation->signatureParty;

        return [
            'type' => 'signature_escalation',
            'invitation_id' => $this->invitation->id,
            'signature_id' => $signature->id,
            'bail_mobilite_id' => $signature->bail_mobilite_id,
            'party_name' => $party->name,
            'party_email' => $party->email,
            'party_phone' => $party->phone,
            'party_role' => $party->role,
            'expired_at' => $this->invitation->expires_at->toISOString(),
            'days_expired' => $this->invitation->expires_at->diffInDays(now()),
            'escalation_level' => $this->getEscalationLevel(),
            'escalation_settings' => $this->escalationSettings,
            'workflow_status' => $signature->signature_status,
            'completion_percentage' => $signature->getCompletionPercentage()
        ];
    }

    protected function getEscalationLevel(): string
    {
        $daysExpired = $this->invitation->expires_at->diffInDays(now());
        
        if ($daysExpired >= 3) {
            return 'CRITICAL';
        }
        
        if ($daysExpired >= 1) {
            return 'HIGH';
        }
        
        return 'MEDIUM';
    }
}