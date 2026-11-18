import { Head, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type PreviewRow = {
    row: number;
    employee_number: string;
    name: string;
    department: string;
    position: string;
    status: string;
    action: string;
    valid: boolean;
    errors: string[];
};
type Credential = {
    employee_number: string;
    name: string;
    temporary_password: string;
};
type Result = {
    createdProfiles: number;
    updatedProfiles: number;
    createdAccounts: number;
    emailSetupSent: number;
};
type TemplateColumn = { key: string; label: string };

export default function ImportEmployees({
    rows = [],
    token,
    result,
    credentials = [],
    statusOptions = ['active', 'on leave', 'inactive', 'terminated'],
    columns = [],
}: {
    rows?: PreviewRow[];
    token?: string;
    result?: Result;
    credentials?: Credential[];
    statusOptions?: string[];
    columns?: TemplateColumn[];
}) {
    const [previewed, setPreviewed] = useState(Boolean(token));
    const [editTemplate, setEditTemplate] = useState(false);
    const upload = useForm<{ file: File | null }>({ file: null });
    const apply = useForm({ token: token ?? '', approval_reference: '' });
    const templateForm = useForm({ columns, status_options: statusOptions });
    const [newColumn, setNewColumn] = useState('');
    const requiredColumns = [
        'employee_number',
        'first_name',
        'last_name',
        'employment_status',
    ];

    function preview(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        upload.post('/hris/employees/import/preview', {
            forceFormData: true,
            onSuccess: () => setPreviewed(true),
        });
    }

    function commit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        apply.transform((data) => ({ ...data, token: token ?? '' }));
        apply.post('/hris/employees/import');
    }

    return (
        <>
            <Head title="Bulk import employees" />
            <main className="mx-auto w-full max-w-6xl space-y-6 p-4 md:p-8">
                <header>
                    <h1 className="text-2xl font-semibold">
                        Bulk import employees
                    </h1>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Import employee records and create employee sign-in
                        accounts from the EMPLOYEES worksheet. This workflow
                        does not add Plantilla positions.
                    </p>
                </header>

                {result ? (
                    <section className="space-y-4 rounded-xl border p-5">
                        <h2 className="text-lg font-semibold">
                            Import complete
                        </h2>
                        <p className="text-sm">
                            Employee records created: {result.createdProfiles};
                            updated: {result.updatedProfiles}. Login accounts
                            created: {result.createdAccounts}.
                        </p>
                        {credentials.length > 0 && (
                            <div className="space-y-3 rounded-lg border border-amber-500/40 bg-amber-500/5 p-4">
                                <h3 className="font-medium">
                                    One-time credentials for employees without
                                    email
                                </h3>
                                <p className="text-sm text-muted-foreground">
                                    Copy these now and share them with each
                                    employee privately. They must change the
                                    temporary password and set up two-factor
                                    authentication at first sign-in. These
                                    passwords are not saved for later viewing.
                                </p>
                                <div className="overflow-x-auto">
                                    <table className="w-full text-left text-sm">
                                        <thead>
                                            <tr>
                                                <th className="p-2">
                                                    Employee Number
                                                </th>
                                                <th className="p-2">Name</th>
                                                <th className="p-2">
                                                    Temporary Password
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {credentials.map((credential) => (
                                                <tr
                                                    key={
                                                        credential.employee_number
                                                    }
                                                    className="border-t"
                                                >
                                                    <td className="p-2">
                                                        {
                                                            credential.employee_number
                                                        }
                                                    </td>
                                                    <td className="p-2">
                                                        {credential.name}
                                                    </td>
                                                    <td className="p-2 font-mono">
                                                        {
                                                            credential.temporary_password
                                                        }
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        )}
                        <p className="text-sm text-muted-foreground">
                            Account setup links sent by email:{' '}
                            {result.emailSetupSent}. If delivery failed, the
                            temporary password appears in the one-time
                            credentials table above.
                        </p>
                    </section>
                ) : (
                    <>
                        <section className="flex flex-wrap items-center justify-between gap-4 rounded-xl border p-4">
                            <div>
                                <h2 className="font-medium">
                                    Employee import template
                                </h2>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Employee Number, First Name, Last Name, and
                                    Employment Status are required. Email is
                                    optional.
                                </p>
                            </div>
                            <div className="flex gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => {
                                        templateForm.setData({
                                            columns,
                                            status_options: statusOptions,
                                        });
                                        setNewColumn('');
                                        setEditTemplate(true);
                                    }}
                                >
                                    Edit template
                                </Button>
                                <a
                                    href="/hris/employees/import/template"
                                    className="inline-flex h-9 items-center rounded-md border px-4 text-sm font-medium hover:bg-accent"
                                >
                                    Download Excel template
                                </a>
                            </div>
                        </section>
                        <section className="space-y-2 rounded-xl border p-4 text-sm">
                            <h2 className="font-medium">
                                Required spreadsheet columns
                            </h2>
                            <p className="text-muted-foreground">
                                Required: Employee Number, First Name, Last
                                Name, and Employment Status. Use one employee
                                per row and keep ID values as text. Email is
                                optional.
                            </p>
                        </section>
                        {editTemplate && (
                            <div
                                className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                                role="presentation"
                                onMouseDown={(event) => {
                                    if (event.target === event.currentTarget)
                                        setEditTemplate(false);
                                }}
                            >
                                <section
                                    role="dialog"
                                    aria-modal="true"
                                    aria-labelledby="template-editor-title"
                                    className="w-full max-w-2xl space-y-4 rounded-xl border bg-background p-6 shadow-xl"
                                >
                                    <div>
                                        <h2
                                            id="template-editor-title"
                                            className="text-lg font-semibold"
                                        >
                                            Edit employee spreadsheet columns
                                        </h2>
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            Rename, add, or remove columns.
                                            Required columns cannot be removed.
                                            Changes apply to future downloads
                                            and imports.
                                        </p>
                                    </div>
                                    <div className="max-h-72 space-y-2 overflow-y-auto pr-1">
                                        {templateForm.data.columns.map(
                                            (column, index) => (
                                                <div
                                                    key={`${column.key}-${index}`}
                                                    className="flex items-center gap-2"
                                                >
                                                    <Input
                                                        aria-label={`Column ${index + 1} name`}
                                                        value={column.label}
                                                        onChange={(event) =>
                                                            templateForm.setData(
                                                                'columns',
                                                                templateForm.data.columns.map(
                                                                    (
                                                                        item,
                                                                        itemIndex,
                                                                    ) =>
                                                                        itemIndex ===
                                                                        index
                                                                            ? {
                                                                                  ...item,
                                                                                  label: event
                                                                                      .target
                                                                                      .value,
                                                                              }
                                                                            : item,
                                                                ),
                                                            )
                                                        }
                                                    />
                                                    {requiredColumns.includes(
                                                        column.key,
                                                    ) ? (
                                                        <span className="text-xs text-muted-foreground">
                                                            Required
                                                        </span>
                                                    ) : (
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() =>
                                                                templateForm.setData(
                                                                    'columns',
                                                                    templateForm.data.columns.filter(
                                                                        (
                                                                            _,
                                                                            itemIndex,
                                                                        ) =>
                                                                            itemIndex !==
                                                                            index,
                                                                    ),
                                                                )
                                                            }
                                                        >
                                                            Remove
                                                        </Button>
                                                    )}
                                                </div>
                                            ),
                                        )}
                                    </div>
                                    <div className="flex gap-2">
                                        <Input
                                            aria-label="New column name"
                                            placeholder="Add a column"
                                            value={newColumn}
                                            onChange={(event) =>
                                                setNewColumn(event.target.value)
                                            }
                                            onKeyDown={(event) => {
                                                if (event.key === 'Enter') {
                                                    event.preventDefault();
                                                    if (newColumn.trim()) {
                                                        templateForm.setData(
                                                            'columns',
                                                            [
                                                                ...templateForm
                                                                    .data
                                                                    .columns,
                                                                {
                                                                    key: '',
                                                                    label: newColumn.trim(),
                                                                },
                                                            ],
                                                        );
                                                        setNewColumn('');
                                                    }
                                                }
                                            }}
                                        />
                                        <Button
                                            type="button"
                                            variant="outline"
                                            disabled={!newColumn.trim()}
                                            onClick={() => {
                                                templateForm.setData(
                                                    'columns',
                                                    [
                                                        ...templateForm.data
                                                            .columns,
                                                        {
                                                            key: '',
                                                            label: newColumn.trim(),
                                                        },
                                                    ],
                                                );
                                                setNewColumn('');
                                            }}
                                        >
                                            Add column
                                        </Button>
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="status-options">
                                            Employment Status choices
                                        </Label>
                                        <textarea
                                            id="status-options"
                                            value={templateForm.data.status_options.join(
                                                '\n',
                                            )}
                                            onChange={(event) =>
                                                templateForm.setData(
                                                    'status_options',
                                                    event.target.value
                                                        .split(/\r?\n/)
                                                        .map((item) =>
                                                            item.trim(),
                                                        )
                                                        .filter(Boolean),
                                                )
                                            }
                                            className="min-h-20 w-full rounded-md border bg-background p-3 text-sm"
                                        />
                                        <p className="text-xs text-muted-foreground">
                                            One choice per line; up to 8
                                            choices, 30 characters each.
                                        </p>
                                    </div>
                                    <InputError
                                        message={
                                            templateForm.errors.columns as
                                                | string
                                                | undefined
                                        }
                                    />
                                    <div className="flex justify-end gap-2">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() =>
                                                setEditTemplate(false)
                                            }
                                        >
                                            Cancel
                                        </Button>
                                        <Button
                                            type="button"
                                            disabled={templateForm.processing}
                                            onClick={() =>
                                                templateForm.put(
                                                    '/hris/employees/import/template',
                                                    {
                                                        onSuccess: () =>
                                                            setEditTemplate(
                                                                false,
                                                            ),
                                                    },
                                                )
                                            }
                                        >
                                            Save template
                                        </Button>
                                    </div>
                                </section>
                            </div>
                        )}
                        <form
                            onSubmit={preview}
                            className="space-y-4 rounded-xl border p-4"
                        >
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2 md:col-span-2">
                                    <Label htmlFor="employee-file">
                                        Employee workbook (.xlsx)
                                    </Label>
                                    <Input
                                        id="employee-file"
                                        type="file"
                                        accept=".xlsx"
                                        required
                                        onChange={(event) =>
                                            upload.setData(
                                                'file',
                                                event.target.files?.[0] ?? null,
                                            )
                                        }
                                    />
                                    {upload.data.file && (
                                        <div className="flex items-center gap-3 text-sm">
                                            <span className="truncate">
                                                {upload.data.file.name}
                                            </span>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() => {
                                                    upload.setData(
                                                        'file',
                                                        null,
                                                    );
                                                    const input =
                                                        document.getElementById(
                                                            'employee-file',
                                                        ) as HTMLInputElement | null;
                                                    if (input) input.value = '';
                                                }}
                                            >
                                                Remove file
                                            </Button>
                                        </div>
                                    )}
                                    <InputError message={upload.errors.file} />
                                </div>
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Add one employee per row under the headers on
                                the EMPLOYEES sheet. Keep employee numbers,
                                TINs, and other ID fields as text to preserve
                                leading zeros.
                            </p>
                            <Button disabled={upload.processing}>
                                {upload.processing
                                    ? 'Checking workbook…'
                                    : 'Preview import'}
                            </Button>
                        </form>
                        {previewed && token && (
                            <>
                                <section className="space-y-3">
                                    <div>
                                        <h2 className="text-lg font-semibold">
                                            Review {rows.length} rows
                                        </h2>
                                        <p className="text-sm text-muted-foreground">
                                            No records are changed until you
                                            apply this preview.
                                        </p>
                                    </div>
                                    <div className="overflow-x-auto rounded-xl border">
                                        <table className="w-full text-left text-sm">
                                            <thead className="bg-muted">
                                                <tr>
                                                    {[
                                                        'Excel row',
                                                        'Employee Number',
                                                        'Name',
                                                        'Department',
                                                        'Position',
                                                        'Employment Status',
                                                        'Action',
                                                        'Validation',
                                                    ].map((heading) => (
                                                        <th
                                                            key={heading}
                                                            className="p-3"
                                                        >
                                                            {heading}
                                                        </th>
                                                    ))}
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {rows.map((row) => (
                                                    <tr
                                                        key={row.row}
                                                        className="border-t align-top"
                                                    >
                                                        <td className="p-3">
                                                            {row.row}
                                                        </td>
                                                        <td className="p-3">
                                                            {
                                                                row.employee_number
                                                            }
                                                        </td>
                                                        <td className="p-3">
                                                            {row.name}
                                                        </td>
                                                        <td className="p-3">
                                                            {row.department}
                                                        </td>
                                                        <td className="p-3">
                                                            {row.position}
                                                        </td>
                                                        <td className="p-3">
                                                            {row.status}
                                                        </td>
                                                        <td className="p-3">
                                                            {row.action}
                                                        </td>
                                                        <td className="p-3">
                                                            {row.valid
                                                                ? 'Ready'
                                                                : row.errors.join(
                                                                      ' ',
                                                                  )}
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                </section>
                                <form
                                    onSubmit={commit}
                                    className="flex flex-wrap items-end gap-3 rounded-xl border p-4"
                                >
                                    <input
                                        type="hidden"
                                        value={token}
                                        readOnly
                                    />
                                    <div className="min-w-64 flex-1 space-y-2">
                                        <Label htmlFor="approval-reference">
                                            Approval reference
                                        </Label>
                                        <Input
                                            id="approval-reference"
                                            required
                                            value={
                                                apply.data.approval_reference
                                            }
                                            onChange={(event) =>
                                                apply.setData(
                                                    'approval_reference',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={
                                                apply.errors
                                                    .approval_reference ??
                                                apply.errors.token
                                            }
                                        />
                                    </div>
                                    <Button
                                        disabled={
                                            apply.processing ||
                                            rows.length === 0 ||
                                            rows.some((row) => !row.valid)
                                        }
                                    >
                                        {apply.processing
                                            ? 'Applying…'
                                            : 'Apply import'}
                                    </Button>
                                    {rows.some((row) => !row.valid) && (
                                        <p className="w-full text-sm text-destructive">
                                            Fix all row errors, then upload the
                                            corrected workbook and preview
                                            again.
                                        </p>
                                    )}
                                </form>
                            </>
                        )}
                    </>
                )}
            </main>
        </>
    );
}
