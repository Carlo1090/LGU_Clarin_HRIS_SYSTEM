import { Link, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import AppLogo from '@/components/app-logo';
import AppearanceToggle from '@/components/appearance-toggle';
import { Button } from '@/components/ui/button';
import { dashboard, home, login } from '@/routes';

const navigation = [
    { id: 'departments', label: 'Offices' },
    { id: 'features', label: 'Features' },
    { id: 'how-it-works', label: 'How it works' },
    { id: 'users', label: 'Users' },
    { id: 'about', label: 'About' },
];

export default function PublicHeader({
    showNavigation = false,
}: {
    showNavigation?: boolean;
}) {
    const {
        component,
        props: { auth },
    } = usePage();

    const headerRef = useRef<HTMLElement>(null);
    const [activeSection, setActiveSection] = useState<string>();

    useEffect(() => {
        if (!showNavigation) {
            return;
        }

        const sections = Array.from(
            document.querySelectorAll<HTMLElement>('main section[id]'),
        ).filter((section) => navigation.some(({ id }) => id === section.id));

        function updateActiveSection() {
            const marker = (headerRef.current?.offsetHeight ?? 0) + 40;
            const current = [...sections]
                .reverse()
                .find(
                    (section) => section.getBoundingClientRect().top <= marker,
                );
            setActiveSection(current?.id);
        }

        updateActiveSection();
        window.addEventListener('scroll', updateActiveSection, {
            passive: true,
        });
        window.addEventListener('resize', updateActiveSection);

        return () => {
            window.removeEventListener('scroll', updateActiveSection);
            window.removeEventListener('resize', updateActiveSection);
        };
    }, [showNavigation]);

    return (
        <header
            ref={headerRef}
            className="sticky top-0 z-50 border-b border-border/60 bg-background/95 backdrop-blur-md"
        >
            <nav
                aria-label="Main navigation"
                className="mx-auto flex min-h-20 w-full max-w-6xl flex-wrap items-center justify-between gap-x-3 px-4 sm:px-6 lg:flex-nowrap lg:px-10"
            >
                <Link
                    href={home()}
                    aria-label="LGU Clarin HRIS home"
                    className="flex min-h-20 min-w-0 items-center gap-2 rounded-md focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-ring [&>div:last-child]:hidden sm:[&>div:last-child]:grid"
                >
                    <AppLogo />
                </Link>
                {showNavigation && (
                    <div className="order-last flex w-full items-center gap-1 overflow-x-auto pb-2 text-xs font-medium text-muted-foreground lg:order-none lg:w-auto lg:overflow-visible lg:pb-0 lg:text-sm">
                        {navigation.map(({ id, label }) => (
                            <a
                                key={id}
                                href={`#${id}`}
                                aria-current={
                                    activeSection === id
                                        ? 'location'
                                        : undefined
                                }
                                onClick={() => setActiveSection(id)}
                                className="shrink-0 rounded-md px-2.5 py-2 transition-[scale,color] duration-200 ease-out hover:text-foreground focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-ring aria-[current=location]:scale-110 aria-[current=location]:text-foreground motion-reduce:transition-none motion-reduce:aria-[current=location]:scale-100 dark:aria-[current=location]:text-white"
                            >
                                {label}
                            </a>
                        ))}
                    </div>
                )}
                <div className="flex shrink-0 items-center gap-1 sm:gap-2">
                    <AppearanceToggle />
                    {auth.user ? (
                        <Button asChild className="rounded-full">
                            <Link href={dashboard()}>Dashboard</Link>
                        </Button>
                    ) : component !== 'auth/login' ? (
                        <Button
                            asChild
                            variant="ghost"
                            className="rounded-full px-3 text-xs sm:text-sm"
                        >
                            <Link href={login()}>Log in</Link>
                        </Button>
                    ) : null}
                </div>
            </nav>
        </header>
    );
}
