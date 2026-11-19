import { Link, router } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

export type DailyTimeRecordData = {
    demo?: boolean;
    name: string;
    month?: string;
    period?: 'full' | 'first' | 'second';
    periodLabel: string;
    officialHours?: string;
    punchTimes?: Record<string, string[]>;
    records: Partial<
        Record<
            number,
            {
                amIn?: string | null;
                amOut?: string;
                pmIn?: string;
                pmOut?: string | null;
                undertimeHours?: number;
                undertimeMinutes?: number;
                remarks?: string | null;
            }
        >
    >;
    previewUrl?: string;
    backUrl: string;
};

const columns = [
    'Day',
    'AM In',
    'AM Out',
    'PM In',
    'PM Out',
    'Under',
    'time',
    'Remarks',
];

export default function DailyTimeRecordPreview({
    demo = false,
    name,
    month,
    period,
    periodLabel,
    officialHours,
    punchTimes,
    records,
    previewUrl,
    backUrl,
    onUpdate,
    busy = false,
    error,
}: DailyTimeRecordData & {
    onUpdate?: (url: string) => void;
    busy?: boolean;
    error?: string;
}) {
    function updatePreview(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        const filters = new FormData(event.currentTarget);
        const selectedMonth = filters.get('month');
        const selectedPeriod = filters.get('period');
        if (
            typeof selectedMonth !== 'string' ||
            typeof selectedPeriod !== 'string'
        ) {
            return;
        }
        const url = new URL(event.currentTarget.action);
        url.searchParams.set('month', selectedMonth);
        url.searchParams.set('period', selectedPeriod);

        if (onUpdate) {
            onUpdate(url.toString());
        } else {
            router.get(url.toString());
        }
    }

    return (
        <div className="space-y-5 print:space-y-0">
            <header className="space-y-4 print:hidden">
                <div className="flex flex-wrap items-center justify-end gap-3">
                    {!onUpdate && (
                        <Button asChild variant="outline" className="mr-auto">
                            <Link href={backUrl}>Back to attendance</Link>
                        </Button>
                    )}
                    <Button
                        type="button"
                        disabled={busy}
                        onClick={() => window.print()}
                    >
                        Print DTR
                    </Button>
                </div>
                {previewUrl && (
                    <form
                        action={previewUrl}
                        method="get"
                        key={`${month}-${period}`}
                        onSubmit={updatePreview}
                        className="flex flex-wrap items-end gap-3"
                    >
                        <label className="space-y-1 text-sm font-medium">
                            <span>Month</span>
                            <Input
                                type="month"
                                name="month"
                                defaultValue={month}
                                required
                            />
                        </label>
                        <label className="space-y-1 text-sm font-medium">
                            <span>Period</span>
                            <select
                                name="period"
                                defaultValue={period}
                                className="block h-9 rounded-md border border-input bg-background px-3"
                            >
                                <option value="full">Full month</option>
                                <option value="first">1â€“15</option>
                                <option value="second">
                                    16â€“End of month
                                </option>
                            </select>
                        </label>
                        <Button type="submit" variant="outline" disabled={busy}>
                            {busy ? 'Updating preview…' : 'Update preview'}
                        </Button>
                        {error && (
                            <p
                                role="alert"
                                className="basis-full text-sm text-destructive"
                            >
                                {error}
                            </p>
                        )}
                    </form>
                )}
                {!demo && (
                    <p className="text-sm text-muted-foreground">
                        Manual entries show the recorded arrival and departure.
                        Imported days with four punches fill the four time slots
                        in order. Review days with other punch counts.
                    </p>
                )}
            </header>

            <div className="overflow-x-auto print:overflow-visible">
                <article className="mx-auto max-w-xl min-w-[29rem] border bg-white px-5 py-7 font-[Arial,sans-serif] text-sm leading-tight text-black sm:px-8 print:max-w-[150mm] print:min-w-0 print:border-0 print:p-0 print:text-[10pt]">
                    <div className="flex justify-between gap-2">
                        {demo && (
                            <p className="font-bold">Demo Â· Fictional data</p>
                        )}
                        <p className="ml-auto">Civil Service Form No. 48</p>
                    </div>
                    <h1 className="my-4 text-center text-xl font-bold">
                        DAILY TIME RECORD
                    </h1>
                    <dl className="mb-4 space-y-1">
                        <div className="flex gap-2">
                            <dt>Name</dt>
                            <dd className="border-b px-1 font-bold uppercase">
                                {name}
                            </dd>
                        </div>
                        <div className="flex gap-2">
                            <dt>For the month of</dt>
                            <dd className="border-b px-1 font-bold">
                                {periodLabel}
                            </dd>
                        </div>
                        <div className="flex gap-2">
                            <dt>Official hours for arrival and departure</dt>
                            <dd className="min-w-24 border-b px-1">
                                {officialHours}
                            </dd>
                        </div>
                    </dl>
                    <table
                        aria-label="Daily Time Record"
                        className="w-full table-fixed border-collapse text-center [&_td]:border [&_td]:border-black [&_td]:px-1 [&_th]:border [&_th]:border-black [&_th]:px-1"
                    >
                        <thead>
                            <tr>
                                {columns.map((column) => (
                                    <th
                                        key={column}
                                        scope="col"
                                        aria-label={
                                            column === 'Under'
                                                ? 'Undertime hours'
                                                : column === 'time'
                                                  ? 'Undertime minutes'
                                                  : undefined
                                        }
                                        className={
                                            column === 'Day'
                                                ? 'w-[7%] py-1'
                                                : column === 'Remarks'
                                                  ? 'w-[20%] py-1'
                                                  : 'py-1'
                                        }
                                    >
                                        {column}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody className="tabular-nums">
                            {Array.from({ length: 31 }, (_, index) => {
                                const day = index + 1;
                                const record = records[day];

                                return (
                                    <tr key={day} className="h-6 print:h-[5mm]">
                                        <th scope="row" className="font-normal">
                                            {day}
                                        </th>
                                        <td>{record?.amIn}</td>
                                        <td>{record?.amOut}</td>
                                        <td>{record?.pmIn}</td>
                                        <td>{record?.pmOut}</td>
                                        <td>{record?.undertimeHours}</td>
                                        <td>{record?.undertimeMinutes}</td>
                                        <td className="break-words">
                                            {record?.remarks}
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                    <footer className="mt-4 space-y-4 print:break-inside-avoid">
                        <p>
                            I certify on my honor that the above is a true and
                            correct report of the hours of work performed,
                            record of which was made daily at the time of
                            arrival and departure.
                        </p>
                        <div className="mt-8 w-3/4 border-t pt-1">
                            Signature of Employee
                        </div>
                        <p>Verified as to the prescribed office hours:</p>
                        <div className="mt-8 w-3/4 border-t pt-1">
                            Municipal Mayor
                        </div>
                    </footer>
                </article>
            </div>
            {punchTimes && Object.keys(punchTimes).length > 0 && (
                <details className="rounded-lg border p-3 text-sm print:hidden">
                    <summary className="cursor-pointer font-medium">
                        Imported punches for this period
                    </summary>
                    <dl className="mt-2 space-y-1">
                        {Object.entries(punchTimes).map(([day, times]) => (
                            <div key={day} className="flex flex-wrap gap-2">
                                <dt className="font-medium">Day {day}</dt>
                                <dd className="tabular-nums">
                                    {times.join(', ')}
                                </dd>
                            </div>
                        ))}
                    </dl>
                </details>
            )}
        </div>
    );
}
