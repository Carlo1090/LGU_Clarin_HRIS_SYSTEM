import type { LucideIcon } from 'lucide-react';

export type WorkspaceMetric = {
    label: string;
    count: number;
    icon: LucideIcon;
};

export function WorkspaceMetrics({ metrics }: { metrics: WorkspaceMetric[] }) {
    return (
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
    );
}

export function WorkspaceOverview({
    title,
    description,
    metrics,
}: {
    title: string;
    description: string;
    metrics: WorkspaceMetric[];
}) {
    return (
        <section className="dashboard-metrics-banner" aria-label={title}>
            <h2 className="text-xl font-semibold tracking-tight">{title}</h2>
            <p className="mt-1 mb-5 text-sm text-muted-foreground">
                {description}
            </p>
            <WorkspaceMetrics metrics={metrics} />
        </section>
    );
}
