<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class IssueIntelligenceService
{
    /**
     * @return array{summary: string, suggested_next_action: string, summary_source: string}
     */
    public function generate(string $title, string $description, string $priority, string $category): array
    {
        $aiPayload = $this->fromLlm($title, $description, $priority, $category);

        if ($aiPayload !== null) {
            return [
                'summary' => $aiPayload['summary'],
                'suggested_next_action' => $aiPayload['suggested_next_action'],
                'summary_source' => 'llm',
            ];
        }

        return [
            ...$this->fromRules($description, $priority, $category),
            'summary_source' => 'rules',
        ];
    }

    /**
     * @return array{summary: string, suggested_next_action: string}|null
     */
    private function fromLlm(string $title, string $description, string $priority, string $category): ?array
    {
        $apiKey = (string) config('services.gemini.api_key');
        $configuredModel = (string) config('services.gemini.model', 'gemini-2.0-flash');

        if ($apiKey === '') {
            return null;
        }

        $modelsToTry = collect([
            $configuredModel,
            'gemini-2.0-flash-lite',
            'gemini-flash-latest',
            'gemini-2.0-flash',
            'gemini-2.5-flash',
            'gemini-1.5-flash',
            'gemini-1.5-flash-latest',
        ])->filter(fn (?string $model): bool => is_string($model) && trim($model) !== '')
            ->map(fn (string $model): string => trim($model))
            ->unique()
            ->values();

        $failures = [];

        foreach ($modelsToTry as $model) {
            try {
                $response = Http::timeout(12)
                    ->acceptJson()
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                        'contents' => [
                            [
                                'parts' => [
                                    [
                                        'text' => "You summarize support tickets.\n\nTitle: {$title}\nPriority: {$priority}\nCategory: {$category}\nDescription: {$description}\n\nReturn JSON only with keys: summary and suggested_next_action. Keep each value <= 200 chars.",
                                    ],
                                ],
                            ],
                        ],
                        'generationConfig' => [
                            'temperature' => 0.2,
                        ],
                    ]);

                if (! $response->successful()) {
                    $errorMessage = (string) (Arr::get($response->json(), 'error.message') ?? '');
                    $failures[] = "model={$model}; status={$response->status()}; message={$errorMessage}";
                    continue;
                }

                $content = Arr::get($response->json(), 'candidates.0.content.parts.0.text');

                if (! is_string($content) || trim($content) === '') {
                    $failures[] = "model={$model}; empty content";
                    continue;
                }

                $decoded = $this->decodeJsonPayload($content);

                if (! is_array($decoded)) {
                    $failures[] = "model={$model}; invalid json payload";
                    continue;
                }

                $summary = Str::limit(trim((string) ($decoded['summary'] ?? '')), 200, '...');
                $nextAction = Str::limit(trim((string) ($decoded['suggested_next_action'] ?? '')), 200, '...');

                if ($summary === '' || $nextAction === '') {
                    $failures[] = "model={$model}; missing fields";
                    continue;
                }

                return [
                    'summary' => $summary,
                    'suggested_next_action' => $nextAction,
                ];
            } catch (Throwable $exception) {
                $failures[] = "model={$model}; exception={$exception->getMessage()}";
            }
        }

        if (app()->isLocal()) {
            Log::warning('IssueIntelligenceService Gemini fallback to rules', [
                'reasons' => $failures,
            ]);
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJsonPayload(string $content): ?array
    {
        $trimmed = trim($content);
        $decoded = json_decode($trimmed, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{(?:[^{}]|(?R))*\}/s', $trimmed, $matches) !== 1) {
            return null;
        }

        $decoded = json_decode($matches[0], true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @return array{summary: string, suggested_next_action: string}
     */
    private function fromRules(string $description, string $priority, string $category): array
    {
        $normalizedDescription = trim(preg_replace('/\s+/', ' ', $description) ?? $description);
        $summary = Str::limit($normalizedDescription, 180, '...');

        $defaultAction = match ($category) {
            'billing' => 'Verify invoice/account details, then route to finance support if mismatch is confirmed.',
            'operations' => 'Check runbooks and service health, then assign to the on-call operator if risk persists.',
            'technical' => 'Reproduce the issue and capture logs/trace details before assigning to engineering.',
            default => 'Collect missing context from the reporter and route to the appropriate owner.',
        };

        $priorityPrefix = match ($priority) {
            'critical' => 'Immediate escalation: notify incident channel and assign owner now. ',
            'high' => 'Prioritize this issue in the next triage cycle. ',
            default => '',
        };

        return [
            'summary' => $summary === '' ? 'Issue submitted with limited details.' : $summary,
            'suggested_next_action' => Str::limit($priorityPrefix.$defaultAction, 200, '...'),
        ];
    }
}
