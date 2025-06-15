<?php

namespace App\Http\Controllers;

use App\Models\Checklist;
use Illuminate\Http\Request;
use PDF; // alias pour Dompdf
use Illuminate\Support\Facades\Storage;

class PdfController extends Controller
{
    public function generateChecklistPdf(Checklist $checklist)
    {
        $checklist->load(['mission', 'items.photos']);

        $pdf = PDF::loadView('pdf.checklist', compact('checklist'));

        // Sauvegarder en local dans storage/app/public/pdf
        $fileName = 'checklist_' . $checklist->id . '.pdf';
        $filePath = "pdf/{$fileName}";
        Storage::disk('public')->put($filePath, $pdf->output());

        return response()->download(storage_path('app/public/' . $filePath));
    }

    public function sharedChecklistPdf($token)
    {
        $sharedLink = \App\Models\SharedLink::where('token', $token)->firstOrFail();

        if ($sharedLink->expires_at->isPast()) {
            abort(403, 'This link has expired.');
        }

        $checklist = $sharedLink->checklist()->with(['mission', 'items.photos'])->first();

        $pdf = PDF::loadView('pdf.checklist', compact('checklist'));

        return $pdf->stream('checklist_' . $checklist->id . '.pdf');
    }
}