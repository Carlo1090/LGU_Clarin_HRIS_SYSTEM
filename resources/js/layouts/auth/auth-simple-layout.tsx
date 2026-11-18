import { FileText, ShieldCheck } from 'lucide-react';
import PublicHeader from '@/components/public-header';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
} from '@/components/ui/card';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="public-surface flex min-h-svh flex-col bg-background text-foreground selection:bg-primary selection:text-primary-foreground">
            <a
                href="#auth-content"
                className="sr-only z-[60] rounded-md bg-primary px-4 py-3 text-primary-foreground focus:not-sr-only focus:fixed focus:top-3 focus:left-3"
            >
                Skip to form
            </a>
            <PublicHeader />
            <main
                id="auth-content"
                className="mx-auto grid w-full max-w-6xl flex-1 items-center gap-10 px-4 py-8 sm:px-6 sm:py-12 lg:grid-cols-2 lg:gap-16 lg:px-10"
            >
                <div className="mx-auto w-full max-w-md min-w-0">
                    {/* <Link
                        href={home()}
                        className="mb-6 inline-flex items-center gap-2 rounded-md text-sm font-medium text-muted-foreground transition-colors hover:text-foreground focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-ring"
                    >
                        <ArrowLeft aria-hidden="true" className="size-4" /> Back
                        to home
                    </Link> */}
                    <Card className="gap-6 rounded-3xl border-border/80 py-7 shadow-xl shadow-[#142014]/[0.04] sm:py-9">
                        <CardHeader className="gap-3 px-6 sm:px-8">
                            <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                                {title}
                            </h1>
                            <CardDescription className="leading-6">
                                {description}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="px-6 sm:px-8">
                            {children}
                        </CardContent>
                    </Card>
                    <p className="mt-5 text-center text-xs leading-5 text-muted-foreground">
                        Need help with access? Contact your HRMO or MIS
                        Administrator.
                    </p>
                </div>
                <aside
                    aria-label="Your municipal workspace"
                    className="hidden min-w-0 lg:block"
                    data-test="auth-hero"
                >
                    <div className="overflow-hidden rounded-[2rem] border border-border bg-gradient-to-br from-accent/70 via-card to-card p-6 shadow-xl shadow-[#142014]/[0.04] xl:p-8 dark:from-[#1C3020] dark:via-card">
                        <div className="relative isolate flex h-[370px] items-end justify-center xl:h-[400px]">
                            <div
                                aria-hidden="true"
                                className="pointer-events-none absolute top-5 left-1/2 -z-10 size-80 -translate-x-1/2 rounded-full border border-primary/25"
                            />
                            <div
                                aria-hidden="true"
                                className="pointer-events-none absolute top-14 left-1/2 -z-10 size-60 -translate-x-1/2 rounded-full border border-primary/30"
                            />
                            <div
                                aria-hidden="true"
                                className="pointer-events-none absolute top-14 right-3 -z-10 h-48 w-44 rotate-12 rounded-3xl bg-gradient-to-br from-[#89D91B] to-[#B6F236] shadow-lg shadow-primary/10 xl:right-5"
                            />
                            <div
                                aria-hidden="true"
                                className="pointer-events-none absolute bottom-7 left-1 -z-10 h-36 w-48 -rotate-6 rounded-3xl bg-gradient-to-br from-[#164A2C] to-[#236B3B] shadow-lg xl:left-3"
                            />
                            <img
                                src="/images/hero_model_green.png"
                                alt=""
                                width={447}
                                height={559}
                                decoding="async"
                                className="h-full w-full object-contain object-bottom drop-shadow-xl"
                            />
                        </div>
                        <div className="relative border-t border-border/80 pt-6">
                            <p className="mb-3 text-xs font-semibold tracking-[0.18em] text-accent-foreground uppercase">
                                LGU Clarin HRIS
                            </p>
                            <h2 className="text-3xl leading-tight font-semibold tracking-tight xl:text-4xl">
                                Our people.
                                <br />
                                <span className="text-accent-foreground">
                                    Our public service.
                                </span>
                            </h2>
                            <p className="mt-3 max-w-sm text-sm leading-6 text-muted-foreground">
                                Supporting the people who serve our community.
                            </p>
                            <div className="mt-5 flex flex-wrap gap-2">
                                <span className="inline-flex items-center gap-2 rounded-full border border-border bg-background/70 px-3 py-1.5 text-xs font-medium text-muted-foreground">
                                    <FileText
                                        aria-hidden="true"
                                        className="size-3.5 text-accent-foreground"
                                    />{' '}
                                    Employee records
                                </span>
                                <span className="inline-flex items-center gap-2 rounded-full border border-border bg-background/70 px-3 py-1.5 text-xs font-medium text-muted-foreground">
                                    <ShieldCheck
                                        aria-hidden="true"
                                        className="size-3.5 text-accent-foreground"
                                    />{' '}
                                    HRMO support
                                </span>
                            </div>
                        </div>
                    </div>
                </aside>
            </main>
            <footer className="border-t border-border/60 px-4 py-5 text-center text-xs leading-5 text-muted-foreground">
                LGU Clarin &middot; Human Resource Information System
            </footer>
        </div>
    );
}
