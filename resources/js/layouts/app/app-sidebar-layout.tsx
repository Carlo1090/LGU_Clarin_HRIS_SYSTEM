import '@/pages/dashboard.css';
import '@/pages/workspace.css';
import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import type { AppLayoutProps } from '@/types';

export default function AppSidebarLayout({ children }: AppLayoutProps) {
    return (
        <AppShell variant="sidebar" className="dashboard-shell">
            <AppSidebar />
            <AppContent
                variant="sidebar"
                className="public-surface dashboard-surface isolate min-w-0 overflow-x-clip bg-background text-foreground"
            >
                <AppSidebarHeader />
                <div className="@container/workspace min-w-0 flex-1">
                    {children}
                </div>
            </AppContent>
        </AppShell>
    );
}
