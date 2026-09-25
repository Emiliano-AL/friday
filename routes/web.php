<?php

use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectMemberController;
use App\Http\Controllers\ProjectSprintController;
use App\Http\Controllers\ProjectTaskController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('AppHome');
})->middleware(['auth'])->name('home');

Route::middleware('auth')->group(function () {
    Route::resource('projects', ProjectController::class);
    Route::put('projects/{project}/status', [ProjectController::class, 'status'])
        ->name('projects.status');
    Route::post('projects/{project}/members', [ProjectMemberController::class, 'store'])
        ->name('projects.members.store');
    Route::delete('projects/{project}/members/{user}', [ProjectMemberController::class, 'destroy'])
        ->name('projects.members.destroy');
    Route::post('projects/{project}/sprints', [ProjectSprintController::class, 'store'])
        ->name('projects.sprints.store');
    Route::put('projects/{project}/sprints/{sprint}', [ProjectSprintController::class, 'update'])
        ->name('projects.sprints.update');
    Route::delete('projects/{project}/sprints/{sprint}', [ProjectSprintController::class, 'destroy'])
        ->name('projects.sprints.destroy');
    Route::post('projects/{project}/tasks', [ProjectTaskController::class, 'store'])
        ->name('projects.tasks.store');
    Route::put('projects/{project}/tasks/{task}', [ProjectTaskController::class, 'update'])
        ->name('projects.tasks.update');
    Route::delete('projects/{project}/tasks/{task}', [ProjectTaskController::class, 'destroy'])
        ->name('projects.tasks.destroy');
    Route::post('projects/{project}/tasks/{task}/comments', [ProjectTaskController::class, 'storeComment'])
        ->name('projects.tasks.comments.store');
});

require __DIR__.'/auth.php';
