import { Head, Link, usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import AOS from 'aos';
import 'aos/dist/aos.css';
import {
    ArrowUpRight,
    Building2,
    CalendarDays,
    ChartNoAxesColumnIncreasing,
    ChartPie,
    CheckCircle2,
    FileText,
    LayoutGrid,
    Lightbulb,
    Play,
    ShieldCheck,
    Sparkles,
    Users,
} from 'lucide-react';
import GradientASCII from '@/components/GradientASCII';
import PublicHeader from '@/components/public-header';
import ScrollReveal from '@/components/ScrollReveal';
import SpotlightCard from '@/components/SpotlightCard';
import TechText from '@/components/TechText';
import { dashboard, login } from '@/routes';
import './welcome.css';

function RevealWords({ children }: { children: string }) {
    return children.split(/(\s+)/).map((word, index) =>
        word.trim() ? (
            <span className="scroll-reveal-word" key={index}>
                <span className="scroll-reveal-piece">{word}</span>
            </span>
        ) : (
            word
        ),
    );
}

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

        let active = true;
        const refreshAnimations = () => {
            if (active) AOS.refresh();
        };
        const frame = requestAnimationFrame(() => AOS.refreshHard());
        void document.fonts.ready.then(refreshAnimations);
        window.addEventListener('load', refreshAnimations);

        return () => {
            active = false;
            cancelAnimationFrame(frame);
            window.removeEventListener('load', refreshAnimations);
        };
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

                            {/* Decorative cards behind the hero image */}
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

                            <div className="relative z-10 flex justify-center pt-6">
                                <img
                                    src="/images/clarin-officials.png"
                                    alt="Three LGU Clarin officials in municipal uniforms"
                                    width={1293}
                                    height={1217}
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

                        <ScrollReveal
                            data-aos="fade-up"
                            containerClassName="landing-reveal landing-reveal--features"
                            textClassName="landing-reveal-text"
                            scrollContainerRef={null}
                            baseRotation={0}
                            baseOpacity={0.2}
                            blurStrength={3}
                            smoothing={0.35}
                            highlightColor="#69B518"
                            wordAnimationEnd="bottom 65%"
                        >
                            Achieve administrative clarity and take control of
                            municipal records with tools designed to simplify,
                            streamline, and personalize your workforce
                            management, all in one secure platform.
                        </ScrollReveal>

                        {/* 4 Feature Cards */}
                        <div className="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                            {/* Card 1 */}
                            <SpotlightCard
                                className="landing-feature"
                                theme="light"
                                style={{ backgroundColor: '#D7F75B' }}
                                spotlightColor="#F2FFD0"
                                intensity={0.95}
                                spotlightSize={240}
                                softness={0.8}
                                borderGlow={0.85}
                                smoothing={0.3}
                            >
                                <div className="relative flex h-full min-h-72 flex-col justify-between gap-2 overflow-hidden rounded-3xl p-6 text-[#142014]">
                                    <div className="relative z-10">
                                        <span className="inline-flex rounded-full bg-white/80 px-3 py-1 text-[10px] font-semibold text-[#142014]">
                                            LGU Clarin
                                        </span>
                                    </div>
                                    <img
                                        src="/images/feature-municipal-portal.png"
                                        alt=""
                                        width={1254}
                                        height={1254}
                                        loading="lazy"
                                        decoding="async"
                                        className="pointer-events-none mx-auto h-28 w-full shrink-0 object-contain"
                                    />
                                    <p className="relative z-10 text-xs font-medium text-[#142014]/80">
                                        Centralized municipal employee portal.
                                    </p>
                                </div>
                            </SpotlightCard>

                            {/* Card 2 */}
                            <SpotlightCard
                                className="landing-feature"
                                theme="light"
                                style={{ backgroundColor: '#9CE82A' }}
                                spotlightColor="#F2FFD0"
                                intensity={0.95}
                                spotlightSize={240}
                                softness={0.8}
                                borderGlow={0.85}
                                smoothing={0.3}
                            >
                                <div className="relative flex h-full min-h-72 flex-col justify-between gap-2 overflow-hidden rounded-3xl p-6 text-[#142014]">
                                    <h3 className="relative z-10 text-sm leading-snug font-bold">
                                        Automated 201 Files &amp; Alerts
                                    </h3>
                                    <img
                                        src="/images/feature-employee-files.png"
                                        alt=""
                                        width={1254}
                                        height={1254}
                                        loading="lazy"
                                        decoding="async"
                                        className="pointer-events-none mx-auto h-28 w-full shrink-0 object-contain"
                                    />
                                    <p className="relative z-10 text-xs leading-relaxed text-[#142014]/85">
                                        Maintain updated employee service
                                        records and get real-time alerts on step
                                        increments and documents.
                                    </p>
                                </div>
                            </SpotlightCard>

                            {/* Card 3 */}
                            <SpotlightCard
                                className="landing-feature"
                                theme="light"
                                style={{ backgroundColor: '#B4F232' }}
                                spotlightColor="#F2FFD0"
                                intensity={0.95}
                                spotlightSize={240}
                                softness={0.8}
                                borderGlow={0.85}
                                smoothing={0.3}
                            >
                                <div className="relative flex h-full min-h-72 flex-col justify-between gap-2 overflow-hidden rounded-3xl p-6 text-[#142014]">
                                    <h3 className="relative z-10 text-sm leading-snug font-bold">
                                        Unified Department Dashboard
                                    </h3>
                                    <img
                                        src="/images/feature-department-dashboard.png"
                                        alt=""
                                        width={1254}
                                        height={1254}
                                        loading="lazy"
                                        decoding="async"
                                        className="pointer-events-none mx-auto h-28 w-full shrink-0 object-contain"
                                    />
                                    <p className="relative z-10 text-xs leading-relaxed text-[#142014]/85">
                                        View all municipal offices, leave
                                        credits, and personnel statuses in one
                                        intuitive interface.
                                    </p>
                                </div>
                            </SpotlightCard>

                            {/* Card 4 */}
                            <SpotlightCard
                                className="landing-feature"
                                theme="light"
                                style={{ backgroundColor: '#DCFA70' }}
                                spotlightColor="#F2FFD0"
                                intensity={0.95}
                                spotlightSize={240}
                                softness={0.8}
                                borderGlow={0.85}
                                smoothing={0.3}
                            >
                                <div className="relative flex h-full min-h-72 flex-col justify-between gap-2 overflow-hidden rounded-3xl p-6 text-[#142014]">
                                    <h3 className="relative z-10 text-sm leading-snug font-bold">
                                        Role-Based HR Insights
                                    </h3>
                                    <img
                                        src="/images/feature-role-insights.png"
                                        alt=""
                                        width={1254}
                                        height={1254}
                                        loading="lazy"
                                        decoding="async"
                                        className="pointer-events-none mx-auto h-28 w-full shrink-0 object-contain"
                                    />
                                    <p className="relative z-10 text-xs leading-relaxed text-[#142014]/85">
                                        Access role-specific HR tools and
                                        reports tailored to employees,
                                        department heads, and HRMO admins.
                                    </p>
                                </div>
                            </SpotlightCard>
                        </div>
                    </section>

                    {/* How It Works: HR decisions and overlapping workspace previews */}
                    <section
                        id="how-it-works"
                        aria-labelledby="how-it-works-title"
                        className="relative overflow-hidden bg-[#A6EB20] py-20 text-[#142014] sm:py-24"
                    >
                        <div className="mx-auto grid max-w-6xl items-center gap-14 px-6 lg:grid-cols-2 lg:gap-16 lg:px-10">
                            {/* Headline, benefits, and portal access */}
                            <div>
                                <p
                                    data-aos="fade-up"
                                    className="mb-5 text-[11px] font-semibold tracking-[0.18em] text-[#1C4722] uppercase"
                                >
                                    How it works
                                </p>
                                <ScrollReveal
                                    id="how-it-works-title"
                                    containerClassName="landing-reveal landing-reveal--how"
                                    textClassName="landing-reveal-text"
                                    scrollContainerRef={null}
                                    baseRotation={0}
                                    baseOpacity={0.25}
                                    blurStrength={3}
                                    effect="rise"
                                    mode="enter"
                                    duration={0.7}
                                    stagger={0.065}
                                >
                                    Smarter HR Records Better Decisions
                                </ScrollReveal>

                                <div
                                    data-aos="fade-up"
                                    data-aos-delay="100"
                                    className="mt-6 space-y-5 text-xs leading-relaxed text-[#142014]/80 sm:text-sm"
                                >
                                    <div className="flex items-start gap-3">
                                        <span className="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-white/40 text-[#1C4722]">
                                            <CheckCircle2
                                                aria-hidden="true"
                                                className="size-3.5"
                                            />
                                        </span>
                                        <p>
                                            <strong className="font-semibold text-[#142014]">
                                                Get clarity on daily DTR logs,
                                            </strong>{' '}
                                            track leave credits, and monitor
                                            service records in one workspace.
                                        </p>
                                    </div>
                                    <div className="flex items-start gap-3">
                                        <span className="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-white/40 text-[#1C4722]">
                                            <CheckCircle2
                                                aria-hidden="true"
                                                className="size-3.5"
                                            />
                                        </span>
                                        <p>
                                            <strong className="font-semibold text-[#142014]">
                                                Extra administrative power
                                            </strong>{' '}
                                            with role-based HRMO tools for
                                            employees, department heads, and
                                            municipal administrators.
                                        </p>
                                    </div>
                                </div>

                                <Link
                                    href={auth.user ? dashboard() : login()}
                                    data-aos="fade-up"
                                    data-aos-delay="200"
                                    className="group mt-8 inline-flex items-center gap-2 rounded-full bg-[#1C4722] px-6 py-3 text-xs font-semibold text-white shadow-sm transition hover:bg-[#15381B] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#1C4722]"
                                >
                                    Explore More
                                    <ArrowUpRight
                                        aria-hidden="true"
                                        className="size-4 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5 motion-reduce:transform-none"
                                    />
                                </Link>
                            </div>

                            {/* Overlapping cards inspired by the supplied layout */}
                            <div className="relative mx-auto w-full max-w-lg">
                                <div
                                    aria-hidden="true"
                                    className="pointer-events-none absolute inset-6 rounded-full bg-white/30 blur-3xl"
                                />
                                <div className="hr-preview-stage relative isolate min-h-[470px] sm:min-h-[500px]">
                                    {/* Back card: illustrative attendance overview */}
                                    <div
                                        className="hr-overview-card absolute top-0 right-0 z-0 w-[min(18rem,90%)] sm:top-2 sm:w-80"
                                        data-aos="fade-up"
                                        data-aos-delay="150"
                                    >
                                        <SpotlightCard
                                            className="landing-hr-spotlight landing-hr-spotlight--back"
                                            theme="light"
                                            style={{
                                                backgroundColor: 'var(--card)',
                                                color: 'var(--card-foreground)',
                                            }}
                                            spotlightColor="#A6EB20"
                                            intensity={0.9}
                                            spotlightSize={240}
                                            softness={0.8}
                                            borderGlow={0.85}
                                            smoothing={0.3}
                                        >
                                            <div className="flex items-center justify-between gap-3 border-b border-border/60 pb-3">
                                                <div>
                                                    <p className="text-[11px] font-medium text-muted-foreground">
                                                        Attendance &amp; DTR
                                                    </p>
                                                    <p className="mt-1 text-xl font-bold tracking-tight">
                                                        Your workweek
                                                    </p>
                                                </div>
                                                <span className="shrink-0 rounded-full border border-border bg-muted px-2.5 py-1 text-[10px] font-semibold">
                                                    Weekly view
                                                </span>
                                            </div>
                                            <div className="mt-4 flex items-center justify-between gap-2 text-[10px] text-muted-foreground">
                                                <span className="rounded-md bg-accent px-2 py-0.5 font-semibold text-accent-foreground">
                                                    DTR logs
                                                </span>
                                                <span>
                                                    Mon &middot; Tue &middot;
                                                    Wed &middot; Thu &middot;
                                                    Fri
                                                </span>
                                            </div>
                                            <div
                                                className="mt-3 h-20 w-full"
                                                aria-hidden="true"
                                            >
                                                <svg
                                                    viewBox="0 0 240 80"
                                                    className="h-full w-full"
                                                    preserveAspectRatio="none"
                                                >
                                                    <defs>
                                                        <linearGradient
                                                            id="hr-attendance-fill"
                                                            x1="0"
                                                            y1="0"
                                                            x2="0"
                                                            y2="1"
                                                        >
                                                            <stop
                                                                offset="0%"
                                                                stopColor="#69B518"
                                                                stopOpacity="0.22"
                                                            />
                                                            <stop
                                                                offset="100%"
                                                                stopColor="#69B518"
                                                                stopOpacity="0"
                                                            />
                                                        </linearGradient>
                                                    </defs>
                                                    <path
                                                        d="M 0 62 Q 45 12 90 42 T 165 28 T 240 12 L 240 80 L 0 80 Z"
                                                        fill="url(#hr-attendance-fill)"
                                                    />
                                                    <path
                                                        d="M 0 62 Q 45 12 90 42 T 165 28 T 240 12"
                                                        fill="none"
                                                        stroke="#69B518"
                                                        strokeWidth="2.5"
                                                        strokeLinecap="round"
                                                    />
                                                </svg>
                                            </div>
                                        </SpotlightCard>
                                    </div>

                                    {/* Transparent portrait between the floating cards */}
                                    <div
                                        className="hr-preview-hero"
                                        aria-hidden="true"
                                        data-aos="fade-up"
                                        data-aos-delay="200"
                                    >
                                        <img
                                            src="/images/how-it-works-hero.png"
                                            alt=""
                                            width={1000}
                                            height={1000}
                                            loading="lazy"
                                            decoding="async"
                                            draggable={false}
                                        />
                                    </div>

                                    {/* Front card: links to employee records and leave tools */}
                                    <div
                                        className="hr-records-card absolute bottom-0 left-0 z-10 w-[min(18rem,90%)] sm:bottom-2 sm:w-80"
                                        data-aos="fade-up"
                                        data-aos-delay="250"
                                    >
                                        <SpotlightCard
                                            className="landing-hr-spotlight landing-hr-spotlight--front"
                                            theme="light"
                                            style={{
                                                backgroundColor: 'var(--card)',
                                                color: 'var(--card-foreground)',
                                            }}
                                            spotlightColor="#A6EB20"
                                            intensity={0.9}
                                            spotlightSize={240}
                                            softness={0.8}
                                            borderGlow={0.85}
                                            smoothing={0.3}
                                        >
                                            <div className="flex items-center justify-between gap-3 pb-4">
                                                <p className="text-xs font-bold">
                                                    Your HR workspace
                                                </p>
                                                <Link
                                                    href={
                                                        auth.user
                                                            ? dashboard()
                                                            : login()
                                                    }
                                                    className="rounded text-[11px] font-medium text-muted-foreground transition hover:text-foreground focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-ring"
                                                >
                                                    View all
                                                </Link>
                                            </div>
                                            <div className="space-y-3">
                                                <Link
                                                    href={
                                                        auth.user
                                                            ? dashboard()
                                                            : login()
                                                    }
                                                    className="group flex items-center justify-between gap-3 rounded-2xl border border-border/60 bg-muted/40 p-3 transition hover:border-[#69B518]/60 hover:bg-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                                                >
                                                    <div>
                                                        <p className="text-sm font-bold tracking-tight">
                                                            201 File
                                                        </p>
                                                        <p className="mt-1 text-[10px] text-muted-foreground">
                                                            Service records
                                                            &amp; documents
                                                        </p>
                                                    </div>
                                                    <span className="flex size-9 shrink-0 items-center justify-center rounded-xl bg-accent text-accent-foreground">
                                                        <FileText
                                                            aria-hidden="true"
                                                            className="size-4"
                                                        />
                                                    </span>
                                                </Link>
                                                <Link
                                                    href={
                                                        auth.user
                                                            ? dashboard()
                                                            : login()
                                                    }
                                                    className="group flex items-center justify-between gap-3 rounded-2xl border border-border/60 bg-muted/40 p-3 transition hover:border-[#69B518]/60 hover:bg-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                                                >
                                                    <div>
                                                        <p className="text-sm font-bold tracking-tight">
                                                            Leave credits
                                                        </p>
                                                        <p className="mt-1 text-[10px] text-muted-foreground">
                                                            Balances &amp;
                                                            applications
                                                        </p>
                                                    </div>
                                                    <span className="flex size-9 shrink-0 items-center justify-center rounded-xl bg-accent text-accent-foreground">
                                                        <CalendarDays
                                                            aria-hidden="true"
                                                            className="size-4"
                                                        />
                                                    </span>
                                                </Link>
                                            </div>
                                        </SpotlightCard>
                                    </div>
                                </div>
                                <p className="mt-5 text-center text-[10px] font-medium tracking-wide text-[#1C4722]/80">
                                    Illustrative HR workspace preview
                                </p>
                            </div>
                        </div>
                    </section>

                    <section
                        id="users"
                        aria-labelledby="users-heading"
                        className="mx-auto max-w-6xl px-6 py-16 lg:px-10"
                    >
                        <h2
                            id="users-heading"
                            className="text-3xl font-semibold tracking-tight"
                        >
                            Who uses the HRIS?
                        </h2>
                        <ul className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            {[
                                [
                                    'Municipal employees',
                                    'Access their employee records and submit requests.',
                                ],
                                [
                                    'Department heads',
                                    'Review requests and coordinate with HR for their teams.',
                                ],
                                [
                                    'HRMO staff',
                                    'Manage personnel records and process employee requests.',
                                ],
                                [
                                    'MIS administrators',
                                    'Manage system access and help users with technical issues.',
                                ],
                            ].map(([role, description]) => (
                                <li
                                    key={role}
                                    className="rounded-2xl border border-border bg-card p-5"
                                >
                                    <h3 className="font-semibold">{role}</h3>
                                    <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
                                        {description}
                                    </p>
                                </li>
                            ))}
                        </ul>
                    </section>

                    {/* About Us: municipal workforce bento cards */}
                    <section
                        id="about"
                        aria-labelledby="about-title"
                        className="mx-auto max-w-6xl px-6 py-20 lg:px-10"
                    >
                        <div className="mx-auto max-w-3xl text-center">
                            <p
                                data-aos="fade-up"
                                className="inline-flex items-center gap-2 text-[11px] font-semibold tracking-[0.2em] text-muted-foreground uppercase"
                            >
                                <span
                                    aria-hidden="true"
                                    className="size-1 rounded-full bg-foreground"
                                />
                                About us
                            </p>
                            <ScrollReveal
                                data-aos="fade-up"
                                containerClassName="landing-reveal landing-reveal--features"
                                textClassName="landing-reveal-text"
                                scrollContainerRef={null}
                                baseRotation={0}
                                baseOpacity={0.2}
                                blurStrength={3}
                                smoothing={0.35}
                                highlightColor="#69B518"
                                wordAnimationEnd="bottom 65%"
                            >
                                <RevealWords>Supporting our people</RevealWords>
                                <br className="hidden sm:inline" />{' '}
                                <RevealWords>with</RevealWords>{' '}
                                <span
                                    aria-hidden="true"
                                    className="inline-flex size-9 -translate-y-0.5 items-center justify-center rounded-full bg-[#38BDF8] align-middle text-white sm:size-10"
                                >
                                    <ChartPie className="size-5" />
                                </span>{' '}
                                <RevealWords>smarter HR</RevealWords>
                                <br className="hidden sm:inline" />
                                <span className="text-muted-foreground">
                                    {' '}
                                    <RevealWords>and</RevealWords>{' '}
                                    <span
                                        aria-hidden="true"
                                        className="inline-flex size-9 -translate-y-0.5 items-center justify-center rounded-full bg-[#C7FA50] align-middle text-[#142014] sm:size-10"
                                    >
                                        <Lightbulb className="size-5" />
                                    </span>{' '}
                                    <RevealWords>
                                        better public service.
                                    </RevealWords>
                                </span>
                            </ScrollReveal>
                        </div>

                        <div className="about-bento mt-12 grid grid-cols-1 gap-5 md:grid-cols-3">
                            {/* Portrait with a floating employee records card */}
                            <article
                                data-aos="fade-up"
                                className="about-bento-portrait relative flex min-h-[380px] flex-col justify-between overflow-hidden rounded-[1.75rem] bg-[#1C4722] p-4 shadow-xs"
                            >
                                <div
                                    aria-hidden="true"
                                    className="absolute inset-0 bg-gradient-to-b from-[#89D91B] via-[#69B518] to-[#1C4722]"
                                />
                                <img
                                    src="/images/black_hero.png"
                                    alt=""
                                    width={403}
                                    height={612}
                                    loading="lazy"
                                    decoding="async"
                                    className="absolute inset-0 size-full object-cover object-top"
                                />
                                <div
                                    aria-hidden="true"
                                    className="pointer-events-none absolute inset-0 bg-gradient-to-b from-black/20 via-transparent to-[#1C4722]/40"
                                />
                                <div className="relative z-10 flex items-center justify-between gap-3 px-2 pt-1">
                                    <span className="text-sm font-extrabold tracking-tight text-white">
                                        LGU CLARIN
                                    </span>
                                    <span
                                        aria-hidden="true"
                                        className="flex size-9 items-center justify-center rounded-xl bg-white text-[#142014] shadow-sm"
                                    >
                                        <ChartNoAxesColumnIncreasing className="size-4" />
                                    </span>
                                </div>
                                <div className="relative z-10 mt-auto rounded-2xl bg-white p-6 shadow-lg">
                                    <h3 className="text-4xl font-medium tracking-tight text-[#142014]">
                                        201 files
                                    </h3>
                                    <p className="mt-2.5 text-xs leading-relaxed text-neutral-600">
                                        Employee records, service history, and
                                        supporting documents in one place.
                                    </p>
                                </div>
                            </article>

                            {/* People-first mission and connected municipal offices */}
                            <article
                                data-aos="fade-up"
                                data-aos-delay="100"
                                className="about-bento-mission flex min-h-[380px] flex-col justify-between rounded-[1.75rem] bg-muted p-7"
                            >
                                <div>
                                    <p className="text-xs font-medium text-muted-foreground">
                                        Built for our public servants
                                    </p>
                                    <h3 className="mt-2 text-4xl font-medium tracking-tight text-foreground lg:text-[42px]">
                                        People first
                                    </h3>
                                </div>
                                <div className="mt-10">
                                    <div
                                        className="flex -space-x-2.5"
                                        aria-hidden="true"
                                    >
                                        <span className="flex size-10 items-center justify-center rounded-full border-2 border-muted bg-[#DDF5B4] text-[#1C4722]">
                                            <Users className="size-4" />
                                        </span>
                                        <span className="flex size-10 items-center justify-center rounded-full border-2 border-muted bg-[#C6E9FA] text-[#1C4722]">
                                            <Building2 className="size-4" />
                                        </span>
                                        <span className="flex size-10 items-center justify-center rounded-full border-2 border-muted bg-[#1C4722] text-[#A6EB20]">
                                            <ShieldCheck className="size-4" />
                                        </span>
                                        <span className="flex size-10 items-center justify-center overflow-hidden rounded-full border-2 border-muted bg-white p-1">
                                            <img
                                                src="/images/clarin_logo.png"
                                                alt=""
                                                width={32}
                                                height={32}
                                                loading="lazy"
                                                className="size-full object-contain"
                                            />
                                        </span>
                                    </div>
                                    <p className="mt-4 text-xs leading-relaxed font-medium text-muted-foreground">
                                        Supporting the people who serve our
                                        community, with clearer records and
                                        simpler daily HR work across municipal
                                        offices.
                                    </p>
                                </div>
                            </article>

                            {/* Lime platform card and charcoal municipal badge */}
                            <div
                                data-aos="fade-up"
                                data-aos-delay="200"
                                className="about-bento-platform flex min-h-[380px] flex-col gap-4"
                            >
                                <article className="flex flex-1 flex-col justify-between rounded-[1.75rem] bg-[#A6EB20] p-7 text-[#142014]">
                                    <div>
                                        <p className="text-xs font-medium text-[#142014]/80">
                                            Connected workforce tools
                                        </p>
                                        <h3 className="mt-2 text-4xl font-medium tracking-tight lg:text-[42px]">
                                            One portal
                                        </h3>
                                    </div>
                                    <p className="mt-8 text-xs leading-relaxed font-medium text-[#142014]/80">
                                        Records, attendance, leave, and HR
                                        administration in a shared municipal
                                        workspace.
                                    </p>
                                </article>
                                <div className="flex items-center justify-between gap-3 rounded-[1.5rem] bg-[#111111] px-7 py-6 text-white">
                                    <span className="text-xs font-medium text-neutral-400">
                                        Our municipality
                                    </span>
                                    <span className="text-3xl font-medium tracking-tight">
                                        Clarin
                                    </span>
                                </div>
                            </div>
                        </div>

                        <p
                            id="contact"
                            className="mt-8 rounded-2xl border border-border bg-muted/50 px-6 py-5 text-xs leading-relaxed text-muted-foreground sm:text-sm"
                        >
                            Need help with your account or role permissions?
                            Contact the{' '}
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

                    {/* Employee showcase on a white background */}
                    <section
                        id="ai-employee-showcase"
                        aria-labelledby="employee-showcase-title"
                        className="employee-showcase bg-white py-12 text-[#142014]"
                    >
                        <div className="mx-auto max-w-6xl px-6 lg:px-10">
                            <div className="relative overflow-hidden rounded-[2.5rem] border border-[#E4E9DF] bg-white px-6 pt-8 pb-0 text-[#142014] shadow-xl shadow-[#142014]/5 sm:px-12 sm:pt-10">
                                {/* Subtle Top & Bottom Radial Ambient Glow */}
                                <div
                                    aria-hidden="true"
                                    className="pointer-events-none absolute top-1/2 left-1/2 size-[520px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-[#A6EB20]/10 blur-3xl"
                                />

                                {/* Centered Headline with Highlighted Pill Word */}
                                <div className="relative z-10 mx-auto mt-12 max-w-2xl text-center">
                                    <ScrollReveal
                                        data-aos="fade-up"
                                        containerClassName="landing-reveal landing-reveal--features"
                                        textClassName="landing-reveal-text"
                                        scrollContainerRef={null}
                                        baseRotation={0}
                                        baseOpacity={0.2}
                                        blurStrength={3}
                                        smoothing={0.35}
                                        highlightColor="#69B518"
                                        wordAnimationEnd="bottom 65%"
                                    >
                                        <RevealWords>
                                            A municipal portal with memory,
                                        </RevealWords>{' '}
                                        <span className="inline-block rounded-full border border-[#69B518]/25 bg-[#A6EB20]/25 px-4 py-0.5 text-[#3C6A0D]">
                                            <RevealWords>
                                                accountability
                                            </RevealWords>
                                        </span>{' '}
                                        <RevealWords>&amp; clarity</RevealWords>
                                    </ScrollReveal>
                                </div>

                                {/* Centerpiece Portrait + 3 Overlapping Floating Cards */}
                                <div className="employee-showcase-stage">
                                    {/* Center Dark Gray Circle Backdrop Behind Person */}
                                    <div
                                        aria-hidden="true"
                                        className="employee-showcase-backdrop"
                                    />

                                    {/* Center Person Image */}
                                    <div
                                        className="employee-showcase-portrait"
                                        data-aos="fade-up"
                                        data-aos-delay="100"
                                    >
                                        <img
                                            src="/images/how-it-works-hero.png"
                                            alt=""
                                            loading="lazy"
                                            decoding="async"
                                            className="h-full w-auto max-w-full object-contain object-bottom drop-shadow-2xl"
                                        />
                                    </div>

                                    {/* 1. Left Floating White Card ("Learns from documents...") */}
                                    <div
                                        className="employee-showcase-card employee-showcase-card--records"
                                        data-aos="fade-up"
                                        data-aos-delay="150"
                                    >
                                        <SpotlightCard
                                            className="employee-showcase-spotlight"
                                            theme="light"
                                            style={{
                                                backgroundColor: '#FFFFFF',
                                                color: '#142014',
                                            }}
                                            spotlightColor="#A6EB20"
                                            intensity={0.95}
                                            spotlightSize={240}
                                            softness={0.8}
                                            borderGlow={0.85}
                                            smoothing={0.3}
                                        >
                                            <div className="flex items-center justify-between">
                                                <span className="flex size-7 items-center justify-center rounded-full bg-[#142014] text-xs font-bold text-white shadow-md shadow-[#A6EB20]/40">
                                                    <CheckCircle2
                                                        aria-hidden="true"
                                                        className="size-4"
                                                    />
                                                </span>
                                                {/* Overlapping Mini Avatar / Icon Stack */}
                                                <div className="flex -space-x-2">
                                                    <span className="flex size-6 items-center justify-center rounded-full border-2 border-white bg-rose-500 text-[9px] font-bold text-white">
                                                        201
                                                    </span>
                                                    <span className="flex size-6 items-center justify-center rounded-full border-2 border-white bg-amber-500 text-[9px] font-bold text-white">
                                                        DTR
                                                    </span>
                                                    <span className="flex size-6 items-center justify-center rounded-full border-2 border-white bg-[#A6EB20] text-[9px] font-bold text-[#142014]">
                                                        HR
                                                    </span>
                                                </div>
                                            </div>

                                            <p className="mt-5 text-xs leading-relaxed font-medium text-neutral-700">
                                                Organizes <br />
                                                <strong className="font-semibold text-neutral-950">
                                                    201 files, SOPs
                                                </strong>{' '}
                                                and service records
                                            </p>
                                        </SpotlightCard>
                                    </div>

                                    {/* 2. Top-Right Floating White Card ("Connects and acts...") */}
                                    <div
                                        className="employee-showcase-card employee-showcase-card--connect"
                                        data-aos="fade-up"
                                        data-aos-delay="200"
                                    >
                                        <SpotlightCard
                                            className="employee-showcase-spotlight"
                                            theme="light"
                                            style={{
                                                backgroundColor: '#FFFFFF',
                                                backgroundImage:
                                                    'linear-gradient(135deg, #FFFFFF 0%, #FFFFFF 50%, #F2FFD9 100%)',
                                                color: '#142014',
                                            }}
                                            spotlightColor="#A6EB20"
                                            intensity={0.95}
                                            spotlightSize={240}
                                            softness={0.8}
                                            borderGlow={0.85}
                                            smoothing={0.3}
                                        >
                                            <div className="flex flex-wrap items-center gap-1.5">
                                                <span className="rounded-full bg-[#142014] px-3 py-1 text-[11px] font-semibold text-white">
                                                    Connects
                                                </span>
                                                <span className="flex size-6 items-center justify-center rounded-full border border-neutral-200 bg-white text-[10px] font-bold shadow-2xs">
                                                    <Sparkles
                                                        aria-hidden="true"
                                                        className="size-3"
                                                    />
                                                </span>
                                                <span className="rounded-full border border-neutral-200 bg-white px-2.5 py-1 text-[10px] font-medium text-neutral-600 shadow-2xs">
                                                    HRMO Sync
                                                </span>
                                                <span className="rounded-full bg-[#142014] px-3 py-1 text-[11px] font-semibold text-white">
                                                    and acts
                                                </span>
                                            </div>

                                            <p className="mt-4 text-xs leading-relaxed font-medium text-neutral-600">
                                                across municipal <br />
                                                offices &amp; divisions
                                            </p>
                                        </SpotlightCard>
                                    </div>

                                    {/* 3. Bottom-Right Floating Lime Green Card ("Auditable & secure by design") */}
                                    <div
                                        className="employee-showcase-card employee-showcase-card--secure"
                                        data-aos="fade-up"
                                        data-aos-delay="250"
                                    >
                                        <SpotlightCard
                                            className="employee-showcase-spotlight"
                                            theme="light"
                                            style={{
                                                backgroundColor: '#B8F53B',
                                                color: '#142014',
                                            }}
                                            spotlightColor="#F2FFD0"
                                            intensity={0.95}
                                            spotlightSize={240}
                                            softness={0.8}
                                            borderGlow={0.85}
                                            smoothing={0.3}
                                        >
                                            <div className="flex items-start justify-between gap-2">
                                                <p className="text-xs leading-snug font-bold text-[#142014]">
                                                    Auditable <br />
                                                    &amp; secure <br />
                                                    by design
                                                </p>
                                                <span className="flex size-6 shrink-0 items-center justify-center rounded-full bg-white text-xs font-bold text-[#142014] shadow-xs">
                                                    <CheckCircle2
                                                        aria-hidden="true"
                                                        className="size-4"
                                                    />
                                                </span>
                                            </div>
                                        </SpotlightCard>
                                    </div>
                                </div>
                            </div>
                        </div>
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

                        {/* Animated municipal wordmark */}
                        <div className="relative isolate -mx-6 mt-12 -mb-12 overflow-hidden bg-white sm:-mx-10 lg:-mx-14">
                            <div
                                aria-hidden="true"
                                className="pointer-events-none absolute inset-0"
                            >
                                <GradientASCII
                                    colors={[
                                        '#69B518',
                                        '#A6EB20',
                                        '#D9FF80',
                                        '#FFFFFF',
                                    ]}
                                    shape="pinch"
                                    cellSize={12}
                                    density={0.85}
                                    glyphOpacity={0.45}
                                    gradientOpacity={0.7}
                                    softness={0.8}
                                    glow={0.1}
                                    grain={0.025}
                                    speed={0.45}
                                    lightMode
                                    mouseInteraction={false}
                                    clickEffect="none"
                                    intro={false}
                                    dpr={1.5}
                                    style={{ height: '100%' }}
                                />
                            </div>
                            <h2 className="sr-only">CLARIN</h2>
                            <div className="relative h-[clamp(10rem,24vw,18rem)] text-[#142014]">
                                <div aria-hidden="true" className="h-full">
                                    <TechText
                                        text="CLARIN"
                                        fontWeight={900}
                                        fontSize={180}
                                        letterSpacing={-0.06}
                                        color="#142014"
                                        accentColor="#1C4722"
                                        intro="assemble"
                                        introDuration={1.6}
                                        sweep={false}
                                        draggable={false}
                                        clickKick={false}
                                        labels={false}
                                        style={{ height: '100%' }}
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}
