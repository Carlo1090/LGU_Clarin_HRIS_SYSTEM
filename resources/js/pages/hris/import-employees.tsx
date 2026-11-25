import { downloadEmployeeImportErrors } from '@/lib/employee-import-errors';
import { useTableSearch } from '@/components/table-search';
import WorkspaceHeader from '@/components/workspace-header';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/hris/resources';

import PersonAvatar from '@/components/person-avatar';

type PreviewRow = {
    row: number;
    employee_number: string;
    name: string;
    avatar?: string | null;
    department: string;
    position: string;
    status: string;
    profile_action: string;
    account_action: string;
    username: string;
    valid: boolean;
    errors: string[];
};
type TemplateColumn = { key: string; label: string };

export default function ImportEmployees({
    rows = [],
    token,
    statusOptions = ['active', 'on leave', 'inactive', 'terminated'],
    columns = [],
}: {
    rows?: PreviewRow[];
    token?: string;
    statusOptions?: string[];
    columns?: TemplateColumn[];
}) {
    const previewSearch = useTableSearch(
        rows,
        [
            'Row',
            'Employee number',
            'Name',
            'Department',
            'Position',
            'Employment status',
            'Validation',
        ],
        (row) => [
            row.row,
            row.employee_number,
            row.name,
            row.department,
            row.position,
            row.status,
            row.valid ? 'Ready' : row.errors.join(' '),
        ],
        'Search import preview',
    );
    const [previewed, setPreviewed] = useState(Boolean(token));
    const [editTemplate, setEditTemplate] = useState(false);
    const upload = useForm<{ file: File | null }>({ file: null });
    const apply = useForm({
        token: token ?? '',
        expiry_days: '14',
        password: '',
    });
    const templateForm = useForm({ columns, status_options: statusOptions });
    const [newColumn, setNewColumn] = useState('');
    const requiredColumns = [
        'employee_number',
        'first_name',
        'last_name',
        'department',
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
        apply.post('/hris/employees/import', {
            onFinish: () => apply.reset('password'),
        });
    }

    return (
        <>
            <Head title="Bulk import employees" />
            <main className="workspace-page max-w-6xl">
                <WorkspaceHeader
                    title="Bulk import employees"
                    description="Import employee records and create employee sign-in accounts from the EMPLOYEES worksheet. This workflow does not add Plantilla positions."
                >
                    <Button
                        asChild
                        variant="outline"
                        size="sm"
                        className="text-foreground"
                    >
                        <Link href={index('employees')}>
                            <ArrowLeft aria-hidden="true" />
                            Back to Employees
                        </Link>
                    </Button>
                </WorkspaceHeader>

                <section className="workspace-panel flex flex-wrap items-center justify-between gap-4 p-4">
                    <div>
                        <h2 className="font-medium">
                            Employee import template
                        </h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            The required columns are Employee Number, First
                            Name, Last Name, Department, and Employment Status.
                            Other fields and Email are optional.
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
                <section className="workspace-panel space-y-2 p-4 text-sm">
                    <h2 className="font-medium">
                        Required spreadsheet columns
                    </h2>
                    <p className="text-muted-foreground">
                        Enter account setup details only. Plantilla and other
                        profile information can be added later. Keep employee
                        numbers as text.
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
                                    Rename, add, or remove columns. Required
                                    columns cannot be removed. Changes apply to
                                    future downloads and imports.
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
                                                        ...templateForm.data
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
                                        templateForm.setData('columns', [
                                            ...templateForm.data.columns,
                                            {
                                                key: '',
                                                label: newColumn.trim(),
                                            },
                                        ]);
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
                                                .map((item) => item.trim())
                                                .filter(Boolean),
                                        )
                                    }
                                    className="min-h-20 w-full rounded-md border bg-background p-3 text-sm"
                                />
                                <p className="text-xs text-muted-foreground">
                                    One choice per line; up to 8 choices, 30
                                    characters each.
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
                                    onClick={() => setEditTemplate(false)}
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
                                                    setEditTemplate(false),
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
                    className="workspace-panel space-y-4 p-4"
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
                                onChange={(event) => {
                                    setPreviewed(false);
                                    upload.setData(
                                        'file',
                                        event.target.files?.[0] ?? null,
                                    );
                                }}
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
                                            setPreviewed(false);
                                            upload.setData('file', null);
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
                        Add one employee per row under the headers on the
                        EMPLOYEES sheet. Keep employee numbers, TINs, and other
                        ID fields as text to preserve leading zeros.
                    </p>
                    <Button disabled={upload.processing}>
                        {upload.processing
                            ? 'Checking workbookâ€¦'
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
                                    {
                                        rows.filter(
                                            (row) =>
                                                row.valid &&
                                                row.account_action ===
                                                    'Create account',
                                        ).length
                                    }{' '}
                                    accounts ready to create;{' '}
                                    {rows.filter((row) => !row.valid).length}{' '}
                                    rows need correction. No records change
                                    until you continue.
                                </p>
                            </div>
                            {previewSearch.toolbar}
                            <div className="overflow-x-auto rounded-xl border">
                                <table className="w-full text-left text-sm">
                                    <thead className="workspace-table-head">
                                        <tr>
                                            {[
                                                'Excel row',
                                                'Employee Number',
                                                'Name',
                                                'Department',
                                                'Position',
                                                'Employment Status',
                                                'Employee profile',
                                                'Login account',
                                                'Username',
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
                                        {previewSearch.rows.map((row) => (
                                            <tr
                                                key={row.row}
                                                className="border-t align-top"
                                            >
                                                <td className="p-3">
                                                    {row.row}
                                                </td>
                                                <td className="p-3">
                                                    {row.employee_number}
                                                </td>
                                                <td className="p-3">
                                                    <span className="inline-flex items-center gap-2">
                                                        <PersonAvatar
                                                            name={row.name}
                                                            src={row.avatar}
                                                        />
                                                        {row.name}
                                                    </span>
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
                                                    {row.profile_action}
                                                </td>
                                                <td className="p-3">
                                                    {row.account_action}
                                                </td>
                                                <td className="p-3 font-mono">
                                                    {row.username}
                                                </td>
                                                <td className="p-3">
                                                    {row.valid
                                                        ? 'Ready'
                                                        : row.errors.join(' ')}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </section>
                        <form
                            onSubmit={commit}
                            className="workspace-panel flex flex-wrap items-end gap-3 p-4"
                        >
                            <input type="hidden" value={token} readOnly />
                            <label className="space-y-1 text-sm">
                                <span>Activation code expiry in days</span>
                                <Input
                                    type="number"
                                    min="1"
                                    required
                                    value={apply.data.expiry_days}
                                    onChange={(event) =>
                                        apply.setData(
                                            'expiry_days',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError
                                    message={apply.errors.expiry_days}
                                />
                            </label>
                            <InputError message={apply.errors.token} />
                            <label className="space-y-1 text-sm">
                                <span>
                                    Current password, if not recently confirmed
                                </span>
                                <Input
                                    type="password"
                                    autoComplete="current-password"
                                    value={apply.data.password}
                                    onChange={(event) =>
                                        apply.setData(
                                            'password',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError message={apply.errors.password} />
                            </label>
                            <Button
                                disabled={
                                    apply.processing ||
                                    !rows.some((row) => row.valid)
                                }
                            >
                                {apply.processing
                                    ? 'Creating accounts...'
                                    : 'Create accounts'}
                            </Button>
                            {rows.some((row) => !row.valid) && (
                                <>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() =>
                                            downloadEmployeeImportErrors(
                                                rows.filter(
                                                    (row) => !row.valid,
                                                ),
                                            )
                                        }
                                    >
                                        Download errors
                                    </Button>
                                    <p className="w-full text-sm text-destructive">
                                        Rows with errors will be held back.
                                        Correct them and upload the workbook
                                        again.
                                    </p>
                                </>
                            )}
                        </form>
                    </>
                )}
            </main>
        </>
    );
}
