<?php

use App\Mail\ProjectInvitationMail;
use App\Models\ProjectInvitation;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Notification;

test('the invitation email is tagged project-invitation', function () {
    $mail = new ProjectInvitationMail(ProjectInvitation::factory()->withToken('token')->create(), 'token');

    expect($mail->envelope()->tags)->toBe(['project-invitation']);
});

test('the password reset email is tagged password-reset', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPasswordNotification::class, fn (ResetPasswordNotification $notification) => $notification->toMail($user)->tags === ['password-reset']);
});
