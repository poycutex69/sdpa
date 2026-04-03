<?php

use App\Services\IssueIntelligenceService;
use Illuminate\Support\Facades\Http;

it('falls back to rules when no gemini api key is configured', function () {
    config([
        'services.gemini.api_key' => '',
    ]);

    $payload = app(IssueIntelligenceService::class)->generate(
        'Checkout failure',
        'Users receive an exception while paying with saved cards.',
        'critical',
        'technical'
    );

    expect($payload['summary_source'])->toBe('rules')
        ->and($payload['summary'])->not->toBe('')
        ->and($payload['suggested_next_action'])->toContain('Immediate escalation');
});

it('uses llm output when gemini returns valid json', function () {
    config([
        'services.gemini.api_key' => 'test-key',
        'services.gemini.model' => 'gemini-1.5-flash',
    ]);

    Http::fake([
        'https://generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [[
                            'text' => json_encode([
                                'summary' => 'Checkout flow fails for saved cards.',
                                'suggested_next_action' => 'Inspect payment gateway logs and deploy rollback if needed.',
                            ]),
                        ]],
                    ],
                ],
            ],
        ], 200),
    ]);

    $payload = app(IssueIntelligenceService::class)->generate(
        'Checkout failure',
        'Users receive an exception while paying with saved cards.',
        'high',
        'technical'
    );

    expect($payload['summary_source'])->toBe('llm')
        ->and($payload['summary'])->toBe('Checkout flow fails for saved cards.')
        ->and($payload['suggested_next_action'])->toBe('Inspect payment gateway logs and deploy rollback if needed.');
});

it('parses llm json from fenced content', function () {
    config([
        'services.gemini.api_key' => 'test-key',
    ]);

    Http::fake([
        'https://generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [[
                            'text' => <<<TEXT
```json
{"summary":"Billing amount mismatch for enterprise account.","suggested_next_action":"Validate seat count history and issue corrected invoice if needed."}
```
TEXT,
                        ]],
                    ]
                ],
            ],
        ], 200),
    ]);

    $payload = app(IssueIntelligenceService::class)->generate(
        'Invoice mismatch',
        'Client reports invoice amount is higher than contracted seats.',
        'medium',
        'billing'
    );

    expect($payload['summary_source'])->toBe('llm')
        ->and($payload['summary'])->toBe('Billing amount mismatch for enterprise account.');
});

it('falls back to rules when llm output is invalid', function () {
    config([
        'services.gemini.api_key' => 'test-key',
    ]);

    Http::fake([
        'https://generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [[
                            'text' => 'Not valid json',
                        ]],
                    ]
                ],
            ],
        ], 200),
    ]);

    $payload = app(IssueIntelligenceService::class)->generate(
        'ETL delay',
        'Nightly ETL has been delayed by 90 minutes for two days.',
        'high',
        'operations'
    );

    expect($payload['summary_source'])->toBe('rules')
        ->and($payload['suggested_next_action'])->toContain('Prioritize this issue');
});
