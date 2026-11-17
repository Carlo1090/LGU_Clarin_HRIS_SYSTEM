import { Head, Link, usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import AOS from 'aos';
import 'aos/dist/aos.css';
import {
    ArrowUpRight,
    CheckCircle2,
    Landmark,
    LayoutGrid,
    Play,
    ShieldCheck,
    TrendingUp,
    UserCheck,
} from 'lucide-react';
import PublicHeader from '@/components/public-header';
import { dashboard, login, register } from '@/routes';

export default function Welcome() {
    const { auth } = usePage().props;

    useEffect(() => {
        AOS.init({
            duration: 700,
            easing: 'ease-out-cubic',
            once: true,
            offset: 60,
            disable: window.matchMedia('(prefers-reduced-motion: reduce)')
                .matches,
        });
    }, []);

    return (
        <>
            <Head title="Welcome">
                {/* <meta
                    name="description"
                    content="LGU Clarin Human Resource Information System. Access your municipal HR workspace."
                /> */}
            </Head>

            <div className="public-surface min-h-screen overflow-x-clip bg-background text-foreground antialiased selection:bg-[#A6EB20] selection:text-primary-foreground">
                <PublicHeader showNavigation />

                <main>
                    {/* Hero Section */}
                    <section className="mx-auto grid w-full max-w-6xl items-center gap-12 px-6 pt-10 pb-20 lg:grid-cols-[1.05fr_1fr] lg:gap-8 lg:px-10">
                        {/* Left Column */}
                        <div data-aos="fade-up">
                            <h1 className="text-4xl leading-[1.1] font-semibold tracking-tight text-foreground sm:text-5xl lg:text-[54px]">
                                Our people.
                                <br />
                                <span className="text-accent-foreground">
                                    Our public
                                </span>
                                <br />
                                <span className="text-accent-foreground">
                                    service.
                                </span>
                            </h1>

                            <p
                                data-aos="fade-up"
                                data-aos-delay="100"
                                className="mt-5 max-w-md text-xs leading-5 text-muted-foreground sm:text-sm"
                            >
                                Welcome to the LGU Clarin HRIS. Sign in to
                                access your workspace, manage 201 files, and
                                streamline municipal HR administration —
                                effortlessly.
                            </p>

                            {/* CTA Row */}
                            <div
                                data-aos="fade-up"
                                data-aos-delay="200"
                                className="mt-7 flex flex-wrap items-center gap-4"
                            >
                                <Link
                                    href={auth.user ? dashboard() : login()}
                                    className="rounded-full bg-[#A6EB20] px-6 py-3 text-xs font-semibold text-primary-foreground shadow-sm transition hover:bg-[#95d818]"
                                >
                                    {auth.user
                                        ? 'Open your dashboard'
                                        : 'Sign in to HRIS'}
                                </Link>

                                <a
                                    href="#how-it-works"
                                    className="inline-flex items-center gap-2.5 text-xs font-semibold text-foreground transition hover:opacity-80"
                                >
                                    <span className="flex size-8 items-center justify-center rounded-full bg-[#1C4722] text-white">
                                        <Play className="ml-0.5 size-3.5 fill-current" />
                                    </span>
                                    How it Works
                                </a>
                            </div>

                            {/* Divider + Pill Tags */}
                            <div
                                data-aos="fade-up"
                                data-aos-delay="300"
                                className="mt-12 max-w-sm border-t border-border pt-5"
                            >
                                <div className="flex flex-wrap gap-2">
                                    {[
                                        '201 Files',
                                        'Leave Credits',
                                        'Service Records',
                                        'DTR Logs',
                                        'Payroll Sync',
                                        'HRMO Support',
                                    ].map((tag) => (
                                        <span
                                            key={tag}
                                            className="rounded-full border border-border bg-card px-3.5 py-1 text-[11px] font-medium text-muted-foreground"
                                        >
                                            {tag}
                                        </span>
                                    ))}
                                </div>
                            </div>
                        </div>

                        {/* Right Column: Cards Behind Hero Girl */}
                        <div
                            data-aos="fade-up"
                            data-aos-delay="150"
                            className="relative flex min-h-[460px] items-center justify-center"
                        >
                            {/* Background Decorative Rings / Glow */}
                            <div className="absolute size-[min(380px,100%)] rounded-full border border-[#8CE01F]/30" />
                            <div className="absolute size-[min(300px,80%)] rounded-full border border-[#8CE01F]/40" />

                            {/* Cards Layer (Positioned behind the girl) */}
                            <div className="pointer-events-none absolute inset-0 flex items-center justify-center">
                                <div className="relative h-[340px] w-full max-w-[400px]">
                                    {/* Top-Left Blank Card */}
                                    <div className="absolute top-2 left-4 z-0 h-28 w-40 rounded-2xl border border-border/90 bg-card shadow-md" />

                                    {/* Top-Right Lime Gradient Blank Card */}
                                    <div className="absolute top-8 right-2 z-0 h-36 w-56 rounded-2xl bg-gradient-to-br from-[#89D91B] to-[#B6F236] opacity-90 shadow-lg" />

                                    {/* Bottom-Left Dark Forest Green Blank Card */}
                                    <div className="absolute bottom-6 left-2 z-0 h-32 w-56 rounded-2xl bg-gradient-to-br from-[#164A2C] to-[#236B3B] opacity-90 shadow-xl" />

                                    {/* Bottom-Right Floating Pill Card */}
                                    <div className="absolute right-10 bottom-12 z-0 h-10 w-24 rounded-full border border-border bg-card shadow-md" />
                                </div>
                            </div>

                            {/* Person Image with Removed Background (Positioned on top of the cards) */}
                            <div className="relative z-10 flex justify-center pt-6">
                                <img
                                    src="/images/hero_model_green.png"
                                    alt="LGU Clarin HRIS User"
                                    className="max-h-[600px] w-auto max-w-full object-contain drop-shadow-2xl"
                                />
                            </div>
                        </div>
                    </section>

                    {/* Partner Offices Strip */}
                    <section
                        id="departments"
                        data-aos="fade-up"
                        className="mx-auto max-w-5xl px-6 py-8 text-center lg:px-10"
                    >
                        <p className="text-[11px] font-medium text-muted-foreground">
                            Municipal Offices &amp; Divisions
                        </p>
                        <div className="mt-6 flex flex-wrap items-center justify-center gap-10 text-muted-foreground sm:gap-16">
                            <span className="text-lg font-extrabold tracking-tight">
                                HRMO
                            </span>
                            <span className="flex items-center -space-x-2">
                                <span className="size-6 rounded-full bg-neutral-400" />
                                <span className="size-6 rounded-full bg-neutral-300" />
                            </span>
                            <span className="flex items-center gap-1.5 text-sm font-bold tracking-wider">
                                <LayoutGrid className="size-4" />
                                MIS CLARIN
                            </span>
                            <span className="font-serif text-lg font-bold">
                                Treasury
                            </span>
                            <span className="text-lg font-extrabold tracking-tight">
                                Mayor&apos;s Office
                                <span className="text-accent-foreground">
                                    .
                                </span>
                            </span>
                        </div>
                    </section>

                    {/* Features Section */}
                    <section
                        id="features"
                        className="mx-auto max-w-6xl px-6 py-20 lg:px-10"
                    >
                        <div
                            data-aos="fade-up"
                            className="flex flex-wrap items-center justify-between gap-4"
                        >
                            <span className="rounded-full bg-[#1C4722] px-4 py-1 text-[11px] font-semibold text-white">
                                Features
                            </span>
                            <span className="text-xs text-muted-foreground">
                                Everything you need, nothing you don&apos;t.
                            </span>
                        </div>

                        <h2
                            data-aos="fade-up"
                            data-aos-delay="100"
                            className="mt-6 max-w-3xl text-3xl leading-[1.2] font-semibold tracking-tight text-foreground sm:text-4xl"
                        >
                            Achieve{' '}
                            <span className="text-accent-foreground">
                                administrative clarity
                            </span>{' '}
                            and take control of municipal records with tools
                            designed to simplify, streamline, and personalize
                            your{' '}
                            <span className="text-accent-foreground">
                                workforce management
                            </span>{' '}
                            — all in one secure platform.
                        </h2>

                        {/* 4 Feature Cards */}
                        <div className="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                            {/* Card 1 */}
                            <div
                                data-aos="fade-up"
                                data-aos-delay="0"
                                className="group relative flex h-72 flex-col justify-between overflow-hidden rounded-3xl bg-gradient-to-b from-[#D7F75B] to-[#B7EA2B] p-6 text-[#142014]"
                            >
                                <div className="relative z-10">
                                    <span className="inline-flex rounded-full bg-white/80 px-3 py-1 text-[10px] font-semibold text-[#142014]">
                                        LGU Clarin
                                    </span>
                                </div>
                                <div className="relative z-10 my-auto flex justify-center">
                                    <div className="flex size-24 items-center justify-center rounded-2xl bg-[#1C4722] text-[#A6EB20] shadow-xl transition group-hover:scale-105">
                                        <Landmark
                                            className="size-11"
                                            strokeWidth={1.6}
                                        />
                                    </div>
                                </div>
                                <p className="relative z-10 text-xs font-medium text-[#142014]/80">
                                    Centralized municipal employee portal.
                                </p>
                            </div>

                            {/* Card 2 */}
                            <div
                                data-aos="fade-up"
                                data-aos-delay="100"
                                className="relative flex h-72 flex-col justify-between overflow-hidden rounded-3xl bg-gradient-to-b from-[#6CC417] to-[#9CE82A] p-6 text-[#142014]"
                            >
                                <h3 className="relative z-10 text-sm leading-snug font-bold">
                                    Automated 201 Files &amp; Alerts
                                </h3>
                                <ArrowUpRight
                                    aria-hidden="true"
                                    className="pointer-events-none absolute -right-6 -bottom-6 size-56 text-[#4D940D]/25"
                                    strokeWidth={2.5}
                                />
                                <p className="relative z-10 text-xs leading-relaxed text-[#142014]/85">
                                    Maintain updated employee service records
                                    and get real-time alerts on step increments
                                    and documents.
                                </p>
                            </div>

                            {/* Card 3 */}
                            <div
                                data-aos="fade-up"
                                data-aos-delay="200"
                                className="relative flex h-72 flex-col justify-between overflow-hidden rounded-3xl bg-gradient-to-b from-[#B4F232] to-[#9BE322] p-6 text-[#142014]"
                            >
                                <h3 className="relative z-10 text-sm leading-snug font-bold">
                                    Unified Department Dashboard
                                </h3>
                                <div
                                    aria-hidden="true"
                                    className="pointer-events-none absolute top-1/2 left-1/2 flex -translate-x-1/2 -translate-y-1/2 -space-x-8"
                                >
                                    <span className="size-32 rounded-full bg-[#86CC14]/35" />
                                    <span className="size-32 rounded-full bg-[#86CC14]/35" />
                                </div>
                                <p className="relative z-10 text-xs leading-relaxed text-[#142014]/85">
                                    View all municipal offices, leave credits,
                                    and personnel statuses in one intuitive
                                    interface.
                                </p>
                            </div>

                            {/* Card 4 */}
                            <div
                                data-aos="fade-up"
                                data-aos-delay="300"
                                className="relative flex h-72 flex-col justify-between overflow-hidden rounded-3xl bg-gradient-to-b from-[#DCFA70] to-[#B7EE38] p-6 text-[#142014]"
                            >
                                <h3 className="relative z-10 text-sm leading-snug font-bold">
                                    Role-Based HR Insights
                                </h3>
                                <span
                                    aria-hidden="true"
                                    className="pointer-events-none absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-[150px] font-black text-[#95CE1B]/30 select-none"
                                >
                                    §
                                </span>
                                <p className="relative z-10 text-xs leading-relaxed text-[#142014]/85">
                                    Access role-specific HR tools and reports
                                    tailored to employees, department heads, and
                                    HRMO admins.
                                </p>
                            </div>
                        </div>
                    </section>

                    {/* Bright Lime Full-Width Banner Section ("3 Easy Steps") */}
                    <section
                        id="how-it-works"
                        className="relative overflow-hidden bg-[#A6EB20] py-40 text-[#142014]"
                    >
                        <div
                            aria-hidden="true"
                            className="pointer-events-none absolute inset-x-0 -bottom-8 text-center text-[130px] leading-none font-black tracking-tighter text-[#93D616] select-none sm:text-[200px]"
                        >
                            CLARIN
                        </div>

                        <div className="relative z-10 mx-auto grid max-w-7xl items-center gap-12 px-6 lg:grid-cols-2 lg:px-10">
                            {/* Left Dark Widget Card */}
                            <div
                                data-aos="fade-up"
                                className="mx-auto w-full max-w-md rounded-3xl bg-[#1C1D1B] p-7 text-white shadow-2xl"
                            >
                                <div className="flex items-center justify-between">
                                    <span className="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-3 py-1 text-xs font-medium">
                                        <span className="size-2 rounded-full bg-[#A6EB20]" />
                                        LGU Clarin HRIS
                                    </span>
                                    <span className="font-mono text-xs text-neutral-400">
                                        HRMO Portal
                                    </span>
                                </div>

                                <div className="mt-8">
                                    <p className="text-xs text-neutral-400">
                                        Available Leave Credits
                                    </p>
                                    <p className="mt-1 text-4xl font-bold tracking-tight">
                                        24.50 Days
                                    </p>
                                </div>

                                <div className="mt-8 flex items-center justify-between gap-2">
                                    <span className="flex size-10 shrink-0 items-center justify-center rounded-full border border-white/15 text-neutral-300">
                                        <LayoutGrid className="size-4" />
                                    </span>

                                    <Link
                                        href={auth.user ? dashboard() : login()}
                                        className="flex flex-1 items-center justify-center gap-1.5 rounded-full border border-white/15 bg-white/5 py-2.5 text-xs font-medium transition hover:bg-white/10"
                                    >
                                        <CheckCircle2 className="size-3.5 text-[#A6EB20]" />
                                        File Leave
                                    </Link>

                                    <Link
                                        href={auth.user ? dashboard() : login()}
                                        className="flex flex-1 items-center justify-center gap-1.5 rounded-full border border-white/15 bg-white/5 py-2.5 text-xs font-medium transition hover:bg-white/10"
                                    >
                                        <ArrowUpRight className="size-3.5 text-[#A6EB20]" />
                                        201 File
                                    </Link>
                                </div>
                            </div>

                            {/* Right 3 Easy Steps List */}
                            <div data-aos="fade-up" data-aos-delay="150">
                                <h2 className="text-3xl leading-tight font-semibold tracking-tight text-[#142014] sm:text-4xl">
                                    Start Managing Your HR Records in 3 Easy
                                    Steps
                                </h2>

                                <div className="mt-8 space-y-6">
                                    <div className="flex items-start gap-4">
                                        <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-[#1C4722] text-white">
                                            <UserCheck className="size-4" />
                                        </span>
                                        <p className="text-xs leading-relaxed font-medium text-[#142014]/90 sm:text-sm">
                                            <strong className="font-bold">
                                                Sign In to Your Workspace.
                                            </strong>{' '}
                                            Log in using your official LGU
                                            Clarin credentials or register an
                                            account for HRMO verification.
                                        </p>
                                    </div>

                                    <div className="flex items-start gap-4">
                                        <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-[#1C4722] text-white">
                                            <TrendingUp className="size-4" />
                                        </span>
                                        <p className="text-xs leading-relaxed font-medium text-[#142014]/90 sm:text-sm">
                                            <strong className="font-bold">
                                                Access Role-Based HR
                                                Information.
                                            </strong>{' '}
                                            View your personal service records,
                                            attendance summaries, and leave
                                            balances anytime.
                                        </p>
                                    </div>

                                    <div className="flex items-start gap-4">
                                        <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-[#1C4722] text-white">
                                            <ShieldCheck className="size-4" />
                                        </span>
                                        <p className="text-xs leading-relaxed font-medium text-[#142014]/90 sm:text-sm">
                                            <strong className="font-bold">
                                                Submit &amp; Track Requests
                                                Seamlessly.
                                            </strong>{' '}
                                            Coordinate directly with the Human
                                            Resource Management Office with
                                            transparent status updates.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    {/* Bottom Info / Help Section */}
                    <section
                        id="about"
                        data-aos="fade-up"
                        className="mx-auto max-w-6xl px-6 py-20 lg:px-10"
                    >
                        <div className="flex flex-wrap items-center justify-between gap-4">
                            <span className="rounded-full bg-[#1C4722] px-4 py-1 text-[11px] font-semibold text-white">
                                Support &amp; Access
                            </span>
                            <span className="text-xs text-muted-foreground">
                                Official Municipal System · Clarin, Bohol
                            </span>
                        </div>

                        <p
                            id="contact"
                            className="mt-5 max-w-2xl text-sm leading-relaxed text-muted-foreground"
                        >
                            Supporting the people who serve our community. Need
                            help with your account or role permissions? Contact
                            the{' '}
                            <strong className="font-semibold text-foreground">
                                Human Resource Management Office (HRMO)
                            </strong>{' '}
                            or your{' '}
                            <strong className="font-semibold text-foreground">
                                MIS Administrator
                            </strong>
                            .
                        </p>
                    </section>
                </main>

                {/* Creative Custom Footer Styled Like Reference Layout */}
                <footer
                    className="mx-auto max-w-6xl px-6 pb-16 lg:px-10"
                    data-aos="fade-up"
                >
                    <div className="relative overflow-hidden rounded-[3rem] border border-border/80 bg-muted px-6 pt-16 pb-12 text-foreground shadow-sm sm:px-10 lg:px-14">
                        {/* Top Info & Navigation Row */}
                        <div className="grid items-start gap-12 border-b border-border pb-16 lg:grid-cols-[1.5fr_1fr_1fr_1.5fr]">
                            {/* Col 1: Bio / Tagline */}
                            <div>
                                <p className="text-base leading-relaxed font-normal text-muted-foreground">
                                    LGU Clarin is dedicated to progressive
                                    public service, administrative transparency,
                                    and empowered workforce management.
                                </p>
                            </div>

                            {/* Col 2: Explore Links */}
                            <div>
                                <p className="mb-4 text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                    Explore
                                </p>
                                <ul className="space-y-3 text-xs font-medium text-muted-foreground">
                                    <li>
                                        <a
                                            href="#about"
                                            className="transition hover:text-foreground"
                                        >
                                            About LGU
                                        </a>
                                    </li>
                                    <li>
                                        <a
                                            href="#features"
                                            className="transition hover:text-foreground"
                                        >
                                            Portal Features
                                        </a>
                                    </li>
                                    <li>
                                        <a
                                            href="#contact"
                                            className="transition hover:text-foreground"
                                        >
                                            Contact Office
                                        </a>
                                    </li>
                                </ul>
                            </div>

                            {/* Col 3: Employee access Pill Badges */}
                            <div>
                                <p className="mb-4 text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                    Employee access
                                </p>
                                <div className="flex flex-col items-start gap-3 text-sm">
                                    <Link
                                        href={auth.user ? dashboard() : login()}
                                        className="text-muted-foreground hover:text-accent-foreground"
                                    >
                                        {auth.user
                                            ? 'Dashboard'
                                            : 'Log in to HRIS'}
                                    </Link>
                                    {!auth.user && (
                                        <Link
                                            href={register()}
                                            className="text-muted-foreground hover:text-accent-foreground"
                                        >
                                            Create an account
                                        </Link>
                                    )}
                                    <a
                                        href="#contact"
                                        className="text-muted-foreground hover:text-accent-foreground"
                                    >
                                        Account support
                                    </a>
                                </div>
                            </div>

                            {/* Col 4: Action Buttons (Call Office / Tools) */}
                            <div className="flex flex-col gap-4">
                                <Link
                                    href={login()}
                                    className="group flex items-center justify-between rounded-2xl border border-border bg-card p-4 shadow-sm transition hover:border-[#69B518]"
                                >
                                    <div>
                                        <p className="text-sm font-bold text-foreground transition group-hover:text-accent-foreground">
                                            Access HRMO workspace
                                        </p>
                                        <p className="text-[11px] text-muted-foreground">
                                            Let&apos;s coordinate together
                                        </p>
                                    </div>
                                    <span className="flex size-9 items-center justify-center rounded-full bg-[#69B518] text-white transition-transform group-hover:scale-105">
                                        <ArrowUpRight className="size-4" />
                                    </span>
                                </Link>

                                <a
                                    href="#features"
                                    className="group flex items-center justify-between rounded-2xl border border-border bg-card p-4 shadow-sm transition hover:border-neutral-400"
                                >
                                    <div>
                                        <p className="text-sm font-bold text-foreground">
                                            Portal Tools &amp; Logs
                                        </p>
                                        <p className="text-[11px] text-muted-foreground">
                                            Employee resources
                                        </p>
                                    </div>
                                    <span className="flex size-9 items-center justify-center rounded-full bg-neutral-900 text-white transition-transform group-hover:scale-105">
                                        <ArrowUpRight className="size-4" />
                                    </span>
                                </a>
                            </div>
                        </div>

                        {/* Huge Bold Typography Banner at Bottom */}
                        <div className="overflow-hidden pt-12 text-center select-none">
                            <h2 className="text-[15vw] leading-none font-black tracking-tighter text-foreground uppercase opacity-90 lg:text-[180px]">
                                CLARIN
                            </h2>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}
