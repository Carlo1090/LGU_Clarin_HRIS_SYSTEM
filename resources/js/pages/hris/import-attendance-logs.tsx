import { Head, Link, useForm } from '@inertiajs/react';
import AttendanceWorkspaceHeader from '@/components/attendance-workspace-header';
import { useRef, type FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type {
    BiometricPunchGroup,
    ImportBatchSummary,
} from '@/types/attendance';

type Page<T> = {
    data: T[];
    current_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

const logsUrl = '/hris/attendance/logs';

function Paging({
    prev_page_url: previous,
    next_page_url: next,
}: Page<unknown>) {
    if (!previous && !next) {
        return null;
    }

    return (
        <div className="flex gap-3 text-sm">
            {previous && (
                <Link className="underline" href={previous}>
                    Previous
                </Link>
            )}
            {next && (
                <Link className="underline" href={next}>
                    Next
                </Link>
            )}
        </div>
    );
}

export default function ImportAttendanceLogs({
    batch,
    groups,
    unassigned,
    month,
    selectedId,
    punches,
}: {
    batch: ImportBatchSummary | null;
    groups: Page<BiometricPunchGroup>;
    unassigned: boolean;
    month: string | null;
    selectedId: number | null;
    punches: Page<{ id: number; punched_at: string }> | null;
}) {
    const inputRef = useRef<HTMLInputElement>(null);
    const form = useForm<{ file: File | null }>({ file: null });
    const monthFilter = month ? { month } : {};

    function upload(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        form.post(logsUrl, {
            onSuccess: () => {
                form.reset();
                if (inputRef.current) {
                    inputRef.current.value = '';
                }
            },
        });
    }

    return (
        <main className="mx-auto w-full max-w-7xl space-y-6 p-4 md:p-8">
            <Head title="Import Attendance Logs" />
            <AttendanceWorkspaceHeader
                active="import"
                month={month}
                canManage
            />
            <div className="flex flex-wrap items-center justify-between gap-3">
                <h2 className="text-xl font-semibold">
                    Import Attendance Logs
                </h2>
                <Button asChild variant="outline">
                    <Link href="/hris/employees">Link IDs on Employees</Link>
                </Button>
            </div>
            <form
                onSubmit={upload}
                className="space-y-4 rounded-xl border bg-card p-5"
            >
                <div className="max-w-xl space-y-2">
                    <label
                        htmlFor="attendance-log"
                        className="text-sm font-medium"
                    >
                        Biometric log file (.dat or .txt)
                    </label>
                    <Input
                        id="attendance-log"
                        ref={inputRef}
                        type="file"
                        accept=".dat,.txt"
                        required
                        disabled={form.processing}
                        onChange={(event) => {
                            form.setData(
                                'file',
                                event.target.files?.[0] ?? null,
                            );
                            form.clearErrors('file');
                        }}
                    />
                    {form.errors.file && (
                        <p role="alert" className="text-sm text-destructive">
                            {form.errors.file}
                        </p>
                    )}
                </div>
                <div className="flex flex-wrap items-center gap-3">
                    <Button
                        type="submit"
                        disabled={!form.data.file || form.processing}
                    >
                        Import log
                    </Button>
                    {form.processing && (
                        <p
                            role="status"
                            className="text-sm text-muted-foreground"
                        >
                            {form.progress && form.progress.percentage !== 100
                                ? `Uploading ${form.progress.percentage ?? 0}%`
                                : 'Processing attendance log…'}
                        </p>
                    )}
                </div>
            </form>

            {batch && (
                <section
                    className="space-y-3 rounded-xl border bg-card p-5"
                    aria-labelledby="import-summary"
                >
                    <h2 id="import-summary" className="text-lg font-semibold">
                        Import summary
                    </h2>
                    <p className="text-sm break-all text-muted-foreground">
                        {batch.file_name}
                    </p>
                    <dl className="grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                        {[
                            ['Punch lines read', batch.total_lines],
                            ['Imported', batch.inserted_count],
                            ['Duplicates', batch.duplicate_count],
                            ['Invalid', batch.invalid_count],
                        ].map(([label, count]) => (
                            <div
                                key={label}
                                className="rounded-lg bg-muted p-3"
                            >
                                <dt>{label}</dt>
                                <dd className="text-xl font-semibold tabular-nums">
                                    {count}
                                </dd>
                            </div>
                        ))}
                    </dl>
                    {batch.clock_offset_seconds !== 0 && (
                        <p className="text-sm">
                            Applied clock offset {batch.clock_offset_seconds}{' '}
                            seconds.
                        </p>
                    )}
                    {batch.invalid_lines.length > 0 && (
                        <details className="text-sm">
                            <summary className="cursor-pointer font-medium">
                                {batch.invalid_count >
                                batch.invalid_lines.length
                                    ? `First ${batch.invalid_lines.length} invalid lines`
                                    : 'Invalid line details'}
                            </summary>
                            <ul className="mt-2 max-h-60 space-y-1 overflow-y-auto">
                                {batch.invalid_lines.map(({ line, reason }) => (
                                    <li key={line}>
                                        Line {line} — {reason}
                                    </li>
                                ))}
                            </ul>
                        </details>
                    )}
                </section>
            )}

            {selectedId !== null && punches && (
                <section
                    className="space-y-3 rounded-xl border bg-card p-5"
                    aria-labelledby="punches-heading"
                >
                    <h2 id="punches-heading" className="text-lg font-semibold">
                        Punches for Biometric ID {selectedId}
                    </h2>
                    {punches.data.length ? (
                        <ol className="grid gap-2 text-sm tabular-nums sm:grid-cols-2 lg:grid-cols-3">
                            {punches.data.map((punch) => (
                                <li key={punch.id}>{punch.punched_at}</li>
                            ))}
                        </ol>
                    ) : (
                        <p className="text-sm text-muted-foreground">
                            No punches found.
                        </p>
                    )}
                    <Paging {...punches} />
                </section>
            )}

            <section
                className="space-y-3 rounded-xl border bg-card p-5"
                aria-labelledby="biometric-groups"
            >
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h2 id="biometric-groups" className="text-lg font-semibold">
                        Imported punches by Biometric ID
                        {month ? ` · ${month}` : ''}
                    </h2>
                    <Button asChild size="sm" variant="outline">
                        <Link
                            href={logsUrl}
                            data={
                                unassigned
                                    ? monthFilter
                                    : { ...monthFilter, unassigned: 1 }
                            }
                        >
                            {unassigned
                                ? 'Show all IDs'
                                : 'Show unassigned IDs'}
                        </Link>
                    </Button>
                </div>
                <div className="overflow-x-auto">
                    <table className="w-full text-left text-sm whitespace-nowrap">
                        <thead className="bg-muted">
                            <tr>
                                {[
                                    'Biometric ID',
                                    'Employee',
                                    'Punches',
                                    'First punch',
                                    'Last punch',
                                ].map((label) => (
                                    <th
                                        key={label}
                                        scope="col"
                                        className="p-3 font-medium"
                                    >
                                        {label}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {groups.data.map((group) => (
                                <tr
                                    key={group.biometric_id}
                                    className="border-b"
                                >
                                    <th scope="row" className="p-3 font-medium">
                                        <Link
                                            className="underline underline-offset-4"
                                            aria-current={
                                                selectedId ===
                                                group.biometric_id
                                                    ? 'true'
                                                    : undefined
                                            }
                                            href={logsUrl}
                                            data={{
                                                ...monthFilter,
                                                ...(unassigned
                                                    ? { unassigned: 1 }
                                                    : {}),
                                                group_page: groups.current_page,
                                                biometric_id:
                                                    group.biometric_id,
                                            }}
                                        >
                                            {group.biometric_id}
                                        </Link>
                                    </th>
                                    <td className="p-3">
                                        {group.employee_number
                                            ? `${group.last_name}, ${group.first_name} (${group.employee_number})`
                                            : 'Unassigned'}
                                    </td>
                                    <td className="p-3 tabular-nums">
                                        {group.punch_count}
                                    </td>
                                    <td className="p-3 tabular-nums">
                                        {group.first_punch}
                                    </td>
                                    <td className="p-3 tabular-nums">
                                        {group.last_punch}
                                    </td>
                                </tr>
                            ))}
                            {!groups.data.length && (
                                <tr>
                                    <td
                                        colSpan={5}
                                        className="p-5 text-center text-muted-foreground"
                                    >
                                        No biometric IDs found.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
                <Paging {...groups} />
            </section>
        </main>
    );
}
