import { Head, Link, router, useForm } from '@inertiajs/react';
import { FormEvent, useEffect, useMemo, useState } from 'react';
import { hrisResourceIcon } from '@/lib/hris-icons';

type Field = {
    name: string;
    label: string;
    type: string;
    required?: boolean;
    options?: { value: number; label: string }[];
};

type HrisValue = string | number | boolean | null;

type HrisRow = {
    id: number;
    [key: string]: HrisValue;
};

type Props = {
    resource: string;
    title: string;
    resources: { key: string; title: string }[];
    fields: Field[];
    canManage: boolean;
    records: {
        data: HrisRow[];
        links: { url: string | null; label: string; active: boolean }[];
    };
};

const moduleDescriptions: Record<string, string> = {
    employees: 'Maintain personnel records and review employee assignments.',
    departments: 'Review municipal offices and maintain department information.',
    attendance: 'Review and maintain employee time and attendance records.',
    'leave-applications': 'Review employee leave requests and their status.',
    vacancies: 'Track open positions and recruitment deadlines.',
};

type FormData = Record<string, string | number | boolean | null>;

const emptyValue = (field: Field): string | number | boolean | null =>
    field.type === 'checkbox' ? false : '';

export default function HrisResourceIndex({
    resource,
    title,
    resources,
    fields,
    canManage,
    records,
}: Props) {
    const [editingId, setEditingId] = useState<number | null>(null);
    const initialData = useMemo(
        () =>
            Object.fromEntries(
                fields.map((field) => [field.name, emptyValue(field)]),
            ) as FormData,
        [fields],
    );
    const form = useForm<FormData>(initialData);

    useEffect(() => {
        form.setData(initialData);
        setEditingId(null);
    }, [resource]);

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const url = `/hris/${resource}`;

        if (editingId) {
            form.put(`${url}/${editingId}`, {
                onSuccess: () => {
                    setEditingId(null);
                    form.reset();
                },
            });
        } else {
            form.post(url, { onSuccess: () => form.reset() });
        }
    };

    const edit = (row: HrisRow) => {
        setEditingId(row.id);
        document.getElementById('record-editor')?.scrollIntoView({ block: 'start' });
        form.setData(
            Object.fromEntries(
                fields.map((field) => [
                    field.name,
                    row[field.name] === null || row[field.name] === undefined
                        ? emptyValue(field)
                        : row[field.name],
                ]),
            ),
        );
    };

    const cancelEdit = () => {
        setEditingId(null);
        form.reset();
        form.clearErrors();
    };

    const remove = (row: HrisRow) => {
        if (window.confirm(`Delete this ${title.toLowerCase()} record?`)) {
            router.delete(`/hris/${resource}/${row.id}`);
        }
    };

    const displayValue = (field: Field, value: HrisValue | undefined) => {
        if (value === null || value === undefined || value === '') return '—';
        if (field.type === 'checkbox') return value ? 'Yes' : 'No';
        if (field.options)
            return (
                field.options.find(
                    (option) => String(option.value) === String(value),
                )?.label ?? String(value)
            );
        return value;
    };

    return (
        <>
            <Head title={title} />
            <div className="mx-auto w-full max-w-7xl space-y-6 p-4 md:p-8">
                <header className="relative isolate overflow-hidden rounded-3xl bg-[#142d1c] px-6 py-8 text-white sm:px-9">
                    <div aria-hidden="true" className="pointer-events-none absolute -top-24 -right-10 -z-10 size-72 rounded-full border border-[#a6eb20]/30" />
                    <div aria-hidden="true" className="pointer-events-none absolute -top-14 right-0 -z-10 size-52 rounded-full bg-[#a6eb20]/10" />
                    <h1 className="text-3xl font-semibold tracking-tight sm:text-4xl">
                        {title}
                    </h1>
                    <p className="mt-2 max-w-xl text-sm leading-6 text-white/80">
                        {moduleDescriptions[resource] ?? 'Manage HRIS records and their linked data.'}
                    </p>
                </header>

                <nav aria-label="HRIS modules" className="flex flex-wrap gap-2">
                    {resources.map((item) => {
                        const Icon = hrisResourceIcon(item.key);

                        return (
                            <Link
                                key={item.key}
                                href={`/hris/${item.key}`}
                                aria-current={resource === item.key ? 'page' : undefined}
                                className="inline-flex items-center gap-2 rounded-full border bg-card px-4 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-muted hover:text-foreground aria-[current=page]:border-primary aria-[current=page]:bg-accent aria-[current=page]:text-accent-foreground"
                            >
                                <Icon aria-hidden="true" className="size-4 shrink-0" />
                                {item.title}
                            </Link>
                        );
                    })}
                </nav>

                {canManage && (
                    <section id="record-editor" className="scroll-mt-20 rounded-2xl border bg-card p-5 sm:p-6">
                        <h2 className="mb-4 text-lg font-medium">
                            {editingId ? 'Edit record' : 'Add record'}
                        </h2>
                        <form
                            onSubmit={submit}
                            className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
                        >
                            {fields.map((field) => (
                                <label
                                    key={field.name}
                                    className={`space-y-1 text-sm ${field.type === 'textarea' ? 'sm:col-span-2 lg:col-span-3' : ''}`}
                                >
                                    <span>
                                        {field.label}
                                        {field.required ? ' *' : ''}
                                    </span>
                                    {field.type === 'select' ? (
                                        <select
                                            value={
                                                form.data[field.name] == null
                                                    ? ''
                                                    : String(
                                                          form.data[field.name],
                                                      )
                                            }
                                            onChange={(event) =>
                                                form.setData(
                                                    field.name,
                                                    event.target.value
                                                        ? Number(
                                                              event.target
                                                                  .value,
                                                          )
                                                        : null,
                                                )
                                            }
                                            required={field.required}
                                            className="w-full rounded-xl border border-input bg-background px-3 py-2 focus-visible:outline-2 focus-visible:outline-ring"
                                        >
                                            <option value="">Select…</option>
                                            {field.options?.map((option) => (
                                                <option
                                                    key={option.value}
                                                    value={option.value}
                                                >
                                                    {option.label}
                                                </option>
                                            ))}
                                        </select>
                                    ) : field.type === 'textarea' ? (
                                        <textarea
                                            value={String(
                                                form.data[field.name] ?? '',
                                            )}
                                            onChange={(event) =>
                                                form.setData(
                                                    field.name,
                                                    event.target.value,
                                                )
                                            }
                                            required={field.required}
                                            className="min-h-24 w-full rounded-xl border border-input bg-background px-3 py-2 focus-visible:outline-2 focus-visible:outline-ring"
                                        />
                                    ) : field.type === 'checkbox' ? (
                                        <input
                                            type="checkbox"
                                            checked={Boolean(
                                                form.data[field.name],
                                            )}
                                            onChange={(event) =>
                                                form.setData(
                                                    field.name,
                                                    event.target.checked,
                                                )
                                            }
                                            className="ml-2 size-4 align-middle accent-primary"
                                        />
                                    ) : (
                                        <input
                                            type={field.type}
                                            step={
                                                field.type === 'number'
                                                    ? 'any'
                                                    : undefined
                                            }
                                            value={(() => {
                                                const value =
                                                    form.data[field.name];

                                                return field.type ===
                                                    'datetime-local' &&
                                                    typeof value === 'string'
                                                    ? value
                                                          .replace(' ', 'T')
                                                          .slice(0, 16)
                                                    : String(value ?? '');
                                            })()}
                                            onChange={(event) =>
                                                form.setData(
                                                    field.name,
                                                    field.type === 'number'
                                                        ? event.target.value ===
                                                          ''
                                                            ? ''
                                                            : Number(
                                                                  event.target
                                                                      .value,
                                                              )
                                                        : event.target.value,
                                                )
                                            }
                                            required={field.required}
                                            className="w-full rounded-xl border border-input bg-background px-3 py-2 focus-visible:outline-2 focus-visible:outline-ring"
                                        />
                                    )}
                                    {form.errors[field.name] && (
                                        <span className="block text-sm text-destructive">
                                            {form.errors[field.name]}
                                        </span>
                                    )}
                                </label>
                            ))}
                            <div className="flex items-center gap-2 sm:col-span-2 lg:col-span-3">
                                <button
                                    type="submit"
                                    disabled={form.processing}
                                    className="rounded-full bg-primary px-5 py-2 font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                                >
                                    {form.processing
                                        ? 'Saving…'
                                        : editingId
                                          ? 'Save changes'
                                          : 'Create record'}
                                </button>
                                {editingId && (
                                    <button
                                        type="button"
                                        onClick={cancelEdit}
                                        className="rounded-full border px-4 py-2 hover:bg-muted"
                                    >
                                        Cancel
                                    </button>
                                )}
                            </div>
                        </form>
                    </section>
                )}

                <section aria-labelledby="records-heading" className="overflow-hidden rounded-2xl border bg-card">
                    <div className="flex flex-wrap items-center justify-between gap-2 px-5 py-4">
                        <h2 id="records-heading" className="text-lg font-semibold tracking-tight">
                            Records
                        </h2>
                        <span className="text-sm text-muted-foreground">
                            {records.data.length} on this page
                        </span>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-muted text-muted-foreground">
                                <tr>
                                    <th className="px-3 py-3 font-medium">
                                        ID
                                    </th>
                                    {fields.map((field) => (
                                        <th
                                            key={field.name}
                                            className="px-3 py-3 font-medium"
                                        >
                                            {field.label}
                                        </th>
                                    ))}
                                    {canManage && (
                                        <th className="px-3 py-3 font-medium">
                                            Actions
                                        </th>
                                    )}
                                </tr>
                            </thead>
                            <tbody>
                                {records.data.map((row) => (
                                    <tr key={row.id} className="border-t transition-colors hover:bg-muted/60">
                                        <td className="px-3 py-3">{row.id}</td>
                                        {fields.map((field) => (
                                            <td
                                                key={field.name}
                                                className="max-w-64 truncate px-3 py-3"
                                            >
                                                {displayValue(
                                                    field,
                                                    row[field.name],
                                                )}
                                            </td>
                                        ))}
                                        {canManage && (
                                            <td className="px-3 py-3 whitespace-nowrap">
                                                <button
                                                    type="button"
                                                    onClick={() => edit(row)}
                                                    className="mr-2 rounded-full border px-3 py-1 font-medium hover:bg-accent hover:text-accent-foreground"
                                                >
                                                    Edit
                                                </button>
                                                <button
                                                    type="button"
                                                    onClick={() => remove(row)}
                                                    className="rounded-full px-3 py-1 font-medium text-destructive hover:bg-destructive/10"
                                                >
                                                    Delete
                                                </button>
                                            </td>
                                        )}
                                    </tr>
                                ))}
                                {records.data.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={
                                                fields.length +
                                                (canManage ? 2 : 1)
                                            }
                                            className="px-3 py-8 text-center text-muted-foreground"
                                        >
                                            No records found.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                    <div className="flex flex-wrap gap-2 border-t p-3">
                        {records.links.map((link, index) => (
                            <Link
                                key={`${link.label}-${index}`}
                                href={link.url ?? '#'}
                                preserveScroll
                                className={`rounded border px-3 py-1 text-sm ${link.active ? 'bg-primary text-primary-foreground' : ''} ${!link.url ? 'pointer-events-none opacity-50' : ''}`}
                            >
                                {link.label
                                    .replace(/&laquo;|&raquo;/g, '')
                                    .trim()}
                            </Link>
                        ))}
                    </div>
                </section>
            </div>
        </>
    );
}
