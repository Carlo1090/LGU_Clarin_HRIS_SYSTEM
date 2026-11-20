import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

export type RecordPageLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export default function RecordPagination({
    links,
}: {
    links: RecordPageLink[];
}) {
    if (links.length === 0) return null;

    return (
        <nav
            aria-label="Record pages"
            className="flex flex-wrap gap-2 border-t p-3"
        >
            {links.map((link, index) => {
                const label = link.label.replace(/&laquo;|&raquo;/g, '').trim();
                const variant = link.active ? 'default' : 'outline';
                const key = `${link.label}-${index}`;

                return link.url ? (
                    <Button key={key} asChild variant={variant} size="sm">
                        <Link
                            href={link.url}
                            preserveScroll
                            aria-current={link.active ? 'page' : undefined}
                        >
                            {label}
                        </Link>
                    </Button>
                ) : (
                    <Button
                        key={key}
                        type="button"
                        variant={variant}
                        size="sm"
                        disabled
                    >
                        {label}
                    </Button>
                );
            })}
        </nav>
    );
}
