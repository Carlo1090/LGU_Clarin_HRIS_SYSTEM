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
import { useRef, useState } from 'react';

export default function EmployeeIdentifierReveal({
    employeeId,
    field,
    label,
    revisionId,
}: {
    employeeId: number;
    field: 'tin' | 'gsis_bp_number';
    label: string;
    revisionId?: number;
}) {
    const [value, setValue] = useState<string | null>(null);
    const [error, setError] = useState(false);
    const [busy, setBusy] = useState(false);
    const pending = useRef<AbortController | null>(null);

    return (
        <Dialog
            onOpenChange={(open) => {
                if (!open) {
                    pending.current?.abort();
                    setValue(null);
                    setError(false);
                    setBusy(false);
                }
            }}
        >
            <DialogTrigger asChild>
                <Button type="button" variant="link" size="sm">
                    Reveal {label}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Reveal {label}</DialogTitle>
                    <DialogDescription>
                        Enter your current password. Each reveal is recorded.
                    </DialogDescription>
                </DialogHeader>
                {value !== null ? (
                    <p className="font-mono">{value}</p>
                ) : (
                    <form
                        className="space-y-3"
                        onSubmit={async (event) => {
                            event.preventDefault();
                            if (busy) return;
                            const form = event.currentTarget;
                            const body = new FormData(form);
                            form.reset();
                            const request = new AbortController();
                            pending.current = request;
                            const cookie = document.cookie
                                .split('; ')
                                .find((part) => part.startsWith('XSRF-TOKEN='));
                            setBusy(true);
                            setError(false);
                            try {
                                const url =
                                    revisionId === undefined
                                        ? `/hris/employees/${employeeId}/identifiers/${field}`
                                        : `/hris/profile-review/${revisionId}/reveal`;
                                const response = await fetch(url, {
                                    method: 'POST',
                                    headers: {
                                        Accept: 'application/json',
                                        'X-XSRF-TOKEN': decodeURIComponent(
                                            cookie?.slice(
                                                'XSRF-TOKEN='.length,
                                            ) ?? '',
                                        ),
                                    },
                                    body,
                                    signal: request.signal,
                                });
                                if (!response.ok)
                                    throw new Error('Reveal failed');
                                const result = await response.json();
                                if (!request.signal.aborted)
                                    setValue(
                                        result.value ?? 'No value recorded',
                                    );
                            } catch {
                                if (!request.signal.aborted) setError(true);
                            } finally {
                                if (!request.signal.aborted) setBusy(false);
                            }
                        }}
                    >
                        <label className="block space-y-1">
                            <span>Current password</span>
                            <Input
                                name="password"
                                type="password"
                                autoComplete="current-password"
                                required
                            />
                        </label>
                        {error && (
                            <p
                                role="alert"
                                className="text-sm text-destructive"
                            >
                                Could not reveal the identifier. Check your
                                password and try again.
                            </p>
                        )}
                        <Button type="submit" disabled={busy}>
                            Reveal
                        </Button>
                    </form>
                )}
            </DialogContent>
        </Dialog>
    );
}
