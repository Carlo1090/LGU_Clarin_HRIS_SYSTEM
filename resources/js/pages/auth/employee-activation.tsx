import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { Head, useForm } from '@inertiajs/react';

export default function EmployeeActivation() {
    const form = useForm({
        employee_number: '',
        activation_code: '',
        password: '',
        password_confirmation: '',
    });

    return (
        <AuthLayout
            title="Activate employee account"
            description="Enter your Employee Number and the code provided by HR."
        >
            <Head title="Activate employee account" />
            <form
                className="space-y-4"
                onSubmit={(event) => {
                    event.preventDefault();
                    form.post('/activate', {
                        onFinish: () =>
                            form.reset(
                                'activation_code',
                                'password',
                                'password_confirmation',
                            ),
                    });
                }}
            >
                <div className="grid gap-2">
                    <Label htmlFor="employee_number">Employee Number</Label>
                    <Input
                        id="employee_number"
                        autoComplete="username"
                        required
                        value={form.data.employee_number}
                        onChange={(event) =>
                            form.setData('employee_number', event.target.value)
                        }
                    />
                    <InputError message={form.errors.employee_number} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="activation_code">Activation code</Label>
                    <Input
                        id="activation_code"
                        autoComplete="one-time-code"
                        required
                        value={form.data.activation_code}
                        onChange={(event) =>
                            form.setData('activation_code', event.target.value)
                        }
                    />
                    <InputError message={form.errors.activation_code} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="password">New passphrase</Label>
                    <Input
                        id="password"
                        type="password"
                        autoComplete="new-password"
                        minLength={12}
                        required
                        value={form.data.password}
                        onChange={(event) =>
                            form.setData('password', event.target.value)
                        }
                    />
                    <InputError message={form.errors.password} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="password_confirmation">
                        Confirm passphrase
                    </Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        autoComplete="new-password"
                        required
                        value={form.data.password_confirmation}
                        onChange={(event) =>
                            form.setData(
                                'password_confirmation',
                                event.target.value,
                            )
                        }
                    />
                </div>
                <Button type="submit" disabled={form.processing}>
                    Activate account
                </Button>
            </form>
        </AuthLayout>
    );
}
