<?php

namespace App\Services;

use App\Models\BailMobilite;
use App\Models\BailMobiliteSignature;
use App\Models\ContractTemplate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class SignatureService
{
    /**
     * Create a tenant signature for a bail mobilite
     */
    public function createTenantSignature(
        BailMobilite $bailMobilite,
        string $signatureType,
        string $signatureData,
        array $metadata = []
    ): BailMobiliteSignature {
        // Get the active contract template for this signature type
        $contractTemplate = ContractTemplate::active()
            ->where('type', $signatureType)
            ->first();

        if (!$contractTemplate) {
            throw new \Exception("No active contract template found for type: {$signatureType}");
        }

        if (!$contractTemplate->admin_signature) {
            throw new \Exception("Contract template must be signed by admin before tenant can sign");
        }

        // Create signature record with metadata
        $signature = BailMobiliteSignature::create([
            'bail_mobilite_id' => $bailMobilite->id,
            'signature_type' => $signatureType,
            'contract_template_id' => $contractTemplate->id,
            'tenant_signature' => $signatureData,
            'tenant_signed_at' => now(),
            'signature_metadata' => array_merge($metadata, [
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'timestamp' => now()->toISOString(),
            ])
        ]);

        // Generate the PDF contract with both signatures
        $pdfPath = $this->generateSignedContract($signature);
        $signature->update(['contract_pdf_path' => $pdfPath]);

        Log::info('Tenant signature created', [
            'bail_mobilite_id' => $bailMobilite->id,
            'signature_type' => $signatureType,
            'signature_id' => $signature->id
        ]);

        return $signature;
    }

    /**
     * Generate PDF contract with both admin and tenant signatures
     */
    public function generateSignedContract(BailMobiliteSignature $signature): string
    {
        $bailMobilite = $signature->bailMobilite;
        $contractTemplate = $signature->contractTemplate;

        // Prepare contract data
        $contractData = [
            'bail_mobilite' => $bailMobilite,
            'contract_template' => $contractTemplate,
            'signature' => $signature,
            'tenant_info' => [
                'name' => $bailMobilite->tenant_name,
                'email' => $bailMobilite->tenant_email,
                'phone' => $bailMobilite->tenant_phone,
            ],
            'property_info' => [
                'address' => $bailMobilite->address,
                'start_date' => $bailMobilite->start_date,
                'end_date' => $bailMobilite->end_date,
            ],
            'signatures' => [
                'admin' => [
                    'signature' => $contractTemplate->admin_signature,
                    'signed_at' => $contractTemplate->admin_signed_at,
                    'name' => $contractTemplate->creator->name ?? 'Administrator'
                ],
                'tenant' => [
                    'signature' => $signature->tenant_signature,
                    'signed_at' => $signature->tenant_signed_at,
                    'name' => $bailMobilite->tenant_name
                ]
            ]
        ];

        // Generate PDF
        $pdf = Pdf::loadView('contracts.signed-contract', $contractData);
        
        // Create filename with timestamp
        $filename = sprintf(
            'contracts/%s_%s_%s_%s.pdf',
            $bailMobilite->id,
            $signature->signature_type,
            $signature->tenant_signed_at->format('Y-m-d_H-i-s'),
            uniqid()
        );

        // Store PDF securely
        Storage::disk('private')->put($filename, $pdf->output());

        return $filename;
    }

    /**
     * Validate signature integrity
     */
    public function validateSignature(BailMobiliteSignature $signature): array
    {
        $validation = [
            'is_valid' => true,
            'errors' => [],
            'warnings' => []
        ];

        // Check if signature data exists
        if (empty($signature->tenant_signature)) {
            $validation['is_valid'] = false;
            $validation['errors'][] = 'Tenant signature is missing';
        }

        // Check if admin signature exists in template
        if (empty($signature->contractTemplate->admin_signature)) {
            $validation['is_valid'] = false;
            $validation['errors'][] = 'Admin signature is missing from contract template';
        }

        // Check if PDF exists
        if ($signature->contract_pdf_path && !Storage::disk('private')->exists($signature->contract_pdf_path)) {
            $validation['is_valid'] = false;
            $validation['errors'][] = 'Signed contract PDF file is missing';
        }

        // Check signature timestamp
        if (!$signature->tenant_signed_at) {
            $validation['is_valid'] = false;
            $validation['errors'][] = 'Signature timestamp is missing';
        }

        // Check if signature is too old (warning only)
        if ($signature->tenant_signed_at && $signature->tenant_signed_at->diffInDays(now()) > 365) {
            $validation['warnings'][] = 'Signature is older than 1 year';
        }

        return $validation;
    }

    /**
     * Get signature metadata for legal verification
     */
    public function getSignatureMetadata(BailMobiliteSignature $signature): array
    {
        return [
            'signature_id' => $signature->id,
            'bail_mobilite_id' => $signature->bail_mobilite_id,
            'signature_type' => $signature->signature_type,
            'tenant_signed_at' => $signature->tenant_signed_at,
            'admin_signed_at' => $signature->contractTemplate->admin_signed_at,
            'contract_template_id' => $signature->contract_template_id,
            'contract_template_name' => $signature->contractTemplate->name,
            'pdf_path' => $signature->contract_pdf_path,
            'metadata' => $signature->signature_metadata ?? [],
            'validation' => $this->validateSignature($signature)
        ];
    }

    /**
     * Retrieve signed contract PDF
     */
    public function getSignedContractPdf(BailMobiliteSignature $signature): ?string
    {
        if (!$signature->contract_pdf_path) {
            return null;
        }

        if (!Storage::disk('private')->exists($signature->contract_pdf_path)) {
            Log::warning('Signed contract PDF not found', [
                'signature_id' => $signature->id,
                'pdf_path' => $signature->contract_pdf_path
            ]);
            return null;
        }

        return Storage::disk('private')->get($signature->contract_pdf_path);
    }

    /**
     * Archive signatures for legal retention
     */
    public function archiveSignatures(BailMobilite $bailMobilite): void
    {
        $signatures = $bailMobilite->signatures;

        foreach ($signatures as $signature) {
            // Create archive record with full metadata
            $archiveData = [
                'original_signature_id' => $signature->id,
                'bail_mobilite_data' => $bailMobilite->toArray(),
                'signature_data' => $signature->toArray(),
                'contract_template_data' => $signature->contractTemplate->toArray(),
                'validation_data' => $this->validateSignature($signature),
                'archived_at' => now()
            ];

            // Store archive data
            $archiveFilename = sprintf(
                'archives/signatures/%s_%s_archive.json',
                $bailMobilite->id,
                $signature->signature_type
            );

            Storage::disk('private')->put($archiveFilename, json_encode($archiveData, JSON_PRETTY_PRINT));

            Log::info('Signature archived', [
                'signature_id' => $signature->id,
                'archive_file' => $archiveFilename
            ]);
        }
    }
}