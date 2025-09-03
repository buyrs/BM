<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleBasedAccessControlService
{
    /**
     * Validate user access to a resource with comprehensive logging
     */
    public function validateAccess(
        User $user,
        string $permission,
        ?Model $resource = null,
        array $context = []
    ): array {
        $startTime = microtime(true);
        
        $result = [
            'granted' => false,
            'user_id' => $user->id,
            'user_email' => $user->email,
            'permission' => $permission,
            'resource_type' => $resource ? get_class($resource) : null,
            'resource_id' => $resource?->getKey(),
            'context' => $context,
            'checks_performed' => [],
            'failure_reasons' => [],
            'validation_time_ms' => 0,
            'validated_at' => now()
        ];

        try {
            // Check 1: User exists and is active
            if (!$this->isUserActive($user)) {
                $result['failure_reasons'][] = 'User account is inactive or suspended';
                $result['checks_performed'][] = 'user_active_check';
                return $this->logAccessValidation($result);
            }
            $result['checks_performed'][] = 'user_active_check';

            // Check 2: Direct permission check
            $hasDirectPermission = $user->can($permission);
            $result['checks_performed'][] = 'direct_permission_check';
            
            if (!$hasDirectPermission) {
                $result['failure_reasons'][] = "User does not have direct permission: {$permission}";
            }

            // Check 3: Role-based permission check
            $rolePermissions = $this->getUserRolePermissions($user);
            $hasRolePermission = in_array($permission, $rolePermissions);
            $result['checks_performed'][] = 'role_permission_check';
            
            if (!$hasRolePermission) {
                $result['failure_reasons'][] = "User roles do not grant permission: {$permission}";
            }

            // Check 4: Resource-specific access control
            if ($resource) {
                $resourceAccess = $this->validateResourceAccess($user, $resource, $permission, $context);
                $result['checks_performed'][] = 'resource_access_check';
                $result['resource_access_details'] = $resourceAccess;
                
                if (!$resourceAccess['granted']) {
                    $result['failure_reasons'] = array_merge(
                        $result['failure_reasons'], 
                        $resourceAccess['failure_reasons']
                    );
                }
            }

            // Check 5: Time-based access restrictions
            $timeAccess = $this->validateTimeBasedAccess($user, $permission, $context);
            $result['checks_performed'][] = 'time_based_access_check';
            $result['time_access_details'] = $timeAccess;
            
            if (!$timeAccess['granted']) {
                $result['failure_reasons'] = array_merge(
                    $result['failure_reasons'], 
                    $timeAccess['failure_reasons']
                );
            }

            // Check 6: IP-based access restrictions
            $ipAccess = $this->validateIpBasedAccess($user, $permission, $context);
            $result['checks_performed'][] = 'ip_based_access_check';
            $result['ip_access_details'] = $ipAccess;
            
            if (!$ipAccess['granted']) {
                $result['failure_reasons'] = array_merge(
                    $result['failure_reasons'], 
                    $ipAccess['failure_reasons']
                );
            }

            // Check 7: Rate limiting
            $rateLimitAccess = $this->validateRateLimit($user, $permission, $context);
            $result['checks_performed'][] = 'rate_limit_check';
            $result['rate_limit_details'] = $rateLimitAccess;
            
            if (!$rateLimitAccess['granted']) {
                $result['failure_reasons'] = array_merge(
                    $result['failure_reasons'], 
                    $rateLimitAccess['failure_reasons']
                );
            }

            // Final decision: All checks must pass
            $result['granted'] = ($hasDirectPermission || $hasRolePermission) &&
                               (!$resource || $resourceAccess['granted']) &&
                               $timeAccess['granted'] &&
                               $ipAccess['granted'] &&
                               $rateLimitAccess['granted'];

            // Additional metadata
            $result['user_roles'] = $user->getRoleNames()->toArray();
            $result['user_permissions'] = $user->getAllPermissions()->pluck('name')->toArray();
            $result['validation_time_ms'] = round((microtime(true) - $startTime) * 1000, 2);

        } catch (\Exception $e) {
            $result['failure_reasons'][] = 'Access validation error: ' . $e->getMessage();
            $result['error'] = $e->getMessage();
            
            Log::error('RBAC validation error', [
                'user_id' => $user->id,
                'permission' => $permission,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $this->logAccessValidation($result);
    }

    /**
     * Check if user account is active
     */
    private function isUserActive(User $user): bool
    {
        // Check if user is verified
        if (!$user->hasVerifiedEmail()) {
            return false;
        }

        // Check for custom suspension fields if they exist
        if (method_exists($user, 'isSuspended') && $user->isSuspended()) {
            return false;
        }

        // Check if user was created recently (potential spam account)
        if ($user->created_at->gt(now()->subMinutes(5))) {
            // Allow but log for monitoring
            Log::channel('security')->info('Recently created user accessing system', [
                'user_id' => $user->id,
                'created_at' => $user->created_at,
                'minutes_old' => $user->created_at->diffInMinutes(now())
            ]);
        }

        return true;
    }

    /**
     * Get all permissions granted through user roles
     */
    private function getUserRolePermissions(User $user): array
    {
        return $user->getPermissionsViaRoles()->pluck('name')->toArray();
    }

    /**
     * Validate resource-specific access
     */
    private function validateResourceAccess(User $user, Model $resource, string $permission, array $context): array
    {
        $result = [
            'granted' => true,
            'failure_reasons' => [],
            'checks_performed' => []
        ];

        $resourceType = get_class($resource);

        // Resource-specific access rules
        switch ($resourceType) {
            case 'App\Models\BailMobilite':
                return $this->validateBailMobiliteAccess($user, $resource, $permission, $context);
                
            case 'App\Models\Mission':
                return $this->validateMissionAccess($user, $resource, $permission, $context);
                
            case 'App\Models\BailMobiliteSignature':
                return $this->validateSignatureAccess($user, $resource, $permission, $context);
                
            case 'App\Models\ContractTemplate':
                return $this->validateContractTemplateAccess($user, $resource, $permission, $context);
                
            case 'App\Models\User':
                return $this->validateUserAccess($user, $resource, $permission, $context);
                
            default:
                // Default resource access - check ownership or admin role
                if (method_exists($resource, 'user_id') && $resource->user_id === $user->id) {
                    $result['checks_performed'][] = 'ownership_check';
                    return $result;
                }
                
                if ($user->hasRole('admin')) {
                    $result['checks_performed'][] = 'admin_override';
                    return $result;
                }
                
                $result['granted'] = false;
                $result['failure_reasons'][] = 'No specific access rule for resource type and user is not owner or admin';
                return $result;
        }
    }

    /**
     * Validate BailMobilite access
     */
    private function validateBailMobiliteAccess(User $user, $bailMobilite, string $permission, array $context): array
    {
        $result = ['granted' => false, 'failure_reasons' => [], 'checks_performed' => []];

        // Admin can access all
        if ($user->hasRole('admin')) {
            $result['granted'] = true;
            $result['checks_performed'][] = 'admin_access';
            return $result;
        }

        // Ops can access bail mobilités they manage
        if ($user->hasRole('ops') && $bailMobilite->ops_user_id === $user->id) {
            $result['granted'] = true;
            $result['checks_performed'][] = 'ops_ownership';
            return $result;
        }

        // Checker can access bail mobilités for their assigned missions
        if ($user->hasRole('checker')) {
            $hasAssignedMission = $bailMobilite->missions()
                ->where('agent_id', $user->id)
                ->exists();
            
            if ($hasAssignedMission) {
                $result['granted'] = true;
                $result['checks_performed'][] = 'checker_assignment';
                return $result;
            }
        }

        $result['failure_reasons'][] = 'User does not have access to this bail mobilité';
        return $result;
    }

    /**
     * Validate Mission access
     */
    private function validateMissionAccess(User $user, $mission, string $permission, array $context): array
    {
        $result = ['granted' => false, 'failure_reasons' => [], 'checks_performed' => []];

        // Admin can access all
        if ($user->hasRole('admin')) {
            $result['granted'] = true;
            $result['checks_performed'][] = 'admin_access';
            return $result;
        }

        // Ops can access missions they assigned or manage
        if ($user->hasRole('ops')) {
            if ($mission->ops_assigned_by === $user->id || 
                ($mission->bailMobilite && $mission->bailMobilite->ops_user_id === $user->id)) {
                $result['granted'] = true;
                $result['checks_performed'][] = 'ops_management';
                return $result;
            }
        }

        // Checker can access their assigned missions
        if ($user->hasRole('checker') && $mission->agent_id === $user->id) {
            $result['granted'] = true;
            $result['checks_performed'][] = 'checker_assignment';
            return $result;
        }

        $result['failure_reasons'][] = 'User does not have access to this mission';
        return $result;
    }

    /**
     * Validate Signature access
     */
    private function validateSignatureAccess(User $user, $signature, string $permission, array $context): array
    {
        $result = ['granted' => false, 'failure_reasons' => [], 'checks_performed' => []];

        // Admin can access all signatures
        if ($user->hasRole('admin')) {
            $result['granted'] = true;
            $result['checks_performed'][] = 'admin_access';
            return $result;
        }

        // Ops can access signatures for bail mobilités they manage
        if ($user->hasRole('ops') && 
            $signature->bailMobilite && 
            $signature->bailMobilite->ops_user_id === $user->id) {
            $result['granted'] = true;
            $result['checks_performed'][] = 'ops_management';
            return $result;
        }

        // Checker can view signatures for missions they completed
        if ($user->hasRole('checker') && $permission === 'view') {
            $hasCompletedMission = $signature->bailMobilite
                ->missions()
                ->where('agent_id', $user->id)
                ->where('status', 'completed')
                ->exists();
            
            if ($hasCompletedMission) {
                $result['granted'] = true;
                $result['checks_performed'][] = 'checker_completed_mission';
                return $result;
            }
        }

        $result['failure_reasons'][] = 'User does not have access to this signature';
        return $result;
    }

    /**
     * Validate ContractTemplate access
     */
    private function validateContractTemplateAccess(User $user, $template, string $permission, array $context): array
    {
        $result = ['granted' => false, 'failure_reasons' => [], 'checks_performed' => []];

        // Only admin can manage contract templates
        if ($user->hasRole('admin')) {
            $result['granted'] = true;
            $result['checks_performed'][] = 'admin_access';
            return $result;
        }

        // Ops can view active templates
        if ($user->hasRole('ops') && $permission === 'view' && $template->is_active) {
            $result['granted'] = true;
            $result['checks_performed'][] = 'ops_view_active';
            return $result;
        }

        $result['failure_reasons'][] = 'Insufficient permissions for contract template access';
        return $result;
    }

    /**
     * Validate User access
     */
    private function validateUserAccess(User $user, User $targetUser, string $permission, array $context): array
    {
        $result = ['granted' => false, 'failure_reasons' => [], 'checks_performed' => []];

        // Users can always view/edit their own profile
        if ($user->id === $targetUser->id && in_array($permission, ['view', 'update'])) {
            $result['granted'] = true;
            $result['checks_performed'][] = 'self_access';
            return $result;
        }

        // Admin can manage all users
        if ($user->hasRole('admin')) {
            $result['granted'] = true;
            $result['checks_performed'][] = 'admin_access';
            return $result;
        }

        // Ops can view checker profiles
        if ($user->hasRole('ops') && $targetUser->hasRole('checker') && $permission === 'view') {
            $result['granted'] = true;
            $result['checks_performed'][] = 'ops_view_checker';
            return $result;
        }

        $result['failure_reasons'][] = 'Insufficient permissions for user access';
        return $result;
    }

    /**
     * Validate time-based access restrictions
     */
    private function validateTimeBasedAccess(User $user, string $permission, array $context): array
    {
        $result = ['granted' => true, 'failure_reasons' => [], 'restrictions_applied' => []];

        // Define time restrictions for sensitive operations
        $timeRestrictions = [
            'delete_signature' => ['start' => '09:00', 'end' => '17:00', 'days' => [1, 2, 3, 4, 5]], // Weekdays 9-5
            'bulk_delete' => ['start' => '10:00', 'end' => '16:00', 'days' => [1, 2, 3, 4, 5]], // Weekdays 10-4
            'export_data' => ['start' => '08:00', 'end' => '18:00', 'days' => [1, 2, 3, 4, 5, 6]], // Mon-Sat 8-6
        ];

        if (isset($timeRestrictions[$permission])) {
            $restriction = $timeRestrictions[$permission];
            $now = now();
            
            // Check day of week (1 = Monday, 7 = Sunday)
            if (!in_array($now->dayOfWeek, $restriction['days'])) {
                $result['granted'] = false;
                $result['failure_reasons'][] = "Operation '{$permission}' not allowed on " . $now->format('l');
                $result['restrictions_applied'][] = 'day_restriction';
            }
            
            // Check time of day
            $currentTime = $now->format('H:i');
            if ($currentTime < $restriction['start'] || $currentTime > $restriction['end']) {
                $result['granted'] = false;
                $result['failure_reasons'][] = "Operation '{$permission}' only allowed between {$restriction['start']} and {$restriction['end']}";
                $result['restrictions_applied'][] = 'time_restriction';
            }
        }

        // Admin users can override time restrictions in emergencies
        if (!$result['granted'] && $user->hasRole('admin') && isset($context['emergency']) && $context['emergency']) {
            $result['granted'] = true;
            $result['restrictions_applied'][] = 'admin_emergency_override';
            
            Log::channel('security')->warning('Admin emergency override of time restrictions', [
                'user_id' => $user->id,
                'permission' => $permission,
                'context' => $context
            ]);
        }

        return $result;
    }

    /**
     * Validate IP-based access restrictions
     */
    private function validateIpBasedAccess(User $user, string $permission, array $context): array
    {
        $result = ['granted' => true, 'failure_reasons' => [], 'ip_checks' => []];

        $request = request();
        $clientIp = $request->ip();
        
        // Define IP restrictions for sensitive operations
        $sensitiveOperations = [
            'delete_signature',
            'bulk_delete',
            'export_sensitive_data',
            'manage_users',
            'view_audit_logs'
        ];

        if (in_array($permission, $sensitiveOperations)) {
            // Check if IP is from a trusted network (example: office network)
            $trustedNetworks = [
                '192.168.1.0/24',
                '10.0.0.0/8',
                '172.16.0.0/12'
            ];

            $isTrustedIp = false;
            foreach ($trustedNetworks as $network) {
                if ($this->ipInRange($clientIp, $network)) {
                    $isTrustedIp = true;
                    $result['ip_checks'][] = "IP {$clientIp} is in trusted network {$network}";
                    break;
                }
            }

            // In production, you might want to be more restrictive
            if (app()->environment('production') && !$isTrustedIp) {
                // For now, just log but don't block
                Log::channel('security')->warning('Sensitive operation from untrusted IP', [
                    'user_id' => $user->id,
                    'permission' => $permission,
                    'ip' => $clientIp,
                    'user_agent' => $request->userAgent()
                ]);
                
                $result['ip_checks'][] = "IP {$clientIp} is not in trusted networks (logged for monitoring)";
            }
        }

        // Check for suspicious IP patterns
        if ($this->isSuspiciousIp($clientIp)) {
            $result['granted'] = false;
            $result['failure_reasons'][] = "Access denied from suspicious IP: {$clientIp}";
            $result['ip_checks'][] = 'suspicious_ip_detected';
        }

        return $result;
    }

    /**
     * Validate rate limiting
     */
    private function validateRateLimit(User $user, string $permission, array $context): array
    {
        $result = ['granted' => true, 'failure_reasons' => [], 'rate_limit_info' => []];

        // Define rate limits for sensitive operations (per hour)
        $rateLimits = [
            'delete_signature' => 5,
            'bulk_delete' => 2,
            'export_data' => 10,
            'create_user' => 20,
            'failed_login' => 5
        ];

        if (isset($rateLimits[$permission])) {
            $limit = $rateLimits[$permission];
            $cacheKey = "rate_limit:{$user->id}:{$permission}:" . now()->format('Y-m-d-H');
            
            $currentCount = cache()->get($cacheKey, 0);
            
            if ($currentCount >= $limit) {
                $result['granted'] = false;
                $result['failure_reasons'][] = "Rate limit exceeded for '{$permission}': {$currentCount}/{$limit} per hour";
                $result['rate_limit_info'] = [
                    'limit' => $limit,
                    'current' => $currentCount,
                    'window' => 'hourly',
                    'reset_at' => now()->addHour()->startOfHour()
                ];
            } else {
                // Increment counter
                cache()->put($cacheKey, $currentCount + 1, now()->addHour());
                $result['rate_limit_info'] = [
                    'limit' => $limit,
                    'current' => $currentCount + 1,
                    'window' => 'hourly',
                    'remaining' => $limit - ($currentCount + 1)
                ];
            }
        }

        return $result;
    }

    /**
     * Check if IP is in CIDR range
     */
    private function ipInRange(string $ip, string $range): bool
    {
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }

        list($subnet, $bits) = explode('/', $range);
        
        if ($bits === null) {
            $bits = 32;
        }

        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - $bits);
        $subnet &= $mask;

        return ($ip & $mask) === $subnet;
    }

    /**
     * Check if IP is suspicious
     */
    private function isSuspiciousIp(string $ip): bool
    {
        // Basic suspicious IP checks
        $suspiciousIps = [
            '0.0.0.0',
            '255.255.255.255'
        ];

        return in_array($ip, $suspiciousIps);
    }

    /**
     * Log access validation result
     */
    private function logAccessValidation(array $result): array
    {
        $logLevel = $result['granted'] ? 'info' : 'warning';
        $logMessage = $result['granted'] ? 'Access granted' : 'Access denied';

        // Log to audit service
        AuditService::logPermissionCheck(
            $result['permission'],
            $result['granted'],
            User::find($result['user_id']),
            null,
            [
                'validation_details' => $result,
                'checks_performed' => $result['checks_performed'],
                'failure_reasons' => $result['failure_reasons']
            ]
        );

        // Log to security channel
        Log::channel('security')->{$logLevel}($logMessage, [
            'user_id' => $result['user_id'],
            'user_email' => $result['user_email'],
            'permission' => $result['permission'],
            'resource_type' => $result['resource_type'],
            'resource_id' => $result['resource_id'],
            'granted' => $result['granted'],
            'checks_performed' => $result['checks_performed'],
            'failure_reasons' => $result['failure_reasons'],
            'validation_time_ms' => $result['validation_time_ms'],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);

        return $result;
    }

    /**
     * Get comprehensive access report for user
     */
    public function getUserAccessReport(User $user): array
    {
        return [
            'user_info' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'created_at' => $user->created_at,
                'email_verified_at' => $user->email_verified_at,
                'is_active' => $this->isUserActive($user)
            ],
            'roles' => $user->getRoleNames()->toArray(),
            'direct_permissions' => $user->getDirectPermissions()->pluck('name')->toArray(),
            'role_permissions' => $this->getUserRolePermissions($user),
            'all_permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
            'recent_access_attempts' => $this->getRecentAccessAttempts($user),
            'security_events' => AuditService::getSecurityEvents($user, 168), // Last 7 days
            'access_restrictions' => $this->getUserAccessRestrictions($user),
            'generated_at' => now()
        ];
    }

    /**
     * Get recent access attempts for user
     */
    private function getRecentAccessAttempts(User $user, int $hours = 24): array
    {
        return AuditService::searchLogs([
            'user_id' => $user->id,
            'event_type' => 'permission_denied',
            'start_date' => now()->subHours($hours)
        ], 20)->map(function ($log) {
            return [
                'permission' => $log->getMetadata('permission'),
                'occurred_at' => $log->occurred_at,
                'ip_address' => $log->ip_address,
                'route_name' => $log->route_name,
                'failure_reason' => $log->action
            ];
        })->toArray();
    }

    /**
     * Get user access restrictions
     */
    private function getUserAccessRestrictions(User $user): array
    {
        $restrictions = [];

        // Check for time-based restrictions
        if (!$user->hasRole('admin')) {
            $restrictions['time_based'] = [
                'sensitive_operations_weekdays_only' => true,
                'business_hours_only' => ['09:00', '17:00']
            ];
        }

        // Check for IP-based restrictions
        $restrictions['ip_based'] = [
            'trusted_networks_required' => !$user->hasRole('admin'),
            'suspicious_ip_blocking' => true
        ];

        // Check for rate limits
        $restrictions['rate_limits'] = [
            'delete_operations' => 5,
            'bulk_operations' => 2,
            'export_operations' => 10
        ];

        return $restrictions;
    }
}