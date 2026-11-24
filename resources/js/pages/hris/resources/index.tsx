import { DirectorySearch } from '@/components/table-search';
import TableAction from '@/components/table-action';
import PersonAvatar from '@/components/person-avatar';
import {
    DirectoryOverview,
    ResourceDirectory,
    directoryLayouts,
    type Field,
    type HrisValue,
    type HrisRow,
} from '@/components/resource-directory';
import WorkspaceHeader from '@/components/workspace-header';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Check, FileUp, Pencil, Plus, Trash2, X } from 'lucide-react';
import { FormEvent, useEffect, useMemo, useState } from 'react';
import AttendanceWorkspaceHeader from '@/components/attendance-workspace-header';
import DailyTimeRecordDialog from '@/components/daily-time-record-dialog';
import LeaveFormPreviewDialog from '@/components/leave-form-preview-dialog';
import AttendanceDirectory, {
    type AttendanceDirectoryProps,
} from '@/components/attendance-directory';
import RecordPagination, {
    type RecordPageLink,
} from '@/components/record-pagination';
import { Button } from '@/components/ui/button';

type Props = {
    resource: string;
    title: string;
    fields: Field[];
    canManage: boolean;
    leaveReviewStage?: [string, string, string] | null;
    canCreateLeaveOnBehalf?: boolean;
    canImport?: boolean;
    officeDirectory?: boolean;
    attendanceDirectory?: false;
    attendanceMonth?: string;
    canManageAttendance?: boolean;
    canLinkBiometricIds?: boolean;
    statusSummary?: { status: string; total: number }[];
    createdAccountCredentials?: {
        employee_number: string;
        reference_code: string;
        username: string;
        name: string;
        temporary_password: string;
        email: string | null;
    } | null;
    accountSetupEmailSent?: boolean | null;
    records: {
        data: HrisRow[];
        links: RecordPageLink[];
    };
};

const moduleDescriptions: Record<string, string> = {
    employees: 'Maintain personnel records and review employee assignments.',
    departments:
        'Review municipal offices and maintain department information.',
    attendance: 'Review and maintain employee time and attendance records.',
    'leave-applications': 'Review employee leave requests and their status.',
    positions: 'Review position assignments, salary grades, and active posts.',
    vacancies: 'Track open positions and recruitment deadlines.',
};

type FormData = Record<string, string | number | boolean | null>;

const emptyValue = (field: Field): string | number | boolean | null =>
    field.type === 'checkbox' ? false : '';

export default function HrisResourceIndex(
    props: Props | AttendanceDirectoryProps,
) {
    if (props.attendanceDirectory) {
        return <AttendanceDirectory {...props} />;
    }

    return <ResourceRecords {...props} />;
}

function ResourceRecords({
    resource,
    title,
    fields,
    canManage,
    leaveReviewStage,
    canCreateLeaveOnBehalf = false,
    canImport = false,
    officeDirectory = false,
    attendanceMonth,
    canManageAttendance = false,
    canLinkBiometricIds = false,
    statusSummary = [],
    createdAccountCredentials = null,
    accountSetupEmailSent = null,
    records,
}: Props) {
    const [reviewing, setReviewing] = useState(false);
    const [reviewError, setReviewError] = useState('');
    const isAttendance = resource === 'attendance';
    const hasActions = canManage || Boolean(leaveReviewStage) || isAttendance;

    function reviewLeave(id: number, decision: 'advance' | 'reject') {
        if (
            !window.confirm(
                decision === 'reject'
                    ? 'Reject this leave request?'
                    : 'Confirm this leave decision?',
            )
        )
            return;

        setReviewError('');
        setReviewing(true);
        router.post(
            `/hris/leave-applications/${id}/review`,
            { decision },
            {
                preserveScroll: true,
                onError: (errors) =>
                    setReviewError(
                        errors.decision ??
                            'The leave decision could not be saved.',
                    ),
                onFinish: () => setReviewing(false),
            },
        );
    }

    const [editingId, setEditingId] = useState<number | string | null>(null);
    const initialData = useMemo(
        () =>
            Object.fromEntries(
                fields.map((field) => [field.name, emptyValue(field)]),
            ) as FormData,
        [fields],
    );
    const form = useForm<FormData>(initialData);
    const [accountEmail, setAccountEmail] = useState<string | null>('');

    useEffect(() => {
        form.setData(initialData);
        setEditingId(null);
        setAccountEmail('');
    }, [resource]);

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const url = `/hris/${resource}`;

        form.transform((data) =>
            resource === 'employees' && accountEmail !== null
                ? { ...data, account_email: accountEmail || null }
                : data,
        );
        if (editingId) {
            form.put(`${url}/${editingId}`, {
                onSuccess: () => {
                    setEditingId(null);
                    form.reset();
                    setAccountEmail('');
                },
            });
        } else {
            form.post(url, {
                onSuccess: () => {
                    form.reset();
                    setAccountEmail('');
                },
            });
        }
    };

    const edit = (row: HrisRow) => {
        setEditingId(row.id);
        setAccountEmail(
            'account_email' in row ? String(row.account_email ?? '') : null,
        );
        const editor = document.getElementById('record-editor');
        if (editor instanceof HTMLDetailsElement) editor.open = true;
        editor?.scrollIntoView({ block: 'start' });
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
        setAccountEmail('');
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
        if (resource === 'leave-applications' && field.name === 'status') {
            return String(value).replaceAll('_', ' ');
        }
        if (field.type === 'checkbox') return value ? 'Yes' : 'No';
        if (Array.isArray(field.options))
            return (
                field.options.find(
                    (option) => String(option.value) === String(value),
                )?.label ?? String(value)
            );
        if (resource === 'employees' && field.name === 'appointment_status') {
            return (
                (
                    {
                        P: 'Permanent',
                        E: 'Elected',
                        T: 'Temporary',
                        S: 'Substitute',
                        CT: 'Coterminous',
                        PA: 'Presidential Appointee',
                        FT: 'Fixed Term',
                    } as Record<string, string>
                )[String(value)] ?? String(value)
            );
        }
        if (resource === 'employees' && field.name === 'employment_status')
            return String(value)
                .replaceAll('_', ' ')
                .replace(/\b\w/g, (letter) => letter.toUpperCase());
        return value;
    };

    function rowActions(row: HrisRow) {
        return (
            <>
                {leaveReviewStage &&
                    typeof row.id === 'number' &&
                    row.status === leaveReviewStage[0] && (
                        <>
                            <TableAction
                                icon={Check}
                                label={leaveReviewStage[2]}
                                disabled={reviewing}
                                onClick={() =>
                                    reviewLeave(Number(row.id), 'advance')
                                }
                            />
                            <TableAction
                                icon={X}
                                label={'Reject leave request'}
                                className="text-destructive"
                                disabled={reviewing}
                                onClick={() =>
                                    reviewLeave(Number(row.id), 'reject')
                                }
                            />
                        </>
                    )}
                {isAttendance && (
                    <span className="mr-2">
                        <DailyTimeRecordDialog
                            url={
                                typeof row.dtr_url === 'string'
                                    ? row.dtr_url
                                    : `/hris/attendance/dtr/${row.employee_id}?month=${String(row.attendance_date).slice(0, 7)}`
                            }
                        />
                    </span>
                )}
                {canManage && row.canManage !== false && (
                    <>
                        <TableAction
                            icon={Pencil}
                            label={`Edit record ${row.id}`}
                            type="button"
                            onClick={() => edit(row)}
                            className="mr-2 rounded-full"
                        />
                        <TableAction
                            icon={Trash2}
                            label={`Delete record ${row.id}`}
                            type="button"
                            onClick={() => remove(row)}
                            className="text-destructive hover:bg-destructive/10 hover:text-destructive"
                        />
                    </>
                )}
            </>
        );
    }

    const directory = directoryLayouts[resource];
    const Editor = directory ? 'details' : 'section';
    const EditorHeading = directory ? 'summary' : 'h2';
    return (
        <>
            <Head title={title} />
            <div className="workspace-page max-w-7xl">
                {isAttendance ? (
                    <AttendanceWorkspaceHeader
                        active="records"
                        month={attendanceMonth}
                        canManage={canManageAttendance}
                    />
                ) : (
                    <WorkspaceHeader
                        title={title}
                        description={
                            moduleDescriptions[resource] ??
                            'Manage HRIS records and their linked data.'
                        }
                    >
                        {resource === 'employees' && canImport && (
                            <Button asChild className="dashboard-action">
                                <Link href="/hris/employees/import">
                                    <FileUp aria-hidden="true" />
                                    Bulk Import Employees
                                </Link>
                            </Button>
                        )}
                        {resource === 'leave-applications' && (
                            <div className="flex flex-wrap gap-2">
                                {canCreateLeaveOnBehalf && (
                                    <Button
                                        asChild
                                        className="dashboard-action"
                                    >
                                        <Link href="/hris/leave-applications/create">
                                            <Plus aria-hidden="true" />
                                            Create leave form
                                        </Link>
                                    </Button>
                                )}
                                <LeaveFormPreviewDialog />
                            </div>
                        )}
                    </WorkspaceHeader>
                )}

                {directory && (
                    <DirectoryOverview
                        resource={resource}
                        title={title}
                        rows={records.data}
                        statusSummary={statusSummary}
                    />
                )}
                {canManage && (
                    <Editor
                        id="record-editor"
                        className="workspace-panel scroll-mt-20 p-5 sm:p-6 [&_summary+*]:mt-4"
                    >
                        <EditorHeading
                            className={
                                directory
                                    ? 'cursor-pointer text-lg font-semibold'
                                    : 'mb-4 text-lg font-medium'
                            }
                        >
                            {editingId
                                ? 'Edit record'
                                : resource === 'employees'
                                  ? 'Create employee account'
                                  : 'Add record'}
                        </EditorHeading>
                        {resource === 'employees' && (
                            <div className="mb-4 rounded-lg bg-muted p-3 text-sm text-muted-foreground">
                                <p>
                                    Creating this record also creates a login
                                    account. The employee number is the
                                    username. The temporary password is shown
                                    once after saving.
                                </p>
                                {canImport && (
                                    <p className="mt-1">
                                        For multiple accounts, use{' '}
                                        <Link
                                            href="/hris/employees/import"
                                            className="font-medium text-foreground underline underline-offset-4"
                                        >
                                            employee bulk import
                                        </Link>
                                        . Email is optional.
                                    </p>
                                )}
                            </div>
                        )}
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
                                                    field.options?.find(
                                                        (option) =>
                                                            String(
                                                                option.value,
                                                            ) ===
                                                            event.target.value,
                                                    )?.value ?? null,
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
                            {resource === 'employees' &&
                                accountEmail !== null && (
                                    <label className="space-y-1 text-sm">
                                        <span>Email (optional)</span>
                                        <input
                                            type="email"
                                            value={accountEmail}
                                            onChange={(event) =>
                                                setAccountEmail(
                                                    event.target.value,
                                                )
                                            }
                                            className="w-full rounded-xl border border-input bg-background px-3 py-2 focus-visible:outline-2 focus-visible:outline-ring"
                                        />
                                        {form.errors.account_email && (
                                            <span className="block text-sm text-destructive">
                                                {form.errors.account_email}
                                            </span>
                                        )}
                                    </label>
                                )}
                            <div className="flex items-center gap-2 sm:col-span-2 lg:col-span-3">
                                <Button
                                    type="submit"
                                    disabled={form.processing}
                                    className="rounded-full"
                                >
                                    {form.processing
                                        ? 'Saving…'
                                        : editingId
                                          ? 'Save changes'
                                          : resource === 'employees'
                                            ? 'Create account'
                                            : 'Create record'}
                                </Button>
                                {editingId && (
                                    <Button
                                        type="button"
                                        onClick={cancelEdit}
                                        variant="outline"
                                        className="rounded-full"
                                    >
                                        Cancel
                                    </Button>
                                )}
                            </div>
                        </form>
                    </Editor>
                )}

                {resource === 'employees' && createdAccountCredentials && (
                    <section className="space-y-2 rounded-xl border border-amber-500/40 bg-amber-500/5 p-5">
                        <h2 className="font-semibold">
                            Employee account created
                        </h2>
                        <p>Name: {createdAccountCredentials.name}</p>
                        <p>
                            Employee number:{' '}
                            <span className="font-mono">
                                {createdAccountCredentials.employee_number}
                            </span>
                        </p>
                        <p>
                            Reference code:{' '}
                            <span className="font-mono">
                                {createdAccountCredentials.reference_code}
                            </span>
                        </p>
                        <p>
                            Username:{' '}
                            <span className="font-mono">
                                {createdAccountCredentials.username}
                            </span>
                        </p>
                        <p>
                            Temporary password:{' '}
                            <span className="font-mono">
                                {createdAccountCredentials.temporary_password}
                            </span>
                        </p>
                        {createdAccountCredentials.email && (
                            <p>
                                {accountSetupEmailSent
                                    ? 'Setup link sent to'
                                    : 'Setup link could not be sent to'}{' '}
                                {createdAccountCredentials.email}.
                            </p>
                        )}
                        <p className="text-sm text-muted-foreground">
                            Share these credentials privately. The employee must
                            change the temporary password and enroll in
                            two-factor authentication.
                        </p>
                    </section>
                )}

                {officeDirectory && (
                    <p className="text-sm text-muted-foreground">
                        Fixed municipal offices from the approved 2025
                        plantilla.
                    </p>
                )}

                {canLinkBiometricIds && (
                    <p className="text-sm text-muted-foreground">
                        Use the ID shown on the biometric device. Save checks
                        imported logs for a match. Lock prevents changes until
                        you unlock the ID. To remove an unlocked ID, clear the
                        field and save.
                    </p>
                )}

                {reviewError && (
                    <p role="alert" className="text-sm text-destructive">
                        {reviewError}
                    </p>
                )}
                <section
                    aria-labelledby="records-heading"
                    className="workspace-panel overflow-hidden"
                >
                    <div className="flex flex-wrap items-center justify-between gap-2 px-5 py-4">
                        <h2
                            id="records-heading"
                            className="text-lg font-semibold tracking-tight"
                        >
                            {directory?.title ?? 'Records'}
                        </h2>
                        <span className="text-sm text-muted-foreground">
                            {records.data.length} on this page
                        </span>
                    </div>
                    <DirectorySearch
                        columns={[{ name: 'id', label: 'ID' }, ...fields]}
                        label={`Search ${title.toLowerCase()}`}
                    />
                    {directory ? (
                        <ResourceDirectory
                            layout={directory}
                            rows={records.data}
                            fields={fields}
                            canLinkBiometricIds={canLinkBiometricIds}
                            renderValue={displayValue}
                            renderActions={hasActions ? rowActions : undefined}
                        />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="workspace-table-head">
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
                                        {hasActions && (
                                            <th className="px-3 py-3 font-medium">
                                                Actions
                                            </th>
                                        )}
                                    </tr>
                                </thead>
                                <tbody>
                                    {records.data.map((row) => (
                                        <tr
                                            key={row.id}
                                            className="border-t transition-colors hover:bg-muted/60"
                                        >
                                            <td className="px-3 py-3">
                                                {row.id}
                                            </td>
                                            {fields.map((field) => (
                                                <td
                                                    key={field.name}
                                                    className="max-w-64 truncate px-3 py-3"
                                                >
                                                    {(field.name ===
                                                        'employee_id' ||
                                                        field.account) &&
                                                        row[field.name] !=
                                                            null && (
                                                            <PersonAvatar
                                                                name={String(
                                                                    displayValue(
                                                                        field,
                                                                        row[
                                                                            field
                                                                                .name
                                                                        ],
                                                                    ),
                                                                )}
                                                                src={String(
                                                                    (field.account
                                                                        ? row[
                                                                              `${field.name}_avatar`
                                                                          ]
                                                                        : row.avatar) ??
                                                                        '',
                                                                )}
                                                            />
                                                        )}{' '}
                                                    {displayValue(
                                                        field,
                                                        row[field.name],
                                                    )}
                                                </td>
                                            ))}
                                            {hasActions && (
                                                <td className="px-3 py-3 whitespace-nowrap">
                                                    {rowActions(row)}
                                                </td>
                                            )}
                                        </tr>
                                    ))}
                                    {records.data.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={
                                                    fields.length +
                                                    1 +
                                                    Number(hasActions)
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
                    )}
                    <RecordPagination links={records.links} />
                </section>
            </div>
        </>
    );
}
