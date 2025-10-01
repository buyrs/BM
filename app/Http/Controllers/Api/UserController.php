<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends BaseApiController
{
    public function __construct(
        private AuditLogger $auditLogger
    ) {}

    /**
     * Get all users with pagination and filtering
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Check permissions - only admin and ops can list users
            if (!$this->checkRole($request, ['admin', 'ops'])) {
                return $this->forbidden('Insufficient permissions to list users');
            }

            $user = $this->getAuthenticatedUser($request);
            
            // Get pagination and sorting parameters
            $paginationParams = $this->getPaginationParams($request);
            $sortingParams = $this->getSortingParams($request, ['name', 'email', 'role', 'created_at']);
            $filters = $this->getFilterParams($request, ['role', 'search']);

            // Build query
            $query = User::query();

            // Apply filters
            if (!empty($filters['role'])) {
                $query->where('role', $filters['role']);
            }

            if (!empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            // Apply sorting
            $query->orderBy($sortingParams['sort_by'], $sortingParams['sort_order']);

            // Get paginated results
            $users = $query->paginate($paginationParams['per_page']);

            // Transform data
            $transformedUsers = $users->getCollection()->map(function ($user) {
                return $this->transformUser($user);
            });

            $users->setCollection($transformedUsers);

            return $this->paginated($users, 'Users retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve users');
        }
    }

    /**
     * Get a specific user by ID
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $currentUser = $this->getAuthenticatedUser($request);
            
            // Users can view their own profile, admin/ops can view others
            if ($currentUser->id !== $id && !$this->checkRole($request, ['admin', 'ops'])) {
                return $this->forbidden('You can only view your own profile');
            }

            $user = User::findOrFail($id);

            return $this->success([
                'user' => $this->transformUser($user, ['detailed'])
            ], 'User retrieved successfully');

        } catch (\Exception $e) {
            return $this->notFound('User not found');
        }
    }

    /**
     * Create a new user
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Check permissions - only admin can create users
            if (!$this->checkRole($request, ['admin'])) {
                return $this->forbidden('Insufficient permissions to create users');
            }

            $currentUser = $this->getAuthenticatedUser($request);

            // Validate request
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'unique:users,email', 'max:255'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
                'role' => ['required', 'string', Rule::in(['admin', 'ops', 'checker'])],
                'timezone' => ['nullable', 'string', 'max:50'],
            ]);

            // Hash password
            $validated['password'] = Hash::make($validated['password']);

            // Create user
            $user = User::create($validated);

            // Log the action
            $this->auditLogger->log('user_created', $currentUser, [
                'created_user_id' => $user->id,
                'created_user_email' => $user->email,
                'created_user_role' => $user->role,
            ]);

            return $this->success([
                'user' => $this->transformUser($user)
            ], 'User created successfully', 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Failed to create user');
        }
    }

    /**
     * Update an existing user
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $currentUser = $this->getAuthenticatedUser($request);
            $user = User::findOrFail($id);

            // Check permissions
            $canUpdateOthers = $this->checkRole($request, ['admin']);
            $isOwnProfile = $currentUser->id === $id;

            if (!$canUpdateOthers && !$isOwnProfile) {
                return $this->forbidden('You can only update your own profile');
            }

            // Define validation rules based on permissions
            $rules = [
                'name' => ['sometimes', 'string', 'max:255'],
                'email' => ['sometimes', 'email', 'max:255', Rule::unique('users')->ignore($id)],
                'timezone' => ['nullable', 'string', 'max:50'],
                'preferences' => ['nullable', 'array'],
            ];

            // Only admin can change roles and passwords for others
            if ($canUpdateOthers && !$isOwnProfile) {
                $rules['role'] = ['sometimes', 'string', Rule::in(['admin', 'ops', 'checker'])];
            }

            // Password change (own profile or admin changing others)
            if ($request->has('password')) {
                $rules['password'] = ['string', 'min:8', 'confirmed'];
            }

            $validated = $request->validate($rules);

            // Hash password if provided
            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            }

            // Store original data for audit
            $originalData = $user->toArray();

            // Update user
            $user->update($validated);

            // Log the action
            $this->auditLogger->log('user_updated', $currentUser, [
                'updated_user_id' => $user->id,
                'updated_user_email' => $user->email,
                'is_own_profile' => $isOwnProfile,
                'changes' => array_diff_assoc($validated, $originalData),
            ]);

            return $this->success([
                'user' => $this->transformUser($user)
            ], 'User updated successfully');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->notFound('User not found');
        }
    }

    /**
     * Delete a user
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            // Check permissions - only admin can delete users
            if (!$this->checkRole($request, ['admin'])) {
                return $this->forbidden('Insufficient permissions to delete users');
            }

            $currentUser = $this->getAuthenticatedUser($request);
            $user = User::findOrFail($id);

            // Prevent self-deletion
            if ($currentUser->id === $id) {
                return $this->error('You cannot delete your own account', 409);
            }

            // Check if user has active missions
            $activeMissions = $user->missions()->whereIn('status', ['pending', 'in_progress'])->count();
            
            if ($activeMissions > 0) {
                return $this->error('Cannot delete user with active missions', 409);
            }

            // Store user data for audit
            $userData = $user->toArray();

            // Delete user
            $user->delete();

            // Log the action
            $this->auditLogger->log('user_deleted', $currentUser, [
                'deleted_user_id' => $id,
                'deleted_user_email' => $userData['email'],
                'deleted_user_role' => $userData['role'],
            ]);

            return $this->success(null, 'User deleted successfully');

        } catch (\Exception $e) {
            return $this->notFound('User not found');
        }
    }

    /**
     * Get user statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            // Check permissions
            if (!$this->checkRole($request, ['admin', 'ops'])) {
                return $this->forbidden('Insufficient permissions to view statistics');
            }

            $stats = [
                'total_users' => User::count(),
                'admin_users' => User::where('role', 'admin')->count(),
                'ops_users' => User::where('role', 'ops')->count(),
                'checker_users' => User::where('role', 'checker')->count(),
                'users_with_2fa' => User::where('two_factor_enabled', true)->count(),
                'recent_users' => User::orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get()
                    ->map(fn($user) => $this->transformUser($user)),
            ];

            return $this->success($stats, 'User statistics retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve user statistics');
        }
    }

    /**
     * Get users by role (for dropdowns, etc.)
     */
    public function byRole(Request $request, string $role): JsonResponse
    {
        try {
            // Check permissions
            if (!$this->checkRole($request, ['admin', 'ops'])) {
                return $this->forbidden('Insufficient permissions to list users by role');
            }

            // Validate role
            if (!in_array($role, ['admin', 'ops', 'checker'])) {
                return $this->error('Invalid role specified', 422);
            }

            $users = User::where('role', $role)
                ->select(['id', 'name', 'email'])
                ->orderBy('name')
                ->get();

            return $this->success([
                'users' => $users,
                'role' => $role,
                'count' => $users->count(),
            ], "Users with role '{$role}' retrieved successfully");

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve users by role');
        }
    }

    /**
     * Transform user for API response
     */
    private function transformUser(User $user, array $options = []): array
    {
        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'two_factor_enabled' => $user->two_factor_enabled,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];

        // Include detailed information if requested
        if (in_array('detailed', $options)) {
            $data['timezone'] = $user->timezone;
            $data['preferences'] = $user->preferences;
            $data['last_login_at'] = $user->last_login_at;
        }

        return $data;
    }
}