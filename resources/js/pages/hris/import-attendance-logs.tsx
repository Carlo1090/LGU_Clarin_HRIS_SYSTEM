import { useTableSearch } from '@/components/table-search';
import PersonAvatar from '@/components/person-avatar';
import { Head, Link, useForm } from '@inertiajs/react';
import AttendanceWorkspaceHeader from '@/components/attendance-workspace-header';
import { useRef, useState, type FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type {
    AttendanceImportPreview,
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
    preview,
    timezone,
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
    preview: AttendanceImportPreview | null;
    timezone: string;
}) {
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
    const [previewReady, setPreviewReady] = useState(false);
    const form = useForm<{ file: File | null; confirm: boolean }>({
        file: null,
        confirm: false,
    });
    const monthFilter = month ? { month } : {};

    function previewUpload(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setPreviewReady(false);
        form.setData('confirm', false);
        form.post(logsUrl, { onSuccess: () => setPreviewReady(true) });
    }

    function confirmImport() {
        form.setData('confirm', true);
        form.post(logsUrl, {
            onSuccess: () => {
                setPreviewReady(false);
                form.reset();
                if (inputRef.current) {
                    inputRef.current.value = '';
                }
            },
            onFinish: () => form.setData('confirm', false),
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
            <form
                onSubmit={previewUpload}
                className="workspace-panel space-y-4 p-5"
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
                            setPreviewReady(false);
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
                        Preview import
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

            {preview && previewReady && (
                <section
                    className="space-y-4 rounded-xl border bg-card p-5"
                    aria-labelledby="preview-heading"
                >
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2
                                id="preview-heading"
                                className="text-lg font-semibold"
                            >
                                Import preview
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                Device timestamps are stored as local
                                {` ${timezone}`} time without adjustment.
                            </p>
                        </div>
                        <Button
                            type="button"
                            disabled={
                                !form.data.file ||
                                !previewReady ||
                                form.processing ||
                                preview.total_rows === 0
                            }
                            onClick={confirmImport}
                        >
                            {form.processing
                                ? 'Importing…'
                                : 'Confirm and import'}
                        </Button>
                    </div>
                    <dl className="grid grid-cols-2 gap-3 text-sm sm:grid-cols-3 lg:grid-cols-6">
                        {[
                            ['Rows read', preview.total_rows],
                            [
                                'Date range',
                                preview.date_range.from
                                    ? `${preview.date_range.from.slice(0, 10)} – ${preview.date_range.to?.slice(0, 10)}`
                                    : '—',
                            ],
                            [
                                'Distinct machine IDs',
                                preview.distinct_machine_ids,
                            ],
                            ['Unlinked IDs', preview.unlinked_ids.length],
                            ['Already imported / repeated', preview.would_skip],
                            ['Malformed rows', preview.invalid_count],
                        ].map(([label, value]) => (
                            <div
                                key={label}
                                className="rounded-lg bg-muted p-3"
                            >
                                <dt>{label}</dt>
                                <dd className="mt-1 text-lg font-semibold tabular-nums">
                                    {value}
                                </dd>
                            </div>
                        ))}
                    </dl>
                    {preview.unlinked_ids.length > 0 && (
                        <p className="text-sm text-amber-800 dark:text-amber-300">
                            Unlinked machine IDs:{' '}
                            {preview.unlinked_ids.join(', ')}. These punches
                            will still be imported.
                        </p>
                    )}
                    {preview.errors.length > 0 && (
                        <details open className="text-sm">
                            <summary className="cursor-pointer font-medium">
                                Malformed rows ({preview.invalid_count})
                            </summary>
                            <ul className="mt-2 max-h-60 space-y-1 overflow-y-auto">
                                {preview.errors.map(({ line, reason }) => (
                                    <li key={line}>
                                        Line {line} — {reason}
                                    </li>
                                ))}
                            </ul>
                        </details>
                    )}
                </section>
            )}

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
                    </div>
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
