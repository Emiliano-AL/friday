<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

function googleUser(): User
{
    return User::factory()->create([
        'password' => null,
        'oauth_provider' => 'google',
        'oauth_id' => 'google-123',
    ]);
}

describe('page', function () {
    it('redirects guests to login', function () {
        $this->get('/settings')->assertRedirect(route('login'));
    });

    it('renders the account and only the projects the user owns', function () {
        $user = User::factory()->create();
        $owned = Project::factory()->archived()->create(['owner_id' => $user->id, 'title' => 'Propio']);
        Project::factory()->create()->members()->attach($user->id);

        $this->actingAs($user)
            ->get('/settings')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/Index')
                ->where('account.name', $user->name)
                ->where('account.email', $user->email)
                ->where('account.hasPassword', true)
                ->where('account.usesGoogle', false)
                ->has('ownedProjects', 1)
                ->where('ownedProjects.0.id', $owned->id)
                ->where('ownedProjects.0.title', 'Propio'));
    });

    it('flags google accounts without a password', function () {
        $this->actingAs(googleUser())
            ->get('/settings')
            ->assertInertia(fn (Assert $page) => $page
                ->where('account.hasPassword', false)
                ->where('account.usesGoogle', true));
    });
});

describe('profile', function () {
    it('updates the trimmed name', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/settings')
            ->patch('/settings/profile', ['name' => '  Ana López  '])
            ->assertRedirect('/settings')
            ->assertSessionHas('success', 'Perfil actualizado.');

        expect($user->fresh()->name)->toBe('Ana López');
    });

    it('rejects an invalid name', function (string $name) {
        $user = User::factory()->create(['name' => 'Original']);

        $this->actingAs($user)
            ->patch('/settings/profile', ['name' => $name])
            ->assertInvalid('name');

        expect($user->fresh()->name)->toBe('Original');
    })->with(['empty' => [''], 'too long' => [str_repeat('a', 256)]]);

    it('never changes the email', function () {
        $user = User::factory()->create(['email' => 'ana@example.com']);

        $this->actingAs($user)
            ->patch('/settings/profile', ['name' => 'Ana', 'email' => 'otro@example.com'])
            ->assertSessionHasNoErrors();

        expect($user->fresh()->email)->toBe('ana@example.com');
    });
});

describe('password', function () {
    it('changes the password with the current one', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put('/settings/password', [
                'current_password' => 'password',
                'password' => 'nueva-clave-segura',
                'password_confirmation' => 'nueva-clave-segura',
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Contraseña actualizada.');

        expect(Hash::check('nueva-clave-segura', $user->fresh()->password))->toBeTrue();
    });

    it('rejects a wrong current password', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put('/settings/password', [
                'current_password' => 'incorrecta',
                'password' => 'nueva-clave-segura',
                'password_confirmation' => 'nueva-clave-segura',
            ])
            ->assertInvalid(['current_password' => 'La contraseña actual no es correcta.']);

        expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
    });

    it('rejects a weak or unconfirmed new password', function (string $password, string $confirmation) {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put('/settings/password', [
                'current_password' => 'password',
                'password' => $password,
                'password_confirmation' => $confirmation,
            ])
            ->assertInvalid('password');
    })->with([
        'too short' => ['corta', 'corta'],
        'mismatch' => ['nueva-clave-segura', 'otra-clave-segura'],
    ]);

    it('lets a google account define a password without a current one', function () {
        $user = googleUser();

        $this->actingAs($user)
            ->put('/settings/password', [
                'password' => 'nueva-clave-segura',
                'password_confirmation' => 'nueva-clave-segura',
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Contraseña definida.');

        expect(Hash::check('nueva-clave-segura', $user->fresh()->password))->toBeTrue();
    });

    it('keeps the current session authenticated after the change', function () {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/settings/password', [
            'current_password' => 'password',
            'password' => 'nueva-clave-segura',
            'password_confirmation' => 'nueva-clave-segura',
        ]);

        $this->get('/settings')->assertOk();
        $this->assertAuthenticatedAs($user);
    });

    it('signs out other sessions that still carry the old password', function () {
        $user = User::factory()->create();
        $oldHash = $user->password;

        $this->actingAs($user)->put('/settings/password', [
            'current_password' => 'password',
            'password' => 'nueva-clave-segura',
            'password_confirmation' => 'nueva-clave-segura',
        ]);

        $this->get('/settings')->assertOk();

        $this->withSession(['password_hash_web' => $oldHash])
            ->get('/settings')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    });

    it('throttles repeated attempts', function () {
        $user = User::factory()->create();

        foreach (range(1, 6) as $attempt) {
            $this->actingAs($user)->put('/settings/password', ['current_password' => 'incorrecta']);
        }

        $this->actingAs($user)
            ->put('/settings/password', ['current_password' => 'incorrecta'])
            ->assertTooManyRequests();
    });
});

describe('delete account', function () {
    it('is blocked while the user owns projects', function () {
        $user = User::factory()->create();
        Project::factory()->create(['owner_id' => $user->id]);

        $this->actingAs($user)
            ->delete('/settings', ['password' => 'password'])
            ->assertInvalid(['password' => 'Elimina tus proyectos antes de eliminar tu cuenta.']);

        expect($user->fresh())->not->toBeNull();
    });

    it('deletes the account with the right password and logs out', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete('/settings', ['password' => 'password'])
            ->assertRedirect(route('login'));

        $this->assertGuest();
        expect($user->fresh())->toBeNull();
    });

    it('rejects a wrong password', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete('/settings', ['password' => 'incorrecta'])
            ->assertInvalid('password');

        expect($user->fresh())->not->toBeNull();
    });

    it('lets a google account confirm with its email', function () {
        $user = googleUser();

        $this->actingAs($user)
            ->delete('/settings', ['confirmation' => strtoupper($user->email)])
            ->assertRedirect(route('login'));

        expect($user->fresh())->toBeNull();
    });

    it('rejects a google account confirmation that does not match', function () {
        $user = googleUser();

        $this->actingAs($user)
            ->delete('/settings', ['confirmation' => 'otro@example.com'])
            ->assertInvalid(['confirmation' => 'La confirmación no coincide.']);

        expect($user->fresh())->not->toBeNull();
    });

    it('removes standalone tasks and detaches project tasks', function () {
        $user = User::factory()->create();
        $standalone = Task::factory()->standalone()->create(['author_id' => $user->id, 'assignee_id' => $user->id]);
        $projectTask = Task::factory()->create(['author_id' => $user->id, 'assignee_id' => $user->id]);

        $this->actingAs($user)->delete('/settings', ['password' => 'password']);

        expect(Task::find($standalone->id))->toBeNull();
        expect($projectTask->fresh())
            ->author_id->toBeNull()
            ->assignee_id->toBeNull();
    });

    it('throttles repeated attempts', function () {
        $user = User::factory()->create();

        foreach (range(1, 6) as $attempt) {
            $this->actingAs($user)->delete('/settings', ['password' => 'incorrecta']);
        }

        $this->actingAs($user)
            ->delete('/settings', ['password' => 'incorrecta'])
            ->assertTooManyRequests();
    });
});
