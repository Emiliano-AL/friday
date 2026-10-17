<?php

use App\Mail\ProjectInvitationMail;
use App\Models\ProjectInvitation;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;

test('the invitation email header shows the Eunox logo', function () {
    $html = (new ProjectInvitationMail(ProjectInvitation::factory()->withToken('token')->create(), 'token'))->render();

    expect($html)->toContain('images/eunox-mail-logo.png')
        ->toContain('alt="Eunox"');
});

test('the password reset email header shows the Eunox logo', function () {
    $html = (string) (new ResetPasswordNotification('token'))->toMail(User::factory()->create())->render();

    expect($html)->toContain('images/eunox-mail-logo.png')
        ->toContain('alt="Eunox"');
});

test('the mail logo image is 96 pixels square', function () {
    [$width, $height] = getimagesize(public_path('images/eunox-mail-logo.png'));

    expect([$width, $height])->toBe([96, 96]);
});
