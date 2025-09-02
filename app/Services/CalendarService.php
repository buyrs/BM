<?php

namespace App\Services;

use App\Models\BailMobilite;
use App\Models\Mission;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CalendarService
{
    /**
     * Get missions for a specific date range with optional filters.
     */
    public function getMissionsForDateRange(Carbon $startDate, Carbon $endDate, array $filters = []): Collection
    {
        try {
            $query = Mission::with([
                'agent:id,name,email',
                'bailMobilite:id,tenant_name,address,status,start_date,end_date,duration_days',
                'checklist:id,mission_id,status',
                'opsAssignedBy:id,name'
            ])
            ->whereNotNull('bail_mobilite_id') // Only BM missions for calendar
            ->whereBetween('scheduled_at', [$startDate, $endDate]);

        // Apply status filter
        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('status', $filters['status']);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        // Apply checker filter
        if (!empty($filters['checker_id'])) {
            $query->where('agent_id', $filters['checker_id']);
        }

        // Apply mission type filter
        if (!empty($filters['mission_type'])) {
            if (is_array($filters['mission_type'])) {
                $query->whereIn('mission_type', $filters['mission_type']);
            } else {
                $query->where('mission_type', $filters['mission_type']);
            }
        }

        // Apply date range filter (this modifies the date range constraints)
        if (!empty($filters['date_range'])) {
            $now = Carbon::now();
            $today = $now->copy()->startOfDay();
            
            switch ($filters['date_range']) {
                case 'today':
                    $query->whereDate('scheduled_at', $today);
                    break;
                case 'tomorrow':
                    $tomorrow = $today->copy()->addDay();
                    $query->whereDate('scheduled_at', $tomorrow);
                    break;
                case 'this_week':
                    $startOfWeek = $today->copy()->startOfWeek();
                    $endOfWeek = $today->copy()->endOfWeek();
                    $query->whereBetween('scheduled_at', [$startOfWeek, $endOfWeek]);
                    break;
                case 'next_week':
                    $nextWeekStart = $today->copy()->addWeek()->startOfWeek();
                    $nextWeekEnd = $today->copy()->addWeek()->endOfWeek();
                    $query->whereBetween('scheduled_at', [$nextWeekStart, $nextWeekEnd]);
                    break;
                case 'this_month':
                    $startOfMonth = $today->copy()->startOfMonth();
                    $endOfMonth = $today->copy()->endOfMonth();
                    $query->whereBetween('scheduled_at', [$startOfMonth, $endOfMonth]);
                    break;
                case 'overdue':
                    $query->where('scheduled_at', '<', $today)
                          ->whereNotIn('status', ['completed', 'cancelled']);
                    break;
            }
        }

        // Apply search filter
        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('tenant_name', 'like', "%{$searchTerm}%")
                  ->orWhere('address', 'like', "%{$searchTerm}%")
                  ->orWhere('tenant_email', 'like', "%{$searchTerm}%")
                  ->orWhere('id', 'like', "%{$searchTerm}%")
                  ->orWhereHas('agent', function ($subQ) use ($searchTerm) {
                      $subQ->where('name', 'like', "%{$searchTerm}%");
                  })
                  ->orWhereHas('bailMobilite', function ($subQ) use ($searchTerm) {
                      $subQ->where('tenant_name', 'like', "%{$searchTerm}%")
                           ->orWhere('address', 'like', "%{$searchTerm}%");
                  });
            });
        }

            return $query->orderBy('scheduled_at')
                        ->orderBy('scheduled_time')
                        ->get();

        } catch (\Exception $e) {
            \Log::error('CalendarService getMissionsForDateRange error: ' . $e->getMessage(), [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'filters' => $filters,
                'trace' => $e->getTraceAsString()
            ]);

            // Return empty collection on error to prevent breaking the calendar
            return collect();
        }
    }

    /**
     * Format missions for calendar display.
     */
    public function formatMissionsForCalendar(Collection|SupportCollection $missions): array
    {
        try {
            return $missions->map(function (Mission $mission) {
            // Only detect conflicts if we have the required data
            $conflicts = [];
            if ($mission->scheduled_at && $mission->scheduled_time && $mission->agent_id) {
                $timeString = is_string($mission->scheduled_time) 
                    ? substr($mission->scheduled_time, 0, 5) // Extract HH:MM from HH:MM:SS
                    : $mission->scheduled_time->format('H:i');
                    
                $conflicts = $this->detectSchedulingConflicts(
                    $mission->scheduled_at,
                    $timeString,
                    $mission->agent_id,
                    $mission->id
                );
            }

            return [
                'id' => $mission->id,
                'type' => $mission->mission_type, // 'entry' or 'exit'
                'scheduled_at' => $mission->scheduled_at ? $mission->scheduled_at->format('Y-m-d') : null,
                'scheduled_time' => is_string($mission->scheduled_time) 
                    ? substr($mission->scheduled_time, 0, 5) // Extract HH:MM from HH:MM:SS
                    : ($mission->scheduled_time ? $mission->scheduled_time->format('H:i') : null),
                'status' => $mission->status,
                'tenant_name' => $mission->tenant_name,
                'address' => $mission->address,
                'agent' => $mission->agent ? [
                    'id' => $mission->agent->id,
                    'name' => $mission->agent->name,
                    'email' => $mission->agent->email,
                ] : null,
                'bail_mobilite' => $mission->bailMobilite ? [
                    'id' => $mission->bailMobilite->id,
                    'status' => $mission->bailMobilite->status,
                    'start_date' => $mission->bailMobilite->start_date->format('Y-m-d'),
                    'end_date' => $mission->bailMobilite->end_date->format('Y-m-d'),
                    'duration_days' => $mission->bailMobilite->getDurationInDays(),
                ] : null,
                'checklist_status' => $mission->checklist?->status ?? 'pending',
                'conflicts' => $conflicts,
                'can_edit' => $this->canEditMission($mission),
                'can_assign' => $this->canAssignMission($mission),
                'ops_assigned_by' => $mission->opsAssignedBy ? [
                    'id' => $mission->opsAssignedBy->id,
                    'name' => $mission->opsAssignedBy->name,
                ] : null,
            ];
            })->toArray();

        } catch (\Exception $e) {
            \Log::error('CalendarService formatMissionsForCalendar error: ' . $e->getMessage(), [
                'missions_count' => $missions->count(),
                'trace' => $e->getTraceAsString()
            ]);

            // Return empty array on error to prevent breaking the calendar
            return [];
        }
    }

    /**
     * Create a new Bail Mobilité mission with entry and exit missions.
     */
    public function createBailMobiliteMission(array $data): BailMobilite
    {
        // Create the bail mobilité
        $bailMobilite = BailMobilite::create([
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'address' => $data['address'],
            'tenant_name' => $data['tenant_name'],
            'tenant_phone' => $data['tenant_phone'] ?? null,
            'tenant_email' => $data['tenant_email'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => 'assigned',
            'ops_user_id' => Auth::id(),
        ]);

        // Create entry mission
        $entryMission = Mission::create([
            'type' => 'checkin',
            'mission_type' => 'entry',
            'scheduled_at' => $data['start_date'],
            'scheduled_time' => isset($data['entry_scheduled_time']) ? $data['entry_scheduled_time'] . ':00' : null,
            'address' => $data['address'],
            'tenant_name' => $data['tenant_name'],
            'tenant_phone' => $data['tenant_phone'] ?? null,
            'tenant_email' => $data['tenant_email'] ?? null,
            'notes' => ($data['notes'] ?? '') . ' - Mission d\'entrée pour Bail Mobilité',
            'status' => isset($data['entry_checker_id']) ? 'assigned' : 'unassigned',
            'agent_id' => $data['entry_checker_id'] ?? null,
            'bail_mobilite_id' => $bailMobilite->id,
            'ops_assigned_by' => Auth::id(),
        ]);

        // Create exit mission
        $exitMission = Mission::create([
            'type' => 'checkout',
            'mission_type' => 'exit',
            'scheduled_at' => $data['end_date'],
            'scheduled_time' => isset($data['exit_scheduled_time']) ? $data['exit_scheduled_time'] . ':00' : null,
            'address' => $data['address'],
            'tenant_name' => $data['tenant_name'],
            'tenant_phone' => $data['tenant_phone'] ?? null,
            'tenant_email' => $data['tenant_email'] ?? null,
            'notes' => ($data['notes'] ?? '') . ' - Mission de sortie pour Bail Mobilité',
            'status' => isset($data['exit_checker_id']) ? 'assigned' : 'unassigned',
            'agent_id' => $data['exit_checker_id'] ?? null,
            'bail_mobilite_id' => $bailMobilite->id,
            'ops_assigned_by' => Auth::id(),
        ]);

        // Update bail mobilité with mission IDs
        $bailMobilite->update([
            'entry_mission_id' => $entryMission->id,
            'exit_mission_id' => $exitMission->id,
        ]);

        return $bailMobilite->load(['entryMission', 'exitMission']);
    }

    /**
     * Get available time slots for a specific date.
     */
    public function getAvailableTimeSlots(Carbon $date, ?int $checkerId = null): array
    {
        // Define standard time slots (9 AM to 6 PM, every hour)
        $standardSlots = [];
        for ($hour = 9; $hour <= 18; $hour++) {
            $standardSlots[] = sprintf('%02d:00', $hour);
            if ($hour < 18) {
                $standardSlots[] = sprintf('%02d:30', $hour);
            }
        }

        // If no checker specified, return all standard slots
        if (!$checkerId) {
            return array_map(function ($slot) {
                return [
                    'time' => $slot,
                    'available' => true,
                    'conflicts' => [],
                ];
            }, $standardSlots);
        }

        // Get existing missions for the checker on this date
        $existingMissions = Mission::where('agent_id', $checkerId)
            ->whereDate('scheduled_at', $date)
            ->whereNotNull('scheduled_time')
            ->pluck('scheduled_time')
            ->map(function ($time) {
                return Carbon::parse($time)->format('H:i');
            })
            ->toArray();

        // Mark slots as available or not
        return array_map(function ($slot) use ($existingMissions) {
            return [
                'time' => $slot,
                'available' => !in_array($slot, $existingMissions),
                'conflicts' => in_array($slot, $existingMissions) ? ['Time slot already booked'] : [],
            ];
        }, $standardSlots);
    }

    /**
     * Detect scheduling conflicts for a mission.
     */
    public function detectSchedulingConflicts(Carbon $date, ?string $time, ?int $checkerId = null, ?int $excludeMissionId = null): array
    {
        $conflicts = [];

        // If no checker or time specified, no conflicts to check
        if (!$checkerId || !$time) {
            return $conflicts;
        }

        // Check for time conflicts with other missions
        $conflictingMissions = Mission::where('agent_id', $checkerId)
            ->whereDate('scheduled_at', $date)
            ->whereNotNull('scheduled_time')
            ->when($excludeMissionId, function ($query, $excludeId) {
                return $query->where('id', '!=', $excludeId);
            })
            ->with(['bailMobilite:id,tenant_name,address'])
            ->get()
            ->filter(function ($mission) use ($time) {
                if (!$mission->scheduled_time) {
                    return false;
                }
                
                $missionTime = is_string($mission->scheduled_time) 
                    ? substr($mission->scheduled_time, 0, 5) // Extract HH:MM from HH:MM:SS
                    : $mission->scheduled_time->format('H:i');
                    
                return $missionTime === $time;
            });

        if ($conflictingMissions->isNotEmpty()) {
            foreach ($conflictingMissions as $mission) {
                $conflicts[] = sprintf(
                    'Conflit avec mission %s chez %s (%s)',
                    $mission->mission_type === 'entry' ? 'd\'entrée' : 'de sortie',
                    $mission->tenant_name,
                    $mission->address
                );
            }
        }

        // Check if it's outside business hours
        $timeCarbon = Carbon::parse($time);
        if ($timeCarbon->hour < 9 || $timeCarbon->hour >= 19) {
            $conflicts[] = 'Heure en dehors des heures d\'ouverture (9h-19h)';
        }

        // Check if it's a weekend
        if ($date->isWeekend()) {
            $conflicts[] = 'Mission programmée un week-end';
        }

        return $conflicts;
    }

    /**
     * Check if the current user can edit a mission.
     */
    private function canEditMission(Mission $mission): bool
    {
        $user = Auth::user();
        
        // If no user is authenticated (e.g., in tests), return false
        if (!$user) {
            return false;
        }
        
        // Ops and Admin can edit missions they assigned or if they have the role
        if ($user->hasRole(['ops', 'admin'])) {
            return true;
        }

        return false;
    }

    /**
     * Check if the current user can assign a mission.
     */
    private function canAssignMission(Mission $mission): bool
    {
        $user = Auth::user();
        
        // If no user is authenticated (e.g., in tests), return false
        if (!$user) {
            return false;
        }
        
        // Only Ops and Admin can assign missions
        if ($user->hasRole(['ops', 'admin'])) {
            return $mission->status === 'unassigned';
        }

        return false;
    }
}