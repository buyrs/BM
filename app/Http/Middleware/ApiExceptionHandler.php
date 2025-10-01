<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpFoundation\Response;

class ApiExceptionHandler
{
    /**
     * Handle an incoming request and catch exceptions.
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $next($request);
        } catch (ValidationException $e) {
            return $this->handleValidationException($e);
        } catch (ModelNotFoundException $e) {
            return $this->handleModelNotFoundException($e);
        } catch (NotFoundHttpException $e) {
            return $this->handleNotFoundHttpException($e);
        } catch (MethodNotAllowedHttpException $e) {
            return $this->handleMethodNotAllowedException($e);
        } catch (AuthenticationException $e) {
            return $this->handleAuthenticationException($e);
        } catch (AuthorizationException $e) {
            return $this->handleAuthorizationException($e);
        } catch (TooManyRequestsHttpException $e) {
            return $this->handleTooManyRequestsException($e);
        } catch (\Exception $e) {
            return $this->handleGenericException($e);
        }
    }

    /**
     * Handle validation exceptions
     */
    private function handleValidationException(ValidationException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'error' => [
                'code' => 'VALIDATION_FAILED',
                'message' => 'The given data was invalid',
                'details' => $e->errors(),
            ]
        ], 422);
    }

    /**
     * Handle model not found exceptions
     */
    private function handleModelNotFoundException(ModelNotFoundException $e): JsonResponse
    {
        $model = class_basename($e->getModel());
        
        return response()->json([
            'success' => false,
            'message' => 'Resource not found',
            'error' => [
                'code' => 'RESOURCE_NOT_FOUND',
                'message' => "{$model} not found",
            ]
        ], 404);
    }

    /**
     * Handle not found HTTP exceptions
     */
    private function handleNotFoundHttpException(NotFoundHttpException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Endpoint not found',
            'error' => [
                'code' => 'ENDPOINT_NOT_FOUND',
                'message' => 'The requested endpoint does not exist',
            ]
        ], 404);
    }

    /**
     * Handle method not allowed exceptions
     */
    private function handleMethodNotAllowedException(MethodNotAllowedHttpException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Method not allowed',
            'error' => [
                'code' => 'METHOD_NOT_ALLOWED',
                'message' => 'The HTTP method is not allowed for this endpoint',
                'allowed_methods' => $e->getHeaders()['Allow'] ?? [],
            ]
        ], 405);
    }

    /**
     * Handle authentication exceptions
     */
    private function handleAuthenticationException(AuthenticationException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Unauthenticated',
            'error' => [
                'code' => 'UNAUTHENTICATED',
                'message' => 'Authentication is required to access this resource',
            ]
        ], 401);
    }

    /**
     * Handle authorization exceptions
     */
    private function handleAuthorizationException(AuthorizationException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Forbidden',
            'error' => [
                'code' => 'FORBIDDEN',
                'message' => 'You do not have permission to access this resource',
            ]
        ], 403);
    }

    /**
     * Handle too many requests exceptions
     */
    private function handleTooManyRequestsException(TooManyRequestsHttpException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Too many requests',
            'error' => [
                'code' => 'RATE_LIMIT_EXCEEDED',
                'message' => 'Rate limit exceeded. Please try again later.',
                'retry_after' => $e->getHeaders()['Retry-After'] ?? null,
            ]
        ], 429);
    }

    /**
     * Handle generic exceptions
     */
    private function handleGenericException(\Exception $e): JsonResponse
    {
        // Log the exception for debugging
        \Log::error('API Exception: ' . $e->getMessage(), [
            'exception' => $e,
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Internal server error',
            'error' => [
                'code' => 'INTERNAL_SERVER_ERROR',
                'message' => 'An unexpected error occurred',
            ]
        ], 500);
    }
}