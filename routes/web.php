<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InviteController;
use App\Http\Controllers\FamilyController;
use App\Http\Controllers\ChoreController;
use App\Http\Controllers\AssignChoreController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SuperAdmin\FamilyController as SuperAdminFamilyController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Member\ChoresController as MemberChoresController;
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
Route::middleware(['auth', 'family.admin'])->prefix('admin')->name('admin.')->group(function () {
    
    // Chores management
    Route::get('chores', [ChoreController::class, 'index'])->name('chores.index');
    Route::post('chores', [ChoreController::class, 'store'])->name('chores.store');
    Route::put('chores/{chore}', [ChoreController::class, 'update'])->name('chores.update');
    Route::delete('chores/{chore}', [ChoreController::class, 'destroy'])->name('chores.destroy');

    // Assign chores
    Route::get('assign-chores', [AssignChoreController::class, 'index'])->name('assign-chores.index');
    Route::post('assign-chores', [AssignChoreController::class, 'assign'])->name('assign-chores.assign');
    Route::delete('assign-chores/{assignedChore}', [AssignChoreController::class, 'unassign'])->name('assign-chores.unassign');
    Route::post('assign-chores/adhoc', [AssignChoreController::class, 'createAdhoc'])->name('assign-chores.adhoc');
    Route::get('assign-chores/adhoc/search', [AssignChoreController::class, 'searchAdhoc'])->name('assign-chores.adhoc.search');
    
    // Family management
    Route::get('family', [FamilyController::class, 'index'])
        ->name('family.index');
    Route::get('family/edit', [FamilyController::class, 'edit'])
        ->name('family.edit');
    Route::put('family', [FamilyController::class, 'update'])
        ->name('family.update');
    Route::patch('family/members/promote', [FamilyController::class, 'promoteMember'])
        ->name('family.promote-member');
    Route::patch('family/members/demote', [FamilyController::class, 'demoteMember'])
        ->name('family.demote-member');
    Route::delete('family/members', [FamilyController::class, 'removeMember'])
        ->name('family.remove-member');
    Route::post('family/members/add', [FamilyController::class, 'addMember'])
        ->name('family.add-member');
    
    // User management
    Route::get('users/{user}/edit', [UserController::class, 'edit'])
        ->name('users.edit');
    Route::put('users/{user}', [UserController::class, 'update'])
        ->name('users.update');
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
Route::post('/invite/family', [InviteController::class, 'storeFamilyInvite'])
    ->name('invite.family.store');
Route::get('/invite/user/{encryptedUserId}', [InviteController::class, 'userInvite'])
    ->name('invite.user');
Route::post('/invite/user', [InviteController::class, 'storeUserInvite'])
    ->name('invite.user.store');

// Member and Admin routes (for viewing/assigning available chores)
Route::middleware(['auth', 'family.member'])->group(function () {
    Route::get('chores', [MemberChoresController::class, 'index'])->name('chores.index');
    Route::post('chores/assign', [MemberChoresController::class, 'assign'])->name('chores.assign');
});

// Profile routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
