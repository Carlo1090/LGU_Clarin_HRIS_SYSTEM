import { router } from '@inertiajs/react';
import { useEffect, useState, type ReactNode } from 'react';
import { Skeleton } from '@/components/ui/skeleton';

export function DirectoryResults({ children }: { children: ReactNode }) {
    const [loading, setLoading] = useState(false);
    useEffect(() => {
        const active = new Set<object>();
        const start = router.on('start', ({ detail: { visit } }) => {
            if (
                visit.method === 'get' &&
                visit.url.pathname === window.location.pathname
            ) {
                active.add(visit);
                setLoading(true);
            }
        });
        const finish = router.on('finish', ({ detail: { visit } }) => {
            active.delete(visit);
            setLoading(active.size > 0);
        });
        return () => {
            start();
            finish();
        };
    }, []);
    if (!loading) return children;
    return (
        <div
            role="status"
            aria-label="Loading records"
            className="divide-y px-4"
        >
            {Array.from({ length: 7 }, (_, i) => (
                <div
                    key={i}
                    aria-hidden="true"
                    className="grid grid-cols-[2fr_1fr_1fr] items-center gap-5 py-4"
                >
                    <Skeleton className="h-4 w-3/4" />
                    <Skeleton className="h-4 w-2/3" />
                    <Skeleton className="h-4 w-1/2" />
                </div>
            ))}
        </div>
    );
}
