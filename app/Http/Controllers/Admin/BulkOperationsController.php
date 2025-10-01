<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BulkOperationsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class BulkOperationsController extends Controller
{
    protected BulkOperationsService $bulkOperationsService;

    public function __construct(BulkOperationsService $bulkOperationsService)
    {
        $this->bulkOperationsService = $bulkOperationsService;
    }

    /**
     * Show the bulk operations dashboard
     */
    public function index(): View
    {
        $stats = $this->bulkOperationsService->getBulkOperationStats();
        
        return view('admin.bulk-operations.index', compact('stats'));
    }

    /**
     * Bulk assign missions
     */
    public function bulkAssignMissions(Request $request): JsonResponse
    {
        try {
            $results = $this->bulkOperationsService->bulkAssignMissions(
                $request->input('mission_ids', []),
                $request->input('assignments', [])
            );

            return response()->json([
                'success' => true,
                'message' => "Bulk assignment completed: {$results['success']} successful, {$results['failed']} failed",
                'results' => $results
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk assignment failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update mission status
     */
    public function bulkUpdateMissionStatus(Request $request): JsonResponse
    {
        try {
            $results = $this->bulkOperationsService->bulkUpdateMissionStatus(
                $request->input('mission_ids', []),
                $request->input('status')
            );

            return response()->json([
                'success' => true,
                'message' => "Status update completed: {$results['success']} successful, {$results['failed']} failed",
                'results' => $results
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Status update failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update users
     */
    public function bulkUpdateUsers(Request $request): JsonResponse
    {
        try {
            $results = $this->bulkOperationsService->bulkUpdateUsers(
                $request->input('user_ids', []),
                $request->input('updates', [])
            );

            return response()->json([
                'success' => true,
                'message' => "User update completed: {$results['success']} successful, {$results['failed']} failed",
                'results' => $results
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'User update failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk import properties
     */
    public function bulkImportProperties(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
        ]);

        try {
            $file = $request->file('file');
            $csvData = array_map('str_getcsv', file($file->getPathname()));
            
            // Remove header row
            $headers = array_shift($csvData);
            
            // Convert to associative array
            $propertiesData = [];
            foreach ($csvData as $row) {
                if (count($row) >= 4) { // Minimum required fields
                    $propertiesData[] = [
                        'owner_name' => $row[0] ?? '',
                        'owner_address' => $row[1] ?? '',
                        'property_address' => $row[2] ?? '',
                        'property_type' => $row[3] ?? '',
                        'description' => $row[4] ?? ''
                    ];
                }
            }

            $results = $this->bulkOperationsService->bulkImportProperties($propertiesData);

            return response()->json([
                'success' => true,
                'message' => "Import completed: {$results['success']} successful, {$results['failed']} failed, {$results['duplicates']} duplicates skipped",
                'results' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk delete properties
     */
    public function bulkDeleteProperties(Request $request): JsonResponse
    {
        try {
            $results = $this->bulkOperationsService->bulkDeleteProperties(
                $request->input('property_ids', [])
            );

            return response()->json([
                'success' => true,
                'message' => "Deletion completed: {$results['success']} successful, {$results['failed']} failed",
                'results' => $results
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Deletion failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk send notifications
     */
    public function bulkSendNotifications(Request $request): JsonResponse
    {
        try {
            $results = $this->bulkOperationsService->bulkSendNotifications(
                $request->input('user_ids', []),
                $request->input('notification_data', [])
            );

            return response()->json([
                'success' => true,
                'message' => "Notifications sent: {$results['success']} successful, {$results['failed']} failed",
                'results' => $results
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Notification sending failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get bulk operation statistics
     */
    public function getStats(): JsonResponse
    {
        try {
            $stats = $this->bulkOperationsService->getBulkOperationStats();
            
            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics: ' . $e->getMessage()
            ], 500);
        }
    }
}