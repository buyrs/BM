<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportGenerationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(
        private ReportGenerationService $reportService
    ) {}

    /**
     * Display the reports page
     */
    public function index(): View
    {
        return view('admin.reports.index');
    }

    /**
     * Generate analytics report
     */
    public function generateAnalyticsReport(Request $request): JsonResponse
    {
        $request->validate([
            'format' => 'required|string|in:pdf,excel,csv',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'sections' => 'nullable|array',
            'sections.*' => 'string|in:mission_metrics,user_performance,property_metrics,maintenance_metrics,system_metrics',
        ]);

        try {
            $startDate = $request->start_date ? Carbon::parse($request->start_date) : null;
            $endDate = $request->end_date ? Carbon::parse($request->end_date) : null;
            $sections = $request->sections ?? [];

            $result = $this->reportService->generateAnalyticsReport(
                $request->format,
                $startDate,
                $endDate,
                $sections
            );

            return response()->json([
                'success' => true,
                'message' => 'Analytics report generated successfully',
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate analytics report: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate mission report
     */
    public function generateMissionReport(Request $request): JsonResponse
    {
        $request->validate([
            'format' => 'required|string|in:pdf,excel,csv',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|string|in:pending,in_progress,completed',
            'checker_id' => 'nullable|integer|exists:users,id',
            'property_type' => 'nullable|string',
        ]);

        try {
            $startDate = $request->start_date ? Carbon::parse($request->start_date) : null;
            $endDate = $request->end_date ? Carbon::parse($request->end_date) : null;
            
            $filters = array_filter([
                'status' => $request->status,
                'checker_id' => $request->checker_id,
                'property_type' => $request->property_type,
            ]);

            $result = $this->reportService->generateMissionReport(
                $request->format,
                $startDate,
                $endDate,
                $filters
            );

            return response()->json([
                'success' => true,
                'message' => 'Mission report generated successfully',
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate mission report: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate user performance report
     */
    public function generateUserPerformanceReport(Request $request): JsonResponse
    {
        $request->validate([
            'format' => 'required|string|in:pdf,excel,csv',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'role' => 'nullable|string|in:admin,ops,checker',
        ]);

        try {
            $startDate = $request->start_date ? Carbon::parse($request->start_date) : null;
            $endDate = $request->end_date ? Carbon::parse($request->end_date) : null;

            $result = $this->reportService->generateUserPerformanceReport(
                $request->format,
                $startDate,
                $endDate,
                $request->role
            );

            return response()->json([
                'success' => true,
                'message' => 'User performance report generated successfully',
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate user performance report: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate maintenance report
     */
    public function generateMaintenanceReport(Request $request): JsonResponse
    {
        $request->validate([
            'format' => 'required|string|in:pdf,excel,csv',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|string|in:pending,in_progress,completed,rejected',
            'priority' => 'nullable|string|in:low,medium,high,urgent',
        ]);

        try {
            $startDate = $request->start_date ? Carbon::parse($request->start_date) : null;
            $endDate = $request->end_date ? Carbon::parse($request->end_date) : null;
            
            $filters = array_filter([
                'status' => $request->status,
                'priority' => $request->priority,
            ]);

            $result = $this->reportService->generateMaintenanceReport(
                $request->format,
                $startDate,
                $endDate,
                $filters
            );

            return response()->json([
                'success' => true,
                'message' => 'Maintenance report generated successfully',
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate maintenance report: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download generated report
     */
    public function downloadReport(string $filename): Response
    {
        $path = 'reports/' . $filename;
        
        if (!Storage::disk('local')->exists($path)) {
            abort(404, 'Report file not found');
        }

        $content = Storage::disk('local')->get($path);
        $mimeType = $this->getMimeType($filename);

        return response($content)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Get list of generated reports
     */
    public function getReports(): JsonResponse
    {
        try {
            $files = Storage::disk('local')->files('reports');
            
            $reports = collect($files)->map(function ($file) {
                $filename = basename($file);
                $size = Storage::disk('local')->size($file);
                $lastModified = Storage::disk('local')->lastModified($file);
                
                return [
                    'filename' => $filename,
                    'path' => $file,
                    'size' => $size,
                    'size_formatted' => $this->formatBytes($size),
                    'created_at' => Carbon::createFromTimestamp($lastModified)->format('Y-m-d H:i:s'),
                    'download_url' => route('admin.reports.download', ['filename' => $filename]),
                ];
            })->sortByDesc('created_at')->values();

            return response()->json([
                'success' => true,
                'data' => $reports,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve reports: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a generated report
     */
    public function deleteReport(string $filename): JsonResponse
    {
        try {
            $path = 'reports/' . $filename;
            
            if (!Storage::disk('local')->exists($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Report file not found',
                ], 404);
            }

            Storage::disk('local')->delete($path);

            return response()->json([
                'success' => true,
                'message' => 'Report deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete report: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available report types and their configurations
     */
    public function getReportTypes(): JsonResponse
    {
        $reportTypes = [
            'analytics' => [
                'name' => 'Analytics Report',
                'description' => 'Comprehensive analytics including mission metrics, user performance, and system statistics',
                'sections' => [
                    'mission_metrics' => 'Mission completion statistics and trends',
                    'user_performance' => 'User activity and performance metrics',
                    'property_metrics' => 'Property-related statistics and completion rates',
                    'maintenance_metrics' => 'Maintenance request statistics and resolution times',
                    'system_metrics' => 'Overall system usage and performance',
                ],
                'formats' => ['pdf', 'excel', 'csv'],
            ],
            'missions' => [
                'name' => 'Mission Report',
                'description' => 'Detailed mission completion report with filtering options',
                'filters' => [
                    'status' => ['pending', 'in_progress', 'completed'],
                    'checker_id' => 'User ID of assigned checker',
                    'property_type' => 'Type of property',
                ],
                'formats' => ['pdf', 'excel', 'csv'],
            ],
            'user_performance' => [
                'name' => 'User Performance Report',
                'description' => 'Individual user performance metrics and statistics',
                'filters' => [
                    'role' => ['admin', 'ops', 'checker'],
                ],
                'formats' => ['pdf', 'excel', 'csv'],
            ],
            'maintenance' => [
                'name' => 'Maintenance Report',
                'description' => 'Maintenance request tracking and resolution statistics',
                'filters' => [
                    'status' => ['pending', 'in_progress', 'completed', 'rejected'],
                    'priority' => ['low', 'medium', 'high', 'urgent'],
                ],
                'formats' => ['pdf', 'excel', 'csv'],
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $reportTypes,
        ]);
    }

    /**
     * Get MIME type for file
     */
    private function getMimeType(string $filename): string
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        
        return match($extension) {
            'pdf' => 'application/pdf',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'csv' => 'text/csv',
            default => 'application/octet-stream',
        };
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}