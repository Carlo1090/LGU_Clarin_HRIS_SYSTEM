import { Link, usePage } from "@inertiajs/react";
import AppLogo from "@/components/app-logo";
import AppearanceToggle from "@/components/appearance-toggle";
import { Button } from "@/components/ui/button";
import { dashboard, home, login } from "@/routes";

export default function PublicHeader({ showNavigation = false }: { showNavigation?: boolean }) {
    const { auth } = usePage().props;

    return (
        <header className="sticky top-0 z-50 border-b border-border/60 bg-background/95 backdrop-blur-md">
            <nav
                aria-label="Main navigation"
                className="mx-auto flex min-h-20 w-full max-w-6xl items-center justify-between gap-3 px-4 sm:px-6 lg:px-10"
            >
                <Link
                    href={home()}
                    aria-label="LGU Clarin HRIS home"
                    className="flex min-w-0 items-center gap-2 rounded-md focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-ring [&>div:last-child]:hidden sm:[&>div:last-child]:grid"
                >
                    <AppLogo />
                </Link>
                {showNavigation && (
                    <div className="hidden items-center gap-6 text-sm font-medium text-muted-foreground lg:flex">
                        <a href="#features" className="transition-colors hover:text-foreground">
                            Features
                        </a>
                        <a href="#how-it-works" className="transition-colors hover:text-foreground">
                            How it works
                        </a>
                        <a href="#departments" className="transition-colors hover:text-foreground">
                            Offices
                        </a>
                        <a href="#about" className="transition-colors hover:text-foreground">
                            About
                        </a>
                    </div>
                )}
                <div className="flex shrink-0 items-center gap-1 sm:gap-2">
                    <AppearanceToggle />
                    {auth.user ? (
                        <Button asChild className="rounded-full">
                            <Link href={dashboard()}>Dashboard</Link>
                        </Button>
                    ) : (
                        <>
                            <Button
                                asChild
                                variant="ghost"
                                className="rounded-full px-3 text-xs sm:text-sm"
                            >
                                <Link href={login()}>Log in</Link>
                            </Button>
                        </>
                    )}
                </div>
            </nav>
        </header>
    );
}
