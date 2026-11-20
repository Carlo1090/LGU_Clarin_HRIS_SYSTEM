export type DashboardMetric = { label: string; value: string };
export type DashboardSeries = { key: string; label: string; color: string };
export type DashboardPoint = {
    month: string;
    period: string;
    [key: string]: string | number;
};
export type DashboardRecord = {
    id: string;
    reference: string;
    subject: string;
    avatar?: string | null;
    description: string;
    date: string | null;
    status: string;
    value: number;
    unit: string;
    href: string;
};
export type DashboardAnalytics = {
    scope: string;
    period: { months: number; start: string; end: string; label: string };
    distribution: {
        title: string;
        description: string;
        unit: string;
        data: { label: string; value: number; fill: string }[];
    };
    bars: {
        title: string;
        description: string;
        currency: boolean;
        series: DashboardSeries[];
        data: DashboardPoint[];
    };
    activity: { title: string; description: string; data: DashboardPoint[] };
    tasks: {
        title: string;
        description: string;
        count: number;
        href: string;
        tone: string;
    }[];
    records: {
        title: string;
        description: string;
        valueLabel: string;
        data: DashboardRecord[];
    };
};
export type NotificationFeed = {
    unreadCount: number;
    items: {
        id: number;
        title: string;
        message: string;
        type: string;
        isRead: boolean;
        createdAt: string | null;
    }[];
};
