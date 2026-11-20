import { CheckCheck, Lock, LockOpen, Save } from 'lucide-react';
import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { EmployeeBiometricLink } from '@/types/attendance';

export default function BiometricIdField({
    employeeId,
    employeeNumber,
    link,
}: {
    employeeId: number;
    employeeNumber: string;
    link: EmployeeBiometricLink;
}) {
    const savedId = link.id?.toString() ?? '';
    const form = useForm({ biometric_id: savedId });
    const lockForm = useForm({ locked: !link.locked });
    const url = `/hris/employees/${employeeId}/biometric-id`;
    const busy = form.processing || lockForm.processing;
    const dirty = form.data.biometric_id !== savedId;
    const canVerify = !dirty && link.id !== null && link.status === 'pending';
    const canSave = !link.locked && dirty;
    const error = form.errors.biometric_id ?? lockForm.errors.locked;
    const noteId = `biometric-note-${employeeId}`;

    function save(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        if (!busy && (canSave || canVerify)) {
            form.transform((data) => ({ ...data, verify_only: canVerify }));
            form.put(url, { preserveScroll: true });
        }
    }

    return (
        <form onSubmit={save} className="min-w-44 space-y-1">
            <div className="flex items-center gap-2">
                <Input
                    type="text"
                    inputMode="numeric"
                    pattern="[0-9]*"
                    maxLength={10}
                    className="h-8 w-26 tabular-nums"
                    aria-label={`Biometric ID for ${employeeNumber}`}
                    aria-invalid={Boolean(error)}
                    aria-describedby={noteId}
                    disabled={busy}
                    readOnly={link.locked}
                    value={form.data.biometric_id}
                    onChange={(event) => {
                        form.setData('biometric_id', event.target.value);
                        form.clearErrors('biometric_id');
                    }}
                />
                <Button
                    size="icon"
                    type="submit"
                    variant="outline"

                    title={
                        canVerify ? 'Verify biometric ID' : 'Save biometric ID'
                    }
                    aria-label={`${canVerify ? 'Verify' : 'Save'} biometric ID for ${employeeNumber}`}
                    disabled={busy || (!canSave && !canVerify)}
                >
                    {canVerify ? (
                        <CheckCheck aria-hidden="true" />
                    ) : (
                        <Save aria-hidden="true" />
                    )}
                </Button>
                {link.id !== null && (
                    <Button
                        size="icon"
                        type="button"
                        variant="outline"

                        title={
                            link.locked
                                ? 'Unlock biometric ID'
                                : 'Lock biometric ID'
                        }
                        aria-label={`${link.locked ? 'Unlock' : 'Lock'} biometric ID for ${employeeNumber}`}
                        disabled={busy || dirty}
                        onClick={() =>
                            lockForm.patch(`${url}/lock`, {
                                preserveScroll: true,
                            })
                        }
                    >
                        {link.locked ? (
                            <LockOpen aria-hidden="true" />
                        ) : (
                            <Lock aria-hidden="true" />
                        )}
                    </Button>
                )}
            </div>
            <div
                id={noteId}
                className="max-w-72 space-y-1 text-xs text-muted-foreground"
            >
                {error ? (
                    <p role="alert" className="text-destructive">
                        {error}
                    </p>
                ) : link.id !== null ? (
                    <>
                        <p className="font-medium text-foreground">
                            {link.status === 'verified'
                                ? 'Verified'
                                : 'Pending verification'}
                            {link.locked && ' · Locked'}
                        </p>
                        <p>
                            {link.note ??
                                (link.status === 'verified'
                                    ? 'Matching attendance logs found.'
                                    : 'Select Verify to check the imported attendance logs.')}
                        </p>
                    </>
                ) : null}
            </div>
        </form>
    );
}
