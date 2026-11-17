import { Link, usePage } from '@inertiajs/react';
import AppLogo from '@/components/app-logo';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { UserMenuContent } from '@/components/user-menu-content';
import { useInitials } from '@/hooks/use-initials';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

export function AppHeader({ breadcrumbs = [] }: Props) {
    const { auth } = usePage().props;
    const getInitials = useInitials();

    return (
        <header className="border-b border-border bg-background">
            <div className="mx-auto flex min-h-16 w-full max-w-7xl items-center justify-between gap-3 px-4 md:px-8">
                <Link
                    href={dashboard()}
                    className="flex min-w-0 items-center gap-2 rounded-md focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-ring"
                    aria-label="LGU Clarin HRIS dashboard"
                >
                    <AppLogo />
                </Link>
                <div className="flex shrink-0 items-center gap-2">
                    <nav aria-label="Main navigation">
                        <Button variant="ghost" asChild>
                            <Link href={dashboard()}>Dashboard</Link>
                        </Button>
                    </nav>
                    {auth.user && (
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button
                                    variant="ghost"
                                    className="size-10 rounded-full p-1"
                                    aria-label="Open account menu"
                                >
                                    <Avatar className="size-8">
                                        <AvatarImage
                                            src={auth.user.avatar}
                                            alt={auth.user.name}
                                        />
                                        <AvatarFallback>
                                            {getInitials(auth.user.name)}
                                        </AvatarFallback>
                                    </Avatar>
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent className="w-56" align="end">
                                <UserMenuContent user={auth.user} />
                            </DropdownMenuContent>
                        </DropdownMenu>
                    )}
                </div>
            </div>
            {breadcrumbs.length > 1 && (
                <div className="mx-auto w-full max-w-7xl px-4 pb-4 md:px-8">
                    <Breadcrumbs breadcrumbs={breadcrumbs} />
                </div>
            )}
        </header>
    );
}
