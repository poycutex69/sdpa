<?php

use function Pest\Laravel\get;
use function Pest\Laravel\post;

test('two factor challenge route is unavailable when feature is disabled', function () {
    get('/two-factor-challenge')->assertNotFound();
});

test('two factor challenge submit route is unavailable when feature is disabled', function () {
    post('/two-factor-challenge', [
        'code' => '000000',
    ])->assertNotFound();
});