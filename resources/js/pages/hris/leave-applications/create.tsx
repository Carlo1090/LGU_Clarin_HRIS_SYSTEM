import { Head, Link, useForm } from '@inertiajs/react';
import { useMemo, type FormEvent } from 'react';
import LeaveFormPreviewDialog, {
    leaveReasons,
    leaveTypes as formLeaveTypes,
    type LeaveApplicant,
} from '@/components/leave-form-preview-dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type EmployeeOption = LeaveApplicant & {
    id: number;
    employeeNumber: string;
};

type Props = {
    employees: EmployeeOption[];
    leaveTypes: { id: number; name: string }[];
    filingDate: string;
};

type FormData = {
    employee_id: string;
    leave_type_id: string;
    start_date: string;
    end_date: string;
    days_requested: string;
    reason: string;
    reason_details: string;
    commutation: boolean;
};

export default function CreateLeaveApplication({
    employees,
    leaveTypes,
    filingDate,
}: Props) {
    const form = useForm<FormData>({
        employee_id: '',
        leave_type_id: '',
        start_date: '',
        end_date: '',
        days_requested: '',
        reason: '',
        reason_details: '',
        commutation: false,
    });

    const applicant = useMemo(
        () =>
            employees.find(({ id }) => String(id) === form.data.employee_id) ??
            null,
        [employees, form.data.employee_id],
    );
    const reasonDetail = leaveReasons
        .flatMap(({ options }) => options)
        .find(({ label }) => label === form.data.reason)?.detail;
    const fieldClass =
        'w-full rounded-xl border border-input bg-background px-3 py-2';

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.clearErrors();
        if (!form.data.employee_id) {
            form.setError('employee_id', 'Select an employee.');
            return;
        }
        if (!form.data.leave_type_id) {
            form.setError('leave_type_id', 'Select a leave type.');
            return;
        }
        if (!form.data.reason) {
            form.setError('reason', 'Select a reason.');
            return;
        }
        if (reasonDetail && !form.data.reason_details.trim()) {
            form.setError('reason_details', 'Enter the requested details.');
            return;
        }

        form.post('/hris/leave-applications/on-behalf', {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Create employee leave form" />
            <main className="workspace-leave-form mx-auto w-full max-w-6xl space-y-6 p-4 md:p-8">
                <header className="flex flex-wrap items-center justify-between gap-4 rounded-3xl bg-[#142d1c] px-6 py-8 text-white sm:px-9">
                    <div>
                        <h1 className="text-3xl font-semibold tracking-tight sm:text-4xl">
                            Create leave form
                        </h1>
                        <p className="mt-2 max-w-2xl text-sm text-white/80">
                            Complete Civil Service Form No. 6 for an employee
                            without a linked system account. The request will
                            enter the usual leave review workflow.
                        </p>
                    </div>
                    <Button
                        asChild
                        variant="secondary"
                        className="rounded-full"
                    >
                        <Link href="/hris/leave-applications">
                            Back to leave applications
                        </Link>
                    </Button>
                </header>

                <form
                    onSubmit={submit}
                    className="space-y-6 rounded-2xl border bg-card p-5 sm:p-7"
                >
                    <section className="space-y-4">
                        <div>
                            <h2 className="text-lg font-semibold">
                                Applicant information
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                Only employee records without a linked login
                                account are available.
                            </p>
                        </div>
                        <label className="grid gap-1 text-sm font-medium">
                            Employee <span className="text-destructive">*</span>
                            <select
                                required
                                value={form.data.employee_id}
                                onChange={(event) => {
                                    form.setData(
                                        'employee_id',
                                        event.target.value,
                                    );
                                    form.clearErrors('employee_id');
                                }}
                                className={fieldClass}
                            >
                                <option value="">Select an employee…</option>
                                {employees.map((employee) => (
                                    <option
                                        key={employee.id}
                                        value={employee.id}
                                    >
                                        {employee.employeeNumber} —{' '}
                                        {employee.lastName},{' '}
                                        {employee.firstName}{' '}
                                        {employee.middleName ?? ''}{' '}
                                        {employee.suffix ?? ''}
                                    </option>
                                ))}
                            </select>
                            {form.errors.employee_id && (
                                <span className="font-normal text-destructive">
                                    {form.errors.employee_id}
                                </span>
                            )}
                        </label>
                        {applicant && (
                            <dl className="grid gap-4 rounded-xl bg-muted p-4 text-sm sm:grid-cols-3">
                                {[
                                    ['Office/Department', applicant.department],
                                    ['Position', applicant.position],
                                    [
                                        'Monthly salary',
                                        applicant.salary
                                            ? `₱${applicant.salary}`
                                            : null,
                                    ],
                                ].map(([label, value]) => (
                                    <div key={label}>
                                        <dt className="text-muted-foreground">
                                            {label}
                                        </dt>
                                        <dd className="mt-1 font-medium">
                                            {value || 'Not on file'}
                                        </dd>
                                    </div>
                                ))}
                            </dl>
                        )}
                    </section>

                    <section className="space-y-4 border-t pt-5">
                        <h2 className="text-lg font-semibold">
                            Details of application
                        </h2>
                        <fieldset className="space-y-3 rounded-xl border p-4">
                            <legend className="px-1 text-sm font-semibold">
                                Type of leave
                            </legend>
                            <div className="grid gap-3 md:grid-cols-2">
                                {leaveTypes.map(({ id, name }) => {
                                    const reference = formLeaveTypes.find(
                                        ([label]) =>
                                            label.toLowerCase() ===
                                            name.trim().toLowerCase(),
                                    )?.[1];
                                    return (
                                        <label
                                            key={id}
                                            className="flex items-start gap-2 text-sm"
                                        >
                                            <input
                                                required
                                                type="radio"
                                                name="leave_type_id"
                                                value={id}
                                                checked={
                                                    form.data.leave_type_id ===
                                                    String(id)
                                                }
                                                onChange={() => {
                                                    form.setData(
                                                        'leave_type_id',
                                                        String(id),
                                                    );
                                                    form.clearErrors(
                                                        'leave_type_id',
                                                    );
                                                }}
                                                className="mt-0.5 size-4 shrink-0 accent-primary"
                                            />
                                            <span>
                                                {name}
                                                {reference && (
                                                    <small className="ml-1 text-muted-foreground">
                                                        ({reference})
                                                    </small>
                                                )}
                                            </span>
                                        </label>
                                    );
                                })}
                            </div>
                            {form.errors.leave_type_id && (
                                <p className="text-sm text-destructive">
                                    {form.errors.leave_type_id}
                                </p>
                            )}
                            {!leaveTypes.length && (
                                <p className="text-sm text-destructive">
                                    No active leave types are available.
                                </p>
                            )}
                        </fieldset>

                        <div className="grid gap-4 sm:grid-cols-3">
                            {(
                                [
                                    ['start_date', 'Start date'],
                                    ['end_date', 'End date'],
                                    [
                                        'days_requested',
                                        'Working days requested',
                                    ],
                                ] as const
                            ).map(([name, label]) => (
                                <label
                                    key={name}
                                    className="grid gap-1 text-sm font-medium"
                                >
                                    {label}
                                    <Input
                                        required
                                        type={
                                            name === 'days_requested'
                                                ? 'number'
                                                : 'date'
                                        }
                                        min={
                                            name === 'days_requested'
                                                ? 1
                                                : name === 'end_date'
                                                  ? form.data.start_date ||
                                                    undefined
                                                  : undefined
                                        }
                                        value={form.data[name]}
                                        onChange={(event) => {
                                            form.setData(
                                                name,
                                                event.target.value,
                                            );
                                            form.clearErrors(name);
                                        }}
                                    />
                                    {form.errors[name] && (
                                        <span className="font-normal text-destructive">
                                            {form.errors[name]}
                                        </span>
                                    )}
                                </label>
                            ))}
                        </div>

                        <fieldset className="space-y-4 rounded-xl border p-4">
                            <legend className="px-1 text-sm font-semibold">
                                Details of leave
                            </legend>
                            <div className="grid gap-5 md:grid-cols-2">
                                {leaveReasons.map(({ title, options }) => (
                                    <div key={title} className="space-y-2">
                                        <h3 className="text-sm font-medium">
                                            {title}
                                        </h3>
                                        {options.map(({ label }) => (
                                            <label
                                                key={label}
                                                className="flex items-start gap-2 text-sm"
                                            >
                                                <input
                                                    type="radio"
                                                    name="reason"
                                                    value={label}
                                                    checked={
                                                        form.data.reason ===
                                                        label
                                                    }
                                                    onChange={() => {
                                                        form.setData({
                                                            ...form.data,
                                                            reason: label,
                                                            reason_details: '',
                                                        });
                                                        form.clearErrors(
                                                            'reason',
                                                            'reason_details',
                                                        );
                                                    }}
                                                    className="mt-0.5 size-4 shrink-0 accent-primary"
                                                />
                                                {label}
                                            </label>
                                        ))}
                                    </div>
                                ))}
                            </div>
                            {reasonDetail && (
                                <label className="grid gap-1 text-sm font-medium">
                                    {reasonDetail}
                                    <textarea
                                        required
                                        maxLength={1900}
                                        rows={3}
                                        value={form.data.reason_details}
                                        onChange={(event) => {
                                            form.setData(
                                                'reason_details',
                                                event.target.value,
                                            );
                                            form.clearErrors('reason_details');
                                        }}
                                        className={fieldClass}
                                    />
                                    {form.errors.reason_details && (
                                        <span className="font-normal text-destructive">
                                            {form.errors.reason_details}
                                        </span>
                                    )}
                                </label>
                            )}
                            {form.errors.reason && (
                                <p
                                    role="alert"
                                    className="text-sm text-destructive"
                                >
                                    {form.errors.reason}
                                </p>
                            )}
                        </fieldset>

                        <fieldset className="space-y-3 rounded-xl border p-4">
                            <legend className="px-1 text-sm font-semibold">
                                Commutation
                            </legend>
                            <div className="flex flex-wrap gap-6 text-sm">
                                {[
                                    { value: false, label: 'Not requested' },
                                    { value: true, label: 'Requested' },
                                ].map(({ value, label }) => (
                                    <label
                                        key={label}
                                        className="flex items-center gap-2"
                                    >
                                        <input
                                            type="radio"
                                            name="commutation"
                                            checked={
                                                form.data.commutation === value
                                            }
                                            onChange={() =>
                                                form.setData(
                                                    'commutation',
                                                    value,
                                                )
                                            }
                                            className="size-4 accent-primary"
                                        />
                                        {label}
                                    </label>
                                ))}
                            </div>
                        </fieldset>
                    </section>

                    <aside className="rounded-xl border border-amber-500/30 bg-amber-500/5 p-4 text-sm">
                        <h2 className="font-semibold">
                            Review and approval section
                        </h2>
                        <p className="mt-1 text-muted-foreground">
                            Leave-credit certification, recommendations, and
                            final approval are completed by the assigned
                            reviewers after this application is submitted.
                        </p>
                    </aside>

                    <div className="flex flex-wrap items-center gap-3 border-t pt-5">
                        <LeaveFormPreviewDialog
                            disabled={!applicant}
                            draft={
                                applicant
                                    ? {
                                          applicant,
                                          filingDate,
                                          leaveType:
                                              leaveTypes.find(
                                                  ({ id }) =>
                                                      String(id) ===
                                                      form.data.leave_type_id,
                                              )?.name ?? '',
                                          startDate: form.data.start_date,
                                          endDate: form.data.end_date,
                                          daysRequested:
                                              form.data.days_requested,
                                          reason: form.data.reason,
                                          reasonDetails:
                                              form.data.reason_details,
                                          commutation: form.data.commutation,
                                      }
                                    : undefined
                            }
                        />
                        <Button
                            type="submit"
                            disabled={
                                form.processing ||
                                !employees.length ||
                                !leaveTypes.length
                            }
                        >
                            {form.processing
                                ? 'Submitting…'
                                : 'Submit for review'}
                        </Button>
                        <Link
                            href="/hris/leave-applications"
                            className="text-sm text-muted-foreground underline underline-offset-4"
                        >
                            Cancel
                        </Link>
                        {!employees.length && (
                            <p className="basis-full text-sm text-muted-foreground">
                                There are no employees without a linked login
                                account.
                            </p>
                        )}
                    </div>
                </form>
            </main>
        </>
    );
}
