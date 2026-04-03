<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIssueRequest;
use App\Http\Requests\UpdateIssueRequest;
use App\Models\Category;
use App\Models\Issue;
use App\Models\User;
use App\Services\IssueIntelligenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IssueController extends Controller
{
    public function __construct(private readonly IssueIntelligenceService $intelligenceService)
    {
    }

    public function index(): Response
    {
        $currentUser = request()->user();
        $filters = request()->only(['status', 'priority', 'category_id']);

        $issuesQuery = Issue::query()
            ->with(['creator:id,name,email', 'assignee:id,name,email', 'category:id,name'])
            ->latest()
            ->applyFilters($filters);

        if (! $currentUser->isAdmin()) {
            $issuesQuery->where(function ($query) use ($currentUser) {
                $query
                    ->where('assigned_to', $currentUser->id)
                    ->orWhere('created_by', $currentUser->id);
            });
        }

        $issues = $issuesQuery->get();
        $allUsers = User::query()->select('id', 'name')->orderBy('name')->get();
        $assignableUsers = $allUsers
            ->where('id', $currentUser->id)
            ->concat($allUsers->where('id', '!=', $currentUser->id))
            ->values();

        return Inertia::render('issues/index', [
            'issues' => $issues,
            'filters' => $filters,
            'meta' => [
                'priorities' => Issue::PRIORITIES,
                'categories' => Category::query()->select('id', 'name')->orderBy('name')->get(),
                'statuses' => Issue::STATUSES,
                'current_user_id' => $currentUser->id,
                'assignable_users' => $assignableUsers,
            ],
        ]);
    }

    public function store(StoreIssueRequest $request): RedirectResponse
    {
        Issue::create([
            ...$this->withIntelligence($request->validated()),
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Issue submitted.');
    }

    public function show(Request $request, Issue $issue): Response
    {
        $currentUser = $request->user();

        abort_unless($this->canAccessIssue($currentUser, $issue), 403);

        $allUsers = User::query()->select('id', 'name')->orderBy('name')->get();
        $assignableUsers = $allUsers
            ->where('id', $currentUser->id)
            ->concat($allUsers->where('id', '!=', $currentUser->id))
            ->values();

        return Inertia::render('issues/show', [
            'issue' => $issue->load(['creator:id,name,email', 'assignee:id,name,email', 'category:id,name']),
            'meta' => [
                'priorities' => Issue::PRIORITIES,
                'categories' => Category::query()->select('id', 'name')->orderBy('name')->get(),
                'statuses' => Issue::STATUSES,
                'assignable_users' => $assignableUsers,
            ],
            'permissions' => [
                'can_edit_issue' => $this->canEditIssue($currentUser, $issue),
                'can_manage_status' => true,
            ],
        ]);
    }

    public function update(UpdateIssueRequest $request, Issue $issue): RedirectResponse
    {
        $currentUser = $request->user();

        abort_unless($this->canAccessIssue($currentUser, $issue), 403);

        $validated = $request->validated();
        $hasNonStatusField = collect(array_keys($validated))->contains(
            fn (string $field): bool => $field !== 'status'
        );

        if ($hasNonStatusField && ! $this->canEditIssue($currentUser, $issue)) {
            abort(403);
        }

        $issue->update($this->withIntelligence($validated, $issue));

        return back()->with('success', 'Issue updated.');
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function withIntelligence(array $validated, ?Issue $existingIssue = null): array
    {
        $title = (string) ($validated['title'] ?? $existingIssue?->title ?? '');
        $description = (string) ($validated['description'] ?? $existingIssue?->description ?? '');
        $priority = (string) ($validated['priority'] ?? $existingIssue?->priority ?? 'medium');
        $categoryName = $this->resolveCategoryName(
            isset($validated['category_id']) ? (int) $validated['category_id'] : $existingIssue?->category_id,
            $existingIssue
        );

        if ($title === '' || $description === '') {
            return $validated;
        }

        return [
            ...$validated,
            'category' => $categoryName,
            ...$this->intelligenceService->generate($title, $description, $priority, $categoryName),
        ];
    }

    private function resolveCategoryName(?int $categoryId, ?Issue $existingIssue = null): string
    {
        if ($categoryId !== null) {
            return (string) (Category::query()->whereKey($categoryId)->value('name') ?? 'other');
        }

        return (string) ($existingIssue?->category ?? 'other');
    }

    private function canAccessIssue(User $user, Issue $issue): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $issue->created_by === $user->id || $issue->assigned_to === $user->id;
    }

    private function canEditIssue(User $user, Issue $issue): bool
    {
        return $user->isAdmin() || $issue->created_by === $user->id;
    }
}
