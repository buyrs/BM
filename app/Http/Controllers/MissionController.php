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
use App\Models\BailMobilite;
use App\Models\BailMobiliteSignature;
use App\Models\ContractTemplate;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\ChecklistPhoto;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\NotificationService;
use App\Services\IncidentDetectionService;

class MissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:super-admin')->except([
            'index', 'show', 'getAssignedMissions', 'getCompletedMissions',
            'submitBailMobiliteChecklist', 'signBailMobiliteContract'
        ]);
        $this->middleware('role:checker')->only([
            'index', 'show', 'getAssignedMissions', 'getCompletedMissions',
            'submitBailMobiliteChecklist', 'signBailMobiliteContract'
        ]);
        $this->middleware('role:ops')->only([
            'assignToChecker', 'validateBailMobiliteChecklist', 'getOpsAssignedMissions'
        ]);
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
        if (!Auth::user()->hasRole('super-admin') && 
            !Auth::user()->hasRole('ops') && 
            $mission->agent_id !== Auth::id()) {
            abort(403);
        }

        $mission->load([
            'agent', 
            'bailMobilite.signatures.contractTemplate',
            'checklist.items.photos'
        ]);

        $contractTemplates = [];
        if ($mission->isBailMobiliteMission()) {
            $contractTemplates = ContractTemplate::active()
                ->where('type', $mission->mission_type)
                ->whereNotNull('admin_signature')
                ->get();
        }

        return Inertia::render('Missions/Show', [
            'mission' => $mission,
            'contractTemplates' => $contractTemplates
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

    /**
     * Assign a mission to a checker (used by Ops users).
     */
    public function assignToChecker(Request $request, Mission $mission)
    {
        $validated = $request->validate([
            'agent_id' => 'required|exists:users,id',
            'scheduled_time' => 'nullable|date_format:H:i'
        ]);

        // Verify the agent has the checker role
        $checker = User::findOrFail($validated['agent_id']);
        if (!$checker->hasRole('checker')) {
            return back()->withErrors(['agent_id' => 'Selected user is not a checker.']);
        }

        // Update mission with assignment details
        $mission->update([
            'agent_id' => $validated['agent_id'],
            'status' => 'assigned',
            'ops_assigned_by' => Auth::id(),
            'scheduled_time' => $validated['scheduled_time'] ?? null
        ]);

        return redirect()->back()
            ->with('success', 'Mission assigned to checker successfully.');
    }

    /**
     * Get missions assigned by the current Ops user.
     */
    public function getOpsAssignedMissions()
    {
        $missions = Mission::with(['agent', 'bailMobilite', 'checklist'])
            ->assignedByOps(Auth::id())
            ->latest()
            ->paginate(10);

        return Inertia::render('Missions/OpsAssigned', [
            'missions' => $missions
        ]);
    }

    /**
     * Submit checklist for a Bail Mobilité mission (used by checkers).
     */
    public function submitBailMobiliteChecklist(Request $request, Mission $mission)
    {
        // Verify this is a BM mission and the checker is assigned
        if (!$mission->isBailMobiliteMission() || $mission->agent_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this mission.');
        }

        $validated = $request->validate([
            'checklist_data' => 'required|array',
            'checklist_data.general_info' => 'required|array',
            'checklist_data.rooms' => 'required|array',
            'checklist_data.utilities' => 'required|array',
            'photos' => 'nullable|array',
            'photos.*' => 'file|image|max:10240', // 10MB max per photo
            'required_photos' => 'nullable|array',
            'required_photos.*' => 'string'
        ]);

        DB::beginTransaction();
        try {
            // Create or update checklist
            $checklist = $mission->checklist ?? new Checklist();
            $checklist->mission_id = $mission->id;
            $checklist->general_info = $validated['checklist_data']['general_info'];
            $checklist->rooms = $validated['checklist_data']['rooms'];
            $checklist->utilities = $validated['checklist_data']['utilities'];
            $checklist->status = 'pending_validation';
            $checklist->save();

            // Handle photo uploads
            if (!empty($validated['photos'])) {
                $this->handleChecklistPhotos($checklist, $validated['photos']);
            }

            // Validate required photos are present
            $requiredPhotos = $validated['required_photos'] ?? [];
            if (!$this->validateRequiredPhotos($checklist, $requiredPhotos)) {
                DB::rollBack();
                return back()->withErrors(['photos' => 'Required photos are missing.']);
            }

            // Update mission status
            $mission->update(['status' => 'pending_validation']);

            DB::commit();

            return redirect()->back()
                ->with('success', 'Checklist submitted successfully. Awaiting Ops validation.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error submitting BM checklist: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to submit checklist. Please try again.']);
        }
    }

    /**
     * Sign Bail Mobilité contract (used by checkers with tenant).
     */
    public function signBailMobiliteContract(Request $request, Mission $mission)
    {
        // Verify this is a BM mission and the checker is assigned
        if (!$mission->isBailMobiliteMission() || $mission->agent_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this mission.');
        }

        $validated = $request->validate([
            'tenant_signature' => 'required|string',
            'contract_template_id' => 'required|exists:contract_templates,id'
        ]);

        // Verify the contract template is active and of the correct type
        $contractTemplate = ContractTemplate::findOrFail($validated['contract_template_id']);
        $expectedType = $mission->isEntryMission() ? 'entry' : 'exit';
        
        if (!$contractTemplate->isReadyForUse() || $contractTemplate->type !== $expectedType) {
            return back()->withErrors(['contract' => 'Invalid or inactive contract template.']);
        }

        DB::beginTransaction();
        try {
            // Create or update signature record
            $signature = BailMobiliteSignature::updateOrCreate(
                [
                    'bail_mobilite_id' => $mission->bail_mobilite_id,
                    'signature_type' => $expectedType
                ],
                [
                    'contract_template_id' => $validated['contract_template_id'],
                    'tenant_signature' => $validated['tenant_signature'],
                    'tenant_signed_at' => now()
                ]
            );

            // Generate PDF contract (this would be implemented in a service)
            $pdfPath = $this->generateContractPdf($signature);
            $signature->update(['contract_pdf_path' => $pdfPath]);

            DB::commit();

            return redirect()->back()
                ->with('success', 'Contract signed successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error signing BM contract: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to sign contract. Please try again.']);
        }
    }

    /**
     * Validate Bail Mobilité checklist (used by Ops users).
     */
    public function validateBailMobiliteChecklist(Request $request, Mission $mission)
    {
        // Verify this is a BM mission and user has ops role
        if (!$mission->isBailMobiliteMission() || !Auth::user()->hasRole('ops')) {
            abort(403, 'Unauthorized access to this mission.');
        }

        $validated = $request->validate([
            'validation_status' => ['required', Rule::in(['approved', 'rejected'])],
            'validation_comments' => 'nullable|string|max:1000'
        ]);

        DB::beginTransaction();
        try {
            $checklist = $mission->checklist;
            if (!$checklist) {
                return back()->withErrors(['error' => 'No checklist found for this mission.']);
            }

            if ($validated['validation_status'] === 'approved') {
                // Approve checklist and update BM status
                $checklist->update([
                    'status' => 'validated',
                    'ops_validation_comments' => $validated['validation_comments']
                ]);

                $mission->update(['status' => 'completed']);

                // Update Bail Mobilité status based on mission type
                $bailMobilite = $mission->bailMobilite;
                if ($mission->isEntryMission()) {
                    $bailMobilite->update(['status' => 'in_progress']);
                    // Schedule exit reminder notification (would be implemented in a service)
                    $this->scheduleExitReminder($bailMobilite);
                } elseif ($mission->isExitMission()) {
                    // Check if all requirements are met for completion
                    if ($this->isExitComplete($mission)) {
                        $bailMobilite->update(['status' => 'completed']);
                    } else {
                        $bailMobilite->update(['status' => 'incident']);
                    }
                }

                // Run incident detection after mission completion
                $this->runIncidentDetectionForBailMobilite($bailMobilite);

            } else {
                // Reject checklist
                $checklist->update([
                    'status' => 'rejected',
                    'ops_validation_comments' => $validated['validation_comments']
                ]);

                $mission->update(['status' => 'assigned']); // Send back to checker
            }

            DB::commit();

            $message = $validated['validation_status'] === 'approved' 
                ? 'Checklist approved successfully.' 
                : 'Checklist rejected and sent back to checker.';

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error validating BM checklist: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to validate checklist. Please try again.']);
        }
    }

    /**
     * Handle photo uploads for checklist items.
     */
    private function handleChecklistPhotos(Checklist $checklist, array $photos)
    {
        foreach ($photos as $itemKey => $photoFiles) {
            if (!is_array($photoFiles)) {
                $photoFiles = [$photoFiles];
            }

            // Find or create checklist item
            $checklistItem = ChecklistItem::firstOrCreate([
                'checklist_id' => $checklist->id,
                'item_name' => $itemKey
            ], [
                'category' => 'general',
                'condition' => 'documented'
            ]);

            // Upload and save photos
            foreach ($photoFiles as $photo) {
                $path = $photo->store('checklist-photos', 'public');
                
                ChecklistPhoto::create([
                    'checklist_item_id' => $checklistItem->id,
                    'photo_path' => $path
                ]);
            }
        }
    }

    /**
     * Validate that all required photos are present.
     */
    private function validateRequiredPhotos(Checklist $checklist, array $requiredPhotos): bool
    {
        foreach ($requiredPhotos as $requiredPhoto) {
            $item = $checklist->items()->where('item_name', $requiredPhoto)->first();
            if (!$item || $item->photos()->count() === 0) {
                return false;
            }
        }
        return true;
    }

    /**
     * Generate contract PDF with signatures.
     * This is a placeholder - would be implemented with a PDF generation service.
     */
    private function generateContractPdf(BailMobiliteSignature $signature): string
    {
        // This would use a service like DomPDF or similar to generate the contract
        // For now, return a placeholder path
        $filename = 'contract_' . $signature->bail_mobilite_id . '_' . $signature->signature_type . '_' . time() . '.pdf';
        $path = 'contracts/' . $filename;
        
        // Placeholder: In real implementation, generate actual PDF here
        Storage::disk('public')->put($path, 'PDF content placeholder');
        
        return $path;
    }

    /**
     * Schedule exit reminder notification.
     * This is a placeholder - would be implemented with a notification service.
     */
    private function scheduleExitReminder(BailMobilite $bailMobilite): void
    {
        // This would schedule a notification 10 days before end date
        // Implementation would depend on the notification system (queues, cron, etc.)
        Log::info('Exit reminder scheduled for Bail Mobilité ID: ' . $bailMobilite->id);
    }

    /**
     * Check if exit mission is complete (all requirements met).
     */
    private function isExitComplete(Mission $mission): bool
    {
        $bailMobilite = $mission->bailMobilite;
        
        // Check if checklist is validated
        if (!$mission->checklist || $mission->checklist->status !== 'validated') {
            return false;
        }

        // Check if contract is signed
        $exitSignature = $bailMobilite->exitSignature;
        if (!$exitSignature || !$exitSignature->isComplete()) {
            return false;
        }

        // Check if keys are returned (this would be part of checklist data)
        $checklistData = $mission->checklist->general_info ?? [];
        $keysReturned = $checklistData['keys']['returned'] ?? false;
        
        return $keysReturned;
    }

    /**
     * Show mission validation page for Ops users.
     */
    public function showValidation(Mission $mission)
    {
        // Verify this is a BM mission and user has ops role
        if (!$mission->isBailMobiliteMission() || !Auth::user()->hasRole('ops')) {
            abort(403, 'Unauthorized access to this mission.');
        }

        $mission->load(['bailMobilite', 'agent', 'checklist.items.photos']);

        return Inertia::render('Ops/MissionValidation', [
            'mission' => $mission
        ]);
    }

    /**
     * Validate mission (used by Ops users).
     */
    public function validateMission(Request $request, Mission $mission)
    {
        // Verify this is a BM mission and user has ops role
        if (!$mission->isBailMobiliteMission() || !Auth::user()->hasRole('ops')) {
            abort(403, 'Unauthorized access to this mission.');
        }

        $validated = $request->validate([
            'validation_status' => ['required', Rule::in(['approved', 'rejected'])],
            'validation_comments' => 'nullable|string|max:1000'
        ]);

        DB::beginTransaction();
        try {
            $checklist = $mission->checklist;
            if (!$checklist) {
                return back()->withErrors(['error' => 'No checklist found for this mission.']);
            }

            if ($validated['validation_status'] === 'approved') {
                // Approve checklist and update BM status
                $checklist->update([
                    'status' => 'validated',
                    'ops_validation_comments' => $validated['validation_comments'],
                    'validated_by' => Auth::id(),
                    'validated_at' => now()
                ]);

                $mission->update(['status' => 'completed']);

                // Update Bail Mobilité status based on mission type
                $bailMobilite = $mission->bailMobilite;
                if ($mission->isEntryMission()) {
                    $bailMobilite->update(['status' => 'in_progress']);
                    
                    // Schedule exit reminder notification using NotificationService
                    $notificationService = app(NotificationService::class);
                    $notificationService->scheduleExitReminder($bailMobilite);
                    
                } elseif ($mission->isExitMission()) {
                    // Check if all requirements are met for completion
                    if ($this->isExitComplete($mission)) {
                        $bailMobilite->update(['status' => 'completed']);
                    } else {
                        $bailMobilite->update(['status' => 'incident']);
                        
                        // Send incident alert
                        $notificationService = app(NotificationService::class);
                        $notificationService->sendIncidentAlert($bailMobilite, 'Exit validation failed - requirements not met');
                    }
                }

            } else {
                // Reject checklist
                $checklist->update([
                    'status' => 'rejected',
                    'ops_validation_comments' => $validated['validation_comments'],
                    'validated_by' => Auth::id(),
                    'validated_at' => now()
                ]);

                $mission->update(['status' => 'assigned']); // Send back to checker
            }

            DB::commit();

            $message = $validated['validation_status'] === 'approved' 
                ? 'Mission validated successfully.' 
                : 'Mission rejected and sent back to checker.';

            return redirect()->route('ops.dashboard')->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error validating mission: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to validate mission. Please try again.']);
        }
    }

    /**
     * Run incident detection for a bail mobilité after mission completion.
     */
    private function runIncidentDetectionForBailMobilite(BailMobilite $bailMobilite)
    {
        try {
            $incidentDetectionService = app(IncidentDetectionService::class);
            
            // Load necessary relationships
            $bailMobilite->load([
                'entryMission.checklist',
                'exitMission.checklist',
                'entrySignature',
                'exitSignature'
            ]);

            $incidents = $incidentDetectionService->detectIncidents($bailMobilite);

            if (!empty($incidents)) {
                $incidentDetectionService->processIncidents($bailMobilite, $incidents);
                Log::info("Incident detection completed for BailMobilite {$bailMobilite->id}: " . count($incidents) . " incidents found");
            }
        } catch (\Exception $e) {
            Log::error("Failed to run incident detection for BailMobilite {$bailMobilite->id}: " . $e->getMessage());
        }
    }
}