<?php

use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectMemberController;
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
});

require __DIR__.'/auth.php';
