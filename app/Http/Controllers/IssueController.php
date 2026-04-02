<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIssueRequest;
use App\Http\Requests\UpdateIssueRequest;
use App\Models\Issue;
use App\Services\IssueIntelligenceService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class IssueController extends Controller
{
    public function __construct(private readonly IssueIntelligenceService $intelligenceService)
    {
    }

    public function index(): Response
    {
        $filters = request()->only(['status', 'priority', 'category']);

        $issues = Issue::query()
            ->latest()
            ->applyFilters($filters)
            ->get();

        return Inertia::render('issues/index', [
            'issues' => $issues,
            'filters' => $filters,
            'meta' => [
                'priorities' => Issue::PRIORITIES,
                'categories' => Issue::CATEGORIES,
                'statuses' => Issue::STATUSES,
            ],
        ]);
    }

    public function store(StoreIssueRequest $request): RedirectResponse
    {
        Issue::create($this->withIntelligence($request->validated()));

        return back()->with('success', 'Issue submitted.');
    }

    public function update(UpdateIssueRequest $request, Issue $issue): RedirectResponse
    {
        $issue->update($this->withIntelligence($request->validated(), $issue));

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
        $category = (string) ($validated['category'] ?? $existingIssue?->category ?? 'other');

        if ($title === '' || $description === '') {
            return $validated;
        }

        return [
            ...$validated,
            ...$this->intelligenceService->generate($title, $description, $priority, $category),
        ];
    }
}
