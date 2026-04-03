<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $openStatuses = ['new', 'in_progress'];

        $criticalIssuesCount = Issue::query()
            ->where('assigned_to', $request->user()->id)
            ->where('priority', 'critical')
            ->whereIn('status', $openStatuses)
            ->count();

        $assignedIssuesQuery = Issue::query()
            ->where('assigned_to', $request->user()->id)
            ->whereIn('status', $openStatuses);

        $assignedIssuesCount = (clone $assignedIssuesQuery)->count();
        $nearDueIssuesCount = (clone $assignedIssuesQuery)
            ->whereNotNull('due_at')
            ->whereBetween('due_at', [now(), now()->addDay()])
            ->count();
        $pastDueIssuesCount = (clone $assignedIssuesQuery)
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->count();

        $assignedIssues = Issue::query()
            ->with('category:id,name')
            ->where('assigned_to', $request->user()->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get([
                'id',
                'title',
                'priority',
                'status',
                'due_at',
                'created_at',
                'category_id',
            ]);

        $createdIssues = Issue::query()
            ->with('category:id,name')
            ->where('created_by', $request->user()->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get([
                'id',
                'title',
                'priority',
                'status',
                'due_at',
                'created_at',
                'category_id',
            ]);

        return Inertia::render('dashboard', [
            'metrics' => [
                'critical_open_issues' => $criticalIssuesCount,
                'assigned_open_issues' => $assignedIssuesCount,
                'near_due_issues' => $nearDueIssuesCount,
                'past_due_issues' => $pastDueIssuesCount,
            ],
            'assigned_issues' => $assignedIssues,
            'created_issues' => $createdIssues,
        ]);
    }
}
