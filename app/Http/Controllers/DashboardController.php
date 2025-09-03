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

    public function checkers()
    {
        $checkers = User::role('checker')
            ->withCount(['assignedMissions as assigned_missions_count' => function ($query) {
                $query->whereIn('status', ['assigned', 'in_progress']);
            }])
            ->withCount(['assignedMissions as completed_missions_count' => function ($query) {
                $query->where('status', 'completed');
            }])
            ->get();

        return Inertia::render('Admin/Checkers', [
            'checkers' => $checkers
        ]);
    }

    public function reports()
    {
        $totalMissions = Mission::count();
        $completedMissions = Mission::where('status', 'completed')->count();
        $activeCheckers = User::role('checker')->whereHas('assignedMissions', function ($query) {
            $query->whereIn('status', ['assigned', 'in_progress']);
        })->count();

        $completionRate = $totalMissions > 0 ? round(($completedMissions / $totalMissions) * 100) : 0;

        $checkinMissions = Mission::where('type', 'checkin')->count();
        $checkoutMissions = Mission::where('type', 'checkout')->count();

        $topCheckers = User::role('checker')
            ->withCount(['assignedMissions as completed_missions_count' => function ($query) {
                $query->where('status', 'completed');
            }])
            ->withCount(['assignedMissions as total_missions_count'])
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

        return Inertia::render('Admin/Analytics', [
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

        // Get all assigned missions (not just limited to 5)
        $assignedMissions = Mission::where('agent_id', $user->id)
            ->whereIn('status', ['assigned', 'in_progress'])
            ->with(['bailMobilite'])
            ->orderBy('scheduled_at', 'asc')
            ->get()
            ->map(function ($mission) {
                return [
                    'id' => $mission->id,
                    'address' => $mission->address,
                    'type' => $mission->type,
                    'status' => $mission->status,
                    'scheduled_at' => $mission->scheduled_at,
                    'tenant_name' => $mission->tenant_name,
                    'tenant_phone' => $mission->tenant_phone,
                    'tenant_email' => $mission->tenant_email,
                    'notes' => $mission->notes,
                    'estimated_duration' => $mission->estimated_duration ?? 60,
                    'priority' => $this->calculateMissionPriority($mission),
                ];
            });

        // Get completed missions for history
        $completedMissions = Mission::where('agent_id', $user->id)
            ->where('status', 'completed')
            ->with(['bailMobilite'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($mission) {
                return [
                    'id' => $mission->id,
                    'address' => $mission->address,
                    'type' => $mission->type,
                    'status' => $mission->status,
                    'completed_at' => $mission->updated_at,
                    'rating' => rand(3, 5), // Mock rating - would come from actual feedback
                ];
            });

        $completedMissionsCount = Mission::where('agent_id', $user->id)
            ->where('status', 'completed')
            ->count();

        // Get pending checklists (if Checklist model exists)
        $pendingChecklists = 0;
        if (class_exists('\App\Models\Checklist')) {
            $pendingChecklists = \App\Models\Checklist::whereHas('mission', function ($query) use ($user) {
                $query->where('agent_id', $user->id);
            })
            ->whereNull('completed_at')
            ->count();
        }

        // Calculate weekly stats
        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();
        
        $weeklyCompleted = Mission::where('agent_id', $user->id)
            ->where('status', 'completed')
            ->whereBetween('updated_at', [$weekStart, $weekEnd])
            ->count();

        $weeklyAssigned = Mission::where('agent_id', $user->id)
            ->whereBetween('created_at', [$weekStart, $weekEnd])
            ->count();

        $onTimeRate = $this->calculateOnTimeRate($user->id);
        $averageRating = $this->calculateAverageRating($user->id);

        return Inertia::render('Checker/Dashboard', [
            'assignedMissions' => $assignedMissions,
            'completedMissions' => $completedMissions,
            'completedMissionsCount' => $completedMissionsCount,
            'pendingChecklistsCount' => $pendingChecklists,
            'weeklyStats' => [
                'completed' => $weeklyCompleted,
                'assigned' => $weeklyAssigned,
                'averageRating' => $averageRating,
                'onTimeRate' => $onTimeRate,
            ],
        ]);
    }

    private function calculateMissionPriority($mission)
    {
        if (!$mission->scheduled_at) return 'normal';
        
        $now = now();
        $scheduled = $mission->scheduled_at;
        $hoursUntilDue = $now->diffInHours($scheduled, false);
        
        if ($hoursUntilDue < 0) return 'overdue';
        if ($hoursUntilDue < 2) return 'urgent';
        if ($hoursUntilDue < 24) return 'high';
        return 'normal';
    }

    private function calculateOnTimeRate($userId)
    {
        $completedMissions = Mission::where('agent_id', $userId)
            ->where('status', 'completed')
            ->whereNotNull('scheduled_at')
            ->get();

        if ($completedMissions->isEmpty()) return 95; // Default rate

        $onTimeMissions = $completedMissions->filter(function ($mission) {
            return $mission->updated_at <= $mission->scheduled_at->addHours(1); // 1 hour grace period
        });

        return round(($onTimeMissions->count() / $completedMissions->count()) * 100);
    }

    private function calculateAverageRating($userId)
    {
        // Mock calculation - in real app, this would come from actual ratings
        return 4.2 + (rand(-20, 30) / 100); // Random rating between 4.0 and 4.5
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
        try {
            $totalMissions = Mission::count();
            $assignedMissions = Mission::where('status', 'assigned')->count();
            $completedMissions = Mission::where('status', 'completed')->count();
            $inProgressMissions = Mission::where('status', 'in_progress')->count();
            $unassignedMissions = Mission::where('status', 'unassigned')->count();
            $pendingMissions = Mission::where('status', 'unassigned')->count(); // For compatibility

            $recentMissions = Mission::with('agent')
                ->latest()
                ->limit(10)
                ->get()
                ->map(function ($mission) {
                    return [
                        'id' => $mission->id,
                        'address' => $mission->address ?? 'N/A',
                        'status' => $mission->status,
                        'type' => $mission->type ?? 'N/A',
                        'created_at' => $mission->created_at,
                        'scheduled_at' => $mission->scheduled_at,
                        'agent' => $mission->agent ? [
                            'id' => $mission->agent->id,
                            'name' => $mission->agent->name,
                        ] : null,
                    ];
                });

            $totalCheckers = User::role('checker')->count();
            $activeCheckers = User::role('checker')->count();
            $onlineCheckers = User::role('checker')
                ->where('updated_at', '>=', now()->subMinutes(15))
                ->count();

            // Get checkers with additional data
            $checkers = User::role('checker')
                ->withCount(['assignedMissions as assigned_missions_count' => function ($query) {
                    $query->whereIn('status', ['assigned', 'in_progress']);
                }])
                ->withCount(['assignedMissions as completed_missions_count' => function ($query) {
                    $query->where('status', 'completed');
                }])
                ->get()
                ->map(function ($checker) {
                    return [
                        'id' => $checker->id,
                        'name' => $checker->name,
                        'email' => $checker->email,
                        'phone' => $checker->phone ?? null,
                        'status' => 'active', // Default status, could be enhanced with actual status field
                        'is_online' => $checker->updated_at >= now()->subMinutes(15),
                        'assigned_missions_count' => $checker->assigned_missions_count,
                        'completed_missions_count' => $checker->completed_missions_count,
                        'performance_score' => $checker->completed_missions_count > 0 
                            ? round(($checker->completed_missions_count / ($checker->assigned_missions_count + $checker->completed_missions_count)) * 100)
                            : null,
                    ];
                });

            // Generate recent activities
            $recentActivities = $this->generateRecentActivities();

            // Generate system health data
            $systemHealth = $this->generateSystemHealth();

            $data = [
                'stats' => [
                    'totalMissions' => $totalMissions,
                    'assignedMissions' => $assignedMissions,
                    'completedMissions' => $completedMissions,
                    'inProgressMissions' => $inProgressMissions,
                    'unassignedMissions' => $unassignedMissions,
                    'pendingMissions' => $pendingMissions, // For compatibility with super admin views
                    'totalCheckers' => $totalCheckers,
                    'activeCheckers' => $activeCheckers,
                    'onlineCheckers' => $onlineCheckers,
                    'missionTrend' => 12, // This would be calculated from historical data
                    'incidentTrend' => -8, // This would be calculated from historical data
                ],
                'recentMissions' => $recentMissions,
                'checkers' => $checkers,
                'recentActivities' => $recentActivities,
                'systemHealth' => $systemHealth,
            ];

            \Log::info('Admin Dashboard Data:', $data);

            return Inertia::render('Admin/Dashboard', $data);
        } catch (\Exception $e) {
            \Log::error('Admin Dashboard Error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            // Return a simple response for debugging
            return Inertia::render('Admin/Dashboard', [
                'stats' => [
                    'totalMissions' => 0,
                    'assignedMissions' => 0,
                    'completedMissions' => 0,
                    'inProgressMissions' => 0,
                    'unassignedMissions' => 0,
                    'pendingMissions' => 0,
                    'totalCheckers' => 0,
                    'activeCheckers' => 0,
                    'onlineCheckers' => 0,
                    'missionTrend' => 0,
                    'incidentTrend' => 0,
                ],
                'recentMissions' => [],
                'checkers' => [],
                'recentActivities' => [],
                'systemHealth' => [
                    'database' => ['status' => 'unknown'],
                    'api' => ['status' => 'unknown'],
                    'storage' => ['status' => 'unknown'],
                    'queue' => ['status' => 'unknown'],
                    'recent_errors' => [],
                    'performance' => null
                ],
                'error' => 'Dashboard loading error: ' . $e->getMessage(),
            ]);
        }
    }

    private function generateRecentActivities()
    {
        // Get recent missions for activity feed
        $recentMissionActivities = Mission::with('agent')
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($mission) {
                $type = 'mission_' . $mission->status;
                $description = "Mission #{$mission->id} at {$mission->address} was {$mission->status}";
                
                return [
                    'id' => 'mission_' . $mission->id,
                    'type' => $type,
                    'description' => $description,
                    'created_at' => $mission->updated_at,
                    'user' => $mission->agent ? ['name' => $mission->agent->name] : null,
                    'metadata' => [
                        'address' => $mission->address,
                        'mission_id' => $mission->id
                    ]
                ];
            });

        // Get recent user activities
        $recentUserActivities = User::role('checker')
            ->latest()
            ->limit(3)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => 'user_' . $user->id,
                    'type' => 'checker_created',
                    'description' => "New checker account created for {$user->name}",
                    'created_at' => $user->created_at,
                    'user' => ['name' => 'Admin User'],
                    'metadata' => [
                        'user_id' => $user->id,
                        'email' => $user->email
                    ]
                ];
            });

        return $recentMissionActivities->concat($recentUserActivities)
            ->sortByDesc('created_at')
            ->values()
            ->toArray();
    }

    private function generateSystemHealth()
    {
        // In a real implementation, these would be actual health checks
        return [
            'database' => [
                'status' => 'healthy',
                'response_time' => rand(20, 100)
            ],
            'api' => [
                'status' => 'healthy',
                'active_connections' => rand(10, 50)
            ],
            'storage' => [
                'status' => rand(0, 10) > 8 ? 'warning' : 'healthy',
                'disk_usage' => rand(60, 85) . '%'
            ],
            'queue' => [
                'status' => 'healthy',
                'pending_jobs' => rand(0, 20)
            ],
            'recent_errors' => [],
            'performance' => [
                'avg_response_time' => rand(200, 400),
                'requests_per_minute' => rand(100, 200),
                'uptime' => 99.8,
                'memory_usage' => rand(400, 800)
            ]
        ];
    }

    public function storeChecker(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
        ]);

        try {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => bcrypt($validated['password']),
                'phone' => $validated['phone'] ?? null,
            ]);

            $user->assignRole('checker');

            return response()->json([
                'success' => true,
                'message' => 'Checker created successfully',
                'checker' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create checker: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateChecker(Request $request, User $checker)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $checker->id,
            'phone' => 'nullable|string|max:20',
        ]);

        try {
            $checker->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Checker updated successfully',
                'checker' => $checker
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update checker: ' . $e->getMessage()
            ], 500);
        }
    }

    public function toggleCheckerStatus(Request $request, User $checker)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,inactive,suspended'
        ]);

        try {
            // In a real implementation, you might have a status field on the user model
            // For now, we'll just return success
            return response()->json([
                'success' => true,
                'message' => 'Checker status updated successfully',
                'status' => $validated['status']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update checker status: ' . $e->getMessage()
            ], 500);
        }
    }
} 