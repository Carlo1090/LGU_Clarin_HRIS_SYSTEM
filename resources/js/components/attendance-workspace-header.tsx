import WorkspaceHeader from '@/components/workspace-header';
import { Link, usePage } from '@inertiajs/react';
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
    {
        id: 'corrections',
        label: 'Corrections',
        href: '/hris/attendance/corrections',
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
    const { canViewAttendanceCorrections } = usePage().props;
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
            <WorkspaceHeader
                title="Attendance records"
                description="Review and maintain employee time and attendance records."
            />
            <nav aria-label="Attendance views" className="flex flex-wrap gap-2">
                {viewLink(views[0])}
                {viewLink(views[1])}
                <DailyTimeRecordDialog url="/hris/attendance/dtr/demo" demo />
                {canManage && views.slice(2, 4).map(viewLink)}
                {canViewAttendanceCorrections && viewLink(views[4])}
            </nav>
        </>
    );
}
