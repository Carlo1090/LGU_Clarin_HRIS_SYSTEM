import { useTableSearch } from '@/components/table-search';
import PersonAvatar from '@/components/person-avatar';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AttendanceWorkspaceHeader from '@/components/attendance-workspace-header';
import { useEffect, useRef } from 'react';
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
    assigned,
    month,
    selectedId,
    punches,
}: {
    batch: ImportBatchSummary | null;
    groups: Page<BiometricPunchGroup>;
    unassigned: boolean;
    assigned: boolean;
    month: string | null;
    selectedId: number | null;
    punches: Page<{
        id: number;
        punched_at: string;
        device_id: number | null;
        punch_state: number | null;
        verify_mode: number | null;
        work_code: number | null;
        derived_slot: string | null;
        is_duplicate: boolean;
        flags: Record<string, boolean | string>;
    }> | null;
}) {
    useEffect(() => {
        if (!batch || batch.status !== 'processing') {
            return;
        }
        const interval = window.setInterval(() => {
            router.reload({ only: ['batch', 'groups'] });
        }, 2500);
        return () => window.clearInterval(interval);
    }, [batch?.id, batch?.status]);

    const search = useTableSearch(
        groups.data,
        ['Biometric ID', 'Employee', 'Punches', 'First punch', 'Last punch'],
        (g) => [
            g.biometric_id,
            g.employee_number
                ? [g.last_name, g.first_name, g.employee_number].join(' ')
                : 'Unassigned',
            g.punch_count,
            g.first_punch,
            g.last_punch,
        ],
    );
    const inputRef = useRef<HTMLInputElement>(null);
    const form = useForm<{ file: File | null }>({
        file: null,
    });
    const monthFilter = month ? { month } : {};

    function importSelectedFile(file: File) {
        form.transform(() => ({ file, confirm: true }));
        form.post(logsUrl, {
            onSuccess: () => {
                form.reset();
                if (inputRef.current) {
                    inputRef.current.value = '';
                }
            },
        });
    }

    function deleteImport() {
        if (!batch) return;
        if (!window.confirm(`Delete imported logs from "${batch.file_name}"?`)) return;

        router.delete(`/hris/attendance/logs/${batch.id}`, {
            preserveScroll: true,
        });
    }

    return (
        <main className="workspace-page max-w-7xl">
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
            <div className="workspace-panel space-y-4 p-5">
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
                            const file = event.target.files?.[0];
                            form.clearErrors('file');
                            if (file) {
                                importSelectedFile(file);
                            }
                        }}
                    />
                    {form.errors.file && (
                        <p role="alert" className="text-sm text-destructive">
                            {form.errors.file}
                        </p>
                    )}
                </div>
                <div className="flex flex-wrap items-center gap-3">
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
            </div>

            {batch && (
                <section
                    className="workspace-panel space-y-3 p-5"
                    aria-labelledby="import-summary"
                >
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <h2
                            id="import-summary"
                            className="text-lg font-semibold"
                        >
                            Import summary
                        </h2>
                        <Button
                            type="button"
                            variant="destructive"
                            size="sm"
                            onClick={deleteImport}
                        >
                            Delete imported logs
                        </Button>
                    </div>
                    <p className="text-sm break-all text-muted-foreground">
                        {batch.file_name}
                    </p>
                    {batch.status === 'processing' && (
                        <p className="text-sm text-blue-700 dark:text-blue-300" role="status">
                            Import is processing in the background.
                        </p>
                    )}
                    {batch.status === 'failed' && (
                        <p className="text-sm text-destructive" role="alert">
                            Import failed{batch.error_message ? `: ${batch.error_message}` : '.'}
                        </p>
                    )}
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
                    className="workspace-panel space-y-3 p-5"
                    aria-labelledby="punches-heading"
                >
                    <h2 id="punches-heading" className="text-lg font-semibold">
                        Punches for Biometric ID {selectedId}
                    </h2>
                    {punches.data.length ? (
                        <ol className="grid gap-2 text-sm tabular-nums sm:grid-cols-2 lg:grid-cols-3">
                            {punches.data.map((punch) => (
                                <li key={punch.id}>
                                    {punch.punched_at} · state{' '}
                                    {punch.punch_state ?? 'unknown'} · verify{' '}
                                    {punch.verify_mode === 1
                                        ? 'fingerprint'
                                        : punch.verify_mode === 0
                                          ? 'PIN'
                                          : (punch.verify_mode ??
                                            'unknown')}{' '}
                                    · device {punch.device_id ?? '—'}
                                    {punch.is_duplicate ? ' · duplicate' : ''}
                                    {Object.keys(punch.flags).length > 0 && (
                                        <span className="block">
                                            Flags:{' '}
                                            {Object.entries(punch.flags)
                                                .map(([name, value]) =>
                                                    typeof value === 'boolean'
                                                        ? name
                                                        : `${name}: ${value}`,
                                                )
                                                .join(', ')}
                                        </span>
                                    )}
                                </li>
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
                className="workspace-panel space-y-3 p-5"
                aria-labelledby="biometric-groups"
            >
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h2 id="biometric-groups" className="text-lg font-semibold">
                        Imported punches by Biometric ID
                        {month ? ` · ${month}` : ''}
                    </h2>
                    <div className="flex flex-wrap items-center gap-2">
                        <Button
                            asChild
                            size="sm"
                            variant={!unassigned && !assigned ? 'default' : 'outline'}
                        >
                            <Link href={logsUrl} data={monthFilter}>
                                Show all IDs
                            </Link>
                        </Button>
                        <Button
                            asChild
                            size="sm"
                            variant={unassigned ? 'default' : 'outline'}
                        >
                            <Link
                                href={logsUrl}
                                data={{ ...monthFilter, unassigned: 1 }}
                            >
                                Show unassigned IDs
                            </Link>
                        </Button>
                        <Button
                            asChild
                            size="sm"
                            variant={assigned ? 'default' : 'outline'}
                        >
                            <Link
                                href={logsUrl}
                                data={{ ...monthFilter, assigned: 1 }}
                            >
                                Show assigned IDs
                            </Link>
                        </Button>
                    </div>
                </div>
                {search.toolbar}
                <div className="overflow-x-auto">
                    <table className="w-full text-left text-sm whitespace-nowrap">
                        <thead className="workspace-table-head">
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
                            {search.rows.map((group) => (
                                <tr
                                    key={group.biometric_id}
                                    className="border-b"
                                >
                                    <th scope="row" className="p-3 font-medium">
                                        {group.biometric_id}
                                    </th>
                                    <td className="p-3">
                                        {group.employee_number ? (
                                            <span className="inline-flex items-center gap-2">
                                                <PersonAvatar
                                                    name={`${group.first_name ?? ''} ${group.last_name ?? ''}`.trim()}
                                                    src={group.avatar}
                                                />
                                                {`${group.last_name}, ${group.first_name} (${group.employee_number})`}
                                            </span>
                                        ) : (
                                            'Unassigned'
                                        )}
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
                            {!search.rows.length && (
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
