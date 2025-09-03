<?php

namespace App\Services;

use App\Models\BailMobilite;
use App\Models\Mission;
use App\Models\Checklist;
use App\Models\User;
use App\Models\IncidentReport;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    /**
     * Export bail mobilités to CSV
     */
    public function exportBailMobilitesToCsv(Collection $bailMobilites): StreamedResponse
    {
        $filename = 'bail_mobilites_' . now()->format('Y-m-d_H-i-s') . '.csv';

        return new StreamedResponse(function () use ($bailMobilites) {
            $handle = fopen('php://output', 'w');

            // CSV headers
            fputcsv($handle, [
                'ID',
                'Tenant Name',
                'Tenant Email',
                'Tenant Phone',
                'Address',
                'Start Date',
                'End Date',
                'Status',
                'Ops User',
                'Entry Checker',
                'Exit Checker',
                'Entry Status',
                'Exit Status',
                'Entry Completed At',
                'Exit Completed At',
                'Duration (Days)',
                'Created At',
                'Updated At',
            ]);

            // CSV data
            foreach ($bailMobilites as $bm) {
                $duration = $bm->start_date && $bm->end_date 
                    ? $bm->start_date->diffInDays($bm->end_date) 
                    : null;

                fputcsv($handle, [
                    $bm->id,
                    $bm->tenant_name,
                    $bm->tenant_email,
                    $bm->tenant_phone,
                    $bm->address,
                    $bm->start_date?->format('Y-m-d'),
                    $bm->end_date?->format('Y-m-d'),
                    $bm->status,
                    $bm->opsUser?->name ?? '',
                    $bm->entryMission?->agent?->user?->name ?? '',
                    $bm->exitMission?->agent?->user?->name ?? '',
                    $bm->entryMission?->status ?? 'pending',
                    $bm->exitMission?->status ?? 'pending',
                    $bm->entryMission?->completed_at?->format('Y-m-d H:i:s'),
                    $bm->exitMission?->completed_at?->format('Y-m-d H:i:s'),
                    $duration,
                    $bm->created_at->format('Y-m-d H:i:s'),
                    $bm->updated_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Export missions to CSV
     */
    public function exportMissionsToCsv(Collection $missions): StreamedResponse
    {
        $filename = 'missions_' . now()->format('Y-m-d_H-i-s') . '.csv';

        return new StreamedResponse(function () use ($missions) {
            $handle = fopen('php://output', 'w');

            // CSV headers
            fputcsv($handle, [
                'ID',
                'Type',
                'Status',
                'Tenant Name',
                'Address',
                'Scheduled At',
                'Completed At',
                'Assigned Checker',
                'Ops User',
                'Priority',
                'Duration (Hours)',
                'Notes',
                'Created At',
                'Updated At',
            ]);

            // CSV data
            foreach ($missions as $mission) {
                $duration = $mission->actual_start_time && $mission->actual_end_time
                    ? $mission->actual_start_time->diffInHours($mission->actual_end_time)
                    : null;

                fputcsv($handle, [
                    $mission->id,
                    $mission->type,
                    $mission->status,
                    $mission->tenant_name,
                    $mission->address,
                    $mission->scheduled_at?->format('Y-m-d H:i:s'),
                    $mission->completed_at?->format('Y-m-d H:i:s'),
                    $mission->agent?->user?->name ?? '',
                    $mission->bailMobilite?->opsUser?->name ?? '',
                    $mission->priority ?? 'normal',
                    $duration,
                    $mission->notes,
                    $mission->created_at->format('Y-m-d H:i:s'),
                    $mission->updated_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Export checklists to CSV
     */
    public function exportChecklistsToCsv(Collection $checklists): StreamedResponse
    {
        $filename = 'checklists_' . now()->format('Y-m-d_H-i-s') . '.csv';

        return new StreamedResponse(function () use ($checklists) {
            $handle = fopen('php://output', 'w');

            // CSV headers
            fputcsv($handle, [
                'ID',
                'Mission ID',
                'Mission Type',
                'Status',
                'Checker',
                'Property Address',
                'Items Count',
                'Photos Count',
                'Issues Count',
                'Tenant Comment',
                'Ops Comment',
                'Validated At',
                'Validated By',
                'Created At',
                'Updated At',
            ]);

            // CSV data
            foreach ($checklists as $checklist) {
                $itemsCount = $checklist->items?->count() ?? 0;
                $photosCount = $checklist->photos?->count() ?? 0;
                $issuesCount = $checklist->items?->where('condition', 'damaged')->count() ?? 0;

                fputcsv($handle, [
                    $checklist->id,
                    $checklist->mission_id,
                    $checklist->mission?->type ?? '',
                    $checklist->status,
                    $checklist->mission?->agent?->user?->name ?? '',
                    $checklist->mission?->address ?? '',
                    $itemsCount,
                    $photosCount,
                    $issuesCount,
                    $checklist->tenant_comment,
                    $checklist->ops_validation_comments,
                    $checklist->validated_at?->format('Y-m-d H:i:s'),
                    $checklist->validatedBy?->name ?? '',
                    $checklist->created_at->format('Y-m-d H:i:s'),
                    $checklist->updated_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Export incidents to CSV
     */
    public function exportIncidentsToCsv(Collection $incidents): StreamedResponse
    {
        $filename = 'incidents_' . now()->format('Y-m-d_H-i-s') . '.csv';

        return new StreamedResponse(function () use ($incidents) {
            $handle = fopen('php://output', 'w');

            // CSV headers
            fputcsv($handle, [
                'ID',
                'Type',
                'Severity',
                'Status',
                'Title',
                'Description',
                'Mission ID',
                'Bail Mobilité ID',
                'Reported By',
                'Assigned To',
                'Resolution Time (Hours)',
                'Created At',
                'Resolved At',
            ]);

            // CSV data
            foreach ($incidents as $incident) {
                $resolutionTime = $incident->created_at && $incident->resolved_at
                    ? $incident->created_at->diffInHours($incident->resolved_at)
                    : null;

                fputcsv($handle, [
                    $incident->id,
                    $incident->type,
                    $incident->severity,
                    $incident->status,
                    $incident->title,
                    $incident->description,
                    $incident->mission_id,
                    $incident->bail_mobilite_id,
                    $incident->reportedBy?->name ?? '',
                    $incident->assignedTo?->name ?? '',
                    $resolutionTime,
                    $incident->created_at->format('Y-m-d H:i:s'),
                    $incident->resolved_at?->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Export audit trail to CSV
     */
    public function exportAuditTrailToCsv(Collection $auditLogs): StreamedResponse
    {
        $filename = 'audit_trail_' . now()->format('Y-m-d_H-i-s') . '.csv';

        return new StreamedResponse(function () use ($auditLogs) {
            $handle = fopen('php://output', 'w');

            // CSV headers
            fputcsv($handle, [
                'ID',
                'User',
                'Action',
                'Model Type',
                'Model ID',
                'Old Values',
                'New Values',
                'IP Address',
                'User Agent',
                'Created At',
            ]);

            // CSV data
            foreach ($auditLogs as $log) {
                fputcsv($handle, [
                    $log->id,
                    $log->user?->name ?? 'System',
                    $log->event,
                    $log->auditable_type,
                    $log->auditable_id,
                    json_encode($log->old_values ?? []),
                    json_encode($log->new_values ?? []),
                    $log->ip_address ?? '',
                    $log->user_agent ?? '',
                    $log->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Export data to JSON format
     */
    public function exportToJson(string $type, Collection $data): array
    {
        $exportData = [
            'type' => $type,
            'exported_at' => now()->toISOString(),
            'count' => $data->count(),
            'data' => $data->toArray(),
        ];

        return $exportData;
    }

    /**
     * Get comprehensive analytics data for export
     */
    public function getAnalyticsData(Carbon $startDate, Carbon $endDate): array
    {
        // Basic metrics
        $bailMobilites = BailMobilite::whereBetween('created_at', [$startDate, $endDate])->get();
        $missions = Mission::whereBetween('created_at', [$startDate, $endDate])->get();
        $checklists = Checklist::whereBetween('created_at', [$startDate, $endDate])->get();
        $incidents = IncidentReport::whereBetween('created_at', [$startDate, $endDate])->get();

        // Performance metrics
        $checkerPerformance = User::role('checker')
            ->with(['agent'])
            ->get()
            ->map(function ($user) use ($startDate, $endDate) {
                $completedMissions = Mission::where('agent_id', $user->agent?->id)
                    ->where('status', 'completed')
                    ->whereBetween('completed_at', [$startDate, $endDate])
                    ->count();

                $totalMissions = Mission::where('agent_id', $user->agent?->id)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count();

                $avgCompletionTime = Mission::where('agent_id', $user->agent?->id)
                    ->where('status', 'completed')
                    ->whereBetween('completed_at', [$startDate, $endDate])
                    ->whereNotNull('actual_start_time')
                    ->whereNotNull('actual_end_time')
                    ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, actual_start_time, actual_end_time)) as avg_hours')
                    ->value('avg_hours');

                return [
                    'name' => $user->name,
                    'email' => $user->email,
                    'missions_completed' => $completedMissions,
                    'missions_total' => $totalMissions,
                    'success_rate' => $totalMissions > 0 ? round(($completedMissions / $totalMissions) * 100, 2) : 0,
                    'avg_completion_time' => round($avgCompletionTime ?? 0, 2),
                ];
            });

        // Monthly trends
        $monthlyTrends = collect();
        $currentDate = $startDate->copy();
        
        while ($currentDate->lte($endDate)) {
            $monthStart = $currentDate->copy()->startOfMonth();
            $monthEnd = $currentDate->copy()->endOfMonth();
            
            if ($monthEnd->gt($endDate)) {
                $monthEnd = $endDate->copy();
            }

            $monthlyTrends->push([
                'month' => $monthStart->format('M Y'),
                'created' => BailMobilite::whereBetween('created_at', [$monthStart, $monthEnd])->count(),
                'completed' => BailMobilite::where('status', 'completed')
                    ->whereBetween('updated_at', [$monthStart, $monthEnd])->count(),
                'incidents' => IncidentReport::whereBetween('created_at', [$monthStart, $monthEnd])->count(),
            ]);

            $currentDate->addMonth();
        }

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'summary' => [
                'bail_mobilites' => $bailMobilites->count(),
                'missions' => $missions->count(),
                'checklists' => $checklists->count(),
                'incidents' => $incidents->count(),
            ],
            'status_distribution' => [
                'assigned' => $bailMobilites->where('status', 'assigned')->count(),
                'in_progress' => $bailMobilites->where('status', 'in_progress')->count(),
                'completed' => $bailMobilites->where('status', 'completed')->count(),
                'incident' => $bailMobilites->where('status', 'incident')->count(),
            ],
            'checker_performance' => $checkerPerformance->toArray(),
            'monthly_trends' => $monthlyTrends->toArray(),
            'incident_analysis' => [
                'by_type' => $incidents->groupBy('type')->map->count(),
                'by_severity' => $incidents->groupBy('severity')->map->count(),
                'avg_resolution_time' => $incidents->where('status', 'resolved')
                    ->avg(function ($incident) {
                        return $incident->created_at->diffInHours($incident->resolved_at ?? now());
                    }),
            ],
        ];
    }
}