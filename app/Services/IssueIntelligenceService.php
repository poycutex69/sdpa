<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
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
        $apiKey = (string) config('services.openai.api_key');

        if ($apiKey === '') {
            return null;
        }

        try {
            $response = Http::timeout(12)
                ->acceptJson()
                ->withToken($apiKey)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => config('services.openai.model', 'gpt-4o-mini'),
                    'temperature' => 0.2,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You summarize support tickets. Return strict JSON with keys: summary and suggested_next_action.',
                        ],
                        [
                            'role' => 'user',
                            'content' => "Title: {$title}\nPriority: {$priority}\nCategory: {$category}\nDescription: {$description}\n\nRules: Keep summary <= 200 chars. Keep suggested_next_action <= 200 chars.",
                        ],
                    ],
                ]);

            $response->throw();

            $content = Arr::get($response->json(), 'choices.0.message.content');

            if (! is_string($content) || trim($content) === '') {
                return null;
            }

            $decoded = json_decode(trim($content), true);

            if (! is_array($decoded)) {
                return null;
            }

            $summary = Str::limit(trim((string) ($decoded['summary'] ?? '')), 200, '...');
            $nextAction = Str::limit(trim((string) ($decoded['suggested_next_action'] ?? '')), 200, '...');

            if ($summary === '' || $nextAction === '') {
                return null;
            }

            return [
                'summary' => $summary,
                'suggested_next_action' => $nextAction,
            ];
        } catch (Throwable) {
            return null;
        }
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
