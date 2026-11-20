import WorkspaceHeader from '@/components/workspace-header';
import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    CalendarClock,
    CalendarMinus,
    ClipboardList,
    UserCheck,
} from 'lucide-react';
import { WorkspaceOverview } from '@/components/workspace-metrics';
import { useState } from 'react';

const path = '/hris/attendance-corrections';
const control = 'w-full rounded-xl border border-input bg-background px-3 py-2';
const button =
    'rounded-full bg-primary px-4 py-2 text-sm font-medium text-primary-foreground disabled:opacity-50';

type Log = {
    id: number;
    attendance_date: string;
    time_in: string | null;
    time_out: string | null;
};

type Correction = {
    id: number;
    date: string | null;
    missingDay: boolean;
    employee: string;
    timeIn: string | null;
    timeOut: string | null;
    reason: string;
    status: string;
    canReview: boolean;
};

export default function AttendanceCorrections({
    records,
    logs,
    advanceLabel,
}: {
    records: {
        data: Correction[];
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    logs: Log[] | null;
    advanceLabel: string;
}) {
    const form = useForm({
        attendance_record_id: '',
        time_in: '',
        time_out: '',
        reason: '',
    });
    const [reviewing, setReviewing] = useState(false);
    const [decisionError, setDecisionError] = useState('');

    function review(id: number, decision: 'advance' | 'reject') {
        if (
            !window.confirm(
                decision === 'advance'
                    ? advanceLabel + '?'
                    : 'Reject this correction?',
            )
        )
            return;
        setDecisionError('');
        setReviewing(true);
        router.post(
            `${path}/${id}/review`,
            { decision },
            {
                preserveScroll: true,
                onError: (errors) =>
                    setDecisionError(
                        errors.decision ?? 'Could not save the decision.',
                    ),
                onFinish: () => setReviewing(false),
            },
        );
    }

    return (
        <div className="workspace-page max-w-7xl">
            <Head title="Attendance corrections" />
            <WorkspaceHeader
                title="Attendance corrections"
                description="Request or review missing days and corrections to daily time records."
            />

            <WorkspaceOverview
                title="Correction overview"
                description="Requests on this page and their review status."
                metrics={[
                    {
                        label: 'Requests',
                        count: records.data.length,
                        icon: ClipboardList,
                    },
                    {
                        label: 'Missing days',
                        count: records.data.filter((row) => row.missingDay)
                            .length,
                        icon: CalendarMinus,
                    },
                    {
                        label: 'In review',
                        count: records.data.filter((row) =>
                            ['pending', 'department_reviewed'].includes(
                                row.status,
                            ),
                        ).length,
                        icon: CalendarClock,
                    },
                    {
                        label: 'Ready for your review',
                        count: records.data.filter((row) => row.canReview)
                            .length,
                        icon: UserCheck,
                    },
                ]}
            />
            {logs && (
                <section
                    aria-labelledby="request-heading"
                    className="workspace-panel p-6"
                >
                    <h2 id="request-heading" className="text-lg font-semibold">
                        New correction
                    </h2>
                    <form
                        className="mt-4 grid gap-4 sm:grid-cols-2"
                        onSubmit={(event) => {
                            event.preventDefault();
                            form.post(path, {
                                preserveScroll: true,
                                onSuccess: () => form.reset(),
                            });
                        }}
                    >
                        <label className="grid gap-1 text-sm sm:col-span-2">
                            Attendance record
                            <select
                                className={control}
                                value={form.data.attendance_record_id}
                                onChange={(event) => {
                                    const log = logs.find(
                                        ({ id }) =>
                                            String(id) === event.target.value,
                                    );
                                    form.setData({
                                        ...form.data,
                                        attendance_record_id:
                                            event.target.value,
                                        time_in:
                                            log?.time_in
                                                ?.replace(' ', 'T')
                                                .slice(0, 16) ?? '',
                                        time_out:
                                            log?.time_out
                                                ?.replace(' ', 'T')
                                                .slice(0, 16) ?? '',
                                    });
                                }}
                            >
                                <option value="">
                                    Missing day (no existing record)
                                </option>
                                {logs.map((log) => (
                                    <option key={log.id} value={log.id}>
                                        {log.attendance_date} · Record #{log.id}
                                    </option>
                                ))}
                            </select>
                            {form.errors.attendance_record_id && (
                                <span className="text-destructive">
                                    {form.errors.attendance_record_id}
                                </span>
                            )}
                        </label>
                        {(['time_in', 'time_out'] as const).map((field) => (
                            <label key={field} className="grid gap-1 text-sm">
                                {field === 'time_in'
                                    ? 'Corrected time in'
                                    : 'Corrected time out'}
                                <input
                                    required
                                    type="datetime-local"
                                    className={control}
                                    value={form.data[field]}
                                    onChange={(event) =>
                                        form.setData(field, event.target.value)
                                    }
                                />
                                {form.errors[field] && (
                                    <span className="text-destructive">
                                        {form.errors[field]}
                                    </span>
                                )}
                            </label>
                        ))}
                        <label className="grid gap-1 text-sm sm:col-span-2">
                            Reason
                            <textarea
                                required
                                maxLength={2000}
                                rows={3}
                                className={control}
                                value={form.data.reason}
                                onChange={(event) =>
                                    form.setData('reason', event.target.value)
                                }
                            />
                            {form.errors.reason && (
                                <span className="text-destructive">
                                    {form.errors.reason}
                                </span>
                            )}
                        </label>
                        <div className="flex items-center gap-3 sm:col-span-2">
                            <button
                                type="submit"
                                className={button}
                                disabled={form.processing}
                            >
                                Submit correction
                            </button>
                            {form.recentlySuccessful && (
                                <span
                                    role="status"
                                    className="text-sm text-accent-foreground"
                                >
                                    Correction submitted.
                                </span>
                            )}
                        </div>
                    </form>
                </section>
            )}

            {decisionError && (
                <p role="alert" className="text-sm text-destructive">
                    {decisionError}
                </p>
            )}
            <section
                aria-labelledby="correction-history"
                className="workspace-panel overflow-hidden"
            >
                <header className="flex flex-wrap items-center justify-between gap-2 px-4 py-5 sm:px-6">
                    <h2
                        id="correction-history"
                        className="text-lg font-semibold"
                    >
                        Correction history
                    </h2>
                    <span className="text-xs text-muted-foreground">
                        {records.data.length} on this page
                    </span>
                </header>
                {!records.data.length && (
                    <p className="border-t p-6 text-sm text-muted-foreground">
                        No corrections found.
                    </p>
                )}
                {records.data.map((row) => (
                    <article
                        key={row.id}
                        className="space-y-3 border-t px-4 py-5 sm:px-6"
                    >
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <h3 className="font-semibold">
                                {row.date ?? 'Date unavailable'} ·{' '}
                                {row.employee}
                                {row.missingDay && ' · Missing day'}
                            </h3>
                            <span className="rounded-full bg-accent px-3 py-1 text-xs text-accent-foreground capitalize">
                                {row.status.replaceAll('_', ' ')}
                            </span>
                        </div>
                        <dl className="grid gap-2 text-sm sm:grid-cols-2">
                            <div>
                                <dt className="text-muted-foreground">
                                    Proposed time in
                                </dt>
                                <dd>{row.timeIn ?? '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Proposed time out
                                </dt>
                                <dd>{row.timeOut ?? '—'}</dd>
                            </div>
                        </dl>
                        <p className="text-sm break-words text-muted-foreground">
                            {row.reason}
                        </p>
                        {row.canReview && (
                            <div className="flex gap-2">
                                <button
                                    type="button"
                                    className={button}
                                    disabled={reviewing}
                                    onClick={() => review(row.id, 'advance')}
                                >
                                    {advanceLabel}
                                </button>
                                <button
                                    type="button"
                                    className="rounded-full border px-4 py-2 text-sm text-destructive"
                                    disabled={reviewing}
                                    onClick={() => review(row.id, 'reject')}
                                >
                                    Reject
                                </button>
                            </div>
                        )}
                    </article>
                ))}
                {(records.prev_page_url || records.next_page_url) && (
                    <nav
                        aria-label="Correction pages"
                        className="flex gap-4 border-t px-5 py-3 text-sm"
                    >
                        {records.prev_page_url && (
                            <Link
                                className="underline"
                                href={records.prev_page_url}
                            >
                                Previous
                            </Link>
                        )}
                        {records.next_page_url && (
                            <Link
                                className="underline"
                                href={records.next_page_url}
                            >
                                Next
                            </Link>
                        )}
                    </nav>
                )}
            </section>
        </div>
    );
}
