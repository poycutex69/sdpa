import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type Issue = {
    id: number;
    title: string;
    description: string;
    priority: string;
    category_id: number | null;
    category: { id: number; name: string } | null;
    status: string;
    due_at: string | null;
    summary: string | null;
    suggested_next_action: string | null;
    summary_source: string;
    requires_escalation: boolean;
    created_at: string;
    assignee: { id: number; name: string } | null;
};

type Filters = {
    status?: string;
    priority?: string;
    category_id?: string;
};

type Meta = {
    priorities: string[];
    categories: { id: number; name: string }[];
    statuses: string[];
    current_user_id: number;
    assignable_users: { id: number; name: string }[];
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
    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
    const createForm = useForm({
        title: '',
        description: '',
        priority: 'medium',
        category_id: meta.categories[0]?.id ? String(meta.categories[0].id) : '',
        assigned_to: String(meta.current_user_id),
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
                createForm.setData(
                    'category_id',
                    meta.categories[0]?.id
                        ? String(meta.categories[0].id)
                        : '',
                );
                createForm.setData('assigned_to', String(meta.current_user_id));
                createForm.setData('status', 'new');
                createForm.clearErrors();
                setIsCreateModalOpen(false);
            },
        });
    };

    const handleCreateModalChange = (open: boolean) => {
        setIsCreateModalOpen(open);

        if (!open) {
            createForm.clearErrors();
        }
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
                <section className="space-y-4">
                    <div className="flex justify-end">
                        <Button type="button" onClick={() => setIsCreateModalOpen(true)}>
                            Create Issue
                        </Button>
                    </div>

                    <div className="grid gap-3 rounded-xl border bg-card p-4 sm:grid-cols-3">
                        <Select
                            value={filters.status ?? 'all'}
                            onValueChange={(value) =>
                                updateFilter(
                                    'status',
                                    value === 'all' ? '' : value,
                                )
                            }
                        >
                            <SelectTrigger className="w-full capitalize">
                                <SelectValue placeholder="All statuses" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All statuses</SelectItem>
                                {meta.statuses.map((status) => (
                                    <SelectItem
                                        key={status}
                                        value={status}
                                        className="capitalize"
                                    >
                                        {status}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>

                        <Select
                            value={filters.priority ?? 'all'}
                            onValueChange={(value) =>
                                updateFilter(
                                    'priority',
                                    value === 'all' ? '' : value,
                                )
                            }
                        >
                            <SelectTrigger className="w-full capitalize">
                                <SelectValue placeholder="All priorities" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All priorities</SelectItem>
                                {meta.priorities.map((priority) => (
                                    <SelectItem
                                        key={priority}
                                        value={priority}
                                        className="capitalize"
                                    >
                                        {priority}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>

                        <Select
                            value={filters.category_id ?? 'all'}
                            onValueChange={(value) =>
                                updateFilter(
                                    'category_id',
                                    value === 'all' ? '' : value,
                                )
                            }
                        >
                            <SelectTrigger className="w-full capitalize">
                                <SelectValue placeholder="All categories" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All categories</SelectItem>
                                {meta.categories.map((category) => (
                                    <SelectItem
                                        key={category.id}
                                        value={String(category.id)}
                                    >
                                        {category.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="space-y-3">
                        {issues.length === 0 ? (
                            <p className="rounded-xl border p-4 text-sm text-muted-foreground">
                                No issues found for the selected filters.
                            </p>
                        ) : (
                            issues.map((issue) => (
                                <article
                                    key={issue.id}
                                    className="rounded-xl border bg-card p-5 shadow-sm"
                                >
                                    <div className="mb-3 flex flex-wrap items-start justify-between gap-3">
                                        <h3 className="max-w-3xl text-lg font-semibold leading-snug">
                                            <Link
                                                href={`/issues/${issue.id}`}
                                                className="hover:underline"
                                            >
                                                {issue.title}
                                            </Link>
                                        </h3>
                                        <div className="flex flex-wrap items-center gap-2">
                                            <span className="rounded-md bg-slate-100 px-2 py-1 text-xs font-medium capitalize dark:bg-slate-800">
                                                {issue.priority}
                                            </span>
                                            <span className="rounded-md bg-slate-100 px-2 py-1 text-xs font-medium capitalize dark:bg-slate-800">
                                                {issue.category?.name ?? 'uncategorized'}
                                            </span>
                                            {issue.requires_escalation && (
                                                <span className="rounded-md bg-red-100 px-2 py-1 text-xs font-semibold text-red-700 dark:bg-red-900/30 dark:text-red-300">
                                                    Escalate
                                                </span>
                                            )}
                                        </div>
                                    </div>

                                    <div className="mb-4 space-y-3 rounded-lg border border-dashed p-3">
                                        <p className="text-sm leading-relaxed">
                                            <span className="font-semibold">Summary:</span>{' '}
                                            {issue.summary ?? 'Not generated'}
                                        </p>
                                        <p className="text-sm leading-relaxed">
                                            <span className="font-semibold">
                                                Suggested next action:
                                            </span>{' '}
                                            {issue.suggested_next_action ?? 'Not generated'}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            Source: {issue.summary_source}
                                        </p>
                                    </div>

                                    <div className="flex flex-wrap items-end justify-between gap-3">
                                        <div className="space-y-1">
                                            {issue.assignee?.name && (
                                                <p className="text-sm text-muted-foreground">
                                                    <span className="font-medium text-foreground">
                                                        Assigned to:
                                                    </span>{' '}
                                                    {issue.assignee.name}
                                                </p>
                                            )}
                                        </div>

                                        <div className="flex flex-wrap items-center gap-2">
                                            <label className="text-sm font-medium">
                                                Status
                                            </label>
                                            <Select
                                                value={issue.status}
                                                onValueChange={(value) =>
                                                    updateStatus(issue.id, value)
                                                }
                                            >
                                                <SelectTrigger className="w-[170px] capitalize">
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {meta.statuses.map((status) => (
                                                        <SelectItem
                                                            key={status}
                                                            value={status}
                                                            className="capitalize"
                                                        >
                                                            {status}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    </div>
                                </article>
                            ))
                        )}
                    </div>
                </section>
            </main>

            <Dialog open={isCreateModalOpen} onOpenChange={handleCreateModalChange}>
                <DialogContent className="sm:max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>Create Issue</DialogTitle>
                        <DialogDescription>
                            Submit a new issue for triage and automated summary generation.
                        </DialogDescription>
                    </DialogHeader>

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
                            <Select
                                value={createForm.data.priority}
                                onValueChange={(value) =>
                                    createForm.setData('priority', value)
                                }
                            >
                                <SelectTrigger className="w-full capitalize">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {meta.priorities.map((priority) => (
                                        <SelectItem
                                            key={priority}
                                            value={priority}
                                            className="capitalize"
                                        >
                                            {priority}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <label className="mb-1 block text-sm font-medium">
                                Category
                            </label>
                            <Select
                                value={createForm.data.category_id}
                                onValueChange={(value) =>
                                    createForm.setData('category_id', value)
                                }
                            >
                                <SelectTrigger className="w-full capitalize">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {meta.categories.map((category) => (
                                        <SelectItem
                                            key={category.id}
                                            value={String(category.id)}
                                        >
                                            {category.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {createForm.errors.category_id && (
                                <p className="mt-1 text-xs text-red-600">
                                    {createForm.errors.category_id}
                                </p>
                            )}
                        </div>

                        <div>
                            <label className="mb-1 block text-sm font-medium">
                                Status
                            </label>
                            <Select
                                value={createForm.data.status}
                                onValueChange={(value) =>
                                    createForm.setData('status', value)
                                }
                            >
                                <SelectTrigger className="w-full capitalize">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {meta.statuses.map((status) => (
                                        <SelectItem
                                            key={status}
                                            value={status}
                                            className="capitalize"
                                        >
                                            {status}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <label className="mb-1 block text-sm font-medium">
                                Assigned To
                            </label>
                            <Select
                                value={createForm.data.assigned_to}
                                onValueChange={(value) =>
                                    createForm.setData('assigned_to', value)
                                }
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {meta.assignable_users.map((user, index) => (
                                        <SelectItem
                                            key={user.id}
                                            value={String(user.id)}
                                        >
                                            {index === 0
                                                ? `Me`
                                                : `${user.name}`}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {createForm.errors.assigned_to && (
                                <p className="mt-1 text-xs text-red-600">
                                    {createForm.errors.assigned_to}
                                </p>
                            )}
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
                </DialogContent>
            </Dialog>
        </>
    );
}

IssueIndex.layout = {
    breadcrumbs: [
        {
            title: 'Issues',
            href: '/issues',
        },
    ],
};
