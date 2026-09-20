<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('AppHome');
})->middleware(['auth'])->name('home');

require __DIR__.'/auth.php';
