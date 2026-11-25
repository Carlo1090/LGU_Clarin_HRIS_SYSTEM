import { useFeedback } from '@/components/feedback-provider';
import { useTableSearch } from '@/components/table-search';
import TableAction from '@/components/table-action';
import PersonAvatar from '@/components/person-avatar';
import {
    Table,
    TableHeader,
    TableHead,
    TableBody,
    TableCell,
    TableRow,
} from '@/components/ui/table';
import WorkspaceHeader from '@/components/workspace-header';
import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    Check,
    X,
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
    avatar?: string | null;
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
    const search = useTableSearch(
        records.data,
        [
            'Employee',
            'Attendance date',
            'Time in',
            'Time out',
            'Reason',
            'Status',
            'Day type',
        ],
        (row) => [
            row.employee,
            row.date,
            row.timeIn,
            row.timeOut,
            row.reason,
            row.status.replaceAll('_', ' '),
            row.missingDay ? 'Missing day' : 'Existing record',
        ],
    );
    const showReview = records.data.some((row) => row.canReview);
    const columns = [
        'Employee',
        'Attendance date',
        'Proposed time in',
        'Proposed time out',
        'Reason',
        'Status',
    ];
    if (showReview) columns.push('Actions');
    const form = useForm({
        attendance_record_id: '',
        time_in: '',
        time_out: '',
        reason: '',
    });
    const [reviewing, setReviewing] = useState(false);
    const [decisionError, setDecisionError] = useState('');

    const feedback = useFeedback();
    async function review(id: number, decision: 'advance' | 'reject') {
        if (
            !(await feedback.confirm(
                decision === 'advance'
                    ? advanceLabel + '?'
                    : 'Reject this correction?',
            ))
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
        <div className="workspace-page">
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
                {search.toolbar}
                <Table
                    aria-labelledby="correction-history"
                    className="workspace-record-table"
                >
                    <TableHeader className="workspace-table-head">
                        <TableRow>
                            {columns.map((column) => (
                                <TableHead scope="col" key={column}>
                                    {column}
                                </TableHead>
                            ))}
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {search.rows.map((row) => (
                            <TableRow key={row.id}>
                                <TableHead scope="row">
                                    <span className="inline-flex items-center gap-2">
                                        <PersonAvatar
                                            name={row.employee}
                                            src={row.avatar}
                                        />
                                        {row.employee}
                                    </span>
                                </TableHead>
                                <TableCell>
                                    {row.date ?? 'Date unavailable'}
                                    {row.missingDay && (
                                        <span className="block text-xs text-muted-foreground">
                                            Missing day
                                        </span>
                                    )}
                                </TableCell>
                                <TableCell>{row.timeIn ?? '—'}</TableCell>
                                <TableCell>{row.timeOut ?? '—'}</TableCell>
                                <TableCell className="max-w-sm min-w-48 break-words whitespace-normal">
                                    {row.reason}
                                </TableCell>
                                <TableCell>
                                    <span className="rounded-full bg-accent px-3 py-1 text-xs text-accent-foreground capitalize">
                                        {row.status.replaceAll('_', ' ')}
                                    </span>
                                </TableCell>
                                {showReview && (
                                    <TableCell>
                                        {row.canReview && (
                                            <div className="flex gap-2">
                                                <TableAction
                                                    icon={Check}
                                                    label={advanceLabel}
                                                    disabled={reviewing}
                                                    onClick={() =>
                                                        review(
                                                            row.id,
                                                            'advance',
                                                        )
                                                    }
                                                />
                                                <TableAction
                                                    icon={X}
                                                    label="Reject correction"
                                                    className="text-destructive"
                                                    disabled={reviewing}
                                                    onClick={() =>
                                                        review(row.id, 'reject')
                                                    }
                                                />
                                            </div>
                                        )}
                                    </TableCell>
                                )}
                            </TableRow>
                        ))}
                        {!search.rows.length && (
                            <TableRow>
                                <TableCell
                                    colSpan={columns.length}
                                    className="py-8 text-center text-muted-foreground"
                                >
                                    No corrections found.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
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
