import {
    Banknote,
    BriefcaseBusiness,
    Building2,
    CalendarCheck,
    CalendarClock,
    CalendarDays,
    CalendarRange,
    ClipboardCheck,
    FileLock2,
    FileText,
    FolderTree,
    IdCard,
    Megaphone,
    Settings,
    ShieldAlert,
    ShieldCheck,
    UserRoundSearch,
    Users,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

const resourceIcons: Partial<Record<string, LucideIcon>> = {
    roles: ShieldCheck,
    departments: Building2,
    positions: IdCard,
    'plantilla-items': FolderTree,
    employees: Users,
    attendance: CalendarCheck,
    'attendance-corrections': CalendarClock,
    'leave-types': CalendarDays,
    'leave-applications': FileText,
    'payroll-periods': CalendarRange,
    payrolls: Banknote,
    vacancies: BriefcaseBusiness,
    applicants: UserRoundSearch,
    'evaluation-periods': CalendarClock,
    'performance-reviews': ClipboardCheck,
    'privacy-requests': FileLock2,
    'privacy-incidents': ShieldAlert,
    'portal/my-profile': IdCard,
    'portal/my-attendance': CalendarCheck,
    'portal/my-leave-credits': CalendarDays,
    'portal/my-leave-requests': FileText,
    'portal/my-payslips': Banknote,
    'portal/staffing': Users,
    'portal/plantilla-summary': FolderTree,
    announcements: Megaphone,
    'system-settings': Settings,
};

export function hrisResourceIcon(resource: string): LucideIcon {
    return resourceIcons[resource] ?? FileText;
}
