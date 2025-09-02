<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Mission;
use App\Models\IncidentReport;
use App\Models\Agent;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $start = $request->input('start', Carbon::now()->subDays(30)->startOfDay());
        $end = $request->input('end', Carbon::now()->endOfDay());
        $type = $request->input('type');
        $checkerId = $request->input('checker_id');

        // Mission Trends (created/completed per day)
        $missionsCreated = Mission::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->when($type, fn($q) => $q->where('type', $type))
            ->when($checkerId, fn($q) => $q->where('agent_id', $checkerId))
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        $missionsCompleted = Mission::select(DB::raw('DATE(updated_at) as date'), DB::raw('count(*) as count'))
            ->when($type, fn($q) => $q->where('type', $type))
            ->when($checkerId, fn($q) => $q->where('agent_id', $checkerId))
            ->where('status', 'completed')
            ->whereBetween('updated_at', [$start, $end])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Status Distribution
        $statusDistribution = Mission::select('status', DB::raw('count(*) as count'))
            ->when($type, fn($q) => $q->where('type', $type))
            ->when($checkerId, fn($q) => $q->where('agent_id', $checkerId))
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('status')
            ->get();

        // Checker Performance
        $checkerPerformance = Agent::with('user')
            ->get()
            ->map(function($agent) use ($start, $end, $type) {
                $completed = Mission::where('agent_id', $agent->user_id)
                    ->when($type, fn($q) => $q->where('type', $type))
                    ->where('status', 'completed')
                    ->whereBetween('updated_at', [$start, $end])
                    ->count();
                return [
                    'id' => $agent->id,
                    'name' => $agent->user->name ?? '',
                    'email' => $agent->user->email ?? '',
                    'completed' => $completed,
                    'refusals' => $agent->refusals_count,
                    'downgraded' => $agent->is_downgraded,
                ];
            });

        // Assignment Efficiency
        $assignmentEfficiency = Mission::select(
                DB::raw("AVG(CAST(strftime('%s', updated_at) AS REAL) - CAST(strftime('%s', created_at) AS REAL)) / 60 as avg_minutes_to_complete")
            )
            ->when($type, fn($q) => $q->where('type', $type))
            ->when($checkerId, fn($q) => $q->where('agent_id', $checkerId))
            ->whereBetween('created_at', [$start, $end])
            ->first();

        // Average time to resolve incidents
        $avgResolutionTimeInSeconds = IncidentReport::whereIn('status', ['resolved', 'closed'])
            ->whereNotNull('resolved_at')
            ->whereNotNull('detected_at')
            ->select(DB::raw("AVG(CAST(strftime('%s', resolved_at) AS REAL) - CAST(strftime('%s', detected_at) AS REAL)) as avg_seconds"))
            ->value('avg_seconds');

        $avgResolutionTime = $avgResolutionTimeInSeconds ? round($avgResolutionTimeInSeconds / 3600, 2) : 0;


        return response()->json([
            'missionsCreated' => $missionsCreated,
            'missionsCompleted' => $missionsCompleted,
            'statusDistribution' => $statusDistribution,
            'checkerPerformance' => $checkerPerformance,
            'assignmentEfficiency' => $assignmentEfficiency,
            'avgResolutionTime' => $avgResolutionTime,
        ]);
    }

    public function checkers()
    {
        $checkers = \App\Models\User::role('checker')->get(['id', 'name', 'email']);
        return response()->json(['checkers' => $checkers]);
    }
} 