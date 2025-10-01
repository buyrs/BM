<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Response as ResponseFacade;

class AuditLogController extends Controller
{
    protected AuditLogger $auditLogger;

    public function __construct(AuditLogger $auditLogger)
    {
        $this->auditLogger = $auditLogger;
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (auth()->user()->role !== 'admin') {
                abort(403, 'Access denied. Admin role required.');
            }
            return $next($request);
        });
    }

    /**
     * Display audit logs with filtering
     */
    public function index(Request $request)
    {
        $filters = $request->only([
            'user_id',
            'action',
            'resource_type',
            'start_date',
            'end_date',
            'search'
        ]);

        // Clean up empty filters
        $filters = array_filter($filters, function ($value) {
            return $value !== null && $value !== '';
        });

        $perPage = $request->get('per_page', 50);
        $auditLogs = $this->auditLogger->getAuditLogs($filters, $perPage);

        // Get filter options for dropdowns
        $users = \App\Models\User::select('id', 'name')->orderBy('name')->get();
        $actions = \App\Models\AuditLog::select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');
        $resourceTypes = \App\Models\AuditLog::select('resource_type')
            ->distinct()
            ->whereNotNull('resource_type')
            ->orderBy('resource_type')
            ->get()
            ->map(function ($item) {
                return [
                    'value' => $item->resource_type,
                    'label' => class_basename($item->resource_type)
                ];
            });

        return view('admin.audit-logs.index', compact(
            'auditLogs',
            'filters',
            'users',
            'actions',
            'resourceTypes'
        ));
    }

    /**
     * Show detailed audit log entry
     */
    public function show($id)
    {
        $auditLog = \App\Models\AuditLog::with('user')->findOrFail($id);

        return view('admin.audit-logs.show', compact('auditLog'));
    }

    /**
     * Export audit logs
     */
    public function export(Request $request)
    {
        $request->validate([
            'format' => 'required|in:csv,json',
            'user_id' => 'nullable|exists:users,id',
            'action' => 'nullable|string',
            'resource_type' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $filters = $request->only([
            'user_id',
            'action',
            'resource_type',
            'start_date',
            'end_date'
        ]);

        // Clean up empty filters
        $filters = array_filter($filters, function ($value) {
            return $value !== null && $value !== '';
        });

        // Get all matching logs (no pagination for export)
        $auditLogs = $this->auditLogger->getAuditLogs($filters, 10000);

        $format = $request->get('format', 'csv');

        if ($format === 'csv') {
            return $this->exportCsv($auditLogs);
        } else {
            return $this->exportJson($auditLogs);
        }
    }

    /**
     * Get suspicious activity
     */
    public function suspicious(Request $request)
    {
        $days = $request->get('days', 7);
        $suspiciousLogs = $this->auditLogger->getSuspiciousActivity($days);

        return view('admin.audit-logs.suspicious', compact('suspiciousLogs', 'days'));
    }

    /**
     * Get audit statistics
     */
    public function statistics(Request $request)
    {
        $days = $request->get('days', 30);
        $startDate = now()->subDays($days);

        // Total logs
        $totalLogs = \App\Models\AuditLog::where('created_at', '>=', $startDate)->count();

        // Logs by action
        $actionStats = \App\Models\AuditLog::where('created_at', '>=', $startDate)
            ->selectRaw('action, COUNT(*) as count')
            ->groupBy('action')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        // Logs by user
        $userStats = \App\Models\AuditLog::with('user')
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('user_id')
            ->selectRaw('user_id, COUNT(*) as count')
            ->groupBy('user_id')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        // Logs by resource type
        $resourceStats = \App\Models\AuditLog::where('created_at', '>=', $startDate)
            ->whereNotNull('resource_type')
            ->selectRaw('resource_type, COUNT(*) as count')
            ->groupBy('resource_type')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        // Daily activity
        $dailyStats = \App\Models\AuditLog::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view('admin.audit-logs.statistics', compact(
            'totalLogs',
            'actionStats',
            'userStats',
            'resourceStats',
            'dailyStats',
            'days'
        ));
    }

    /**
     * Clean up old audit logs
     */
    public function cleanup(Request $request)
    {
        $request->validate([
            'retention_days' => 'required|integer|min:30|max:3650',
            'confirm' => 'required|accepted'
        ]);

        $retentionDays = $request->get('retention_days');
        $deletedCount = $this->auditLogger->cleanupOldLogs($retentionDays);

        return redirect()->route('admin.audit-logs.index')
            ->with('success', "Successfully deleted {$deletedCount} old audit log records.");
    }

    /**
     * Export audit logs as CSV
     */
    protected function exportCsv($auditLogs)
    {
        $filename = 'audit_logs_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($auditLogs) {
            $file = fopen('php://output', 'w');

            // CSV headers
            fputcsv($file, [
                'ID',
                'User',
                'Action',
                'Resource Type',
                'Resource ID',
                'IP Address',
                'User Agent',
                'Changes',
                'Created At'
            ]);

            // CSV data
            foreach ($auditLogs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->user->name ?? 'N/A',
                    $log->action,
                    $log->resource_type ? class_basename($log->resource_type) : 'N/A',
                    $log->resource_id ?? 'N/A',
                    $log->ip_address ?? 'N/A',
                    $log->user_agent ?? 'N/A',
                    $log->changes ? json_encode($log->changes) : 'N/A',
                    $log->created_at->format('Y-m-d H:i:s')
                ]);
            }

            fclose($file);
        };

        return ResponseFacade::stream($callback, 200, $headers);
    }

    /**
     * Export audit logs as JSON
     */
    protected function exportJson($auditLogs)
    {
        $filename = 'audit_logs_' . now()->format('Y-m-d_H-i-s') . '.json';

        $data = $auditLogs->map(function ($log) {
            return [
                'id' => $log->id,
                'user' => $log->user->name ?? null,
                'user_id' => $log->user_id,
                'action' => $log->action,
                'resource_type' => $log->resource_type,
                'resource_id' => $log->resource_id,
                'changes' => $log->changes,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'created_at' => $log->created_at->toISOString()
            ];
        });

        return ResponseFacade::json($data, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}