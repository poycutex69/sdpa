<?php

use function Pest\Laravel\get;
use function Pest\Laravel\post;

test('registration screen route is unavailable when feature is disabled', function () {
    get('/register')->assertNotFound();
});

test('registration action route is unavailable when feature is disabled', function () {
    post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();
});