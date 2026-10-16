<?php

use App\Http\Controllers\BacklogController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InvitationResponseController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectInvitationController;
use App\Http\Controllers\ProjectMemberController;
use App\Http\Controllers\ProjectSprintController;
use App\Http\Controllers\ProjectTaskController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskImportController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('invitations/{token}', [InvitationResponseController::class, 'show'])
    ->name('invitations.show');

Route::middleware(['auth', 'auth.session'])->group(function () {
    Route::resource('projects', ProjectController::class);
    Route::put('projects/{project}/status', [ProjectController::class, 'status'])
        ->name('projects.status');
    Route::post('projects/{project}/members', [ProjectMemberController::class, 'store'])
        ->middleware('throttle:project-invitations')
        ->name('projects.members.store');
    Route::post('projects/{project}/invitations/{invitation}/resend', [ProjectInvitationController::class, 'resend'])
        ->middleware('throttle:project-invitations')
        ->name('projects.invitations.resend');
    Route::delete('projects/{project}/invitations/{invitation}', [ProjectInvitationController::class, 'destroy'])
        ->name('projects.invitations.destroy');
    Route::post('invitations/{token}/accept', [InvitationResponseController::class, 'accept'])
        ->name('invitations.accept');
    Route::post('invitations/{token}/decline', [InvitationResponseController::class, 'decline'])
        ->name('invitations.decline');
    Route::delete('projects/{project}/members/{user}', [ProjectMemberController::class, 'destroy'])
        ->name('projects.members.destroy');
    Route::post('projects/{project}/sprints', [ProjectSprintController::class, 'store'])
        ->name('projects.sprints.store');
    Route::get('projects/{project}/sprints/{sprint}', [ProjectSprintController::class, 'show'])
        ->name('projects.sprints.show');
    Route::put('projects/{project}/sprints/{sprint}', [ProjectSprintController::class, 'update'])
        ->name('projects.sprints.update');
    Route::delete('projects/{project}/sprints/{sprint}', [ProjectSprintController::class, 'destroy'])
        ->name('projects.sprints.destroy');
    Route::post('projects/{project}/sprints/{sprint}/start', [ProjectSprintController::class, 'start'])
        ->name('projects.sprints.start');
    Route::post('projects/{project}/sprints/{sprint}/complete', [ProjectSprintController::class, 'complete'])
        ->name('projects.sprints.complete');
    Route::post('projects/{project}/tasks/import/preview', [TaskImportController::class, 'projectPreview'])
        ->name('projects.tasks.import.preview');
    Route::post('projects/{project}/tasks/import', [TaskImportController::class, 'projectStore'])
        ->name('projects.tasks.import.store');
    Route::post('tasks/import/preview', [TaskImportController::class, 'preview'])
        ->name('tasks.import.preview');
    Route::post('tasks/import', [TaskImportController::class, 'store'])
        ->name('tasks.import.store');
    Route::post('projects/{project}/tasks', [ProjectTaskController::class, 'store'])
        ->name('projects.tasks.store');
    Route::put('projects/{project}/tasks/{task}', [ProjectTaskController::class, 'update'])
        ->name('projects.tasks.update');
    Route::delete('projects/{project}/tasks/{task}', [ProjectTaskController::class, 'destroy'])
        ->name('projects.tasks.destroy');
    Route::post('projects/{project}/tasks/{task}/comments', [ProjectTaskController::class, 'storeComment'])
        ->name('projects.tasks.comments.store');

    Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::patch('settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile.update');
    Route::put('settings/password', [SettingsController::class, 'updatePassword'])
        ->middleware('throttle:account-security')
        ->name('settings.password.update');
    Route::delete('settings', [SettingsController::class, 'destroy'])
        ->middleware('throttle:account-security')
        ->name('settings.destroy');

    Route::get('backlog', [BacklogController::class, 'index'])->name('backlog.index');

    Route::get('tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::post('tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::get('tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
    Route::put('tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
    Route::post('tasks/{task}/comments', [TaskController::class, 'storeComment'])
        ->name('tasks.comments.store');
});

require __DIR__.'/auth.php';
