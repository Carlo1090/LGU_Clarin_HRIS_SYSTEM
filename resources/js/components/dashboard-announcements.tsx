import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import { Megaphone } from 'lucide-react';

export type DashboardAnnouncement = {
    id: number;
    title: string;
    content: string;
    visible_at: string | null;
};

const manilaDateTime = new Intl.DateTimeFormat('en-PH', {
    timeZone: 'Asia/Manila',
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
});

function AnnouncementEntry({
    announcement,
}: {
    announcement: DashboardAnnouncement;
}) {
    return (
        <article className="min-w-0 space-y-1.5">
            <h3 className="text-sm font-semibold [overflow-wrap:anywhere]">
                {announcement.title}
            </h3>
            {announcement.visible_at && (
                <time
                    dateTime={announcement.visible_at}
                    className="block text-xs text-muted-foreground"
                >
                    {manilaDateTime.format(new Date(announcement.visible_at))}{' '}
                    Manila time
                </time>
            )}
            <p className="text-sm [overflow-wrap:anywhere] whitespace-pre-wrap">
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
            aria-labelledby="dashboard-announcements"
            className="space-y-4 rounded-lg border border-l-4 border-l-primary bg-card p-4 text-card-foreground sm:p-5"
        >
            <div className="flex flex-wrap items-center justify-between gap-3">
                <h2
                    id="dashboard-announcements"
                    className="flex items-center gap-2 text-sm font-semibold"
                >
                    <Megaphone
                        className="size-4 text-primary"
                        aria-hidden="true"
                    />
                    Announcements
                </h2>
                {canManage && (
                    <Button asChild size="sm" variant="outline">
                        <Link href="/hris/announcements">
                            Manage announcements
                        </Link>
                    </Button>
                )}
            </div>
            {latest ? (
                <AnnouncementEntry announcement={latest} />
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
        </section>
    );
}
