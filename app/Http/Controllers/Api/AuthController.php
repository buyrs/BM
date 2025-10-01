<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\LoginRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends BaseApiController
{
    public function __construct(
        private AuditLogger $auditLogger
    ) {}

    /**
     * Login user and create token
     * 
     * @group Authentication
     * 
     * Authenticate user credentials and return an API token for subsequent requests.
     * 
     * @bodyParam email string required The user's email address. Example: user@example.com
     * @bodyParam password string required The user's password. Example: password123
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Login successful",
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "name": "John Doe",
     *       "email": "user@example.com",
     *       "role": "checker"
     *     },
     *     "token": "1|abc123...",
     *     "expires_at": "2024-02-01T00:00:00.000000Z"
     *   }
     * }
     * 
     * @response 401 {
     *   "success": false,
     *   "message": "Invalid credentials"
     * }
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $credentials = $request->validated();

            // Attempt authentication
            if (!Auth::attempt($credentials)) {
                $this->auditLogger->log('api_login_failed', null, [
                    'email' => $credentials['email'],
                    'ip_address' => $request->ip(),
                ]);

                return $this->unauthorized('Invalid credentials');
            }

            $user = Auth::user();

            // Check if user account is active
            if (!$user || $user->deleted_at) {
                return $this->unauthorized('Account is inactive');
            }

            // Create API token
            $token = $user->createToken('api-token', ['*'], now()->addDays(30));

            // Log successful login
            $this->auditLogger->log('api_login_success', $user, [
                'token_name' => 'api-token',
                'ip_address' => $request->ip(),
            ]);

            return $this->success([
                'user' => $this->transformUser($user),
                'token' => $token->plainTextToken,
                'expires_at' => $token->accessToken->expires_at,
            ], 'Login successful');

        } catch (ValidationException $e) {
            return $this->validationError($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            return $this->serverError('Login failed');
        }
    }

    /**
     * Logout user and revoke token
     * 
     * @group Authentication
     * @authenticated
     * 
     * Revoke the current API token and log out the user.
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Logout successful"
     * }
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if ($user) {
                // Revoke current token
                $request->user()->currentAccessToken()->delete();

                // Log logout
                $this->auditLogger->log('api_logout', $user, [
                    'ip_address' => $request->ip(),
                ]);
            }

            return $this->success(null, 'Logout successful');

        } catch (\Exception $e) {
            return $this->serverError('Logout failed');
        }
    }

    /**
     * Refresh user token
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return $this->unauthorized('User not authenticated');
            }

            // Revoke current token
            $request->user()->currentAccessToken()->delete();

            // Create new token
            $token = $user->createToken('api-token', ['*'], now()->addDays(30));

            // Log token refresh
            $this->auditLogger->log('api_token_refresh', $user, [
                'ip_address' => $request->ip(),
            ]);

            return $this->success([
                'user' => $this->transformUser($user),
                'token' => $token->plainTextToken,
                'expires_at' => $token->accessToken->expires_at,
            ], 'Token refreshed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Token refresh failed');
        }
    }

    /**
     * Get authenticated user profile
     * 
     * @group Authentication
     * @authenticated
     * 
     * Retrieve the profile information of the currently authenticated user.
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Profile retrieved successfully",
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "name": "John Doe",
     *       "email": "user@example.com",
     *       "role": "checker",
     *       "two_factor_enabled": false,
     *       "timezone": "UTC",
     *       "preferences": {},
     *       "created_at": "2024-01-01T00:00:00.000000Z",
     *       "updated_at": "2024-01-01T00:00:00.000000Z"
     *     }
     *   }
     * }
     */
    public function profile(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return $this->unauthorized('User not authenticated');
            }

            return $this->success([
                'user' => $this->transformUser($user),
            ], 'Profile retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve profile');
        }
    }

    /**
     * Revoke all user tokens
     */
    public function revokeAllTokens(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return $this->unauthorized('User not authenticated');
            }

            // Revoke all tokens for the user
            $user->tokens()->delete();

            // Log token revocation
            $this->auditLogger->log('api_tokens_revoked', $user, [
                'ip_address' => $request->ip(),
            ]);

            return $this->success(null, 'All tokens revoked successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to revoke tokens');
        }
    }

    /**
     * Get user's active tokens
     */
    public function tokens(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return $this->unauthorized('User not authenticated');
            }

            $tokens = $user->tokens()->select(['id', 'name', 'created_at', 'last_used_at', 'expires_at'])->get();

            return $this->success([
                'tokens' => $tokens,
            ], 'Tokens retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve tokens');
        }
    }

    /**
     * Transform user for API response
     */
    private function transformUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'two_factor_enabled' => $user->two_factor_enabled,
            'timezone' => $user->timezone,
            'preferences' => $user->preferences,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }
}