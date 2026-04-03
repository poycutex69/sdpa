import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

type Category = {
    id: number;
    name: string;
    created_at: string;
};

export default function CategoriesIndex({
    categories,
}: {
    categories: Category[];
}) {
    const [editingCategoryId, setEditingCategoryId] = useState<number | null>(null);
    const form = useForm({
        name: '',
    });

    const submit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (editingCategoryId !== null) {
            form.patch(`/categories/${editingCategoryId}`, {
                preserveScroll: true,
                onSuccess: () => {
                    form.reset('name');
                    setEditingCategoryId(null);
                },
            });

            return;
        }

        form.post('/categories', {
            preserveScroll: true,
            onSuccess: () => form.reset('name'),
        });
    };

    const startEdit = (category: Category) => {
        setEditingCategoryId(category.id);
        form.setData('name', category.name);
        form.clearErrors('name');
    };

    const cancelEdit = () => {
        setEditingCategoryId(null);
        form.reset('name');
        form.clearErrors('name');
    };

    const deleteCategory = (category: Category) => {
        const shouldDelete = window.confirm(
            `Delete "${category.name}"? Issues in this category will become uncategorized.`,
        );

        if (!shouldDelete) {
            return;
        }

        router.delete(`/categories/${category.id}`, {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Categories" />
            <main className="mx-auto max-w-4xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
                <section>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Manage Categories
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Admin-only page for creating issue categories.
                    </p>
                </section>

                <section className="rounded-xl border bg-card p-4">
                    <h2 className="mb-3 text-lg font-medium">
                        {editingCategoryId === null
                            ? 'Add Category'
                            : 'Edit Category'}
                    </h2>
                    <form onSubmit={submit} className="flex flex-col gap-3 sm:flex-row">
                        <input
                            value={form.data.name}
                            onChange={(event) => form.setData('name', event.target.value)}
                            className="w-full rounded-md border px-3 py-2 text-sm"
                            placeholder="Category name"
                        />
                        <button
                            type="submit"
                            disabled={form.processing}
                            className="rounded-md bg-black px-4 py-2 text-sm font-medium text-white hover:opacity-90 disabled:opacity-50 dark:bg-white dark:text-black"
                        >
                            {form.processing ? 'Saving...' : 'Save'}
                        </button>
                        {editingCategoryId !== null && (
                            <button
                                type="button"
                                onClick={cancelEdit}
                                className="rounded-md border px-4 py-2 text-sm font-medium hover:bg-muted"
                            >
                                Cancel
                            </button>
                        )}
                    </form>
                    {form.errors.name && (
                        <p className="mt-2 text-xs text-red-600">{form.errors.name}</p>
                    )}
                </section>

                <section className="rounded-xl border bg-card p-4">
                    <h2 className="mb-3 text-lg font-medium">Existing Categories</h2>
                    {categories.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No categories yet.
                        </p>
                    ) : (
                        <ul className="space-y-2">
                            {categories.map((category) => (
                                <li
                                    key={category.id}
                                    className="flex items-center justify-between gap-3 rounded-md border px-3 py-2 text-sm"
                                >
                                    <span>{category.name}</span>
                                    <div className="flex items-center gap-2">
                                        <button
                                            type="button"
                                            onClick={() => startEdit(category)}
                                            className="rounded border px-2 py-1 text-xs hover:bg-muted"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => deleteCategory(category)}
                                            className="rounded border border-red-300 px-2 py-1 text-xs text-red-600 hover:bg-red-50 dark:border-red-800 dark:hover:bg-red-900/20"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
            </main>
        </>
    );
}

CategoriesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Categories',
            href: '/categories',
        },
    ],
};
