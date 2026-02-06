<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Client;
use App\Models\Property;
use App\Models\Mission;

class ClientPolicy
{
    /**
     * Determine if user can view client portal.
     */
    public function viewPortal(User $user): bool
    {
        // Must have client role and be associated with a client
        return $user->role === 'client' && $this->getClientForUser($user) !== null;
    }

    /**
     * Determine if user can view a specific property.
     */
    public function viewProperty(User $user, Property $property): bool
    {
        // Admins and ops can view all
        if (in_array($user->role, ['admin', 'ops'])) {
            return true;
        }

        // Clients can only view their own properties
        if ($user->role === 'client') {
            $client = $this->getClientForUser($user);
            return $client && $client->canAccessProperty($property);
        }

        // Checkers can view properties they have missions for
        if ($user->role === 'checker') {
            return Mission::where('checker_id', $user->id)
                ->where('property_id', $property->id)
                ->exists();
        }

        return false;
    }

    /**
     * Determine if user can view a specific mission.
     */
    public function viewMission(User $user, Mission $mission): bool
    {
        // Admins and ops can view all
        if (in_array($user->role, ['admin', 'ops'])) {
            return true;
        }

        // Clients can only view missions for their properties
        if ($user->role === 'client') {
            $client = $this->getClientForUser($user);
            return $client && $client->canAccessMission($mission);
        }

        // Checkers can view their assigned missions
        if ($user->role === 'checker') {
            return $mission->checker_id === $user->id;
        }

        return false;
    }

    /**
     * Determine if user can view mission report/details.
     */
    public function viewMissionReport(User $user, Mission $mission): bool
    {
        // Same as viewMission but only for completed missions for clients
        if ($user->role === 'client') {
            $client = $this->getClientForUser($user);
            return $client && 
                   $client->canAccessMission($mission) && 
                   $mission->status === 'completed';
        }

        return $this->viewMission($user, $mission);
    }

    /**
     * Get client record for a user.
     */
    private function getClientForUser(User $user): ?Client
    {
        return Client::where('user_id', $user->id)->first();
    }

    /**
     * Apply scope to property query for user.
     */
    public static function scopePropertiesForUser($query, User $user)
    {
        if (in_array($user->role, ['admin', 'ops'])) {
            return $query; // No restriction
        }

        if ($user->role === 'client') {
            $client = Client::where('user_id', $user->id)->first();
            if ($client) {
                return $query->where('client_id', $client->id);
            }
            return $query->whereRaw('1 = 0'); // No access
        }

        if ($user->role === 'checker') {
            $propertyIds = Mission::where('checker_id', $user->id)
                ->pluck('property_id')
                ->unique();
            return $query->whereIn('id', $propertyIds);
        }

        return $query->whereRaw('1 = 0'); // Default no access
    }

    /**
     * Apply scope to mission query for user.
     */
    public static function scopeMissionsForUser($query, User $user)
    {
        if (in_array($user->role, ['admin', 'ops'])) {
            return $query; // No restriction
        }

        if ($user->role === 'client') {
            $client = Client::where('user_id', $user->id)->first();
            if ($client) {
                $propertyIds = $client->getPropertyIds();
                return $query->whereIn('property_id', $propertyIds)
                             ->where('status', 'completed'); // Clients only see completed
            }
            return $query->whereRaw('1 = 0');
        }

        if ($user->role === 'checker') {
            return $query->where('checker_id', $user->id);
        }

        return $query->whereRaw('1 = 0');
    }
}
