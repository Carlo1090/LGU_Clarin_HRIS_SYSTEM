import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import { Clock3, Megaphone } from 'lucide-react';
import { useEffect, useState } from 'react';

export type DashboardAnnouncement = {
    id: number;
    title: string;
    content: string;
    visible_at: string | null;
};

const manilaDateTimeOptions = {
    timeZone: 'Asia/Manila',
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
} satisfies Intl.DateTimeFormatOptions;

const manilaDateTime = new Intl.DateTimeFormat('en-PH', manilaDateTimeOptions);

const manilaClock = new Intl.DateTimeFormat('en-PH', {
    ...manilaDateTimeOptions,
    second: '2-digit',
});

function ManilaClock() {
    const [now, setNow] = useState<Date | null>(null);

    useEffect(() => {
        const tick = () => setNow(new Date());
        tick();
        const interval = window.setInterval(tick, 1000);

        return () => window.clearInterval(interval);
    }, []);

    return (
        <div className="ml-auto inline-flex max-w-full items-center gap-1.5 rounded-full bg-primary px-3 py-1.5 text-xs font-semibold text-primary-foreground">
            <Clock3 className="size-3.5 shrink-0" aria-hidden="true" />
            <span>
                Manila time
                {now && (
                    <>
                        {' · '}
                        <time className="tabular-nums" dateTime={now.toISOString()}>
                            {manilaClock.format(now)}
                        </time>
                    </>
                )}
            </span>
        </div>
    );
}

function AnnouncementEntry({
    announcement,
    featured = false,
}: {
    announcement: DashboardAnnouncement;
    featured?: boolean;
}) {
    return (
        <article className="min-w-0 space-y-1.5">
            <h3
                className={`font-semibold [overflow-wrap:anywhere] ${featured ? 'text-lg' : 'text-sm'}`}
            >
                {announcement.title}
            </h3>
            {announcement.visible_at && (
                <time
                    dateTime={announcement.visible_at}
                    className="block text-xs text-muted-foreground"
                >
                    Posted {manilaDateTime.format(new Date(announcement.visible_at))}{' '}
                    Manila time
                </time>
            )}
            <p className="text-sm whitespace-pre-wrap [overflow-wrap:anywhere]">
                {announcement.content}
            </p>
        </article>
    );
}

export default function DashboardAnnouncements({
    announcements,
    canManage,
}: {
    announcements: DashboardAnnouncement[];
    canManage: boolean;
}) {
    const [latest, ...older] = announcements;

    return (
        <section
            id="dashboard-announcements"
            aria-labelledby="dashboard-announcements-title"
            className={`scroll-mt-20 space-y-4 rounded-xl border border-l-4 p-4 text-card-foreground sm:p-5 ${
                latest
                    ? 'border-primary/40 border-l-primary bg-gradient-to-r from-primary/15 to-card'
                    : 'border-l-primary bg-card'
            }`}
        >
            <div className="flex flex-wrap items-center justify-between gap-3">
                <h2
                    id="dashboard-announcements-title"
                    className="flex items-center gap-2 text-sm font-semibold"
                >
                    <Megaphone
                        className="size-4 text-primary"
                        aria-hidden="true"
                    />
                    Announcements
                </h2>
                <ManilaClock />
            </div>
            {latest ? (
                <AnnouncementEntry announcement={latest} featured />
            ) : (
                <p className="text-sm text-muted-foreground">
                    No active announcements.
                </p>
            )}
            {older.length > 0 && (
                <details className="border-t pt-3">
                    <summary className="cursor-pointer text-sm font-medium">
                        Earlier announcements ({older.length})
                    </summary>
                    <div className="mt-3 space-y-4">
                        {older.map((announcement) => (
                            <AnnouncementEntry
                                key={announcement.id}
                                announcement={announcement}
                            />
                        ))}
                    </div>
                </details>
            )}
            {canManage && (
                <Button asChild size="sm" variant="outline">
                    <Link href="/hris/announcements">Manage announcements</Link>
                </Button>
            )}
        </section>
    );
}
