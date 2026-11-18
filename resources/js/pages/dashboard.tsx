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
import { useState } from 'react';
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
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
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
}: {
    title: string;
    description: string;
    children: ReactNode;
    accessory?: ReactNode;
}) {
    return (
        <Card className="min-w-0 gap-4 rounded-xl py-5 shadow-none">
            <CardHeader className="flex-row items-start justify-between gap-3 px-5">
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
            <CardContent className="min-w-0 px-5">{children}</CardContent>
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

export default function Dashboard({ role, office, cards, analytics }: Props) {
    const { auth } = usePage().props;
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
        analytics.bars.series.map((series) => [
            series.key,
            { label: series.label, color: series.color },
        ]),
    );
    const activityConfig = {
        value: { label: 'Records', color: '#69b518' },
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
                className="mx-auto w-full max-w-[1440px] space-y-6 p-4 sm:p-6 lg:p-7"
                aria-busy={loading}
            >
                <header className="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
                    <div>
                        <div className="flex flex-wrap items-center gap-2.5">
                            <h1 className="text-2xl font-semibold tracking-tight">
                                {title}
                            </h1>
                            <Badge
                                variant="outline"
                                className="rounded-md border-border text-[10px] font-medium text-muted-foreground"
                            >
                                {role}
                            </Badge>
                        </div>
                        <p className="mt-1.5 text-xs text-muted-foreground">
                            Welcome back, {firstName}.{' '}
                            {office ??
                                'Here is the latest from your workspace.'}
                        </p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <Select
                            value={String(analytics.period.months)}
                            onValueChange={changePeriod}
                            disabled={loading}
                        >
                            <SelectTrigger
                                className="h-9 w-40 rounded-lg bg-card text-xs"
                                aria-label="Reporting period"
                            >
                                <CalendarDays
                                    className="size-3.5 text-muted-foreground"
                                    aria-hidden="true"
                                />
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent className="public-surface">
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
                            className="h-9 rounded-lg bg-foreground text-xs text-background hover:bg-foreground/90"
                            onClick={() => downloadReport(cards, analytics)}
                        >
                            <ArrowDownToLine
                                className="size-3.5"
                                aria-hidden="true"
                            />
                            Download report
                        </Button>
                    </div>
                </header>

                {cards.length ? (
                    <>
                        <section
                            aria-label="Key information"
                            className={cn(
                                'grid gap-4 sm:grid-cols-2',
                                cards.length > 4
                                    ? 'xl:grid-cols-5'
                                    : 'xl:grid-cols-4',
                            )}
                        >
                            {cards.map((card) => {
                                const Icon =
                                    cardIcons[card.label] ?? LayoutDashboard;
                                return (
                                    <Card
                                        key={card.label}
                                        className="gap-0 rounded-xl py-0 shadow-none"
                                    >
                                        <CardContent className="p-5">
                                            <div className="flex items-start justify-between gap-3">
                                                <h2 className="text-xs leading-5 font-medium text-muted-foreground">
                                                    {card.label}
                                                </h2>
                                                <span className="flex size-8 shrink-0 items-center justify-center rounded-full bg-muted">
                                                    <Icon
                                                        className="size-3.5 text-muted-foreground"
                                                        aria-hidden="true"
                                                    />
                                                </span>
                                            </div>
                                            <p className="mt-2 text-[28px] leading-tight font-semibold tracking-tight tabular-nums">
                                                {card.value}
                                            </p>
                                            <p className="mt-2 flex items-center gap-1.5 text-[10px] text-muted-foreground">
                                                <span
                                                    className="size-1.5 rounded-full bg-[#69b518]"
                                                    aria-hidden="true"
                                                />
                                                Current{' '}
                                                {card.value.startsWith('PHP')
                                                    ? 'recorded amount'
                                                    : 'total'}
                                            </p>
                                        </CardContent>
                                    </Card>
                                );
                            })}
                        </section>
                        <div className="flex flex-wrap items-center justify-between gap-2 text-[11px] text-muted-foreground">
                            <p>{analytics.scope}</p>
                            <p>
                                {analytics.period.label}
                                <span className="hidden sm:inline">
                                    {' '}
                                    &middot; Charts reflect the selected period
                                </span>
                            </p>
                        </div>

                        <section
                            aria-label="Dashboard charts"
                            className="grid gap-5 md:grid-cols-2 xl:grid-cols-3"
                        >
                            <AnalyticsCard
                                title={analytics.distribution.title}
                                description={analytics.distribution.description}
                            >
                                <ChartContainer
                                    config={distributionConfig}
                                    className="mx-auto aspect-auto h-[184px] min-h-[184px] w-full max-w-[260px]"
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
                                                    ? analytics.distribution
                                                          .data
                                                    : [
                                                          {
                                                              label: 'No records',
                                                              value: 1,
                                                              fill: '#e3e9df',
                                                          },
                                                      ]
                                            }
                                            dataKey="value"
                                            nameKey="label"
                                            innerRadius={59}
                                            outerRadius={81}
                                            paddingAngle={
                                                distributionTotal ? 2 : 0
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
                                                                dy="-5"
                                                                className="fill-foreground text-[28px] font-semibold"
                                                            >
                                                                {number.format(
                                                                    distributionTotal,
                                                                )}
                                                            </tspan>
                                                            <tspan
                                                                x={Number(
                                                                    viewBox.cx,
                                                                )}
                                                                dy="22"
                                                                className="fill-muted-foreground text-[10px]"
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
                                    <div className="mt-3 grid grid-cols-2 gap-x-4 gap-y-2.5">
                                        {analytics.distribution.data.map(
                                            (item) => (
                                                <div
                                                    key={item.label}
                                                    className="flex min-w-0 items-center gap-2 text-[10px]"
                                                >
                                                    <span
                                                        className="size-2 shrink-0 rounded-full"
                                                        style={{
                                                            backgroundColor:
                                                                item.fill,
                                                        }}
                                                        aria-hidden="true"
                                                    />
                                                    <span className="truncate text-muted-foreground">
                                                        {item.label}
                                                    </span>
                                                    <span className="ml-auto font-semibold tabular-nums">
                                                        {number.format(
                                                            item.value,
                                                        )}
                                                    </span>
                                                </div>
                                            ),
                                        )}
                                    </div>
                                ) : (
                                    <p className="mt-3 text-center text-xs text-muted-foreground">
                                        No records in this workspace yet.
                                    </p>
                                )}
                            </AnalyticsCard>

                            <AnalyticsCard
                                title={analytics.bars.title}
                                description={analytics.bars.description}
                            >
                                <div className="relative">
                                    <ChartContainer
                                        config={barsConfig}
                                        className="aspect-auto h-[200px] min-h-[200px] w-full"
                                    >
                                        <BarChart
                                            accessibilityLayer
                                            data={analytics.bars.data}
                                            margin={{
                                                top: 8,
                                                right: 0,
                                                left: -12,
                                                bottom: 0,
                                            }}
                                        >
                                            <CartesianGrid
                                                vertical={false}
                                                strokeDasharray="3 3"
                                            />
                                            <XAxis
                                                dataKey="month"
                                                tickLine={false}
                                                axisLine={false}
                                                tickMargin={10}
                                                tick={{ fontSize: 10 }}
                                                minTickGap={0}
                                            />
                                            <YAxis
                                                tickLine={false}
                                                axisLine={false}
                                                allowDecimals={false}
                                                width={40}
                                                tick={{ fontSize: 10 }}
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
                                                        fill={`var(--color-${series.key})`}
                                                        radius={[3, 3, 0, 0]}
                                                        maxBarSize={18}
                                                        isAnimationActive={
                                                            false
                                                        }
                                                    />
                                                ),
                                            )}
                                        </BarChart>
                                    </ChartContainer>
                                    {!hasBars && (
                                        <div className="pointer-events-none absolute inset-x-0 top-1/2 text-center text-[11px] text-muted-foreground">
                                            No entries in this period.
                                        </div>
                                    )}
                                </div>
                                <div className="mt-3 flex flex-wrap items-center justify-center gap-x-3 gap-y-1.5">
                                    {analytics.bars.series.map((series) => (
                                        <span
                                            key={series.key}
                                            className="inline-flex items-center gap-1.5 text-[9px] text-muted-foreground"
                                        >
                                            <span
                                                className="size-1.5 rounded-full"
                                                style={{
                                                    backgroundColor:
                                                        series.color,
                                                }}
                                                aria-hidden="true"
                                            />
                                            {series.label}
                                        </span>
                                    ))}
                                </div>
                            </AnalyticsCard>

                            <AnalyticsCard
                                title={analytics.activity.title}
                                description={analytics.activity.description}
                                accessory={
                                    <span className="rounded-md bg-accent px-2 py-1 text-xs font-semibold text-accent-foreground tabular-nums">
                                        {number.format(activityTotal)}
                                    </span>
                                }
                            >
                                <div className="relative">
                                    <ChartContainer
                                        config={activityConfig}
                                        className="aspect-auto h-[200px] min-h-[200px] w-full"
                                    >
                                        <AreaChart
                                            accessibilityLayer
                                            data={analytics.activity.data}
                                            margin={{
                                                top: 8,
                                                right: 4,
                                                left: -12,
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
                                                        stopColor="#69b518"
                                                        stopOpacity={0.3}
                                                    />
                                                    <stop
                                                        offset="100%"
                                                        stopColor="#69b518"
                                                        stopOpacity={0.02}
                                                    />
                                                </linearGradient>
                                            </defs>
                                            <CartesianGrid
                                                vertical={false}
                                                strokeDasharray="3 3"
                                            />
                                            <XAxis
                                                dataKey="month"
                                                tickLine={false}
                                                axisLine={false}
                                                tickMargin={10}
                                                tick={{ fontSize: 10 }}
                                                minTickGap={0}
                                            />
                                            <YAxis
                                                tickLine={false}
                                                axisLine={false}
                                                allowDecimals={false}
                                                width={38}
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
                                                strokeWidth={2}
                                                dot={false}
                                                activeDot={{ r: 4 }}
                                                isAnimationActive={false}
                                            />
                                        </AreaChart>
                                    </ChartContainer>
                                    {activityTotal === 0 && (
                                        <div className="pointer-events-none absolute inset-x-0 top-1/2 text-center text-[11px] text-muted-foreground">
                                            No activity in this period.
                                        </div>
                                    )}
                                </div>
                                <p className="mt-3 text-center text-[10px] text-muted-foreground">
                                    {analytics.period.label}
                                </p>
                            </AnalyticsCard>
                        </section>

                        <div className="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(0,2.1fr)]">
                            <AnalyticsCard
                                title="Your work queue"
                                description="A few things that may need your attention."
                                accessory={
                                    <Badge
                                        variant="secondary"
                                        className="rounded-md text-[10px]"
                                    >
                                        {number.format(tasksTotal)}
                                    </Badge>
                                }
                            >
                                {analytics.tasks.length ? (
                                    <div className="space-y-3">
                                        {analytics.tasks.map((task) => (
                                            <Link
                                                key={task.title}
                                                href={task.href}
                                                className="group block rounded-lg border border-border/70 p-3 transition hover:border-[#69b518]/50 hover:bg-accent/30"
                                            >
                                                <div className="flex items-start justify-between gap-3">
                                                    <p className="text-xs font-medium group-hover:text-accent-foreground">
                                                        {task.title}
                                                    </p>
                                                    <ArrowUpRight
                                                        className="mt-0.5 size-3.5 shrink-0 text-muted-foreground"
                                                        aria-hidden="true"
                                                    />
                                                </div>
                                                <p className="mt-1 text-[10px] leading-relaxed text-muted-foreground">
                                                    {task.description}
                                                </p>
                                                <div className="mt-2 flex items-center gap-2">
                                                    <Badge
                                                        variant="outline"
                                                        className={cn(
                                                            'rounded px-1.5 py-0 text-[9px]',
                                                            task.count
                                                                ? task.tone ===
                                                                  'amber'
                                                                    ? 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-300'
                                                                    : 'border-border bg-accent text-accent-foreground'
                                                                : 'border-border bg-muted text-muted-foreground',
                                                        )}
                                                    >
                                                        {task.count
                                                            ? 'Follow up'
                                                            : 'Up to date'}
                                                    </Badge>
                                                    <span className="text-[10px] font-medium tabular-nums">
                                                        {number.format(
                                                            task.count,
                                                        )}{' '}
                                                        {task.count === 1
                                                            ? 'record'
                                                            : 'records'}
                                                    </span>
                                                </div>
                                            </Link>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="flex flex-col items-center py-8 text-center">
                                        <CheckCircle2
                                            className="mb-3 size-8 text-[#69b518]"
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
                            </AnalyticsCard>

                            <Card className="min-w-0 gap-0 rounded-xl py-0 shadow-none">
                                <CardHeader className="gap-1 px-5 pt-5 pb-4">
                                    <CardTitle className="text-[13px]">
                                        <h2>{analytics.records.title}</h2>
                                    </CardTitle>
                                    <CardDescription className="text-[11px]">
                                        {analytics.records.description}
                                    </CardDescription>
                                </CardHeader>
                                <div className="flex flex-wrap items-center gap-2 px-5 pb-4">
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
                                            className="h-8 rounded-lg pl-8 text-xs"
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
                                        <SelectContent className="public-surface">
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
                                            className="public-surface"
                                        >
                                            <DropdownMenuLabel>
                                                Visible columns
                                            </DropdownMenuLabel>
                                            <DropdownMenuSeparator />
                                            {(
                                                [
                                                    'status',
                                                    'date',
                                                    'value',
                                                ] as const
                                            ).map((key) => (
                                                <DropdownMenuCheckboxItem
                                                    key={key}
                                                    checked={columns[key]}
                                                    onCheckedChange={(
                                                        checked,
                                                    ) =>
                                                        setColumns(
                                                            (current) => ({
                                                                ...current,
                                                                [key]: checked,
                                                            }),
                                                        )
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
                                <div className="min-w-0 px-5">
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
                                                                    href={
                                                                        row.href
                                                                    }
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
                                                            Number(
                                                                columns.status,
                                                            ) +
                                                            Number(
                                                                columns.date,
                                                            ) +
                                                            Number(
                                                                columns.value,
                                                            )
                                                        }
                                                        className="h-36 text-center text-xs text-muted-foreground"
                                                    >
                                                        {query ||
                                                        status !== 'all'
                                                            ? 'No records match your filters.'
                                                            : 'No records available for this period.'}
                                                    </TableCell>
                                                </TableRow>
                                            )}
                                        </TableBody>
                                    </Table>
                                </div>
                                <div className="flex flex-wrap items-center justify-between gap-3 border-t px-5 py-3">
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
                                            onClick={() =>
                                                setPage(currentPage - 1)
                                            }
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
                                            onClick={() =>
                                                setPage(currentPage + 1)
                                            }
                                        >
                                            <ChevronRight
                                                className="size-3.5"
                                                aria-hidden="true"
                                            />
                                        </Button>
                                    </div>
                                </div>
                            </Card>
                        </div>
                    </>
                ) : (
                    <Card className="rounded-xl shadow-none">
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
            </div>
        </>
    );
}
