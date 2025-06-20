<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Mission;
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
                DB::raw('AVG(TIMESTAMPDIFF(MINUTE, created_at, updated_at)) as avg_minutes_to_complete'),
                DB::raw('AVG(TIMESTAMPDIFF(MINUTE, created_at, assigned_at)) as avg_minutes_to_assign')
            )
            ->when($type, fn($q) => $q->where('type', $type))
            ->when($checkerId, fn($q) => $q->where('agent_id', $checkerId))
            ->whereBetween('created_at', [$start, $end])
            ->first();

        return response()->json([
            'missionsCreated' => $missionsCreated,
            'missionsCompleted' => $missionsCompleted,
            'statusDistribution' => $statusDistribution,
            'checkerPerformance' => $checkerPerformance,
            'assignmentEfficiency' => $assignmentEfficiency,
        ]);
    }

    public function checkers()
    {
        $checkers = \App\Models\User::role('checker')->get(['id', 'name', 'email']);
        return response()->json(['checkers' => $checkers]);
    }
} 