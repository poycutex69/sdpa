<?php

use App\Models\Category;
use App\Models\Issue;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('guest is redirected when viewing a single issue page', function () {
    $issue = Issue::query()->create([
        'title' => 'Issue for guest redirect',
        'description' => 'A sample issue for guest redirect behavior.',
        'priority' => 'medium',
        'category' => 'technical',
        'status' => 'new',
    ]);

    get(route('issues.show', $issue))->assertRedirect(route('login'));
});

test('non related normal user cannot view another users issue page', function () {
    $creator = User::factory()->create(['level' => User::LEVEL_USER]);
    $otherUser = User::factory()->create(['level' => User::LEVEL_USER]);
    $issue = Issue::query()->create([
        'created_by' => $creator->id,
        'assigned_to' => null,
        'title' => 'Restricted issue',
        'description' => 'This issue should not be visible to unrelated users.',
        'priority' => 'high',
        'category' => 'operations',
        'status' => 'new',
    ]);

    actingAs($otherUser)
        ->get(route('issues.show', $issue))
        ->assertForbidden();
});

test('assignee can view issue and update status only', function () {
    $creator = User::factory()->create(['level' => User::LEVEL_USER]);
    $assignee = User::factory()->create(['level' => User::LEVEL_USER]);
    $category = Category::query()->create(['name' => 'billing']);
    $issue = Issue::query()->create([
        'created_by' => $creator->id,
        'assigned_to' => $assignee->id,
        'title' => 'Assigned issue',
        'description' => 'An issue assigned to another normal user.',
        'priority' => 'medium',
        'category' => 'billing',
        'category_id' => $category->id,
        'status' => 'new',
    ]);

    actingAs($assignee)
        ->get(route('issues.show', $issue))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('issues/show')
            ->where('permissions.can_edit_issue', false)
            ->where('permissions.can_manage_status', true),
        );

    actingAs($assignee)
        ->patch(route('issues.update', $issue), [
            'status' => 'in_progress',
        ])
        ->assertRedirect();

    expect($issue->refresh()->status)->toBe('in_progress');
});

test('assignee cannot edit non status fields', function () {
    $creator = User::factory()->create(['level' => User::LEVEL_USER]);
    $assignee = User::factory()->create(['level' => User::LEVEL_USER]);
    $issue = Issue::query()->create([
        'created_by' => $creator->id,
        'assigned_to' => $assignee->id,
        'title' => 'Not editable by assignee',
        'description' => 'Only status updates should be allowed for assignee.',
        'priority' => 'low',
        'category' => 'technical',
        'status' => 'new',
    ]);

    actingAs($assignee)
        ->patch(route('issues.update', $issue), [
            'title' => 'Changed title',
        ])
        ->assertForbidden();

    expect($issue->refresh()->title)->toBe('Not editable by assignee');
});

test('author can edit full issue details', function () {
    $author = User::factory()->create(['level' => User::LEVEL_USER]);
    $assignee = User::factory()->create(['level' => User::LEVEL_USER]);
    $category = Category::query()->create(['name' => 'technical']);
    $issue = Issue::query()->create([
        'created_by' => $author->id,
        'assigned_to' => $assignee->id,
        'title' => 'Author editable issue',
        'description' => 'This issue should be editable by its author.',
        'priority' => 'medium',
        'category' => 'technical',
        'category_id' => $category->id,
        'status' => 'new',
    ]);

    actingAs($author)
        ->patch(route('issues.update', $issue), [
            'title' => 'Updated by author',
            'description' => 'Author updated issue content with enough details.',
            'priority' => 'high',
            'category_id' => $category->id,
            'assigned_to' => $author->id,
            'due_at' => now()->addDay()->toDateTimeString(),
        ])
        ->assertRedirect();

    $issue->refresh();
    expect($issue->title)->toBe('Updated by author')
        ->and($issue->priority)->toBe('high')
        ->and($issue->assigned_to)->toBe($author->id);
});

test('admin can view and edit any issue', function () {
    $admin = User::factory()->create(['level' => User::LEVEL_ADMIN]);
    $author = User::factory()->create(['level' => User::LEVEL_USER]);
    $issue = Issue::query()->create([
        'created_by' => $author->id,
        'assigned_to' => null,
        'title' => 'Admin managed issue',
        'description' => 'Admin should be able to update any issue.',
        'priority' => 'critical',
        'category' => 'operations',
        'status' => 'new',
    ]);

    actingAs($admin)
        ->get(route('issues.show', $issue))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('issues/show')
            ->where('permissions.can_edit_issue', true)
            ->where('permissions.can_manage_status', true),
        );

    actingAs($admin)
        ->patch(route('issues.update', $issue), [
            'title' => 'Updated by admin',
        ])
        ->assertRedirect();

    expect($issue->refresh()->title)->toBe('Updated by admin');
});
