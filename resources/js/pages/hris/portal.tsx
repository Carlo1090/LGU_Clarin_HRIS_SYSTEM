import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { dashboard } from '@/routes';

type Cell = string | number | null;

type Props = {
    title: string;
    description: string;
    columns: string[];
    records?: {
        data: Cell[][];
        prev_page_url: string | null;
        next_page_url: string | null;
    } | null;
    profile?: Cell[] | null;
    missingEmployee?: boolean;
    leaveTypes?: { id: number; name: string }[] | null;
};

function LeaveRequestForm({ types }: { types: { id: number; name: string }[] }) {
    const form = useForm({
        leave_type_id: '',
        start_date: '',
        end_date: '',
        days_requested: '',
        reason: '',
    });

    const fieldClass = 'w-full rounded-xl border border-input bg-background px-3 py-2';

    return (
        <section aria-labelledby="leave-form-heading" className="rounded-2xl border bg-card p-6">
            <h2 id="leave-form-heading" className="text-lg font-semibold">New leave request</h2>
            {types.length ? (
                <form className="mt-4 grid gap-4 sm:grid-cols-2" onSubmit={(event) => {
                    event.preventDefault();
                    form.post('/hris/portal/my-leave-requests', {
                        preserveScroll: true,
                        onSuccess: () => form.reset(),
                    });
                }}>
                    <label className="grid gap-1 text-sm">
                        Leave type
                        <select required className={fieldClass} value={form.data.leave_type_id} onChange={(event) => form.setData('leave_type_id', event.target.value)}>
                            <option value="">Select a leave type</option>
                            {types.map((type) => <option key={type.id} value={type.id}>{type.name}</option>)}
                        </select>
                        {form.errors.leave_type_id && <span className="text-destructive">{form.errors.leave_type_id}</span>}
                    </label>
                    {([
                        ['start_date', 'Start date'],
                        ['end_date', 'End date'],
                        ['days_requested', 'Requested workdays'],
                    ] as const).map(([name, label]) => (
                        <label key={name} className="grid gap-1 text-sm">
                            {label}
                            <input
                                required
                                type={name === 'days_requested' ? 'number' : 'date'}
                                min={name === 'days_requested' ? 1 : name === 'end_date' ? form.data.start_date || undefined : undefined}
                                className={fieldClass}
                                value={form.data[name]}
                                onChange={(event) => form.setData(name, event.target.value)}
                            />
                            {form.errors[name] && <span className="text-destructive">{form.errors[name]}</span>}
                        </label>
                    ))}
                    <label className="grid gap-1 text-sm sm:col-span-2">
                        Reason
                        <textarea required maxLength={2000} rows={3} className={fieldClass} value={form.data.reason} onChange={(event) => form.setData('reason', event.target.value)} />
                        {form.errors.reason && <span className="text-destructive">{form.errors.reason}</span>}
                    </label>
                    <div className="flex flex-wrap items-center gap-3 sm:col-span-2">
                        <button type="submit" disabled={form.processing} className="rounded-full bg-primary px-5 py-2 text-sm font-medium text-primary-foreground disabled:opacity-50">
                            {form.processing ? 'Submitting…' : 'Submit request'}
                        </button>
                        {form.recentlySuccessful && <p role="status" className="text-sm text-accent-foreground">Request submitted for review.</p>}
                    </div>
                </form>
            ) : (
                <p className="mt-2 text-sm text-muted-foreground">No leave types are currently available. Contact HRMO for help.</p>
            )}
        </section>
    );
}

export default function PortalPage({
    title,
    description,
    columns,
    records,
    profile,
    missingEmployee,
    leaveTypes,
}: Props) {
    return (
        <div className="mx-auto w-full max-w-7xl space-y-6 p-4 md:p-8">
            <Head title={title} />
            <header className="rounded-3xl bg-[#142d1c] px-6 py-8 text-white sm:px-9">
                <Link href={dashboard()} className="inline-flex items-center gap-2 text-sm font-medium text-[#c2ec8b] hover:underline">
                    <ArrowLeft aria-hidden="true" className="size-4" />
                    Dashboard
                </Link>
                <h1 className="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">{title}</h1>
                <p className="mt-2 text-sm text-white/80">{description}</p>
            </header>

            {leaveTypes && <LeaveRequestForm types={leaveTypes} />}

            <section aria-label={title} className="overflow-hidden rounded-2xl border bg-card">
                {missingEmployee ? (
                    <p className="p-6 text-sm text-muted-foreground">
                        Contact HRMO to link your account to your employee record.
                    </p>
                ) : profile ? (
                    <dl className="grid gap-6 p-6 sm:grid-cols-2 lg:grid-cols-3">
                        {columns.map((column, index) => (
                            <div key={column}>
                                <dt className="text-sm text-muted-foreground">{column}</dt>
                                <dd className="mt-1 font-medium">{profile[index] ?? '—'}</dd>
                            </div>
                        ))}
                    </dl>
                ) : (
                    <>
                        <div className="overflow-x-auto">
                            <table aria-label={title} className="w-full text-left text-sm">
                                <thead className="bg-muted text-muted-foreground">
                                    <tr>
                                        {columns.map((column) => (
                                            <th key={column} scope="col" className="px-5 py-3 font-medium">{column}</th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {records?.data.map((row, index) => (
                                        <tr key={index} className="border-t hover:bg-muted/60">
                                            {row.map((value, cell) => (
                                                <td key={columns[cell]} className="px-5 py-4">{columns[cell] === 'Status' && typeof value === 'string' ? value.replaceAll('_', ' ') : value ?? '—'}</td>
                                            ))}
                                        </tr>
                                    ))}
                                    {!records?.data.length && (
                                        <tr>
                                            <td colSpan={columns.length} className="px-5 py-8 text-center text-muted-foreground">No records found.</td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                        {(records?.prev_page_url || records?.next_page_url) && (
                            <nav aria-label="Record pages" className="flex gap-3 border-t p-4">
                                {records.prev_page_url && <Link href={records.prev_page_url} className="rounded-full border px-4 py-2 text-sm hover:bg-muted">Previous</Link>}
                                {records.next_page_url && <Link href={records.next_page_url} className="rounded-full border px-4 py-2 text-sm hover:bg-muted">Next</Link>}
                            </nav>
                        )}
                    </>
                )}
            </section>
        </div>
    );
}
