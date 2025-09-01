<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MissionCalendarResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->mission_type,
            'scheduled_at' => $this->scheduled_at?->format('Y-m-d'),
            'scheduled_time' => $this->scheduled_time,
            'status' => $this->status,
            'tenant_name' => $this->tenant_name,
            'tenant_phone' => $this->tenant_phone,
            'tenant_email' => $this->tenant_email,
            'address' => $this->address,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            
            // Agent information
            'agent' => $this->whenLoaded('agent', function () {
                return [
                    'id' => $this->agent->id,
                    'user_id' => $this->agent->user->id,
                    'name' => $this->agent->user->name,
                    'email' => $this->agent->user->email,
                ];
            }),
            
            // Bail Mobilité information
            'bail_mobilite' => $this->whenLoaded('bailMobilite', function () {
                return [
                    'id' => $this->bailMobilite->id,
                    'status' => $this->bailMobilite->status,
                    'start_date' => $this->bailMobilite->start_date?->format('Y-m-d'),
                    'end_date' => $this->bailMobilite->end_date?->format('Y-m-d'),
                    'duration_days' => $this->bailMobilite->getDurationInDays(),
                    'remaining_days' => $this->bailMobilite->getRemainingDays(),
                    'needs_exit_reminder' => $this->bailMobilite->needsExitReminder(),
                    'ops_user' => $this->whenLoaded('bailMobilite.opsUser', function () {
                        return [
                            'id' => $this->bailMobilite->opsUser->id,
                            'name' => $this->bailMobilite->opsUser->name,
                        ];
                    }),
                ];
            }),
            
            // Checklist information
            'checklist' => $this->whenLoaded('checklist', function () {
                return [
                    'id' => $this->checklist->id,
                    'status' => $this->checklist->status,
                    'items_count' => $this->checklist->items->count(),
                    'completed_items_count' => $this->checklist->items->where('status', 'completed')->count(),
                    'photos_count' => $this->checklist->items->sum(function ($item) {
                        return $item->photos->count();
                    }),
                    'validated_at' => $this->checklist->validated_at?->format('Y-m-d H:i:s'),
                    'validator_comments' => $this->checklist->validator_comments,
                ];
            }),
            
            // Ops assignment information
            'ops_assigned_by' => $this->whenLoaded('opsAssignedBy', function () {
                return [
                    'id' => $this->opsAssignedBy->id,
                    'name' => $this->opsAssignedBy->name,
                ];
            }),
            
            // Signatures information (for BM missions)
            'signatures' => $this->whenLoaded('bailMobilite.signatures', function () {
                return $this->bailMobilite->signatures->map(function ($signature) {
                    return [
                        'id' => $signature->id,
                        'signature_type' => $signature->signature_type,
                        'tenant_signature' => !is_null($signature->tenant_signature),
                        'checker_signature' => !is_null($signature->checker_signature),
                        'signed_at' => $signature->signed_at?->format('Y-m-d H:i:s'),
                        'contract_template' => [
                            'id' => $signature->contractTemplate->id,
                            'name' => $signature->contractTemplate->name,
                            'version' => $signature->contractTemplate->version,
                        ],
                    ];
                });
            }),
            
            // Recent notifications
            'recent_notifications' => $this->whenLoaded('bailMobilite.notifications', function () {
                return $this->bailMobilite->notifications->map(function ($notification) {
                    return [
                        'id' => $notification->id,
                        'type' => $notification->type,
                        'status' => $notification->status,
                        'scheduled_at' => $notification->scheduled_at?->format('Y-m-d H:i:s'),
                        'data' => $notification->data,
                    ];
                });
            }),
            
            // Permission flags
            'permissions' => [
                'can_edit' => $this->canEdit(),
                'can_assign' => $this->canAssign(),
                'can_validate' => $this->canValidate(),
                'can_view_details' => $this->canViewDetails(),
            ],
            
            // Status indicators
            'indicators' => [
                'is_overdue' => $this->isOverdue(),
                'has_conflicts' => $this->hasSchedulingConflicts(),
                'needs_attention' => $this->needsAttention(),
                'is_urgent' => $this->isUrgent(),
            ],
        ];
    }
    
    /**
     * Check if the mission can be edited by the current user.
     */
    private function canEdit(): bool
    {
        $user = auth()->user();
        return $user && $user->hasRole(['ops', 'admin']);
    }
    
    /**
     * Check if the mission can be assigned by the current user.
     */
    private function canAssign(): bool
    {
        $user = auth()->user();
        return $user && $user->hasRole(['ops', 'admin']) && $this->status === 'unassigned';
    }
    
    /**
     * Check if the mission can be validated by the current user.
     */
    private function canValidate(): bool
    {
        $user = auth()->user();
        return $user && $user->hasRole(['ops', 'admin']) && $this->status === 'completed';
    }
    
    /**
     * Check if the current user can view mission details.
     */
    private function canViewDetails(): bool
    {
        $user = auth()->user();
        return $user && $user->hasRole(['ops', 'admin', 'checker']);
    }
    
    /**
     * Check if the mission is overdue.
     */
    private function isOverdue(): bool
    {
        if (!$this->scheduled_at) {
            return false;
        }
        
        $scheduledDateTime = $this->scheduled_at;
        if ($this->scheduled_time) {
            $scheduledDateTime = $this->scheduled_at->setTimeFromTimeString($this->scheduled_time);
        }
        
        return $scheduledDateTime->isPast() && !in_array($this->status, ['completed', 'cancelled']);
    }
    
    /**
     * Check if the mission has scheduling conflicts.
     */
    private function hasSchedulingConflicts(): bool
    {
        // This would be implemented based on business logic
        // For now, return false as a placeholder
        return false;
    }
    
    /**
     * Check if the mission needs attention.
     */
    private function needsAttention(): bool
    {
        return $this->isOverdue() || 
               $this->hasSchedulingConflicts() || 
               ($this->bailMobilite && $this->bailMobilite->status === 'incident');
    }
    
    /**
     * Check if the mission is urgent.
     */
    private function isUrgent(): bool
    {
        if (!$this->scheduled_at) {
            return false;
        }
        
        // Mission is urgent if it's scheduled within the next 24 hours
        return $this->scheduled_at->isBefore(now()->addDay()) && 
               !in_array($this->status, ['completed', 'cancelled']);
    }
}