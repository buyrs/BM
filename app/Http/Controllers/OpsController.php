<?php

namespace App\Http\Controllers;

use App\Models\BailMobilite;
use App\Models\Mission;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class OpsController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:ops|admin');
    }

    /**
     * Display the ops dashboard with metrics and notifications.
     */
    public function dashboard()
    {
        $opsUserId = Auth::id();

        // Get bail mobilités statistics
        $bailMobiliteStats = [
            'assigned' => BailMobilite::assigned()->count(),
            'in_progress' => BailMobilite::inProgress()->count(),
            'completed' => BailMobilite::completed()->count(),
            'incident' => BailMobilite::incident()->count(),
            'total' => BailMobilite::count(),
        ];

        // Get recent bail mobilités
        $recentBailMobilites = BailMobilite::with(['opsUser', 'entryMission.agent', 'exitMission.agent'])
            ->latest()
            ->limit(5)
            ->get();

        // Get pending notifications
        $pendingNotifications = Notification::forRecipient($opsUserId)
            ->pending()
            ->with('bailMobilite')
            ->latest()
            ->limit(10)
            ->get();

        // Get missions requiring validation
        $missionsForValidation = Mission::whereHas('bailMobilite')
            ->where('ops_assigned_by', $opsUserId)
            ->where('status', 'completed')
            ->whereDoesntHave('checklist', function ($query) {
                $query->where('status', 'validated');
            })
            ->with(['bailMobilite', 'agent', 'checklist'])
            ->get();

        // Get bail mobilités ending soon (within 10 days)
        $endingSoon = BailMobilite::inProgress()
            ->endingWithinDays(10)
            ->with(['opsUser', 'exitMission.agent'])
            ->get();

        return Inertia::render('Ops/Dashboard', [
            'stats' => $bailMobiliteStats,
            'recentBailMobilites' => $recentBailMobilites,
            'pendingNotifications' => $pendingNotifications,
            'missionsForValidation' => $missionsForValidation,
            'endingSoon' => $endingSoon,
        ]);
    }
}