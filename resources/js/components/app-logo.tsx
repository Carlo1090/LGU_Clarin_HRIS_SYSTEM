import { Landmark } from 'lucide-react';

export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-9 items-center justify-center rounded-xl bg-emerald-700 text-white shadow-sm">
                <Landmark aria-hidden="true" className="size-5" />
            </div>
            <div className="ml-1 grid flex-1 text-left">
                <span className="truncate text-sm leading-tight font-semibold">
                    LGU Clarin
                </span>
                <span className="truncate text-[10px] leading-tight text-muted-foreground">
                    Human Resource System
                </span>
            </div>
        </>
    );
}
