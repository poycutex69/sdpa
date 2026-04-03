<?php

use function Pest\Laravel\get;
use function Pest\Laravel\post;

test('forgot password screen route is unavailable when feature is disabled', function () {
    get('/forgot-password')->assertNotFound();
});

test('password reset screen route is unavailable when feature is disabled', function () {
    get('/reset-password/random-token')->assertNotFound();
});

test('password reset post route is unavailable when feature is disabled', function () {
    post('/reset-password', [
        'token' => 'token',
        'email' => 'any@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();
});