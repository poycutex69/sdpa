<?php

use function Pest\Laravel\get;

test('email verification notice route is unavailable when feature is disabled', function () {
    get('/email/verify')->assertNotFound();
});

test('email verification action route is unavailable when feature is disabled', function () {
    get('/email/verify/1/test-hash')->assertNotFound();
});