import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';

export default function PersonAvatar({
    name,
    src,
}: {
    name: string;
    src?: string | null;
}) {
    const initials = useInitials();
    return (
        <Avatar className="inline-flex size-8 shrink-0 align-middle">
            <AvatarImage
                src={src || undefined}
                alt=""
                className="object-cover"
            />
            <AvatarFallback
                aria-hidden="true"
                className="bg-accent text-xs text-accent-foreground"
            >
                {initials(name)}
            </AvatarFallback>
        </Avatar>
    );
}
