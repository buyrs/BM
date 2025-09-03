<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use App\Models\Mission;
use App\Models\Checklist;
use App\Models\BailMobilite;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ReportController extends Controller
{
    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Generate mission report PDF
     */
    public function missionReport(Mission $mission)
    {
        Gate::authorize('view', $mission);
        
        return $this->reportService->generateMissionReport($mission);
    }

    /**
     * Generate checklist report PDF
     */
    public function checklistReport(Checklist $checklist)
    {
        Gate::authorize('view', $checklist);
        
        return $this->reportService->generateChecklistReport($checklist);
    }

    /**
     * Generate bail mobilité report PDF
     */
    public function bailMobiliteReport(BailMobilite $bailMobilite)
    {
        Gate::authorize('view', $bailMobilite);
        
        return $this->reportService->generateBailMobiliteReport($bailMobilite);
    }

    /**
     * Generate performance report PDF
     */
    public function performanceReport(Request $request)
    {
        Gate::authorize('export_data');

        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'checker_id' => 'nullable|exists:users,id',
        ]);

        $startDate = $request->date_from 
            ? Carbon::parse($request->date_from) 
            : Carbon::now()->subMonth();
        
        $endDate = $request->date_to 
            ? Carbon::parse($request->date_to) 
            : Carbon::now();

        $checkerId = $request->checker_id;

        return $this->reportService->generatePerformanceReport($startDate, $endDate, $checkerId);
    }

    /**
     * Generate incident report PDF
     */
    public function incidentReport(Request $request)
    {
        Gate::authorize('export_data');

        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $startDate = $request->date_from 
            ? Carbon::parse($request->date_from) 
            : Carbon::now()->subMonth();
        
        $endDate = $request->date_to 
            ? Carbon::parse($request->date_to) 
            : Carbon::now();

        return $this->reportService->generateIncidentReport($startDate, $endDate);
    }

    /**
     * Generate analytics report PDF
     */
    public function analyticsReport(Request $request)
    {
        Gate::authorize('export_data');

        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $startDate = $request->date_from 
            ? Carbon::parse($request->date_from) 
            : Carbon::now()->subMonths(6);
        
        $endDate = $request->date_to 
            ? Carbon::parse($request->date_to) 
            : Carbon::now();

        return $this->reportService->generateAnalyticsReport($startDate, $endDate);
    }

    /**
     * Generate contract report PDF
     */
    public function contractReport(Request $request)
    {
        Gate::authorize('export_data');

        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $startDate = $request->date_from 
            ? Carbon::parse($request->date_from) 
            : Carbon::now()->subMonth();
        
        $endDate = $request->date_to 
            ? Carbon::parse($request->date_to) 
            : Carbon::now();

        return $this->reportService->generateContractReport($startDate, $endDate);
    }

    /**
     * Generate audit trail report PDF
     */
    public function auditTrailReport(Request $request)
    {
        Gate::authorize('export_audit_trail');

        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'user_id' => 'nullable|exists:users,id',
            'event' => 'nullable|string',
            'auditable_type' => 'nullable|string',
        ]);

        // This would generate a PDF report for audit trail
        // For now, we'll redirect to the audit trail export
        return redirect()->route('api.export.audit-trail', $request->all());
    }
}