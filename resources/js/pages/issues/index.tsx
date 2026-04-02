import { Head, router, useForm } from '@inertiajs/react';

type Issue = {
    id: number;
    title: string;
    description: string;
    priority: string;
    category: string;
    status: string;
    due_at: string | null;
    summary: string | null;
    suggested_next_action: string | null;
    summary_source: string;
    requires_escalation: boolean;
    created_at: string;
};

type Filters = {
    status?: string;
    priority?: string;
    category?: string;
};

type Meta = {
    priorities: string[];
    categories: string[];
    statuses: string[];
};

export default function IssueIndex({
    issues,
    filters,
    meta,
}: {
    issues: Issue[];
    filters: Filters;
    meta: Meta;
}) {
    const createForm = useForm({
        title: '',
        description: '',
        priority: 'medium',
        category: 'technical',
        status: 'new',
        due_at: '',
    });

    const submitIssue = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        createForm.post('/issues', {
            preserveScroll: true,
            onSuccess: () => {
                createForm.reset('title', 'description', 'due_at');
                createForm.setData('priority', 'medium');
                createForm.setData('category', 'technical');
                createForm.setData('status', 'new');
            },
        });
    };

    const updateFilter = (key: keyof Filters, value: string) => {
        const nextFilters = {
            ...filters,
            [key]: value || undefined,
        };

        router.get('/issues', nextFilters, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const updateStatus = (issueId: number, status: string) => {
        router.patch(
            `/issues/${issueId}`,
            { status },
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    return (
        <>
            <Head title="Issue Intake" />

            <main className="mx-auto max-w-6xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
                <section className="space-y-1">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Issue Intake and Smart Summary
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Submit support issues, auto-generate summaries, and
                        triage by priority/status/category.
                    </p>
                </section>

                <section className="rounded-xl border bg-card p-4">
                    <h2 className="mb-4 text-lg font-medium">Create Issue</h2>
                    <form onSubmit={submitIssue} className="grid gap-4 md:grid-cols-2">
                        <div className="md:col-span-2">
                            <label className="mb-1 block text-sm font-medium">
                                Title
                            </label>
                            <input
                                value={createForm.data.title}
                                onChange={(event) => createForm.setData('title', event.target.value)}
                                className="w-full rounded-md border px-3 py-2 text-sm"
                                placeholder="Brief issue title"
                            />
                            {createForm.errors.title && (
                                <p className="mt-1 text-xs text-red-600">
                                    {createForm.errors.title}
                                </p>
                            )}
                        </div>

                        <div className="md:col-span-2">
                            <label className="mb-1 block text-sm font-medium">
                                Description
                            </label>
                            <textarea
                                value={createForm.data.description}
                                onChange={(event) =>
                                    createForm.setData('description', event.target.value)
                                }
                                rows={4}
                                className="w-full rounded-md border px-3 py-2 text-sm"
                                placeholder="Add details, impact, and any relevant context"
                            />
                            {createForm.errors.description && (
                                <p className="mt-1 text-xs text-red-600">
                                    {createForm.errors.description}
                                </p>
                            )}
                        </div>

                        <div>
                            <label className="mb-1 block text-sm font-medium">
                                Priority
                            </label>
                            <select
                                value={createForm.data.priority}
                                onChange={(event) =>
                                    createForm.setData('priority', event.target.value)
                                }
                                className="w-full rounded-md border px-3 py-2 text-sm capitalize"
                            >
                                {meta.priorities.map((priority) => (
                                    <option key={priority} value={priority}>
                                        {priority}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label className="mb-1 block text-sm font-medium">
                                Category
                            </label>
                            <select
                                value={createForm.data.category}
                                onChange={(event) =>
                                    createForm.setData('category', event.target.value)
                                }
                                className="w-full rounded-md border px-3 py-2 text-sm capitalize"
                            >
                                {meta.categories.map((category) => (
                                    <option key={category} value={category}>
                                        {category}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label className="mb-1 block text-sm font-medium">
                                Status
                            </label>
                            <select
                                value={createForm.data.status}
                                onChange={(event) =>
                                    createForm.setData('status', event.target.value)
                                }
                                className="w-full rounded-md border px-3 py-2 text-sm capitalize"
                            >
                                {meta.statuses.map((status) => (
                                    <option key={status} value={status}>
                                        {status}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label className="mb-1 block text-sm font-medium">
                                Due Date (optional)
                            </label>
                            <input
                                type="datetime-local"
                                value={createForm.data.due_at}
                                onChange={(event) => createForm.setData('due_at', event.target.value)}
                                className="w-full rounded-md border px-3 py-2 text-sm"
                            />
                        </div>

                        <div className="md:col-span-2">
                            <button
                                type="submit"
                                disabled={createForm.processing}
                                className="rounded-md bg-black px-4 py-2 text-sm font-medium text-white hover:opacity-90 disabled:opacity-50 dark:bg-white dark:text-black"
                            >
                                {createForm.processing ? 'Submitting...' : 'Submit Issue'}
                            </button>
                        </div>
                    </form>
                </section>

                <section className="space-y-4">
                    <div className="grid gap-3 rounded-xl border bg-card p-4 sm:grid-cols-3">
                        <select
                            value={filters.status ?? ''}
                            onChange={(event) => updateFilter('status', event.target.value)}
                            className="w-full rounded-md border px-3 py-2 text-sm capitalize"
                        >
                            <option value="">All statuses</option>
                            {meta.statuses.map((status) => (
                                <option key={status} value={status}>
                                    {status}
                                </option>
                            ))}
                        </select>

                        <select
                            value={filters.priority ?? ''}
                            onChange={(event) => updateFilter('priority', event.target.value)}
                            className="w-full rounded-md border px-3 py-2 text-sm capitalize"
                        >
                            <option value="">All priorities</option>
                            {meta.priorities.map((priority) => (
                                <option key={priority} value={priority}>
                                    {priority}
                                </option>
                            ))}
                        </select>

                        <select
                            value={filters.category ?? ''}
                            onChange={(event) => updateFilter('category', event.target.value)}
                            className="w-full rounded-md border px-3 py-2 text-sm capitalize"
                        >
                            <option value="">All categories</option>
                            {meta.categories.map((category) => (
                                <option key={category} value={category}>
                                    {category}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="space-y-3">
                        {issues.length === 0 ? (
                            <p className="rounded-xl border p-4 text-sm text-muted-foreground">
                                No issues found for the selected filters.
                            </p>
                        ) : (
                            issues.map((issue) => (
                                <article key={issue.id} className="rounded-xl border bg-card p-4">
                                    <div className="mb-2 flex flex-wrap items-center justify-between gap-2">
                                        <h3 className="text-base font-semibold">
                                            {issue.title}
                                        </h3>
                                        <div className="flex items-center gap-2">
                                            <span className="rounded bg-slate-100 px-2 py-1 text-xs capitalize dark:bg-slate-800">
                                                {issue.priority}
                                            </span>
                                            <span className="rounded bg-slate-100 px-2 py-1 text-xs capitalize dark:bg-slate-800">
                                                {issue.category}
                                            </span>
                                            {issue.requires_escalation && (
                                                <span className="rounded bg-red-100 px-2 py-1 text-xs font-semibold text-red-700 dark:bg-red-900/30 dark:text-red-300">
                                                    Escalate
                                                </span>
                                            )}
                                        </div>
                                    </div>

                                    <p className="mb-3 text-sm text-muted-foreground">
                                        {issue.description}
                                    </p>

                                    <div className="mb-3 grid gap-2 text-sm">
                                        <p>
                                            <span className="font-medium">Summary:</span>{' '}
                                            {issue.summary ?? 'Not generated'}
                                        </p>
                                        <p>
                                            <span className="font-medium">Suggested next action:</span>{' '}
                                            {issue.suggested_next_action ?? 'Not generated'}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            Source: {issue.summary_source}
                                        </p>
                                    </div>

                                    <div className="flex flex-wrap items-center gap-2">
                                        <label className="text-sm font-medium">
                                            Status
                                        </label>
                                        <select
                                            value={issue.status}
                                            onChange={(event) =>
                                                updateStatus(issue.id, event.target.value)
                                            }
                                            className="rounded-md border px-2 py-1 text-sm capitalize"
                                        >
                                            {meta.statuses.map((status) => (
                                                <option key={status} value={status}>
                                                    {status}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                </article>
                            ))
                        )}
                    </div>
                </section>
            </main>
        </>
    );
}
