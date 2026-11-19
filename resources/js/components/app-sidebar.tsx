import { Link, usePage } from '@inertiajs/react';
import { LayoutGrid } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { hrisResourceIcon } from '@/lib/hris-icons';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

export function AppSidebar() {
    const isDashboard = usePage().component === 'dashboard';
    const { hrisNavigation, isHrmoWorkspace } = usePage<{
        isHrmoWorkspace: boolean;
        hrisNavigation: { title: string; href: string }[];
    }>().props;
    const items: NavItem[] = [
        ...mainNavItems,
        ...hrisNavigation.map((item) => ({
            ...item,
            icon: hrisResourceIcon(item.href.replace('/hris/', '')),
        })),
    ];

    return (
        <Sidebar
            collapsible="icon"
            variant="inset"
            className={
                'portal-sidebar' + (isDashboard ? ' dashboard-sidebar' : '')
            }
        >
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain
                    items={items}
                    label={isHrmoWorkspace ? 'HRMO workspace' : undefined}
                />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
