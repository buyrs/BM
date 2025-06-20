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

    // Admin routes
    Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'adminDashboard'])->name('dashboard');
        Route::get('/analytics/data', [\App\Http\Controllers\Admin\AnalyticsController::class, 'index'])->name('analytics.data');
        Route::get('/checkers', [\App\Http\Controllers\Admin\AnalyticsController::class, 'checkers'])->name('checkers');
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
