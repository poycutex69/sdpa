<?php

use App\Models\Category;
use App\Models\Issue;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use function Pest\Laravel\actingAs;

test('admin can view all issues on issues page', function () {
    $admin = User::factory()->create([
        'level' => User::LEVEL_ADMIN,
    ]);
    $normalUser = User::factory()->create([
        'level' => User::LEVEL_USER,
    ]);
    $category = Category::query()->create([
        'name' => 'technical',
    ]);

    $issueAssignedToAdmin = Issue::query()->create([
        'created_by' => $normalUser->id,
        'assigned_to' => $admin->id,
        'title' => 'Assigned to admin',
        'description' => 'A sample issue assigned to admin user.',
        'priority' => 'high',
        'category' => 'technical',
        'category_id' => $category->id,
        'status' => 'new',
    ]);

    $issueAssignedToNormalUser = Issue::query()->create([
        'created_by' => $normalUser->id,
        'assigned_to' => $normalUser->id,
        'title' => 'Assigned to normal user',
        'description' => 'A sample issue assigned to normal user.',
        'priority' => 'medium',
        'category' => 'technical',
        'category_id' => $category->id,
        'status' => 'in_progress',
    ]);

    actingAs($admin)
        ->get(route('issues.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('issues/index')
            ->where('issues', fn ($issues): bool => collect($issues)
                ->pluck('id')
                ->sort()
                ->values()
                ->all() === collect([$issueAssignedToAdmin->id, $issueAssignedToNormalUser->id])->sort()->values()->all()),
        );
});

test('normal user only sees assigned or created issues on issues page', function () {
    $normalUser = User::factory()->create([
        'level' => User::LEVEL_USER,
    ]);
    $otherUser = User::factory()->create([
        'level' => User::LEVEL_USER,
    ]);
    $category = Category::query()->create([
        'name' => 'billing',
    ]);

    $createdByCurrentUser = Issue::query()->create([
        'created_by' => $normalUser->id,
        'assigned_to' => $otherUser->id,
        'title' => 'Created by current user',
        'description' => 'A sample issue created by the current user.',
        'priority' => 'low',
        'category' => 'billing',
        'category_id' => $category->id,
        'status' => 'new',
    ]);

    $assignedToCurrentUser = Issue::query()->create([
        'created_by' => $otherUser->id,
        'assigned_to' => $normalUser->id,
        'title' => 'Assigned to current user',
        'description' => 'A sample issue assigned to the current user.',
        'priority' => 'critical',
        'category' => 'billing',
        'category_id' => $category->id,
        'status' => 'in_progress',
    ]);

    Issue::query()->create([
        'created_by' => $otherUser->id,
        'assigned_to' => $otherUser->id,
        'title' => 'Hidden from current user',
        'description' => 'This issue should not be visible to the current user.',
        'priority' => 'high',
        'category' => 'billing',
        'category_id' => $category->id,
        'status' => 'new',
    ]);

    actingAs($normalUser)
        ->get(route('issues.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('issues/index')
            ->where('issues', fn ($issues): bool => collect($issues)
                ->pluck('id')
                ->sort()
                ->values()
                ->all() === collect([$createdByCurrentUser->id, $assignedToCurrentUser->id])->sort()->values()->all()),
        );
});
