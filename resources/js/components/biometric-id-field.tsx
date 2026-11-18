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
    const error = form.errors.biometric_id;
    const noteId = `biometric-note-${employeeId}`;

    function save(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        form.put(`/hris/employees/${employeeId}/biometric-id`, {
            preserveScroll: true,
        });
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
                    disabled={form.processing}
                    value={form.data.biometric_id}
                    onChange={(event) => {
                        form.setData('biometric_id', event.target.value);
                        form.clearErrors('biometric_id');
                    }}
                />
                <Button
                    size="sm"
                    type="submit"
                    variant="outline"
                    disabled={
                        form.processing || form.data.biometric_id === savedId
                    }
                >
                    {form.processing ? 'Saving…' : 'Save'}
                </Button>
            </div>
            <div id={noteId} className="text-xs text-muted-foreground">
                {error ? (
                    <p role="alert" className="text-destructive">
                        {error}
                    </p>
                ) : link.id !== null ? (
                    <>
                        <span className="capitalize">{link.status}</span>
                        {link.note && <span> · {link.note}</span>}
                    </>
                ) : null}
            </div>
        </form>
    );
}
