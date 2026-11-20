import { Form, Head } from '@inertiajs/react';
import {
    CalendarCheck2,
    CalendarClock,
    CalendarMinus,
    UsersRound,
} from 'lucide-react';
import AttendanceWorkspaceHeader from '@/components/attendance-workspace-header';
import DailyTimeRecordDialog from '@/components/daily-time-record-dialog';
import RecordPagination, {
    type RecordPageLink,
} from '@/components/record-pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useInitials } from '@/hooks/use-initials';

type EmployeeAttendance = {
    id: number;
    name: string;
    employee_number: string;
    department: string | null;
    attendance_complete_days: number;
    attendance_review_days: number;
    dtr_url: string;
};

export type AttendanceDirectoryProps = {
    attendanceDirectory: true;
    title: string;
    attendanceMonth: string;
    canManageAttendance: boolean;
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
        label: 'Needs review',
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
    records,
}: AttendanceDirectoryProps) {
    const initials = useInitials();
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
        <div className="workspace-page max-w-7xl">
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
                    </div>
                    <Form
                        key={attendanceMonth}
                        action="/hris/attendance"
                        method="get"
                        className="flex max-w-full flex-wrap items-end gap-2"
                    >
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
                <dl className="grid grid-cols-2 gap-3 xl:grid-cols-4">
                    {metrics.map(({ label, icon: Icon, count }) => (
                        <div
                            key={label}
                            className="workspace-metric flex flex-col justify-between gap-3 p-4 sm:p-5"
                        >
                            <dt className="flex flex-wrap items-center justify-between gap-2 text-xs font-medium">
                                {label}
                                <Icon aria-hidden="true" className="size-4" />
                            </dt>
                            <dd className="text-3xl font-semibold tracking-tight">
                                {count}
                            </dd>
                        </div>
                    ))}
                </dl>
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
                {records.data.length > 0 ? (
                    <ul className="divide-y border-t">
                        {records.data.map((employee) => {
                            const status = statuses[statusFor(employee)];
                            return (
                                <li
                                    key={employee.id}
                                    className="grid gap-3 px-4 py-4 transition-colors hover:bg-muted/50 sm:grid-cols-[minmax(0,1fr)_10rem_auto] sm:items-center sm:px-6"
                                >
                                    <div className="flex min-w-0 items-center gap-3">
                                        <span
                                            aria-hidden="true"
                                            className="grid size-10 shrink-0 place-items-center rounded-xl bg-accent text-sm font-semibold text-accent-foreground"
                                        >
                                            {initials(employee.name)}
                                        </span>
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
                                    <div>
                                        <Badge className={status.badge}>
                                            {status.label}
                                        </Badge>
                                        {employee.attendance_complete_days +
                                            employee.attendance_review_days >
                                            0 && (
                                            <p className="mt-1 text-xs text-muted-foreground tabular-nums">
                                                {
                                                    employee.attendance_complete_days
                                                }{' '}
                                                complete ·{' '}
                                                {
                                                    employee.attendance_review_days
                                                }{' '}
                                                to review
                                            </p>
                                        )}
                                    </div>
                                    <div>
                                        <DailyTimeRecordDialog
                                            url={employee.dtr_url}
                                        />
                                    </div>
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
