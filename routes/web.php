<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\MissionController;
use App\Http\Controllers\BladeMissionController;
use App\Http\Controllers\MissionAssignmentController;
use App\Http\Controllers\MissionStatusController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ChecklistController;
use App\Http\Controllers\DashboardController;

use App\Http\Controllers\PdfController;
use App\Http\Controllers\InstallerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Installer routes
Route::prefix('install')->group(function () {
    Route::get('/', [InstallerController::class, 'index'])->name('installer.index');
    Route::post('/database', [InstallerController::class, 'database'])->name('installer.database');
    Route::post('/install', [InstallerController::class, 'install'])->name('installer.install');
});

// Health check endpoint
Route::get('/api/health', [App\Http\Controllers\CalendarController::class, 'health'])->name('health');

Route::get('/welcome', function () {
    return view('welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => app()->version(),
        'phpVersion' => PHP_VERSION,
    ]);
})->name('welcome');

Route::get('/', [InstallerController::class, 'index']);

// Routes for authenticated users
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Super Admin routes
    // Super Admin routes - redirect to admin (same person)
    Route::middleware(['role:super-admin'])->prefix('super-admin')->name('super-admin.')->group(function () {
        Route::get('/dashboard', function () {
            return redirect()->route('admin.dashboard');
        })->name('dashboard');
        Route::get('/missions', function () {
            return redirect()->route('admin.missions');
        })->name('missions');
        Route::get('/checkers', function () {
            return redirect()->route('admin.checkers');
        })->name('checkers');
        Route::get('/reports', function () {
            return redirect()->route('admin.analytics.data');
        })->name('reports');
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

    // Checker routes
    Route::middleware(['role:checker'])->prefix('checker')->name('checker.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'checkerDashboard'])->name('dashboard');
        Route::get('/missions', [DashboardController::class, 'checkerMissions'])->name('missions');
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

    // Mission routes
    Route::prefix('missions')->group(function () {
        Route::get('/', [MissionController::class, 'index'])->name('missions.index');
        Route::get('/assigned', [MissionController::class, 'getAssignedMissions'])->name('missions.assigned');
        Route::get('/completed', [MissionController::class, 'getCompletedMissions'])->name('missions.completed');
        
        // API route for offline caching
        Route::get('/api/critical', [MissionController::class, 'getCriticalMissions'])->name('missions.api.critical');
        
        Route::middleware(['role:super-admin'])->group(function () {
            Route::get('/create', [MissionController::class, 'create'])->name('missions.create');
            Route::post('/', [MissionController::class, 'store'])->name('missions.store');
            Route::get('/{mission}/edit', [MissionController::class, 'edit'])->name('missions.edit');
            Route::patch('/{mission}', [MissionController::class, 'update'])->name('missions.update');
            Route::delete('/{mission}', [MissionController::class, 'destroy'])->name('missions.destroy');
            Route::patch('/{mission}/assign', [MissionController::class, 'assignAgent'])->name('missions.assign-agent');
        });

        Route::get('/{mission}', [MissionController::class, 'show'])->name('missions.show');
        Route::patch('/{mission}/status', [MissionController::class, 'updateStatus'])->name('missions.update-status');
        Route::patch('/{mission}/start', [MissionController::class, 'startMission'])->name('missions.start');
        Route::patch('/{mission}/refuse', [MissionController::class, 'refuseMission'])->name('missions.refuse');
        
        // Bail Mobilité specific mission routes
        Route::middleware(['ops.access:assign_missions_to_checkers'])->group(function () {
            Route::post('/{mission}/assign-to-checker', [MissionController::class, 'assignToChecker'])->name('missions.assign-to-checker');
            Route::post('/{mission}/validate-bail-mobilite-checklist', [MissionController::class, 'validateBailMobiliteChecklist'])->name('missions.validate-bail-mobilite-checklist');
            Route::get('/ops-assigned', [MissionController::class, 'getOpsAssignedMissions'])->name('missions.ops-assigned');
        });
        
        Route::middleware(['role:checker'])->group(function () {
            Route::post('/{mission}/submit-bail-mobilite-checklist', [MissionController::class, 'submitBailMobiliteChecklist'])->name('missions.submit-bail-mobilite-checklist');
            Route::post('/{mission}/sign-bail-mobilite-contract', [MissionController::class, 'signBailMobiliteContract'])->name('missions.sign-bail-mobilite-contract');
        });
    });

    // Blade Mission routes (new implementation)
    Route::prefix('blade-missions')->name('blade-missions.')->group(function () {
        Route::get('/', [BladeMissionController::class, 'index'])->name('index');
        Route::get('/create', [BladeMissionController::class, 'create'])->name('create');
        Route::post('/', [BladeMissionController::class, 'store'])->name('store');
        Route::get('/{mission}', [BladeMissionController::class, 'show'])->name('show');
        Route::get('/{mission}/edit', [BladeMissionController::class, 'edit'])->name('edit');
        Route::put('/{mission}', [BladeMissionController::class, 'update'])->name('update');
        Route::delete('/{mission}', [BladeMissionController::class, 'destroy'])->name('destroy');
        
        // Additional mission actions
        Route::post('/{mission}/assign', [BladeMissionController::class, 'assignToChecker'])->name('assign');
        Route::post('/{mission}/update-status', [BladeMissionController::class, 'updateStatus'])->name('update-status');
        Route::post('/bulk-update', [BladeMissionController::class, 'bulkUpdate'])->name('bulk-update');
        Route::get('/assigned', [BladeMissionController::class, 'getAssignedMissions'])->name('assigned');
        Route::get('/completed', [BladeMissionController::class, 'getCompletedMissions'])->name('completed');
        Route::get('/calendar', [BladeMissionController::class, 'calendar'])->name('calendar');
        Route::post('/{mission}/update-schedule', [BladeMissionController::class, 'updateSchedule'])->name('update-schedule');
        Route::get('/api/statistics', [BladeMissionController::class, 'getStatistics'])->name('statistics');
    });

    // Mission Assignment routes
    Route::prefix('mission-assignments')->name('mission-assignments.')->group(function () {
        Route::get('/', [MissionAssignmentController::class, 'index'])->name('index');
        Route::post('/assign/{mission}', [MissionAssignmentController::class, 'assignSingle'])->name('assign');
        Route::post('/bulk-assign', [MissionAssignmentController::class, 'bulkAssign'])->name('bulk-assign');
        Route::post('/reassign/{mission}', [MissionAssignmentController::class, 'reassign'])->name('reassign');
        Route::get('/api/agent-availability', [MissionAssignmentController::class, 'getAgentAvailability'])->name('agent-availability');
    });

    // Mission Status Tracking routes
    Route::prefix('mission-status')->name('mission-status.')->group(function () {
        Route::get('/dashboard', [MissionStatusController::class, 'dashboard'])->name('dashboard');
        Route::post('/update/{mission}', [MissionStatusController::class, 'updateStatus'])->name('update');
        Route::get('/updates', [MissionStatusController::class, 'getStatusUpdates'])->name('updates');
        Route::get('/history/{mission}', [MissionStatusController::class, 'getStatusHistory'])->name('history');
    });

    // Checklist routes
    Route::prefix('checklists')->name('checklists.')->group(function () {
        Route::get('/{mission}', [ChecklistController::class, 'show'])->name('show');
        Route::post('/{mission}', [ChecklistController::class, 'store'])->name('store');
        Route::put('/{mission}', [ChecklistController::class, 'update'])->name('update');
        Route::post('/{mission}/rooms', [ChecklistController::class, 'addRoom'])->name('add-room');
        
        // Photo management
        Route::post('/items/{item}/photos', [ChecklistController::class, 'uploadPhoto'])->name('upload-photo');
        Route::delete('/photos/{photo}', [ChecklistController::class, 'deletePhoto'])->name('delete-photo');
        Route::get('/photos/{photo}/url/{size?}', [ChecklistController::class, 'getPhotoUrl'])->name('photo-url');
    });

    // Signature routes
    Route::prefix('signatures')->name('signatures.')->group(function () {
        Route::post('/validate', [\App\Http\Controllers\SignatureController::class, 'validateSignature'])->name('validate-bulk');
        Route::post('/checklist/{checklist}/save', [\App\Http\Controllers\SignatureController::class, 'saveSignature'])->name('save');
        Route::get('/checklist/{checklist}/{type}', [\App\Http\Controllers\SignatureController::class, 'getSignature'])->name('get');
        Route::delete('/checklist/{checklist}/{type}', [\App\Http\Controllers\SignatureController::class, 'deleteSignature'])->name('delete');
        Route::post('/thumbnail', [\App\Http\Controllers\SignatureController::class, 'createThumbnail'])->name('thumbnail');
        Route::post('/checklist/{checklist}/verify', [\App\Http\Controllers\SignatureController::class, 'verifyIntegrity'])->name('verify');
    });

    // Security routes
    Route::prefix('security')->name('security.')->group(function () {
        Route::post('/checklist/{checklist}/encrypt', [\App\Http\Controllers\ChecklistSecurityController::class, 'encryptChecklist'])->name('encrypt');
        Route::post('/checklist/{checklist}/decrypt', [\App\Http\Controllers\ChecklistSecurityController::class, 'decryptChecklist'])->name('decrypt');
        Route::post('/checklist/{checklist}/audit', [\App\Http\Controllers\ChecklistSecurityController::class, 'performSecurityAudit'])->name('audit');
        Route::get('/checklist/{checklist}/report', [\App\Http\Controllers\ChecklistSecurityController::class, 'generateSecurityReport'])->name('report');
        Route::post('/checklist/{checklist}/backup', [\App\Http\Controllers\ChecklistSecurityController::class, 'createSecureBackup'])->name('backup');
        Route::post('/checklist/{checklist}/restore', [\App\Http\Controllers\ChecklistSecurityController::class, 'restoreFromBackup'])->name('restore');
        Route::get('/checklist/{checklist}/integrity', [\App\Http\Controllers\ChecklistSecurityController::class, 'verifyIntegrity'])->name('integrity');
        Route::get('/checklist/{checklist}/status', [\App\Http\Controllers\ChecklistSecurityController::class, 'getSecurityStatus'])->name('status');
    });

    // PDF routes
    Route::prefix('pdf')->name('pdf.')->group(function () {
        Route::get('/mission/{mission}', [PdfController::class, 'generateMissionPdf'])->name('mission');
        Route::get('/checklist/{checklist}', [PdfController::class, 'generateChecklistPdf'])->name('checklist');
        Route::get('/shared/{token}', [PdfController::class, 'sharedChecklistPdf'])->name('shared');
        Route::get('/statistics', [PdfController::class, 'getPdfStatistics'])->name('statistics');
        Route::post('/cleanup', [PdfController::class, 'cleanupOldPdfs'])->name('cleanup');
    });

    // Ops routes for Bail Mobilité management
    Route::middleware(['ops.access'])->prefix('ops')->name('ops.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\OpsController::class, 'dashboard'])->name('dashboard');
        
        // Dashboard API routes
        Route::get('/api/kanban-data', [\App\Http\Controllers\OpsController::class, 'getKanbanData'])->name('api.kanban-data');
        Route::get('/api/bail-mobilites', [\App\Http\Controllers\OpsController::class, 'getBailMobilites'])->name('api.bail-mobilites');
        Route::get('/api/export', [\App\Http\Controllers\OpsController::class, 'exportData'])->name('api.export');
        Route::get('/api/analytics-export', [\App\Http\Controllers\OpsController::class, 'exportAnalytics'])->name('api.analytics-export');
        Route::get('/api/ops-users', [\App\Http\Controllers\OpsController::class, 'getOpsUsers'])->name('api.ops-users');
        Route::get('/api/checkers', [\App\Http\Controllers\OpsController::class, 'getCheckers'])->name('api.checkers');
        
        // Notification routes
        Route::get('/notifications', [\App\Http\Controllers\OpsController::class, 'notifications'])->name('notifications');
        Route::post('/notifications/{notification}/mark-handled', [\App\Http\Controllers\OpsController::class, 'markNotificationAsHandled'])->name('notifications.mark-handled');
        Route::get('/api/notifications/pending', [\App\Http\Controllers\OpsController::class, 'getPendingNotifications'])->name('api.notifications.pending');
        Route::post('/notifications/{notification}/action', [\App\Http\Controllers\OpsController::class, 'handleNotificationAction'])->name('notifications.action');
        
        // Bail Mobilité routes
        Route::resource('bail-mobilites', \App\Http\Controllers\BailMobiliteController::class);
        Route::post('bail-mobilites/{bailMobilite}/assign-entry', [\App\Http\Controllers\BailMobiliteController::class, 'assignEntry'])->name('bail-mobilites.assign-entry');
        Route::post('bail-mobilites/{bailMobilite}/assign-exit', [\App\Http\Controllers\BailMobiliteController::class, 'assignExit'])->name('bail-mobilites.assign-exit');
        Route::post('bail-mobilites/{bailMobilite}/validate-entry', [\App\Http\Controllers\BailMobiliteController::class, 'validateEntry'])->name('bail-mobilites.validate-entry');
        Route::post('bail-mobilites/{bailMobilite}/validate-exit', [\App\Http\Controllers\BailMobiliteController::class, 'validateExit'])->name('bail-mobilites.validate-exit');
        Route::post('bail-mobilites/{bailMobilite}/handle-incident', [\App\Http\Controllers\BailMobiliteController::class, 'handleIncident'])->name('bail-mobilites.handle-incident');
        Route::get('checkers/available', [\App\Http\Controllers\BailMobiliteController::class, 'getAvailableCheckers'])->name('checkers.available');
        
        // Mission validation routes
        Route::get('missions/{mission}/validate', [MissionController::class, 'showValidation'])->name('missions.validate');
        Route::post('missions/{mission}/validate', [MissionController::class, 'validateMission'])->name('missions.validate.submit');
        
        // Incident detection and management routes
        Route::post('bail-mobilites/{bailMobilite}/detect-incidents', [\App\Http\Controllers\BailMobiliteController::class, 'detectIncidents'])->name('bail-mobilites.detect-incidents');
        Route::get('bail-mobilites/{bailMobilite}/incidents', [\App\Http\Controllers\BailMobiliteController::class, 'getIncidents'])->name('bail-mobilites.incidents');
        Route::get('api/incident-stats', [\App\Http\Controllers\BailMobiliteController::class, 'getIncidentStats'])->name('api.incident-stats');
        Route::post('api/run-incident-detection', [\App\Http\Controllers\BailMobiliteController::class, 'runIncidentDetection'])->name('api.run-incident-detection');
        
        // Incident management routes
        Route::resource('incidents', \App\Http\Controllers\IncidentController::class)->only(['index', 'show']);
        Route::patch('incidents/{incident}/status', [\App\Http\Controllers\IncidentController::class, 'updateStatus'])->name('incidents.update-status');
        Route::post('incidents/{incident}/corrective-actions', [\App\Http\Controllers\IncidentController::class, 'createCorrectiveAction'])->name('incidents.create-corrective-action');
        Route::patch('corrective-actions/{correctiveAction}', [\App\Http\Controllers\IncidentController::class, 'updateCorrectiveAction'])->name('corrective-actions.update');
        Route::get('api/incidents/stats', [\App\Http\Controllers\IncidentController::class, 'getStats'])->name('api.incidents.stats');
        Route::get('api/bail-mobilites/{bailMobilite}/incidents', [\App\Http\Controllers\IncidentController::class, 'getIncidentsForBailMobilite'])->name('api.bail-mobilites.incidents');
        Route::post('api/incidents/bulk-update', [\App\Http\Controllers\IncidentController::class, 'bulkUpdate'])->name('api.incidents.bulk-update');
        
        // Calendar routes
        Route::prefix('calendar')->name('calendar.')->group(function () {
            Route::get('/', [\App\Http\Controllers\CalendarController::class, 'index'])->name('index');
            Route::get('/missions', [\App\Http\Controllers\CalendarController::class, 'getMissions'])->name('missions');
            Route::post('/missions', [\App\Http\Controllers\CalendarController::class, 'createMission'])->name('missions.create');
            Route::patch('/missions/{mission}', [\App\Http\Controllers\CalendarController::class, 'updateMission'])->name('missions.update');
            Route::get('/missions/{mission}/details', [\App\Http\Controllers\CalendarController::class, 'getMissionDetails'])->name('missions.details');
            Route::patch('/missions/{mission}/status', [\App\Http\Controllers\CalendarController::class, 'updateMissionStatus'])->name('missions.update-status');
            Route::post('/missions/{mission}/assign', [\App\Http\Controllers\CalendarController::class, 'assignMissionToChecker'])->name('missions.assign');
            Route::delete('/missions/{mission}', [\App\Http\Controllers\CalendarController::class, 'deleteMission'])->name('missions.delete');
            Route::post('/missions/bulk-update', [\App\Http\Controllers\CalendarController::class, 'bulkUpdateMissions'])->name('missions.bulk-update');
            Route::get('/time-slots', [\App\Http\Controllers\CalendarController::class, 'getAvailableTimeSlots'])->name('time-slots');
            Route::post('/conflicts', [\App\Http\Controllers\CalendarController::class, 'detectConflicts'])->name('conflicts');
        });
    });

    // API routes for contract templates (for Ops users)
    Route::middleware(['ops.access:view_contract_templates'])->prefix('api')->name('api.')->group(function () {
        Route::get('/contract-templates/active', [\App\Http\Controllers\ContractTemplateController::class, 'getActiveTemplates'])->name('contract-templates.active');
    });

    // Signature routes
    Route::prefix('signatures')->name('signatures.')->group(function () {
        // Checker routes for creating signatures
        Route::middleware(['role:checker'])->group(function () {
            Route::post('/bail-mobilites/{bailMobilite}/sign', [\App\Http\Controllers\SignatureController::class, 'createTenantSignature'])->name('create-tenant');
        });
        
        // Ops and Admin routes for viewing signatures
        Route::middleware(['ops.access:view_signatures'])->group(function () {
            Route::get('/{signature}', [\App\Http\Controllers\SignatureController::class, 'getSignature'])->name('show');
            Route::get('/{signature}/download', [\App\Http\Controllers\SignatureController::class, 'downloadContract'])->name('download');
            Route::get('/{signature}/preview', [\App\Http\Controllers\SignatureController::class, 'previewContract'])->name('preview');
            Route::get('/{signature}/validate', [\App\Http\Controllers\SignatureController::class, 'validateSignature'])->name('validate-single');
            Route::get('/bail-mobilites/{bailMobilite}/signatures', [\App\Http\Controllers\SignatureController::class, 'getBailMobiliteSignatures'])->name('bail-mobilite');
            Route::post('/bail-mobilites/{bailMobilite}/archive', [\App\Http\Controllers\SignatureController::class, 'archiveSignatures'])->name('archive');

            // Multi-party signature workflow routes
            Route::prefix('workflow')->name('workflow.')->group(function () {
                Route::get('/{signature}/status', [\App\Http\Controllers\SignatureWorkflowController::class, 'showWorkflowStatus'])->name('status');
                Route::get('/{signature}/status/api', [\App\Http\Controllers\SignatureWorkflowController::class, 'getWorkflowStatusApi'])->name('status.api');
                Route::post('/{signature}/initialize', [\App\Http\Controllers\SignatureWorkflowController::class, 'initializeWorkflow'])->name('initialize');
                Route::post('/invitations/{invitation}/resend', [\App\Http\Controllers\SignatureWorkflowController::class, 'resendInvitation'])->name('resend');
                Route::post('/invitations/{invitation}/cancel', [\App\Http\Controllers\SignatureWorkflowController::class, 'cancelInvitation'])->name('cancel');
                Route::get('/{signature}/download-contract', [\App\Http\Controllers\SignatureWorkflowController::class, 'downloadContract'])->name('download-contract');
            });
        });

        // Public invitation routes (no auth required)
        Route::prefix('invitations')->name('invitations.')->group(function () {
            Route::get('/{token}', [\App\Http\Controllers\SignatureWorkflowController::class, 'showInvitation'])->name('show');
            Route::post('/{token}/process', [\App\Http\Controllers\SignatureWorkflowController::class, 'processSignature'])->name('process');
            Route::get('/{invitation}/completed', [\App\Http\Controllers\SignatureWorkflowController::class, 'showCompletion'])->name('completed');
        });
    });

    // Admin routes (authenticated)
    Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/analytics/data', [\App\Http\Controllers\Admin\AnalyticsController::class, 'index'])->name('analytics.data');
        Route::get('/checkers', [\App\Http\Controllers\Admin\AnalyticsController::class, 'checkers'])->name('checkers');
        
        // Role Management routes
        Route::get('/role-management', [\App\Http\Controllers\RoleManagementController::class, 'index'])->name('role-management');
        Route::post('/roles', [\App\Http\Controllers\RoleManagementController::class, 'storeRole'])->name('roles.store');
        Route::put('/roles/{role}', [\App\Http\Controllers\RoleManagementController::class, 'updateRole'])->name('roles.update');
        Route::delete('/roles/{role}', [\App\Http\Controllers\RoleManagementController::class, 'destroyRole'])->name('roles.destroy');
        Route::post('/users', [\App\Http\Controllers\RoleManagementController::class, 'storeUser'])->name('users.store');
        Route::put('/users/{user}', [\App\Http\Controllers\RoleManagementController::class, 'updateUser'])->name('users.update');
        Route::post('/users/{user}/assign-role', [\App\Http\Controllers\RoleManagementController::class, 'assignRole'])->name('users.assign-role');
        Route::post('/users/{user}/reset-password', [\App\Http\Controllers\RoleManagementController::class, 'resetPassword'])->name('users.reset-password');
        Route::get('/role-stats', [\App\Http\Controllers\RoleManagementController::class, 'getRoleStats'])->name('role-stats');
        
        // Contract Templates routes
        Route::resource('contract-templates', \App\Http\Controllers\ContractTemplateController::class);
        Route::post('contract-templates/{contractTemplate}/sign', [\App\Http\Controllers\ContractTemplateController::class, 'signTemplate'])->name('contract-templates.sign');
        Route::patch('contract-templates/{contractTemplate}/toggle-active', [\App\Http\Controllers\ContractTemplateController::class, 'toggleActive'])->name('contract-templates.toggle-active');
        Route::get('contract-templates/{contractTemplate}/preview', [\App\Http\Controllers\ContractTemplateController::class, 'preview'])->name('contract-templates.preview');
        Route::get('contract-templates/{contractTemplate}/validate', [\App\Http\Controllers\ContractTemplateController::class, 'validateTemplate'])->name('contract-templates.validate');
        Route::get('contract-templates/placeholders', [\App\Http\Controllers\ContractTemplateController::class, 'getPlaceholders'])->name('contract-templates.placeholders');
        Route::post('contract-templates/{contractTemplate}/create-version', [\App\Http\Controllers\ContractTemplateController::class, 'createVersion'])->name('contract-templates.create-version');
    });
});

// Google OAuth routes
Route::get('/auth/google', [GoogleController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleController::class, 'handleGoogleCallback']);

// Super Admin authentication
Route::prefix('super-admin')->name('super-admin.')->group(function () {
    Route::get('login', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'store']);
    Route::post('logout', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

require __DIR__.'/auth.php';

Route::get('/shared/checklist/pdf/{token}', [App\Http\Controllers\PdfController::class, 'sharedChecklistPdf'])
    ->name('shared.checklist.pdf')
    ->middleware('signed');

// Admin authentication and dashboard
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'store']);
    Route::post('logout', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'destroy'])->name('logout');
    
    Route::middleware(['auth', 'role:admin|super-admin'])->group(function () {
        Route::get('dashboard', [\App\Http\Controllers\DashboardController::class, 'adminDashboard'])->name('dashboard');
        Route::get('missions', [MissionController::class, 'index'])->name('missions');
        Route::get('missions/assigned', [MissionController::class, 'getAssignedMissions'])->name('missions.assigned');
        Route::get('missions/completed', [MissionController::class, 'getCompletedMissions'])->name('missions.completed');
        Route::get('checkers', [\App\Http\Controllers\DashboardController::class, 'checkers'])->name('checkers');
        Route::post('checkers', [\App\Http\Controllers\DashboardController::class, 'storeChecker'])->name('checkers.store');
        Route::put('checkers/{checker}', [\App\Http\Controllers\DashboardController::class, 'updateChecker'])->name('checkers.update');
        Route::patch('checkers/{checker}/toggle-status', [\App\Http\Controllers\DashboardController::class, 'toggleCheckerStatus'])->name('checkers.toggle-status');
        Route::get('analytics/data', [\App\Http\Controllers\DashboardController::class, 'reports'])->name('analytics.data');
        Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    });

    // Export and Reporting routes
    Route::middleware(['auth'])->group(function () {
        Route::prefix('api/export')->name('api.export.')->group(function () {
            Route::get('/bail-mobilites', [\App\Http\Controllers\ExportController::class, 'exportBailMobilites'])->name('bail-mobilites');
            Route::get('/missions', [\App\Http\Controllers\ExportController::class, 'exportMissions'])->name('missions');
            Route::get('/checklists', [\App\Http\Controllers\ExportController::class, 'exportChecklists'])->name('checklists');
            Route::get('/incidents', [\App\Http\Controllers\ExportController::class, 'exportIncidents'])->name('incidents');
            Route::get('/audit-trail', [\App\Http\Controllers\ExportController::class, 'exportAuditTrail'])->name('audit-trail');
            Route::get('/analytics', [\App\Http\Controllers\ExportController::class, 'exportAnalytics'])->name('analytics');
        });

        Route::prefix('api/reports')->name('api.reports.')->group(function () {
            Route::get('/mission/{mission}', [\App\Http\Controllers\ReportController::class, 'missionReport'])->name('mission');
            Route::get('/checklist/{checklist}', [\App\Http\Controllers\ReportController::class, 'checklistReport'])->name('checklist');
            Route::get('/bail-mobilite/{bailMobilite}', [\App\Http\Controllers\ReportController::class, 'bailMobiliteReport'])->name('bail-mobilite');
            Route::get('/performance', [\App\Http\Controllers\ReportController::class, 'performanceReport'])->name('performance');
            Route::get('/incidents', [\App\Http\Controllers\ReportController::class, 'incidentReport'])->name('incidents');
            Route::get('/analytics', [\App\Http\Controllers\ReportController::class, 'analyticsReport'])->name('analytics');
            Route::get('/contracts', [\App\Http\Controllers\ReportController::class, 'contractReport'])->name('contracts');
            Route::get('/audit-trail', [\App\Http\Controllers\ReportController::class, 'auditTrailReport'])->name('audit-trail');
        });

        Route::get('/api/audit-trail', [\App\Http\Controllers\AuditController::class, 'index'])->name('api.audit-trail');
        Route::delete('profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });
});

// Checker authentication and dashboard
Route::prefix('checker')->name('checker.')->group(function () {
    Route::get('login', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'store']);
    Route::post('logout', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'destroy'])->name('logout');
    
    Route::middleware(['auth', 'role:checker'])->group(function () {
        Route::get('dashboard', [\App\Http\Controllers\DashboardController::class, 'checkerDashboard'])->name('dashboard');
        Route::get('missions', [MissionController::class, 'getAssignedMissions'])->name('missions');
        Route::get('missions/completed', [MissionController::class, 'getCompletedMissions'])->name('missions.completed');
    });
});

// Ops authentication and dashboard
Route::prefix('ops')->name('ops.')->group(function () {
    Route::get('login', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'store']);
    Route::post('logout', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'destroy'])->name('logout');
    
    Route::middleware(['auth', 'role:ops'])->group(function () {
        Route::get('dashboard', [\App\Http\Controllers\OpsController::class, 'dashboard'])->name('dashboard');
    });
});
