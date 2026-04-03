<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIssueRequest;
use App\Http\Requests\UpdateIssueRequest;
use App\Models\Category;
use App\Models\Issue;
use App\Services\IssueIntelligenceService;
use Illuminate\Http\JsonResponse;

class IssueApiController extends Controller
{
    public function __construct(private readonly IssueIntelligenceService $intelligenceService)
    {
    }

    public function index(): JsonResponse
    {
        $filters = request()->only(['status', 'priority', 'category_id']);

        $issues = Issue::query()
            ->with(['creator:id,name,email', 'assignee:id,name,email', 'category:id,name'])
            ->latest()
            ->applyFilters($filters)
            ->get();

        return response()->json([
            'data' => $issues,
            'meta' => [
                'filters' => $filters,
                'allowed' => [
                    'priorities' => Issue::PRIORITIES,
                    'categories' => Category::query()->select('id', 'name')->orderBy('name')->get(),
                    'statuses' => Issue::STATUSES,
                ],
            ],
        ]);
    }

    public function store(StoreIssueRequest $request): JsonResponse
    {
        $issue = Issue::create([
            ...$this->withIntelligence($request->validated()),
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['data' => $issue], 201);
    }

    public function show(Issue $issue): JsonResponse
    {
        return response()->json([
            'data' => $issue->load(['creator:id,name,email', 'assignee:id,name,email', 'category:id,name']),
        ]);
    }

    public function update(UpdateIssueRequest $request, Issue $issue): JsonResponse
    {
        $issue->update($this->withIntelligence($request->validated(), $issue));
        $issue->refresh();

        return response()->json([
            'data' => $issue->load(['creator:id,name,email', 'assignee:id,name,email', 'category:id,name']),
        ]);
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
}
