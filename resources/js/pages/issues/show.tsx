import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
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
    created_at: string;
    creator: { id: number; name: string; email: string } | null;
    assignee: { id: number; name: string; email: string } | null;
};

type Meta = {
    priorities: string[];
    categories: { id: number; name: string }[];
    statuses: string[];
    assignable_users: { id: number; name: string }[];
};

type Permissions = {
    can_edit_issue: boolean;
    can_manage_status: boolean;
};

function toDateTimeInputValue(dateValue: string | null): string {
    if (!dateValue) {
        return '';
    }

    const date = new Date(dateValue);
    if (Number.isNaN(date.getTime())) {
        return '';
    }

    const local = new Date(date.getTime() - date.getTimezoneOffset() * 60_000);
    return local.toISOString().slice(0, 16);
}

export default function IssueShow({
    issue,
    meta,
    permissions,
}: {
    issue: Issue;
    meta: Meta;
    permissions: Permissions;
}) {
    const statusForm = useForm({
        status: issue.status,
    });

    const editForm = useForm({
        title: issue.title,
        description: issue.description,
        priority: issue.priority,
        category_id: issue.category_id ? String(issue.category_id) : '',
        assigned_to: issue.assignee ? String(issue.assignee.id) : '',
        due_at: toDateTimeInputValue(issue.due_at),
    });

    const submitStatus = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        statusForm.patch(`/issues/${issue.id}`, {
            preserveScroll: true,
        });
    };

    const submitEdit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        editForm.patch(`/issues/${issue.id}`, {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title={`Issue #${issue.id}`} />
            <main className="mx-auto max-w-5xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Issue #{issue.id}
                    </h1>
                    <Button variant="outline" asChild>
                        <Link href="/issues">Back to Issues</Link>
                    </Button>
                </div>

                <section className="rounded-xl border bg-card p-5">
                    <h2 className="text-xl font-semibold">{issue.title}</h2>
                    <p className="mt-3 text-sm leading-relaxed text-muted-foreground">
                        {issue.description}
                    </p>

                    <div className="mt-4 grid gap-2 text-sm sm:grid-cols-2">
                        <p>
                            <span className="font-medium">Priority:</span>{' '}
                            <span className="capitalize">{issue.priority}</span>
                        </p>
                        <p>
                            <span className="font-medium">Category:</span>{' '}
                            {issue.category?.name ?? 'uncategorized'}
                        </p>
                        <p>
                            <span className="font-medium">Created by:</span>{' '}
                            {issue.creator?.name ?? 'Unknown'}
                        </p>
                        <p>
                            <span className="font-medium">Assigned to:</span>{' '}
                            {issue.assignee?.name ?? 'Unassigned'}
                        </p>
                        <p>
                            <span className="font-medium">Due at:</span>{' '}
                            {issue.due_at
                                ? new Date(issue.due_at).toLocaleString()
                                : 'No due date'}
                        </p>
                        <p>
                            <span className="font-medium">Created at:</span>{' '}
                            {new Date(issue.created_at).toLocaleString()}
                        </p>
                    </div>
                </section>

                <section className="rounded-xl border bg-card p-5">
                    <h3 className="text-base font-semibold">AI Summary</h3>
                    <p className="mt-2 text-sm">
                        <span className="font-medium">Summary:</span>{' '}
                        {issue.summary ?? 'Not generated'}
                    </p>
                    <p className="mt-2 text-sm">
                        <span className="font-medium">Suggested next action:</span>{' '}
                        {issue.suggested_next_action ?? 'Not generated'}
                    </p>
                    <p className="mt-2 text-xs text-muted-foreground">
                        Source: {issue.summary_source}
                    </p>
                </section>

                {permissions.can_manage_status && (
                    <section className="rounded-xl border bg-card p-5">
                        <h3 className="text-base font-semibold">Update Status</h3>
                        <form
                            onSubmit={submitStatus}
                            className="mt-3 flex flex-wrap items-end gap-3"
                        >
                            <div className="min-w-[220px]">
                                <label className="mb-1 block text-sm font-medium">
                                    Status
                                </label>
                                <Select
                                    value={statusForm.data.status}
                                    onValueChange={(value) =>
                                        statusForm.setData('status', value)
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
                                {statusForm.errors.status && (
                                    <p className="mt-1 text-xs text-red-600">
                                        {statusForm.errors.status}
                                    </p>
                                )}
                            </div>
                            <Button type="submit" disabled={statusForm.processing}>
                                {statusForm.processing ? 'Saving...' : 'Save Status'}
                            </Button>
                        </form>
                    </section>
                )}

                {permissions.can_edit_issue && (
                    <section className="rounded-xl border bg-card p-5">
                        <h3 className="text-base font-semibold">Edit Issue</h3>
                        <form
                            onSubmit={submitEdit}
                            className="mt-3 grid gap-4 md:grid-cols-2"
                        >
                            <div className="md:col-span-2">
                                <label className="mb-1 block text-sm font-medium">
                                    Title
                                </label>
                                <input
                                    value={editForm.data.title}
                                    onChange={(event) =>
                                        editForm.setData('title', event.target.value)
                                    }
                                    className="w-full rounded-md border px-3 py-2 text-sm"
                                />
                                {editForm.errors.title && (
                                    <p className="mt-1 text-xs text-red-600">
                                        {editForm.errors.title}
                                    </p>
                                )}
                            </div>

                            <div className="md:col-span-2">
                                <label className="mb-1 block text-sm font-medium">
                                    Description
                                </label>
                                <textarea
                                    rows={4}
                                    value={editForm.data.description}
                                    onChange={(event) =>
                                        editForm.setData(
                                            'description',
                                            event.target.value,
                                        )
                                    }
                                    className="w-full rounded-md border px-3 py-2 text-sm"
                                />
                                {editForm.errors.description && (
                                    <p className="mt-1 text-xs text-red-600">
                                        {editForm.errors.description}
                                    </p>
                                )}
                            </div>

                            <div>
                                <label className="mb-1 block text-sm font-medium">
                                    Priority
                                </label>
                                <Select
                                    value={editForm.data.priority}
                                    onValueChange={(value) =>
                                        editForm.setData('priority', value)
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
                                    value={editForm.data.category_id}
                                    onValueChange={(value) =>
                                        editForm.setData('category_id', value)
                                    }
                                >
                                    <SelectTrigger className="w-full">
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
                                {editForm.errors.category_id && (
                                    <p className="mt-1 text-xs text-red-600">
                                        {editForm.errors.category_id}
                                    </p>
                                )}
                            </div>

                            <div>
                                <label className="mb-1 block text-sm font-medium">
                                    Assigned To
                                </label>
                                <Select
                                    value={editForm.data.assigned_to}
                                    onValueChange={(value) =>
                                        editForm.setData('assigned_to', value)
                                    }
                                >
                                    <SelectTrigger className="w-full">
                                        <SelectValue placeholder="Unassigned" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {meta.assignable_users.map((user) => (
                                            <SelectItem
                                                key={user.id}
                                                value={String(user.id)}
                                            >
                                                {user.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {editForm.errors.assigned_to && (
                                    <p className="mt-1 text-xs text-red-600">
                                        {editForm.errors.assigned_to}
                                    </p>
                                )}
                            </div>

                            <div>
                                <label className="mb-1 block text-sm font-medium">
                                    Due Date
                                </label>
                                <input
                                    type="datetime-local"
                                    value={editForm.data.due_at}
                                    onChange={(event) =>
                                        editForm.setData('due_at', event.target.value)
                                    }
                                    className="w-full rounded-md border px-3 py-2 text-sm"
                                />
                                {editForm.errors.due_at && (
                                    <p className="mt-1 text-xs text-red-600">
                                        {editForm.errors.due_at}
                                    </p>
                                )}
                            </div>

                            <div className="md:col-span-2">
                                <Button type="submit" disabled={editForm.processing}>
                                    {editForm.processing
                                        ? 'Saving...'
                                        : 'Save Issue Changes'}
                                </Button>
                            </div>
                        </form>
                    </section>
                )}
            </main>
        </>
    );
}

IssueShow.layout = {
    breadcrumbs: [
        {
            title: 'Issues',
            href: '/issues',
        },
    ],
};
