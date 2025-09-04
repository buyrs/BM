<?php

namespace App\Http\Controllers;

use App\Models\Checklist;
use App\Models\Mission;
use App\Services\PdfGenerationService;
use Illuminate\Http\Request;
use PDF; // alias pour Dompdf
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class PdfController extends Controller
{
    public function __construct(
        private PdfGenerationService $pdfGenerationService
    ) {}

    public function generateChecklistPdf(Checklist $checklist)
    {
        try {
            $result = $this->pdfGenerationService->generateChecklistPdf($checklist);

            if (!$result['success']) {
                return redirect()->back()->with('error', 'Unable to generate PDF: ' . $result['error']);
            }

            return response()->download(storage_path('app/public/' . $result['file_path']))
                ->deleteFileAfterSend(true);
        } catch (Exception $e) {
            Log::error('PDF generation failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Unable to generate PDF. Please try again.');
        }
    }

    public function sharedChecklistPdf($token)
    {
        try {
            $sharedLink = \App\Models\SharedLink::where('token', $token)->firstOrFail();

            if ($sharedLink->expires_at->isPast()) {
                abort(403, 'This link has expired.');
            }

            $checklist = $sharedLink->checklist()->with(['mission', 'items.photos'])->first();
            $pdf = $this->pdfGenerationService->generatePdfStream($checklist);

            return $pdf->stream('checklist_' . $checklist->id . '.pdf');
        } catch (Exception $e) {
            Log::error('Shared PDF generation failed: ' . $e->getMessage());
            abort(500, 'Unable to generate PDF. Please try again later.');
        }
    }

    public function generateMissionPdf(Mission $mission)
    {
        try {
            $result = $this->pdfGenerationService->generateMissionPdf($mission);

            if (!$result['success']) {
                return redirect()->back()->with('error', 'Unable to generate PDF: ' . $result['error']);
            }

            return response()->download(storage_path('app/public/' . $result['file_path']))
                ->deleteFileAfterSend(true);
        } catch (Exception $e) {
            Log::error('Mission PDF generation failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Unable to generate PDF. Please try again.');
        }
    }

    public function getPdfStatistics()
    {
        try {
            $statistics = $this->pdfGenerationService->getPdfStatistics();
            
            return response()->json([
                'success' => true,
                'statistics' => $statistics
            ]);
        } catch (Exception $e) {
            Log::error('Failed to get PDF statistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to get PDF statistics'
            ], 500);
        }
    }

    public function cleanupOldPdfs(Request $request)
    {
        try {
            $daysOld = $request->input('days_old', 30);
            $deletedCount = $this->pdfGenerationService->cleanupOldPdfs($daysOld);
            
            return response()->json([
                'success' => true,
                'deleted_files' => $deletedCount,
                'message' => "Cleaned up {$deletedCount} old PDF files"
            ]);
        } catch (Exception $e) {
            Log::error('PDF cleanup failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'PDF cleanup failed'
            ], 500);
        }
    }
}