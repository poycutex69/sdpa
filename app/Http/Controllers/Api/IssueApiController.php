<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIssueRequest;
use App\Http\Requests\UpdateIssueRequest;
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
        $filters = request()->only(['status', 'priority', 'category']);

        $issues = Issue::query()
            ->latest()
            ->applyFilters($filters)
            ->get();

        return response()->json([
            'data' => $issues,
            'meta' => [
                'filters' => $filters,
                'allowed' => [
                    'priorities' => Issue::PRIORITIES,
                    'categories' => Issue::CATEGORIES,
                    'statuses' => Issue::STATUSES,
                ],
            ],
        ]);
    }

    public function store(StoreIssueRequest $request): JsonResponse
    {
        $issue = Issue::create($this->withIntelligence($request->validated()));

        return response()->json(['data' => $issue], 201);
    }

    public function show(Issue $issue): JsonResponse
    {
        return response()->json(['data' => $issue]);
    }

    public function update(UpdateIssueRequest $request, Issue $issue): JsonResponse
    {
        $issue->update($this->withIntelligence($request->validated(), $issue));
        $issue->refresh();

        return response()->json(['data' => $issue]);
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
