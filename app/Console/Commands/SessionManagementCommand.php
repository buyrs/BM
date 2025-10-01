<?php

namespace App\Console\Commands;

use App\Services\SessionSecurityService;
use Illuminate\Console\Command;

class SessionManagementCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'session:manage 
                            {action : The action to perform (cleanup|stats|terminate)}
                            {--user-id= : User ID for user-specific actions}
                            {--session-id= : Session ID to terminate}
                            {--all : Terminate all sessions for a user}';

    /**
     * The console command description.
     */
    protected $description = 'Manage user sessions and security';

    public function __construct(
        private SessionSecurityService $sessionSecurityService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'cleanup' => $this->cleanupSessions(),
            'stats' => $this->showSessionStats(),
            'terminate' => $this->terminateSessions(),
            default => $this->error("Unknown action: {$action}. Use: cleanup, stats, or terminate")
        };
    }

    /**
     * Clean up expired sessions.
     */
    private function cleanupSessions(): int
    {
        $this->info('Cleaning up expired sessions...');
        
        $cleaned = $this->sessionSecurityService->cleanupExpiredSessions();
        
        $this->info("Cleaned up {$cleaned} expired sessions.");
        
        return 0;
    }

    /**
     * Show session statistics.
     */
    private function showSessionStats(): int
    {
        $userId = $this->option('user-id');
        
        if ($userId) {
            $sessions = $this->sessionSecurityService->getActiveSessions((int) $userId);
            
            $this->info("Active sessions for user {$userId}:");
            $this->newLine();
            
            if (empty($sessions)) {
                $this->info('No active sessions found.');
                return 0;
            }
            
            $headers = ['Session ID', 'IP Address', 'User Agent', 'Login Time', 'Last Activity', 'Current'];
            $rows = [];
            
            foreach ($sessions as $session) {
                $rows[] = [
                    substr($session['session_id'], 0, 16) . '...',
                    $session['ip_address'],
                    substr($session['user_agent'], 0, 50) . '...',
                    date('Y-m-d H:i:s', $session['login_time']),
                    date('Y-m-d H:i:s', $session['last_activity']),
                    $session['is_current'] ? 'Yes' : 'No'
                ];
            }
            
            $this->table($headers, $rows);
            
            // Check for suspicious activity
            $suspicious = $this->sessionSecurityService->checkSuspiciousActivity((int) $userId);
            if (!empty($suspicious)) {
                $this->newLine();
                $this->warn('Suspicious activity detected:');
                foreach ($suspicious as $activity) {
                    $this->line("- {$activity['message']}");
                }
            }
        } else {
            $this->error('Please provide a user ID with --user-id option');
            return 1;
        }
        
        return 0;
    }

    /**
     * Terminate sessions.
     */
    private function terminateSessions(): int
    {
        $userId = $this->option('user-id');
        $sessionId = $this->option('session-id');
        $all = $this->option('all');
        
        if (!$userId) {
            $this->error('Please provide a user ID with --user-id option');
            return 1;
        }
        
        if ($all) {
            if ($this->confirm("Are you sure you want to terminate ALL sessions for user {$userId}?")) {
                $terminated = $this->sessionSecurityService->terminateOtherSessions((int) $userId);
                $this->info("Terminated {$terminated} sessions for user {$userId}.");
            } else {
                $this->info('Operation cancelled.');
            }
        } elseif ($sessionId) {
            $success = $this->sessionSecurityService->terminateSession((int) $userId, $sessionId);
            if ($success) {
                $this->info("Session {$sessionId} terminated for user {$userId}.");
            } else {
                $this->error("Failed to terminate session {$sessionId} for user {$userId}.");
            }
        } else {
            $this->error('Please provide either --session-id or --all option');
            return 1;
        }
        
        return 0;
    }
}