<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SignatureInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $invitation = $this->data['invitation'];
        $party = $this->data['party'];
        $signature = $this->data['signature'];
        $bailMobilite = $this->data['bailMobilite'];
        $contractTemplate = $this->data['contractTemplate'];

        return (new MailMessage)
            ->subject('Invitation à signer un contrat - ' . $contractTemplate->name)
            ->greeting('Bonjour ' . $party->name . ',')
            ->line('Vous avez été invité à signer un contrat pour le bail mobilité suivant :')
            ->line('**Locataire:** ' . $bailMobilite->tenant_name)
            ->line('**Adresse:** ' . $bailMobilite->address)
            ->line('**Type de contrat:** ' . $contractTemplate->name)
            ->line('**Votre rôle:** ' . $this->getRoleDisplayName($party->role))
            ->action('Signer le contrat', $this->data['signatureUrl'])
            ->line('Cette invitation expirera le: ' . $this->data['expiresAt']->format('d/m/Y à H:i'))
            ->line('Si vous rencontrez des problèmes pour accéder au lien, veuillez contacter notre équipe.')
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

    public function toArray($notifiable)
    {
        return [
            'invitation_id' => $this->data['invitation']->id,
            'signature_id' => $this->data['signature']->id,
            'bail_mobilite_id' => $this->data['bailMobilite']->id,
            'party_role' => $this->data['party']->role,
            'signature_url' => $this->data['signatureUrl'],
            'expires_at' => $this->data['expiresAt']->toISOString()
        ];
    }
}