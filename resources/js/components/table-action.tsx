import type { ComponentProps } from 'react';
import type { LucideIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';

export default function TableAction({
    label,
    icon: Icon,
    ...props
}: ComponentProps<typeof Button> & { label: string; icon: LucideIcon }) {
    return (
        <Button
            type="button"
            size="icon"
            variant="ghost"
            aria-label={label}
            title={label}
            {...props}
        >
            <Icon aria-hidden="true" />
        </Button>
    );
}
