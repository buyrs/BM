<?php

namespace App\Services;

use App\Models\Mission;
use App\Models\Property;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\User;
use App\Models\MaintenanceRequest;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

class ReportGenerationService extends BaseService
{
    private AnalyticsService $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        parent::__construct();
        $this->analyticsService = $analyticsService;
    }

    /**
     * Generate a comprehensive analytics report
     */
    public function generateAnalyticsReport(
        string $format = 'pdf',
        Carbon $startDate = null,
        Carbon $endDate = null,
        array $sections = []
    ): array {
        $startDate = $startDate ?? Carbon::now()->subDays(30);
        $endDate = $endDate ?? Carbon::now();

        // Default sections if none specified
        if (empty($sections)) {
            $sections = ['mission_metrics', 'user_performance', 'property_metrics', 'maintenance_metrics', 'system_metrics'];
        }

        $reportData = [
            'title' => 'Analytics Report',
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'formatted_period' => $startDate->format('M j, Y') . ' - ' . $endDate->format('M j, Y'),
            ],
            'generated_at' => Carbon::now()->format('Y-m-d H:i:s'),
            'sections' => [],
        ];

        // Collect data for each requested section
        foreach ($sections as $section) {
            switch ($section) {
                case 'mission_metrics':
                    $reportData['sections']['mission_metrics'] = $this->analyticsService->getMissionMetrics($startDate, $endDate);
                    break;
                case 'user_performance':
                    $reportData['sections']['user_performance'] = $this->analyticsService->getUserPerformanceMetrics($startDate, $endDate);
                    break;
                case 'property_metrics':
                    $reportData['sections']['property_metrics'] = $this->analyticsService->getPropertyMetrics($startDate, $endDate);
                    break;
                case 'maintenance_metrics':
                    $reportData['sections']['maintenance_metrics'] = $this->analyticsService->getMaintenanceMetrics($startDate, $endDate);
                    break;
                case 'system_metrics':
                    $reportData['sections']['system_metrics'] = $this->analyticsService->getSystemMetrics();
                    break;
            }
        }

        // Generate the report in the requested format
        switch ($format) {
            case 'pdf':
                return $this->generatePdfReport($reportData);
            case 'excel':
                return $this->generateExcelReport($reportData);
            case 'csv':
                return $this->generateCsvReport($reportData);
            default:
                throw new \InvalidArgumentException("Unsupported report format: {$format}");
        }
    }

    /**
     * Generate mission completion report
     */
    public function generateMissionReport(
        string $format = 'pdf',
        Carbon $startDate = null,
        Carbon $endDate = null,
        array $filters = []
    ): array {
        $startDate = $startDate ?? Carbon::now()->subDays(30);
        $endDate = $endDate ?? Carbon::now();

        $query = Mission::with(['admin', 'ops', 'checker', 'checklists'])
            ->whereBetween('created_at', [$startDate, $endDate]);

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['checker_id'])) {
            $query->where('checker_id', $filters['checker_id']);
        }
        if (!empty($filters['property_type'])) {
            $query->whereHas('property', function ($q) use ($filters) {
                $q->where('property_type', $filters['property_type']);
            });
        }

        $missions = $query->get();

        $reportData = [
            'title' => 'Mission Completion Report',
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'formatted_period' => $startDate->format('M j, Y') . ' - ' . $endDate->format('M j, Y'),
            ],
            'generated_at' => Carbon::now()->format('Y-m-d H:i:s'),
            'filters' => $filters,
            'missions' => $missions,
            'summary' => [
                'total_missions' => $missions->count(),
                'completed_missions' => $missions->where('status', 'completed')->count(),
                'in_progress_missions' => $missions->where('status', 'in_progress')->count(),
                'pending_missions' => $missions->where('status', 'pending')->count(),
            ],
        ];

        switch ($format) {
            case 'pdf':
                return $this->generateMissionPdfReport($reportData);
            case 'excel':
                return $this->generateMissionExcelReport($reportData);
            case 'csv':
                return $this->generateMissionCsvReport($reportData);
            default:
                throw new \InvalidArgumentException("Unsupported report format: {$format}");
        }
    }

    /**
     * Generate user performance report
     */
    public function generateUserPerformanceReport(
        string $format = 'pdf',
        Carbon $startDate = null,
        Carbon $endDate = null,
        string $role = null
    ): array {
        $startDate = $startDate ?? Carbon::now()->subDays(30);
        $endDate = $endDate ?? Carbon::now();

        $query = User::with(['missions', 'opsMissions', 'adminMissions']);
        
        if ($role) {
            $query->where('role', $role);
        }

        $users = $query->get();

        $reportData = [
            'title' => 'User Performance Report',
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'formatted_period' => $startDate->format('M j, Y') . ' - ' . $endDate->format('M j, Y'),
            ],
            'generated_at' => Carbon::now()->format('Y-m-d H:i:s'),
            'role_filter' => $role,
            'users' => $users->map(function ($user) use ($startDate, $endDate) {
                $missions = $user->missions()->whereBetween('created_at', [$startDate, $endDate])->get();
                $completedMissions = $missions->where('status', 'completed');
                
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'total_missions' => $missions->count(),
                    'completed_missions' => $completedMissions->count(),
                    'completion_rate' => $missions->count() > 0 ? round(($completedMissions->count() / $missions->count()) * 100, 2) : 0,
                    'avg_completion_time' => $this->calculateAverageCompletionTime($completedMissions),
                    'last_login' => $user->last_login_at?->format('Y-m-d H:i:s'),
                ];
            }),
        ];

        switch ($format) {
            case 'pdf':
                return $this->generateUserPerformancePdfReport($reportData);
            case 'excel':
                return $this->generateUserPerformanceExcelReport($reportData);
            case 'csv':
                return $this->generateUserPerformanceCsvReport($reportData);
            default:
                throw new \InvalidArgumentException("Unsupported report format: {$format}");
        }
    }

    /**
     * Generate maintenance requests report
     */
    public function generateMaintenanceReport(
        string $format = 'pdf',
        Carbon $startDate = null,
        Carbon $endDate = null,
        array $filters = []
    ): array {
        $startDate = $startDate ?? Carbon::now()->subDays(30);
        $endDate = $endDate ?? Carbon::now();

        $query = MaintenanceRequest::with(['mission', 'checklist', 'reportedBy', 'assignedTo'])
            ->whereBetween('created_at', [$startDate, $endDate]);

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        $maintenanceRequests = $query->get();

        $reportData = [
            'title' => 'Maintenance Requests Report',
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'formatted_period' => $startDate->format('M j, Y') . ' - ' . $endDate->format('M j, Y'),
            ],
            'generated_at' => Carbon::now()->format('Y-m-d H:i:s'),
            'filters' => $filters,
            'maintenance_requests' => $maintenanceRequests,
            'summary' => [
                'total_requests' => $maintenanceRequests->count(),
                'completed_requests' => $maintenanceRequests->where('status', 'completed')->count(),
                'pending_requests' => $maintenanceRequests->where('status', 'pending')->count(),
                'in_progress_requests' => $maintenanceRequests->where('status', 'in_progress')->count(),
                'avg_resolution_time' => $this->calculateAverageResolutionTime($maintenanceRequests->where('status', 'completed')),
            ],
        ];

        switch ($format) {
            case 'pdf':
                return $this->generateMaintenancePdfReport($reportData);
            case 'excel':
                return $this->generateMaintenanceExcelReport($reportData);
            case 'csv':
                return $this->generateMaintenanceCsvReport($reportData);
            default:
                throw new \InvalidArgumentException("Unsupported report format: {$format}");
        }
    }

    /**
     * Generate PDF report using HTML template
     */
    private function generatePdfReport(array $data): array
    {
        $html = View::make('reports.analytics-pdf', $data)->render();
        $filename = 'analytics-report-' . Carbon::now()->format('Y-m-d-H-i-s') . '.pdf';
        
        // In a real implementation, you would use a PDF library like DomPDF or wkhtmltopdf
        // For now, we'll simulate the PDF generation
        $pdfContent = $this->simulatePdfGeneration($html);
        
        $path = 'reports/' . $filename;
        Storage::disk('local')->put($path, $pdfContent);

        return [
            'success' => true,
            'filename' => $filename,
            'path' => $path,
            'size' => strlen($pdfContent),
            'download_url' => route('reports.download', ['filename' => $filename]),
        ];
    }

    /**
     * Generate Excel report
     */
    private function generateExcelReport(array $data): array
    {
        $filename = 'analytics-report-' . Carbon::now()->format('Y-m-d-H-i-s') . '.xlsx';
        
        // In a real implementation, you would use PhpSpreadsheet or similar
        // For now, we'll generate a CSV-like content
        $csvContent = $this->generateAnalyticsCsvContent($data);
        
        $path = 'reports/' . $filename;
        Storage::disk('local')->put($path, $csvContent);

        return [
            'success' => true,
            'filename' => $filename,
            'path' => $path,
            'size' => strlen($csvContent),
            'download_url' => route('reports.download', ['filename' => $filename]),
        ];
    }

    /**
     * Generate CSV report
     */
    private function generateCsvReport(array $data): array
    {
        $filename = 'analytics-report-' . Carbon::now()->format('Y-m-d-H-i-s') . '.csv';
        $csvContent = $this->generateAnalyticsCsvContent($data);
        
        $path = 'reports/' . $filename;
        Storage::disk('local')->put($path, $csvContent);

        return [
            'success' => true,
            'filename' => $filename,
            'path' => $path,
            'size' => strlen($csvContent),
            'download_url' => route('reports.download', ['filename' => $filename]),
        ];
    }

    /**
     * Generate mission-specific PDF report
     */
    private function generateMissionPdfReport(array $data): array
    {
        $html = View::make('reports.missions-pdf', $data)->render();
        $filename = 'missions-report-' . Carbon::now()->format('Y-m-d-H-i-s') . '.pdf';
        
        $pdfContent = $this->simulatePdfGeneration($html);
        $path = 'reports/' . $filename;
        Storage::disk('local')->put($path, $pdfContent);

        return [
            'success' => true,
            'filename' => $filename,
            'path' => $path,
            'size' => strlen($pdfContent),
            'download_url' => route('reports.download', ['filename' => $filename]),
        ];
    }

    /**
     * Generate mission-specific Excel report
     */
    private function generateMissionExcelReport(array $data): array
    {
        $filename = 'missions-report-' . Carbon::now()->format('Y-m-d-H-i-s') . '.xlsx';
        $csvContent = $this->generateMissionsCsvContent($data);
        
        $path = 'reports/' . $filename;
        Storage::disk('local')->put($path, $csvContent);

        return [
            'success' => true,
            'filename' => $filename,
            'path' => $path,
            'size' => strlen($csvContent),
            'download_url' => route('reports.download', ['filename' => $filename]),
        ];
    }

    /**
     * Generate mission-specific CSV report
     */
    private function generateMissionCsvReport(array $data): array
    {
        $filename = 'missions-report-' . Carbon::now()->format('Y-m-d-H-i-s') . '.csv';
        $csvContent = $this->generateMissionsCsvContent($data);
        
        $path = 'reports/' . $filename;
        Storage::disk('local')->put($path, $csvContent);

        return [
            'success' => true,
            'filename' => $filename,
            'path' => $path,
            'size' => strlen($csvContent),
            'download_url' => route('reports.download', ['filename' => $filename]),
        ];
    }

    /**
     * Generate user performance PDF report
     */
    private function generateUserPerformancePdfReport(array $data): array
    {
        $html = View::make('reports.user-performance-pdf', $data)->render();
        $filename = 'user-performance-report-' . Carbon::now()->format('Y-m-d-H-i-s') . '.pdf';
        
        $pdfContent = $this->simulatePdfGeneration($html);
        $path = 'reports/' . $filename;
        Storage::disk('local')->put($path, $pdfContent);

        return [
            'success' => true,
            'filename' => $filename,
            'path' => $path,
            'size' => strlen($pdfContent),
            'download_url' => route('reports.download', ['filename' => $filename]),
        ];
    }

    /**
     * Generate user performance Excel report
     */
    private function generateUserPerformanceExcelReport(array $data): array
    {
        $filename = 'user-performance-report-' . Carbon::now()->format('Y-m-d-H-i-s') . '.xlsx';
        $csvContent = $this->generateUserPerformanceCsvContent($data);
        
        $path = 'reports/' . $filename;
        Storage::disk('local')->put($path, $csvContent);

        return [
            'success' => true,
            'filename' => $filename,
            'path' => $path,
            'size' => strlen($csvContent),
            'download_url' => route('reports.download', ['filename' => $filename]),
        ];
    }

    /**
     * Generate user performance CSV report
     */
    private function generateUserPerformanceCsvReport(array $data): array
    {
        $filename = 'user-performance-report-' . Carbon::now()->format('Y-m-d-H-i-s') . '.csv';
        $csvContent = $this->generateUserPerformanceCsvContent($data);
        
        $path = 'reports/' . $filename;
        Storage::disk('local')->put($path, $csvContent);

        return [
            'success' => true,
            'filename' => $filename,
            'path' => $path,
            'size' => strlen($csvContent),
            'download_url' => route('reports.download', ['filename' => $filename]),
        ];
    }

    /**
     * Generate maintenance PDF report
     */
    private function generateMaintenancePdfReport(array $data): array
    {
        $html = View::make('reports.maintenance-pdf', $data)->render();
        $filename = 'maintenance-report-' . Carbon::now()->format('Y-m-d-H-i-s') . '.pdf';
        
        $pdfContent = $this->simulatePdfGeneration($html);
        $path = 'reports/' . $filename;
        Storage::disk('local')->put($path, $pdfContent);

        return [
            'success' => true,
            'filename' => $filename,
            'path' => $path,
            'size' => strlen($pdfContent),
            'download_url' => route('reports.download', ['filename' => $filename]),
        ];
    }

    /**
     * Generate maintenance Excel report
     */
    private function generateMaintenanceExcelReport(array $data): array
    {
        $filename = 'maintenance-report-' . Carbon::now()->format('Y-m-d-H-i-s') . '.xlsx';
        $csvContent = $this->generateMaintenanceCsvContent($data);
        
        $path = 'reports/' . $filename;
        Storage::disk('local')->put($path, $csvContent);

        return [
            'success' => true,
            'filename' => $filename,
            'path' => $path,
            'size' => strlen($csvContent),
            'download_url' => route('reports.download', ['filename' => $filename]),
        ];
    }

    /**
     * Generate maintenance CSV report
     */
    private function generateMaintenanceCsvReport(array $data): array
    {
        $filename = 'maintenance-report-' . Carbon::now()->format('Y-m-d-H-i-s') . '.csv';
        $csvContent = $this->generateMaintenanceCsvContent($data);
        
        $path = 'reports/' . $filename;
        Storage::disk('local')->put($path, $csvContent);

        return [
            'success' => true,
            'filename' => $filename,
            'path' => $path,
            'size' => strlen($csvContent),
            'download_url' => route('reports.download', ['filename' => $filename]),
        ];
    }

    /**
     * Simulate PDF generation (in real implementation, use DomPDF or similar)
     */
    private function simulatePdfGeneration(string $html): string
    {
        return "PDF Content: " . $html;
    }

    /**
     * Generate CSV content for analytics data
     */
    private function generateAnalyticsCsvContent(array $data): string
    {
        $csv = "Analytics Report\n";
        $csv .= "Generated: {$data['generated_at']}\n";
        $csv .= "Period: {$data['period']['formatted_period']}\n\n";

        foreach ($data['sections'] as $sectionName => $sectionData) {
            $csv .= strtoupper(str_replace('_', ' ', $sectionName)) . "\n";
            $csv .= $this->arrayToCsv($sectionData) . "\n";
        }

        return $csv;
    }

    /**
     * Generate CSV content for missions data
     */
    private function generateMissionsCsvContent(array $data): string
    {
        $csv = "Mission ID,Title,Property Address,Status,Checker,Created At,Updated At\n";
        
        foreach ($data['missions'] as $mission) {
            $csv .= sprintf(
                "%d,%s,%s,%s,%s,%s,%s\n",
                $mission->id,
                $this->escapeCsv($mission->title),
                $this->escapeCsv($mission->property_address),
                $mission->status,
                $mission->checker ? $this->escapeCsv($mission->checker->name) : 'N/A',
                $mission->created_at->format('Y-m-d H:i:s'),
                $mission->updated_at->format('Y-m-d H:i:s')
            );
        }

        return $csv;
    }

    /**
     * Generate CSV content for user performance data
     */
    private function generateUserPerformanceCsvContent(array $data): string
    {
        $csv = "User ID,Name,Email,Role,Total Missions,Completed Missions,Completion Rate,Avg Completion Time,Last Login\n";
        
        foreach ($data['users'] as $user) {
            $csv .= sprintf(
                "%d,%s,%s,%s,%d,%d,%.2f%%,%.2f hours,%s\n",
                $user['id'],
                $this->escapeCsv($user['name']),
                $this->escapeCsv($user['email']),
                $user['role'],
                $user['total_missions'],
                $user['completed_missions'],
                $user['completion_rate'],
                $user['avg_completion_time'],
                $user['last_login'] ?? 'Never'
            );
        }

        return $csv;
    }

    /**
     * Generate CSV content for maintenance data
     */
    private function generateMaintenanceCsvContent(array $data): string
    {
        $csv = "Request ID,Mission ID,Description,Status,Priority,Reported By,Assigned To,Created At,Completed At\n";
        
        foreach ($data['maintenance_requests'] as $request) {
            $csv .= sprintf(
                "%d,%d,%s,%s,%s,%s,%s,%s,%s\n",
                $request->id,
                $request->mission_id,
                $this->escapeCsv($request->description),
                $request->status,
                $request->priority,
                $request->reportedBy ? $this->escapeCsv($request->reportedBy->name) : 'N/A',
                $request->assignedTo ? $this->escapeCsv($request->assignedTo->name) : 'N/A',
                $request->created_at->format('Y-m-d H:i:s'),
                $request->completed_at ? $request->completed_at->format('Y-m-d H:i:s') : 'N/A'
            );
        }

        return $csv;
    }

    /**
     * Convert array to CSV format
     */
    private function arrayToCsv(array $data): string
    {
        $csv = '';
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $csv .= "{$key}:\n" . $this->arrayToCsv($value);
            } else {
                $csv .= "{$key},{$value}\n";
            }
        }
        return $csv;
    }

    /**
     * Escape CSV values
     */
    private function escapeCsv(string $value): string
    {
        return '"' . str_replace('"', '""', $value) . '"';
    }

    /**
     * Calculate average completion time for missions
     */
    private function calculateAverageCompletionTime(Collection $missions): float
    {
        if ($missions->isEmpty()) {
            return 0;
        }

        $totalHours = 0;
        $count = 0;

        foreach ($missions as $mission) {
            if ($mission->created_at && $mission->updated_at) {
                $totalHours += $mission->created_at->diffInHours($mission->updated_at);
                $count++;
            }
        }

        return $count > 0 ? round($totalHours / $count, 2) : 0;
    }

    /**
     * Calculate average resolution time for maintenance requests
     */
    private function calculateAverageResolutionTime(Collection $requests): float
    {
        if ($requests->isEmpty()) {
            return 0;
        }

        $totalHours = 0;
        $count = 0;

        foreach ($requests as $request) {
            if ($request->created_at && $request->completed_at) {
                $totalHours += $request->created_at->diffInHours($request->completed_at);
                $count++;
            }
        }

        return $count > 0 ? round($totalHours / $count, 2) : 0;
    }
}