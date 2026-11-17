import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight, Landmark } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { dashboard, login, register } from '@/routes';

export default function Welcome() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Welcome">
                <meta
                    name="description"
                    content="LGU Clarin Human Resource Information System. Access your municipal HR workspace."
                />
            </Head>
            <div className="flex min-h-svh flex-col bg-background text-foreground">
                <header className="border-b">
                    <nav
                        aria-label="Main navigation"
                        className="mx-auto flex w-full max-w-6xl items-center justify-between gap-4 px-6 py-5"
                    >
                        <div className="flex items-center gap-2">
                            <AppLogo />
                        </div>
                        <Link
                            href={auth.user ? dashboard() : login()}
                            className="shrink-0 rounded-lg border px-4 py-2 text-sm font-medium transition hover:bg-muted focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-emerald-700"
                        >
                            {auth.user ? 'Dashboard' : 'Log in'}
                        </Link>
                    </nav>
                </header>
                <main className="mx-auto grid w-full max-w-6xl flex-1 items-center gap-12 px-6 py-16 lg:grid-cols-[1.2fr_1fr] lg:gap-20 lg:py-24">
                    <section>
                        <p className="mb-6 inline-flex rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold tracking-wide text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">
                            LOCAL GOVERNMENT UNIT OF CLARIN
                        </p>
                        <h1 className="max-w-xl text-4xl leading-tight font-semibold tracking-tight sm:text-5xl lg:text-6xl">
                            Our people.
                            <br />
                            Our public service.
                        </h1>
                        <p className="mt-6 text-lg font-medium text-emerald-700 dark:text-emerald-400">
                            Human Resource Information System
                        </p>
                        <p className="mt-3 max-w-lg text-base leading-7 text-muted-foreground">
                            Welcome to the LGU Clarin HRIS. Sign in to access
                            your workspace and the HR information available to
                            your role.
                        </p>
                        <div className="mt-8 flex flex-wrap items-center gap-4">
                            <Link
                                href={auth.user ? dashboard() : login()}
                                className="inline-flex items-center gap-3 rounded-xl bg-emerald-700 px-6 py-3 text-sm font-semibold text-white transition hover:bg-emerald-800 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-emerald-700"
                            >
                                {auth.user
                                    ? 'Open your dashboard'
                                    : 'Sign in to HRIS'}
                                <ArrowRight
                                    aria-hidden="true"
                                    className="size-4"
                                />
                            </Link>
                            {!auth.user && (
                                <Link
                                    href={register()}
                                    className="rounded-md px-2 py-3 text-sm font-medium underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-emerald-700"
                                >
                                    Create an account
                                </Link>
                            )}
                        </div>
                        <p className="mt-8 max-w-md text-sm leading-6 text-muted-foreground">
                            Need help with your account? Contact the HRMO or
                            your MIS Administrator.
                        </p>
                    </section>
                    <aside className="relative isolate overflow-hidden rounded-3xl bg-gradient-to-br from-emerald-950 via-emerald-900 to-teal-800 p-8 text-white shadow-xl sm:p-12">
                        <div
                            aria-hidden="true"
                            className="absolute -top-20 -right-20 -z-10 size-72 rounded-full border border-white/10"
                        />
                        <div
                            aria-hidden="true"
                            className="absolute -right-8 -bottom-28 -z-10 size-80 rounded-full border border-white/10"
                        />
                        <div className="mb-12 inline-flex size-20 items-center justify-center rounded-2xl border border-white/20 bg-white/10">
                            <Landmark
                                aria-hidden="true"
                                className="size-10"
                                strokeWidth={1.5}
                            />
                        </div>
                        <p className="text-xs font-semibold tracking-[0.2em] text-emerald-200 uppercase">
                            LGU Clarin
                        </p>
                        <h2 className="mt-3 text-3xl leading-tight font-semibold tracking-tight">
                            Supporting the people who serve our community.
                        </h2>
                        <p className="mt-5 text-sm leading-7 text-emerald-100/80">
                            A shared workspace for municipal human resource
                            administration.
                        </p>
                        <div className="mt-10 border-t border-white/15 pt-6 text-sm text-emerald-100">
                            Human Resource Management Office
                        </div>
                    </aside>
                </main>
                <footer className="border-t px-6 py-5 text-center text-xs text-muted-foreground">
                    LGU Clarin · Human Resource Information System
                </footer>
            </div>
        </>
    );
}
