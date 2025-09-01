<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\MissionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ChecklistController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\InstallerController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => app()->version(),
        'phpVersion' => PHP_VERSION,
    ]);
});

// Routes for authenticated users
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Super Admin routes
    Route::middleware(['role:super-admin'])->prefix('super-admin')->name('super-admin.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'superAdminDashboard'])->name('dashboard');
        Route::get('/missions', [DashboardController::class, 'missions'])->name('missions');
        Route::get('/checkers', [DashboardController::class, 'checkers'])->name('checkers');
        Route::get('/reports', [DashboardController::class, 'reports'])->name('reports');
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

    // Checklist routes
    Route::prefix('checklists')->name('checklists.')->group(function () {
        Route::get('/{mission}', [ChecklistController::class, 'show'])->name('show');
        Route::post('/{mission}', [ChecklistController::class, 'store'])->name('store');
        Route::put('/{mission}', [ChecklistController::class, 'update'])->name('update');
    });

    // PDF routes
    Route::prefix('pdf')->name('pdf.')->group(function () {
        Route::get('/mission/{mission}', [PdfController::class, 'mission'])->name('mission');
        Route::get('/checklist/{checklist}', [PdfController::class, 'checklist'])->name('checklist');
    });

    // Ops routes for Bail Mobilité management
    Route::middleware(['ops.access'])->prefix('ops')->name('ops.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\OpsController::class, 'dashboard'])->name('dashboard');
        
        // Dashboard API routes
        Route::get('/api/kanban-data', [\App\Http\Controllers\OpsController::class, 'getKanbanData'])->name('api.kanban-data');
        Route::get('/api/bail-mobilites', [\App\Http\Controllers\OpsController::class, 'getBailMobilites'])->name('api.bail-mobilites');
        Route::get('/api/export', [\App\Http\Controllers\OpsController::class, 'exportData'])->name('api.export');
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
            Route::get('/{signature}/validate', [\App\Http\Controllers\SignatureController::class, 'validateSignature'])->name('validate');
            Route::get('/bail-mobilites/{bailMobilite}/signatures', [\App\Http\Controllers\SignatureController::class, 'getBailMobiliteSignatures'])->name('bail-mobilite');
            Route::post('/bail-mobilites/{bailMobilite}/archive', [\App\Http\Controllers\SignatureController::class, 'archiveSignatures'])->name('archive');
        });
    });

    // Admin routes
    Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'adminDashboard'])->name('dashboard');
        Route::get('/analytics/data', [\App\Http\Controllers\Admin\AnalyticsController::class, 'index'])->name('analytics.data');
        Route::get('/checkers', [\App\Http\Controllers\Admin\AnalyticsController::class, 'checkers'])->name('checkers');
        
        // Contract Templates routes
        Route::resource('contract-templates', \App\Http\Controllers\ContractTemplateController::class);
        Route::post('contract-templates/{contractTemplate}/sign', [\App\Http\Controllers\ContractTemplateController::class, 'signTemplate'])->name('contract-templates.sign');
        Route::patch('contract-templates/{contractTemplate}/toggle-active', [\App\Http\Controllers\ContractTemplateController::class, 'toggleActive'])->name('contract-templates.toggle-active');
        Route::get('contract-templates/{contractTemplate}/preview', [\App\Http\Controllers\ContractTemplateController::class, 'preview'])->name('contract-templates.preview');
        Route::post('contract-templates/{contractTemplate}/create-version', [\App\Http\Controllers\ContractTemplateController::class, 'createVersion'])->name('contract-templates.create-version');
    });
});

// Google OAuth routes
Route::get('/auth/google', [GoogleController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleController::class, 'handleGoogleCallback']);

require __DIR__.'/auth.php';

Route::get('/shared/checklist/pdf/{token}', [App\Http\Controllers\PdfController::class, 'sharedChecklistPdf'])
    ->name('shared.checklist.pdf')
    ->middleware('signed');

// Admin authentication and dashboard
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'store']);
    Route::post('logout', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'destroy'])->name('logout');
    
    Route::middleware(['auth', 'role:admin'])->group(function () {
        Route::get('dashboard', [\App\Http\Controllers\DashboardController::class, 'adminDashboard'])->name('dashboard');
        Route::get('missions', [MissionController::class, 'index'])->name('missions');
        Route::get('missions/assigned', [MissionController::class, 'getAssignedMissions'])->name('missions.assigned');
        Route::get('missions/completed', [MissionController::class, 'getCompletedMissions'])->name('missions.completed');
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
