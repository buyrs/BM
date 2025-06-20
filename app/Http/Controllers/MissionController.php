<?php

namespace App\Http\Controllers;

use App\Models\Mission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use App\Models\Agent;
use Illuminate\Support\Carbon;

class MissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:super-admin')->except(['index', 'show', 'getAssignedMissions', 'getCompletedMissions']);
        $this->middleware('role:checker')->only(['index', 'show', 'getAssignedMissions', 'getCompletedMissions']);
    }

    public function index()
    {
        if (Auth::user()->hasRole('super-admin')) {
            $missions = Mission::with('agent')
                ->latest()
                ->paginate(10);
        } else {
            $missions = Mission::where('agent_id', Auth::id())
                ->where('status', '!=', 'completed')
                ->latest()
                ->paginate(10);
        }

        return Inertia::render('Missions/Index', [
            'missions' => $missions
        ]);
    }

    public function create()
    {
        $checkers = User::role('checker')->get();
        
        return Inertia::render('Missions/Create', [
            'checkers' => $checkers
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['checkin', 'checkout'])],
            'scheduled_at' => 'required|date',
            'address' => 'required|string|max:255',
            'tenant_name' => 'required|string|max:255',
            'tenant_phone' => 'nullable|string|max:20',
            'tenant_email' => 'nullable|email|max:255',
            'notes' => 'nullable|string',
            'agent_id' => 'nullable|exists:users,id',
            'auto_assign' => 'nullable|boolean',
        ]);

        if (!empty($validated['agent_id'])) {
            $validated['status'] = 'assigned';
        }

        // Auto-assign logic
        if (!empty($validated['auto_assign'])) {
            $checker = $this->getAutoAssignedChecker();
            if ($checker) {
                $validated['agent_id'] = $checker->user_id;
                $validated['status'] = 'assigned';
            }
        }

        $mission = Mission::create($validated);

        return redirect()->route('missions.index')
            ->with('success', 'Mission created successfully.');
    }

    /**
     * Get the next checker for auto-assignment, prioritizing non-downgraded and least loaded.
     */
    protected function getAutoAssignedChecker()
    {
        // Get all active agents (checkers)
        $agents = Agent::where('status', 'active')->get();
        // Filter out downgraded agents, but include them if all are downgraded
        $nonDowngraded = $agents->where('is_downgraded', false);
        $eligible = $nonDowngraded->count() ? $nonDowngraded : $agents;
        // Sort by number of assigned missions (least first)
        $eligible = $eligible->sortBy(function($agent) {
            return Mission::where('agent_id', $agent->user_id)
                ->whereIn('status', ['assigned', 'in_progress'])
                ->count();
        });
        return $eligible->first();
    }

    /**
     * Checker refuses a mission. Increments refusal count, downgrades if needed, and reassigns mission.
     */
    public function refuseMission(Request $request, Mission $mission)
    {
        $user = Auth::user();
        $agent = Agent::where('user_id', $user->id)->firstOrFail();
        $now = Carbon::now();
        // Reset refusals if new month
        if (!$agent->refusals_month || $now->format('Y-m') !== Carbon::parse($agent->refusals_month)->format('Y-m')) {
            $agent->refusals_count = 0;
            $agent->refusals_month = $now->startOfMonth();
        }
        $agent->refusals_count += 1;
        // Downgrade if more than 1 refusal this month
        if ($agent->refusals_count > 1) {
            $agent->is_downgraded = true;
        }
        $agent->save();
        // Unassign mission and auto-assign to next eligible checker
        $mission->agent_id = null;
        $mission->status = 'unassigned';
        $mission->save();
        $nextChecker = $this->getAutoAssignedChecker();
        if ($nextChecker) {
            $mission->agent_id = $nextChecker->user_id;
            $mission->status = 'assigned';
            $mission->save();
        }
        return redirect()->back()->with('success', 'Mission refused and reassigned.');
    }

    public function show(Mission $mission)
    {
        if (!Auth::user()->hasRole('super-admin') && $mission->agent_id !== Auth::id()) {
            abort(403);
        }

        return Inertia::render('Missions/Show', [
            'mission' => $mission->load('agent')
        ]);
    }

    public function edit(Mission $mission)
    {
        $checkers = User::role('checker')->get();
        
        return Inertia::render('Missions/Edit', [
            'mission' => $mission,
            'checkers' => $checkers
        ]);
    }

    public function update(Request $request, Mission $mission)
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['checkin', 'checkout'])],
            'scheduled_at' => 'required|date',
            'address' => 'required|string|max:255',
            'tenant_name' => 'required|string|max:255',
            'tenant_phone' => 'nullable|string|max:20',
            'tenant_email' => 'nullable|email|max:255',
            'notes' => 'nullable|string',
            'agent_id' => 'nullable|exists:users,id',
            'status' => ['required', Rule::in(['unassigned', 'assigned', 'in_progress', 'completed', 'cancelled'])]
        ]);

        $mission->update($validated);

        return redirect()->route('missions.index')
            ->with('success', 'Mission updated successfully.');
    }

    public function destroy(Mission $mission)
    {
        $mission->delete();

        return redirect()->route('missions.index')
            ->with('success', 'Mission deleted successfully.');
    }

    public function assignAgent(Request $request, Mission $mission)
    {
        $validated = $request->validate([
            'agent_id' => 'required|exists:users,id'
        ]);

        $mission->update([
            'agent_id' => $validated['agent_id'],
            'status' => 'assigned'
        ]);

        return redirect()->back()
            ->with('success', 'Agent assigned successfully.');
    }

    public function getAssignedMissions()
    {
        $missions = Mission::where('agent_id', Auth::id())
            ->whereIn('status', ['assigned', 'in_progress'])
            ->latest()
            ->paginate(10);

        return Inertia::render('Missions/Assigned', [
            'missions' => $missions
        ]);
    }

    public function getCompletedMissions()
    {
        $missions = Mission::where('agent_id', Auth::id())
            ->where('status', 'completed')
            ->latest()
            ->paginate(10);

        return Inertia::render('Missions/Completed', [
            'missions' => $missions
        ]);
    }

    public function updateStatus(Request $request, Mission $mission)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['in_progress', 'completed'])]
        ]);

        if (!Auth::user()->hasRole('super-admin') && $mission->agent_id !== Auth::id()) {
            abort(403);
        }

        $mission->update([
            'status' => $validated['status']
        ]);

        return redirect()->back()
            ->with('success', 'Mission status updated successfully.');
    }
}