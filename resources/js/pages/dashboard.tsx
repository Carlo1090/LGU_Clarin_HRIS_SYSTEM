import GradientASCII from '@/components/GradientASCII';
import TechText from '@/components/TechText';
import AOS from 'aos';
import 'aos/dist/aos.css';
import SpotlightCard from '@/components/SpotlightCard';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    ArrowDownToLine,
    ArrowUpRight,
    Building2,
    CalendarCheck,
    CalendarDays,
    CheckCircle2,
    ChevronLeft,
    ChevronRight,
    ClipboardCheck,
    ClipboardList,
    Columns3,
    FileText,
    LayoutDashboard,
    Search,
    ShieldCheck,
    Users,
    Wallet,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useEffect, useState } from 'react';
import type { ReactNode } from 'react';
import {
    Area,
    AreaChart,
    Bar,
    BarChart,
    CartesianGrid,
    Label,
    Pie,
    PieChart,
    XAxis,
    YAxis,
} from 'recharts';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
} from '@/components/ui/chart';
import type { ChartConfig } from '@/components/ui/chart';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { cn } from '@/lib/utils';
import type { DashboardAnalytics, DashboardMetric } from '@/types/dashboard';

type Props = {
    role: string;
    office: string | null;
    cards: DashboardMetric[];
    analytics: DashboardAnalytics;
};
const number = new Intl.NumberFormat('en-PH', { maximumFractionDigits: 2 });
const compact = new Intl.NumberFormat('en-PH', {
    notation: 'compact',
    maximumFractionDigits: 1,
});
const formatDate = (date: string | null) =>
    date
        ? new Intl.DateTimeFormat('en-PH', {
              month: 'short',
              day: 'numeric',
              year: 'numeric',
              timeZone: 'Asia/Manila',
          }).format(new Date(`${date.slice(0, 10)}T00:00:00+08:00`))
        : '-';
const statusLabel = (status: string) =>
    status
        .replace(/[_-]/g, ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());

function statusStyle(status: string) {
    if (
        [
            'active',
            'approved',
            'paid',
            'present',
            'success',
            'completed',
            'processed',
        ].includes(status)
    )
        return 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-300';
    if (
        ['failed', 'error', 'rejected', 'absent', 'terminated'].includes(status)
    )
        return 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-800 dark:bg-rose-950 dark:text-rose-300';
    if (
        [
            'pending',
            'department_reviewed',
            'hr_validated',
            'draft',
            'late',
            'incomplete',
        ].includes(status)
    )
        return 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-300';
    return 'border-border bg-muted text-muted-foreground';
}

function downloadReport(
    cards: DashboardMetric[],
    analytics: DashboardAnalytics,
) {
    const rows: (string | number)[][] = [
        ['LGU Clarin HRIS dashboard'],
        ['Scope', analytics.scope],
        ['Reporting period', analytics.period.label],
        [],
        ['Current metric', 'Value'],
        ...cards.map((card) => [card.label, card.value]),
        [],
        [analytics.distribution.title, 'Count'],
        ...analytics.distribution.data.map((item) => [item.label, item.value]),
        [],
        [
            analytics.bars.title,
            ...analytics.bars.series.map((item) => item.label),
        ],
        ...analytics.bars.data.map((item) => [
            item.period,
            ...analytics.bars.series.map((series) => item[series.key] ?? 0),
        ]),
        [],
        [analytics.activity.title, 'Count'],
        ...analytics.activity.data.map((item) => [
            item.period,
            item.value ?? 0,
        ]),
        [],
        [analytics.records.title, analytics.records.description],
        [
            'Reference',
            'Name / record',
            'Type',
            'Date',
            'Status',
            analytics.records.valueLabel,
        ],
        ...analytics.records.data.map((row) => [
            row.reference,
            row.subject,
            row.description,
            row.date ?? '',
            statusLabel(row.status),
            row.value,
        ]),
    ];
    const csv = rows
        .map((row) =>
            row
                .map((value) => {
                    let cell = String(value);
                    if (typeof value === 'string' && /^[=+\-@\t\r]/.test(cell))
                        cell = `'${cell}`;
                    return `"${cell.replace(/"/g, '""')}"`;
                })
                .join(','),
        )
        .join('\r\n');
    const url = URL.createObjectURL(
        new Blob(['\uFEFF', csv], { type: 'text/csv;charset=utf-8;' }),
    );
    const link = document.createElement('a');
    link.href = url;
    link.download = `clarin-dashboard-${analytics.period.start}-${analytics.period.end}.csv`;
    link.click();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
}

function AnalyticsCard({
    title,
    description,
    children,
    accessory,
    className,
}: {
    title: string;
    description: string;
    children: ReactNode;
    accessory?: ReactNode;
    className?: string;
}) {
    return (
        <Card
            data-aos="fade-up"
            className={cn(
                'dashboard-panel min-w-0 gap-4 rounded-lg py-4 shadow-none',
                className,
            )}
        >
            <SpotlightCard
                className="dashboard-spotlight"
                style={{}}
                theme="light"
                spotlightColor="#A6EB20"
                intensity={0.45}
                proximity={0}
            >
                <CardHeader className="flex-row items-start justify-between gap-3 px-4">
                    <div className="min-w-0 space-y-1.5">
                        <CardTitle className="text-[13px] leading-5">
                            <h2>{title}</h2>
                        </CardTitle>
                        <CardDescription className="text-[11px] leading-4">
                            {description}
                        </CardDescription>
                    </div>
                    {accessory}
                </CardHeader>
                <CardContent className="min-w-0 px-4">{children}</CardContent>
            </SpotlightCard>
        </Card>
    );
}

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

const chartColors = [
    'var(--dashboard-chart-1)',
    'var(--dashboard-chart-2)',
    'var(--dashboard-chart-3)',
    'var(--dashboard-chart-4)',
    'var(--dashboard-chart-5)',
    'var(--dashboard-chart-6)',
];

export default function Dashboard({ role, office, cards, analytics }: Props) {
    const { auth } = usePage().props;

    useEffect(() => {
        AOS.init({
            duration: 700,
            easing: 'ease-out-cubic',
            once: true,
            offset: 50,
            disable: window.matchMedia('(prefers-reduced-motion: reduce)')
                .matches,
        });
        const frame = requestAnimationFrame(() => AOS.refreshHard());
        const refresh = () => AOS.refresh();
        window.addEventListener('load', refresh);
        return () => {
            cancelAnimationFrame(frame);
            window.removeEventListener('load', refresh);
        };
    }, [role, cards.length]);
    const [query, setQuery] = useState('');
    const [status, setStatus] = useState('all');
    const [page, setPage] = useState(1);
    const [loading, setLoading] = useState(false);
    const [columns, setColumns] = useState({
        status: true,
        date: true,
        value: true,
    });
    const isHrmo = ['HRMO Head', 'HR Officer'].includes(role);
    const title = isHrmo
        ? 'HRMO Dashboard'
        : role === 'Unassigned'
          ? 'HRIS Dashboard'
          : `${role} Dashboard`;
    const firstName = auth.user.name.trim().split(/\s+/)[0];
    const distributionTotal = analytics.distribution.data.reduce(
        (sum, item) => sum + item.value,
        0,
    );
    const activityTotal = analytics.activity.data.reduce(
        (sum, item) => sum + Number(item.value ?? 0),
        0,
    );
    const distributionConfig = {
        value: { label: analytics.distribution.unit },
    } satisfies ChartConfig;
    const barsConfig: ChartConfig = Object.fromEntries(
        analytics.bars.series.map((series, index) => [
            series.key,
            {
                label: series.label,
                color: chartColors[index % chartColors.length],
            },
        ]),
    );
    const activityConfig = {
        value: { label: 'Records', color: 'var(--dashboard-chart-1)' },
    } satisfies ChartConfig;
    const filteredRows = analytics.records.data.filter(
        (row) =>
            (status === 'all' || row.status === status) &&
            `${row.reference} ${row.subject} ${row.description} ${statusLabel(row.status)}`
                .toLowerCase()
                .includes(query.trim().toLowerCase()),
    );
    const pages = Math.max(1, Math.ceil(filteredRows.length / 5));
    const currentPage = Math.min(page, pages);
    const visibleRows = filteredRows.slice(
        (currentPage - 1) * 5,
        currentPage * 5,
    );
    const statuses = [
        ...new Set(analytics.records.data.map((row) => row.status)),
    ].sort();
    const hasBars = analytics.bars.data.some((row) =>
        analytics.bars.series.some(
            (series) => Number(row[series.key] ?? 0) > 0,
        ),
    );
    const tasksTotal = analytics.tasks.reduce(
        (sum, task) => sum + task.count,
        0,
    );

    const changePeriod = (months: string) => {
        setPage(1);
        setStatus('all');
        setQuery('');
        router.get(
            '/dashboard',
            { months },
            {
                preserveScroll: true,
                preserveState: true,
                onStart: () => setLoading(true),
                onFinish: () => setLoading(false),
            },
        );
    };

    return (
        <>
            <Head title={title} />
            <div
                className="dashboard-page mx-auto w-full max-w-[1600px] space-y-4 p-3 sm:p-5 lg:p-6"
                aria-busy={loading}
            >
                <header className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div className="flex flex-wrap items-center gap-2">
                            <h1 className="text-xl font-semibold tracking-tight">
                                {title}
                            </h1>
                            <Badge
                                variant="outline"
                                className="rounded-md text-[10px] font-normal text-muted-foreground"
                            >
                                {role}
                            </Badge>
                        </div>
                        <p className="mt-1 text-[11px] text-muted-foreground">
                            Welcome back, {firstName}.{' '}
                            {office ?? 'Here is your workspace at a glance.'}
                        </p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <Select
                            value={String(analytics.period.months)}
                            onValueChange={changePeriod}
                            disabled={loading}
                        >
                            <SelectTrigger
                                className="h-8 w-36 rounded-md bg-card text-[11px]"
                                aria-label="Reporting period"
                            >
                                <CalendarDays
                                    className="size-3.5 text-muted-foreground"
                                    aria-hidden="true"
                                />
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent className="public-surface dashboard-popover">
                                <SelectItem value="3">Last 3 months</SelectItem>
                                <SelectItem value="6">Last 6 months</SelectItem>
                                <SelectItem value="12">
                                    Last 12 months
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <Button
                            type="button"
                            size="sm"
                            className="dashboard-action h-8 rounded-md text-[11px]"
                            onClick={() => downloadReport(cards, analytics)}
                        >
                            <ArrowDownToLine
                                className="size-3.5"
                                aria-hidden="true"
                            />{' '}
                            Export report
                        </Button>
                    </div>
                </header>

                {cards.length ? (
                    <>
                        <nav
                            aria-label="Dashboard sections"
                            className="flex w-fit max-w-full flex-wrap gap-1 rounded-md border border-border bg-muted/50 p-1"
                        >
                            <Button
                                asChild
                                variant="secondary"
                                size="sm"
                                className="h-6 rounded-sm px-2.5 text-[11px] shadow-sm"
                            >
                                <a href="#dashboard-overview">Overview</a>
                            </Button>
                            <Button
                                asChild
                                variant="ghost"
                                size="sm"
                                className="h-6 rounded-sm px-2.5 text-[11px] text-muted-foreground"
                            >
                                <a href="#dashboard-queue">Work queue</a>
                            </Button>
                            <Button
                                asChild
                                variant="ghost"
                                size="sm"
                                className="h-6 rounded-sm px-2.5 text-[11px] text-muted-foreground"
                            >
                                <a href="#dashboard-records">Records</a>
                            </Button>
                        </nav>

                        <section
                            className="dashboard-metrics-banner"
                            data-aos="fade-up"
                            aria-label="Key information"
                        >
                            <div className="dashboard-banner-heading">
                                <div>
                                    <h2>Your workspace at a glance</h2>
                                    <p>
                                        {analytics.scope} &middot; Current key
                                        information
                                    </p>
                                </div>
                                <span>{role}</span>
                                <div
                                    className="dashboard-hero-art"
                                    aria-hidden="true"
                                >
                                    <div className="dashboard-hero-accent" />
                                    <img
                                        src="/images/clarin-officials.png"
                                        alt=""
                                    />
                                </div>
                            </div>
                            <div
                                className={cn(
                                    'grid gap-3 sm:grid-cols-2',
                                    cards.length > 4
                                        ? 'xl:grid-cols-5'
                                        : 'xl:grid-cols-4',
                                )}
                            >
                                {cards.map((card, index) => {
                                    const Icon =
                                        cardIcons[card.label] ??
                                        LayoutDashboard;
                                    return (
                                        <Card
                                            key={card.label}
                                            data-aos="fade-up"
                                            data-aos-delay={Math.min(
                                                index * 50,
                                                200,
                                            )}
                                            className="dashboard-metric gap-0 rounded-lg py-0 shadow-none"
                                        >
                                            <SpotlightCard
                                                className="dashboard-spotlight dashboard-metric-light"
                                                style={{}}
                                                theme="light"
                                                spotlightColor="#A6EB20"
                                                intensity={0.5}
                                                proximity={0}
                                            >
                                                <CardContent className="p-4">
                                                    <div className="flex items-start justify-between gap-3">
                                                        <h2 className="text-[11px] font-medium">
                                                            {card.label}
                                                        </h2>
                                                        <Icon
                                                            className="size-4 shrink-0 text-muted-foreground"
                                                            aria-hidden="true"
                                                        />
                                                    </div>
                                                    <p className="mt-1 flex items-center gap-1.5 text-[10px] text-muted-foreground">
                                                        <span
                                                            className="size-1 rounded-full bg-primary"
                                                            aria-hidden="true"
                                                        />
                                                        Current{' '}
                                                        {card.value.startsWith(
                                                            'PHP',
                                                        )
                                                            ? 'recorded amount'
                                                            : 'total'}
                                                    </p>
                                                    <p className="mt-5 text-[27px] leading-none font-semibold tracking-tight tabular-nums">
                                                        {card.value}
                                                    </p>
                                                </CardContent>
                                            </SpotlightCard>
                                        </Card>
                                    );
                                })}
                            </div>
                        </section>

                        <section
                            id="dashboard-overview"
                            aria-label="Dashboard charts"
                            className="grid scroll-mt-20 gap-3 md:grid-cols-2 xl:grid-cols-12"
                        >
                            <AnalyticsCard
                                className="md:col-span-2 xl:col-span-8"
                                title={analytics.activity.title}
                                description={analytics.activity.description}
                                accessory={
                                    <div
                                        className="flex shrink-0 flex-wrap gap-0.5 rounded-md border border-border p-0.5"
                                        role="group"
                                        aria-label="Chart reporting period"
                                    >
                                        {[3, 6, 12].map((months) => (
                                            <Button
                                                key={months}
                                                size="sm"
                                                variant="ghost"
                                                disabled={loading}
                                                aria-pressed={
                                                    analytics.period.months ===
                                                    months
                                                }
                                                className={cn(
                                                    'h-6 rounded-sm px-2 text-[10px]',
                                                    analytics.period.months ===
                                                        months &&
                                                        'bg-secondary text-foreground shadow-sm',
                                                )}
                                                onClick={() =>
                                                    changePeriod(String(months))
                                                }
                                            >
                                                {months}m
                                            </Button>
                                        ))}
                                    </div>
                                }
                            >
                                <div className="relative">
                                    <ChartContainer
                                        config={activityConfig}
                                        className="aspect-auto h-[230px] min-h-[230px] w-full"
                                    >
                                        <AreaChart
                                            accessibilityLayer
                                            data={analytics.activity.data}
                                            margin={{
                                                top: 12,
                                                right: 8,
                                                left: -10,
                                                bottom: 0,
                                            }}
                                        >
                                            <defs>
                                                <linearGradient
                                                    id="dashboard-activity-fill"
                                                    x1="0"
                                                    y1="0"
                                                    x2="0"
                                                    y2="1"
                                                >
                                                    <stop
                                                        offset="0%"
                                                        stopColor="var(--dashboard-chart-1)"
                                                        stopOpacity={0.38}
                                                    />
                                                    <stop
                                                        offset="100%"
                                                        stopColor="var(--dashboard-chart-1)"
                                                        stopOpacity={0.015}
                                                    />
                                                </linearGradient>
                                            </defs>
                                            <CartesianGrid
                                                vertical={false}
                                                strokeDasharray="3 4"
                                            />
                                            <XAxis
                                                dataKey="month"
                                                tickLine={false}
                                                axisLine={false}
                                                tickMargin={12}
                                                tick={{ fontSize: 10 }}
                                                minTickGap={12}
                                            />
                                            <YAxis
                                                tickLine={false}
                                                axisLine={false}
                                                allowDecimals={false}
                                                width={42}
                                                tick={{ fontSize: 10 }}
                                            />
                                            <ChartTooltip
                                                content={
                                                    <ChartTooltipContent indicator="line" />
                                                }
                                            />
                                            <Area
                                                type="monotone"
                                                dataKey="value"
                                                stroke="var(--color-value)"
                                                fill="url(#dashboard-activity-fill)"
                                                strokeWidth={1.5}
                                                dot={false}
                                                activeDot={{ r: 4 }}
                                                isAnimationActive={false}
                                            />
                                        </AreaChart>
                                    </ChartContainer>
                                    {activityTotal === 0 && (
                                        <p className="pointer-events-none absolute inset-x-0 top-1/2 text-center text-[11px] text-muted-foreground">
                                            No activity in this period.
                                        </p>
                                    )}
                                </div>
                                <div className="mt-3 flex flex-wrap justify-between gap-2 border-t border-border pt-3 text-[10px] text-muted-foreground">
                                    <span>{analytics.period.label}</span>
                                    <span>
                                        <span className="font-medium text-foreground">
                                            {number.format(activityTotal)}
                                        </span>{' '}
                                        records in this period
                                    </span>
                                </div>
                            </AnalyticsCard>

                            <AnalyticsCard
                                className="dashboard-snapshot md:col-span-2 xl:col-span-4"
                                title="Workspace snapshot"
                                description={analytics.scope}
                            >
                                <div className="dashboard-snapshot-intro">
                                    <div
                                        className="dashboard-snapshot-art"
                                        aria-hidden="true"
                                    >
                                        <img
                                            src="/images/black_hero.png"
                                            alt=""
                                            loading="lazy"
                                            decoding="async"
                                        />
                                    </div>
                                    <p className="text-[32px] leading-none font-semibold tracking-tight tabular-nums">
                                        {number.format(distributionTotal)}
                                    </p>
                                    <p className="mt-1.5 text-[11px] text-muted-foreground">
                                        {analytics.distribution.unit} &middot;{' '}
                                        {analytics.distribution.title.toLowerCase()}
                                    </p>
                                    <div className="mt-5 flex items-center gap-2.5">
                                        <Avatar className="size-8 border border-border">
                                            <AvatarImage
                                                src={auth.user.avatar}
                                                alt=""
                                            />
                                            <AvatarFallback className="bg-accent text-[10px] font-semibold text-accent-foreground">
                                                {auth.user.name
                                                    .split(/\s+/)
                                                    .filter(Boolean)
                                                    .slice(0, 2)
                                                    .map((word) => word[0])
                                                    .join('')}
                                            </AvatarFallback>
                                        </Avatar>
                                        <div className="min-w-0">
                                            <p className="truncate text-[11px] font-medium">
                                                {auth.user.name}
                                            </p>
                                            <p className="truncate text-[10px] text-muted-foreground">
                                                {role}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <h3 className="mt-5 text-[10px] font-semibold">
                                    Highlights
                                </h3>
                                <dl className="mt-1 divide-y divide-border text-[11px]">
                                    <div className="flex justify-between gap-3 py-2.5">
                                        <dt className="text-muted-foreground">
                                            Activity this period
                                        </dt>
                                        <dd className="font-medium tabular-nums">
                                            {number.format(activityTotal)}
                                        </dd>
                                    </div>
                                    <div className="flex justify-between gap-3 py-2.5">
                                        <dt className="text-muted-foreground">
                                            Work queue records
                                        </dt>
                                        <dd className="font-medium tabular-nums">
                                            {number.format(tasksTotal)}
                                        </dd>
                                    </div>
                                    <div className="flex justify-between gap-3 py-2.5">
                                        <dt className="text-muted-foreground">
                                            Recent records shown
                                        </dt>
                                        <dd className="font-medium tabular-nums">
                                            {analytics.records.data.length}
                                        </dd>
                                    </div>
                                </dl>
                            </AnalyticsCard>

                            <AnalyticsCard
                                className="dashboard-queue-card md:col-span-2 xl:col-span-6"
                                title="Your work queue"
                                description="Open a workspace to review your next actions."
                                accessory={
                                    <Badge
                                        variant="outline"
                                        className="rounded-md text-[10px]"
                                    >
                                        {number.format(tasksTotal)} records
                                    </Badge>
                                }
                            >
                                <div
                                    id="dashboard-queue"
                                    className="scroll-mt-24"
                                >
                                    {analytics.tasks.length ? (
                                        <div className="grid gap-2.5 sm:grid-cols-3">
                                            {analytics.tasks.map((task) => (
                                                <Link
                                                    key={task.title}
                                                    href={task.href}
                                                    className="dashboard-task group flex min-w-0 flex-col rounded-lg border border-border p-3"
                                                >
                                                    <div className="flex items-center justify-between gap-2">
                                                        <span className="flex items-center gap-1.5 text-[10px] text-muted-foreground">
                                                            <span
                                                                className={cn(
                                                                    'size-1.5 rounded-full',
                                                                    task.count
                                                                        ? 'bg-primary'
                                                                        : 'bg-muted-foreground/50',
                                                                )}
                                                                aria-hidden="true"
                                                            />
                                                            {task.count
                                                                ? 'Follow up'
                                                                : 'Up to date'}
                                                        </span>
                                                        <ArrowUpRight
                                                            className="size-3.5 text-muted-foreground transition group-hover:text-primary"
                                                            aria-hidden="true"
                                                        />
                                                    </div>
                                                    <p className="mt-5 text-[26px] leading-none font-semibold tabular-nums">
                                                        {number.format(
                                                            task.count,
                                                        )}
                                                    </p>
                                                    <h3 className="mt-3 text-[11px] leading-4 font-medium">
                                                        {task.title}
                                                    </h3>
                                                    <p className="mt-1 mb-5 text-[10px] leading-relaxed text-muted-foreground">
                                                        {task.description}
                                                    </p>
                                                    <span className="mt-auto w-fit rounded border border-primary/20 bg-accent px-2 py-1 text-[9px] font-medium text-accent-foreground">
                                                        Open workspace
                                                    </span>
                                                </Link>
                                            ))}
                                        </div>
                                    ) : (
                                        <div className="flex min-h-48 flex-col items-center justify-center text-center">
                                            <CheckCircle2
                                                className="mb-3 size-7 text-primary"
                                                aria-hidden="true"
                                            />
                                            <p className="text-sm font-medium">
                                                You're all caught up
                                            </p>
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                No pending actions for this
                                                workspace.
                                            </p>
                                        </div>
                                    )}
                                </div>
                            </AnalyticsCard>

                            <AnalyticsCard
                                className="dashboard-attendance-card xl:col-span-3"
                                title={analytics.bars.title}
                                description={analytics.bars.description}
                            >
                                <div className="relative">
                                    <ChartContainer
                                        config={barsConfig}
                                        className="aspect-auto h-[185px] min-h-[185px] w-full"
                                    >
                                        <BarChart
                                            accessibilityLayer
                                            data={analytics.bars.data}
                                            margin={{
                                                top: 8,
                                                right: 2,
                                                left: -10,
                                                bottom: 0,
                                            }}
                                        >
                                            <CartesianGrid
                                                vertical={false}
                                                strokeDasharray="3 4"
                                            />
                                            <XAxis
                                                dataKey="month"
                                                tickLine={false}
                                                axisLine={false}
                                                tickMargin={10}
                                                tick={{ fontSize: 9 }}
                                                minTickGap={12}
                                            />
                                            <YAxis
                                                tickLine={false}
                                                axisLine={false}
                                                allowDecimals={false}
                                                width={42}
                                                tick={{ fontSize: 9 }}
                                                tickFormatter={(
                                                    value: number,
                                                ) => compact.format(value)}
                                            />
                                            <ChartTooltip
                                                cursor={{
                                                    fill: 'var(--muted)',
                                                    opacity: 0.5,
                                                }}
                                                content={
                                                    <ChartTooltipContent />
                                                }
                                            />
                                            {analytics.bars.series.map(
                                                (series) => (
                                                    <Bar
                                                        key={series.key}
                                                        dataKey={series.key}
                                                        name={series.label}
                                                        fill={
                                                            'var(--color-' +
                                                            series.key +
                                                            ')'
                                                        }
                                                        radius={[2, 2, 0, 0]}
                                                        maxBarSize={14}
                                                        isAnimationActive={
                                                            false
                                                        }
                                                    />
                                                ),
                                            )}
                                        </BarChart>
                                    </ChartContainer>
                                    {!hasBars && (
                                        <p className="pointer-events-none absolute inset-x-0 top-1/2 text-center text-[10px] text-muted-foreground">
                                            No entries in this period.
                                        </p>
                                    )}
                                </div>
                                <div className="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1.5">
                                    {analytics.bars.series.map(
                                        (series, index) => (
                                            <span
                                                key={series.key}
                                                className="inline-flex items-center gap-1.5 text-[9px] text-muted-foreground"
                                            >
                                                <span
                                                    className="size-1.5 rounded-full"
                                                    style={{
                                                        backgroundColor:
                                                            chartColors[
                                                                index %
                                                                    chartColors.length
                                                            ],
                                                    }}
                                                    aria-hidden="true"
                                                />
                                                {series.label}
                                            </span>
                                        ),
                                    )}
                                </div>
                            </AnalyticsCard>

                            <AnalyticsCard
                                className="dashboard-workforce-card xl:col-span-3"
                                title={analytics.distribution.title}
                                description={analytics.distribution.description}
                            >
                                <ChartContainer
                                    config={distributionConfig}
                                    className="mx-auto aspect-auto h-[180px] min-h-[180px] w-full max-w-[240px]"
                                >
                                    <PieChart accessibilityLayer>
                                        {distributionTotal > 0 && (
                                            <ChartTooltip
                                                cursor={false}
                                                content={
                                                    <ChartTooltipContent
                                                        hideLabel
                                                        nameKey="label"
                                                    />
                                                }
                                            />
                                        )}
                                        <Pie
                                            data={
                                                distributionTotal
                                                    ? analytics.distribution.data.map(
                                                          (item, index) => ({
                                                              ...item,
                                                              fill: chartColors[
                                                                  index %
                                                                      chartColors.length
                                                              ],
                                                          }),
                                                      )
                                                    : [
                                                          {
                                                              label: 'No records',
                                                              value: 1,
                                                              fill: 'var(--muted)',
                                                          },
                                                      ]
                                            }
                                            dataKey="value"
                                            nameKey="label"
                                            innerRadius={55}
                                            outerRadius={76}
                                            paddingAngle={
                                                distributionTotal ? 3 : 0
                                            }
                                            strokeWidth={0}
                                            isAnimationActive={false}
                                        >
                                            <Label
                                                content={({ viewBox }) => {
                                                    if (
                                                        !viewBox ||
                                                        !('cx' in viewBox) ||
                                                        !('cy' in viewBox)
                                                    )
                                                        return null;
                                                    return (
                                                        <text
                                                            x={Number(
                                                                viewBox.cx,
                                                            )}
                                                            y={Number(
                                                                viewBox.cy,
                                                            )}
                                                            textAnchor="middle"
                                                            dominantBaseline="middle"
                                                        >
                                                            <tspan
                                                                x={Number(
                                                                    viewBox.cx,
                                                                )}
                                                                dy="-3"
                                                                className="fill-foreground text-[27px] font-semibold"
                                                            >
                                                                {number.format(
                                                                    distributionTotal,
                                                                )}
                                                            </tspan>
                                                            <tspan
                                                                x={Number(
                                                                    viewBox.cx,
                                                                )}
                                                                dy="21"
                                                                className="fill-muted-foreground text-[9px]"
                                                            >
                                                                {
                                                                    analytics
                                                                        .distribution
                                                                        .unit
                                                                }
                                                            </tspan>
                                                        </text>
                                                    );
                                                }}
                                            />
                                        </Pie>
                                    </PieChart>
                                </ChartContainer>
                                {analytics.distribution.data.length ? (
                                    <div className="mt-2 grid grid-cols-2 gap-x-3 gap-y-2">
                                        {analytics.distribution.data.map(
                                            (item, index) => (
                                                <div
                                                    key={item.label}
                                                    className="flex min-w-0 items-center gap-1.5 text-[9px]"
                                                >
                                                    <span
                                                        className="size-1.5 shrink-0 rounded-full"
                                                        style={{
                                                            backgroundColor:
                                                                chartColors[
                                                                    index %
                                                                        chartColors.length
                                                                ],
                                                        }}
                                                        aria-hidden="true"
                                                    />
                                                    <span className="truncate text-muted-foreground">
                                                        {item.label}
                                                    </span>
                                                    <span className="ml-auto font-medium tabular-nums">
                                                        {number.format(
                                                            item.value,
                                                        )}
                                                    </span>
                                                </div>
                                            ),
                                        )}
                                    </div>
                                ) : (
                                    <p className="mt-2 text-center text-[10px] text-muted-foreground">
                                        No records in this workspace yet.
                                    </p>
                                )}
                            </AnalyticsCard>
                        </section>
                        <Card
                            id="dashboard-records"
                            data-aos="fade-up"
                            className="dashboard-panel min-w-0 scroll-mt-20 gap-0 rounded-lg py-0 shadow-none"
                        >
                            <CardHeader className="gap-1 px-4 pt-4 pb-4">
                                <CardTitle className="text-[13px]">
                                    <h2>{analytics.records.title}</h2>
                                </CardTitle>
                                <CardDescription className="text-[11px]">
                                    {analytics.records.description}
                                </CardDescription>
                            </CardHeader>
                            <div className="flex flex-wrap items-center gap-2 px-4 pb-4">
                                <div className="relative min-w-0 flex-1">
                                    <Search
                                        className="pointer-events-none absolute top-1/2 left-2.5 size-3.5 -translate-y-1/2 text-muted-foreground"
                                        aria-hidden="true"
                                    />
                                    <Input
                                        value={query}
                                        onChange={(event) => {
                                            setQuery(event.target.value);
                                            setPage(1);
                                        }}
                                        placeholder="Filter records..."
                                        aria-label="Filter dashboard records"
                                        className="h-8! rounded-md! pl-8 text-xs"
                                    />
                                </div>
                                <Select
                                    value={status}
                                    onValueChange={(value) => {
                                        setStatus(value);
                                        setPage(1);
                                    }}
                                >
                                    <SelectTrigger
                                        className="h-8 w-[130px] rounded-lg text-xs"
                                        aria-label="Filter by status"
                                    >
                                        <SelectValue placeholder="All statuses" />
                                    </SelectTrigger>
                                    <SelectContent className="public-surface dashboard-popover">
                                        <SelectItem value="all">
                                            All statuses
                                        </SelectItem>
                                        {statuses.map((value) => (
                                            <SelectItem
                                                key={value}
                                                value={value}
                                            >
                                                {statusLabel(value)}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="outline"
                                            className="h-8 rounded-lg text-xs"
                                        >
                                            <Columns3
                                                className="size-3.5"
                                                aria-hidden="true"
                                            />
                                            <span className="hidden sm:inline">
                                                Columns
                                            </span>
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent
                                        align="end"
                                        className="public-surface dashboard-popover"
                                    >
                                        <DropdownMenuLabel>
                                            Visible columns
                                        </DropdownMenuLabel>
                                        <DropdownMenuSeparator />
                                        {(
                                            ['status', 'date', 'value'] as const
                                        ).map((key) => (
                                            <DropdownMenuCheckboxItem
                                                key={key}
                                                checked={columns[key]}
                                                onCheckedChange={(checked) =>
                                                    setColumns((current) => ({
                                                        ...current,
                                                        [key]: checked,
                                                    }))
                                                }
                                            >
                                                {key === 'value'
                                                    ? analytics.records
                                                          .valueLabel
                                                    : statusLabel(key)}
                                            </DropdownMenuCheckboxItem>
                                        ))}
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                            <div className="min-w-0 px-4">
                                <Table className="min-w-[560px] text-xs">
                                    <TableHeader>
                                        <TableRow className="hover:bg-transparent">
                                            <TableHead className="h-9 pl-0 text-[10px] font-medium">
                                                Record
                                            </TableHead>
                                            <TableHead className="h-9 text-[10px] font-medium">
                                                Type
                                            </TableHead>
                                            {columns.status && (
                                                <TableHead className="h-9 text-[10px] font-medium">
                                                    Status
                                                </TableHead>
                                            )}
                                            {columns.date && (
                                                <TableHead className="h-9 text-[10px] font-medium">
                                                    Date
                                                </TableHead>
                                            )}
                                            {columns.value && (
                                                <TableHead className="h-9 text-right text-[10px] font-medium">
                                                    {
                                                        analytics.records
                                                            .valueLabel
                                                    }
                                                </TableHead>
                                            )}
                                            <TableHead className="h-9 pr-0">
                                                <span className="sr-only">
                                                    Open record workspace
                                                </span>
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {visibleRows.length ? (
                                            visibleRows.map((row) => (
                                                <TableRow key={row.id}>
                                                    <TableCell className="py-3 pl-0">
                                                        <div className="flex items-center gap-2.5">
                                                            <Avatar className="size-7">
                                                                <AvatarImage
                                                                    src={
                                                                        row.avatar ??
                                                                        undefined
                                                                    }
                                                                    alt=""
                                                                />
                                                                <AvatarFallback className="bg-muted text-[9px] font-semibold">
                                                                    {row.subject
                                                                        .split(
                                                                            /\s+/,
                                                                        )
                                                                        .filter(
                                                                            Boolean,
                                                                        )
                                                                        .slice(
                                                                            0,
                                                                            2,
                                                                        )
                                                                        .map(
                                                                            (
                                                                                word,
                                                                            ) =>
                                                                                word[0],
                                                                        )
                                                                        .join(
                                                                            '',
                                                                        )
                                                                        .toUpperCase()}
                                                                </AvatarFallback>
                                                            </Avatar>
                                                            <div className="min-w-0">
                                                                <p className="max-w-40 truncate text-[11px] font-medium">
                                                                    {
                                                                        row.subject
                                                                    }
                                                                </p>
                                                                <p className="mt-0.5 text-[9px] text-muted-foreground">
                                                                    {
                                                                        row.reference
                                                                    }
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell className="max-w-36 truncate text-[10px] text-muted-foreground">
                                                        {row.description}
                                                    </TableCell>
                                                    {columns.status && (
                                                        <TableCell>
                                                            <Badge
                                                                variant="outline"
                                                                className={cn(
                                                                    'rounded-md px-1.5 py-0.5 text-[9px] font-medium whitespace-nowrap',
                                                                    statusStyle(
                                                                        row.status,
                                                                    ),
                                                                )}
                                                            >
                                                                {statusLabel(
                                                                    row.status,
                                                                )}
                                                            </Badge>
                                                        </TableCell>
                                                    )}
                                                    {columns.date && (
                                                        <TableCell className="text-[10px] whitespace-nowrap text-muted-foreground">
                                                            {formatDate(
                                                                row.date,
                                                            )}
                                                        </TableCell>
                                                    )}
                                                    {columns.value && (
                                                        <TableCell className="text-right text-[11px] tabular-nums">
                                                            {number.format(
                                                                row.value,
                                                            )}
                                                        </TableCell>
                                                    )}
                                                    <TableCell className="pr-0 text-right">
                                                        <Button
                                                            asChild
                                                            variant="ghost"
                                                            size="icon"
                                                            className="size-7 rounded-md"
                                                        >
                                                            <Link
                                                                href={row.href}
                                                                aria-label={`Open ${row.reference} workspace`}
                                                            >
                                                                <ArrowUpRight
                                                                    className="size-3.5"
                                                                    aria-hidden="true"
                                                                />
                                                            </Link>
                                                        </Button>
                                                    </TableCell>
                                                </TableRow>
                                            ))
                                        ) : (
                                            <TableRow>
                                                <TableCell
                                                    colSpan={
                                                        3 +
                                                        Number(columns.status) +
                                                        Number(columns.date) +
                                                        Number(columns.value)
                                                    }
                                                    className="h-36 text-center text-xs text-muted-foreground"
                                                >
                                                    {query || status !== 'all'
                                                        ? 'No records match your filters.'
                                                        : 'No records available for this period.'}
                                                </TableCell>
                                            </TableRow>
                                        )}
                                    </TableBody>
                                </Table>
                            </div>
                            <div className="flex flex-wrap items-center justify-between gap-3 border-t px-4 py-3">
                                <p className="text-[10px] text-muted-foreground">
                                    {filteredRows.length
                                        ? `${(currentPage - 1) * 5 + 1}-${Math.min(currentPage * 5, filteredRows.length)}`
                                        : '0'}{' '}
                                    of {filteredRows.length} records
                                </p>
                                <div className="flex items-center gap-2">
                                    <span className="mr-1 text-[10px] text-muted-foreground">
                                        Page {currentPage} of {pages}
                                    </span>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="icon"
                                        className="size-7 rounded-md"
                                        aria-label="Previous records page"
                                        disabled={currentPage <= 1}
                                        onClick={() => setPage(currentPage - 1)}
                                    >
                                        <ChevronLeft
                                            className="size-3.5"
                                            aria-hidden="true"
                                        />
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="icon"
                                        className="size-7 rounded-md"
                                        aria-label="Next records page"
                                        disabled={currentPage >= pages}
                                        onClick={() => setPage(currentPage + 1)}
                                    >
                                        <ChevronRight
                                            className="size-3.5"
                                            aria-hidden="true"
                                        />
                                    </Button>
                                </div>
                            </div>
                        </Card>
                        <p className="pb-1 text-[10px] text-muted-foreground">
                            {analytics.scope} &middot; Charts reflect the
                            selected reporting period.
                        </p>
                    </>
                ) : (
                    <Card className="dashboard-panel rounded-lg shadow-none">
                        <CardContent className="flex items-start gap-4">
                            <ShieldCheck
                                className="size-6 shrink-0 text-muted-foreground"
                                aria-hidden="true"
                            />
                            <div>
                                <h2 className="font-semibold">
                                    Your workspace is being prepared
                                </h2>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Contact HRMO or the MIS Administrator to
                                    have the correct role assigned to your
                                    account.
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                )}
                <footer
                    className="dashboard-footer pt-4 pb-8"
                    data-aos="fade-up"
                >
                    <div className="relative overflow-hidden rounded-[3rem] border border-border/80 bg-muted px-6 pt-16 pb-12 text-foreground shadow-sm sm:px-10 lg:px-14">
                        {/* Top Info & Navigation Row */}
                        <div className="grid items-start gap-12 border-b border-border pb-16 lg:grid-cols-[1.5fr_1fr_1fr_1.5fr]">
                            {/* Col 1: Bio / Tagline */}
                            <div>
                                <p className="text-base leading-relaxed font-normal text-muted-foreground">
                                    LGU Clarin is dedicated to progressive
                                    public service, administrative transparency,
                                    and empowered workforce management.
                                </p>
                            </div>

                            {/* Col 2: Explore Links */}
                            <div>
                                <p className="mb-4 text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                    Explore
                                </p>
                                <ul className="space-y-3 text-xs font-medium text-muted-foreground">
                                    <li>
                                        <a
                                            href="/#about"
                                            className="transition hover:text-foreground"
                                        >
                                            About LGU
                                        </a>
                                    </li>
                                    <li>
                                        <a
                                            href="/#features"
                                            className="transition hover:text-foreground"
                                        >
                                            Portal Features
                                        </a>
                                    </li>
                                    <li>
                                        <a
                                            href="/#contact"
                                            className="transition hover:text-foreground"
                                        >
                                            Contact Office
                                        </a>
                                    </li>
                                </ul>
                            </div>

                            {/* Col 3: Employee access Pill Badges */}
                            <div>
                                <p className="mb-4 text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                    Employee access
                                </p>
                                <div className="flex flex-col items-start gap-3 text-sm">
                                    <Link
                                        href="/dashboard"
                                        className="text-muted-foreground hover:text-accent-foreground"
                                    >
                                        Dashboard
                                    </Link>
                                    <a
                                        href="/#contact"
                                        className="text-muted-foreground hover:text-accent-foreground"
                                    >
                                        Account support
                                    </a>
                                </div>
                            </div>

                            {/* Col 4: Action Buttons (Call Office / Tools) */}
                            <div className="flex flex-col gap-4">
                                <Link
                                    href="/settings/profile"
                                    className="group flex items-center justify-between rounded-2xl border border-border bg-card p-4 shadow-sm transition hover:border-[#69B518]"
                                >
                                    <div>
                                        <p className="text-sm font-bold text-foreground transition group-hover:text-accent-foreground">
                                            Account settings
                                        </p>
                                        <p className="text-[11px] text-muted-foreground">
                                            Manage your profile
                                        </p>
                                    </div>
                                    <span className="flex size-9 items-center justify-center rounded-full bg-[#69B518] text-white transition-transform group-hover:scale-105">
                                        <ArrowUpRight className="size-4" />
                                    </span>
                                </Link>

                                <a
                                    href="/#features"
                                    className="group flex items-center justify-between rounded-2xl border border-border bg-card p-4 shadow-sm transition hover:border-neutral-400"
                                >
                                    <div>
                                        <p className="text-sm font-bold text-foreground">
                                            Portal Tools &amp; Logs
                                        </p>
                                        <p className="text-[11px] text-muted-foreground">
                                            Employee resources
                                        </p>
                                    </div>
                                    <span className="flex size-9 items-center justify-center rounded-full bg-neutral-900 text-white transition-transform group-hover:scale-105">
                                        <ArrowUpRight className="size-4" />
                                    </span>
                                </a>
                            </div>
                        </div>

                        {/* Animated municipal wordmark */}
                        <div className="relative isolate -mx-6 mt-12 -mb-12 overflow-hidden bg-white sm:-mx-10 lg:-mx-14">
                            <div
                                aria-hidden="true"
                                className="pointer-events-none absolute inset-0"
                            >
                                <GradientASCII
                                    colors={[
                                        '#69B518',
                                        '#A6EB20',
                                        '#D9FF80',
                                        '#FFFFFF',
                                    ]}
                                    shape="pinch"
                                    cellSize={12}
                                    density={0.85}
                                    glyphOpacity={0.45}
                                    gradientOpacity={0.7}
                                    softness={0.8}
                                    glow={0.1}
                                    grain={0.025}
                                    speed={0.45}
                                    lightMode
                                    mouseInteraction={false}
                                    clickEffect="none"
                                    intro={false}
                                    dpr={1.5}
                                    style={{ height: '100%' }}
                                />
                            </div>
                            <h2 className="sr-only">CLARIN</h2>
                            <div className="relative h-[clamp(10rem,24vw,18rem)] text-[#142014]">
                                <div aria-hidden="true" className="h-full">
                                    <TechText
                                        text="CLARIN"
                                        fontWeight={900}
                                        fontSize={180}
                                        letterSpacing={-0.06}
                                        color="#142014"
                                        accentColor="#1C4722"
                                        intro="assemble"
                                        introDuration={1.6}
                                        sweep={false}
                                        draggable={false}
                                        clickKick={false}
                                        labels={false}
                                        style={{ height: '100%' }}
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}
