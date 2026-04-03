<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Issue;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;

class IssueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::query()->orderBy('id')->get();

        if ($users->isEmpty()) {
            return;
        }

        $categoryNames = ['technical', 'billing', 'operations', 'other'];
        $categoryIdsByName = collect($categoryNames)
            ->mapWithKeys(fn (string $name): array => [
                $name => Category::query()->firstOrCreate(['name' => $name])->id,
            ]);

        $priorityOptions = ['low', 'medium', 'high', 'critical'];
        $statusOptions = ['new', 'in_progress', 'resolved', 'closed'];
        $subjects = [
            'Checkout flow',
            'Invoice reconciliation',
            'Data export',
            'Email notifications',
            'Agent dashboard',
            'Ticket assignment',
            'Role permissions',
            'Webhook processing',
            'Queue worker',
            'Search indexing',
        ];

        for ($index = 1; $index <= 50; $index++) {
            $creator = $users[($index - 1) % $users->count()];
            $assignee = $users[$index % $users->count()];
            $category = $categoryNames[($index - 1) % count($categoryNames)];
            $priority = $priorityOptions[($index - 1) % count($priorityOptions)];
            $status = $statusOptions[($index - 1) % count($statusOptions)];
            $subject = $subjects[($index - 1) % count($subjects)];

            Issue::query()->create([
                'created_by' => $creator->id,
                'assigned_to' => $assignee->id,
                'category' => $category,
                'category_id' => $categoryIdsByName[$category],
                'title' => sprintf('%s issue #%02d', $subject, $index),
                'description' => sprintf(
                    'Issue %02d affects %s for active users. Symptoms were reported by support and require follow-up.',
                    $index,
                    strtolower($subject)
                ),
                'priority' => $priority,
                'status' => $status,
                'due_at' => $this->dueAtFor($index, $status),
                'summary' => sprintf('%s issue #%02d requires investigation by the operations team.', $subject, $index),
                'suggested_next_action' => $this->nextActionFor($priority, $status),
                'summary_source' => 'rules',
            ]);
        }
    }

    private function dueAtFor(int $index, string $status): ?CarbonInterface
    {
        if (in_array($status, ['resolved', 'closed'], true) && $index % 3 === 0) {
            return null;
        }

        $hourOffset = ($index % 2 === 0) ? 6 + ($index % 18) : -1 * (4 + ($index % 30));

        return now()->addHours($hourOffset);
    }

    private function nextActionFor(string $priority, string $status): string
    {
        if ($status === 'new') {
            return 'Acknowledge the issue, gather logs, and assign ownership.';
        }

        if ($status === 'in_progress' && in_array($priority, ['high', 'critical'], true)) {
            return 'Escalate to on-call engineer and post an internal progress update.';
        }

        if ($status === 'in_progress') {
            return 'Continue investigation, document findings, and prepare a fix.';
        }

        return 'Validate completion notes and confirm no further action is required.';
    }
}
