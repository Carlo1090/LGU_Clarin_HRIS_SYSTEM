import InputError from '@/components/input-error';
import EmployeeIdentifierReveal from '@/components/employee-identifier-reveal';
import EmployeeAccountStatusAction from '@/components/employee-account-status-action';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import WorkspaceHeader from '@/components/workspace-header';
import { Head, Link, useForm } from '@inertiajs/react';

type ReviewRecord = {
    id: number;
    employeeId: number;
    employeeNumber: string;
    field: string;
    submitted: string | null;
    current: string | null;
    conflict: boolean;
    version: string;
};

type AuditEntry = {
    id: number;
    action: string;
    actor: string | null;
    subjectId: number | null;
    subjectType: string;
    reactivateEmployeeId: number | null;
    at: string | null;
    details: Record<string, string | number | boolean | null>;
};
type Page<T> = {
    data: T[];
    next_page_url: string | null;
    prev_page_url: string | null;
};
type Settings = {
    statuses: string[];
    eligibleFields: string[];
    na_fields: Record<string, string[]>;
    privacy_notice: string;
};

function ReviewField({ record }: { record: ReviewRecord }) {
    const form = useForm({
        decision: '',
        password: '',
        reason: '',
        version: record.version,
    });
    const sensitive =
        record.field === 'tin' || record.field === 'gsis_bp_number'
            ? record.field
            : null;

    return (
        <form
            className="workspace-panel space-y-3 p-4"
            onSubmit={(event) => {
                event.preventDefault();
                form.post(`/hris/profile-review/${record.id}`, {
                    preserveScroll: true,
                    onFinish: () => form.reset('password'),
                });
            }}
        >
            <h2 className="font-semibold">
                {record.employeeNumber} · {record.field.replaceAll('_', ' ')}
            </h2>
            {record.conflict && (
                <p className="text-sm font-medium">
                    The submitted value differs from the employee record.
                </p>
            )}
            <dl className="grid gap-2 text-sm sm:grid-cols-2">
                <div>
                    <dt>Current value</dt>
                    <dd>{record.current ?? 'None recorded'}</dd>
                </div>
                <div>
                    <dt>Submitted value</dt>
                    <dd>{record.submitted ?? 'None recorded'}</dd>
                </div>
            </dl>
            {sensitive && (
                <div className="flex gap-2">
                    {record.current && (
                        <EmployeeIdentifierReveal
                            employeeId={record.employeeId}
                            field={sensitive}
                            label="current value"
                        />
                    )}
                    {record.submitted && record.submitted !== 'N/A' && (
                        <EmployeeIdentifierReveal
                            employeeId={record.employeeId}
                            field={sensitive}
                            label="submitted value"
                            revisionId={record.id}
                        />
                    )}
                </div>
            )}
            <label className="block space-y-1 text-sm">
                <span>Decision</span>
                <select
                    className="block w-full rounded-md border bg-background px-2 py-1"
                    value={form.data.decision}
                    onChange={(event) =>
                        form.setData('decision', event.target.value)
                    }
                    required
                >
                    <option value="">Choose a decision</option>
                    <option value="use_submitted">Use submitted</option>
                    <option
                        value="keep_existing"
                        disabled={record.current === null}
                    >
                        Keep existing
                    </option>
                    <option value="return">Return for correction</option>
                </select>
            </label>
            <InputError message={form.errors.decision ?? form.errors.version} />
            {form.data.decision === 'return' && (
                <label className="block space-y-1 text-sm">
                    <span>Reason</span>
                    <textarea
                        className="w-full rounded-md border bg-background p-2"
                        value={form.data.reason}
                        onChange={(event) =>
                            form.setData('reason', event.target.value)
                        }
                        required
                        maxLength={1000}
                    />
                    <InputError message={form.errors.reason} />
                </label>
            )}
            <label className="block space-y-1 text-sm">
                <span>Current password, if not recently confirmed</span>
                <Input
                    value={form.data.password}
                    onChange={(event) =>
                        form.setData('password', event.target.value)
                    }
                    type="password"
                    autoComplete="current-password"
                />
            </label>
            <InputError message={form.errors.password} />
            <Button disabled={form.processing} type="submit">
                Save decision
            </Button>
        </form>
    );
}

function Pagination({
    previous,
    next,
}: {
    previous: string | null;
    next: string | null;
}) {
    return (
        <div className="flex gap-3">
            {previous && <Link href={previous}>Previous</Link>}
            {next && <Link href={next}>Next</Link>}
        </div>
    );
}

function ProfileSettings({ settings }: { settings: Settings }) {
    const form = useForm({
        na_fields: { ...settings.na_fields },
        privacy_notice: settings.privacy_notice,
    });

    return (
        <details className="workspace-panel p-4">
            <summary className="font-semibold">Profile settings</summary>
            <form
                className="mt-4 space-y-4"
                onSubmit={(event) => {
                    event.preventDefault();
                    form.put('/hris/profile-review/settings', {
                        preserveScroll: true,
                    });
                }}
            >
                <label className="block space-y-1 text-sm">
                    <span>Employee privacy notice</span>
                    <textarea
                        className="w-full rounded-md border bg-background p-2"
                        value={form.data.privacy_notice}
                        onChange={(event) =>
                            form.setData('privacy_notice', event.target.value)
                        }
                        maxLength={4000}
                    />
                    <InputError message={form.errors.privacy_notice} />
                </label>
                <h3 className="text-sm font-semibold">
                    Fields that may be marked Not applicable
                </h3>
                {settings.statuses.map((status) => (
                    <fieldset className="space-y-1 text-sm" key={status}>
                        <legend className="font-medium capitalize">
                            {status}
                        </legend>
                        {settings.eligibleFields.map((field) => {
                            const selected = form.data.na_fields[status] ?? [];
                            return (
                                <label
                                    key={field}
                                    className="flex items-center gap-2"
                                >
                                    <input
                                        type="checkbox"
                                        checked={selected.includes(field)}
                                        onChange={(event) => {
                                            form.setData('na_fields', {
                                                ...form.data.na_fields,
                                                [status]: event.target.checked
                                                    ? [...selected, field]
                                                    : selected.filter(
                                                          (value) =>
                                                              value !== field,
                                                      ),
                                            });
                                        }}
                                    />
                                    {field.replaceAll('_', ' ')}
                                </label>
                            );
                        })}
                    </fieldset>
                ))}
                <InputError message={form.errors.na_fields} />
                <Button type="submit" disabled={form.processing}>
                    Save profile settings
                </Button>
            </form>
        </details>
    );
}

export default function EmployeeProfileReview({
    records,
    audit,
    settings,
}: {
    records: Page<ReviewRecord>;
    audit: Page<AuditEntry>;
    settings: Settings;
}) {
    return (
        <main className="workspace-page max-w-4xl">
            <Head title="Employee profile review" />
            <WorkspaceHeader title="Employee profile review" />
            <ProfileSettings settings={settings} />
            {records.data.length === 0 && <p>No fields awaiting review.</p>}
            {records.data.map((record) => (
                <ReviewField
                    key={`${record.id}-${record.version}`}
                    record={record}
                />
            ))}
            <Pagination
                previous={records.prev_page_url}
                next={records.next_page_url}
            />
            <section className="workspace-panel space-y-3 p-4">
                <h2 className="font-semibold">Account and identifier audit</h2>
                {audit.data.length === 0 && <p>No events recorded.</p>}
                {audit.data.map((entry) => (
                    <article className="border-b py-2 text-sm" key={entry.id}>
                        <p className="font-medium capitalize">
                            {entry.action
                                .replace('employee.', '')
                                .replaceAll('_', ' ')}
                        </p>
                        <p>
                            {entry.actor ?? 'System'} · {entry.subjectType}{' '}
                            {entry.subjectId} ·{' '}
                            {entry.at &&
                                new Date(entry.at).toLocaleString('en-PH', {
                                    timeZone: 'Asia/Manila',
                                })}
                        </p>
                        {Object.entries(entry.details)
                            .filter(([, value]) => value !== null)
                            .map(([key, value]) => (
                                <p key={key}>
                                    {key.replaceAll('_', ' ')} · {String(value)}
                                </p>
                            ))}
                        {entry.reactivateEmployeeId !== null && (
                            <EmployeeAccountStatusAction
                                employeeId={entry.reactivateEmployeeId}
                                action="reactivate"
                            />
                        )}
                    </article>
                ))}
                <Pagination
                    previous={audit.prev_page_url}
                    next={audit.next_page_url}
                />
            </section>
        </main>
    );
}
