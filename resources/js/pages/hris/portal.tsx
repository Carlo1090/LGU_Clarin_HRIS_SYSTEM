import { useTableSearch } from '@/components/table-search';
import WorkspaceHeader from '@/components/workspace-header';
import { Head, Link, useForm } from '@inertiajs/react';
import DailyTimeRecordDialog from '@/components/daily-time-record-dialog';
import LeaveFormPreviewDialog, {
    leaveReasons,
    leaveTypes as formLeaveTypes,
    type LeaveApplicant,
} from '@/components/leave-form-preview-dialog';

type Cell = string | number | null;

type Props = {
    title: string;
    description: string;
    columns: string[];
    records?: {
        data: Cell[][];
        prev_page_url: string | null;
        next_page_url: string | null;
    } | null;
    profile?: Cell[] | null;
    missingEmployee?: boolean;
    dtrPreviewUrl?: string | null;
    dtrDemoUrl?: string | null;
    leaveTypes?: { id: number; name: string }[] | null;
    leaveApplicant?: LeaveApplicant | null;
    filingDate?: string | null;
};

function LeaveRequestForm({
    types,
    applicant,
    filingDate,
}: {
    types: { id: number; name: string }[];
    applicant: LeaveApplicant;
    filingDate: string;
}) {
    const form = useForm({
        leave_type_id: '',
        start_date: '',
        end_date: '',
        days_requested: '',
        reason: '',
        reason_details: '',
        commutation: false,
    });

    const reasonDetail = leaveReasons
        .flatMap(({ options }) => options)
        .find(({ label }) => label === form.data.reason)?.detail;

    const fieldClass =
        'w-full rounded-xl border border-input bg-background px-3 py-2';

    return (
        <section
            aria-labelledby="leave-form-heading"
            className="workspace-panel p-6"
        >
            <h2 id="leave-form-heading" className="text-lg font-semibold">
                New leave request
            </h2>
            {types.length > 0 && (
                <p className="mt-1 text-sm text-muted-foreground">
                    Fill in your details, then preview them on Civil Service
                    Form 6 before submitting.
                </p>
            )}
            {types.length ? (
                <form
                    className="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
                    onSubmit={(event) => {
                        event.preventDefault();
                        if (!form.data.leave_type_id) {
                            form.setError(
                                'leave_type_id',
                                'Select a leave type.',
                            );
                            return;
                        }
                        if (
                            !form.data.reason ||
                            (reasonDetail && !form.data.reason_details.trim())
                        ) {
                            form.setError(
                                'reason',
                                form.data.reason
                                    ? 'Enter the requested details.'
                                    : 'Select a reason.',
                            );
                            return;
                        }
                        form.transform(({ reason_details, ...data }) => ({
                            ...data,
                            reason: reason_details.trim()
                                ? data.reason + ' - ' + reason_details.trim()
                                : data.reason,
                        }));
                        form.post('/hris/portal/my-leave-requests', {
                            preserveScroll: true,
                            onSuccess: () => form.reset(),
                        });
                    }}
                >
                    <fieldset className="rounded-lg border p-4 sm:col-span-2 xl:col-span-4">
                        <legend className="px-1 text-sm font-semibold">
                            Type of leave
                        </legend>
                        <p className="mb-3 text-sm text-muted-foreground">
                            Select one leave type.
                        </p>
                        <div className="grid gap-2 lg:grid-cols-2">
                            {types.map(({ id, name }) => {
                                const reference = formLeaveTypes.find(
                                    ([label]) =>
                                        label.toLowerCase() ===
                                        name.trim().toLowerCase(),
                                )?.[1];
                                return (
                                    <label
                                        key={id}
                                        className="flex cursor-pointer items-start gap-2 text-sm"
                                    >
                                        <input
                                            type="checkbox"
                                            className="mt-0.5 size-4 shrink-0 accent-primary"
                                            checked={
                                                form.data.leave_type_id ===
                                                String(id)
                                            }
                                            onChange={(event) => {
                                                form.setData(
                                                    'leave_type_id',
                                                    event.target.checked
                                                        ? String(id)
                                                        : '',
                                                );
                                                form.clearErrors(
                                                    'leave_type_id',
                                                );
                                            }}
                                            aria-describedby={
                                                form.errors.leave_type_id
                                                    ? 'leave-type-error'
                                                    : undefined
                                            }
                                        />
                                        <span>
                                            {name}
                                            {reference && (
                                                <small className="ml-1 text-muted-foreground">
                                                    ({reference})
                                                </small>
                                            )}
                                        </span>
                                    </label>
                                );
                            })}
                        </div>
                        {form.errors.leave_type_id && (
                            <p
                                id="leave-type-error"
                                role="alert"
                                className="mt-2 text-sm text-destructive"
                            >
                                {form.errors.leave_type_id}
                            </p>
                        )}
                    </fieldset>
                    {(
                        [
                            ['start_date', 'Start date'],
                            ['end_date', 'End date'],
                            ['days_requested', 'Requested workdays'],
                        ] as const
                    ).map(([name, label]) => (
                        <label key={name} className="grid gap-1 text-sm">
                            {label}
                            <input
                                required
                                type={
                                    name === 'days_requested'
                                        ? 'number'
                                        : 'date'
                                }
                                min={
                                    name === 'days_requested'
                                        ? 1
                                        : name === 'end_date'
                                          ? form.data.start_date || undefined
                                          : undefined
                                }
                                className={fieldClass}
                                value={form.data[name]}
                                onChange={(event) =>
                                    form.setData(name, event.target.value)
                                }
                            />
                            {form.errors[name] && (
                                <span className="text-destructive">
                                    {form.errors[name]}
                                </span>
                            )}
                        </label>
                    ))}
                    <fieldset className="rounded-lg border p-4 sm:col-span-2 xl:col-span-4">
                        <legend className="px-1 text-sm font-semibold">
                            Reason
                        </legend>
                        <div className="grid gap-4 lg:grid-cols-2">
                            {leaveReasons.map(({ title, options }) => (
                                <div key={title} className="space-y-2">
                                    <p className="text-sm font-medium">
                                        {title}
                                    </p>
                                    {options.map(({ label }) => (
                                        <label
                                            key={label}
                                            className="flex cursor-pointer items-start gap-2 text-sm"
                                        >
                                            <input
                                                type="checkbox"
                                                className="mt-0.5 size-4 shrink-0 accent-primary"
                                                checked={
                                                    form.data.reason === label
                                                }
                                                onChange={(event) => {
                                                    form.setData({
                                                        ...form.data,
                                                        reason: event.target
                                                            .checked
                                                            ? label
                                                            : '',
                                                        reason_details: '',
                                                    });
                                                    form.clearErrors('reason');
                                                }}
                                            />
                                            {label}
                                        </label>
                                    ))}
                                </div>
                            ))}
                        </div>
                        {reasonDetail && (
                            <label className="mt-4 grid gap-1 text-sm">
                                {reasonDetail}
                                <textarea
                                    required
                                    maxLength={1900}
                                    rows={2}
                                    className={fieldClass}
                                    value={form.data.reason_details}
                                    onChange={(event) => {
                                        form.setData(
                                            'reason_details',
                                            event.target.value,
                                        );
                                        form.clearErrors('reason');
                                    }}
                                />
                            </label>
                        )}
                        {form.errors.reason && (
                            <p
                                role="alert"
                                className="mt-2 text-sm text-destructive"
                            >
                                {form.errors.reason}
                            </p>
                        )}
                    </fieldset>
                    <fieldset className="rounded-lg border p-4 sm:col-span-2 xl:col-span-4">
                        <legend className="px-1 text-sm font-semibold">
                            Commutation
                        </legend>
                        <div className="flex flex-wrap gap-6 text-sm">
                            {[
                                { value: false, label: 'Not requested' },
                                { value: true, label: 'Requested' },
                            ].map(({ value, label }) => (
                                <label
                                    key={label}
                                    className="flex items-center gap-2"
                                >
                                    <input
                                        type="radio"
                                        name="commutation"
                                        checked={
                                            form.data.commutation === value
                                        }
                                        onChange={() =>
                                            form.setData('commutation', value)
                                        }
                                    />
                                    {label}
                                </label>
                            ))}
                        </div>
                    </fieldset>
                    <div className="flex flex-wrap items-center gap-3 border-t pt-4 sm:col-span-2 xl:col-span-4">
                        <LeaveFormPreviewDialog
                            draft={{
                                applicant,
                                filingDate,
                                leaveType:
                                    types.find(
                                        ({ id }) =>
                                            String(id) ===
                                            form.data.leave_type_id,
                                    )?.name ?? '',
                                startDate: form.data.start_date,
                                endDate: form.data.end_date,
                                daysRequested: form.data.days_requested,
                                reason: form.data.reason,
                                reasonDetails: form.data.reason_details,
                                commutation: form.data.commutation,
                            }}
                        />
                        <button
                            type="submit"
                            disabled={form.processing}
                            className="rounded-full bg-primary px-5 py-2 text-sm font-medium text-primary-foreground disabled:opacity-50"
                        >
                            {form.processing ? 'Submitting…' : 'Submit request'}
                        </button>
                        {form.recentlySuccessful && (
                            <p
                                role="status"
                                className="text-sm text-accent-foreground"
                            >
                                Request submitted for review.
                            </p>
                        )}
                    </div>
                </form>
            ) : (
                <p className="mt-2 text-sm text-muted-foreground">
                    No leave types are currently available. Contact HRMO for
                    help.
                </p>
            )}
        </section>
    );
}

export default function PortalPage({
    title,
    description,
    columns,
    records,
    profile,
    missingEmployee,
    dtrPreviewUrl,
    dtrDemoUrl,
    leaveTypes,
    leaveApplicant,
    filingDate,
}: Props) {
    const search = useTableSearch(records?.data ?? [], columns, (row) => row);
    return (
        <div className="workspace-page max-w-7xl">
            <Head title={title} />
<<<<<<< HEAD
            <WorkspaceHeader title={title} description={description}>
                {canPreviewLeaveForm && <LeaveFormDemoDialog />}
            </WorkspaceHeader>
=======
            <header className="flex flex-wrap items-center justify-between gap-4 rounded-3xl bg-[#142d1c] px-6 py-8 text-white sm:px-9">
                <div>
                    <h1 className="text-3xl font-semibold tracking-tight sm:text-4xl">
                        {title}
                    </h1>
                    <p className="mt-2 text-sm text-white/80">{description}</p>
                </div>
            </header>
>>>>>>> 2bcfedee3e691c4d958da8917ba2da6b47d6ffcd

            {leaveTypes && leaveApplicant && (
                <LeaveRequestForm
                    types={leaveTypes}
                    applicant={leaveApplicant}
                    filingDate={filingDate ?? ''}
                />
            )}
            {dtrDemoUrl && (
                <div className="flex flex-wrap gap-2">
                    {dtrPreviewUrl && (
                        <DailyTimeRecordDialog url={dtrPreviewUrl} />
                    )}
                    <DailyTimeRecordDialog url={dtrDemoUrl} demo />
                </div>
            )}

            <section
                aria-label={title}
                className="workspace-panel overflow-hidden"
            >
                {missingEmployee ? (
                    <p className="p-6 text-sm text-muted-foreground">
                        Contact HRMO to link your account to your employee
                        record.
                    </p>
                ) : profile ? (
                    <dl className="grid gap-6 p-6 sm:grid-cols-2 lg:grid-cols-3">
                        {columns.map((column, index) => (
                            <div key={column}>
                                <dt className="text-sm text-muted-foreground">
                                    {column}
                                </dt>
                                <dd className="mt-1 font-medium">
                                    {profile[index] ?? '—'}
                                </dd>
                            </div>
                        ))}
                    </dl>
                ) : (
                    <>
                        {search.toolbar}
                        <div className="overflow-x-auto">
                            <table
                                aria-label={title}
                                className="w-full text-left text-sm"
                            >
                                <thead className="workspace-table-head">
                                    <tr>
                                        {columns.map((column) => (
                                            <th
                                                key={column}
                                                scope="col"
                                                className="px-5 py-3 font-medium"
                                            >
                                                {column}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {search.rows.map((row, index) => (
                                        <tr
                                            key={index}
                                            className="border-t hover:bg-muted/60"
                                        >
                                            {row.map((value, cell) => (
                                                <td
                                                    key={columns[cell]}
                                                    className="px-5 py-4"
                                                >
                                                    {columns[cell] ===
                                                        'Status' &&
                                                    typeof value === 'string'
                                                        ? value.replaceAll(
                                                              '_',
                                                              ' ',
                                                          )
                                                        : (value ?? '—')}
                                                </td>
                                            ))}
                                        </tr>
                                    ))}
                                    {!search.rows.length && (
                                        <tr>
                                            <td
                                                colSpan={columns.length}
                                                className="px-5 py-8 text-center text-muted-foreground"
                                            >
                                                No records found.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                        {(records?.prev_page_url || records?.next_page_url) && (
                            <nav
                                aria-label="Record pages"
                                className="flex gap-3 border-t p-4"
                            >
                                {records.prev_page_url && (
                                    <Link
                                        href={records.prev_page_url}
                                        className="rounded-full border px-4 py-2 text-sm hover:bg-muted"
                                    >
                                        Previous
                                    </Link>
                                )}
                                {records.next_page_url && (
                                    <Link
                                        href={records.next_page_url}
                                        className="rounded-full border px-4 py-2 text-sm hover:bg-muted"
                                    >
                                        Next
                                    </Link>
                                )}
                            </nav>
                        )}
                    </>
                )}
            </section>
        </div>
    );
}
