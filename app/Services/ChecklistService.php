<?php

namespace App\Services;

use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\ChecklistPhoto;
use App\Models\Mission;
use App\Models\User;
use App\Services\AuditService;
use App\Services\IncidentDetectionService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ChecklistService
{
    public function __construct(
        private IncidentDetectionService $incidentDetectionService,
        private NotificationService $notificationService
    ) {}

    /**
     * Create a new checklist for a mission
     */
    public function createChecklist(Mission $mission, array $data, ?User $user = null): Checklist
    {
        $user = $user ?? auth()->user();

        DB::beginTransaction();
        try {
            // Create checklist with default structure
            $checklist = Checklist::create([
                'mission_id' => $mission->id,
                'general_info' => $data['general_info'] ?? [],
                'rooms' => $data['rooms'] ?? [],
                'utilities' => $data['utilities'] ?? [],
                'status' => 'draft'
            ]);

            // Create checklist items if provided
            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    $this->createChecklistItem($checklist, $itemData, $user);
                }
            }

            // Log checklist creation
            AuditService::logCreated($checklist, $user, [
                'mission_id' => $mission->id,
                'mission_type' => $mission->mission_type
            ]);

            DB::commit();

            Log::info("Checklist created successfully", [
                'checklist_id' => $checklist->id,
                'mission_id' => $mission->id,
                'created_by' => $user->name
            ]);

            return $checklist;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to create checklist", [
                'mission_id' => $mission->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Update checklist with validation and incident detection
     */
    public function updateChecklist(Checklist $checklist, array $data, ?User $user = null): Checklist
    {
        $user = $user ?? auth()->user();
        $oldValues = $checklist->getAttributes();

        DB::beginTransaction();
        try {
            $checklist->update($data);

            // Update checklist items if provided
            if (isset($data['items']) && is_array($data['items'])) {
                $this->updateChecklistItems($checklist, $data['items'], $user);
            }

            // Detect incidents based on checklist data
            $this->incidentDetectionService->detectIncidentsFromChecklist($checklist);

            // Log checklist update
            AuditService::logUpdated($checklist, $oldValues, $user, [
                'mission_id' => $checklist->mission_id,
                'status_changed' => isset($data['status']) && $data['status'] !== $oldValues['status']
            ]);

            DB::commit();

            Log::info("Checklist updated successfully", [
                'checklist_id' => $checklist->id,
                'updated_by' => $user->name
            ]);

            return $checklist;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to update checklist", [
                'checklist_id' => $checklist->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Submit checklist for validation
     */
    public function submitChecklist(Checklist $checklist, array $data, ?User $user = null): Checklist
    {
        $user = $user ?? auth()->user();

        DB::beginTransaction();
        try {
            // Validate checklist completeness
            if (!$this->isChecklistComplete($checklist)) {
                throw new \InvalidArgumentException('Checklist is not complete. Please fill all required fields.');
            }

            // Update checklist with submission data
            $checklist->update([
                'status' => 'submitted',
                'tenant_signature' => $data['tenant_signature'] ?? null,
                'agent_signature' => $data['agent_signature'] ?? null,
                'submitted_at' => now()
            ]);

            // Send notification to ops for validation
            $this->notificationService->sendChecklistValidationAlert($checklist->mission);

            // Detect incidents from submitted checklist
            $this->incidentDetectionService->detectIncidentsFromChecklist($checklist);

            // Log checklist submission
            AuditService::logUpdated($checklist, ['status' => 'draft'], $user, [
                'mission_id' => $checklist->mission_id,
                'submitted_for_validation' => true
            ]);

            DB::commit();

            Log::info("Checklist submitted for validation", [
                'checklist_id' => $checklist->id,
                'mission_id' => $checklist->mission_id,
                'submitted_by' => $user->name
            ]);

            return $checklist;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to submit checklist", [
                'checklist_id' => $checklist->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Validate checklist (used by ops users)
     */
    public function validateChecklist(Checklist $checklist, array $data, ?User $user = null): Checklist
    {
        $user = $user ?? auth()->user();

        if (!$user->hasRole('ops')) {
            throw new \UnauthorizedHttpException('', 'Only ops users can validate checklists');
        }

        DB::beginTransaction();
        try {
            $checklist->update([
                'status' => $data['status'], // 'validated' or 'rejected'
                'ops_validation_comments' => $data['comments'] ?? null,
                'validated_by' => $user->id,
                'validated_at' => now()
            ]);

            // Update mission status if validated
            if ($data['status'] === 'validated') {
                $checklist->mission->update(['status' => 'completed']);
            }

            // Log checklist validation
            AuditService::logUpdated($checklist, ['status' => 'submitted'], $user, [
                'validation_status' => $data['status'],
                'validation_comments' => $data['comments'] ?? null
            ]);

            DB::commit();

            Log::info("Checklist validation completed", [
                'checklist_id' => $checklist->id,
                'validation_status' => $data['status'],
                'validated_by' => $user->name
            ]);

            return $checklist;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to validate checklist", [
                'checklist_id' => $checklist->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Create a checklist item
     */
    public function createChecklistItem(Checklist $checklist, array $data, ?User $user = null): ChecklistItem
    {
        $user = $user ?? auth()->user();

        $item = ChecklistItem::create([
            'checklist_id' => $checklist->id,
            'category' => $data['category'],
            'item_name' => $data['item_name'],
            'condition' => $data['condition'],
            'notes' => $data['notes'] ?? null,
            'is_required' => $data['is_required'] ?? false,
            'created_by' => $user->id
        ]);

        // Log item creation
        AuditService::logCreated($item, $user, [
            'checklist_id' => $checklist->id,
            'category' => $data['category']
        ]);

        return $item;
    }

    /**
     * Update checklist items
     */
    protected function updateChecklistItems(Checklist $checklist, array $items, ?User $user = null): void
    {
        foreach ($items as $itemData) {
            if (isset($itemData['id'])) {
                // Update existing item
                $item = ChecklistItem::find($itemData['id']);
                if ($item && $item->checklist_id === $checklist->id) {
                    $item->update($itemData);
                }
            } else {
                // Create new item
                $this->createChecklistItem($checklist, $itemData, $user);
            }
        }
    }

    /**
     * Upload photo for checklist item
     */
    public function uploadChecklistPhoto(ChecklistItem $item, $file, ?User $user = null): ChecklistPhoto
    {
        $user = $user ?? auth()->user();

        // Validate file
        if (!$this->isValidPhotoFile($file)) {
            throw new \InvalidArgumentException('Invalid photo file');
        }

        // Generate unique filename
        $filename = 'checklist_' . $item->checklist_id . '_' . $item->id . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path = 'checklists/photos/' . $filename;

        // Store file
        Storage::disk('private')->put($path, file_get_contents($file));

        // Create photo record
        $photo = ChecklistPhoto::create([
            'checklist_item_id' => $item->id,
            'filename' => $filename,
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'uploaded_by' => $user->id
        ]);

        // Log photo upload
        AuditService::logFileOperation('upload', $filename, $user, [
            'checklist_item_id' => $item->id,
            'file_size' => $file->getSize()
        ]);

        return $photo;
    }

    /**
     * Delete checklist photo
     */
    public function deleteChecklistPhoto(ChecklistPhoto $photo, ?User $user = null): bool
    {
        $user = $user ?? auth()->user();

        DB::beginTransaction();
        try {
            // Delete file from storage
            if (Storage::disk('private')->exists($photo->file_path)) {
                Storage::disk('private')->delete($photo->file_path);
            }

            // Log photo deletion
            AuditService::logFileOperation('delete', $photo->filename, $user, [
                'checklist_item_id' => $photo->checklist_item_id
            ]);

            $photo->delete();

            DB::commit();

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to delete checklist photo", [
                'photo_id' => $photo->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Check if checklist is complete
     */
    public function isChecklistComplete(Checklist $checklist): bool
    {
        // Check if all required items are filled
        $requiredItems = $checklist->items()->where('is_required', true)->get();
        
        foreach ($requiredItems as $item) {
            if (empty($item->condition) || empty($item->notes)) {
                return false;
            }
        }

        // Check if signatures are present
        if (empty($checklist->tenant_signature) || empty($checklist->agent_signature)) {
            return false;
        }

        return true;
    }

    /**
     * Get checklist statistics
     */
    public function getChecklistStatistics(User $user = null): array
    {
        $query = Checklist::query();

        if ($user && $user->hasRole('checker')) {
            $query->whereHas('mission', function($q) use ($user) {
                $q->where('agent_id', $user->id);
            });
        }

        $stats = $query->selectRaw('
            COUNT(*) as total_checklists,
            COUNT(CASE WHEN status = "draft" THEN 1 END) as draft_checklists,
            COUNT(CASE WHEN status = "submitted" THEN 1 END) as submitted_checklists,
            COUNT(CASE WHEN status = "validated" THEN 1 END) as validated_checklists,
            COUNT(CASE WHEN status = "rejected" THEN 1 END) as rejected_checklists
        ')->first();

        return [
            'total' => $stats->total_checklists ?? 0,
            'draft' => $stats->draft_checklists ?? 0,
            'submitted' => $stats->submitted_checklists ?? 0,
            'validated' => $stats->validated_checklists ?? 0,
            'rejected' => $stats->rejected_checklists ?? 0,
            'validation_rate' => $stats->total_checklists > 0 
                ? round((($stats->validated_checklists ?? 0) / $stats->total_checklists) * 100, 2) 
                : 0
        ];
    }

    /**
     * Get checklists for validation (ops users)
     */
    public function getChecklistsForValidation(User $user = null): \Illuminate\Database\Eloquent\Collection
    {
        if (!$user || !$user->hasRole('ops')) {
            return collect();
        }

        return Checklist::where('status', 'submitted')
                       ->with(['mission.agent', 'mission.bailMobilite'])
                       ->orderBy('submitted_at', 'asc')
                       ->get();
    }

    /**
     * Validate photo file
     */
    protected function isValidPhotoFile($file): bool
    {
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 10 * 1024 * 1024; // 10MB

        return in_array($file->getMimeType(), $allowedMimes) && 
               $file->getSize() <= $maxSize;
    }

    /**
     * Generate checklist report
     */
    public function generateChecklistReport(Checklist $checklist): array
    {
        $checklist->load(['mission.agent', 'mission.bailMobilite', 'items.photos']);

        return [
            'checklist_id' => $checklist->id,
            'mission_id' => $checklist->mission_id,
            'mission_type' => $checklist->mission->mission_type,
            'tenant_name' => $checklist->mission->bailMobilite?->tenant_name ?? $checklist->mission->tenant_name,
            'address' => $checklist->mission->address,
            'agent_name' => $checklist->mission->agent?->name,
            'status' => $checklist->status,
            'submitted_at' => $checklist->submitted_at,
            'validated_at' => $checklist->validated_at,
            'validated_by' => $checklist->validatedBy?->name,
            'general_info' => $checklist->general_info,
            'rooms' => $checklist->rooms,
            'utilities' => $checklist->utilities,
            'items' => $checklist->items->map(function($item) {
                return [
                    'category' => $item->category,
                    'item_name' => $item->item_name,
                    'condition' => $item->condition,
                    'notes' => $item->notes,
                    'photos' => $item->photos->map(function($photo) {
                        return [
                            'filename' => $photo->filename,
                            'original_name' => $photo->original_name,
                            'uploaded_at' => $photo->created_at
                        ];
                    })
                ];
            }),
            'signatures' => [
                'tenant_signature' => !empty($checklist->tenant_signature),
                'agent_signature' => !empty($checklist->agent_signature)
            ]
        ];
    }
}
