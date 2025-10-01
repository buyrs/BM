<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class AnalyticsDashboardController extends Controller
{
    public function __construct(
        private AnalyticsService $analyticsService
    ) {}

    /**
     * Display the analytics dashboard
     */
    public function index(): View
    {
        return view('admin.analytics.dashboard');
    }

    /**
     * Get dashboard data API endpoint
     */
    public function getDashboardData(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $request->start_date ? Carbon::parse($request->start_date) : null;
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : null;

        $data = $this->analyticsService->getDashboardData($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get mission metrics
     */
    public function getMissionMetrics(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $request->start_date ? Carbon::parse($request->start_date) : null;
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : null;

        $metrics = $this->analyticsService->getMissionMetrics($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }

    /**
     * Get user performance metrics
     */
    public function getUserPerformance(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $request->start_date ? Carbon::parse($request->start_date) : null;
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : null;

        $performance = $this->analyticsService->getUserPerformanceMetrics($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $performance,
        ]);
    }

    /**
     * Get property metrics
     */
    public function getPropertyMetrics(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $request->start_date ? Carbon::parse($request->start_date) : null;
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : null;

        $metrics = $this->analyticsService->getPropertyMetrics($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }

    /**
     * Get maintenance metrics
     */
    public function getMaintenanceMetrics(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $request->start_date ? Carbon::parse($request->start_date) : null;
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : null;

        $metrics = $this->analyticsService->getMaintenanceMetrics($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }

    /**
     * Get system metrics
     */
    public function getSystemMetrics(): JsonResponse
    {
        $metrics = $this->analyticsService->getSystemMetrics();

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }

    /**
     * Get trending data for charts
     */
    public function getTrendingData(Request $request): JsonResponse
    {
        $request->validate([
            'metric' => 'required|string|in:missions_created,missions_completed,checklists_completed',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'interval' => 'nullable|string|in:daily,monthly',
        ]);

        $metric = $request->metric;
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $interval = $request->interval ?? 'daily';

        $data = $this->analyticsService->getTrendingData($metric, $startDate, $endDate, $interval);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Clear analytics cache
     */
    public function clearCache(): JsonResponse
    {
        $this->analyticsService->clearCache();

        return response()->json([
            'success' => true,
            'message' => 'Analytics cache cleared successfully',
        ]);
    }

    /**
     * Get available date ranges for filtering
     */
    public function getDateRanges(): JsonResponse
    {
        $now = Carbon::now();
        
        $ranges = [
            'today' => [
                'label' => 'Today',
                'start_date' => $now->copy()->startOfDay()->toDateString(),
                'end_date' => $now->copy()->endOfDay()->toDateString(),
            ],
            'yesterday' => [
                'label' => 'Yesterday',
                'start_date' => $now->copy()->subDay()->startOfDay()->toDateString(),
                'end_date' => $now->copy()->subDay()->endOfDay()->toDateString(),
            ],
            'last_7_days' => [
                'label' => 'Last 7 Days',
                'start_date' => $now->copy()->subDays(7)->startOfDay()->toDateString(),
                'end_date' => $now->copy()->endOfDay()->toDateString(),
            ],
            'last_30_days' => [
                'label' => 'Last 30 Days',
                'start_date' => $now->copy()->subDays(30)->startOfDay()->toDateString(),
                'end_date' => $now->copy()->endOfDay()->toDateString(),
            ],
            'this_month' => [
                'label' => 'This Month',
                'start_date' => $now->copy()->startOfMonth()->toDateString(),
                'end_date' => $now->copy()->endOfMonth()->toDateString(),
            ],
            'last_month' => [
                'label' => 'Last Month',
                'start_date' => $now->copy()->subMonth()->startOfMonth()->toDateString(),
                'end_date' => $now->copy()->subMonth()->endOfMonth()->toDateString(),
            ],
            'last_3_months' => [
                'label' => 'Last 3 Months',
                'start_date' => $now->copy()->subMonths(3)->startOfDay()->toDateString(),
                'end_date' => $now->copy()->endOfDay()->toDateString(),
            ],
            'last_12_months' => [
                'label' => 'Last 12 Months',
                'start_date' => $now->copy()->subMonths(12)->startOfDay()->toDateString(),
                'end_date' => $now->copy()->endOfDay()->toDateString(),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $ranges,
        ]);
    }
}