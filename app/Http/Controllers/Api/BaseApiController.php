<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class BaseApiController extends Controller
{
    /**
     * Return a successful response
     */
    protected function success($data = null, string $message = 'Success', int $statusCode = Response::HTTP_OK): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return an error response
     */
    protected function error(string $message = 'Error', int $statusCode = Response::HTTP_BAD_REQUEST, array $errors = []): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a paginated response
     */
    protected function paginated(LengthAwarePaginator $paginator, string $message = 'Success'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'has_more_pages' => $paginator->hasMorePages(),
            ],
        ]);
    }

    /**
     * Return a collection response
     */
    protected function collection(Collection $collection, string $message = 'Success'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $collection,
            'count' => $collection->count(),
        ]);
    }

    /**
     * Return a not found response
     */
    protected function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->error($message, Response::HTTP_NOT_FOUND);
    }

    /**
     * Return an unauthorized response
     */
    protected function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->error($message, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Return a forbidden response
     */
    protected function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->error($message, Response::HTTP_FORBIDDEN);
    }

    /**
     * Return a validation error response
     */
    protected function validationError(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->error($message, Response::HTTP_UNPROCESSABLE_ENTITY, $errors);
    }

    /**
     * Return a server error response
     */
    protected function serverError(string $message = 'Internal server error'): JsonResponse
    {
        return $this->error($message, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Get pagination parameters from request
     */
    protected function getPaginationParams(Request $request): array
    {
        return [
            'page' => $request->get('page', 1),
            'per_page' => min($request->get('per_page', 15), 100), // Max 100 items per page
        ];
    }

    /**
     * Get sorting parameters from request
     */
    protected function getSortingParams(Request $request, array $allowedFields = []): array
    {
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        // Validate sort field
        if (!empty($allowedFields) && !in_array($sortBy, $allowedFields)) {
            $sortBy = 'created_at';
        }

        // Validate sort order
        if (!in_array(strtolower($sortOrder), ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        return [
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder,
        ];
    }

    /**
     * Get filtering parameters from request
     */
    protected function getFilterParams(Request $request, array $allowedFilters = []): array
    {
        $filters = [];

        foreach ($allowedFilters as $filter) {
            if ($request->has($filter)) {
                $filters[$filter] = $request->get($filter);
            }
        }

        return $filters;
    }

    /**
     * Transform model for API response
     */
    protected function transformModel($model, array $relations = []): array
    {
        if (!$model) {
            return [];
        }

        $data = $model->toArray();

        // Load specified relations
        if (!empty($relations)) {
            $model->load($relations);
            foreach ($relations as $relation) {
                $data[$relation] = $model->$relation;
            }
        }

        return $data;
    }

    /**
     * Check if user has required role
     */
    protected function checkRole(Request $request, array $allowedRoles): bool
    {
        $user = $request->user();
        
        if (!$user) {
            return false;
        }

        return in_array($user->role, $allowedRoles);
    }

    /**
     * Get authenticated user from request
     */
    protected function getAuthenticatedUser(Request $request)
    {
        return $request->user();
    }
}