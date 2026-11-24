import { Head, Link } from '@inertiajs/react';
import AttendanceWorkspaceHeader from '@/components/attendance-workspace-header';
import { Button } from '@/components/ui/button';
import type { DailyPunchRow } from '@/types/attendance';

type Props = {
    rows: {
        data: DailyPunchRow[];
        current_page: number;
        last_page: number;
        total: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: {
        start_date: string;
        end_date: string;
        employee: number | null;
        department: number | null;
        flags_only: string | null;
    };
    employees: { id: number; name: string }[];
    departments: { id: number; name: string }[];
    canManage: boolean;
    timezone: string;
};

const slots = [
    { key: 'AM_IN', label: 'Morning IN' },
    { key: 'LUNCH_OUT', label: 'Lunch/Morning OUT' },
    { key: 'PM_IN', label: 'Afternoon IN' },
    { key: 'PM_OUT', label: 'Afternoon/Evening OUT' },
] as const;

const exportHref = (filters: Props['filters']) => {
    const query = new URLSearchParams();
    query.set('start_date', filters.start_date);
    query.set('end_date', filters.end_date);
    if (filters.employee) query.set('employee', String(filters.employee));
    if (filters.department) query.set('department', String(filters.department));
    if (filters.flags_only) query.set('flags_only', filters.flags_only);
    return `/hris/attendance/daily-punches/export?${query.toString()}`;
};

export default function DailyPunches({
    rows,
    filters,
    employees,
    departments,
    canManage,
    timezone,
}: Props) {
    return (
        <main className="workspace-page">
            <Head title="Daily Punches" />
            <AttendanceWorkspaceHeader active="daily" canManage={canManage} />

            <section className="space-y-4 rounded-xl border bg-card p-5">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 className="text-xl font-semibold">
                            Daily attendance punches
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            Device times are shown as recorded in {timezone}.
                            Slots are assigned using the effective work schedule.
                        </p>
                    </div>
                    <Button asChild variant="outline">
                        <a href={exportHref(filters)}>Export Excel workbook</a>
                    </Button>
                </div>

                <form
                    method="get"
                    action="/hris/attendance/daily-punches"
                    className="grid grid-cols-[repeat(auto-fit,minmax(min(100%,9rem),1fr))] gap-3"
                >
                    <label className="grid gap-1 text-sm">
                        Start date
                        <input
                            className="h-10 rounded-md border bg-background px-3"
                            type="date"
                            name="start_date"
                            defaultValue={filters.start_date}
                            required
                        />
                    </label>
                    <label className="grid gap-1 text-sm">
                        End date
                        <input
                            className="h-10 rounded-md border bg-background px-3"
                            type="date"
                            name="end_date"
                            defaultValue={filters.end_date}
                            required
                        />
                    </label>
                    <label className="grid gap-1 text-sm">
                        Employee
                        <select
                            className="h-10 rounded-md border bg-background px-3"
                            name="employee"
                            defaultValue={filters.employee ?? ''}
                        >
                            <option value="">All employees</option>
                            {employees.map((employee) => (
                                <option key={employee.id} value={employee.id}>
                                    {employee.name}
                                </option>
                            ))}
                        </select>
                    </label>
                    <label className="grid gap-1 text-sm">
                        Office / department
                        <select
                            className="h-10 rounded-md border bg-background px-3"
                            name="department"
                            defaultValue={filters.department ?? ''}
                        >
                            <option value="">All departments</option>
                            {departments.map((department) => (
                                <option
                                    key={department.id}
                                    value={department.id}
                                >
                                    {department.name}
                                </option>
                            ))}
                        </select>
                    </label>
                    <label className="grid gap-1 text-sm">
                        Flags only
                        <select
                            className="h-10 rounded-md border bg-background px-3"
                            name="flags_only"
                            defaultValue={filters.flags_only ?? ''}
                        >
                            <option value="">All punches</option>
                            <option value="missing">Missing punch</option>
                            <option value="no_schedule">No schedule</option>
                            <option value="duplicate">Duplicate</option>
                            <option value="pin_verify">PIN verification</option>
                            <option value="unlinked">Unlinked ID</option>
                        </select>
                    </label>
                    <div className="flex items-end">
                        <Button type="submit" className="w-full">
                            Apply filters
                        </Button>
                    </div>
                </form>

                <p className="text-sm text-muted-foreground" role="status">
                    {rows.total} employee-days
                </p>
                <div className="overflow-x-auto">
                    <table className="w-full min-w-[1150px] border-collapse text-left text-sm">
                        <thead className="bg-muted">
                            <tr>
                                {[
                                    'Employee',
                                    'Date',
                                    ...slots.map((slot) => slot.label),
                                    'Result',
                                    'Flags and raw punches',
                                ].map((label) => (
                                    <th
                                        key={label}
                                        scope="col"
                                        className="p-3 font-medium"
                                    >
                                        {label}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {rows.data.map((row) => (
                                <tr
                                    key={row.key}
                                    className="border-b align-top"
                                >
                                    <th scope="row" className="p-3 font-medium">
                                        {row.employee_name ?? (
                                            <span className="text-amber-800 dark:text-amber-300">
                                                Unlinked ID
                                            </span>
                                        )}
                                        <div className="text-xs font-normal text-muted-foreground">
                                            {row.employee_number ??
                                                `Machine ID ${row.machine_user_id}`}
                                            {row.department_name
                                                ? ` · ${row.department_name}`
                                                : ''}
                                        </div>
                                    </th>
                                    <td className="p-3 tabular-nums">
                                        {row.date}
                                    </td>
                                    {slots.map(({ key }) => {
                                        const punches = row.slots[key] ?? [];
                                        const missing =
                                            row.flags.missing_slots.includes(
                                                key,
                                            );
                                        return (
                                            <td
                                                key={key}
                                                className={`min-w-32 p-3 tabular-nums ${missing ? 'bg-amber-50 dark:bg-amber-950/30' : ''}`}
                                                aria-label={
                                                    missing
                                                        ? `Missing ${key}`
                                                        : undefined
                                                }
                                            >
                                                {punches[0]?.time ?? (missing ? 'Missing' : '—')}
                                            </td>
                                        );
                                    })}
                                    <td className="min-w-44 space-y-1 p-3 text-xs">
                                        {row.status && (
                                            <p className="font-semibold capitalize">{row.status.replaceAll('_', ' ')}</p>
                                        )}
                                        {row.schedule_id !== null && (
                                            <>
                                                <p>{row.schedule_source === 'department' ? 'Department' : 'Override'} schedule #{row.schedule_id}</p>
                                                <p>Late {row.late_minutes_am} min AM / {row.late_minutes_pm} min PM</p>
                                                <p>Undertime {row.undertime_minutes} min</p>
                                            </>
                                        )}
                                    </td>
                                    <td className="min-w-60 p-3">
                                        <div className="mb-2 flex flex-wrap gap-1">
                                            {row.flag_labels.map((flag) => (
                                                <span
                                                    key={flag}
                                                    className="rounded-full bg-muted px-2 py-1 text-xs"
                                                >
                                                    {flag}
                                                </span>
                                            ))}
                                            {!row.flag_labels.length && '—'}
                                        </div>
                                        <ol className="grid gap-2 text-xs">
                                            {row.punches.map((punch) => (
                                                <li key={punch.id}>
                                                    <span className="font-medium tabular-nums">{punch.punched_at}</span>
                                                    <span className="ml-2">
                                                        {slots.find(({ key }) => key === punch.derived_slot)?.label ?? (punch.is_duplicate ? 'Merged duplicate' : 'Unassigned')}
                                                    </span>
                                                    <span className="block text-muted-foreground">
                                                        Device status {punch.punch_state ?? 'unknown'} · {punch.verify_label} · Device {punch.device_id ?? '—'}
                                                    </span>
                                                </li>
                                            ))}
                                        </ol>
                                    </td>
                                </tr>
                            ))}
                            {!rows.data.length && (
                                <tr>
                                    <td
                                        colSpan={8}
                                        className="p-8 text-center text-muted-foreground"
                                    >
                                        No attendance punches found for this
                                        date range.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
                {rows.last_page > 1 && (
                    <nav
                        aria-label="Daily punch pages"
                        className="flex flex-wrap gap-2"
                    >
                        {rows.links.map((link) =>
                            link.url ? (
                                <Link
                                    key={link.label}
                                    href={link.url}
                                    className={`rounded border px-3 py-2 text-sm ${link.active ? 'bg-primary text-primary-foreground' : ''}`}
                                >
                                    {link.label
                                        .replace(/&laquo;/g, '←')
                                        .replace(/&raquo;/g, '→')}
                                </Link>
                            ) : (
                                <span
                                    key={link.label}
                                    className="rounded border px-3 py-2 text-sm text-muted-foreground"
                                >
                                    {link.label
                                        .replace(/&laquo;/g, '←')
                                        .replace(/&raquo;/g, '→')}
                                </span>
                            ),
                        )}
                    </nav>
                )}
            </section>
        </main>
    );
}
