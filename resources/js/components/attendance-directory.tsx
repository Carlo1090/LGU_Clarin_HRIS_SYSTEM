import { DirectorySearch } from '@/components/table-search';
import { WorkspaceMetrics } from '@/components/workspace-metrics';
import { Form, Head, Link, usePage } from '@inertiajs/react';
import {
    CalendarCheck2,
    CalendarClock,
    CalendarMinus,
    UsersRound,
} from 'lucide-react';
import AttendanceWorkspaceHeader from '@/components/attendance-workspace-header';
import RecordPagination, {
    type RecordPageLink,
} from '@/components/record-pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import PersonAvatar from '@/components/person-avatar';

type EmployeeAttendance = {
    id: number;
    name: string;
    employee_number: string;
    avatar?: string | null;
    department: string | null;
    attendance_complete_days: number;
    attendance_review_days: number;
    requests_pending: number;
    review_days: { date: string; finding: string | null; state: string }[];
};

export type AttendanceDirectoryProps = {
    attendanceDirectory: true;
    title: string;
    attendanceMonth: string;
    canManageAttendance: boolean;
    unlinkedMachineIds: number;
    records: {
        data: EmployeeAttendance[];
        from: number | null;
        to: number | null;
        total: number;
        links: RecordPageLink[];
    };
};

const statuses = {
    complete: {
        label: 'Complete logs',
        icon: CalendarCheck2,
        badge: 'bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200',
    },
    review: {
        label: 'Employees with flagged days',
        icon: CalendarClock,
        badge: 'bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-200',
    },
    empty: {
        label: 'No work logs',
        icon: CalendarMinus,
        badge: 'bg-muted text-muted-foreground',
    },
};

function statusFor(employee: EmployeeAttendance): keyof typeof statuses {
    if (employee.attendance_review_days > 0) return 'review';
    if (employee.attendance_complete_days > 0) return 'complete';
    return 'empty';
}

export default function AttendanceDirectory({
    title,
    attendanceMonth,
    canManageAttendance,
    unlinkedMachineIds,
    records,
}: AttendanceDirectoryProps) {
    const { url, props } = usePage();
    const search = new URL(url, 'http://localhost').searchParams;
    const totals = { complete: 0, review: 0, empty: 0 };
    for (const employee of records.data) totals[statusFor(employee)]++;
    const monthLabel = new Intl.DateTimeFormat('en-PH', {
        month: 'long',
        year: 'numeric',
        timeZone: 'UTC',
    }).format(new Date(attendanceMonth + '-01T00:00:00Z'));
    const metrics = [
        {
            label: 'Employees on this page',
            icon: UsersRound,
            count: records.data.length,
        },
        ...(['complete', 'review', 'empty'] as const).map((key) => ({
            ...statuses[key],
            count: totals[key],
        })),
    ];

    return (
        <div className="workspace-page">
            <Head title={title} />
            <AttendanceWorkspaceHeader
                active="records"
                month={attendanceMonth}
                canManage={canManageAttendance}
            />
            <section
                className="dashboard-metrics-banner"
                aria-labelledby="attendance-overview"
            >
                <div className="mb-5 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h2
                            id="attendance-overview"
                            className="text-xl font-semibold tracking-tight"
                        >
                            {monthLabel}
                        </h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Log status for the employees on this page.
                        </p>
                        {unlinkedMachineIds > 0 && (
                            <Link
                                className="text-sm underline"
                                href={`/hris/attendance/logs?month=${attendanceMonth}&unassigned=1`}
                            >
                                {unlinkedMachineIds} unlinked machine{' '}
                                {unlinkedMachineIds === 1 ? 'ID' : 'IDs'} this
                                month
                            </Link>
                        )}
                    </div>
                    <Form
                        key={attendanceMonth}
                        action="/hris/attendance"
                        method="get"
                        className="flex max-w-full flex-wrap items-end gap-2"
                    >
                        {['q', 'column'].map(
                            (key) =>
                                search.has(key) && (
                                    <input
                                        key={key}
                                        type="hidden"
                                        name={key}
                                        value={search.get(key) ?? ''}
                                    />
                                ),
                        )}
                        <label className="grid min-w-0 gap-1 text-xs font-medium">
                            Attendance month
                            <Input
                                name="month"
                                type="month"
                                defaultValue={attendanceMonth}
                                required
                                className="w-full bg-card"
                            />
                        </label>
                        <Button type="submit" className="dashboard-action">
                            Show month
                        </Button>
                    </Form>
                </div>
                <WorkspaceMetrics metrics={metrics} />
            </section>
            <section
                className="workspace-panel overflow-hidden"
                aria-labelledby="attendance-employees"
            >
                <div className="flex flex-wrap items-center justify-between gap-2 px-4 py-5 sm:px-6">
                    <h2
                        id="attendance-employees"
                        className="text-lg font-semibold tracking-tight"
                    >
                        Employee attendance
                    </h2>
                    <span className="text-xs text-muted-foreground tabular-nums">
                        {records.from && records.to
                            ? `${records.from}–${records.to} of ${records.total} employees`
                            : `${records.total} employees`}
                    </span>
                </div>
                <DirectorySearch
                    columns={[
                        { name: 'employee', label: 'Employee' },
                        { name: 'department', label: 'Department' },
                    ]}
                    label="Search employee attendance"
                />
                {records.data.length > 0 ? (
                    <ul className="divide-y border-t">
                        {records.data.map((employee) => {
                            return (
                                <li
                                    key={employee.id}
                                    className="grid gap-3 px-4 py-4 transition-colors hover:bg-muted/50 sm:grid-cols-[minmax(0,1fr)_10rem_auto] sm:items-center sm:px-6"
                                >
                                    <div className="flex min-w-0 items-center gap-3">
                                        <PersonAvatar
                                            name={employee.name}
                                            src={employee.avatar}
                                        />
                                        <div className="min-w-0">
                                            <p className="font-semibold break-words">
                                                {employee.name}
                                            </p>
                                            <p className="mt-1 text-xs break-words text-muted-foreground">
                                                {employee.employee_number} ·{' '}
                                                {employee.department ??
                                                    'Unassigned department'}
                                            </p>
                                        </div>
                                    </div>
                                    <div className="flex flex-wrap gap-1">
                                        <Badge className={employee.attendance_review_days ? statuses.review.badge : statuses.empty.badge}>
                                            Flagged days · {employee.attendance_review_days}
                                        </Badge>
                                        <Badge variant="outline">
                                            Requests pending · {employee.requests_pending}
                                        </Badge>
                                    </div>
                                    <div className="flex flex-wrap items-center gap-2">
                                        {props.canViewAttendanceCorrections && (
                                            <Button asChild variant="outline" size="sm">
                                                <Link
                                                    href="/hris/attendance/corrections"
                                                    data={{ employee: employee.id, month: attendanceMonth }}
                                                >
                                                    Corrections ({employee.requests_pending})
                                                </Link>
                                            </Button>
                                        )}
                                        <Button
                                            asChild
                                            variant="outline"
                                            size="sm"
                                        >
                                            <Link
                                                href="/hris/attendance/daily-punches"
                                                data={{
                                                    month: attendanceMonth,
                                                    employee: employee.id,
                                                }}
                                            >
                                                Daily punches
                                            </Link>
                                        </Button>
                                    </div>
                                    {employee.review_days.length > 0 && (
                                        <details className="text-sm sm:col-span-full">
                                            <summary className="cursor-pointer">Day details</summary>
                                            <ul className="mt-2 divide-y rounded-md border px-3">
                                                {employee.review_days.map(({ date, finding, state }) => (
                                                    <li key={date} className="grid gap-1 py-2 sm:grid-cols-3">
                                                        <span>{date}</span>
                                                        <span className="capitalize">{finding?.replaceAll('_', ' ') ?? 'Resolved'}</span>
                                                        <span>{state}</span>
                                                    </li>
                                                ))}
                                            </ul>
                                        </details>
                                    )}
                                </li>
                            );
                        })}
                    </ul>
                ) : (
                    <p className="border-t p-6 text-sm text-muted-foreground">
                        No employees found.
                    </p>
                )}
                <RecordPagination links={records.links} />
            </section>
        </div>
    );
}
