export default function AppLogo() {
    return (
        <>
            <div className="app-logo-mark flex aspect-square size-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-white sm:size-10">
                <img
                    src="/images/clarin_logo.png"
                    alt=""
                    width={40}
                    height={40}
                    className="size-full object-contain"
                />
            </div>
            <div className="app-logo-copy grid min-w-0 text-left">
                <span className="truncate text-sm leading-tight font-semibold text-foreground">
                    LGU Clarin
                </span>
                <span className="hidden truncate text-[10px] leading-tight font-medium text-muted-foreground sm:block">
                    Human Resource System
                </span>
            </div>
        </>
    );
}
