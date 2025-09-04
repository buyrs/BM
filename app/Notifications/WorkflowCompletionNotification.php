<?php

namespace App\Notifications;

use App\Models\BailMobiliteSignature;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkflowCompletionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $signature;

    public function __construct(BailMobiliteSignature $signature)
    {
        $this->signature = $signature;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $bailMobilite = $this->signature->bailMobilite;
        $contractTemplate = $this->signature->contractTemplate;

        return (new MailMessage)
            ->subject('✅ Signature de contrat complétée - ' . $bailMobilite->address)
            ->greeting('Bonjour,')
            ->line('Le processus de signature multi-parties a été complété avec succès pour le contrat suivant :')
            ->line('**Locataire:** ' . $bailMobilite->tenant_name)
            ->line('**Adresse:** ' . $bailMobilite->address)
            ->line('**Type de contrat:** ' . $contractTemplate->name)
            ->line('**Date de complétion:** ' . now()->format('d/m/Y à H:i'))
            ->line('**Parties ayant signé:** ' . $this->getSignatoriesList())
            ->action('Voir le contrat signé', route('signatures.show', $this->signature->id))
            ->line('Le contrat signé est maintenant disponible dans le système et peut être téléchargé.')
            ->salutation('Cordialement, \nL\'équipe Bail Mobilité');
    }

    protected function getSignatoriesList(): string
    {
        $signatories = $this->signature->getCompletedSignatures();
        $names = [];

        foreach ($signatories as $signatory) {
            $names[] = $signatory['name'] . ' (' . $this->getRoleDisplayName($signatory['party']) . ')';
        }

        return implode(', ', $names);
    }

    protected function getRoleDisplayName(string $role): string
    {
        $roles = [
            'tenant' => 'Locataire',
            'admin' => 'Administrateur',
            'landlord' => 'Propriétaire',
            'agent' => 'Agent',
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
            'type' => 'workflow_completed',
            'signature_id' => $this->signature->id,
            'bail_mobilite_id' => $this->signature->bail_mobilite_id,
            'tenant_name' => $this->signature->bailMobilite->tenant_name,
            'address' => $this->signature->bailMobilite->address,
            'completed_at' => now()->toISOString(),
            'signatories' => $this->signature->getCompletedSignatures()
        ];
    }
}