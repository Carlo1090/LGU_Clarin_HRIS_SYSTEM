import { Link } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import WorkspaceHeader from '@/components/workspace-header';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn, toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem } from '@/types';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Profile',
        href: edit(),
        icon: null,
    },
    {
        title: 'Security',
        href: editSecurity(),
        icon: null,
    },
    {
        title: 'Appearance',
        href: editAppearance(),
        icon: null,
    },
];

export default function SettingsLayout({ children }: PropsWithChildren) {
    const { isCurrentOrParentUrl } = useCurrentUrl();

    return (
        <div className="workspace-page">
            <WorkspaceHeader
                title="Settings"
                description="Manage your profile and account settings"
            />

            <div className="flex flex-col @min-[50rem]/workspace:flex-row @min-[50rem]/workspace:gap-6">
                <aside className="workspace-panel w-full shrink-0 self-start p-2 @min-[50rem]/workspace:w-48">
                    <nav
                        className="flex flex-col space-y-1 space-x-0"
                        aria-label="Settings"
                    >
                        {sidebarNavItems.map((item, index) => (
                            <Button
                                key={`${toUrl(item.href)}-${index}`}
                                size="sm"
                                variant="ghost"
                                asChild
                                className={cn('w-full justify-start', {
                                    'bg-accent text-accent-foreground':
                                        isCurrentOrParentUrl(item.href),
                                })}
                            >
                                <Link
                                    href={item.href}
                                    aria-current={
                                        isCurrentOrParentUrl(item.href)
                                            ? 'page'
                                            : undefined
                                    }
                                >
                                    {item.icon && (
                                        <item.icon className="h-4 w-4" />
                                    )}
                                    {item.title}
                                </Link>
                            </Button>
                        ))}
                    </nav>
                </aside>

                <Separator className="my-6 @min-[50rem]/workspace:hidden" />

                <div className="workspace-panel min-w-0 flex-1 p-5 sm:p-7">
                    <section className="max-w-3xl space-y-12">
                        {children}
                    </section>
                </div>
            </div>
        </div>
    );
}
