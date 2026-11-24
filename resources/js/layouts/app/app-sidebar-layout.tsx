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
                onPointerMove={(event) => {
                    if (event.pointerType === 'touch' || !(event.target instanceof Element)) return;
                    const panel = event.target.closest<HTMLElement>('.workspace-panel');
                    if (!panel) return;
                    const bounds = panel.getBoundingClientRect();
                    panel.style.setProperty('--panel-x', `${event.clientX - bounds.left}px`);
                    panel.style.setProperty('--panel-y', `${event.clientY - bounds.top}px`);
                }}
                variant="sidebar"
                className="public-surface dashboard-surface isolate min-w-0 overflow-x-clip bg-background text-foreground"
            >
                <AppSidebarHeader />
                {children}
            </AppContent>
        </AppShell>
    );
}
