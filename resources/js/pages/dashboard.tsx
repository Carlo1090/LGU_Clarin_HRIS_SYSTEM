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
import { hrisResourceIcon } from '@/lib/hris-icons';

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

export default function Dashboard({ role, cards, links }: Props) {
    const isHrmo = role === 'HRMO Head' || role === 'HR Officer';
    const title = isHrmo ? 'HRMO Portal' : role === 'Unassigned' ? 'HRIS Dashboard' : `${role} Portal`;
    const primaryLink = isHrmo
        ? links.find((link) => link.href === '/hris/employees')
        : links[0];

    return (
        <>
            <Head title={title} />
            <div className="mx-auto w-full max-w-7xl space-y-8 p-4 md:p-8">
                <header className="relative isolate overflow-hidden rounded-3xl bg-[#142d1c] px-6 py-8 text-white sm:px-9 sm:py-10">
                    <div aria-hidden="true" className="pointer-events-none absolute inset-y-0 right-0 hidden w-[42%] md:block">
                        <div className="absolute top-1/2 right-6 size-72 -translate-y-1/2 rounded-full border border-[#a6eb20]/25" />
                        <div className="absolute top-1/2 right-14 size-56 -translate-y-1/2 rounded-full bg-[#a6eb20]/10" />
                        <img
                            src="/images/clarin-officials.png"
                            alt=""
                            width={1293}
                            height={1216}
                            className="absolute right-4 bottom-0 h-[94%] w-[90%] object-contain object-right-bottom lg:right-10"
                        />
                    </div>
                    <div className="relative md:max-w-[58%]">
                        <p className="text-sm font-medium text-[#c2ec8b]">
                            LGU Clarin{role !== 'Unassigned' && ` · ${role}`}
                        </p>
                        <h1 className="mt-3 text-4xl font-semibold tracking-tight sm:text-5xl">
                            {title}
                        </h1>
                        <p className="mt-3 max-w-md text-sm leading-6 text-white/80 sm:text-base">
                            {isHrmo
                                ? 'Oversee municipal staff records, attendance, leave requests, and vacancies.'
                                : 'View your role’s key information and open the tools available to you.'}
                        </p>
                        {primaryLink && (
                            <Link
                                href={primaryLink.href}
                                className="mt-6 inline-flex items-center gap-2 rounded-full bg-[#a6eb20] px-5 py-2.5 text-sm font-semibold text-[#142014] transition-colors hover:bg-[#c2ec8b] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white"
                            >
                                {isHrmo ? 'Employee directory' : primaryLink.label}
                                <ArrowUpRight aria-hidden="true" className="size-4" />
                            </Link>
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
                                {isHrmo ? 'Municipal workforce overview' : 'Current information for your HRIS workspace'}
                            </p>
                        </div>
                        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            {cards.map((card) => {
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
                                                className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-accent text-accent-foreground"
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
                                    <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-accent text-accent-foreground transition group-hover:bg-primary group-hover:text-primary-foreground">
                                        {(() => {
                                            const Icon = hrisResourceIcon(link.href.replace('/hris/', ''));

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
