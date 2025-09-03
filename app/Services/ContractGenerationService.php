<?php

namespace App\Services;

use App\Models\ContractTemplate;
use App\Models\BailMobilite;
use App\Models\BailMobiliteSignature;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ContractGenerationService
{
    /**
     * Generate a contract from a template with bail mobilité data.
     */
    public function generateContract(ContractTemplate $template, BailMobilite $bailMobilite, string $signatureType = 'entry'): array
    {
        // Prepare data for placeholder replacement
        $contractData = $this->prepareContractData($template, $bailMobilite, $signatureType);
        
        // Replace placeholders in content
        $processedContent = $this->replacePlaceholders($template->content, $contractData);
        
        return [
            'content' => $processedContent,
            'data' => $contractData,
            'template' => $template,
            'bail_mobilite' => $bailMobilite,
            'signature_type' => $signatureType,
            'is_html' => $this->isHtmlContent($template->content),
        ];
    }

    /**
     * Generate a signed PDF contract.
     */
    public function generateSignedPdf(
        ContractTemplate $template, 
        BailMobilite $bailMobilite, 
        BailMobiliteSignature $tenantSignature,
        string $signatureType = 'entry'
    ): string {
        // Generate contract content
        $contract = $this->generateContract($template, $bailMobilite, $signatureType);
        
        // Create HTML content for PDF
        $htmlContent = $this->createPdfHtml($contract, $tenantSignature);
        
        // Generate PDF (this would use a PDF library like DomPDF or wkhtmltopdf)
        $pdfPath = $this->generatePdfFromHtml($htmlContent, $bailMobilite, $signatureType);
        
        return $pdfPath;
    }

    /**
     * Prepare contract data for placeholder replacement.
     */
    private function prepareContractData(ContractTemplate $template, BailMobilite $bailMobilite, string $signatureType): array
    {
        return [
            'tenant_name' => $bailMobilite->tenant_name,
            'tenant_email' => $bailMobilite->tenant_email ?? 'Non renseigné',
            'tenant_phone' => $bailMobilite->tenant_phone ?? 'Non renseigné',
            'address' => $bailMobilite->address,
            'start_date' => Carbon::parse($bailMobilite->start_date)->format('d/m/Y'),
            'end_date' => Carbon::parse($bailMobilite->end_date)->format('d/m/Y'),
            'admin_name' => $template->creator->name ?? 'Administrateur',
            'admin_signature_date' => $template->admin_signed_at ? 
                $template->admin_signed_at->format('d/m/Y H:i') : 
                'Non signé',
            'contract_type' => $signatureType === 'entry' ? 'Entrée' : 'Sortie',
            'contract_reference' => 'BM-' . $bailMobilite->id . '-' . strtoupper($signatureType),
            'generation_date' => Carbon::now()->format('d/m/Y H:i'),
        ];
    }

    /**
     * Replace placeholders in content.
     */
    private function replacePlaceholders(string $content, array $data): string
    {
        foreach ($data as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }

        return $content;
    }

    /**
     * Check if content is HTML.
     */
    private function isHtmlContent(string $content): bool
    {
        return $content !== strip_tags($content);
    }

    /**
     * Create HTML content for PDF generation.
     */
    private function createPdfHtml(array $contract, BailMobiliteSignature $tenantSignature): string
    {
        $content = $contract['content'];
        $data = $contract['data'];
        $template = $contract['template'];
        $bailMobilite = $contract['bail_mobilite'];
        
        // Convert rich text content to PDF-friendly HTML if needed
        if ($contract['is_html']) {
            $content = $this->sanitizeHtmlForPdf($content);
        } else {
            $content = '<div style="white-space: pre-wrap; font-family: Arial, sans-serif; line-height: 1.6;">' . 
                      htmlspecialchars($content) . '</div>';
        }

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Contrat de Bail Mobilité - ' . $data['contract_reference'] . '</title>
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    line-height: 1.6; 
                    margin: 40px;
                    color: #333;
                }
                .header { 
                    text-align: center; 
                    margin-bottom: 30px; 
                    border-bottom: 2px solid #333;
                    padding-bottom: 20px;
                }
                .contract-info {
                    background: #f8f9fa;
                    padding: 15px;
                    margin: 20px 0;
                    border-left: 4px solid #007bff;
                }
                .signatures {
                    margin-top: 40px;
                    display: flex;
                    justify-content: space-between;
                }
                .signature-block {
                    width: 45%;
                    text-align: center;
                    border: 1px solid #ddd;
                    padding: 20px;
                    min-height: 120px;
                }
                .signature-image {
                    max-width: 200px;
                    max-height: 80px;
                    margin: 10px 0;
                }
                .footer {
                    margin-top: 40px;
                    text-align: center;
                    font-size: 12px;
                    color: #666;
                    border-top: 1px solid #ddd;
                    padding-top: 20px;
                }
                h1, h2, h3 { color: #333; }
                strong { font-weight: bold; }
                em { font-style: italic; }
                ul, ol { margin: 10px 0; padding-left: 30px; }
                hr { border: none; border-top: 1px solid #ddd; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>CONTRAT DE BAIL MOBILITÉ</h1>
                <h2>' . $data['contract_type'] . '</h2>
                <p><strong>Référence:</strong> ' . $data['contract_reference'] . '</p>
            </div>

            <div class="contract-info">
                <h3>Informations du Contrat</h3>
                <p><strong>Locataire:</strong> ' . $data['tenant_name'] . '</p>
                <p><strong>Adresse:</strong> ' . $data['address'] . '</p>
                <p><strong>Période:</strong> du ' . $data['start_date'] . ' au ' . $data['end_date'] . '</p>
                <p><strong>Généré le:</strong> ' . $data['generation_date'] . '</p>
            </div>

            <div class="content">
                ' . $content . '
            </div>

            <div class="signatures">
                <div class="signature-block">
                    <h4>Signature du Propriétaire/Hôte</h4>';
        
        if ($template->admin_signature) {
            $html .= '<img src="' . $template->admin_signature . '" alt="Signature Admin" class="signature-image">';
        }
        
        $html .= '
                    <p><strong>' . $data['admin_name'] . '</strong></p>
                    <p>Signé le: ' . $data['admin_signature_date'] . '</p>
                </div>

                <div class="signature-block">
                    <h4>Signature du Locataire</h4>';
        
        if ($tenantSignature->signature_data) {
            $html .= '<img src="' . $tenantSignature->signature_data . '" alt="Signature Locataire" class="signature-image">';
        }
        
        $html .= '
                    <p><strong>' . $tenantSignature->signer_name . '</strong></p>
                    <p>Signé le: ' . $tenantSignature->signed_at->format('d/m/Y H:i') . '</p>
                </div>
            </div>

            <div class="footer">
                <p>Ce document a été généré électroniquement et constitue un contrat légalement contraignant.</p>
                <p>Généré le ' . Carbon::now()->format('d/m/Y à H:i') . '</p>
            </div>
        </body>
        </html>';

        return $html;
    }

    /**
     * Sanitize HTML content for PDF generation.
     */
    private function sanitizeHtmlForPdf(string $html): string
    {
        // Remove potentially problematic HTML elements and attributes
        $allowedTags = '<p><br><strong><b><em><i><u><ul><ol><li><h1><h2><h3><h4><h5><h6><hr>';
        
        return strip_tags($html, $allowedTags);
    }

    /**
     * Generate PDF from HTML content.
     */
    private function generatePdfFromHtml(string $html, BailMobilite $bailMobilite, string $signatureType): string
    {
        // This is a placeholder for PDF generation
        // In a real implementation, you would use a library like:
        // - DomPDF: $pdf = PDF::loadHTML($html);
        // - wkhtmltopdf: exec("wkhtmltopdf ...");
        // - Puppeteer: via Node.js service
        
        $filename = 'contracts/BM-' . $bailMobilite->id . '-' . $signatureType . '-' . time() . '.pdf';
        
        // For now, we'll save the HTML content as a file
        // In production, this should generate an actual PDF
        Storage::disk('public')->put($filename . '.html', $html);
        
        return $filename;
    }

    /**
     * Validate contract template for generation.
     */
    public function validateTemplate(ContractTemplate $template): array
    {
        $errors = [];

        if (!$template->is_active) {
            $errors[] = 'Template is not active';
        }

        if (!$template->admin_signature) {
            $errors[] = 'Template is not signed by admin';
        }

        if (empty($template->content)) {
            $errors[] = 'Template content is empty';
        }

        return $errors;
    }

    /**
     * Get available placeholders for templates.
     */
    public function getAvailablePlaceholders(): array
    {
        return [
            'tenant_name' => 'Tenant Name',
            'tenant_email' => 'Tenant Email',
            'tenant_phone' => 'Tenant Phone',
            'address' => 'Property Address',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'admin_name' => 'Admin Name',
            'admin_signature_date' => 'Admin Signature Date',
            'contract_type' => 'Contract Type (Entry/Exit)',
            'contract_reference' => 'Contract Reference',
            'generation_date' => 'Generation Date',
        ];
    }
}