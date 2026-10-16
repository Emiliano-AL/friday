<?php

use App\Mail\ProjectInvitationMail;
use App\Models\ProjectInvitation;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Notification;

test('the invitation email subject names the product as Eunox', function () {
    $mail = new ProjectInvitationMail(ProjectInvitation::factory()->withToken('token')->create(), 'token');

    expect($mail->envelope()->subject)
        ->toEndWith(' en Eunox')
        ->not->toContain('Friday');
});

test('the invitation email body names the product as Eunox', function () {
    $mail = new ProjectInvitationMail(ProjectInvitation::factory()->withToken('token')->create(), 'token');

    expect($mail->render())
        ->toContain('en Eunox.')
        ->not->toContain('Friday');
});

test('emails are sent from Eunox', function () {
    expect(config('mail.from.name'))->toBe('Eunox');
});

test('the password reset email is branded as Eunox', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $notification) use ($user) {
        $html = (string) $notification->toMail($user)->render();

        return str_contains($html, 'Eunox') && ! str_contains($html, 'Laravel');
    });
});
