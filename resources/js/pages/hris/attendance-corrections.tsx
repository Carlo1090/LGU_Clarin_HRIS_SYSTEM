import AttendanceWorkspaceHeader from '@/components/attendance-workspace-header';
import WorkspaceHeader from '@/components/workspace-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Head, Link, useForm } from '@inertiajs/react';

const path = '/hris/attendance/corrections';
const slots = {
    am_in: 'AM in',
    am_out: 'AM out',
    pm_in: 'PM in',
    pm_out: 'PM out',
} as const;

type Correction = {
    id: number;
    date: string;
    employee: string;
    slot: keyof typeof slots | null;
    proposedAt: string | null;
    legacyTimes: string[];
    reason: string;
    evidence: string | null;
    filer: string | null;
    status: string;
    comment: string | null;
    canReview: boolean;
};

type Props = {
    records: {
        data: Correction[];
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    selected: (Correction & { punches: string[] }) | null;
    employeeId: number | null;
    employeeNumber: string | null;
    employeeView: boolean;
    month: string;
    flagged: Record<string, string>;
    punches: Record<string, string[]>;
    canFile: boolean;
    canManageAttendance: boolean;
};

function statusLabel(status: string) {
    return ['pending', 'department_reviewed'].includes(status)
        ? 'Requested'
        : status.replaceAll('_', ' ');
}

export default function AttendanceCorrections({
    records,
    selected,
    employeeId,
    employeeNumber,
    employeeView,
    month,
    flagged,
    punches,
    canFile,
    canManageAttendance,
}: Props) {
    const filter = { month, ...(employeeId && !employeeView ? { employee: employeeId } : {}) };
    const dates = Object.keys({ ...flagged, ...punches }).sort();
    const request = useForm({
        employee: employeeView ? null : employeeId,
        work_date: '',
        slot: '',
        proposed_at: '',
        reason: '',
        evidence: '',
    });
    const review = useForm({ decision: '', comment: '' });

    return (
        <div className="workspace-page">
            <Head title={employeeView ? 'My attendance' : 'Attendance records'} />
            {employeeView ? (
                <WorkspaceHeader title="My attendance" description="View your punches, flagged days, and correction requests.">
                    <Link href="/hris/portal/my-attendance" className="underline">
                        Daily time records
                    </Link>
                </WorkspaceHeader>
            ) : (
                <AttendanceWorkspaceHeader active="corrections" month={month} canManage={canManageAttendance} />
            )}

            {employeeId && (
                <section className="workspace-panel space-y-4 p-4">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <h2 className="font-semibold">
                            {employeeView ? 'My punches and flagged days' : `Flagged days for ${employeeNumber}`}
                        </h2>
                        <form action={path} className="flex items-end gap-2">
                            {!employeeView && <input type="hidden" name="employee" value={employeeId} />}
                            <label className="grid gap-1 text-sm">
                                Attendance month
                                <Input type="month" name="month" defaultValue={month} required />
                            </label>
                            <Button type="submit" variant="outline">Show month</Button>
                        </form>
                    </div>
                    {dates.length ? (
                        <ul className="divide-y border-y">
                            {dates.map((date) => (
                                <li key={date} className="flex flex-wrap items-center justify-between gap-2 py-2 text-sm">
                                    <div>
                                        <strong>{date}</strong>
                                        {flagged[date] && (
                                            <span className="ml-2 capitalize">{flagged[date].replaceAll('_', ' ')}</span>
                                        )}
                                        {employeeView && (
                                            <p className="text-muted-foreground">
                                                {punches[date]?.join(' · ') || 'No recorded punches'}
                                            </p>
                                        )}
                                    </div>
                                    {canFile && flagged[date] && (
                                        <Button asChild size="sm" variant="outline">
                                            <a href="#file-correction" onClick={() => {
                                                request.setData({
                                                    ...request.data,
                                                    work_date: date,
                                                    proposed_at: '',
                                                });
                                            }}>
                                                Request correction
                                            </a>
                                        </Button>
                                    )}
                                </li>
                            ))}
                        </ul>
                    ) : <p className="text-sm text-muted-foreground">No days to display this month.</p>}
                </section>
            )}

            {canFile && Object.keys(flagged).length > 0 && (
                <section id="file-correction" className="workspace-panel p-4">
                    <h2 className="font-semibold">{employeeView ? 'File a correction' : `File for ${employeeNumber}`}</h2>
                    <form className="mt-3 grid gap-3 sm:grid-cols-2" onSubmit={(event) => {
                        event.preventDefault();
                        request.post(path, { preserveScroll: true, onSuccess: () => request.reset() });
                    }}>
                        <label className="grid gap-1 text-sm">
                            Flagged date
                            <select className="rounded-md border bg-background p-2" required value={request.data.work_date}
                                onChange={(event) => request.setData('work_date', event.target.value)}>
                                <option value="">Choose a date</option>
                                {Object.keys(flagged).map((date) => <option key={date}>{date}</option>)}
                            </select>
                        </label>
                        <label className="grid gap-1 text-sm">
                            Slot
                            <select className="rounded-md border bg-background p-2" required value={request.data.slot}
                                onChange={(event) => request.setData('slot', event.target.value)}>
                                <option value="">Choose a slot</option>
                                {Object.entries(slots).map(([key, label]) => <option value={key} key={key}>{label}</option>)}
                            </select>
                        </label>
                        <label className="grid gap-1 text-sm">
                            Proposed date and time
                            <Input type="datetime-local" required value={request.data.proposed_at}
                                onChange={(event) => request.setData('proposed_at', event.target.value)} />
                        </label>
                        <label className="grid gap-1 text-sm">
                            Evidence or memo reference (optional)
                            <Input value={request.data.evidence} maxLength={2000}
                                onChange={(event) => request.setData('evidence', event.target.value)} />
                        </label>
                        <label className="grid gap-1 text-sm sm:col-span-2">
                            {employeeView ? 'Reason' : 'Reason for filing on the employee’s behalf'}
                            <textarea className="rounded-md border bg-background p-2" required maxLength={2000} value={request.data.reason}
                                onChange={(event) => request.setData('reason', event.target.value)} />
                        </label>
                        {Object.values(request.errors).map((error) => <p key={error} role="alert" className="text-sm text-destructive">{error}</p>)}
                        <div><Button disabled={request.processing}>Submit correction</Button></div>
                    </form>
                </section>
            )}

            {selected && (
                <section id="request-details" className="workspace-panel space-y-4 p-4">
                    <h2 className="font-semibold">Request #{selected.id} · {selected.employee} · {selected.date}</h2>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div>
                            <h3 className="text-sm font-semibold">Raw punches</h3>
                            {selected.punches.length ? (
                                <ul className="text-sm">{selected.punches.map((time, index) => <li key={index}>{time}</li>)}</ul>
                            ) : <p className="text-sm">No recorded punches.</p>}
                        </div>
                        <div className="space-y-1 text-sm">
                            <h3 className="font-semibold">Proposal</h3>
                            <p>{selected.slot ? `${slots[selected.slot]} · ${selected.proposedAt}` : selected.legacyTimes.join(' · ')}</p>
                            <p>Filed by {selected.filer ?? 'Unrecorded'}</p>
                            <p>{selected.reason}</p>
                            {selected.evidence && <p>Evidence or memo reference · {selected.evidence}</p>}
                            <p className="capitalize">Status · {statusLabel(selected.status)}</p>
                            {selected.comment && <p>Decision comment · {selected.comment}</p>}
                        </div>
                    </div>
                    {selected.canReview && (
                        <form key={selected.id} className="space-y-2" onSubmit={(event) => {
                            event.preventDefault();
                            const button = (event.nativeEvent as SubmitEvent).submitter as HTMLButtonElement | null;
                            if (!button?.value) return;
                            review.transform((data) => ({ ...data, decision: button.value }));
                            review.post(`${path}/${selected.id}/review`, { preserveScroll: true, onSuccess: () => review.reset() });
                        }}>
                            {!selected.slot && <p className="text-sm">Return this older request for filing against a specific slot.</p>}
                            <label className="grid gap-1 text-sm">
                                Decision comment (required when returning)
                                <textarea className="rounded-md border bg-background p-2" maxLength={2000} value={review.data.comment}
                                    onChange={(event) => review.setData('comment', event.target.value)} />
                            </label>
                            {Object.values(review.errors).map((error) => <p key={error} role="alert" className="text-sm text-destructive">{error}</p>)}
                            <div className="flex flex-wrap gap-2">
                                {selected.slot && <Button value="approve" disabled={review.processing}>Approve</Button>}
                                <Button value="return" variant="outline" disabled={review.processing}>Return with comment</Button>
                                <Button value="reject" variant="destructive" disabled={review.processing}>Reject</Button>
                            </div>
                        </form>
                    )}
                </section>
            )}

            <section className="workspace-panel space-y-3 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <h2 className="font-semibold">Correction requests</h2>
                    {!employeeView && employeeId && <Link href={path} className="text-sm underline">Show all employees</Link>}
                </div>
                {records.data.length ? (
                    <ul className="divide-y border-y">
                        {records.data.map((row) => (
                            <li key={row.id} className="flex flex-wrap items-center justify-between gap-2 py-2 text-sm">
                                <span>#{row.id} · {row.employee} · {row.date} {row.slot && `· ${slots[row.slot]}`}</span>
                                <span className="capitalize">{statusLabel(row.status)}</span>
                                <Link className="underline" href={path} data={{ ...filter, request: row.id }} preserveScroll={false}>
                                    {row.canReview ? 'Review request' : 'View request'}
                                </Link>
                            </li>
                        ))}
                    </ul>
                ) : <p className="text-sm text-muted-foreground">No correction requests.</p>}
                <nav aria-label="Request pages" className="flex gap-4 text-sm">
                    {records.prev_page_url && <Link className="underline" href={records.prev_page_url}>Previous</Link>}
                    {records.next_page_url && <Link className="underline" href={records.next_page_url}>Next</Link>}
                </nav>
            </section>
        </div>
    );
}
