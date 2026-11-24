import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const times = [
    ['am_in', 'AM in'],
    ['am_out', 'AM out'],
    ['pm_in', 'PM in'],
    ['pm_out', 'PM out'],
] as const;

const dateFields = [
    ['effective_from', 'Effective from'],
    ['effective_to', 'Effective to'],
] as const;

type ScheduleForm = {
    am_in: string;
    am_out: string;
    pm_in: string;
    pm_out: string;
    grace_minutes: number;
    crosses_midnight: boolean;
    effective_from: string;
    effective_to: string | null;
    reason: string;
};

const empty: ScheduleForm = {
    am_in: '',
    am_out: '',
    pm_in: '',
    pm_out: '',
    grace_minutes: 0,
    crosses_midnight: false,
    effective_from: '',
    effective_to: null,
    reason: '',
};

export default function WorkScheduleDialog({
    id,
    name,
    override = false,
}: {
    id: number;
    name: string;
    override?: boolean;
}) {
    const [open, setOpen] = useState(false);
    const [history, setHistory] = useState<(ScheduleForm & { id: number })[]>(
        [],
    );
    const [loading, setLoading] = useState(false);
    const [loadError, setLoadError] = useState('');
    const [revision, setRevision] = useState(0);
    const [selected, setSelected] = useState<number | null>(null);
    const title = override ? 'Schedule override' : 'Work schedule';
    const url = `/hris/attendance/${override ? 'employee-overrides' : 'department-schedules'}/${id}`;
    const form = useForm<ScheduleForm>({ ...empty });

    useEffect(() => {
        if (!open) return;
        const controller = new AbortController();
        setLoading(true);
        setLoadError('');
        fetch(url, {
            headers: { Accept: 'application/json' },
            signal: controller.signal,
        })
            .then((response) => {
                if (!response.ok) throw new Error();
                return response.json();
            })
            .then(setHistory)
            .catch(() => {
                if (!controller.signal.aborted) {
                    setLoadError('Could not load schedules.');
                }
            })
            .finally(() => {
                if (!controller.signal.aborted) setLoading(false);
            });
        return () => controller.abort();
    }, [open, revision, url]);

    function clear() {
        setSelected(null);
        form.setData({ ...empty });
        form.clearErrors();
    }

    function saved() {
        clear();
        setRevision((value) => value + 1);
    }

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button type="button" size="sm" variant="outline">
                    {override ? title : 'Edit schedule'}
                </Button>
            </DialogTrigger>
            <DialogContent className="max-h-[85dvh] overflow-y-auto sm:max-w-2xl">
                <DialogTitle>{title}</DialogTitle>
                <DialogDescription>{name}</DialogDescription>
                <form
                    className="grid grid-cols-2 gap-3"
                    onSubmit={(event) => {
                        event.preventDefault();
                        const options = {
                            preserveScroll: true,
                            onSuccess: saved,
                        };
                        if (selected === null) form.post(url, options);
                        else form.put(`${url}/${selected}`, options);
                    }}
                >
                    {[...times, ...dateFields].map(([key, label]) => (
                        <label className="grid gap-1 text-sm" key={key}>
                            {label}
                            <Input
                                required={key !== 'effective_to'}
                                type={
                                    key.startsWith('effective_')
                                        ? 'date'
                                        : 'time'
                                }
                                value={form.data[key] ?? ''}
                                onChange={(event) =>
                                    form.setData(key, event.target.value)
                                }
                            />
                            {form.errors[key] && (
                                <span role="alert" className="text-destructive">
                                    {form.errors[key]}
                                </span>
                            )}
                        </label>
                    ))}
                    <label className="grid gap-1 text-sm">
                        Grace minutes
                        <Input
                            required
                            type="number"
                            min={0}
                            max={1440}
                            value={form.data.grace_minutes}
                            onChange={(event) =>
                                form.setData(
                                    'grace_minutes',
                                    Number(event.target.value),
                                )
                            }
                        />
                        {form.errors.grace_minutes && (
                            <span role="alert" className="text-destructive">
                                {form.errors.grace_minutes}
                            </span>
                        )}
                    </label>
                    <label className="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            checked={form.data.crosses_midnight}
                            onChange={(event) =>
                                form.setData(
                                    'crosses_midnight',
                                    event.target.checked,
                                )
                            }
                        />
                        Crosses midnight
                    </label>
                    {form.errors.crosses_midnight && (
                        <p
                            role="alert"
                            className="col-span-2 text-sm text-destructive"
                        >
                            {form.errors.crosses_midnight}
                        </p>
                    )}
                    {override && (
                        <label className="col-span-2 grid gap-1 text-sm">
                            Reason
                            <Input
                                required
                                maxLength={255}
                                value={form.data.reason}
                                onChange={(event) =>
                                    form.setData('reason', event.target.value)
                                }
                            />
                            {form.errors.reason && (
                                <span role="alert" className="text-destructive">
                                    {form.errors.reason}
                                </span>
                            )}
                        </label>
                    )}
                    <div className="col-span-2 flex flex-wrap gap-2">
                        <Button type="submit" disabled={form.processing}>
                            {selected === null
                                ? 'Add schedule'
                                : 'Save changes'}
                        </Button>
                        {selected !== null && (
                            <>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={clear}
                                    disabled={form.processing}
                                >
                                    Cancel edit
                                </Button>
                                <Button
                                    type="button"
                                    variant="destructive"
                                    disabled={form.processing}
                                    onClick={() => {
                                        if (
                                            window.confirm(
                                                'Delete this schedule?',
                                            )
                                        ) {
                                            form.delete(`${url}/${selected}`, {
                                                preserveScroll: true,
                                                onSuccess: saved,
                                            });
                                        }
                                    }}
                                >
                                    Delete schedule
                                </Button>
                            </>
                        )}
                    </div>
                </form>
                <section className="space-y-2 border-t pt-3">
                    <h3 className="text-sm font-semibold">Schedule history</h3>
                    {loading && (
                        <p role="status" className="text-sm">
                            Loading schedules…
                        </p>
                    )}
                    {loadError && (
                        <p role="alert" className="text-sm text-destructive">
                            {loadError}
                        </p>
                    )}
                    {!loading && !loadError && history.length === 0 && (
                        <p className="text-sm text-muted-foreground">
                            No schedules yet.
                        </p>
                    )}
                    {!loading &&
                        !loadError &&
                        history.map((schedule) => (
                            <div
                                key={schedule.id}
                                className="flex flex-wrap items-center justify-between gap-2 rounded-md border p-2 text-sm"
                            >
                                <div>
                                    <p>
                                        {schedule.effective_from} to{' '}
                                        {schedule.effective_to ?? 'ongoing'}
                                    </p>
                                    <p className="text-muted-foreground">
                                        {schedule.am_in.slice(0, 5)}–
                                        {schedule.am_out.slice(0, 5)} /{' '}
                                        {schedule.pm_in.slice(0, 5)}–
                                        {schedule.pm_out.slice(0, 5)}
                                        {schedule.crosses_midnight &&
                                            ' · overnight'}
                                    </p>
                                    {schedule.reason && (
                                        <p>{schedule.reason}</p>
                                    )}
                                </div>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    disabled={form.processing}
                                    onClick={() => {
                                        setSelected(schedule.id);
                                        form.clearErrors();
                                        form.setData({
                                            am_in: schedule.am_in.slice(0, 5),
                                            am_out: schedule.am_out.slice(0, 5),
                                            pm_in: schedule.pm_in.slice(0, 5),
                                            pm_out: schedule.pm_out.slice(0, 5),
                                            grace_minutes:
                                                schedule.grace_minutes,
                                            crosses_midnight:
                                                schedule.crosses_midnight,
                                            effective_from:
                                                schedule.effective_from,
                                            effective_to: schedule.effective_to,
                                            reason: schedule.reason ?? '',
                                        });
                                    }}
                                >
                                    Edit
                                </Button>
                            </div>
                        ))}
                </section>
            </DialogContent>
        </Dialog>
    );
}
