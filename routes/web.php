<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FamilyInviteController;
use App\Http\Controllers\ChoreTemplateController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'family.member'])
    ->name('dashboard');

// Family setup (for users without families)
Route::get('/family/setup', function () {
    return Inertia::render('Family/Setup');
})->middleware(['auth'])->name('family.setup');

// Family admin routes
Route::middleware(['auth', 'family.admin'])->group(function () {
    // Family invites
    Route::resource('family.invites', FamilyInviteController::class)
        ->except(['edit', 'update']);
    
    // Chore templates
    Route::resource('templates', ChoreTemplateController::class)
        ->names([
            'index' => 'admin.templates.index',
            'create' => 'admin.templates.create',
            'store' => 'admin.templates.store',
            'show' => 'admin.templates.show',
            'edit' => 'admin.templates.edit',
            'update' => 'admin.templates.update',
            'destroy' => 'admin.templates.destroy',
        ]);
    Route::patch('templates/{template}/toggle', [ChoreTemplateController::class, 'toggle'])
        ->name('admin.templates.toggle');
});

// Invite acceptance (no auth required for checking invites)
Route::post('/invites/check', [FamilyInviteController::class, 'check'])
    ->name('invites.check');

Route::middleware('auth')->group(function () {
    Route::post('/invites/accept', [FamilyInviteController::class, 'accept'])
        ->name('invites.accept');
});

// Profile routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
