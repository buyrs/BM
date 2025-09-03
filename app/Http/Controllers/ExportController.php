<?php

namespace App\Http\Controllers;

use App\Services\ExportService;
use App\Models\BailMobilite;
use App\Models\Mission;
use App\Models\Checklist;
use App\Models\IncidentReport;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use OwenIt\Auditing\Models\Audit;

class ExportController extends Controller
{
    protected ExportService $exportService;

    public function __construct(ExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    /**
     * Export bail mobilités
     */
    public function exportBailMobilites(Request $request)
    {
        Gate::authorize('export_data');

        $request->validate([
            'format' => 'required|in:csv,json',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'status' => 'nullable|in:assigned,in_progress,completed,incident',
            'ops_user_id' => 'nullable|exists:users,id',
        ]);

        $query = BailMobilite::with([
            'opsUser:id,name',
            'entryMission.agent.user:id,name',
            'exitMission.agent.user:id,name',
        ]);

        // Apply filters
        if ($request->date_from) {
            $query->where('start_date', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->where('end_date', '<=', $request->date_to);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->ops_user_id) {
            $query->where('ops_user_id', $request->ops_user_id);
        }

        $bailMobilites = $query->get();

        if ($request->format === 'csv') {
            return $this->exportService->exportBailMobilitesToCsv($bailMobilites);
        }

        return response()->json(
            $this->exportService->exportToJson('bail_mobilites', $bailMobilites)
        );
    }

    /**
     * Export missions
     */
    public function exportMissions(Request $request)
    {
        Gate::authorize('export_data');

        $request->validate([
            'format' => 'required|in:csv,json',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'status' => 'nullable|in:pending,assigned,in_progress,completed,cancelled',
            'type' => 'nullable|in:entry,exit',
            'agent_id' => 'nullable|exists:agents,id',
        ]);

        $query = Mission::with([
            'agent.user:id,name',
            'bailMobilite.opsUser:id,name',
        ]);

        // Apply filters
        if ($request->date_from) {
            $query->where('scheduled_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->where('scheduled_at', '<=', $request->date_to);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->type) {
            $query->where('type', $request->type);
        }
        if ($request->agent_id) {
            $query->where('agent_id', $request->agent_id);
        }

        $missions = $query->get();

        if ($request->format === 'csv') {
            return $this->exportService->exportMissionsToCsv($missions);
        }

        return response()->json(
            $this->exportService->exportToJson('missions', $missions)
        );
    }

    /**
     * Export checklists
     */
    public function exportChecklists(Request $request)
    {
        Gate::authorize('export_data');

        $request->validate([
            'format' => 'required|in:csv,json',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'status' => 'nullable|in:pending,submitted,validated,rejected',
            'mission_type' => 'nullable|in:entry,exit',
        ]);

        $query = Checklist::with([
            'mission.agent.user:id,name',
            'validatedBy:id,name',
            'items',
            'photos',
        ]);

        // Apply filters
        if ($request->date_from) {
            $query->where('created_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->where('created_at', '<=', $request->date_to);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->mission_type) {
            $query->whereHas('mission', function ($q) use ($request) {
                $q->where('type', $request->mission_type);
            });
        }

        $checklists = $query->get();

        if ($request->format === 'csv') {
            return $this->exportService->exportChecklistsToCsv($checklists);
        }

        return response()->json(
            $this->exportService->exportToJson('checklists', $checklists)
        );
    }

    /**
     * Export incidents
     */
    public function exportIncidents(Request $request)
    {
        Gate::authorize('export_data');

        $request->validate([
            'format' => 'required|in:csv,json',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'type' => 'nullable|string',
            'severity' => 'nullable|in:low,medium,high,critical',
            'status' => 'nullable|in:open,in_progress,resolved,closed',
        ]);

        $query = IncidentReport::with([
            'reportedBy:id,name',
            'assignedTo:id,name',
            'mission',
            'bailMobilite',
        ]);

        // Apply filters
        if ($request->date_from) {
            $query->where('created_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->where('created_at', '<=', $request->date_to);
        }
        if ($request->type) {
            $query->where('type', $request->type);
        }
        if ($request->severity) {
            $query->where('severity', $request->severity);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $incidents = $query->get();

        if ($request->format === 'csv') {
            return $this->exportService->exportIncidentsToCsv($incidents);
        }

        return response()->json(
            $this->exportService->exportToJson('incidents', $incidents)
        );
    }

    /**
     * Export audit trail
     */
    public function exportAuditTrail(Request $request)
    {
        Gate::authorize('export_audit_trail');

        $request->validate([
            'format' => 'required|in:csv,json',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'user_id' => 'nullable|exists:users,id',
            'event' => 'nullable|string',
            'auditable_type' => 'nullable|string',
        ]);

        $query = Audit::with('user:id,name');

        // Apply filters
        if ($request->date_from) {
            $query->where('created_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->where('created_at', '<=', $request->date_to);
        }
        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->event) {
            $query->where('event', $request->event);
        }
        if ($request->auditable_type) {
            $query->where('auditable_type', $request->auditable_type);
        }

        $auditLogs = $query->orderBy('created_at', 'desc')->get();

        if ($request->format === 'csv') {
            return $this->exportService->exportAuditTrailToCsv($auditLogs);
        }

        return response()->json(
            $this->exportService->exportToJson('audit_trail', $auditLogs)
        );
    }

    /**
     * Export comprehensive analytics data
     */
    public function exportAnalytics(Request $request)
    {
        Gate::authorize('export_data');

        $request->validate([
            'format' => 'required|in:json,csv',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $startDate = $request->date_from 
            ? Carbon::parse($request->date_from) 
            : Carbon::now()->subMonths(6);
        
        $endDate = $request->date_to 
            ? Carbon::parse($request->date_to) 
            : Carbon::now();

        $analyticsData = $this->exportService->getAnalyticsData($startDate, $endDate);

        if ($request->format === 'json') {
            return response()->json([
                'type' => 'analytics',
                'exported_at' => now()->toISOString(),
                'data' => $analyticsData,
            ]);
        }

        // For CSV, we'll create a summary CSV
        $filename = 'analytics_summary_' . now()->format('Y-m-d_H-i-s') . '.csv';

        return response()->streamDownload(function () use ($analyticsData) {
            $handle = fopen('php://output', 'w');

            // Summary section
            fputcsv($handle, ['Analytics Summary']);
            fputcsv($handle, ['Period', $analyticsData['period']['start'] . ' to ' . $analyticsData['period']['end']]);
            fputcsv($handle, []);

            // Basic metrics
            fputcsv($handle, ['Metric', 'Count']);
            foreach ($analyticsData['summary'] as $metric => $count) {
                fputcsv($handle, [ucfirst(str_replace('_', ' ', $metric)), $count]);
            }
            fputcsv($handle, []);

            // Status distribution
            fputcsv($handle, ['Status Distribution']);
            fputcsv($handle, ['Status', 'Count']);
            foreach ($analyticsData['status_distribution'] as $status => $count) {
                fputcsv($handle, [ucfirst(str_replace('_', ' ', $status)), $count]);
            }
            fputcsv($handle, []);

            // Checker performance
            fputcsv($handle, ['Checker Performance']);
            fputcsv($handle, ['Name', 'Missions Completed', 'Success Rate (%)', 'Avg Completion Time (h)']);
            foreach ($analyticsData['checker_performance'] as $checker) {
                fputcsv($handle, [
                    $checker['name'],
                    $checker['missions_completed'],
                    $checker['success_rate'],
                    $checker['avg_completion_time'],
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}