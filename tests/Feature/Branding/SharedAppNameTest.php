<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests receive Eunox as the shared product name', function () {
    $this->get(route('login'))
        ->assertInertia(fn (Assert $page) => $page->where('name', 'Eunox'));
});

test('authenticated users receive Eunox as the shared product name', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page->where('name', 'Eunox'));
});

test('the shared product name comes from the app name config', function () {
    config(['app.name' => 'Otro']);

    $this->get(route('login'))
        ->assertInertia(fn (Assert $page) => $page->where('name', 'Otro'));
});
