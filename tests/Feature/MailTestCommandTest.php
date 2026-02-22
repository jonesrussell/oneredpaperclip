<?php

test('mail test command fails when not in production', function () {
    $this->artisan('mail:test', ['email' => 'test@example.com'])
        ->assertFailed();
});

test('mail test command sends test email with force option', function () {
    config(['mail.default' => 'array']);

    $this->artisan('mail:test', ['email' => 'test@example.com', '--force' => true])
        ->assertSuccessful();

    $transport = app('mailer')->getSymfonyTransport();
    expect($transport->messages())->toHaveCount(1);

    $sent = $transport->messages()[0];
    $original = $sent->getOriginalMessage();
    expect($original->getTo())->toHaveCount(1);
    expect($original->getTo()[0]->getAddress())->toBe('test@example.com');
    expect($original->getSubject())->toContain('Mail test');
});
