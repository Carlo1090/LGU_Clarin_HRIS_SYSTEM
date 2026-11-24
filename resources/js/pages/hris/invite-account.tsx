import WorkspaceHeader from '@/components/workspace-header';
import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { store } from '@/routes/hris/invitations';

export default function InviteAccount({ roles }: { roles: string[] }) {
    const form = useForm({
        employee_number: '',
        email: '',
        role: '',
        approval_reference: '',
    });

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        form.post(store.url());
    }

    return (
        <>
            <Head title="Invite an employee" />
            <main className="workspace-page workspace-invite max-w-xl">
                <WorkspaceHeader
                    title="Invite an employee"
                    description="Use an existing employee record. The employee will set a password, verify their email, and enroll in two-factor authentication."
                />
                <form
                    onSubmit={submit}
                    className="workspace-panel space-y-4 p-5"
                >
                    <div className="space-y-2">
                        <Label htmlFor="employee_number">Employee number</Label>
                        <Input
                            id="employee_number"
                            required
                            value={form.data.employee_number}
                            onChange={(event) =>
                                form.setData(
                                    'employee_number',
                                    event.target.value,
                                )
                            }
                        />
                        <InputError message={form.errors.employee_number} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="email">Work email (optional)</Label>
                        <Input
                            id="email"
                            type="email"
                            value={form.data.email}
                            onChange={(event) =>
                                form.setData('email', event.target.value)
                            }
                        />
                        <InputError message={form.errors.email} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="role">Approved role</Label>
                        <select
                            id="role"
                            required
                            value={form.data.role}
                            onChange={(event) =>
                                form.setData('role', event.target.value)
                            }
                            className="h-9 w-full rounded-md border bg-background px-3 text-sm"
                        >
                            <option value="">Select a role</option>
                            {roles.map((role) => (
                                <option key={role}>{role}</option>
                            ))}
                        </select>
                        <InputError message={form.errors.role} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="approval_reference">
                            Approval reference
                        </Label>
                        <Input
                            id="approval_reference"
                            required
                            maxLength={100}
                            value={form.data.approval_reference}
                            onChange={(event) =>
                                form.setData(
                                    'approval_reference',
                                    event.target.value,
                                )
                            }
                        />
                        <InputError message={form.errors.approval_reference} />
                    </div>
                    <p className="text-sm text-muted-foreground">
                        An email address is needed to deliver the one-time
                        password setup link.
                    </p>
                    <Button disabled={form.processing}>
                        Send account setup email
                    </Button>
                </form>
            </main>
        </>
    );
}
