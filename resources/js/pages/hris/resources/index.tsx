import { Head, Link, router, useForm } from "@inertiajs/react";
import { FormEvent, useEffect, useMemo, useState } from "react";

type Field = {
    name: string;
    label: string;
    type: string;
    required?: boolean;
    options?: { value: number; label: string }[];
};

type HrisValue = string | number | boolean | null | undefined;

type HrisRow = {
    id: number | string;
    canManage?: boolean;
    [key: string]: HrisValue;
};

type Props = {
    resource: string;
    title: string;
    resources: { key: string; title: string }[];
    fields: Field[];
    canManage: boolean;
    canImport?: boolean;
    officeDirectory?: boolean;
    statusSummary?: { status: string; total: number }[];
    records: {
        data: HrisRow[];
        links: { url: string | null; label: string; active: boolean }[];
    };
};

type FormData = Record<string, string | number | boolean | null>;

const emptyValue = (field: Field): string | number | boolean | null =>
    field.type === "checkbox" ? false : "";

export default function HrisResourceIndex({
    resource,
    title,
    resources,
    fields,
    canManage,
    canImport = false,
    officeDirectory = false,
    statusSummary = [],
    records,
}: Props) {
    const [editingId, setEditingId] = useState<number | string | null>(null);
    const initialData = useMemo(
        () =>
            Object.fromEntries(fields.map((field) => [field.name, emptyValue(field)])) as FormData,
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
        if (value === null || value === undefined || value === "") return "—";
        if (field.type === "checkbox") return value ? "Yes" : "No";
        if (field.options)
            return (
                field.options.find((option) => String(option.value) === String(value))?.label ??
                String(value)
            );
        if (resource === "employees" && field.name === "appointment_status") {
            return ({ P: "Permanent", E: "Elected", T: "Temporary", S: "Substitute", CT: "Casual Temporary", PA: "Permanent Acting", FT: "Fixed Term" } as Record<string, string>)[String(value)] ?? String(value);
        }
        if (resource === "employees" && field.name === "employment_status") return String(value).replaceAll("_", " ").replace(/\b\w/g, (letter) => letter.toUpperCase());
        return value;
    };

    return (
        <>
            <Head title={title} />
            <main className="space-y-6 p-4 md:p-8">
                <header>
                    <h1 className="text-2xl font-semibold">{title}</h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Manage HRIS records and their linked data.
                    </p>
                </header>

                <nav aria-label="HRIS modules" className="flex flex-wrap gap-2">
                    {resources.map((item) => (
                        <Link
                            key={item.key}
                            href={`/hris/${item.key}`}
                            className={`rounded-md border px-3 py-2 text-sm ${resource === item.key ? "bg-primary text-primary-foreground" : "hover:bg-muted"}`}
                        >
                            {item.title}
                        </Link>
                    ))}
                </nav>

                {resource === "employees" && statusSummary.length > 0 && (
                    <section aria-label="Employees by status" className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        {statusSummary.map(({ status, total }) => (
                            <div key={status} className="rounded-lg border p-4">
                                <p className="text-sm capitalize text-muted-foreground">{status.replaceAll("_", " ")}</p>
                                <p className="mt-1 text-2xl font-semibold">{total}</p>
                            </div>
                        ))}
                    </section>
                )}

                {canManage && (
                    <section className="rounded-lg border p-4">
                        <h2 className="mb-4 text-lg font-medium">
                            {editingId ? "Edit record" : "Add record"}
                        </h2>
                        <form
                            onSubmit={submit}
                            className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
                        >
                            {fields.map((field) => (
                                <label
                                    key={field.name}
                                    className={`space-y-1 text-sm ${field.type === "textarea" ? "sm:col-span-2 lg:col-span-3" : ""}`}
                                >
                                    <span>
                                        {field.label}
                                        {field.required ? " *" : ""}
                                    </span>
                                    {field.type === "select" ? (
                                        <select
                                            value={
                                                form.data[field.name] == null
                                                    ? ""
                                                    : String(form.data[field.name])
                                            }
                                            onChange={(event) =>
                                                form.setData(
                                                    field.name,
                                                    event.target.value
                                                        ? Number(event.target.value)
                                                        : null,
                                                )
                                            }
                                            required={field.required}
                                            className="w-full rounded-md border bg-background px-3 py-2"
                                        >
                                            <option value="">Select…</option>
                                            {field.options?.map((option) => (
                                                <option key={option.value} value={option.value}>
                                                    {option.label}
                                                </option>
                                            ))}
                                        </select>
                                    ) : field.type === "textarea" ? (
                                        <textarea
                                            value={String(form.data[field.name] ?? "")}
                                            onChange={(event) =>
                                                form.setData(field.name, event.target.value)
                                            }
                                            required={field.required}
                                            className="min-h-24 w-full rounded-md border bg-background px-3 py-2"
                                        />
                                    ) : field.type === "checkbox" ? (
                                        <input
                                            type="checkbox"
                                            checked={Boolean(form.data[field.name])}
                                            onChange={(event) =>
                                                form.setData(field.name, event.target.checked)
                                            }
                                            className="ml-2 size-4 align-middle"
                                        />
                                    ) : (
                                        <input
                                            type={field.type}
                                            step={field.type === "number" ? "any" : undefined}
                                            value={(() => {
                                                const value = form.data[field.name];

                                                return field.type === "datetime-local" &&
                                                    typeof value === "string"
                                                    ? value.replace(" ", "T").slice(0, 16)
                                                    : String(value ?? "");
                                            })()}
                                            onChange={(event) =>
                                                form.setData(
                                                    field.name,
                                                    field.type === "number"
                                                        ? event.target.value === ""
                                                            ? ""
                                                            : Number(event.target.value)
                                                        : event.target.value,
                                                )
                                            }
                                            required={field.required}
                                            className="w-full rounded-md border bg-background px-3 py-2"
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
                                    className="rounded-md bg-primary px-4 py-2 text-primary-foreground disabled:opacity-50"
                                >
                                    {form.processing
                                        ? "Saving…"
                                        : editingId
                                          ? "Save changes"
                                          : "Create record"}
                                </button>
                                {editingId && (
                                    <button
                                        type="button"
                                        onClick={cancelEdit}
                                        className="rounded-md border px-4 py-2"
                                    >
                                        Cancel
                                    </button>
                                )}
                            </div>
                        </form>
                    </section>
                )}

                {resource === "employees" && canImport && (
                    <Link
                        href="/hris/employees/import"
                        className="inline-flex rounded-md border px-3 py-2 text-sm hover:bg-muted"
                    >
                        Bulk import Plantilla Excel
                    </Link>
                )}

                {officeDirectory && (
                    <p className="text-sm text-muted-foreground">
                        Fixed municipal offices from the approved 2025 plantilla.
                    </p>
                )}

                <section className="overflow-hidden rounded-lg border">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-muted/50">
                                <tr>
                                    <th className="px-3 py-3 font-medium">ID</th>
                                    {fields.map((field) => (
                                        <th key={field.name} className="px-3 py-3 font-medium">
                                            {field.label}
                                        </th>
                                    ))}
                                    {canManage && (
                                        <th className="px-3 py-3 font-medium">Actions</th>
                                    )}
                                </tr>
                            </thead>
                            <tbody>
                                {records.data.map((row) => (
                                    <tr key={row.id} className="border-t">
                                        <td className="px-3 py-3">{row.id}</td>
                                        {fields.map((field) => (
                                            <td
                                                key={field.name}
                                                className="max-w-64 truncate px-3 py-3"
                                            >
                                                {resource === "employees" && field.name === "employment_status" ? (
                                                    <span className="inline-flex rounded-full border px-2.5 py-1 text-xs font-medium capitalize">{displayValue(field, row[field.name])}</span>
                                                ) : displayValue(field, row[field.name])}
                                            </td>
                                        ))}
                                        {canManage && row.canManage !== false && (
                                            <td className="px-3 py-3 whitespace-nowrap">
                                                <button
                                                    type="button"
                                                    onClick={() => edit(row)}
                                                    className="mr-3 underline"
                                                >
                                                    Edit
                                                </button>
                                                <button
                                                    type="button"
                                                    onClick={() => remove(row)}
                                                    className="text-destructive underline"
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
                                            colSpan={fields.length + (canManage ? 2 : 1)}
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
                                href={link.url ?? "#"}
                                preserveScroll
                                className={`rounded border px-3 py-1 text-sm ${link.active ? "bg-primary text-primary-foreground" : ""} ${!link.url ? "pointer-events-none opacity-50" : ""}`}
                            >
                                {link.label.replace(/&laquo;|&raquo;/g, "").trim()}
                            </Link>
                        ))}
                    </div>
                </section>
            </main>
        </>
    );
}
