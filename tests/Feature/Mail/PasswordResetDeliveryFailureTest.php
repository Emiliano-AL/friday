<?php

use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Password;
use Symfony\Component\Mailer\Exception\TransportException;

test('a failed reset email returns to the form with a clear message instead of an error page', function () {
    Password::shouldReceive('sendResetLink')->andThrow(new TransportException('postmark down'));

    $this->from(route('password.request'))
        ->post(route('password.email'), ['email' => 'a@example.com'])
        ->assertRedirect(route('password.request'))
        ->assertSessionHasErrors(['email' => 'No pudimos enviar el correo. Intenta de nuevo en unos minutos.']);
});

test('a failed reset email is reported', function () {
    Exceptions::fake();
    Password::shouldReceive('sendResetLink')->andThrow(new TransportException('postmark down'));

    $this->post(route('password.email'), ['email' => 'a@example.com']);

    Exceptions::assertReported(TransportException::class);
});
