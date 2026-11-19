import { usePage } from '@inertiajs/react';
import '@/pages/dashboard.css';
import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import type { AppLayoutProps } from '@/types';

export default function AppSidebarLayout({ children }: AppLayoutProps) {
    const isDashboard = usePage().component === 'dashboard';

    return (
        <AppShell
            variant="sidebar"
            className={isDashboard ? 'dashboard-shell' : undefined}
        >
            <AppSidebar />
            <AppContent
                variant="sidebar"
                className={
                    'public-surface isolate min-w-0 overflow-x-clip bg-background text-foreground' +
                    (isDashboard ? ' dashboard-surface' : '')
                }
            >
                <AppSidebarHeader />
                {children}
            </AppContent>
        </AppShell>
    );
}
