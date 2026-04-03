import { Head, Link } from '@inertiajs/react';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import { dashboard } from '@/routes';

type DashboardMetrics = {
    critical_open_issues: number;
    assigned_open_issues: number;
    near_due_issues: number;
    past_due_issues: number;
};

type DashboardIssue = {
    id: number;
    title: string;
    priority: 'low' | 'medium' | 'high' | 'critical';
    status: 'new' | 'in_progress' | 'resolved' | 'closed';
    due_at: string | null;
    created_at: string;
    category: { id: number; name: string } | null;
};

export default function Dashboard({
    metrics,
    assigned_issues,
    created_issues,
}: {
    metrics: DashboardMetrics;
    assigned_issues: DashboardIssue[];
    created_issues: DashboardIssue[];
}) {
    const onTrackIssues = Math.max(
        metrics.assigned_open_issues -
            metrics.near_due_issues -
            metrics.past_due_issues,
        0,
    );

    const chartData = [
        { label: 'On Track', value: onTrackIssues, color: '#22c55e' },
        { label: 'Near Due', value: metrics.near_due_issues, color: '#f59e0b' },
        { label: 'Past Due', value: metrics.past_due_issues, color: '#ef4444' },
    ];

    const totalForChart = Math.max(
        chartData.reduce((sum, item) => sum + item.value, 0),
        1,
    );
    const radius = 44;
    const circumference = 2 * Math.PI * radius;
    let cumulativeOffset = 0;

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="grid auto-rows-min gap-4 md:grid-cols-2">
                    <div className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <p className="text-sm text-muted-foreground">
                            My Critical Open Issues
                        </p>
                        <p className="mt-2 text-4xl font-semibold tracking-tight">
                            {metrics.critical_open_issues}
                        </p>
                        <p className="mt-2 text-xs text-muted-foreground">
                            Critical issues assigned to you with status new or in
                            progress.
                        </p>
                    </div>

                    <div className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <p className="text-sm text-muted-foreground">
                            My Assigned Open Issues
                        </p>
                        <p className="mt-2 text-4xl font-semibold tracking-tight">
                            {metrics.assigned_open_issues}
                        </p>

                        <div className="mt-3 flex items-center gap-4">
                            <svg width="120" height="120" viewBox="0 0 120 120" className="shrink-0">
                                <circle
                                    cx="60"
                                    cy="60"
                                    r={radius}
                                    stroke="#e5e7eb"
                                    strokeWidth="12"
                                    fill="none"
                                />
                                {chartData.map((item) => {
                                    const dashLength =
                                        (item.value / totalForChart) *
                                        circumference;
                                    const segment = (
                                        <circle
                                            key={item.label}
                                            cx="60"
                                            cy="60"
                                            r={radius}
                                            stroke={item.color}
                                            strokeWidth="12"
                                            fill="none"
                                            strokeDasharray={`${dashLength} ${circumference}`}
                                            strokeDashoffset={-cumulativeOffset}
                                            transform="rotate(-90 60 60)"
                                            strokeLinecap="butt"
                                        />
                                    );
                                    cumulativeOffset += dashLength;
                                    return segment;
                                })}
                            </svg>

                            <div className="space-y-1 text-xs">
                                {chartData.map((item) => (
                                    <p key={item.label} className="flex items-center gap-2">
                                        <span
                                            className="inline-block size-2 rounded-full"
                                            style={{ backgroundColor: item.color }}
                                        />
                                        <span>{item.label}:</span>
                                        <span className="font-semibold">{item.value}</span>
                                    </p>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
                <div className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <div className="mb-3 flex items-center justify-between">
                        <h2 className="text-base font-semibold">My Issue Lists</h2>
                        <span className="text-xs text-muted-foreground">
                            Showing up to 5 records per list
                        </span>
                    </div>

                    <div className="grid gap-4 lg:grid-cols-2">
                        <div>
                            <h3 className="mb-2 text-sm font-medium">
                                Assigned Issues To Me
                            </h3>
                            {assigned_issues.length === 0 ? (
                                <div className="relative min-h-48 overflow-hidden rounded-lg border border-dashed">
                                    <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                                    <p className="relative z-10 p-4 text-sm text-muted-foreground">
                                        No issues are currently assigned to you.
                                    </p>
                                </div>
                            ) : (
                                <ul className="space-y-2">
                                    {assigned_issues.map((issue) => (
                                        <li
                                            key={issue.id}
                                            className="rounded-lg border px-3 py-2"
                                        >
                                            <div className="flex flex-wrap items-center justify-between gap-2">
                                                <p className="font-medium">
                                                    <Link
                                                        href={`/issues/${issue.id}`}
                                                        className="hover:underline"
                                                    >
                                                        {issue.title}
                                                    </Link>
                                                </p>
                                                <div className="flex items-center gap-2 text-xs">
                                                    <span className="rounded bg-slate-100 px-2 py-1 capitalize dark:bg-slate-800">
                                                        {issue.priority}
                                                    </span>
                                                    <span className="rounded bg-slate-100 px-2 py-1 capitalize dark:bg-slate-800">
                                                        {issue.status.replace(
                                                            '_',
                                                            ' ',
                                                        )}
                                                    </span>
                                                </div>
                                            </div>
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                Due:{' '}
                                                {issue.due_at
                                                    ? new Date(
                                                          issue.due_at,
                                                      ).toLocaleString()
                                                    : 'No due date'}
                                            </p>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </div>

                        <div>
                            <h3 className="mb-2 text-sm font-medium">
                                Created By Me (Newest First)
                            </h3>
                            {created_issues.length === 0 ? (
                                <div className="relative min-h-48 overflow-hidden rounded-lg border border-dashed">
                                    <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                                    <p className="relative z-10 p-4 text-sm text-muted-foreground">
                                        You have not created any issues yet.
                                    </p>
                                </div>
                            ) : (
                                <ul className="space-y-2">
                                    {created_issues.map((issue) => (
                                        <li
                                            key={issue.id}
                                            className="rounded-lg border px-3 py-2"
                                        >
                                            <div className="flex flex-wrap items-center justify-between gap-2">
                                                <p className="font-medium">
                                                    <Link
                                                        href={`/issues/${issue.id}`}
                                                        className="hover:underline"
                                                    >
                                                        {issue.title}
                                                    </Link>
                                                </p>
                                                <div className="flex items-center gap-2 text-xs">
                                                    <span className="rounded bg-slate-100 px-2 py-1 capitalize dark:bg-slate-800">
                                                        {issue.priority}
                                                    </span>
                                                    <span className="rounded bg-slate-100 px-2 py-1 capitalize dark:bg-slate-800">
                                                        {issue.status.replace(
                                                            '_',
                                                            ' ',
                                                        )}
                                                    </span>
                                                    <span className="rounded bg-slate-100 px-2 py-1 capitalize dark:bg-slate-800">
                                                        {issue.category?.name ??
                                                            'uncategorized'}
                                                    </span>
                                                </div>
                                            </div>
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                Created:{' '}
                                                {new Date(
                                                    issue.created_at,
                                                ).toLocaleString()}
                                            </p>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
