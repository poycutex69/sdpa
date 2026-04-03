<?php

use function Pest\Laravel\get;
use function Pest\Laravel\post;

test('verification notification route is unavailable when feature is disabled', function () {
    post('/email/verification-notification')->assertNotFound();
});

test('verification notice route is unavailable when feature is disabled', function () {
    get('/email/verify')->assertNotFound();
});