import AppearanceToggle from '@/components/appearance-toggle';
import { SidebarTrigger } from '@/components/ui/sidebar';

export function AppSidebarHeader() {
    return (
        <header className="sticky top-0 z-20 flex h-16 shrink-0 items-center gap-3 rounded-t-[inherit] border-b border-border bg-background/95 px-4 backdrop-blur-md sm:px-6">
            <SidebarTrigger className="-ml-1 shrink-0" />
            <div className="ml-auto shrink-0">
                <AppearanceToggle />
            </div>
        </header>
    );
}
