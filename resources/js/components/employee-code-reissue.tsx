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

export default function EmployeeCodeReissue({
    employeeId,
    canRevokeNow,
}: {
    employeeId: number;
    canRevokeNow: boolean;
}) {
    const [open, setOpen] = useState(false);
    const form = useForm({ password: '', revoke_now: false });

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
                    Reissue code
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Reissue an activation code</DialogTitle>
                    <DialogDescription>
                        Confirm the employee’s identity in person before issuing
                        a code.
                    </DialogDescription>
                </DialogHeader>
                <form
                    className="space-y-4"
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.post(
                            `/hris/employees/${employeeId}/activation-code`,
                            {
                                onFinish: () => form.reset('password'),
                                onSuccess: () => setOpen(false),
                            },
                        );
                    }}
                >
                    <label className="block space-y-1 text-sm">
                        <span>Current password, if not recently confirmed</span>
                        <Input
                            type="password"
                            autoComplete="current-password"
                            value={form.data.password}
                            onChange={(event) =>
                                form.setData('password', event.target.value)
                            }
                        />
                    </label>
                    <InputError message={form.errors.password} />
                    {canRevokeNow && (
                        <label className="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                checked={form.data.revoke_now}
                                onChange={(event) =>
                                    form.setData(
                                        'revoke_now',
                                        event.target.checked,
                                    )
                                }
                            />
                            Revoke sessions now for suspected compromise
                        </label>
                    )}
                    <Button type="submit" disabled={form.processing}>
                        Issue replacement code
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}
