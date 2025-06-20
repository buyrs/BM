<?php

namespace App\Http\Controllers;

use App\Models\Checklist;
use Illuminate\Http\Request;
use PDF; // alias pour Dompdf
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class PdfController extends Controller
{
    public function generateChecklistPdf(Checklist $checklist)
    {
        try {
            $checklist->load(['mission', 'items.photos']);

            $pdf = PDF::loadView('pdf.checklist', compact('checklist'));
            
            // Set PDF options for better compression
            $pdf->setOption('compress', true);
            $pdf->setOption('dpi', 150); // Lower DPI for smaller file size
            $pdf->setOption('image-dpi', 150);
            $pdf->setOption('image-quality', 80);

            // Generate unique filename
            $fileName = 'checklist_' . $checklist->id . '_' . time() . '.pdf';
            $filePath = "pdf/{$fileName}";
            
            // Save with compression
            Storage::disk('public')->put($filePath, $pdf->output());

            return response()->download(storage_path('app/public/' . $filePath))
                ->deleteFileAfterSend(true); // Clean up after download
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

            $pdf = PDF::loadView('pdf.checklist', compact('checklist'));
            
            // Set PDF options for better compression
            $pdf->setOption('compress', true);
            $pdf->setOption('dpi', 150);
            $pdf->setOption('image-dpi', 150);
            $pdf->setOption('image-quality', 80);

            return $pdf->stream('checklist_' . $checklist->id . '.pdf');
        } catch (Exception $e) {
            Log::error('Shared PDF generation failed: ' . $e->getMessage());
            abort(500, 'Unable to generate PDF. Please try again later.');
        }
    }
}