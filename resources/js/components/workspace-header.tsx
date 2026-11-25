import type { ReactNode } from 'react';
import SpotlightCard from '@/components/SpotlightCard';

export default function WorkspaceHeader({
    title,
    description,
    children,
}: {
    title: string;
    description?: string;
    children?: ReactNode;
}) {
    return (
        <header className="workspace-banner">
            <SpotlightCard
                className="dashboard-spotlight workspace-banner-content"
                theme="dark"
                style={{}}
                spotlightColor="#A6EB20"
                intensity={0.35}
                proximity={0}
            >
                <div className="workspace-banner-copy">
                    <h1>{title}</h1>
                    {description && <p>{description}</p>}
                    {children}
                </div>
            </SpotlightCard>
        </header>
    );
}
