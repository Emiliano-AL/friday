<?php

use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Bridge\Postmark\Transport\PostmarkApiTransport;

test('the postmark mailer resolves the native Postmark API transport', function () {
    config(['services.postmark.key' => 'POSTMARK_API_TEST']);

    expect(Mail::mailer('postmark')->getSymfonyTransport())->toBeInstanceOf(PostmarkApiTransport::class);
});

test('the postmark mailer uses the transactional stream and a bounded timeout', function () {
    expect(config('mail.mailers.postmark.message_stream_id'))->toBe('outbound')
        ->and(config('mail.mailers.postmark.client.timeout'))->toBe(5);
});
