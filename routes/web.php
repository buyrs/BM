<?php

use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\Auth\OpsLoginController;
use App\Http\Controllers\Auth\CheckerLoginController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\SecureFileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ChecklistController;
use App\Http\Controllers\GuestChecklistController;
use App\Http\Controllers\AdminChecklistController;
use App\Http\Controllers\MissionController;
use App\Http\Controllers\AmenityTypeController;
use App\Http\Controllers\AmenityController;
use App\Http\Controllers\Admin\PropertyController as AdminPropertyController;
use App\Http\Controllers\Ops\PropertyController as OpsPropertyController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Public Dashboard Route (fixes RouteNotFoundException)
Route::get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard');

// Admin Routes
Route::get('/admin/login', [AdminLoginController::class, 'create'])->name('admin.login');
Route::post('/admin/login', [AdminLoginController::class, 'store']);
Route::middleware('auth:admin')->group(function () {
    Route::get('/admin/dashboard', function () {
        return view('admin.dashboard');
    })->name('admin.dashboard');
    Route::post('/admin/logout', [AdminLoginController::class, 'destroy'])->name('admin.logout');

    // Admin User Management
    Route::resource('/admin/users', UserController::class)->names('admin.users');

    // Admin Checklist Management
    Route::post('/admin/checklists/{checklist}/send-to-guest', [AdminChecklistController::class, 'sendToGuest'])->name('admin.checklists.sendToGuest');

    // Admin Mission Management
    Route::resource('/admin/missions', MissionController::class)->names('admin.missions');
    Route::post('/admin/missions/{mission}/approve', [MissionController::class, 'approve'])->name('admin.missions.approve');

    // Admin Amenity Type Management
    Route::resource('/admin/amenity-types', AmenityTypeController::class)->names('admin.amenity_types');

    // Admin Amenity Management
    Route::resource('/admin/amenities', AmenityController::class)->names('admin.amenities');
    
    // Admin Property Management
    Route::resource('/admin/properties', AdminPropertyController::class)->names('admin.properties');
    Route::get('/admin/properties-upload', [AdminPropertyController::class, 'uploadForm'])->name('admin.properties.upload.form');
    Route::post('/admin/properties-upload', [AdminPropertyController::class, 'upload'])->name('admin.properties.upload');
    Route::get('/admin/properties-template', [AdminPropertyController::class, 'template'])->name('admin.properties.template');
    
    // Admin Audit Log Management
    Route::get('/admin/audit-logs', [\App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('admin.audit-logs.index');
    
    // Admin Performance Monitoring
    Route::get('/admin/performance', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'index'])->name('admin.performance.index');
    Route::get('/admin/performance/health', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'health'])->name('admin.performance.health');
    Route::get('/admin/performance/metrics', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'metrics'])->name('admin.performance.metrics');
    Route::get('/admin/performance/database-analysis', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'databaseAnalysis'])->name('admin.performance.database-analysis');
    Route::delete('/admin/performance/metrics', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'clearMetrics'])->name('admin.performance.clear-metrics');
    Route::get('/admin/performance/export', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'exportReport'])->name('admin.performance.export');
    Route::get('/admin/audit-logs/statistics', [\App\Http\Controllers\Admin\AuditLogController::class, 'statistics'])->name('admin.audit-logs.statistics');
    Route::get('/admin/audit-logs/suspicious', [\App\Http\Controllers\Admin\AuditLogController::class, 'suspicious'])->name('admin.audit-logs.suspicious');
    Route::get('/admin/audit-logs/export', [\App\Http\Controllers\Admin\AuditLogController::class, 'export'])->name('admin.audit-logs.export');
    Route::post('/admin/audit-logs/cleanup', [\App\Http\Controllers\Admin\AuditLogController::class, 'cleanup'])->name('admin.audit-logs.cleanup');
    Route::get('/admin/audit-logs/{auditLog}', [\App\Http\Controllers\Admin\AuditLogController::class, 'show'])->name('admin.audit-logs.show');
    
    // Admin Reports
    Route::get('/admin/reports', [\App\Http\Controllers\Admin\ReportController::class, 'index'])->name('admin.reports.index');
    Route::get('/admin/reports/list', [\App\Http\Controllers\Admin\ReportController::class, 'getReports'])->name('admin.reports.list');
    Route::get('/admin/reports/types', [\App\Http\Controllers\Admin\ReportController::class, 'getReportTypes'])->name('admin.reports.types');
    Route::post('/admin/reports/generate/analytics', [\App\Http\Controllers\Admin\ReportController::class, 'generateAnalyticsReport'])->name('admin.reports.generate.analytics');
    Route::post('/admin/reports/generate/missions', [\App\Http\Controllers\Admin\ReportController::class, 'generateMissionReport'])->name('admin.reports.generate.missions');
    Route::post('/admin/reports/generate/user_performance', [\App\Http\Controllers\Admin\ReportController::class, 'generateUserPerformanceReport'])->name('admin.reports.generate.user_performance');
    Route::post('/admin/reports/generate/maintenance', [\App\Http\Controllers\Admin\ReportController::class, 'generateMaintenanceReport'])->name('admin.reports.generate.maintenance');
    Route::get('/admin/reports/download/{filename}', [\App\Http\Controllers\Admin\ReportController::class, 'downloadReport'])->name('admin.reports.download');
    Route::delete('/admin/reports/{filename}', [\App\Http\Controllers\Admin\ReportController::class, 'deleteReport'])->name('admin.reports.delete');
    
    // Admin Analytics Dashboard
    Route::get('/admin/analytics', [\App\Http\Controllers\Admin\AnalyticsDashboardController::class, 'index'])->name('admin.analytics.dashboard');
    Route::get('/admin/analytics/dashboard-data', [\App\Http\Controllers\Admin\AnalyticsDashboardController::class, 'getDashboardData'])->name('admin.analytics.dashboard-data');
    Route::get('/admin/analytics/mission-metrics', [\App\Http\Controllers\Admin\AnalyticsDashboardController::class, 'getMissionMetrics'])->name('admin.analytics.mission-metrics');
    Route::get('/admin/analytics/user-performance', [\App\Http\Controllers\Admin\AnalyticsDashboardController::class, 'getUserPerformance'])->name('admin.analytics.user-performance');
    Route::get('/admin/analytics/property-metrics', [\App\Http\Controllers\Admin\AnalyticsDashboardController::class, 'getPropertyMetrics'])->name('admin.analytics.property-metrics');
    Route::get('/admin/analytics/maintenance-metrics', [\App\Http\Controllers\Admin\AnalyticsDashboardController::class, 'getMaintenanceMetrics'])->name('admin.analytics.maintenance-metrics');
    Route::get('/admin/analytics/system-metrics', [\App\Http\Controllers\Admin\AnalyticsDashboardController::class, 'getSystemMetrics'])->name('admin.analytics.system-metrics');
    Route::get('/admin/analytics/trending-data', [\App\Http\Controllers\Admin\AnalyticsDashboardController::class, 'getTrendingData'])->name('admin.analytics.trending-data');
    Route::get('/admin/analytics/date-ranges', [\App\Http\Controllers\Admin\AnalyticsDashboardController::class, 'getDateRanges'])->name('admin.analytics.date-ranges');
    Route::post('/admin/analytics/clear-cache', [\App\Http\Controllers\Admin\AnalyticsDashboardController::class, 'clearCache'])->name('admin.analytics.clear-cache');
    
    // Admin Backup Management
    Route::get('/admin/backups', [\App\Http\Controllers\Admin\BackupController::class, 'index'])->name('admin.backups.index');
    Route::get('/admin/backups/dashboard', [\App\Http\Controllers\Admin\BackupController::class, 'dashboard'])->name('admin.backups.dashboard');
    Route::get('/admin/backups/database', [\App\Http\Controllers\Admin\BackupController::class, 'listDatabaseBackups'])->name('admin.backups.database.list');
    Route::get('/admin/backups/files', [\App\Http\Controllers\Admin\BackupController::class, 'listFileBackups'])->name('admin.backups.files.list');
    Route::post('/admin/backups/database', [\App\Http\Controllers\Admin\BackupController::class, 'createDatabaseBackup'])->name('admin.backups.database.create');
    Route::post('/admin/backups/files', [\App\Http\Controllers\Admin\BackupController::class, 'createFileBackup'])->name('admin.backups.files.create');
    Route::post('/admin/backups/verify', [\App\Http\Controllers\Admin\BackupController::class, 'verifyBackup'])->name('admin.backups.verify');
    Route::delete('/admin/backups/delete', [\App\Http\Controllers\Admin\BackupController::class, 'deleteBackup'])->name('admin.backups.delete');
    Route::get('/admin/backups/download', [\App\Http\Controllers\Admin\BackupController::class, 'downloadBackup'])->name('admin.backups.download');
    Route::get('/admin/backups/health-check', [\App\Http\Controllers\Admin\BackupController::class, 'healthCheck'])->name('admin.backups.health-check');
    Route::get('/admin/backups/statistics', [\App\Http\Controllers\Admin\BackupController::class, 'statistics'])->name('admin.backups.statistics');
    Route::post('/admin/backups/cleanup', [\App\Http\Controllers\Admin\BackupController::class, 'cleanup'])->name('admin.backups.cleanup');
    Route::post('/admin/backups/test', [\App\Http\Controllers\Admin\BackupController::class, 'test'])->name('admin.backups.test');
    
    // Admin Bulk Operations
    Route::get('/admin/bulk-operations', [\App\Http\Controllers\Admin\BulkOperationsController::class, 'index'])->name('admin.bulk-operations.index');
    Route::post('/admin/bulk-operations/missions/assign', [\App\Http\Controllers\Admin\BulkOperationsController::class, 'bulkAssignMissions'])->name('admin.bulk-operations.missions.assign');
    Route::post('/admin/bulk-operations/missions/status', [\App\Http\Controllers\Admin\BulkOperationsController::class, 'bulkUpdateMissionStatus'])->name('admin.bulk-operations.missions.status');
    Route::post('/admin/bulk-operations/users', [\App\Http\Controllers\Admin\BulkOperationsController::class, 'bulkUpdateUsers'])->name('admin.bulk-operations.users');
    Route::post('/admin/bulk-operations/properties/import', [\App\Http\Controllers\Admin\BulkOperationsController::class, 'bulkImportProperties'])->name('admin.bulk-operations.properties.import');
    Route::post('/admin/bulk-operations/properties/delete', [\App\Http\Controllers\Admin\BulkOperationsController::class, 'bulkDeleteProperties'])->name('admin.bulk-operations.properties.delete');
    Route::post('/admin/bulk-operations/notifications', [\App\Http\Controllers\Admin\BulkOperationsController::class, 'bulkSendNotifications'])->name('admin.bulk-operations.notifications');
    Route::get('/admin/bulk-operations/stats', [\App\Http\Controllers\Admin\BulkOperationsController::class, 'getStats'])->name('admin.bulk-operations.stats');
    
    // Admin Advanced Search
    Route::get('/admin/advanced-search', [\App\Http\Controllers\Admin\AdvancedSearchController::class, 'index'])->name('admin.advanced-search.index');
    Route::post('/admin/advanced-search/missions', [\App\Http\Controllers\Admin\AdvancedSearchController::class, 'searchMissions'])->name('admin.advanced-search.missions');
    Route::post('/admin/advanced-search/users', [\App\Http\Controllers\Admin\AdvancedSearchController::class, 'searchUsers'])->name('admin.advanced-search.users');
    Route::post('/admin/advanced-search/properties', [\App\Http\Controllers\Admin\AdvancedSearchController::class, 'searchProperties'])->name('admin.advanced-search.properties');
    Route::post('/admin/advanced-search/global', [\App\Http\Controllers\Admin\AdvancedSearchController::class, 'globalSearch'])->name('admin.advanced-search.global');
    Route::post('/admin/advanced-search/save', [\App\Http\Controllers\Admin\AdvancedSearchController::class, 'saveSearch'])->name('admin.advanced-search.save');
    Route::get('/admin/advanced-search/saved', [\App\Http\Controllers\Admin\AdvancedSearchController::class, 'getSavedSearches'])->name('admin.advanced-search.saved');
    Route::delete('/admin/advanced-search/saved/delete', [\App\Http\Controllers\Admin\AdvancedSearchController::class, 'deleteSavedSearch'])->name('admin.advanced-search.saved.delete');
    Route::post('/admin/advanced-search/export', [\App\Http\Controllers\Admin\AdvancedSearchController::class, 'exportResults'])->name('admin.advanced-search.export');
    Route::get('/admin/advanced-search/download/{filename}', [\App\Http\Controllers\Admin\AdvancedSearchController::class, 'downloadExport'])->name('admin.advanced-search.download');
    Route::get('/admin/advanced-search/analytics', [\App\Http\Controllers\Admin\AdvancedSearchController::class, 'getAnalytics'])->name('admin.advanced-search.analytics');
    Route::get('/admin/advanced-search/suggestions', [\App\Http\Controllers\Admin\AdvancedSearchController::class, 'getSuggestions'])->name('admin.advanced-search.suggestions');
    
    // Admin File Management
    Route::get('/admin/file-manager', [\App\Http\Controllers\FileManagerController::class, 'index'])->name('admin.file-manager.index');
    Route::get('/admin/file-manager/files', [\App\Http\Controllers\FileManagerController::class, 'getFiles'])->name('admin.file-manager.files');
    Route::get('/admin/file-manager/files/{fileMetadata}', [\App\Http\Controllers\FileManagerController::class, 'show'])->name('admin.file-manager.files.show');
    Route::delete('/admin/file-manager/files/{fileMetadata}', [\App\Http\Controllers\FileUploadController::class, 'destroy'])->name('admin.file-manager.files.destroy');
    Route::post('/admin/file-manager/upload', [\App\Http\Controllers\FileUploadController::class, 'upload'])->name('admin.file-manager.upload');
    Route::get('/admin/file-manager/download/{fileMetadata}', [\App\Http\Controllers\FileUploadController::class, 'download'])->name('admin.file-manager.download');
    Route::post('/admin/file-manager/bulk-delete', [\App\Http\Controllers\FileManagerController::class, 'bulkDelete'])->name('admin.file-manager.bulk-delete');
    Route::post('/admin/file-manager/bulk-move', [\App\Http\Controllers\FileManagerController::class, 'bulkMove'])->name('admin.file-manager.bulk-move');
    Route::get('/admin/file-manager/search', [\App\Http\Controllers\FileManagerController::class, 'search'])->name('admin.file-manager.search');
    Route::get('/admin/file-manager/stats', [\App\Http\Controllers\FileManagerController::class, 'getStats'])->name('admin.file-manager.stats');
    Route::get('/admin/file-manager/export', [\App\Http\Controllers\FileManagerController::class, 'export'])->name('admin.file-manager.export');
    
    // Image Optimization Routes
    Route::post('/admin/images/{fileMetadata}/thumbnails', [\App\Http\Controllers\ImageOptimizationController::class, 'generateThumbnails'])->name('admin.images.thumbnails');
    Route::post('/admin/images/{fileMetadata}/metadata', [\App\Http\Controllers\ImageOptimizationController::class, 'extractMetadata'])->name('admin.images.metadata');
    Route::post('/admin/images/{fileMetadata}/convert', [\App\Http\Controllers\ImageOptimizationController::class, 'convertFormat'])->name('admin.images.convert');
    Route::post('/admin/images/batch-optimize', [\App\Http\Controllers\ImageOptimizationController::class, 'batchOptimize'])->name('admin.images.batch-optimize');
    Route::get('/admin/images/{fileMetadata}/thumbnail/{size?}', [\App\Http\Controllers\ImageOptimizationController::class, 'getThumbnail'])->name('admin.images.thumbnail');
    Route::delete('/admin/images/{fileMetadata}/thumbnails', [\App\Http\Controllers\ImageOptimizationController::class, 'cleanupThumbnails'])->name('admin.images.cleanup-thumbnails');
    
    // Debug route
    Route::get('/admin/debug-properties', function() {
        $user = auth('admin')->user();
        $properties = \App\Models\Property::all();
        return response()->json([
            'authenticated' => auth('admin')->check(),
            'user' => $user ? $user->only(['id', 'name', 'email', 'role']) : null,
            'properties_count' => $properties->count(),
            'first_property' => $properties->first() ? $properties->first()->only(['id', 'property_address', 'owner_name']) : null,
        ]);
    })->name('admin.debug.properties');
});

// Ops Routes
Route::get('/ops/login', [OpsLoginController::class, 'create'])->name('ops.login');
Route::post('/ops/login', [OpsLoginController::class, 'store']);
Route::middleware('auth:ops')->group(function () {
    Route::get('/ops/dashboard', function () {
        return view('ops.dashboard');
    })->name('ops.dashboard');
    Route::post('/ops/logout', [OpsLoginController::class, 'destroy'])->name('ops.logout');

    // Ops User Management (only for Checkers)
    Route::resource('/ops/users', UserController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])->names('ops.users');

    // Ops Mission Management
    Route::resource('/ops/missions', MissionController::class)->names('ops.missions');
    
    // Ops Property Management
    Route::resource('/ops/properties', OpsPropertyController::class)->only(['index', 'show', 'create', 'store', 'edit', 'update'])->names('ops.properties');
    Route::get('/ops/properties-upload', [OpsPropertyController::class, 'uploadForm'])->name('ops.properties.upload.form');
    Route::post('/ops/properties-upload', [OpsPropertyController::class, 'upload'])->name('ops.properties.upload');
    Route::get('/ops/properties-template', [OpsPropertyController::class, 'template'])->name('ops.properties.template');
    
    // Ops Maintenance Request Management
    Route::get('/ops/maintenance-requests', [\App\Http\Controllers\Ops\MaintenanceRequestController::class, 'index'])->name('ops.maintenance-requests.index');
    Route::get('/ops/maintenance-requests/{maintenanceRequest}', [\App\Http\Controllers\Ops\MaintenanceRequestController::class, 'show'])->name('ops.maintenance-requests.show');
    Route::post('/ops/maintenance-requests/{maintenanceRequest}/approve', [\App\Http\Controllers\Ops\MaintenanceRequestController::class, 'approve'])->name('ops.maintenance-requests.approve');
    Route::post('/ops/maintenance-requests/{maintenanceRequest}/reject', [\App\Http\Controllers\Ops\MaintenanceRequestController::class, 'reject'])->name('ops.maintenance-requests.reject');
    Route::post('/ops/maintenance-requests/{maintenanceRequest}/start-work', [\App\Http\Controllers\Ops\MaintenanceRequestController::class, 'startWork'])->name('ops.maintenance-requests.start-work');
    Route::post('/ops/maintenance-requests/{maintenanceRequest}/complete', [\App\Http\Controllers\Ops\MaintenanceRequestController::class, 'complete'])->name('ops.maintenance-requests.complete');
    Route::post('/ops/maintenance-requests/{maintenanceRequest}/update-assignment', [\App\Http\Controllers\Ops\MaintenanceRequestController::class, 'updateAssignment'])->name('ops.maintenance-requests.update-assignment');
});

// Checker Routes
Route::get('/checker/login', [CheckerLoginController::class, 'create'])->name('checker.login');
Route::post('/checker/login', [CheckerLoginController::class, 'store']);
Route::middleware('auth:checker')->group(function () {
    Route::get('/checker/dashboard', [CheckerLoginController::class, 'dashboard'])->name('checker.dashboard');
    Route::post('/checker/logout', [CheckerLoginController::class, 'destroy'])->name('checker.logout');

    // Checklist Management
    Route::get('/checklists/{checklist}', [ChecklistController::class, 'show'])->name('checklists.show');
    Route::put('/checklists/{checklist}', [ChecklistController::class, 'update'])->name('checklists.update');
    Route::post('/checklists/{checklist}/submit', [ChecklistController::class, 'submit'])->name('checklists.submit');
});

// Guest Checklist Access
Route::get('/guest/checklists/{checklist}/{token}', [GuestChecklistController::class, 'show'])->name('guest.checklists.show');

// Two-Factor Authentication Routes
Route::middleware('auth')->group(function () {
    Route::get('/two-factor', [TwoFactorController::class, 'show'])->name('two-factor.show');
    Route::post('/two-factor', [TwoFactorController::class, 'store'])->name('two-factor.store');
    Route::delete('/two-factor', [TwoFactorController::class, 'destroy'])->name('two-factor.destroy');
    Route::post('/two-factor/recovery-codes', [TwoFactorController::class, 'generateRecoveryCodes'])->name('two-factor.recovery-codes');
});

Route::get('/two-factor/challenge', [TwoFactorChallengeController::class, 'create'])->name('two-factor.login');
Route::post('/two-factor/challenge', [TwoFactorChallengeController::class, 'store']);

// Secure File Upload Routes
Route::middleware('auth')->group(function () {
    Route::post('/files/upload', [SecureFileController::class, 'upload'])->name('files.upload');
    Route::get('/files/download/{path}', [SecureFileController::class, 'download'])->name('files.download')->where('path', '.*');
    Route::get('/files/info/{path}', [SecureFileController::class, 'info'])->name('files.info')->where('path', '.*');
    Route::delete('/files/{path}', [SecureFileController::class, 'delete'])->name('files.delete')->where('path', '.*');
    Route::get('/files', [SecureFileController::class, 'list'])->name('files.list');
    Route::get('/files/allowed-types/{category?}', [SecureFileController::class, 'allowedTypes'])->name('files.allowed-types');
});

// Notification Routes
Route::middleware('auth')->group(function () {
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/api/notifications', [\App\Http\Controllers\NotificationController::class, 'getNotifications'])->name('api.notifications.get');
    Route::post('/notifications/{notification}/mark-as-read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.mark-as-read');
    Route::post('/notifications/mark-multiple-read', [\App\Http\Controllers\NotificationController::class, 'markMultipleAsRead'])->name('notifications.mark-multiple-read');
    Route::post('/notifications/mark-all-read', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::post('/notifications/{notification}/mark-action-taken', [\App\Http\Controllers\NotificationController::class, 'markActionTaken'])->name('notifications.mark-action-taken');
    Route::get('/notifications/counts', [\App\Http\Controllers\NotificationController::class, 'getCounts'])->name('notifications.counts');
});

// PWA Routes
Route::get('/manifest.json', [\App\Http\Controllers\PWAController::class, 'manifest'])->name('pwa.manifest');
Route::get('/sw.js', [\App\Http\Controllers\PWAController::class, 'serviceWorker'])->name('pwa.sw');
Route::get('/offline', [\App\Http\Controllers\PWAController::class, 'offline'])->name('pwa.offline');

// Health check routes
Route::get('/health', [\App\Http\Controllers\HealthCheckController::class, 'health'])->name('health');
Route::get('/health/detailed', [\App\Http\Controllers\HealthCheckController::class, 'detailed'])->name('health.detailed');
Route::get('/health/ready', [\App\Http\Controllers\HealthCheckController::class, 'ready'])->name('health.ready');
Route::get('/health/live', [\App\Http\Controllers\HealthCheckController::class, 'live'])->name('health.live');
Route::get('/metrics', [\App\Http\Controllers\HealthCheckController::class, 'metrics'])->name('metrics');
Route::get('/info', [\App\Http\Controllers\HealthCheckController::class, 'info'])->name('info');

// Ping route for connectivity testing
Route::match(['GET', 'HEAD'], '/ping', function() {
    return response('', 200);
})->name('ping');
