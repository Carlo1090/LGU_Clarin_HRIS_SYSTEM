import { Head, Link } from '@inertiajs/react';
import {
    ArrowUpRight,
    Building2,
    CalendarCheck,
    CalendarDays,
    ClipboardCheck,
    ClipboardList,
    FileText,
    LayoutDashboard,
    ShieldCheck,
    Users,
    Wallet,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

type DashboardCard = {
    label: string;
    value: string;
};

type DashboardLink = {
    label: string;
    href: string;
};

type Props = {
    role: string;
    office: string | null;
    cards: DashboardCard[];
    links: DashboardLink[];
};

const cardIcons: Record<string, LucideIcon> = {
    'Attendance this month': CalendarCheck,
    'Available leave days': CalendarDays,
    Payslips: Wallet,
    'Pending leave requests': ClipboardList,
    'Department employees': Users,
    'Total employees': Users,
    'Active employees': Users,
    Departments: Building2,
    'Attendance issues': CalendarCheck,
    'Pending leave approvals': ClipboardCheck,
    'Pending HR transactions': ClipboardList,
    'Incomplete 201 files': FileText,
    'Performance reviews': ClipboardCheck,
    'Open vacancies': Building2,
    'Draft payroll periods': CalendarDays,
    'Payrolls in preparation': ClipboardList,
    'Payroll errors': ClipboardCheck,
    'Recorded deductions': Wallet,
    'Approved payrolls': ClipboardCheck,
    'Approved gross total': Wallet,
    'Approved net total': Wallet,
    'All recorded deductions': Wallet,
    'Payrolls awaiting disbursement': Wallet,
    'Net amount awaiting disbursement': Wallet,
    'Payrolls marked paid': ClipboardCheck,
    'User accounts': Users,
    'Inactive accounts': Users,
    'Audit log entries': ClipboardList,
    'Failed logins': ShieldCheck,
    'System settings': LayoutDashboard,
    'Pending privacy requests': ClipboardList,
    'Open privacy incidents': ShieldCheck,
    'Access log entries': ClipboardList,
    'Payroll records': FileText,
    'Payroll gross total': Wallet,
    'Payroll net total': Wallet,
};

function iconForLink(href: string): LucideIcon {
    if (href.includes('employee')) return Users;
    if (href.includes('department') || href.includes('vacanc'))
        return Building2;
    if (href.includes('attendance')) return CalendarCheck;
    if (href.includes('payroll')) return Wallet;
    if (href.includes('leave') || href.includes('privacy'))
        return ClipboardList;
    if (href.includes('performance')) return ClipboardCheck;

    return FileText;
}

export default function Dashboard({ role, office, cards, links }: Props) {
    return (
        <>
            <Head title={`${role} Dashboard`} />
            <div className="mx-auto w-full max-w-7xl space-y-8 p-4 md:p-8">
                <header className="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-950 via-blue-950 to-teal-900 px-6 py-8 text-white shadow-lg shadow-blue-950/10 sm:px-9 sm:py-10">
                    <div className="absolute -top-20 -right-10 size-64 rounded-full border border-white/10" />
                    <div className="absolute -top-12 -right-2 size-48 rounded-full border border-white/10" />
                    <div className="relative max-w-3xl">
                        <div className="mb-5 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-xs font-medium text-blue-50">
                            <LayoutDashboard className="size-3.5" />
                            LGU CLARIN · HUMAN RESOURCE INFORMATION SYSTEM
                        </div>
                        <p className="text-sm font-medium text-teal-200">
                            Your workspace
                        </p>
                        <h1 className="mt-1 text-3xl font-semibold tracking-tight sm:text-4xl">
                            {role} Dashboard
                        </h1>
                        {office && (
                            <p className="mt-3 text-sm text-blue-100">
                                {office}
                            </p>
                        )}
                    </div>
                </header>

                {cards.length > 0 ? (
                    <section aria-label="Key information" className="space-y-4">
                        <div>
                            <h2 className="text-lg font-semibold tracking-tight">
                                At a glance
                            </h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Current information for your HRIS workspace
                            </p>
                        </div>
                        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            {cards.map((card, index) => {
                                const Icon =
                                    cardIcons[card.label] ?? LayoutDashboard;

                                return (
                                    <article
                                        key={card.label}
                                        className="group rounded-2xl border bg-card p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md"
                                    >
                                        <div className="flex items-start justify-between gap-3">
                                            <h3 className="text-sm leading-5 font-medium text-muted-foreground">
                                                {card.label}
                                            </h3>
                                            <span
                                                className={`flex size-10 shrink-0 items-center justify-center rounded-xl ${
                                                    index % 3 === 0
                                                        ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300'
                                                        : index % 3 === 1
                                                          ? 'bg-teal-50 text-teal-700 dark:bg-teal-950 dark:text-teal-300'
                                                          : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'
                                                }`}
                                            >
                                                <Icon
                                                    aria-hidden="true"
                                                    className="size-5"
                                                />
                                            </span>
                                        </div>
                                        <p className="mt-5 text-3xl font-semibold tracking-tight tabular-nums">
                                            {card.value}
                                        </p>
                                    </article>
                                );
                            })}
                        </div>
                    </section>
                ) : (
                    <section className="rounded-2xl border bg-card p-6">
                        <div className="flex gap-4">
                            <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300">
                                <ShieldCheck
                                    aria-hidden="true"
                                    className="size-5"
                                />
                            </span>
                            <div>
                                <h2 className="font-semibold">
                                    No role is assigned to this account
                                </h2>
                                <p className="mt-1 text-sm leading-6 text-muted-foreground">
                                    Contact the MIS Administrator or HRMO to
                                    have your account assigned the correct role.
                                </p>
                            </div>
                        </div>
                    </section>
                )}

                {links.length > 0 && (
                    <section className="space-y-4">
                        <div>
                            <h2 className="text-lg font-semibold tracking-tight">
                                Quick access
                            </h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Go directly to the tools available for your role
                            </p>
                        </div>
                        <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                            {links.map((link) => (
                                <Link
                                    key={link.href}
                                    href={link.href}
                                    className="group flex items-center gap-4 rounded-2xl border bg-card p-4 shadow-sm transition duration-200 hover:border-primary/30 hover:shadow-md"
                                >
                                    <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-primary/5 text-primary transition group-hover:bg-primary group-hover:text-primary-foreground">
                                        {(() => {
                                            const Icon = iconForLink(link.href);

                                            return (
                                                <Icon
                                                    aria-hidden="true"
                                                    className="size-5"
                                                />
                                            );
                                        })()}
                                    </span>
                                    <span className="min-w-0 flex-1">
                                        <span className="block font-medium">
                                            {link.label}
                                        </span>
                                        <span className="mt-0.5 block text-xs text-muted-foreground">
                                            Open {link.label.toLowerCase()}
                                        </span>
                                    </span>
                                    <ArrowUpRight
                                        aria-hidden="true"
                                        className="size-4 shrink-0 text-muted-foreground transition group-hover:translate-x-0.5 group-hover:-translate-y-0.5 group-hover:text-foreground"
                                    />
                                </Link>
                            ))}
                        </div>
                    </section>
                )}
            </div>
        </>
    );
}
