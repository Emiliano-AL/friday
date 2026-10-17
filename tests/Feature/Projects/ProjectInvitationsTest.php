<?php

use App\Enums\ProjectInvitationStatus;
use App\Enums\ProjectStatus;
use App\Mail\ProjectInvitationMail;
use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Mailer\Exception\HttpTransportException;
use Symfony\Component\Mailer\Exception\TransportException;

test('owners invite an unregistered email and the invitation link is mailed', function () {
    Mail::fake();
    $owner = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();

    $response = $this->actingAs($owner)->post("/projects/{$project->id}/members", [
        'email' => 'Nuevo@Example.com',
    ]);

    $response->assertRedirect(route('projects.show', $project, absolute: false))
        ->assertSessionHas('success', 'Invitación enviada a nuevo@example.com.');

    $invitation = ProjectInvitation::query()->sole();
    expect($invitation->email)->toBe('nuevo@example.com')
        ->and($invitation->status)->toBe(ProjectInvitationStatus::Pending)
        ->and($invitation->invited_by_id)->toBe($owner->id);

    Mail::assertQueued(ProjectInvitationMail::class, fn (ProjectInvitationMail $mail) => $mail->hasTo('nuevo@example.com')
        && $invitation->is(ProjectInvitation::findByToken($mail->token)));
    $this->assertDatabaseCount('project_user', 0);
});

test('owners still add registered users instantly without an invitation', function () {
    Mail::fake();
    $owner = User::factory()->create();
    $collaborator = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();

    $this->actingAs($owner)->post("/projects/{$project->id}/members", [
        'email' => $collaborator->email,
    ]);

    $this->assertDatabaseHas('project_user', ['project_id' => $project->id, 'user_id' => $collaborator->id]);
    $this->assertDatabaseCount('project_invitations', 0);
    Mail::assertNothingOutgoing();
});

test('inviting the same email twice resends the existing pending invitation', function () {
    Mail::fake();
    $owner = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();

    $this->actingAs($owner)->post("/projects/{$project->id}/members", ['email' => 'nuevo@example.com']);
    $this->actingAs($owner)->post("/projects/{$project->id}/members", ['email' => 'nuevo@example.com']);

    $this->assertDatabaseCount('project_invitations', 1);
    Mail::assertQueued(ProjectInvitationMail::class, 2);
});

test('owners can not invite on an inactive project', function () {
    Mail::fake();
    $owner = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create(['status' => ProjectStatus::Archived]);

    $response = $this->actingAs($owner)->post("/projects/{$project->id}/members", [
        'email' => 'nuevo@example.com',
    ]);

    $response->assertSessionHasErrors(['email' => 'No puedes invitar en un proyecto archivado o completado.']);
    $this->assertDatabaseCount('project_invitations', 0);
    Mail::assertNothingOutgoing();
});

test('non-owners can not invite collaborators', function () {
    Mail::fake();
    $member = User::factory()->create();
    $project = Project::factory()->hasAttached($member, [], 'members')->create();

    $this->actingAs($member)
        ->post("/projects/{$project->id}/members", ['email' => 'nuevo@example.com'])
        ->assertNotFound();

    $this->assertDatabaseCount('project_invitations', 0);
});

test('a failed delivery keeps the invitation and tells the owner to resend', function () {
    $owner = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();
    Mail::shouldReceive('to')->andThrow(new TransportException('smtp down'));

    $response = $this->actingAs($owner)->post("/projects/{$project->id}/members", [
        'email' => 'nuevo@example.com',
    ]);

    $response->assertSessionHas('error', 'No pudimos enviar el correo. Puedes reenviar la invitación.');
    $this->assertDatabaseHas('project_invitations', ['email' => 'nuevo@example.com', 'status' => 'pending']);
});

test('a rejection from the Postmark API keeps the invitation and tells the owner to resend', function () {
    $owner = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();
    Mail::shouldReceive('to')->andThrow(new HttpTransportException(
        'Sender signature not confirmed',
        new MockResponse('', ['http_code' => 422]),
    ));

    $response = $this->actingAs($owner)->post("/projects/{$project->id}/members", [
        'email' => 'nuevo@example.com',
    ]);

    $response->assertSessionHas('error', 'No pudimos enviar el correo. Puedes reenviar la invitación.');
    $this->assertDatabaseHas('project_invitations', ['email' => 'nuevo@example.com', 'status' => 'pending']);
});

test('guests opening a valid link see the invitation and are sent back to it after signing in', function () {
    $project = Project::factory()->create(['title' => 'Tienda']);
    ProjectInvitation::factory()->for($project)->withToken('valid-token')->create([
        'email' => 'nuevo@example.com',
        'invited_by_id' => $project->owner_id,
    ]);

    $this->get('/invitations/valid-token')
        ->assertInertia(fn ($page) => $page
            ->component('Invitations/Show')
            ->where('state', 'guest')
            ->where('invitation.email', 'nuevo@example.com')
            ->where('invitation.project.title', 'Tienda')
            ->where('invitation.invitedBy.name', $project->owner->name)
        )
        ->assertSessionHas('url.intended', route('invitations.show', 'valid-token'))
        ->assertSessionHas('invitation_email', 'nuevo@example.com');
});

test('unusable links explain why they can not be accepted', function (Closure $makeInvitation, string $reason) {
    $makeInvitation();

    $this->get('/invitations/some-token')
        ->assertInertia(fn ($page) => $page
            ->component('Invitations/Show')
            ->where('state', 'unavailable')
            ->where('reason', $reason)
        );
})->with([
    'unknown token' => [fn () => null, 'not_found'],
    'cancelled' => [fn () => ProjectInvitation::factory()->cancelled()->withToken('some-token')->create(), 'cancelled'],
    'already used' => [fn () => ProjectInvitation::factory()->accepted()->withToken('some-token')->create(), 'used'],
    'expired' => [fn () => ProjectInvitation::factory()->expired()->withToken('some-token')->create(), 'expired'],
    'inactive project' => [fn () => ProjectInvitation::factory()
        ->for(Project::factory()->state(['status' => ProjectStatus::Completed]))
        ->withToken('some-token')->create(), 'inactive_project'],
]);

test('the invited user accepts and becomes a project member', function () {
    $user = User::factory()->create(['email' => 'nuevo@example.com']);
    $project = Project::factory()->create();
    $invitation = ProjectInvitation::factory()->for($project)->withToken('valid-token')->create([
        'email' => 'nuevo@example.com',
    ]);

    $this->actingAs($user)
        ->post('/invitations/valid-token/accept')
        ->assertRedirect(route('projects.show', $project, absolute: false));

    $this->assertDatabaseHas('project_user', ['project_id' => $project->id, 'user_id' => $user->id]);
    expect($invitation->fresh()->status)->toBe(ProjectInvitationStatus::Accepted)
        ->and($invitation->fresh()->responded_at)->not->toBeNull();
});

test('invitations to an inactive project can not be accepted', function () {
    $user = User::factory()->create(['email' => 'nuevo@example.com']);
    $project = Project::factory()->create(['status' => ProjectStatus::Archived]);
    ProjectInvitation::factory()->for($project)->withToken('valid-token')->create([
        'email' => 'nuevo@example.com',
    ]);

    $this->actingAs($user)
        ->post('/invitations/valid-token/accept')
        ->assertRedirect(route('invitations.show', 'valid-token', absolute: false));

    $this->assertDatabaseCount('project_user', 0);
});

test('a signed-in user with another email can not answer the invitation', function () {
    $stranger = User::factory()->create(['email' => 'otro@example.com']);
    $invitation = ProjectInvitation::factory()->withToken('valid-token')->create(['email' => 'nuevo@example.com']);

    $this->actingAs($stranger)
        ->get('/invitations/valid-token')
        ->assertInertia(fn ($page) => $page
            ->where('state', 'mismatch')
            ->where('currentEmail', 'otro@example.com')
        );

    $this->actingAs($stranger)->post('/invitations/valid-token/accept')->assertForbidden();
    $this->actingAs($stranger)->post('/invitations/valid-token/decline')->assertForbidden();

    expect($invitation->fresh()->status)->toBe(ProjectInvitationStatus::Pending);
    $this->assertDatabaseCount('project_user', 0);
});

test('the invited user declines without joining the project', function () {
    $user = User::factory()->create(['email' => 'nuevo@example.com']);
    $invitation = ProjectInvitation::factory()->withToken('valid-token')->create(['email' => 'nuevo@example.com']);

    $this->actingAs($user)
        ->post('/invitations/valid-token/decline')
        ->assertRedirect(route('home', absolute: false));

    expect($invitation->fresh()->status)->toBe(ProjectInvitationStatus::Declined)
        ->and($invitation->fresh()->responded_at)->not->toBeNull();
    $this->assertDatabaseCount('project_user', 0);
});

test('accepting while already a member does not duplicate the membership', function () {
    $user = User::factory()->create(['email' => 'nuevo@example.com']);
    $project = Project::factory()->hasAttached($user, [], 'members')->create();
    ProjectInvitation::factory()->for($project)->withToken('valid-token')->create(['email' => 'nuevo@example.com']);

    $this->actingAs($user)->post('/invitations/valid-token/accept');

    $this->assertDatabaseCount('project_user', 1);
});

test('signing in from the invitation link returns to the invitation', function () {
    $user = User::factory()->create(['email' => 'nuevo@example.com']);
    ProjectInvitation::factory()->withToken('valid-token')->create(['email' => 'nuevo@example.com']);

    $this->get('/invitations/valid-token');

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('invitations.show', 'valid-token'));
});

test('signing in with google from the invitation link returns to the invitation', function () {
    ProjectInvitation::factory()->withToken('valid-token')->create(['email' => 'nuevo@example.com']);

    $this->get('/invitations/valid-token');

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-123',
        'name' => 'Nuevo',
        'email' => 'nuevo@example.com',
    ]));

    $this->get(route('google.callback', absolute: false))
        ->assertRedirect(route('invitations.show', 'valid-token'));
});

test('owners resend an invitation with a fresh link and validity period', function () {
    Mail::fake();
    $owner = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();
    $invitation = ProjectInvitation::factory()->for($project)->expired()->withToken('old-token')->create([
        'email' => 'nuevo@example.com',
    ]);

    $this->actingAs($owner)
        ->post("/projects/{$project->id}/invitations/{$invitation->id}/resend")
        ->assertSessionHas('success', 'Invitación reenviada a nuevo@example.com.');

    expect(ProjectInvitation::findByToken('old-token'))->toBeNull()
        ->and($invitation->fresh()->isExpired())->toBeFalse()
        ->and($invitation->fresh()->expires_at->isAfter(now()->addDays(6)))->toBeTrue();
    Mail::assertQueued(ProjectInvitationMail::class, fn (ProjectInvitationMail $mail) => $invitation->is(ProjectInvitation::findByToken($mail->token)));
});

test('owners cancel an invitation and its link stops working', function () {
    $owner = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();
    $invitation = ProjectInvitation::factory()->for($project)->withToken('valid-token')->create();

    $this->actingAs($owner)->delete("/projects/{$project->id}/invitations/{$invitation->id}");

    expect($invitation->fresh()->status)->toBe(ProjectInvitationStatus::Cancelled);
    $this->get('/invitations/valid-token')
        ->assertInertia(fn ($page) => $page->where('state', 'unavailable')->where('reason', 'cancelled'));
});

test('only the owner can resend or cancel invitations of their own project', function () {
    Mail::fake();
    $member = User::factory()->create();
    $project = Project::factory()->hasAttached($member, [], 'members')->create();
    $invitation = ProjectInvitation::factory()->for($project)->create();
    $otherProject = Project::factory()->for($project->owner, 'owner')->create();

    $this->actingAs($member)->post("/projects/{$project->id}/invitations/{$invitation->id}/resend")->assertNotFound();
    $this->actingAs($member)->delete("/projects/{$project->id}/invitations/{$invitation->id}")->assertNotFound();
    $this->actingAs($project->owner)->delete("/projects/{$otherProject->id}/invitations/{$invitation->id}")->assertNotFound();

    expect($invitation->fresh()->status)->toBe(ProjectInvitationStatus::Pending);
    Mail::assertNothingOutgoing();
});

test('invitations can not be resent on an inactive project', function () {
    Mail::fake();
    $owner = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create(['status' => ProjectStatus::Archived]);
    $invitation = ProjectInvitation::factory()->for($project)->create();

    $this->actingAs($owner)
        ->post("/projects/{$project->id}/invitations/{$invitation->id}/resend")
        ->assertSessionHas('error', 'No puedes invitar en un proyecto archivado o completado.');

    Mail::assertNothingOutgoing();
});

test('sending invitations is rate limited per user', function () {
    Mail::fake();
    $owner = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();

    foreach (range(1, 20) as $attempt) {
        $this->actingAs($owner)->post("/projects/{$project->id}/members", ['email' => "persona{$attempt}@example.com"]);
    }

    $this->actingAs($owner)
        ->post("/projects/{$project->id}/members", ['email' => 'una-mas@example.com'])
        ->assertTooManyRequests();
});

test('only the owner receives the pending invitations in the project payload', function () {
    $member = User::factory()->create();
    $project = Project::factory()->hasAttached($member, [], 'members')->create();
    ProjectInvitation::factory()->for($project)->create(['email' => 'nuevo@example.com']);

    $this->actingAs($project->owner)
        ->get("/projects/{$project->id}")
        ->assertInertia(fn ($page) => $page
            ->has('project.pendingInvitations', 1)
            ->where('project.pendingInvitations.0.email', 'nuevo@example.com')
            ->where('project.pendingInvitations.0.isExpired', false)
        );

    $this->actingAs($member)
        ->get("/projects/{$project->id}")
        ->assertInertia(fn ($page) => $page->has('project.pendingInvitations', 0));
});
