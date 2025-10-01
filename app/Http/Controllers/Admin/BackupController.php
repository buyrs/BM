<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DatabaseBackupService;
use App\Services\FileBackupService;
use App\Services\BackupMonitoringService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class BackupController extends Controller
{
    protected DatabaseBackupService $databaseBackupService;
    protected FileBackupService $fileBackupService;
    protected BackupMonitoringService $monitoringService;

    public function __construct(
        DatabaseBackupService $databaseBackupService,
        FileBackupService $fileBackupService,
        BackupMonitoringService $monitoringService
    ) {
        $this->databaseBackupService = $databaseBackupService;
        $this->fileBackupService = $fileBackupService;
        $this->monitoringService = $monitoringService;
    }

    /**
     * Show backup dashboard
     */
    public function index(): View
    {
        return view('admin.backups.index');
    }

    /**
     * Get backup dashboard data
     */
    public function dashboard(): JsonResponse
    {
        try {
            // Get health check results
            $healthResult = $this->monitoringService->performHealthCheck();
            $statsResult = $this->monitoringService->getBackupStatistics();
            
            $data = [
                'health' => $healthResult['success'] ? $healthResult['data'] : null,
                'statistics' => $statsResult['success'] ? $statsResult['data'] : null,
                'last_updated' => now()
            ];
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * List database backups
     */
    public function listDatabaseBackups(): JsonResponse
    {
        $result = $this->databaseBackupService->listBackups();
        
        return response()->json($result);
    }

    /**
     * List file backups
     */
    public function listFileBackups(): JsonResponse
    {
        $result = $this->fileBackupService->listBackups();
        
        return response()->json($result);
    }

    /**
     * Create database backup
     */
    public function createDatabaseBackup(Request $request): JsonResponse
    {
        $request->validate([
            'compress' => 'boolean',
            'encrypt' => 'boolean',
            'encryption_key' => 'nullable|string|min:8',
            'verify' => 'boolean'
        ]);

        $options = [
            'compress' => $request->boolean('compress', true),
            'encrypt' => $request->boolean('encrypt', false),
            'encryption_key' => $request->input('encryption_key'),
            'verify' => $request->boolean('verify', true)
        ];

        $result = $this->databaseBackupService->createBackup($options);
        
        if ($result['success'] && $options['verify']) {
            $verifyResult = $this->databaseBackupService->verifyBackup($result['data']['path']);
            $result['data']['verification'] = $verifyResult;
        }

        return response()->json($result);
    }

    /**
     * Create file backup
     */
    public function createFileBackup(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:full,incremental',
            'encrypt' => 'boolean',
            'encryption_key' => 'nullable|string|min:8'
        ]);

        $type = $request->input('type');
        $options = [
            'encrypt' => $request->boolean('encrypt', false),
            'encryption_key' => $request->input('encryption_key')
        ];

        if ($type === 'incremental') {
            $result = $this->fileBackupService->createIncrementalBackup($options);
        } else {
            $result = $this->fileBackupService->createFullBackup($options);
        }

        return response()->json($result);
    }

    /**
     * Verify backup
     */
    public function verifyBackup(Request $request): JsonResponse
    {
        $request->validate([
            'path' => 'required|string',
            'type' => 'required|in:database,file'
        ]);

        $path = $request->input('path');
        $type = $request->input('type');

        if ($type === 'database') {
            $result = $this->databaseBackupService->verifyBackup($path);
        } else {
            $result = $this->fileBackupService->verifyBackup($path);
        }

        return response()->json($result);
    }

    /**
     * Delete backup
     */
    public function deleteBackup(Request $request): JsonResponse
    {
        $request->validate([
            'path' => 'required|string'
        ]);

        try {
            $path = $request->input('path');
            $disk = config('backup.disk', 'local');
            
            if (!\Storage::disk($disk)->exists($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Backup file not found'
                ], 404);
            }
            
            \Storage::disk($disk)->delete($path);
            
            return response()->json([
                'success' => true,
                'message' => 'Backup deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete backup: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download backup
     */
    public function downloadBackup(Request $request)
    {
        $request->validate([
            'path' => 'required|string'
        ]);

        try {
            $path = $request->input('path');
            $disk = config('backup.disk', 'local');
            
            if (!\Storage::disk($disk)->exists($path)) {
                abort(404, 'Backup file not found');
            }
            
            $filename = basename($path);
            
            return \Storage::disk($disk)->download($path, $filename);
            
        } catch (\Exception $e) {
            abort(500, 'Failed to download backup: ' . $e->getMessage());
        }
    }

    /**
     * Run health check
     */
    public function healthCheck(): JsonResponse
    {
        $result = $this->monitoringService->performHealthCheck();
        
        return response()->json($result);
    }

    /**
     * Get backup statistics
     */
    public function statistics(): JsonResponse
    {
        $result = $this->monitoringService->getBackupStatistics();
        
        return response()->json($result);
    }

    /**
     * Clean up old backups
     */
    public function cleanup(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:database,file',
            'retention_days' => 'required|integer|min:1|max:365'
        ]);

        $type = $request->input('type');
        $retentionDays = $request->integer('retention_days');

        if ($type === 'database') {
            $result = $this->databaseBackupService->cleanupOldBackups($retentionDays);
        } else {
            $result = $this->fileBackupService->cleanupOldBackups($retentionDays);
        }

        return response()->json($result);
    }

    /**
     * Test backup system
     */
    public function test(): JsonResponse
    {
        try {
            $results = [];
            
            // Test database backup creation
            $dbResult = $this->databaseBackupService->createBackup([
                'compress' => true,
                'encrypt' => false
            ]);
            
            $results['database_backup'] = $dbResult['success'];
            
            if ($dbResult['success']) {
                // Test verification
                $verifyResult = $this->databaseBackupService->verifyBackup($dbResult['data']['path']);
                $results['database_verification'] = $verifyResult['success'];
                
                // Clean up test backup
                \Storage::disk(config('backup.disk', 'local'))->delete($dbResult['data']['path']);
            }
            
            // Test file backup creation (small test)
            $fileResult = $this->fileBackupService->createIncrementalBackup([
                'encrypt' => false
            ]);
            
            $results['file_backup'] = $fileResult['success'];
            
            if ($fileResult['success'] && isset($fileResult['data']['path'])) {
                // Test verification
                $verifyResult = $this->fileBackupService->verifyBackup($fileResult['data']['path']);
                $results['file_verification'] = $verifyResult['success'];
                
                // Clean up test backup
                \Storage::disk(config('backup.disk', 'local'))->delete($fileResult['data']['path']);
            }
            
            $allPassed = !in_array(false, $results, true);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'all_tests_passed' => $allPassed,
                    'results' => $results,
                    'tested_at' => now()
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Backup system test failed: ' . $e->getMessage()
            ], 500);
        }
    }
}