<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\MissionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ChecklistController;
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

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => app()->version(),
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Routes for authenticated users
Route::middleware(['auth', 'verified'])->group(function () {
    // Super Admin routes
    Route::middleware(['role:super-admin'])->group(function () {
        Route::get('/super-admin/dashboard', function () {
            return Inertia::render('SuperAdmin/Dashboard');
        })->name('super-admin.dashboard');
    });

    // Checker routes
    Route::middleware(['role:checker'])->group(function () {
        Route::get('/checker/dashboard', function () {
            return Inertia::render('Checker/Dashboard');
        })->name('checker.dashboard');
    });

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

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
    });

    // Checklist routes
    Route::prefix('checklist')->group(function () {
        Route::get('/{mission}/create', [ChecklistController::class, 'create'])->name('checklist.create');
        Route::post('/{mission}', [ChecklistController::class, 'store'])->name('checklist.store');
        Route::get('/{mission}/{checklist}/review', [ChecklistController::class, 'review'])->name('checklist.review');
        Route::post('/items/{item}/photos', [ChecklistController::class, 'uploadPhoto'])->name('checklist.upload-photo');
        Route::delete('/photos/{photo}', [ChecklistController::class, 'deletePhoto'])->name('checklist.delete-photo');
    });
});

// Google OAuth routes
Route::get('/auth/google', [GoogleController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleController::class, 'handleGoogleCallback']);

require __DIR__.'/auth.php';

Route::get('/shared/checklist/pdf/{token}', [App\Http\Controllers\PdfController::class, 'sharedChecklistPdf'])
    ->name('shared.checklist.pdf')
    ->middleware('signed');
