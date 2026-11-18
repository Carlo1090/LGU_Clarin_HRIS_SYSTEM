import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type Scope = 'municipal' | 'office' | 'employee';
type Schedule = {
    id: number;
    department_id: number | null;
    employee_id: number | null;
    effective_from: string;
    effective_until: string | null;
    workdays: number[];
    am_in: string;
    am_out: string;
    pm_in: string;
    pm_out: string;
};
type Holiday = { id: number; date: string; name: string };
type Office = { id: number; name: string };
type Employee = {
    id: number;
    employee_number: string;
    first_name: string;
    last_name: string;
};

const calendarUrl = '/hris/attendance/work-calendar';
const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
const timeFields = [
    ['am_in', 'AM In'],
    ['am_out', 'AM Out'],
    ['pm_in', 'PM In'],
    ['pm_out', 'PM Out'],
] as const;
const selectClass = 'h-9 rounded-md border border-input bg-background px-3';

export default function WorkCalendar({
    departments,
    employees,
    schedules,
    holidays,
}: {
    departments: Office[];
    employees: Employee[];
    schedules: Schedule[];
    holidays: Holiday[];
}) {
    const [editing, setEditing] = useState(false);
    const schedule = useForm({
        scope: 'municipal' as Scope,
        department_id: '',
        employee_id: '',
        effective_from: '',
        effective_until: '',
        workdays: [1, 2, 3, 4, 5],
        am_in: '08:00',
        am_out: '12:00',
        pm_in: '13:00',
        pm_out: '17:00',
    });
    const holiday = useForm({ date: '', name: '' });

    function submitSchedule(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        schedule.post(`${calendarUrl}/schedules`, { preserveScroll: true });
    }

    function changeSchedule(row: Schedule) {
        setEditing(true);
        schedule.clearErrors();
        schedule.setData({
            scope: row.employee_id
                ? 'employee'
                : row.department_id
                  ? 'office'
                  : 'municipal',
            department_id: row.department_id?.toString() ?? '',
            employee_id: row.employee_id?.toString() ?? '',
            effective_from: row.effective_from,
            effective_until: row.effective_until ?? '',
            workdays: row.workdays,
            am_in: row.am_in,
            am_out: row.am_out,
            pm_in: row.pm_in,
            pm_out: row.pm_out,
        });
        document
            .getElementById('schedule-editor')
            ?.scrollIntoView({ block: 'start' });
    }

    function scheduleName(row: Schedule) {
        if (row.employee_id) {
            const employee = employees.find(({ id }) => id === row.employee_id);
            return employee
                ? `${employee.last_name}, ${employee.first_name} (${employee.employee_number})`
                : 'Employee schedule';
        }
        return row.department_id
            ? (departments.find(({ id }) => id === row.department_id)?.name ??
                  'Office schedule')
            : 'Municipal schedule';
    }

    return (
        <main className="mx-auto w-full max-w-6xl space-y-6 p-4 md:p-8">
            <Head title="Work Calendar" />
            <header className="space-y-2">
                <Link href="/hris/attendance" className="text-sm underline">
                    Attendance Records
                </Link>
                <h1 className="text-2xl font-semibold">Work Calendar</h1>
                <p className="text-sm text-muted-foreground">
                    Employee schedules take priority over office schedules, then
                    the municipal schedule. Each rule applies from its effective
                    date.
                </p>
            </header>
            <section
                id="schedule-editor"
                className="scroll-mt-20 space-y-4 rounded-xl border bg-card p-5"
            >
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <h2 className="text-lg font-semibold">
                        {editing ? 'Edit schedule' : 'Add schedule'}
                    </h2>
                    {editing && (
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => {
                                setEditing(false);
                                schedule.resetAndClearErrors();
                            }}
                        >
                            New schedule
                        </Button>
                    )}
                </div>
                <form
                    onSubmit={submitSchedule}
                    className="grid gap-4 sm:grid-cols-2"
                >
                    <label className="grid gap-1 text-sm font-medium">
                        Applies to
                        <select
                            value={schedule.data.scope}
                            disabled={editing}
                            onChange={(event) =>
                                schedule.setData(
                                    'scope',
                                    event.target.value as Scope,
                                )
                            }
                            className={selectClass}
                        >
                            <option value="municipal">Municipality</option>
                            <option value="office">Office exception</option>
                            <option value="employee">Employee exception</option>
                        </select>
                    </label>
                    {schedule.data.scope === 'office' && (
                        <label className="grid gap-1 text-sm font-medium">
                            Office
                            <select
                                required
                                disabled={editing}
                                value={schedule.data.department_id}
                                onChange={(event) =>
                                    schedule.setData(
                                        'department_id',
                                        event.target.value,
                                    )
                                }
                                className={selectClass}
                            >
                                <option value="">Choose an office</option>
                                {departments.map((office) => (
                                    <option key={office.id} value={office.id}>
                                        {office.name}
                                    </option>
                                ))}
                            </select>
                        </label>
                    )}
                    {schedule.data.scope === 'employee' && (
                        <label className="grid gap-1 text-sm font-medium">
                            Employee
                            <select
                                required
                                disabled={editing}
                                value={schedule.data.employee_id}
                                onChange={(event) =>
                                    schedule.setData(
                                        'employee_id',
                                        event.target.value,
                                    )
                                }
                                className={selectClass}
                            >
                                <option value="">Choose an employee</option>
                                {employees.map((employee) => (
                                    <option
                                        key={employee.id}
                                        value={employee.id}
                                    >
                                        {employee.last_name},{' '}
                                        {employee.first_name} (
                                        {employee.employee_number})
                                    </option>
                                ))}
                            </select>
                        </label>
                    )}
                    <div className="grid grid-cols-2 gap-3 sm:col-span-2">
                        <label className="grid gap-1 text-sm font-medium">
                            Effective from
                            <Input
                                type="date"
                                required
                                disabled={editing}
                                value={schedule.data.effective_from}
                                onChange={(event) =>
                                    schedule.setData(
                                        'effective_from',
                                        event.target.value,
                                    )
                                }
                            />
                        </label>
                        <label className="grid gap-1 text-sm font-medium">
                            Effective until (optional)
                            <Input
                                type="date"
                                min={schedule.data.effective_from || undefined}
                                value={schedule.data.effective_until}
                                onChange={(event) =>
                                    schedule.setData(
                                        'effective_until',
                                        event.target.value,
                                    )
                                }
                            />
                        </label>
                    </div>
                    <fieldset className="space-y-2 sm:col-span-2">
                        <legend className="text-sm font-medium">
                            Workdays
                        </legend>
                        <div className="flex flex-wrap gap-3">
                            {days.map((day, index) => (
                                <label
                                    key={day}
                                    className="flex items-center gap-1.5 text-sm"
                                >
                                    <input
                                        type="checkbox"
                                        checked={schedule.data.workdays.includes(
                                            index + 1,
                                        )}
                                        onChange={(event) => {
                                            schedule.setData(
                                                'workdays',
                                                event.target.checked
                                                    ? [
                                                          ...schedule.data
                                                              .workdays,
                                                          index + 1,
                                                      ]
                                                    : schedule.data.workdays.filter(
                                                          (value) =>
                                                              value !==
                                                              index + 1,
                                                      ),
                                            );
                                        }}
                                    />
                                    {day}
                                </label>
                            ))}
                        </div>
                    </fieldset>
                    <div className="grid grid-cols-2 gap-3 sm:col-span-2 sm:grid-cols-4">
                        {timeFields.map(([key, label]) => (
                            <label
                                key={key}
                                className="grid gap-1 text-sm font-medium"
                            >
                                {label}
                                <Input
                                    type="time"
                                    required
                                    value={schedule.data[key]}
                                    onChange={(event) =>
                                        schedule.setData(
                                            key,
                                            event.target.value,
                                        )
                                    }
                                />
                            </label>
                        ))}
                    </div>
                    {Object.keys(schedule.errors).length > 0 && (
                        <ul
                            role="alert"
                            className="text-sm text-destructive sm:col-span-2"
                        >
                            {Object.entries(schedule.errors).map(
                                ([key, message]) => (
                                    <li key={key}>{message}</li>
                                ),
                            )}
                        </ul>
                    )}
                    <div>
                        <Button type="submit" disabled={schedule.processing}>
                            Save schedule
                        </Button>
                    </div>
                </form>
                <div className="divide-y border-t">
                    {schedules.length === 0 && (
                        <p className="py-3 text-sm text-muted-foreground">
                            No work schedules are configured.
                        </p>
                    )}
                    {schedules.map((row) => (
                        <div
                            key={row.id}
                            className="flex flex-wrap items-center justify-between gap-3 py-3 text-sm"
                        >
                            <div>
                                <p className="font-semibold">
                                    {scheduleName(row)}
                                </p>
                                <p className="text-muted-foreground">
                                    {row.effective_from}
                                    {row.effective_until
                                        ? ` to ${row.effective_until}`
                                        : ' onward'}
                                    {' · '}
                                    {row.workdays
                                        .map((day) => days[day - 1])
                                        .join(', ')}
                                    {' · '}
                                    {row.am_in}–{row.am_out} / {row.pm_in}–
                                    {row.pm_out}
                                </p>
                            </div>
                            <div className="flex gap-2">
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    onClick={() => changeSchedule(row)}
                                >
                                    Edit
                                </Button>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="ghost"
                                    onClick={() => {
                                        if (
                                            window.confirm(
                                                'Remove this work schedule?',
                                            )
                                        ) {
                                            router.delete(
                                                `${calendarUrl}/schedules/${row.id}`,
                                                { preserveScroll: true },
                                            );
                                        }
                                    }}
                                >
                                    Remove
                                </Button>
                            </div>
                        </div>
                    ))}
                </div>
            </section>

            <section className="space-y-4 rounded-xl border bg-card p-5">
                <h2 className="text-lg font-semibold">Municipal holidays</h2>
                <form
                    className="flex flex-wrap items-end gap-3"
                    onSubmit={(event) => {
                        event.preventDefault();
                        holiday.post(`${calendarUrl}/holidays`, {
                            preserveScroll: true,
                            onSuccess: () => holiday.reset(),
                        });
                    }}
                >
                    <label className="grid gap-1 text-sm font-medium">
                        Date
                        <Input
                            type="date"
                            required
                            value={holiday.data.date}
                            onChange={(event) =>
                                holiday.setData('date', event.target.value)
                            }
                        />
                    </label>
                    <label className="grid gap-1 text-sm font-medium">
                        Holiday name
                        <Input
                            required
                            maxLength={255}
                            value={holiday.data.name}
                            onChange={(event) =>
                                holiday.setData('name', event.target.value)
                            }
                        />
                    </label>
                    <Button type="submit" disabled={holiday.processing}>
                        Save holiday
                    </Button>
                    {(holiday.errors.date || holiday.errors.name) && (
                        <p
                            role="alert"
                            className="basis-full text-sm text-destructive"
                        >
                            {holiday.errors.date || holiday.errors.name}
                        </p>
                    )}
                </form>
                <div className="divide-y border-t">
                    {holidays.length === 0 && (
                        <p className="py-3 text-sm text-muted-foreground">
                            No holidays are configured.
                        </p>
                    )}
                    {holidays.map((row) => (
                        <div
                            key={row.id}
                            className="flex flex-wrap items-center justify-between gap-3 py-3 text-sm"
                        >
                            <p>
                                <strong>{row.date}</strong> · {row.name}
                            </p>
                            <Button
                                type="button"
                                size="sm"
                                variant="ghost"
                                onClick={() => {
                                    if (
                                        window.confirm(
                                            'Remove this holiday from the calendar?',
                                        )
                                    ) {
                                        router.delete(
                                            `${calendarUrl}/holidays/${row.id}`,
                                            { preserveScroll: true },
                                        );
                                    }
                                }}
                            >
                                Remove
                            </Button>
                        </div>
                    ))}
                </div>
            </section>
        </main>
    );
}
