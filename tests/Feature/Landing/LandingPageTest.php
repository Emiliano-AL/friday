<?php

use App\Models\ProjectInvitation;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('guests see the public landing page at the root route', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Landing'));
});

test('the landing page receives no dashboard data', function () {
    $props = AssertableInertia::fromTestResponse($this->get(route('home')))->toArray()['props'];

    expect($props)->not->toHaveKey('dashboard')
        ->and($props)->not->toHaveKey('projects');
});

test('the login and register pages stay reachable for guests', function () {
    $this->get(route('login'))->assertOk();
    $this->get(route('register'))->assertOk();
});

test('logging in from the landing still lands on the dashboard', function () {
    $user = User::factory()->create();

    $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('home', absolute: false));

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('AppHome'));
});

test('authenticated users keep seeing the dashboard at the root route', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('AppHome'));
});

test('after logging out the root route shows the landing again', function () {
    $this->actingAs(User::factory()->create())->post(route('logout'));

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Landing'));
});

test('invitation links keep working for guests', function () {
    ProjectInvitation::factory()->withToken('tok')->create();

    $this->get(route('invitations.show', 'tok'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Invitations/Show')
            ->where('state', 'guest')
        );
});
