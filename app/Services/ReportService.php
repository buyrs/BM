<?php

namespace App\Services;

use App\Models\BailMobilite;
use App\Models\Mission;
use App\Models\Checklist;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportService
{
    /**
     * Generate mission report PDF
     */
    public function generateMissionReport(Mission $mission): \Illuminate\Http\Response
    {
        $data = [
            'mission' => $mission->load([
                'agent.user',
                'bailMobilite.opsUser',
                'checklist.items',
                'checklist.photos',
            ]),
            'generated_at' => now(),
        ];

        $pdf = Pdf::loadView('reports.mission', $data);
        
        return $pdf->download("mission_report_{$mission->id}.pdf");
    }

    /**
     * Generate checklist report PDF
     */
    public function generateChecklistReport(Checklist $checklist): \Illuminate\Http\Response
    {
        $data = [
            'checklist' => $checklist->load([
                'mission.agent.user',
                'mission.bailMobilite',
                'items',
                'photos',
                'validatedBy',
            ]),
            'generated_at' => now(),
        ];

        $pdf = Pdf::loadView('reports.checklist', $data);
        
        return $pdf->download("checklist_report_{$checklist->id}.pdf");
    }

    /**
     * Generate bail mobilité summary report PDF
     */
    public function generateBailMobiliteReport(BailMobilite $bailMobilite): \Illuminate\Http\Response
    {
        $data = [
            'bailMobilite' => $bailMobilite->load([
                'opsUser',
                'entryMission.agent.user',
                'exitMission.agent.user',
                'entryMission.checklist.items',
                'exitMission.checklist.items',
                'signatures',
                'incidentReports',
            ]),
            'generated_at' => now(),
        ];

        $pdf = Pdf::loadView('reports.bail-mobilite', $data);
        
        return $pdf->download("bail_mobilite_report_{$bailMobilite->id}.pdf");
    }

    /**
     * Generate performance report PDF
     */
    public function generatePerformanceReport(Carbon $startDate, Carbon $endDate, ?int $checkerId = null): \Illuminate\Http\Response
    {
        $checkers = User::role('checker')
            ->with(['agent'])
            ->when($checkerId, fn($q) => $q->where('id', $checkerId))
            ->get();

        $performanceData = $checkers->map(function ($checker) use ($startDate, $endDate) {
            $missions = Mission::where('agent_id', $checker->agent?->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();

            $completedMissions = $missions->where('status', 'completed');
            $avgCompletionTime = $completedMissions
                ->filter(fn($m) => $m->actual_start_time && $m->actual_end_time)
                ->avg(fn($m) => $m->actual_start_time->diffInHours($m->actual_end_time));

            return [
                'checker' => $checker,
                'total_missions' => $missions->count(),
                'completed_missions' => $completedMissions->count(),
                'success_rate' => $missions->count() > 0 
                    ? round(($completedMissions->count() / $missions->count()) * 100, 2) 
                    : 0,
                'avg_completion_time' => round($avgCompletionTime ?? 0, 2),
                'missions_by_status' => $missions->groupBy('status')->map->count(),
            ];
        });

        $data = [
            'performance_data' => $performanceData,
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'generated_at' => now(),
        ];

        $pdf = Pdf::loadView('reports.performance', $data);
        
        $filename = $checkerId 
            ? "checker_performance_report_{$checkerId}.pdf"
            : "team_performance_report.pdf";
            
        return $pdf->download($filename);
    }

    /**
     * Generate incident report PDF
     */
    public function generateIncidentReport(Carbon $startDate, Carbon $endDate): \Illuminate\Http\Response
    {
        $incidents = \App\Models\IncidentReport::with([
            'reportedBy',
            'assignedTo',
            'mission',
            'bailMobilite',
            'correctiveActions',
        ])
        ->whereBetween('created_at', [$startDate, $endDate])
        ->orderBy('created_at', 'desc')
        ->get();

        $incidentAnalysis = [
            'total' => $incidents->count(),
            'by_type' => $incidents->groupBy('type')->map->count(),
            'by_severity' => $incidents->groupBy('severity')->map->count(),
            'by_status' => $incidents->groupBy('status')->map->count(),
            'avg_resolution_time' => $incidents->where('status', 'resolved')
                ->avg(fn($i) => $i->created_at->diffInHours($i->resolved_at ?? now())),
        ];

        $data = [
            'incidents' => $incidents,
            'analysis' => $incidentAnalysis,
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'generated_at' => now(),
        ];

        $pdf = Pdf::loadView('reports.incidents', $data);
        
        return $pdf->download("incident_report_{$startDate->format('Y-m-d')}_to_{$endDate->format('Y-m-d')}.pdf");
    }

    /**
     * Generate comprehensive analytics report PDF
     */
    public function generateAnalyticsReport(Carbon $startDate, Carbon $endDate): \Illuminate\Http\Response
    {
        $exportService = app(ExportService::class);
        $analyticsData = $exportService->getAnalyticsData($startDate, $endDate);

        // Additional calculations for the report
        $bailMobilites = BailMobilite::whereBetween('created_at', [$startDate, $endDate])->get();
        $missions = Mission::whereBetween('created_at', [$startDate, $endDate])->get();

        $additionalMetrics = [
            'avg_bail_duration' => $bailMobilites->avg(function ($bm) {
                return $bm->start_date && $bm->end_date 
                    ? $bm->start_date->diffInDays($bm->end_date) 
                    : 0;
            }),
            'mission_completion_rate' => $missions->count() > 0 
                ? round(($missions->where('status', 'completed')->count() / $missions->count()) * 100, 2)
                : 0,
            'on_time_completion_rate' => $missions->where('status', 'completed')
                ->filter(fn($m) => $m->completed_at && $m->scheduled_at && $m->completed_at->lte($m->scheduled_at))
                ->count(),
        ];

        $data = [
            'analytics' => $analyticsData,
            'additional_metrics' => $additionalMetrics,
            'generated_at' => now(),
        ];

        $pdf = Pdf::loadView('reports.analytics', $data);
        
        return $pdf->download("analytics_report_{$startDate->format('Y-m-d')}_to_{$endDate->format('Y-m-d')}.pdf");
    }

    /**
     * Generate contract summary report PDF
     */
    public function generateContractReport(Carbon $startDate, Carbon $endDate): \Illuminate\Http\Response
    {
        $contracts = \App\Models\BailMobiliteSignature::with([
            'bailMobilite.opsUser',
            'contractTemplate',
        ])
        ->whereBetween('signed_at', [$startDate, $endDate])
        ->orderBy('signed_at', 'desc')
        ->get();

        $contractAnalysis = [
            'total_signed' => $contracts->count(),
            'by_type' => $contracts->groupBy('signature_type')->map->count(),
            'by_template' => $contracts->groupBy('contract_template_id')->map->count(),
            'avg_signing_time' => $contracts->avg(function ($contract) {
                return $contract->bailMobilite->created_at->diffInHours($contract->signed_at);
            }),
        ];

        $data = [
            'contracts' => $contracts,
            'analysis' => $contractAnalysis,
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'generated_at' => now(),
        ];

        $pdf = Pdf::loadView('reports.contracts', $data);
        
        return $pdf->download("contract_report_{$startDate->format('Y-m-d')}_to_{$endDate->format('Y-m-d')}.pdf");
    }
}