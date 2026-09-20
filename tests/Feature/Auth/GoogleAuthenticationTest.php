<?php

use App\Enums\OAuthProvider;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;

test('users are redirected to google', function () {
    Socialite::fake('google');

    $response = $this->get(route('google.redirect', absolute: false));

    $response->assertRedirect();
});

test('new users can authenticate with google', function () {
    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-123',
        'name' => 'Jason Beggs',
        'email' => 'jason@example.com',
    ]));

    $response = $this->get(route('google.callback', absolute: false));

    $response->assertRedirect(route('home', absolute: false));
    $this->assertAuthenticated();

    $this->assertDatabaseHas('users', [
        'name' => 'Jason Beggs',
        'email' => 'jason@example.com',
        'oauth_provider' => OAuthProvider::Google->value,
        'oauth_id' => 'google-123',
    ]);

    $user = User::query()->where('email', 'jason@example.com')->firstOrFail();

    expect($user->email_verified_at)->not->toBeNull();
});

test('returning google users authenticate into the same account', function () {
    $fakeUser = SocialiteUser::fake([
        'id' => 'google-123',
        'name' => 'Jason Beggs',
        'email' => 'jason@example.com',
    ]);

    Socialite::fake('google', $fakeUser);
    $this->get(route('google.callback', absolute: false))->assertRedirect(route('home', absolute: false));

    $firstUserId = User::query()->where('email', 'jason@example.com')->value('id');

    $this->flushSession();

    Socialite::fake('google', $fakeUser);
    $this->get(route('google.callback', absolute: false))->assertRedirect(route('home', absolute: false));

    expect(User::query()->where('email', 'jason@example.com')->count())->toBe(1);
    expect(User::query()->where('email', 'jason@example.com')->value('id'))->toBe($firstUserId);
});

test('google login links to an existing local account with the same email', function () {
    $user = User::factory()->create([
        'email' => 'jason@example.com',
    ]);

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-123',
        'name' => 'Jason Beggs',
        'email' => 'jason@example.com',
    ]));

    $response = $this->get(route('google.callback', absolute: false));

    $response->assertRedirect(route('home', absolute: false));
    $this->assertAuthenticated();

    expect(User::query()->where('email', 'jason@example.com')->count())->toBe(1);

    $user->refresh();

    expect($user->oauth_provider)->toBe(OAuthProvider::Google);
    expect($user->oauth_id)->toBe('google-123');
    expect($user->email_verified_at)->not->toBeNull();
    expect(Hash::check('password', $user->password))->toBeTrue();
});

test('failed google callbacks redirect to login without creating a user', function () {
    $provider = Mockery::mock(Provider::class);
    $provider->shouldReceive('user')->andThrow(new InvalidStateException);
    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $response = $this->get(route('google.callback', absolute: false));

    $response->assertRedirect(route('login', absolute: false));
    $response->assertSessionHas('status');
    $this->assertGuest();
    expect(User::query()->count())->toBe(0);
});

test('users return to the intended page after a google login', function () {
    $this->get('/')->assertRedirect(route('login', absolute: false));

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-123',
        'name' => 'Jason Beggs',
        'email' => 'jason@example.com',
    ]));

    $response = $this->get(route('google.callback', absolute: false));

    $response->assertRedirect('/');
});
