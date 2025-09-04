<?php

namespace App\Services;

use App\Models\Checklist;
use App\Models\Mission;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class PdfGenerationService
{
    /**
     * Generate checklist PDF
     */
    public function generateChecklistPdf(Checklist $checklist, array $options = []): array
    {
        try {
            $checklist->load(['mission.agent', 'mission.bailMobilite', 'items.photos', 'validatedBy']);

            // Prepare data for PDF
            $pdfData = $this->prepareChecklistData($checklist, $options);

            // Generate PDF
            $pdf = Pdf::loadView('pdf.checklist', $pdfData);
            
            // Set PDF options
            $this->configurePdfOptions($pdf, $options);

            // Generate filename
            $filename = $this->generateFilename($checklist, $options);
            $filePath = "pdf/checklists/{$filename}";

            // Save PDF to storage
            Storage::disk('public')->put($filePath, $pdf->output());

            // Log PDF generation
            Log::info('Checklist PDF generated', [
                'checklist_id' => $checklist->id,
                'filename' => $filename,
                'file_path' => $filePath
            ]);

            return [
                'success' => true,
                'filename' => $filename,
                'file_path' => $filePath,
                'url' => Storage::disk('public')->url($filePath),
                'size' => Storage::disk('public')->size($filePath)
            ];

        } catch (Exception $e) {
            Log::error('Failed to generate checklist PDF', [
                'checklist_id' => $checklist->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate mission PDF
     */
    public function generateMissionPdf(Mission $mission, array $options = []): array
    {
        try {
            $mission->load(['agent', 'bailMobilite', 'checklist.items.photos']);

            // Prepare data for PDF
            $pdfData = $this->prepareMissionData($mission, $options);

            // Generate PDF
            $pdf = Pdf::loadView('pdf.mission', $pdfData);
            
            // Set PDF options
            $this->configurePdfOptions($pdf, $options);

            // Generate filename
            $filename = $this->generateMissionFilename($mission, $options);
            $filePath = "pdf/missions/{$filename}";

            // Save PDF to storage
            Storage::disk('public')->put($filePath, $pdf->output());

            return [
                'success' => true,
                'filename' => $filename,
                'file_path' => $filePath,
                'url' => Storage::disk('public')->url($filePath),
                'size' => Storage::disk('public')->size($filePath)
            ];

        } catch (Exception $e) {
            Log::error('Failed to generate mission PDF', [
                'mission_id' => $mission->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Prepare checklist data for PDF
     */
    protected function prepareChecklistData(Checklist $checklist, array $options = []): array
    {
        $mission = $checklist->mission;
        
        return [
            'checklist' => $checklist,
            'mission' => $mission,
            'property_info' => [
                'address' => $mission->address,
                'reference' => $mission->reference ?? 'BM-' . $mission->id,
                'type' => $mission->mission_type,
                'scheduled_date' => $mission->scheduled_date,
                'agent_name' => $mission->agent?->name,
                'tenant_name' => $mission->bailMobilite?->tenant_name ?? $mission->tenant_name
            ],
            'tenant_info' => [
                'name' => $mission->bailMobilite?->tenant_name ?? $mission->tenant_name,
                'email' => $mission->bailMobilite?->tenant_email ?? $mission->tenant_email,
                'phone' => $mission->bailMobilite?->tenant_phone ?? $mission->tenant_phone
            ],
            'options' => array_merge([
                'include_photos' => true,
                'include_signatures' => true,
                'include_validation' => true,
                'watermark' => false,
                'company_logo' => true
            ], $options)
        ];
    }

    /**
     * Prepare mission data for PDF
     */
    protected function prepareMissionData(Mission $mission, array $options = []): array
    {
        return [
            'mission' => $mission,
            'property_info' => [
                'address' => $mission->address,
                'reference' => $mission->reference ?? 'BM-' . $mission->id,
                'type' => $mission->mission_type,
                'scheduled_date' => $mission->scheduled_date,
                'status' => $mission->status
            ],
            'tenant_info' => [
                'name' => $mission->bailMobilite?->tenant_name ?? $mission->tenant_name,
                'email' => $mission->bailMobilite?->tenant_email ?? $mission->tenant_email,
                'phone' => $mission->bailMobilite?->tenant_phone ?? $mission->tenant_phone
            ],
            'options' => array_merge([
                'include_checklist' => true,
                'include_photos' => true,
                'include_signatures' => true
            ], $options)
        ];
    }

    /**
     * Configure PDF options
     */
    protected function configurePdfOptions($pdf, array $options = []): void
    {
        $defaultOptions = [
            'compress' => true,
            'dpi' => 150,
            'image-dpi' => 150,
            'image-quality' => 80,
            'defaultFont' => 'Arial',
            'isRemoteEnabled' => true,
            'isHtml5ParserEnabled' => true,
            'isPhpEnabled' => false
        ];

        $pdfOptions = array_merge($defaultOptions, $options);

        foreach ($pdfOptions as $key => $value) {
            $pdf->setOption($key, $value);
        }
    }

    /**
     * Generate filename for checklist PDF
     */
    protected function generateFilename(Checklist $checklist, array $options = []): string
    {
        $prefix = $options['prefix'] ?? 'checklist';
        $timestamp = $options['timestamp'] ?? now()->format('Y-m-d_H-i-s');
        $suffix = $options['suffix'] ?? '';
        
        return "{$prefix}_{$checklist->id}_{$timestamp}{$suffix}.pdf";
    }

    /**
     * Generate filename for mission PDF
     */
    protected function generateMissionFilename(Mission $mission, array $options = []): string
    {
        $prefix = $options['prefix'] ?? 'mission';
        $timestamp = $options['timestamp'] ?? now()->format('Y-m-d_H-i-s');
        $suffix = $options['suffix'] ?? '';
        
        return "{$prefix}_{$mission->id}_{$timestamp}{$suffix}.pdf";
    }

    /**
     * Generate PDF with custom template
     */
    public function generateCustomPdf(string $template, array $data, array $options = []): array
    {
        try {
            $pdf = Pdf::loadView($template, $data);
            $this->configurePdfOptions($pdf, $options);

            $filename = $options['filename'] ?? 'document_' . time() . '.pdf';
            $filePath = "pdf/custom/{$filename}";

            Storage::disk('public')->put($filePath, $pdf->output());

            return [
                'success' => true,
                'filename' => $filename,
                'file_path' => $filePath,
                'url' => Storage::disk('public')->url($filePath),
                'size' => Storage::disk('public')->size($filePath)
            ];

        } catch (Exception $e) {
            Log::error('Failed to generate custom PDF', [
                'template' => $template,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate PDF stream (for direct download)
     */
    public function generatePdfStream(Checklist $checklist, array $options = []): \Barryvdh\DomPDF\PDF
    {
        $checklist->load(['mission.agent', 'mission.bailMobilite', 'items.photos', 'validatedBy']);
        $pdfData = $this->prepareChecklistData($checklist, $options);
        
        $pdf = Pdf::loadView('pdf.checklist', $pdfData);
        $this->configurePdfOptions($pdf, $options);
        
        return $pdf;
    }

    /**
     * Clean up old PDF files
     */
    public function cleanupOldPdfs(int $daysOld = 30): int
    {
        $deletedCount = 0;
        $cutoffDate = now()->subDays($daysOld);
        
        try {
            $pdfDirectories = [
                'pdf/checklists',
                'pdf/missions',
                'pdf/custom'
            ];

            foreach ($pdfDirectories as $directory) {
                $files = Storage::disk('public')->files($directory);
                
                foreach ($files as $file) {
                    $lastModified = Storage::disk('public')->lastModified($file);
                    
                    if ($lastModified < $cutoffDate->timestamp) {
                        Storage::disk('public')->delete($file);
                        $deletedCount++;
                    }
                }
            }

            Log::info('PDF cleanup completed', [
                'deleted_files' => $deletedCount,
                'days_old' => $daysOld
            ]);

        } catch (Exception $e) {
            Log::error('PDF cleanup failed', [
                'error' => $e->getMessage()
            ]);
        }

        return $deletedCount;
    }

    /**
     * Get PDF statistics
     */
    public function getPdfStatistics(): array
    {
        $statistics = [
            'total_files' => 0,
            'total_size' => 0,
            'by_type' => [
                'checklists' => 0,
                'missions' => 0,
                'custom' => 0
            ]
        ];

        try {
            $pdfDirectories = [
                'pdf/checklists' => 'checklists',
                'pdf/missions' => 'missions',
                'pdf/custom' => 'custom'
            ];

            foreach ($pdfDirectories as $directory => $type) {
                $files = Storage::disk('public')->files($directory);
                $statistics['by_type'][$type] = count($files);
                
                foreach ($files as $file) {
                    $statistics['total_size'] += Storage::disk('public')->size($file);
                }
            }

            $statistics['total_files'] = array_sum($statistics['by_type']);
            $statistics['total_size_mb'] = round($statistics['total_size'] / (1024 * 1024), 2);

        } catch (Exception $e) {
            Log::error('Failed to get PDF statistics', [
                'error' => $e->getMessage()
            ]);
        }

        return $statistics;
    }
}
