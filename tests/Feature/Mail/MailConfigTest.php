<?php

use Illuminate\Support\Facades\File;

test('tests never use a real mail provider', function () {
    expect(config('mail.default'))->toBe('array');
});

test('the mailer defaults to log when the environment does not choose one', function () {
    expect(File::get(config_path('mail.php')))->toContain("env('MAIL_MAILER', 'log')");
});

// The project's config files are read directly: at runtime Laravel merges the
// framework's inert default mailers and services back into config().
test('only the supported mailers are configured', function () {
    $mailers = array_keys((require config_path('mail.php'))['mailers']);
    sort($mailers);

    expect($mailers)->toBe(['array', 'log', 'postmark', 'smtp']);
});

test('postmark is the only configured email service', function () {
    $services = require config_path('services.php');

    expect($services)
        ->not->toHaveKey('ses')
        ->not->toHaveKey('resend')
        ->and($services['postmark'])->toHaveKey('key');
});
