<?php

use App\Models\User;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withHeader;

test('guests are redirected to login for protected web pages', function () {
    get('/issues')->assertRedirect(route('login'));
    get('/dashboard')->assertRedirect(route('login'));
});

test('root route redirects to login page', function () {
    get('/')->assertRedirect('/login');
});

test('api login returns sanctum token for valid credentials', function () {
    $user = User::factory()->create([
        'password' => 'password',
        'level' => User::LEVEL_ADMIN,
    ]);

    $response = postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'pest-suite',
    ]);

    $response
        ->assertOk()
        ->assertJsonStructure([
            'token',
            'token_type',
            'user' => ['id', 'email', 'level'],
        ])
        ->assertJsonPath('token_type', 'Bearer')
        ->assertJsonPath('user.level', User::LEVEL_ADMIN);
});

test('api me endpoint requires sanctum token', function () {
    getJson('/api/me')->assertUnauthorized();
});

test('api me endpoint returns authenticated user with valid token', function () {
    $user = User::factory()->create(['level' => User::LEVEL_USER]);
    $token = $user->createToken('pest-token')->plainTextToken;

    withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/me')
        ->assertOk()
        ->assertJsonPath('user.email', $user->email)
        ->assertJsonPath('user.level', User::LEVEL_USER);
});

test('api logout revokes current sanctum token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('logout-token')->plainTextToken;

    withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/logout')
        ->assertOk()
        ->assertJsonPath('message', 'Logged out successfully.');

    expect($user->fresh()->tokens()->count())->toBe(0);
});
