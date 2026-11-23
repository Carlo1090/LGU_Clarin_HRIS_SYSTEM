import { Link } from '@inertiajs/react';
import DailyTimeRecordDialog from '@/components/daily-time-record-dialog';
import { Button } from '@/components/ui/button';

const views = [
    { id: 'records', label: 'Employees', href: '/hris/attendance' },
    {
        id: 'daily',
        label: 'Daily Punches',
        href: '/hris/attendance/daily-punches',
    },
    {
        id: 'import',
        label: 'Import Attendance Logs',
        href: '/hris/attendance/logs',
    },
    {
        id: 'report',
        label: 'Monthly Attendance Report',
        href: '/hris/attendance/monthly-report',
    },
] as const;

export default function AttendanceWorkspaceHeader({
    active,
    month,
    canManage,
}: {
    active: (typeof views)[number]['id'];
    month?: string | null;
    canManage: boolean;
}) {
    function viewLink(view: (typeof views)[number]) {
        const current = active === view.id;
        return (
            <Button
                key={view.id}
                asChild
                variant={current ? 'default' : 'outline'}
                className={current ? 'border border-transparent' : undefined}
            >
                <Link
                    href={view.href}
                    data={month ? { month } : undefined}
                    aria-current={current ? 'page' : undefined}
                >
                    {view.label}
                </Link>
            </Button>
        );
    }

    return (
        <>
            <header className="relative isolate overflow-hidden rounded-3xl bg-[#142d1c] px-6 py-8 text-white sm:px-9">
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute -top-24 -right-10 -z-10 size-72 rounded-full border border-[#a6eb20]/30"
                />
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute -top-14 right-0 -z-10 size-52 rounded-full bg-[#a6eb20]/10"
                />
                <h1 className="text-3xl font-semibold tracking-tight sm:text-4xl">
                    Attendance records
                </h1>
                <p className="mt-2 max-w-xl text-sm leading-6 text-white/80">
                    Review and maintain employee time and attendance records.
                </p>
            </header>
            <nav aria-label="Attendance views" className="flex flex-wrap gap-2">
                {viewLink(views[0])}
                {viewLink(views[1])}
                <DailyTimeRecordDialog url="/hris/attendance/dtr/demo" demo />
                {canManage && views.slice(2).map(viewLink)}
            </nav>
        </>
    );
}
