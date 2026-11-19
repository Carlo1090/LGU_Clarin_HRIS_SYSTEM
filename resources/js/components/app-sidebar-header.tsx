import { Link, router, usePage } from '@inertiajs/react';
import {
    Bell,
    CheckCheck,
    LayoutDashboard,
    Search,
    Settings2,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import AppearanceToggle from '@/components/appearance-toggle';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    CommandDialog,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
    CommandSeparator,
} from '@/components/ui/command';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Separator } from '@/components/ui/separator';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { UserMenuContent } from '@/components/user-menu-content';
import { useInitials } from '@/hooks/use-initials';
import { hrisResourceIcon } from '@/lib/hris-icons';
import { edit } from '@/routes/profile';
import type { NotificationFeed } from '@/types/dashboard';

type Destination = { title: string; href: string };

export function AppSidebarHeader() {
    const { auth, hrisNavigation, notifications } = usePage<{
        hrisNavigation: Destination[];
        notifications: NotificationFeed;
    }>().props;
    const isDashboard = usePage().component === 'dashboard';
    const surface = isDashboard
        ? 'public-surface dashboard-popover'
        : 'public-surface';
    const [searchOpen, setSearchOpen] = useState(false);
    const [updating, setUpdating] = useState(false);
    const initials = useInitials();
    const feed = notifications ?? { unreadCount: 0, items: [] };
    const destinations: Destination[] = [
        { title: 'Dashboard', href: '/dashboard' },
        ...(hrisNavigation ?? []),
    ];

    useEffect(() => {
        const handleKey = (event: KeyboardEvent) => {
            if (
                event.key.toLowerCase() === 'k' &&
                (event.metaKey || event.ctrlKey)
            ) {
                event.preventDefault();
                setSearchOpen((open) => !open);
            }
        };
        document.addEventListener('keydown', handleKey);
        return () => document.removeEventListener('keydown', handleKey);
    }, []);

    const visit = (href: string) => {
        setSearchOpen(false);
        router.visit(href);
    };
    const markRead = (id?: number) => {
        if (updating) return;
        setUpdating(true);
        router.post(
            id ? `/notifications/${id}/read` : '/notifications/read-all',
            {},
            {
                preserveScroll: true,
                onFinish: () => setUpdating(false),
            },
        );
    };

    return (
        <header className="sticky top-0 z-30 flex h-16 shrink-0 items-center gap-3 rounded-t-[inherit] border-b border-border bg-background/95 px-4 backdrop-blur-md sm:px-6">
            <SidebarTrigger className="-ml-1 shrink-0" />
            <Separator
                orientation="vertical"
                className="hidden h-4! sm:block"
            />
            <Button
                type="button"
                variant="outline"
                className="h-9 min-w-0 flex-1 justify-start gap-2 rounded-lg text-muted-foreground shadow-none sm:max-w-sm"
                aria-label="Search pages and tools"
                aria-keyshortcuts="Control+K Meta+K"
                onClick={() => setSearchOpen(true)}
            >
                <Search className="size-4 shrink-0" aria-hidden="true" />
                <span className="truncate text-xs sm:text-sm">
                    Search workspace...
                </span>
                <kbd className="ml-auto hidden shrink-0 rounded border bg-muted px-1.5 py-0.5 font-mono text-[10px] sm:inline-flex">
                    Ctrl K
                </kbd>
            </Button>
            <div className="ml-auto flex shrink-0 items-center gap-0.5 sm:gap-1">
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            className="relative rounded-full"
                            aria-label={`Notifications${feed.unreadCount ? `, ${feed.unreadCount} unread` : ''}`}
                        >
                            <Bell className="size-4" aria-hidden="true" />
                            {feed.unreadCount > 0 && (
                                <span className="absolute top-1 right-1 flex min-h-3.5 min-w-3.5 items-center justify-center rounded-full bg-[#69b518] px-1 text-[9px] font-bold text-white">
                                    {feed.unreadCount > 99
                                        ? '99+'
                                        : feed.unreadCount}
                                </span>
                            )}
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        align="end"
                        className={
                            surface +
                            ' w-[min(22rem,calc(100vw-2rem))] rounded-xl p-0'
                        }
                    >
                        <div className="flex items-center justify-between gap-2 px-4 py-3">
                            <DropdownMenuLabel className="p-0">
                                Notifications
                            </DropdownMenuLabel>
                            <Button
                                type="button"
                                size="sm"
                                variant="ghost"
                                className="h-7 px-2 text-xs"
                                disabled={!feed.unreadCount || updating}
                                onClick={() => markRead()}
                            >
                                <CheckCheck
                                    className="size-3.5"
                                    aria-hidden="true"
                                />{' '}
                                Mark all read
                            </Button>
                        </div>
                        <DropdownMenuSeparator className="m-0" />
                        {feed.items.length ? (
                            <div className="max-h-80 overflow-y-auto p-1">
                                {feed.items.map((item) => (
                                    <DropdownMenuItem
                                        key={item.id}
                                        disabled={updating}
                                        className="items-start gap-3 rounded-lg px-3 py-3"
                                        onSelect={(event) => {
                                            event.preventDefault();
                                            if (!item.isRead) markRead(item.id);
                                        }}
                                    >
                                        <span
                                            className={`mt-1.5 size-2 shrink-0 rounded-full ${item.isRead ? 'bg-muted-foreground/25' : 'bg-[#69b518]'}`}
                                            aria-hidden="true"
                                        />
                                        <div className="min-w-0 space-y-1">
                                            <p
                                                className={`text-xs ${item.isRead ? 'font-medium' : 'font-semibold'}`}
                                            >
                                                {item.title}
                                            </p>
                                            <p className="text-xs leading-relaxed text-muted-foreground">
                                                {item.message}
                                            </p>
                                            {item.createdAt && (
                                                <p className="text-[10px] text-muted-foreground">
                                                    {new Intl.DateTimeFormat(
                                                        'en-PH',
                                                        {
                                                            dateStyle: 'medium',
                                                            timeStyle: 'short',
                                                            timeZone:
                                                                'Asia/Manila',
                                                        },
                                                    ).format(
                                                        new Date(
                                                            item.createdAt,
                                                        ),
                                                    )}
                                                </p>
                                            )}
                                            {!item.isRead && (
                                                <span className="sr-only">
                                                    Select to mark as read
                                                </span>
                                            )}
                                        </div>
                                    </DropdownMenuItem>
                                ))}
                            </div>
                        ) : (
                            <div className="flex flex-col items-center px-5 py-9 text-center">
                                <span className="mb-3 flex size-10 items-center justify-center rounded-full bg-muted">
                                    <Bell
                                        className="size-4 text-muted-foreground"
                                        aria-hidden="true"
                                    />
                                </span>
                                <p className="text-sm font-medium">
                                    You're all caught up
                                </p>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    New notifications will appear here.
                                </p>
                            </div>
                        )}
                    </DropdownMenuContent>
                </DropdownMenu>
                <AppearanceToggle />
                <Button
                    asChild
                    variant="ghost"
                    size="icon"
                    className="hidden rounded-full sm:inline-flex"
                >
                    <Link href={edit()} aria-label="Account settings">
                        <Settings2 className="size-4" aria-hidden="true" />
                    </Link>
                </Button>
                {auth.user && (
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button
                                variant="ghost"
                                size="icon"
                                className="ml-1 rounded-full"
                                aria-label="Open profile menu"
                            >
                                <Avatar className="size-8 border border-border">
                                    <AvatarImage
                                        src={auth.user.avatar}
                                        alt=""
                                    />
                                    <AvatarFallback className="bg-accent text-[11px] font-semibold text-accent-foreground">
                                        {initials(auth.user.name)}
                                    </AvatarFallback>
                                </Avatar>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent
                            align="end"
                            className={surface + ' min-w-60 rounded-xl'}
                        >
                            <UserMenuContent user={auth.user} />
                        </DropdownMenuContent>
                    </DropdownMenu>
                )}
            </div>
            <CommandDialog
                open={searchOpen}
                onOpenChange={setSearchOpen}
                title="Search your workspace"
                description="Find and open the pages and tools available to your account."
                className={surface + ' rounded-xl'}
            >
                <CommandInput
                    placeholder="Search pages and tools..."
                    className="pr-10"
                />
                <CommandList>
                    <CommandEmpty>
                        No matching pages. Try another search.
                    </CommandEmpty>
                    <CommandGroup heading="Workspace">
                        {destinations.map((item) => {
                            const Icon =
                                item.href === '/dashboard'
                                    ? LayoutDashboard
                                    : hrisResourceIcon(
                                          item.href.replace('/hris/', ''),
                                      );
                            return (
                                <CommandItem
                                    key={item.href}
                                    value={`${item.title} ${item.href}`}
                                    onSelect={() => visit(item.href)}
                                >
                                    <Icon
                                        className="size-4"
                                        aria-hidden="true"
                                    />
                                    <span>{item.title}</span>
                                </CommandItem>
                            );
                        })}
                    </CommandGroup>
                    <CommandSeparator />
                    <CommandGroup heading="Account">
                        <CommandItem onSelect={() => visit(edit().url)}>
                            <Settings2 className="size-4" aria-hidden="true" />
                            Profile and account settings
                        </CommandItem>
                    </CommandGroup>
                </CommandList>
            </CommandDialog>
        </header>
    );
}
