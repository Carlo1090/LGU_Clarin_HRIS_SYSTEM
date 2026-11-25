import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function EmployeeAccountStatusAction({
    employeeId,
    action,
}: {
    employeeId: number;
    action: 'deactivate' | 'reactivate';
}) {
    const [open, setOpen] = useState(false);
    const form = useForm({ password: '', reason: '' });
    const deactivating = action === 'deactivate';
    const title = deactivating ? 'Deactivate account' : 'Reactivate account';

    return (
        <Dialog
            open={open}
            onOpenChange={(next) => {
                setOpen(next);
                if (!next) form.reset();
            }}
        >
            <DialogTrigger asChild>
                <Button type="button" variant="outline" size="sm">
                    {title}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    <DialogDescription>
                        {deactivating
                            ? 'The account will be disabled and its sessions revoked.'
                            : 'Existing sessions will be revoked. The employee must use a new code and set a new password.'}
                    </DialogDescription>
                </DialogHeader>
                <form
                    className="space-y-3"
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.post(`/hris/employees/${employeeId}/${action}`, {
                            onFinish: () => form.reset('password'),
                            onSuccess: () => setOpen(false),
                        });
                    }}
                >
                    {deactivating && (
                        <label className="block space-y-1 text-sm">
                            <span>Reason</span>
                            <textarea
                                className="w-full rounded-md border bg-background p-2"
                                required
                                maxLength={1000}
                                value={form.data.reason}
                                onChange={(event) =>
                                    form.setData('reason', event.target.value)
                                }
                            />
                            <InputError message={form.errors.reason} />
                        </label>
                    )}
                    <label className="block space-y-1 text-sm">
                        <span>Current password</span>
                        <Input
                            type="password"
                            autoComplete="current-password"
                            required
                            value={form.data.password}
                            onChange={(event) =>
                                form.setData('password', event.target.value)
                            }
                        />
                        <InputError message={form.errors.password} />
                    </label>
                    <Button
                        type="submit"
                        disabled={form.processing}
                        variant={deactivating ? 'destructive' : 'default'}
                    >
                        {title}
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}
