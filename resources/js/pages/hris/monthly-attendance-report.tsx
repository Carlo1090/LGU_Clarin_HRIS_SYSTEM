import { Form, Head, Link, useForm } from '@inertiajs/react';
import AttendanceWorkspaceHeader from '@/components/attendance-workspace-header';
import { ArrowDown, ArrowUp, ArrowUpDown } from 'lucide-react';
import { Fragment, type FormEvent } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type Sort = 'days_attended' | 'late_arrivals';
type Direction = 'asc' | 'desc';
type EmployeeSummary = {
    id: number;
    name: string;
    employee_number: string;
    days_attended: number;
    late_arrivals: number;
    undetermined_days: number;
};
type ClockInRule = {
    starts_at: string | null;
    grace_minutes: number;
    effective_from: string;
};
type Day = {
    work_date: string;
    punch_count: number;
    first_punch: string;
    last_punch: string;
    clock_in: string | null;
    starts_at: string | null;
    grace_minutes: number | null;
    lateness: 'on_time' | 'late' | 'undetermined';
};
type EmployeeDetail = {
    id: number;
    name: string;
    has_biometric_id: boolean;
    rule: ClockInRule | null;
    days: Day[];
};

const reportUrl = '/hris/attendance/monthly-report';
const sortColumns = [
    ['days_attended', 'Days attended'],
    ['late_arrivals', 'Late arrivals'],
] as const;
const latenessLabels = {
    on_time: 'On time',
    late: 'Late',
    undetermined: 'Undetermined',
};

function DailyRecords({
    detail,
    month,
}: {
    detail: EmployeeDetail;
    month: string;
}) {
    const form = useForm({
        effective_from: detail.rule?.effective_from.startsWith(month)
            ? detail.rule.effective_from
            : `${month}-01`,
        starts_at: detail.rule?.starts_at ?? '',
        grace_minutes: detail.rule?.grace_minutes.toString() ?? '0',
    });

    function save(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        form.put(`${reportUrl}/${detail.id}/clock-in-rule`, {
            preserveScroll: true,
        });
    }

    return (
        <section
            aria-label={`Daily attendance for ${detail.name}`}
            className="space-y-4 bg-muted/40 p-4"
        >
            <h2 className="font-semibold">Daily attendance · {detail.name}</h2>
            <details className="space-y-3 rounded-md border bg-card p-3">
                <summary className="cursor-pointer font-medium">
                    Set clock-in rule
                </summary>
                {detail.rule && (
                    <p className="text-sm text-muted-foreground">
                        Latest rule through this month ·{' '}
                        {detail.rule.effective_from} ·{' '}
                        {detail.rule.starts_at
                            ? `${detail.rule.starts_at} plus ${detail.rule.grace_minutes} min grace`
                            : 'Start time undetermined'}
                    </p>
                )}
                <form
                    onSubmit={save}
                    className="flex flex-wrap items-end gap-3"
                >
                    <label className="grid gap-1 text-sm">
                        Effective from
                        <Input
                            type="date"
                            required
                            value={form.data.effective_from}
                            onChange={(event) =>
                                form.setData(
                                    'effective_from',
                                    event.target.value,
                                )
                            }
                        />
                    </label>
                    <label className="grid gap-1 text-sm">
                        Scheduled start (optional)
                        <Input
                            type="time"
                            value={form.data.starts_at}
                            onChange={(event) =>
                                form.setData('starts_at', event.target.value)
                            }
                        />
                    </label>
                    <label className="grid gap-1 text-sm">
                        Grace minutes
                        <Input
                            type="number"
                            required
                            min={0}
                            max={1439}
                            value={form.data.grace_minutes}
                            onChange={(event) =>
                                form.setData(
                                    'grace_minutes',
                                    event.target.value,
                                )
                            }
                        />
                    </label>
                    <Button type="submit" disabled={form.processing}>
                        Save rule
                    </Button>
                    {Object.keys(form.errors).length > 0 && (
                        <ul
                            role="alert"
                            className="basis-full space-y-1 text-sm text-destructive"
                        >
                            {Object.entries(form.errors).map(([key, error]) => (
                                <li key={key}>{error}</li>
                            ))}
                        </ul>
                    )}
                    <p className="basis-full text-xs text-muted-foreground">
                        The rule applies from its effective date until a newer
                        rule takes effect. A blank start makes lateness
                        undetermined.
                    </p>
                </form>
            </details>
            {detail.days.length ? (
                <div className="overflow-x-auto rounded-md border bg-card">
                    <table
                        aria-label="Daily biometric records"
                        className="w-full text-left text-sm whitespace-nowrap"
                    >
                        <thead className="workspace-table-head">
                            <tr>
                                {[
                                    'Date',
                                    'Punches',
                                    'First punch',
                                    'Last punch',
                                    'Clock-in',
                                    'Scheduled start',
                                    'Lateness',
                                ].map((label) => (
                                    <th
                                        key={label}
                                        scope="col"
                                        className="p-2.5 font-medium"
                                    >
                                        {label}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {detail.days.map((day) => (
                                <tr
                                    key={day.work_date}
                                    className="border-t tabular-nums"
                                >
                                    <th
                                        scope="row"
                                        className="p-2.5 font-medium"
                                    >
                                        {day.work_date}
                                    </th>
                                    <td className="p-2.5">{day.punch_count}</td>
                                    <td className="p-2.5">
                                        {day.first_punch.slice(11)}
                                    </td>
                                    <td className="p-2.5">
                                        {day.last_punch.slice(11)}
                                    </td>
                                    <td className="p-2.5">
                                        {day.clock_in ?? '—'}
                                    </td>
                                    <td className="p-2.5">
                                        {day.starts_at
                                            ? `${day.starts_at} + ${day.grace_minutes ?? 0} min`
                                            : '—'}
                                    </td>
                                    <td className="p-2.5">
                                        <Badge
                                            variant={
                                                day.lateness === 'late'
                                                    ? 'destructive'
                                                    : 'secondary'
                                            }
                                        >
                                            {latenessLabels[day.lateness]}
                                        </Badge>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            ) : detail.has_biometric_id ? (
                <p className="text-sm text-muted-foreground">
                    No imported punches for this month.
                </p>
            ) : (
                <p className="text-sm text-muted-foreground">
                    Assign a Biometric ID to this employee on the{' '}
                    <Link href="/hris/employees" className="underline">
                        Employees
                    </Link>{' '}
                    page.
                </p>
            )}
        </section>
    );
}

export default function MonthlyAttendanceReport({
    month,
    sort,
    direction,
    summary,
    unlinkedIds,
    detail,
}: {
    month: string;
    sort: Sort;
    direction: Direction;
    summary: {
        data: EmployeeSummary[];
        current_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    unlinkedIds: number;
    detail: EmployeeDetail | null;
}) {
    const filters = { month, sort, direction };

    return (
        <main className="workspace-page max-w-7xl">
            <Head title="Monthly Attendance Report" />
            <AttendanceWorkspaceHeader
                active="report"
                month={month}
                canManage
            />
            <section aria-label="Report overview" className="space-y-2">
                <h2 className="text-xl font-semibold">
                    Monthly Attendance Report
                </h2>
                <p className="text-sm text-muted-foreground">
                    A date with imported punches counts as one attended day. The
                    first punch on a four-punch day is used as the clock-in.
                    Lateness is undetermined when either a clock-in or scheduled
                    start is unavailable.
                </p>
            </section>
            <div className="flex flex-wrap items-end justify-between gap-4">
                <Form
                    key={month}
                    action={reportUrl}
                    method="get"
                    className="flex flex-wrap items-end gap-2"
                >
                    <label className="grid gap-1 text-sm font-medium">
                        Report month
                        <Input
                            type="month"
                            name="month"
                            defaultValue={month}
                            required
                        />
                    </label>
                    <input type="hidden" name="sort" value={sort} />
                    <input type="hidden" name="direction" value={direction} />
                    <Button
                        type="submit"
                        variant="outline"
                        className="min-w-28"
                    >
                        Show month
                    </Button>
                </Form>
                {unlinkedIds > 0 && (
                    <Button
                        asChild
                        variant="outline"
                        className="h-11 rounded-full"
                    >
                        <Link
                            href="/hris/attendance/logs"
                            data={{ unassigned: 1, month }}
                        >
                            Review {unlinkedIds} unlinked{' '}
                            {unlinkedIds === 1 ? 'ID' : 'IDs'}
                        </Link>
                    </Button>
                )}
            </div>
            <div className="workspace-panel overflow-x-auto">
                <table
                    aria-label="Monthly attendance summary"
                    className="w-full text-left text-sm"
                >
                    <thead className="workspace-table-head">
                        <tr>
                            <th scope="col" className="p-3 font-medium">
                                Employee
                            </th>
                            <th scope="col" className="p-3 font-medium">
                                Employee number
                            </th>
                            {sortColumns.map(([column, label]) => {
                                const active = sort === column;
                                const nextDirection =
                                    active && direction === 'desc'
                                        ? 'asc'
                                        : 'desc';
                                const Icon = active
                                    ? direction === 'asc'
                                        ? ArrowUp
                                        : ArrowDown
                                    : ArrowUpDown;
                                return (
                                    <th
                                        key={column}
                                        scope="col"
                                        aria-sort={
                                            active
                                                ? direction === 'asc'
                                                    ? 'ascending'
                                                    : 'descending'
                                                : undefined
                                        }
                                        className="p-3"
                                    >
                                        <Link
                                            href={reportUrl}
                                            data={{
                                                month,
                                                sort: column,
                                                direction: nextDirection,
                                            }}
                                            aria-label={`Sort by ${label.toLowerCase()} ${nextDirection === 'asc' ? 'ascending' : 'descending'}`}
                                            className="inline-flex items-center gap-1.5 font-medium whitespace-nowrap underline-offset-4 hover:underline"
                                        >
                                            {label}
                                            <Icon
                                                className="size-3.5"
                                                aria-hidden="true"
                                            />
                                        </Link>
                                    </th>
                                );
                            })}
                            <th scope="col" className="p-3 font-medium">
                                Daily records
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {summary.data.map((row) => {
                            const expanded = detail?.id === row.id;
                            const allUndetermined =
                                row.undetermined_days > 0 &&
                                row.undetermined_days === row.days_attended;
                            return (
                                <Fragment key={row.id}>
                                    <tr className="border-t">
                                        <th
                                            scope="row"
                                            className="p-3 font-medium"
                                        >
                                            {row.name}
                                        </th>
                                        <td className="p-3">
                                            {row.employee_number}
                                        </td>
                                        <td className="p-3 tabular-nums">
                                            {row.days_attended}
                                        </td>
                                        <td className="p-3 tabular-nums">
                                            {allUndetermined
                                                ? 'Undetermined'
                                                : row.late_arrivals}
                                            {row.undetermined_days > 0 && (
                                                <p className="text-xs text-muted-foreground">
                                                    {row.undetermined_days}{' '}
                                                    {allUndetermined
                                                        ? row.undetermined_days ===
                                                          1
                                                            ? 'day'
                                                            : 'days'
                                                        : 'undetermined'}
                                                </p>
                                            )}
                                        </td>
                                        <td className="p-3">
                                            <Button
                                                asChild
                                                variant="outline"
                                                size="sm"
                                                className="w-28 aria-expanded:border-primary aria-expanded:bg-accent aria-expanded:text-accent-foreground"
                                            >
                                                <Link
                                                    href={reportUrl}
                                                    data={{
                                                        ...filters,
                                                        page: summary.current_page,
                                                        ...(expanded
                                                            ? {}
                                                            : {
                                                                  employee:
                                                                      row.id,
                                                              }),
                                                    }}
                                                    only={['detail']}
                                                    preserveScroll
                                                    aria-expanded={expanded}
                                                    aria-label={`${expanded ? 'Hide' : 'View'} daily records for ${row.name}`}
                                                >
                                                    {expanded
                                                        ? 'Hide records'
                                                        : 'View records'}
                                                </Link>
                                            </Button>
                                        </td>
                                    </tr>
                                    {expanded && (
                                        <tr>
                                            <td colSpan={5}>
                                                <DailyRecords
                                                    key={`${month}-${row.id}`}
                                                    detail={detail}
                                                    month={month}
                                                />
                                            </td>
                                        </tr>
                                    )}
                                </Fragment>
                            );
                        })}
                        {!summary.data.length && (
                            <tr>
                                <td
                                    colSpan={5}
                                    className="p-4 text-center text-muted-foreground"
                                >
                                    No employees found.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
            {(summary.prev_page_url || summary.next_page_url) && (
                <nav
                    aria-label="Report pagination"
                    className="flex gap-3 text-sm"
                >
                    {[
                        { label: 'Previous', href: summary.prev_page_url },
                        { label: 'Next', href: summary.next_page_url },
                    ].map(({ label, href }) =>
                        href ? (
                            <Button
                                key={label}
                                asChild
                                size="sm"
                                variant="outline"
                                className="min-w-20"
                            >
                                <Link href={href}>{label}</Link>
                            </Button>
                        ) : (
                            <Button
                                key={label}
                                type="button"
                                size="sm"
                                variant="outline"
                                className="min-w-20"
                                disabled
                            >
                                {label}
                            </Button>
                        ),
                    )}
                </nav>
            )}
        </main>
    );
}
