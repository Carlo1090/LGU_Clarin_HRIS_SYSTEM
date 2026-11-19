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
                {(!link.locked || canVerify) && (
                    <Button
                        size="sm"
                        type="submit"
                        variant="outline"
                        disabled={busy || (!canSave && !canVerify)}
                    >
                        {canVerify ? 'Verify' : 'Save'}
                    </Button>
                )}
                {link.id !== null && (
                    <Button
                        size="sm"
                        type="button"
                        variant="outline"
                        aria-label={`${link.locked ? 'Unlock' : 'Lock'} biometric ID for ${employeeNumber}`}
                        disabled={busy || dirty}
                        onClick={() =>
                            lockForm.patch(`${url}/lock`, {
                                preserveScroll: true,
                            })
                        }
                    >
                        {link.locked ? 'Unlock' : 'Lock'}
                    </Button>
                )}
            </div>
            <div id={noteId} className="text-xs text-muted-foreground">
                {error ? (
                    <p role="alert" className="text-destructive">
                        {error}
                    </p>
                ) : link.id !== null ? (
                    <>
                        {link.locked && <span>Locked · </span>}
                        <span className="capitalize">{link.status}</span>
                        {link.note && <span> · {link.note}</span>}
                    </>
                ) : null}
            </div>
        </form>
    );
}
