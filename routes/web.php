<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InviteController;
use App\Http\Controllers\FamilyController;
use App\Http\Controllers\ChoreTemplateController;
use App\Http\Controllers\ChoreController;
use App\Http\Controllers\SuperAdmin\FamilyController as SuperAdminFamilyController;
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
    
    // Chores
    Route::resource('chores', ChoreController::class)
        ->names([
            'index' => 'admin.chores.index',
            'create' => 'admin.chores.create',
            'store' => 'admin.chores.store',
            'show' => 'admin.chores.show',
            'edit' => 'admin.chores.edit',
            'update' => 'admin.chores.update',
            'destroy' => 'admin.chores.destroy',
        ]);
    Route::post('chores/from-template', [ChoreController::class, 'createFromTemplate'])
        ->name('admin.chores.from-template');
    
    // Family management
    Route::get('family', [FamilyController::class, 'index'])
        ->name('admin.family.index');
    Route::get('family/edit', [FamilyController::class, 'edit'])
        ->name('admin.family.edit');
    Route::put('family', [FamilyController::class, 'update'])
        ->name('admin.family.update');
    Route::get('family/members', [FamilyController::class, 'members'])
        ->name('admin.family.members');
    Route::patch('family/members/promote', [FamilyController::class, 'promoteMember'])
        ->name('admin.family.promote-member');
    Route::patch('family/members/demote', [FamilyController::class, 'demoteMember'])
        ->name('admin.family.demote-member');
    Route::delete('family/members', [FamilyController::class, 'removeMember'])
        ->name('admin.family.remove-member');
    Route::post('family/members/add', [FamilyController::class, 'addMember'])
        ->name('admin.family.add-member');
});

// Super Admin routes
Route::middleware(['auth', 'super.admin'])->prefix('super-admin')->name('super-admin.')->group(function () {
    Route::resource('families', SuperAdminFamilyController::class);
    Route::get('families/{family}/members', [SuperAdminFamilyController::class, 'members'])
        ->name('families.members');
    Route::delete('families/{family}/members', [SuperAdminFamilyController::class, 'removeMember'])
        ->name('families.remove-member');
    Route::patch('families/{family}/members/promote', [SuperAdminFamilyController::class, 'promoteMember'])
        ->name('families.promote-member');
    Route::patch('families/{family}/members/demote', [SuperAdminFamilyController::class, 'demoteMember'])
        ->name('families.demote-member');
});

// Invite routes (no auth required)
Route::get('/invite/family/{encryptedFamilyId}', [InviteController::class, 'familyInvite'])
    ->name('invite.family');
Route::get('/invite/user/{encryptedUserId}', [InviteController::class, 'userInvite'])
    ->name('invite.user');

// Profile routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
