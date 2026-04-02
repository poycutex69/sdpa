<?php

namespace Database\Seeders;

use App\Models\Issue;
use Illuminate\Database\Seeder;

class IssueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = [
            [
                'title' => 'Checkout page returns 500',
                'description' => 'Customers hit a 500 error when placing an order with coupon codes since yesterday.',
                'priority' => 'critical',
                'category' => 'technical',
                'status' => 'in_progress',
                'due_at' => now()->subHours(2),
                'summary' => 'Order checkout fails with 500 when coupon logic runs.',
                'suggested_next_action' => 'Escalate to on-call engineer, inspect checkout logs, and hotfix failing coupon validator.',
                'summary_source' => 'rules',
            ],
            [
                'title' => 'Invoice mismatch for enterprise account',
                'description' => 'Client reports invoice amount is higher than contracted seats by 5 licenses.',
                'priority' => 'high',
                'category' => 'billing',
                'status' => 'new',
                'due_at' => now()->addHours(12),
                'summary' => 'Enterprise invoice appears to overcharge by 5 licenses.',
                'suggested_next_action' => 'Validate seat history and billing plan changes, then issue correction or explanation.',
                'summary_source' => 'rules',
            ],
            [
                'title' => 'Daily ETL job delayed',
                'description' => 'Ops reports ETL completed 90 minutes late for two consecutive days.',
                'priority' => 'medium',
                'category' => 'operations',
                'status' => 'in_progress',
                'due_at' => now()->addDay(),
                'summary' => 'Nightly ETL job is repeatedly delayed and impacting reporting freshness.',
                'suggested_next_action' => 'Review scheduler logs, queue depth, and upstream dependencies to isolate delay.',
                'summary_source' => 'rules',
            ],
        ];

        foreach ($rows as $row) {
            Issue::query()->create($row);
        }
    }
}
