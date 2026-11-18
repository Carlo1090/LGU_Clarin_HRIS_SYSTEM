import { Link, usePage } from '@inertiajs/react';
import { BriefcaseBusiness, Building2, CalendarCheck, ClipboardList, Folder, LayoutGrid, Users } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
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
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

const moduleIcons: Record<string, LucideIcon> = {
    '/hris/employees': Users,
    '/hris/departments': Building2,
    '/hris/attendance': CalendarCheck,
    '/hris/leave-applications': ClipboardList,
    '/hris/vacancies': BriefcaseBusiness,
};

export function AppSidebar() {
    const { hrisNavigation, isHrmoWorkspace } = usePage<{
        isHrmoWorkspace: boolean;
        hrisNavigation: { title: string; href: string }[];
    }>().props;
    const items: NavItem[] = [
        ...mainNavItems,
        ...hrisNavigation.map((item) => ({ ...item, icon: moduleIcons[item.href] ?? Folder })),
    ];

    return (
        <Sidebar collapsible="icon" variant="inset" className="portal-sidebar">
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
                <NavMain items={items} label={isHrmoWorkspace ? 'HRMO workspace' : undefined} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
