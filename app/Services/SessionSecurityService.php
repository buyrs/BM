<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redis;

class SessionSecurityService
{
    /**
     * Track user login and update security information.
     */
    public function trackLogin(int $userId, string $ipAddress, string $userAgent): void
    {
        $user = Auth::user();
        
        if ($user) {
            $user->update([
                'last_login_at' => now(),
                'login_attempts' => 0, // Reset failed attempts on successful login
            ]);

            // Store session security info
            Session::put('security.ip_address', $ipAddress);
            Session::put('security.user_agent', $userAgent);
            Session::put('security.login_time', now()->timestamp);
            Session::put('security.last_activity', now()->timestamp);

            // Track active sessions in Redis
            $sessionKey = "user_sessions:{$userId}";
            $sessionData = [
                'session_id' => Session::getId(),
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'login_time' => now()->timestamp,
                'last_activity' => now()->timestamp,
            ];

            Redis::hset($sessionKey, Session::getId(), json_encode($sessionData));
            Redis::expire($sessionKey, config('session.lifetime') * 60);
        }
    }

    /**
     * Validate session security.
     */
    public function validateSession(): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $currentIp = request()->ip();
        $currentUserAgent = request()->userAgent();
        $sessionIp = Session::get('security.ip_address');
        $sessionUserAgent = Session::get('security.user_agent');

        // Check for IP address changes (optional - can be disabled for mobile users)
        if (config('session.strict_ip', false) && $sessionIp && $sessionIp !== $currentIp) {
            $this->invalidateSession('IP address changed');
            return false;
        }

        // Check for user agent changes
        if ($sessionUserAgent && $sessionUserAgent !== $currentUserAgent) {
            $this->invalidateSession('User agent changed');
            return false;
        }

        // Check session timeout
        $lastActivity = Session::get('security.last_activity');
        $maxInactivity = config('session.max_inactivity', 3600); // 1 hour default

        if ($lastActivity && (now()->timestamp - $lastActivity) > $maxInactivity) {
            $this->invalidateSession('Session timeout due to inactivity');
            return false;
        }

        // Update last activity
        Session::put('security.last_activity', now()->timestamp);
        $this->updateSessionActivity();

        return true;
    }

    /**
     * Invalidate current session.
     */
    public function invalidateSession(string $reason = 'Security violation'): void
    {
        $userId = Auth::id();
        $sessionId = Session::getId();

        // Log the session invalidation
        logger()->warning('Session invalidated', [
            'user_id' => $userId,
            'session_id' => $sessionId,
            'reason' => $reason,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Remove from Redis tracking
        if ($userId) {
            Redis::hdel("user_sessions:{$userId}", $sessionId);
        }

        // Invalidate Laravel session
        Session::invalidate();
        Session::regenerateToken();
        Auth::logout();
    }

    /**
     * Update session activity in Redis.
     */
    private function updateSessionActivity(): void
    {
        $userId = Auth::id();
        $sessionId = Session::getId();

        if ($userId && $sessionId) {
            $sessionKey = "user_sessions:{$userId}";
            $sessionData = Redis::hget($sessionKey, $sessionId);

            if ($sessionData) {
                $data = json_decode($sessionData, true);
                $data['last_activity'] = now()->timestamp;
                Redis::hset($sessionKey, $sessionId, json_encode($data));
            }
        }
    }

    /**
     * Get all active sessions for a user.
     */
    public function getActiveSessions(int $userId): array
    {
        $sessionKey = "user_sessions:{$userId}";
        $sessions = Redis::hgetall($sessionKey);
        $activeSessions = [];

        foreach ($sessions as $sessionId => $sessionData) {
            $data = json_decode($sessionData, true);
            $activeSessions[] = [
                'session_id' => $sessionId,
                'ip_address' => $data['ip_address'],
                'user_agent' => $data['user_agent'],
                'login_time' => $data['login_time'],
                'last_activity' => $data['last_activity'],
                'is_current' => $sessionId === Session::getId(),
            ];
        }

        // Sort by last activity (most recent first)
        usort($activeSessions, fn($a, $b) => $b['last_activity'] - $a['last_activity']);

        return $activeSessions;
    }

    /**
     * Terminate a specific session.
     */
    public function terminateSession(int $userId, string $sessionId): bool
    {
        $sessionKey = "user_sessions:{$userId}";
        
        // Remove from Redis
        $removed = Redis::hdel($sessionKey, $sessionId);

        // If it's the current session, invalidate it
        if ($sessionId === Session::getId()) {
            $this->invalidateSession('Session terminated by user');
        }

        return $removed > 0;
    }

    /**
     * Terminate all other sessions except current.
     */
    public function terminateOtherSessions(int $userId): int
    {
        $currentSessionId = Session::getId();
        $sessionKey = "user_sessions:{$userId}";
        $sessions = Redis::hgetall($sessionKey);
        $terminated = 0;

        foreach ($sessions as $sessionId => $sessionData) {
            if ($sessionId !== $currentSessionId) {
                Redis::hdel($sessionKey, $sessionId);
                $terminated++;
            }
        }

        return $terminated;
    }

    /**
     * Check for suspicious activity.
     */
    public function checkSuspiciousActivity(int $userId): array
    {
        $suspiciousActivities = [];
        $sessions = $this->getActiveSessions($userId);

        // Check for multiple concurrent sessions from different IPs
        $uniqueIps = array_unique(array_column($sessions, 'ip_address'));
        if (count($uniqueIps) > 3) {
            $suspiciousActivities[] = [
                'type' => 'multiple_ips',
                'message' => 'Multiple concurrent sessions from different IP addresses',
                'details' => ['ip_count' => count($uniqueIps), 'ips' => $uniqueIps]
            ];
        }

        // Check for sessions from unusual locations (would need GeoIP service)
        // This is a placeholder for geolocation-based detection

        // Check for rapid session creation
        $recentSessions = array_filter($sessions, fn($s) => $s['login_time'] > (now()->timestamp - 300)); // Last 5 minutes
        if (count($recentSessions) > 5) {
            $suspiciousActivities[] = [
                'type' => 'rapid_sessions',
                'message' => 'Rapid session creation detected',
                'details' => ['session_count' => count($recentSessions)]
            ];
        }

        return $suspiciousActivities;
    }

    /**
     * Clean up expired sessions.
     */
    public function cleanupExpiredSessions(): int
    {
        $pattern = 'user_sessions:*';
        $keys = Redis::keys($pattern);
        $cleaned = 0;

        foreach ($keys as $key) {
            $sessions = Redis::hgetall($key);
            $maxAge = config('session.lifetime') * 60;

            foreach ($sessions as $sessionId => $sessionData) {
                $data = json_decode($sessionData, true);
                if ((now()->timestamp - $data['last_activity']) > $maxAge) {
                    Redis::hdel($key, $sessionId);
                    $cleaned++;
                }
            }

            // Remove the key if no sessions remain
            if (Redis::hlen($key) === 0) {
                Redis::del($key);
            }
        }

        return $cleaned;
    }
}