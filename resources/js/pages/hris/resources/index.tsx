import { Form, Head, Link, router, useForm } from '@inertiajs/react';
import { FileUp } from 'lucide-react';
import { FormEvent, useEffect, useMemo, useState } from 'react';
import BiometricIdField from '@/components/biometric-id-field';
import DailyTimeRecordDialog from '@/components/daily-time-record-dialog';
import LeaveFormDemoDialog from '@/components/leave-form-demo-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type Field = {
    name: string;
    label: string;
    type: string;
    required?: boolean;
    options?: { value: string | number; label: string }[];
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
    fields: Field[];
    canManage: boolean;
    leaveReviewStage?: [string, string, string] | null;
    canImport?: boolean;
    officeDirectory?: boolean;
    attendanceDirectory?: boolean;
    attendanceMonth?: string;
    canManageAttendance?: boolean;
    canLinkBiometricIds?: boolean;
    statusSummary?: { status: string; total: number }[];
    createdAccountCredentials?: {
        employee_number: string;
        username: string;
        name: string;
        temporary_password: string;
        email: string | null;
    } | null;
    accountSetupEmailSent?: boolean | null;
    records: {
        data: HrisRow[];
        links: { url: string | null; label: string; active: boolean }[];
    };
};

const moduleDescriptions: Record<string, string> = {
    employees: 'Maintain personnel records and review employee assignments.',
    departments:
        'Review municipal offices and maintain department information.',
    attendance: 'Review and maintain employee time and attendance records.',
    'leave-applications': 'Review employee leave requests and their status.',
    vacancies: 'Track open positions and recruitment deadlines.',
};

type FormData = Record<string, string | number | boolean | null>;

const emptyValue = (field: Field): string | number | boolean | null =>
    field.type === 'checkbox' ? false : '';

function AttendanceBadge({
    complete,
    review,
}: {
    complete: number;
    review: number;
}) {
    return (
        <div className="space-y-1">
            {review > 0 ? (
                <Badge className="border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200">
                    Needs review
                </Badge>
            ) : complete > 0 ? (
                <Badge className="border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200">
                    Complete logs
                </Badge>
            ) : (
                <Badge variant="secondary">No work logs</Badge>
            )}
            {complete + review > 0 && (
                <p className="text-xs text-muted-foreground tabular-nums">
                    {complete} complete · {review} to review
                </p>
            )}
        </div>
    );
}

export default function HrisResourceIndex({
    resource,
    title,
    fields,
    canManage,
    leaveReviewStage,
    canImport = false,
    officeDirectory = false,
    attendanceDirectory = false,
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
    const [accountEmail, setAccountEmail] = useState('');

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
            form.transform((data) =>
                resource === 'employees'
                    ? { ...data, account_email: accountEmail || null }
                    : data,
            );
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
        document
            .getElementById('record-editor')
            ?.scrollIntoView({ block: 'start' });
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
        if (resource === 'leave-applications' && field.name === 'status') {
            return String(value).replaceAll('_', ' ');
        }
        if (field.type === 'checkbox') return value ? 'Yes' : 'No';
        if (field.options)
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

    return (
        <>
            <Head title={title} />
            <div className="mx-auto w-full max-w-7xl space-y-6 p-4 md:p-8">
                <header className="relative isolate overflow-hidden rounded-3xl bg-[#142d1c] px-6 py-8 text-white sm:px-9">
                    <div
                        aria-hidden="true"
                        className="pointer-events-none absolute -top-24 -right-10 -z-10 size-72 rounded-full border border-[#a6eb20]/30"
                    />
                    <div
                        aria-hidden="true"
                        className="pointer-events-none absolute -top-14 right-0 -z-10 size-52 rounded-full bg-[#a6eb20]/10"
                    />
                    <div className="flex flex-wrap items-center justify-between gap-x-6 gap-y-4">
                        <div>
                            <h1 className="text-3xl font-semibold tracking-tight sm:text-4xl">
                                {title}
                            </h1>
                            <p className="mt-2 max-w-xl text-sm leading-6 text-white/80">
                                {moduleDescriptions[resource] ??
                                    'Manage HRIS records and their linked data.'}
                            </p>
                        </div>
                        {resource === 'employees' && canImport && (
                            <Button
                                asChild
                                size="lg"
                                className="bg-[#a6eb20] font-semibold text-[#142d1c] hover:bg-[#b9f34e] focus-visible:ring-white/70"
                            >
                                <Link href="/hris/employees/import">
                                    <FileUp aria-hidden="true" />
                                    Bulk Import Employees
                                </Link>
                            </Button>
                        )}
                        {resource === 'leave-applications' && (
                            <LeaveFormDemoDialog />
                        )}
                    </div>
                </header>

                {isAttendance && (
                    <nav
                        aria-label="Attendance views"
                        className="flex flex-wrap gap-2"
                    >
                        <Button
                            asChild
                            variant={
                                attendanceDirectory ? 'default' : 'outline'
                            }
                        >
                            <Link
                                href="/hris/attendance"
                                aria-current={
                                    attendanceDirectory ? 'page' : undefined
                                }
                            >
                                Employees
                            </Link>
                        </Button>
                        <Button
                            asChild
                            variant={
                                attendanceDirectory ? 'outline' : 'default'
                            }
                        >
                            <Link
                                href="/hris/attendance?view=entries"
                                aria-current={
                                    attendanceDirectory ? undefined : 'page'
                                }
                            >
                                Attendance entries
                            </Link>
                        </Button>
                        <DailyTimeRecordDialog
                            url="/hris/attendance/dtr/demo"
                            demo
                        />
                        {canManageAttendance && (
                            <>
                                <Button asChild variant="outline">
                                    <Link href="/hris/attendance/logs">
                                        Import Attendance Logs
                                    </Link>
                                </Button>
                                <Button asChild variant="outline">
                                    <Link
                                        href="/hris/attendance/monthly-report"
                                        data={
                                            attendanceMonth
                                                ? { month: attendanceMonth }
                                                : undefined
                                        }
                                    >
                                        Monthly Attendance Report
                                    </Link>
                                </Button>
                            </>
                        )}
                    </nav>
                )}

                {attendanceMonth && (
                    <Form
                        key={attendanceMonth}
                        action="/hris/attendance"
                        method="get"
                        className="flex flex-wrap items-end gap-3"
                    >
                        <label className="space-y-1 text-sm font-medium">
                            <span>Attendance month</span>
                            <Input
                                name="month"
                                type="month"
                                defaultValue={attendanceMonth}
                                required
                            />
                        </label>
                        <Button type="submit" variant="outline">
                            Show month
                        </Button>
                        <p className="basis-full text-sm text-muted-foreground">
                            Badges summarize recorded workdays for this month.
                        </p>
                    </Form>
                )}

                {resource === 'employees' && statusSummary.length > 0 && (
                    <section
                        aria-label="Employees by status"
                        className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4"
                    >
                        {statusSummary.map(({ status, total }) => (
                            <div key={status} className="rounded-lg border p-4">
                                <p className="text-sm text-muted-foreground capitalize">
                                    {status.replaceAll('_', ' ')}
                                </p>
                                <p className="mt-1 text-2xl font-semibold">
                                    {total}
                                </p>
                            </div>
                        ))}
                    </section>
                )}

                {canManage && (
                    <section
                        id="record-editor"
                        className="scroll-mt-20 rounded-2xl border bg-card p-5 sm:p-6"
                    >
                        <h2 className="mb-4 text-lg font-medium">
                            {editingId
                                ? 'Edit record'
                                : resource === 'employees'
                                  ? 'Create employee account'
                                  : 'Add record'}
                        </h2>
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
                            {resource === 'employees' && !editingId && (
                                <label className="space-y-1 text-sm">
                                    <span>Email (optional)</span>
                                    <input
                                        type="email"
                                        value={accountEmail}
                                        onChange={(event) =>
                                            setAccountEmail(event.target.value)
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
                                <button
                                    type="submit"
                                    disabled={form.processing}
                                    className="rounded-full bg-primary px-5 py-2 font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                                >
                                    {form.processing
                                        ? 'Saving…'
                                        : editingId
                                          ? 'Save changes'
                                          : resource === 'employees'
                                            ? 'Create account'
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
                        Enter the Biometric ID from the machine’s enrollment
                        records. To remove a link, clear the field and save.
                    </p>
                )}

                {reviewError && (
                    <p role="alert" className="text-sm text-destructive">
                        {reviewError}
                    </p>
                )}
                <section
                    aria-labelledby="records-heading"
                    className="overflow-hidden rounded-2xl border bg-card"
                >
                    <div className="flex flex-wrap items-center justify-between gap-2 px-5 py-4">
                        <h2
                            id="records-heading"
                            className="text-lg font-semibold tracking-tight"
                        >
                            {attendanceDirectory ? 'Employees' : 'Records'}
                        </h2>
                        <span className="text-sm text-muted-foreground">
                            {records.data.length} on this page
                        </span>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-muted text-muted-foreground">
                                <tr>
                                    {canLinkBiometricIds && (
                                        <th
                                            scope="col"
                                            className="px-3 py-3 font-medium"
                                        >
                                            Biometric ID
                                        </th>
                                    )}
                                    {!attendanceDirectory && (
                                        <th className="px-3 py-3 font-medium">
                                            ID
                                        </th>
                                    )}
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
                                            {attendanceDirectory
                                                ? 'DTR'
                                                : 'Actions'}
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
                                        {canLinkBiometricIds &&
                                            typeof row.id === 'number' && (
                                                <td className="px-3 py-3">
                                                    <BiometricIdField
                                                        key={`${row.id}-${row.biometric_id ?? ''}-${row.biometric_locked === true}`}
                                                        employeeId={row.id}
                                                        employeeNumber={String(
                                                            row.employee_number,
                                                        )}
                                                        link={{
                                                            id:
                                                                typeof row.biometric_id ===
                                                                'number'
                                                                    ? row.biometric_id
                                                                    : null,
                                                            locked:
                                                                row.biometric_locked ===
                                                                true,
                                                            status:
                                                                row.biometric_status ===
                                                                'verified'
                                                                    ? 'verified'
                                                                    : 'pending',
                                                            note:
                                                                typeof row.biometric_note ===
                                                                'string'
                                                                    ? row.biometric_note
                                                                    : null,
                                                        }}
                                                    />
                                                </td>
                                            )}
                                        {!attendanceDirectory && (
                                            <td className="px-3 py-3">
                                                {row.id}
                                            </td>
                                        )}
                                        {fields.map((field) => (
                                            <td
                                                key={field.name}
                                                className="max-w-64 truncate px-3 py-3"
                                            >
                                                {field.name ===
                                                'attendance_summary' ? (
                                                    <AttendanceBadge
                                                        complete={Number(
                                                            row.attendance_complete_days ??
                                                                0,
                                                        )}
                                                        review={Number(
                                                            row.attendance_review_days ??
                                                                0,
                                                        )}
                                                    />
                                                ) : resource === 'employees' &&
                                                  field.name ===
                                                      'employment_status' ? (
                                                    <span className="inline-flex rounded-full border px-2.5 py-1 text-xs font-medium capitalize">
                                                        {displayValue(
                                                            field,
                                                            row[field.name],
                                                        )}
                                                    </span>
                                                ) : (
                                                    displayValue(
                                                        field,
                                                        row[field.name],
                                                    )
                                                )}
                                            </td>
                                        ))}
                                        {leaveReviewStage && (
                                            <td className="px-3 py-3 whitespace-nowrap">
                                                {typeof row.id === 'number' &&
                                                    row.status ===
                                                        leaveReviewStage[0] && (
                                                        <>
                                                            <button
                                                                type="button"
                                                                disabled={
                                                                    reviewing
                                                                }
                                                                onClick={() =>
                                                                    reviewLeave(
                                                                        Number(
                                                                            row.id,
                                                                        ),
                                                                        'advance',
                                                                    )
                                                                }
                                                                className="mr-2 rounded-full bg-primary px-3 py-1 font-medium text-primary-foreground disabled:opacity-50"
                                                            >
                                                                {
                                                                    leaveReviewStage[2]
                                                                }
                                                            </button>
                                                            <button
                                                                type="button"
                                                                disabled={
                                                                    reviewing
                                                                }
                                                                onClick={() =>
                                                                    reviewLeave(
                                                                        Number(
                                                                            row.id,
                                                                        ),
                                                                        'reject',
                                                                    )
                                                                }
                                                                className="rounded-full px-3 py-1 font-medium text-destructive hover:bg-destructive/10 disabled:opacity-50"
                                                            >
                                                                Reject
                                                            </button>
                                                        </>
                                                    )}
                                            </td>
                                        )}
                                        {hasActions && !leaveReviewStage && (
                                            <td className="px-3 py-3 whitespace-nowrap">
                                                {isAttendance && (
                                                    <span className="mr-2">
                                                        <DailyTimeRecordDialog
                                                            url={
                                                                typeof row.dtr_url ===
                                                                'string'
                                                                    ? row.dtr_url
                                                                    : `/hris/attendance/dtr/${row.employee_id}?month=${String(row.attendance_date).slice(0, 7)}`
                                                            }
                                                        />
                                                    </span>
                                                )}
                                                {canManage &&
                                                    row.canManage !== false && (
                                                        <>
                                                            <button
                                                                type="button"
                                                                onClick={() =>
                                                                    edit(row)
                                                                }
                                                                className="mr-2 rounded-full border px-3 py-1 font-medium hover:bg-accent hover:text-accent-foreground"
                                                            >
                                                                Edit
                                                            </button>
                                                            <button
                                                                type="button"
                                                                onClick={() =>
                                                                    remove(row)
                                                                }
                                                                className="rounded-full px-3 py-1 font-medium text-destructive hover:bg-destructive/10"
                                                            >
                                                                Delete
                                                            </button>
                                                        </>
                                                    )}
                                            </td>
                                        )}
                                    </tr>
                                ))}
                                {records.data.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={
                                                fields.length +
                                                Number(!attendanceDirectory) +
                                                Number(canLinkBiometricIds) +
                                                Number(hasActions)
                                            }
                                            className="px-3 py-8 text-center text-muted-foreground"
                                        >
                                            {attendanceDirectory
                                                ? 'No employees found.'
                                                : 'No records found.'}
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
