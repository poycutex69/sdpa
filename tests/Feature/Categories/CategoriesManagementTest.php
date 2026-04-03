<?php

use App\Models\Category;
use App\Models\User;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\patch;
use function Pest\Laravel\post;

test('guests are redirected to login for categories page', function () {
    get('/categories')->assertRedirect(route('login'));
});

test('normal users cannot access categories page', function () {
    $user = User::factory()->create(['level' => User::LEVEL_USER]);

    actingAs($user)->get('/categories')->assertForbidden();
});

test('admin can view categories page', function () {
    $admin = User::factory()->create(['level' => User::LEVEL_ADMIN]);

    actingAs($admin)->get('/categories')->assertOk();
});

test('admin can create category', function () {
    $admin = User::factory()->create(['level' => User::LEVEL_ADMIN]);

    actingAs($admin)->post('/categories', [
        'name' => 'compliance',
    ])->assertRedirect();

    assertDatabaseHas('categories', [
        'name' => 'compliance',
    ]);
});

test('admin can update category', function () {
    $admin = User::factory()->create(['level' => User::LEVEL_ADMIN]);
    $category = Category::query()->create(['name' => 'legacy']);

    actingAs($admin)->patch("/categories/{$category->id}", [
        'name' => 'legacy-updated',
    ])->assertRedirect();

    assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => 'legacy-updated',
    ]);
});

test('admin can delete category', function () {
    $admin = User::factory()->create(['level' => User::LEVEL_ADMIN]);
    $category = Category::query()->create(['name' => 'to-delete']);

    actingAs($admin)->delete("/categories/{$category->id}")->assertRedirect();

    assertDatabaseMissing('categories', [
        'id' => $category->id,
    ]);
});

test('normal users cannot update category', function () {
    $user = User::factory()->create(['level' => User::LEVEL_USER]);
    $category = Category::query()->create(['name' => 'protected-update']);

    actingAs($user)->patch("/categories/{$category->id}", [
        'name' => 'attempted-update',
    ])->assertForbidden();
});

test('normal users cannot delete category', function () {
    $user = User::factory()->create(['level' => User::LEVEL_USER]);
    $category = Category::query()->create(['name' => 'protected-delete']);

    actingAs($user)->delete("/categories/{$category->id}")
        ->assertForbidden();
});
