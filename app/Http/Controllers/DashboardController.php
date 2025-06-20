<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Mission;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        // This is the default dashboard accessible to all authenticated users.
        // It will redirect to role-specific dashboards based on middleware.
        return Inertia::render('Dashboard');
    }

    public function superAdminDashboard()
    {
        $totalMissions = Mission::count();
        $pendingMissions = Mission::where('status', 'unassigned')->count();
        $assignedMissions = Mission::where('status', 'assigned')->count();
        $completedMissions = Mission::where('status', 'completed')->count();

        $recentMissions = Mission::with('agent')
            ->latest()
            ->limit(5)
            ->get();

        $totalCheckers = User::role('checker')->count();

        return Inertia::render('SuperAdmin/Dashboard', [
            'stats' => [
                'totalMissions' => $totalMissions,
                'pendingMissions' => $pendingMissions,
                'assignedMissions' => $assignedMissions,
                'completedMissions' => $completedMissions,
                'totalCheckers' => $totalCheckers,
            ],
            'recentMissions' => $recentMissions,
        ]);
    }

    public function missions()
    {
        $missions = Mission::with('agent')
            ->latest()
            ->get();

        return Inertia::render('SuperAdmin/Missions', [
            'missions' => $missions
        ]);
    }

    public function checkers()
    {
        $checkers = User::role('checker')
            ->withCount(['missions as assigned_missions_count' => function ($query) {
                $query->whereIn('status', ['assigned', 'in_progress']);
            }])
            ->withCount(['missions as completed_missions_count' => function ($query) {
                $query->where('status', 'completed');
            }])
            ->get();

        return Inertia::render('SuperAdmin/Checkers', [
            'checkers' => $checkers
        ]);
    }

    public function reports()
    {
        $totalMissions = Mission::count();
        $completedMissions = Mission::where('status', 'completed')->count();
        $activeCheckers = User::role('checker')->whereHas('missions', function ($query) {
            $query->whereIn('status', ['assigned', 'in_progress']);
        })->count();

        $completionRate = $totalMissions > 0 ? round(($completedMissions / $totalMissions) * 100) : 0;

        $checkinMissions = Mission::where('type', 'checkin')->count();
        $checkoutMissions = Mission::where('type', 'checkout')->count();

        $topCheckers = User::role('checker')
            ->withCount(['missions as completed_missions_count' => function ($query) {
                $query->where('status', 'completed');
            }])
            ->withCount(['missions as total_missions_count'])
            ->get()
            ->map(function ($checker) {
                $checker->completion_rate = $checker->total_missions_count > 0
                    ? round(($checker->completed_missions_count / $checker->total_missions_count) * 100)
                    : 0;
                return $checker;
            })
            ->sortByDesc('completed_missions_count')
            ->take(5);

        $recentActivity = Mission::with('agent')
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($mission) {
                return [
                    'id' => $mission->id,
                    'type' => 'mission_' . $mission->status,
                    'description' => "Mission at {$mission->address} was {$mission->status}",
                    'created_at' => $mission->created_at
                ];
            });

        return Inertia::render('SuperAdmin/Reports', [
            'stats' => [
                'totalMissions' => $totalMissions,
                'completedMissions' => $completedMissions,
                'activeCheckers' => $activeCheckers,
                'completionRate' => $completionRate,
                'checkinMissions' => $checkinMissions,
                'checkoutMissions' => $checkoutMissions
            ],
            'topCheckers' => $topCheckers,
            'recentActivity' => $recentActivity
        ]);
    }

    public function checkerDashboard()
    {
        $user = Auth::user();

        $assignedMissions = Mission::where('agent_id', $user->id)
            ->whereIn('status', ['assigned', 'in_progress'])
            ->latest()
            ->limit(5)
            ->get();

        $completedMissions = Mission::where('agent_id', $user->id)
            ->where('status', 'completed')
            ->count();

        $pendingChecklists = 0; // Placeholder for now, if you have a Checklist model

        return Inertia::render('Checker/Dashboard', [
            'assignedMissions' => $assignedMissions,
            'completedMissionsCount' => $completedMissions,
            'pendingChecklistsCount' => $pendingChecklists,
        ]);
    }

    public function checkerMissions()
    {
        $user = Auth::user();

        $missions = Mission::where('agent_id', $user->id)
            ->whereIn('status', ['assigned', 'in_progress', 'completed'])
            ->latest()
            ->paginate(10);

        return Inertia::render('Checker/Missions', [
            'missions' => $missions
        ]);
    }

    public function adminDashboard()
    {
        $totalMissions = Mission::count();
        $assignedMissions = Mission::where('status', 'assigned')->count();
        $completedMissions = Mission::where('status', 'completed')->count();

        $recentMissions = Mission::with('agent')
            ->latest()
            ->limit(5)
            ->get();

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'totalMissions' => $totalMissions,
                'assignedMissions' => $assignedMissions,
                'completedMissions' => $completedMissions,
            ],
            'recentMissions' => $recentMissions,
        ]);
    }
} 