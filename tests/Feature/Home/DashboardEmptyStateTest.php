<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('users without projects and without assigned tasks see a null dashboard', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('home'));

    $response->assertOk()->assertInertia(fn ($page) => $page->component('AppHome'));

    $props = AssertableInertia::fromTestResponse($response)->toArray()['props'];

    expect($props)->toHaveKey('dashboard')
        ->and($props['dashboard'])->toBeNull()
        ->and($props)->toHaveKey('projects')
        ->and($props['projects'])->toBe([]);
});

test('users with projects see a dashboard payload', function () {
    $user = User::factory()->create();
    Project::factory()->for($user, 'owner')->create();

    $props = AssertableInertia::fromTestResponse(
        $this->actingAs($user)->get(route('home')),
    )->toArray()['props'];

    expect($props['dashboard'])->not->toBeNull();
});

test('users with only assigned standalone tasks see a dashboard payload', function () {
    $user = User::factory()->create();
    Task::factory()->standalone()->for($user, 'assignee')->create();

    $props = AssertableInertia::fromTestResponse(
        $this->actingAs($user)->get(route('home')),
    )->toArray()['props'];

    expect($props['dashboard'])->not->toBeNull();
});
