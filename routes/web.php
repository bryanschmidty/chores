<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\ChoreCompletionController;
use App\Http\Controllers\ChoreInstanceController;
use App\Http\Controllers\ChoreTemplateController;
use App\Http\Controllers\WeeklyClaimController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirectToGoogle'])
    ->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback'])
    ->name('auth.google.callback');
Route::post('/logout', [GoogleAuthController::class, 'logout'])
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/app', function (Request $request) {
        $user = $request->user();

        if ($user->hasRole('parent')) {
            return to_route('parent.templates');
        }

        if ($user->hasRole('supervisor')) {
            return to_route('supervisor.queue');
        }

        return to_route('kid.index');
    })->name('app.home');

    Route::get('/kid', [ChoreInstanceController::class, 'kidIndex'])
        ->name('kid.index');
    Route::get('/kid/chores', [ChoreInstanceController::class, 'kidAll'])
        ->name('kid.chores');
    Route::get('/kid/chores/{choreInstance}', [ChoreInstanceController::class, 'kidShow'])
        ->name('kid.chores.show');

    Route::get('/parent/templates', [ChoreTemplateController::class, 'parentIndex'])
        ->name('parent.templates');
    Route::get('/parent/chores', [ChoreInstanceController::class, 'parentIndex'])
        ->name('parent.chores');
    Route::get('/parent/history', [ChoreCompletionController::class, 'parentHistory'])
        ->name('parent.history');
    Route::get('/parent/leaderboard', [ChoreCompletionController::class, 'parentLeaderboard'])
        ->name('parent.leaderboard');

    Route::get('/supervisor/queue', [ChoreCompletionController::class, 'supervisorQueue'])
        ->name('supervisor.queue');

    Route::get('/chore-templates', [ChoreTemplateController::class, 'index'])
        ->name('chore-templates.index');
    Route::post('/chore-templates', [ChoreTemplateController::class, 'store'])
        ->name('chore-templates.store');
    Route::put('/chore-templates/{choreTemplate}', [ChoreTemplateController::class, 'update'])
        ->name('chore-templates.update');
    Route::patch('/chore-templates/{choreTemplate}/archive', [ChoreTemplateController::class, 'archive'])
        ->name('chore-templates.archive');

    Route::get('/chore-instances', [ChoreInstanceController::class, 'index'])
        ->name('chore-instances.index');
    Route::post('/chore-instances', [ChoreInstanceController::class, 'store'])
        ->name('chore-instances.store');
    Route::put('/chore-instances/{choreInstance}', [ChoreInstanceController::class, 'update'])
        ->name('chore-instances.update');
    Route::delete('/chore-instances/{choreInstance}', [ChoreInstanceController::class, 'destroy'])
        ->name('chore-instances.destroy');
    Route::post('/chore-instances/{choreInstance}/claim', [ChoreInstanceController::class, 'claim'])
        ->name('chore-instances.claim');
    Route::post('/chore-instances/{choreInstance}/assign', [ChoreInstanceController::class, 'assign'])
        ->name('chore-instances.assign');

    Route::post('/weekly-claims', [WeeklyClaimController::class, 'store'])
        ->name('weekly-claims.store');
    Route::put('/weekly-claims/{weeklyClaim}', [WeeklyClaimController::class, 'update'])
        ->name('weekly-claims.update');

    Route::post('/chore-completions', [ChoreCompletionController::class, 'store'])
        ->name('chore-completions.store');
    Route::post('/chore-completions/{choreCompletion}/approve', [ChoreCompletionController::class, 'approve'])
        ->name('chore-completions.approve');
    Route::post('/chore-completions/{choreCompletion}/reject', [ChoreCompletionController::class, 'reject'])
        ->name('chore-completions.reject');
    Route::get('/leaderboard', [ChoreCompletionController::class, 'leaderboard'])
        ->name('leaderboard.index');
    Route::get('/completion-history', [ChoreCompletionController::class, 'history'])
        ->name('completion-history.index');
});
