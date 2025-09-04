<?php

namespace App\Services;

use App\Models\ContractTemplate;
use App\Models\BailMobiliteSignature;
use App\Models\BailMobilite;
use App\Models\User;
use App\Services\AuditService;
use App\Services\SignatureService;
use App\Services\ContractGenerationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class ContractService
{
    public function __construct(
        private SignatureService $signatureService,
        private ContractGenerationService $contractGenerationService
    ) {}

    /**
     * Create a new contract template
     */
    public function createContractTemplate(array $data, ?User $user = null): ContractTemplate
    {
        $user = $user ?? auth()->user();

        DB::beginTransaction();
        try {
            $template = ContractTemplate::create([
                'name' => $data['name'],
                'type' => $data['type'],
                'content' => $data['content'],
                'is_active' => $data['is_active'] ?? false,
                'created_by' => $user->id
            ]);

            // Log template creation
            AuditService::logCreated($template, $user, [
                'template_type' => $data['type'],
                'is_active' => $template->is_active
            ]);

            DB::commit();

            Log::info("Contract template created successfully", [
                'template_id' => $template->id,
                'template_name' => $template->name,
                'created_by' => $user->name
            ]);

            return $template;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to create contract template", [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Update contract template
     */
    public function updateContractTemplate(ContractTemplate $template, array $data, ?User $user = null): ContractTemplate
    {
        $user = $user ?? auth()->user();
        $oldValues = $template->getAttributes();

        DB::beginTransaction();
        try {
            $template->update($data);

            // Log template update
            AuditService::logUpdated($template, $oldValues, $user, [
                'template_name' => $template->name,
                'content_changed' => isset($data['content']) && $data['content'] !== $oldValues['content']
            ]);

            DB::commit();

            Log::info("Contract template updated successfully", [
                'template_id' => $template->id,
                'updated_by' => $user->name
            ]);

            return $template;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to update contract template", [
                'template_id' => $template->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Sign contract template as admin
     */
    public function signContractTemplate(ContractTemplate $template, string $signatureData, ?User $user = null): ContractTemplate
    {
        $user = $user ?? auth()->user();

        if (!$user->hasRole(['admin', 'super-admin'])) {
            throw new \UnauthorizedHttpException('', 'Only admin users can sign contract templates');
        }

        DB::beginTransaction();
        try {
            $template->update([
                'admin_signature' => $signatureData,
                'admin_signed_at' => now()
            ]);

            // Log admin signature
            AuditService::logUpdated($template, ['admin_signature' => null], $user, [
                'admin_signed' => true,
                'signature_timestamp' => now()
            ]);

            DB::commit();

            Log::info("Contract template signed by admin", [
                'template_id' => $template->id,
                'signed_by' => $user->name
            ]);

            return $template;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to sign contract template", [
                'template_id' => $template->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Create tenant signature for bail mobilité
     */
    public function createTenantSignature(
        BailMobilite $bailMobilite,
        string $signatureType,
        string $signatureData,
        int $contractTemplateId,
        ?User $user = null
    ): BailMobiliteSignature {
        $user = $user ?? auth()->user();

        DB::beginTransaction();
        try {
            // Get contract template
            $contractTemplate = ContractTemplate::findOrFail($contractTemplateId);

            if (!$contractTemplate->isReadyForUse()) {
                throw new \InvalidArgumentException('Contract template is not ready for use');
            }

            // Create signature using signature service
            $signature = $this->signatureService->createTenantSignature(
                $bailMobilite,
                $signatureType,
                $signatureData,
                [
                    'contract_template_id' => $contractTemplateId,
                    'signed_by_user_id' => $user->id,
                    'signature_method' => 'electronic_pad'
                ]
            );

            // Generate PDF contract
            $pdfPath = $this->generateContractPdf($signature, $contractTemplate);
            $signature->update(['contract_pdf_path' => $pdfPath]);

            // Log signature creation
            AuditService::logCreated($signature, $user, [
                'bail_mobilite_id' => $bailMobilite->id,
                'signature_type' => $signatureType,
                'contract_template_id' => $contractTemplateId
            ]);

            DB::commit();

            Log::info("Tenant signature created successfully", [
                'signature_id' => $signature->id,
                'bail_mobilite_id' => $bailMobilite->id,
                'signature_type' => $signatureType
            ]);

            return $signature;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to create tenant signature", [
                'bail_mobilite_id' => $bailMobilite->id,
                'signature_type' => $signatureType,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generate PDF contract with signatures
     */
    public function generateContractPdf(BailMobiliteSignature $signature, ContractTemplate $template): string
    {
        try {
            $bailMobilite = $signature->bailMobilite;
            
            // Prepare contract data
            $contractData = [
                'bail_mobilite' => $bailMobilite,
                'signature' => $signature,
                'template' => $template,
                'admin_signature' => $template->admin_signature,
                'tenant_signature' => $signature->tenant_signature,
                'admin_signed_at' => $template->admin_signed_at,
                'tenant_signed_at' => $signature->tenant_signed_at,
                'generated_at' => now()
            ];

            // Generate PDF using contract generation service
            $pdf = $this->contractGenerationService->generateContractPdf($contractData);
            
            // Save PDF to storage
            $filename = 'contract_' . $bailMobilite->id . '_' . $signature->signature_type . '_' . time() . '.pdf';
            $path = 'contracts/' . $filename;
            
            \Storage::disk('private')->put($path, $pdf->output());
            
            Log::info("Contract PDF generated successfully", [
                'signature_id' => $signature->id,
                'pdf_path' => $path
            ]);

            return $path;

        } catch (\Exception $e) {
            Log::error("Failed to generate contract PDF", [
                'signature_id' => $signature->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Verify signature integrity
     */
    public function verifySignatureIntegrity(BailMobiliteSignature $signature): array
    {
        try {
            $isValid = $this->signatureService->verifySignatureIntegrity($signature);
            
            return [
                'is_valid' => $isValid,
                'verified_at' => now(),
                'signature_id' => $signature->id,
                'verification_method' => 'integrity_hash'
            ];

        } catch (\Exception $e) {
            Log::error("Failed to verify signature integrity", [
                'signature_id' => $signature->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'is_valid' => false,
                'error' => $e->getMessage(),
                'signature_id' => $signature->id
            ];
        }
    }

    /**
     * Get active contract templates by type
     */
    public function getActiveTemplatesByType(string $type): \Illuminate\Database\Eloquent\Collection
    {
        return ContractTemplate::active()
            ->where('type', $type)
            ->whereNotNull('admin_signature')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get contract statistics
     */
    public function getContractStatistics(User $user = null): array
    {
        $query = BailMobiliteSignature::query();

        if ($user && $user->hasRole('ops')) {
            $query->whereHas('bailMobilite', function($q) use ($user) {
                $q->where('ops_user_id', $user->id);
            });
        }

        $stats = $query->selectRaw('
            COUNT(*) as total_signatures,
            COUNT(CASE WHEN signature_type = "entry" THEN 1 END) as entry_signatures,
            COUNT(CASE WHEN signature_type = "exit" THEN 1 END) as exit_signatures,
            COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as signatures_last_30_days,
            COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as signatures_last_7_days
        ')->first();

        return [
            'total_signatures' => $stats->total_signatures ?? 0,
            'entry_signatures' => $stats->entry_signatures ?? 0,
            'exit_signatures' => $stats->exit_signatures ?? 0,
            'signatures_last_30_days' => $stats->signatures_last_30_days ?? 0,
            'signatures_last_7_days' => $stats->signatures_last_7_days ?? 0
        ];
    }

    /**
     * Get contract templates for management
     */
    public function getContractTemplatesForManagement(User $user = null): \Illuminate\Database\Eloquent\Collection
    {
        if (!$user || !$user->hasRole(['admin', 'super-admin'])) {
            return collect();
        }

        return ContractTemplate::with(['creator'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Delete contract template
     */
    public function deleteContractTemplate(ContractTemplate $template, ?User $user = null): bool
    {
        $user = $user ?? auth()->user();

        if (!$user->hasRole(['admin', 'super-admin'])) {
            throw new \UnauthorizedHttpException('', 'Only admin users can delete contract templates');
        }

        // Check if template is being used
        $usageCount = $template->signatures()->count();
        if ($usageCount > 0) {
            throw new \InvalidArgumentException("Cannot delete template: {$usageCount} signatures are using this template");
        }

        DB::beginTransaction();
        try {
            // Log template deletion
            AuditService::logDeleted($template, $user, [
                'template_name' => $template->name,
                'template_type' => $template->type
            ]);

            $template->delete();

            DB::commit();

            Log::info("Contract template deleted successfully", [
                'template_id' => $template->id,
                'deleted_by' => $user->name
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to delete contract template", [
                'template_id' => $template->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get signature history for a bail mobilité
     */
    public function getSignatureHistory(BailMobilite $bailMobilite): \Illuminate\Database\Eloquent\Collection
    {
        return $bailMobilite->signatures()
            ->with(['contractTemplate'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Validate contract template content
     */
    public function validateContractTemplate(array $data): array
    {
        $errors = [];

        // Check required fields
        if (empty($data['name'])) {
            $errors['name'] = 'Template name is required';
        }

        if (empty($data['type'])) {
            $errors['type'] = 'Template type is required';
        }

        if (empty($data['content'])) {
            $errors['content'] = 'Template content is required';
        }

        // Validate template type
        if (!in_array($data['type'], ['entry', 'exit'])) {
            $errors['type'] = 'Template type must be either "entry" or "exit"';
        }

        // Check for required placeholders in content
        $requiredPlaceholders = ['{{tenant_name}}', '{{property_address}}', '{{start_date}}', '{{end_date}}'];
        foreach ($requiredPlaceholders as $placeholder) {
            if (strpos($data['content'], $placeholder) === false) {
                $errors['content'] = "Template content must include placeholder: {$placeholder}";
                break;
            }
        }

        return $errors;
    }

    /**
     * Process contract template content with data
     */
    public function processContractContent(string $content, array $data): string
    {
        $placeholders = [
            '{{tenant_name}}' => $data['tenant_name'] ?? '',
            '{{property_address}}' => $data['property_address'] ?? '',
            '{{start_date}}' => $data['start_date'] ?? '',
            '{{end_date}}' => $data['end_date'] ?? '',
            '{{tenant_phone}}' => $data['tenant_phone'] ?? '',
            '{{tenant_email}}' => $data['tenant_email'] ?? '',
            '{{current_date}}' => now()->format('d/m/Y'),
            '{{current_time}}' => now()->format('H:i')
        ];

        return str_replace(array_keys($placeholders), array_values($placeholders), $content);
    }
}
