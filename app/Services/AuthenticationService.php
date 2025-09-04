<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

class AuthenticationService
{
    /**
     * Handle user login and redirect based on role
     */
    public function handleLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            
            $user = Auth::user();
            $this->logUserActivity($user, 'login', $request);
            
            return $this->getRoleBasedRedirect($user);
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Handle user registration
     */
    public function handleRegistration(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Assign default role based on registration context
        $defaultRole = $this->getDefaultRoleForRegistration($request);
        $user->assignRole($defaultRole);

        Auth::login($user);
        $this->logUserActivity($user, 'registration', $request);

        return $this->getRoleBasedRedirect($user);
    }

    /**
     * Handle Google OAuth callback
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            
            $user = User::where('google_id', $googleUser->id)->first();
            
            if ($user) {
                Auth::login($user);
                $this->logUserActivity($user, 'google_login');
                return $this->getRoleBasedRedirect($user);
            }

            // Check if user exists with same email
            $existingUser = User::where('email', $googleUser->email)->first();
            if ($existingUser) {
                // Link Google account to existing user
                $existingUser->update([
                    'google_id' => $googleUser->id,
                    'avatar' => $googleUser->avatar,
                ]);
                
                Auth::login($existingUser);
                $this->logUserActivity($existingUser, 'google_login');
                return $this->getRoleBasedRedirect($existingUser);
            }

            // Create new user
            $newUser = User::create([
                'name' => $googleUser->name,
                'email' => $googleUser->email,
                'google_id' => $googleUser->id,
                'avatar' => $googleUser->avatar,
                'password' => Hash::make(uniqid()), // Random password for OAuth users
                'email_verified_at' => now(),
            ]);

            // Assign default role
            $newUser->assignRole('checker');
            
            Auth::login($newUser);
            $this->logUserActivity($newUser, 'google_registration');
            
            return $this->getRoleBasedRedirect($newUser);
            
        } catch (\Exception $e) {
            \Log::error('Google OAuth Error: ' . $e->getMessage());
            return redirect()->route('login')->withErrors([
                'email' => 'Google authentication failed. Please try again.',
            ]);
        }
    }

    /**
     * Get role-based redirect URL
     */
    public function getRoleBasedRedirect(User $user)
    {
        if ($user->hasRole('super-admin')) {
            return redirect()->route('super-admin.dashboard');
        }
        
        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }
        
        if ($user->hasRole('ops')) {
            return redirect()->route('ops.dashboard');
        }
        
        if ($user->hasRole('checker')) {
            return redirect()->route('checker.dashboard');
        }

        // Default fallback
        return redirect()->route('dashboard');
    }

    /**
     * Get default role for registration based on context
     */
    private function getDefaultRoleForRegistration(Request $request)
    {
        // Check if registration is coming from a specific role context
        if ($request->is('admin/*')) {
            return 'admin';
        }
        
        if ($request->is('ops/*')) {
            return 'ops';
        }
        
        if ($request->is('checker/*')) {
            return 'checker';
        }

        // Default role for general registration
        return 'checker';
    }

    /**
     * Log user activity for audit purposes
     */
    private function logUserActivity(User $user, string $action, Request $request = null)
    {
        $metadata = [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'action' => $action,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'timestamp' => now(),
        ];

        \Log::info('User Authentication Activity', $metadata);
    }

    /**
     * Check if user has permission to access a specific route
     */
    public function hasRoutePermission(User $user, string $routeName)
    {
        // Define route-permission mappings
        $routePermissions = [
            'admin.*' => ['access_admin_panel'],
            'ops.*' => ['view_ops_dashboard'],
            'checker.*' => ['view_missions'],
            'missions.create' => ['create_missions'],
            'missions.edit' => ['edit_missions'],
            'missions.destroy' => ['delete_missions'],
            'checklists.create' => ['create_checklists'],
            'checklists.edit' => ['edit_checklists'],
            'bail-mobilites.create' => ['create_bail_mobilite'],
            'bail-mobilites.edit' => ['edit_bail_mobilite'],
            'contract-templates.*' => ['view_contract_templates'],
        ];

        foreach ($routePermissions as $pattern => $permissions) {
            if (fnmatch($pattern, $routeName)) {
                return $user->hasAnyPermission($permissions);
            }
        }

        return true; // Default allow if no specific permission required
    }

    /**
     * Get user's accessible routes based on their role
     */
    public function getAccessibleRoutes(User $user)
    {
        $routes = [];

        if ($user->hasRole('super-admin')) {
            $routes = array_merge($routes, [
                'super-admin.dashboard',
                'admin.dashboard',
                'ops.dashboard',
                'checker.dashboard',
            ]);
        }

        if ($user->hasRole('admin')) {
            $routes = array_merge($routes, [
                'admin.dashboard',
                'admin.missions',
                'admin.checkers',
                'admin.analytics.data',
                'contract-templates.*',
            ]);
        }

        if ($user->hasRole('ops')) {
            $routes = array_merge($routes, [
                'ops.dashboard',
                'ops.bail-mobilites.*',
                'ops.calendar.*',
                'ops.notifications',
            ]);
        }

        if ($user->hasRole('checker')) {
            $routes = array_merge($routes, [
                'checker.dashboard',
                'checker.missions',
                'missions.show',
                'checklists.*',
            ]);
        }

        return array_unique($routes);
    }

    /**
     * Validate user session and refresh if needed
     */
    public function validateUserSession(User $user)
    {
        // Check if user account is still active
        if (!$user->email_verified_at) {
            return false;
        }

        // Check if user has been deactivated (if you have a status field)
        // if ($user->status === 'inactive') {
        //     return false;
        // }

        return true;
    }

    /**
     * Get user's dashboard data based on role
     */
    public function getDashboardData(User $user)
    {
        $data = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name'),
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ],
            'accessible_routes' => $this->getAccessibleRoutes($user),
        ];

        // Add role-specific data
        if ($user->hasRole('admin') || $user->hasRole('super-admin')) {
            $data['admin_stats'] = $this->getAdminStats();
        }

        if ($user->hasRole('ops')) {
            $data['ops_stats'] = $this->getOpsStats();
        }

        if ($user->hasRole('checker')) {
            $data['checker_stats'] = $this->getCheckerStats($user);
        }

        return $data;
    }

    /**
     * Get admin dashboard statistics
     */
    private function getAdminStats()
    {
        return [
            'total_users' => User::count(),
            'total_checkers' => User::role('checker')->count(),
            'total_ops' => User::role('ops')->count(),
            'recent_users' => User::with('roles')
                ->latest()
                ->limit(5)
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'roles' => $user->roles->pluck('name'),
                        'created_at' => $user->created_at,
                    ];
                }),
        ];
    }

    /**
     * Get ops dashboard statistics
     */
    private function getOpsStats()
    {
        return [
            'total_missions' => \App\Models\Mission::count(),
            'pending_missions' => \App\Models\Mission::where('status', 'unassigned')->count(),
            'active_checkers' => User::role('checker')->count(),
        ];
    }

    /**
     * Get checker dashboard statistics
     */
    private function getCheckerStats(User $user)
    {
        return [
            'assigned_missions' => $user->assignedMissions()->whereIn('status', ['assigned', 'in_progress'])->count(),
            'completed_missions' => $user->assignedMissions()->where('status', 'completed')->count(),
            'pending_checklists' => 0, // This would be calculated based on your checklist system
        ];
    }
}
